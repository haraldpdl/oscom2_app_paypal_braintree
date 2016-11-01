<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Hooks\Admin\Orders;

use OSC\OM\HTML;
use OSC\OM\OSCOM;
use OSC\OM\Registry;

use OSC\Apps\PayPal\Braintree\Braintree as BraintreeApp;

class Action implements \OSC\OM\Modules\HooksInterface
{
    protected $app;
    protected $ms;
    protected $server = 1;

    public function __construct()
    {
        if (!Registry::exists('Braintree')) {
            Registry::set('Braintree', new BraintreeApp());
        }

        $this->app = Registry::get('Braintree');

        $this->ms = Registry::get('MessageStack');

        $this->app->loadDefinitions('Module/Hooks/Admin/Orders/Action');
    }

    public function execute()
    {
        if (isset($_GET['tabaction'])) {
            $Qstatus = $this->app->db->prepare('select comments from :table_orders_status_history where orders_id = :orders_id and orders_status_id = :orders_status_id and comments like "%Transaction ID:%" order by date_added limit 1');
            $Qstatus->bindInt(':orders_id', $_GET['oID']);
            $Qstatus->bindInt(':orders_status_id', OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID);
            $Qstatus->execute();

            if ($Qstatus->fetch() !== false) {
                $pp = [];

                foreach (explode("\n", $Qstatus->value('comments')) as $s) {
                    if (!empty($s) && (strpos($s, ':') !== false)) {
                        $entry = explode(':', $s, 2);

                        $pp[trim($entry[0])] = trim($entry[1]);
                    }
                }

                if (isset($pp['Transaction ID'])) {
                    $Qorder = $this->app->db->prepare('select o.orders_id, o.payment_method, o.currency, o.currency_value, ot.value as total from :table_orders o, :table_orders_total ot where o.orders_id = :orders_id and o.orders_id = ot.orders_id and ot.class = "ot_total"');
                    $Qorder->bindInt(':orders_id', $_GET['oID']);
                    $Qorder->execute();

                    if ((isset($pp['Server']) && ($pp['Server'] !== 'production')) || (strpos($Qorder->value('payment_method'), 'Sandbox') !== false)) {
                        $this->server = 0;
                    }

                    switch ($_GET['tabaction']) {
                        case 'getTransactionDetails':
                            $this->getTransactionDetails($pp, $Qorder->toArray());
                            break;

                        case 'doCapture':
                            $this->doCapture($pp, $Qorder->toArray());
                            break;

                        case 'doVoid':
                            $this->doVoid($pp, $Qorder->toArray());
                            break;

                        case 'doRefund':
                            $this->doRefund($pp, $Qorder->toArray());
                            break;
                    }

                    OSCOM::redirect('orders.php', 'page=' . $_GET['page'] . '&oID=' . $_GET['oID'] . '&action=edit#section_status_history_content');
                }
            }
        }
    }

    protected function getTransactionDetails($comments, $order)
    {
        $result = null;

        $this->app->setupCredentials($this->server === 1 ? 'live' : 'sandbox');

        $error = false;

        try {
            $response = \Braintree\Transaction::find($comments['Transaction ID']);
        } catch (\Exception $e) {
            $error = true;
        }

        if (($error === false) && is_object($response) && (get_class($response) == 'Braintree\\Transaction') && isset($response->id) && ($response->id == $comments['Transaction ID'])) {
            $result = 'Transaction ID: ' . HTML::sanitize($response->id) . "\n";

            if (($response->paymentInstrumentType == 'credit_card') && isset($comments['3D Secure'])) {
                if (isset($response->threeDSecureInfo) && is_object($response->threeDSecureInfo)) {
                    $result .= '3D Secure: ' . HTML::sanitize($response->threeDSecureInfo->status . ' (Liability Shifted: ' . ($response->threeDSecureInfo->liabilityShifted === true ? 'true' : 'false') . ')') . "\n";
                } else {
                    $result .= '3D Secure: ** MISSING **' . "\n";
                }
            }

            $result .= 'Payment Status: ' . HTML::sanitize($response->status) . "\n" .
                       'Payment Type: ' . HTML::sanitize($response->paymentInstrumentType) . "\n";

            if ($this->server === 0) {
                $result .= 'Server: sandbox' . "\n";
            }

            $result .= 'Status History:';

            foreach ($response->statusHistory as $sh) {
                $sh->timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));

                $result .= "\n" . HTML::sanitize('[' . $sh->timestamp->format('Y-m-d H:i:s T') . '] ' . $sh->status . ' ' . $sh->amount . ' ' . $response->currencyIsoCode);
            }
        }

        if (!empty($result)) {
            $sql_data_array = [
                'orders_id' => (int)$order['orders_id'],
                'orders_status_id' => OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => $result
            ];

            $this->app->db->save('orders_status_history', $sql_data_array);

            $this->ms->add($this->app->getDef('ms_success_getTransactionDetails'), 'success');
        } else {
            $this->ms->add($this->app->getDef('ms_error_getTransactionDetails'), 'error');
        }
    }

    protected function doCapture($comments, $order)
    {
        $capture_value = $this->app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);

        if ($this->app->formatCurrencyRaw($_POST['btCaptureAmount'], $order['currency'], 1) < $capture_value) {
            $capture_value = $this->app->formatCurrencyRaw($_POST['btCaptureAmount'], $order['currency'], 1);
        }

        $this->app->setupCredentials($this->server === 1 ? 'live' : 'sandbox');

        $error = false;

        try {
            $response = \Braintree\Transaction::submitForSettlement($comments['Transaction ID'], $capture_value);
        } catch (\Exception $e) {
            $error = true;
        }

        if (($error === false) && is_object($response) && (get_class($response) == 'Braintree\\Result\\Successful') && ($response->success === true) && (get_class($response->transaction) == 'Braintree\\Transaction') && isset($response->transaction->id) && ($response->transaction->id == $comments['Transaction ID'])) {
            $result = 'Braintree App: Capture (' . $capture_value . ')' . "\n" .
                      'Transaction ID: ' . HTML::sanitize($response->transaction->id) . "\n" .
                      'Payment Status: ' . HTML::sanitize($response->transaction->status) . "\n" .
                      'Status History:';


            foreach ($response->transaction->statusHistory as $sh) {
                $sh->timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));

                $result .= "\n" . HTML::sanitize('[' . $sh->timestamp->format('Y-m-d H:i:s T') . '] ' . $sh->status . ' ' . $sh->amount . ' ' . $response->transaction->currencyIsoCode);
            }

            $sql_data_array = [
                'orders_id' => (int)$order['orders_id'],
                'orders_status_id' => OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => $result
            ];

            $this->app->db->save('orders_status_history', $sql_data_array);

// immediately settle sandbox transactions
            if (strpos($order['payment_method'], 'Sandbox') !== false) {
                $error = false;

                try {
                    $response = \Braintree\Test\Transaction::settle($comments['Transaction ID']);
                } catch (\Exception $e) {
                    $error = true;
                }

                if (($error === false) && is_object($response) && (get_class($response) == 'Braintree\\Transaction') && isset($response->id) && ($response->id == $comments['Transaction ID'])) {
                    $result = 'Braintree App: Settled (' . HTML::sanitize($response->amount) . ')' . "\n" .
                              'Transaction ID: ' . HTML::sanitize($response->id) . "\n" .
                              'Payment Status: ' . HTML::sanitize($response->status) . "\n" .
                              'Status History:';

                    foreach ($response->statusHistory as $sh) {
                        $sh->timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));

                        $result .= "\n" . HTML::sanitize('[' . $sh->timestamp->format('Y-m-d H:i:s T') . '] ' . $sh->status . ' ' . $sh->amount . ' ' . $response->currencyIsoCode);
                    }

                    $sql_data_array = [
                        'orders_id' => (int)$order['orders_id'],
                        'orders_status_id' => OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID,
                        'date_added' => 'now()',
                        'customer_notified' => '0',
                        'comments' => $result
                    ];

                    $this->app->db->save('orders_status_history', $sql_data_array);
                }
            }

            $this->ms->add($this->app->getDef('ms_success_doCapture'), 'success');
        } else {
            $this->ms->add($this->app->getDef('ms_error_doCapture'), 'error');
        }
    }

    protected function doVoid($comments, $order)
    {
        $this->app->setupCredentials($this->server === 1 ? 'live' : 'sandbox');

        $error = false;

        try {
            $response = \Braintree\Transaction::void($comments['Transaction ID']);
        } catch (\Exception $e) {
            $error = true;
        }

        if (($error === false) && is_object($response) && (get_class($response) == 'Braintree\\Result\\Successful') && ($response->success === true) && (get_class($response->transaction) == 'Braintree\\Transaction') && isset($response->transaction->id) && ($response->transaction->id == $comments['Transaction ID'])) {
            $result = 'Braintree App: Void (' . HTML::sanitize($response->transaction->amount) . ')' . "\n" .
                      'Transaction ID: ' . HTML::sanitize($response->transaction->id) . "\n" .
                      'Payment Status: ' . HTML::sanitize($response->transaction->status) . "\n" .
                      'Status History:';


            foreach ($response->transaction->statusHistory as $sh) {
                $sh->timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));

                $result .= "\n" . HTML::sanitize('[' . $sh->timestamp->format('Y-m-d H:i:s T') . '] ' . $sh->status . ' ' . $sh->amount . ' ' . $response->transaction->currencyIsoCode);
            }

            $sql_data_array = [
                'orders_id' => (int)$order['orders_id'],
                'orders_status_id' => OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => $result
            ];

            $this->app->db->save('orders_status_history', $sql_data_array);

            $this->ms->add($this->app->getDef('ms_success_doVoid'), 'success');
        } else {
            $this->ms->add($this->app->getDef('ms_error_doVoid'), 'error');
        }
    }

    protected function doRefund($comments, $order)
    {
        $refund_value = (isset($_POST['btRefundAmount']) && !empty($_POST['btRefundAmount'])) ? $this->app->formatCurrencyRaw($_POST['btRefundAmount'], $order['currency'], 1) : null;

        $this->app->setupCredentials($this->server === 1 ? 'live' : 'sandbox');

        $error = false;

        try {
            $response = \Braintree\Transaction::refund($comments['Transaction ID'], $refund_value);
        } catch (\Exception $e) {
            $error = true;
        }

        if (($error === false) && is_object($response) && (get_class($response) == 'Braintree\\Result\\Successful') && ($response->success === true) && (get_class($response->transaction) == 'Braintree\\Transaction') && isset($response->transaction->refundedTransactionId) && ($response->transaction->refundedTransactionId == $comments['Transaction ID'])) {
            $result = 'Braintree App: Refund (' . HTML::sanitize($response->transaction->amount) . ')' . "\n" .
                      'Credit Transaction ID: ' . HTML::sanitize($response->transaction->id) . "\n" .
                      'Transaction ID: ' . HTML::sanitize($response->transaction->refundedTransactionId) . "\n" .
                      'Payment Status: ' . HTML::sanitize($response->transaction->status) . "\n" .
                      'Status History:';


            foreach ($response->transaction->statusHistory as $sh) {
                $sh->timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));

                $result .= "\n" . HTML::sanitize('[' . $sh->timestamp->format('Y-m-d H:i:s T') . '] ' . $sh->status . ' ' . $sh->amount . ' ' . $response->transaction->currencyIsoCode);
            }

            $sql_data_array = [
                'orders_id' => (int)$order['orders_id'],
                'orders_status_id' => OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID,
                'date_added' => 'now()',
                'customer_notified' => '0',
                'comments' => $result
            ];

            $this->app->db->save('orders_status_history', $sql_data_array);

            $this->ms->add($this->app->getDef('ms_success_doRefund', [
                'refund_amount' => HTML::sanitize($response->transaction->amount)
            ]), 'success');
        } else {
            $this->ms->add($this->app->getDef('ms_error_doRefund'), 'error');
        }
    }
}

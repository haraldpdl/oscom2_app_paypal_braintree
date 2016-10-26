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

class PageTab implements \OSC\OM\Modules\HooksInterface
{
    protected $app;

    public function __construct()
    {
        if (!Registry::exists('Braintree')) {
            Registry::set('Braintree', new BraintreeApp());
        }

        $this->app = Registry::get('Braintree');
    }

    public function display()
    {
        global $oID;

        if (!defined('OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID')) {
            return false;
        }

        $this->app->loadDefinitions('Module/Hooks/Admin/Orders/PageTab');

        $output = '';

        $status = [];

        $Qc = $this->app->db->prepare('select comments from :table_orders_status_history where orders_id = :orders_id and orders_status_id = :orders_status_id and comments like "Transaction ID:%" order by date_added desc limit 1');
        $Qc->bindInt(':orders_id', $oID);
        $Qc->bindInt(':orders_status_id', OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID);
        $Qc->execute();

        if ($Qc->fetch() !== false) {
            foreach (explode("\n", $Qc->value('comments')) as $s) {
                if (!empty($s) && (strpos($s, ':') !== false) && (substr($s, 0, 1) !== '[')) {
                    $entry = explode(':', $s, 2);

                    $key = trim($entry[0]);
                    $value = trim($entry[1]);

                    if ((strlen($key) > 0) && (strlen($value) > 0)) {
                        $status[$key] = $value;
                    }
                }
            }

            if (isset($status['Transaction ID'])) {
                $Qorder = $this->app->db->prepare('select o.orders_id, o.payment_method, o.currency, o.currency_value, ot.value as total from :table_orders o, :table_orders_total ot where o.orders_id = :orders_id and o.orders_id = ot.orders_id and ot.class = "ot_total"');
                $Qorder->bindInt(':orders_id', $oID);
                $Qorder->execute();

                $pp_server = (strpos(strtolower($Qorder->value('payment_method')), 'sandbox') !== false) ? 'sandbox' : 'live';

                $info_button = HTML::button($this->app->getDef('button_details'), 'fa fa-info-circle', OSCOM::link('orders.php', 'page=' . $_GET['page'] . '&oID=' . $oID . '&action=edit&tabaction=getTransactionDetails'), null, 'btn-primary');
                $capture_button = $this->getCaptureButton($status, $Qorder->toArray());
                $void_button = $this->getVoidButton($status, $Qorder->toArray());
                $refund_button = $this->getRefundButton($status, $Qorder->toArray());
                $braintree_button = HTML::button($this->app->getDef('button_view_at_braintree'), 'fa fa-external-link', 'https://' . ($pp_server == 'sandbox' ? 'sandbox.' : '') . 'braintreegateway.com/merchants/' . ($pp_server == 'sandbox' ? OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_MERCHANT_ID : OSCOM_APP_PAYPAL_BRAINTREE_MERCHANT_ID) . '/transactions/' . $status['Transaction ID'], ['newwindow' => true], 'btn-info');

                $tab_title = addslashes($this->app->getDef('tab_title'));

                $output = <<<EOD
<div id="section_paypalAppBraintree_content" class="tab-pane oscom-m-top-15">
  {$info_button} {$capture_button} {$void_button} {$refund_button} {$braintree_button}
</div>

<script>
$('#section_paypalAppBraintree_content').appendTo('#orderTabs .tab-content');
$('#orderTabs .nav-tabs').append('<li><a data-target="#section_paypalAppBraintree_content" data-toggle="tab">{$tab_title}</a></li>');
</script>
EOD;

            }
        }

        return $output;
    }

    protected function getCaptureButton($status, $order)
    {
        $output = '';

        if ($status['Payment Status'] == 'authorized') {
            $Qv = $this->app->db->prepare('select comments from :table_orders_status_history where orders_id = :orders_id and orders_status_id = :orders_status_id and comments like "Braintree App: Void (%" limit 1');
            $Qv->bindInt(':orders_id', $order['orders_id']);
            $Qv->bindInt(':orders_status_id', OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID);
            $Qv->execute();

            if ($Qv->fetch() === false) {
                $Qc = $this->app->db->prepare('select comments from :table_orders_status_history where orders_id = :orders_id and orders_status_id = :orders_status_id and comments like "Braintree App: Capture (%" limit 1');
                $Qc->bindInt(':orders_id', $order['orders_id']);
                $Qc->bindInt(':orders_status_id', OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID);
                $Qc->execute();

                if ($Qc->fetch() === false) {
                    $output .= HTML::button($this->app->getDef('button_dialog_capture'), 'fa fa-check-circle', '#', ['params' => 'data-button="braintreeButtonDoCapture"'], 'btn-success');

                    $dialog_title = HTML::outputProtected($this->app->getDef('dialog_capture_title'));
                    $dialog_body = $this->app->getDef('dialog_capture_body');
                    $field_amount_title = $this->app->getDef('dialog_capture_amount_field_title');
                    $capture_link = OSCOM::link('orders.php', 'page=' . $_GET['page'] . '&oID=' . $order['orders_id'] . '&action=edit&tabaction=doCapture');
                    $capture_currency = $order['currency'];
                    $capture_total = $this->app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);
                    $dialog_button_capture = $this->app->getDef('dialog_capture_button_capture');
                    $dialog_button_cancel = $this->app->getDef('dialog_capture_button_cancel');

                    $output .= <<<EOD
<div id="braintree-dialog-capture" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">{$dialog_title}</h4>
      </div>

      <div class="modal-body">
        <form id="btCaptureForm" action="{$capture_link}" method="post">
          <p>{$dialog_body}</p>

          <div class="form-group">
            <label for="btCaptureAmount">{$field_amount_title}</label>

            <div class="input-group">
              <div class="input-group-addon">
                {$capture_currency}
              </div>

              <input type="text" name="btCaptureAmount" value="{$capture_total}" id="btCaptureAmount" class="form-control" />
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button id="braintree-dialog-capture-button" type="button" class="btn btn-success">{$dialog_button_capture}</button>
        <button type="button" class="btn btn-link" data-dismiss="modal">{$dialog_button_cancel}</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function() {
  $('a[data-button="braintreeButtonDoCapture"]').click(function(e) {
    e.preventDefault();

    $('#braintree-dialog-capture').modal('show');
  });

  $('#braintree-dialog-capture-button').on('click', function() {
    $('#btCaptureForm').submit();
  });
});
</script>
EOD;
                }
            }
        }

        return $output;
    }

    protected function getVoidButton($status, $order)
    {
        $output = '';

        $Qs = $this->app->db->prepare('select comments from :table_orders_status_history where orders_id = :orders_id and orders_status_id = :orders_status_id and comments like "%Payment Status:%" order by date_added desc limit 1');
        $Qs->bindInt(':orders_id', $order['orders_id']);
        $Qs->bindInt(':orders_status_id', OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID);
        $Qs->execute();

        if ($Qs->fetch() !== false) {
            $last_status = [];

            foreach (explode("\n", $Qs->value('comments')) as $s) {
                if (!empty($s) && (strpos($s, ':') !== false) && (substr($s, 0, 1) !== '[')) {
                    $entry = explode(':', $s, 2);

                    $key = trim($entry[0]);
                    $value = trim($entry[1]);

                    if ((strlen($key) > 0) && (strlen($value) > 0)) {
                        $last_status[$key] = $value;
                    }
                }
            }

            if (($last_status['Payment Status'] == 'authorized') || ($last_status['Payment Status'] == 'submitted_for_settlement')) {
                $Qv = $this->app->db->prepare('select comments from :table_orders_status_history where orders_id = :orders_id and orders_status_id = :orders_status_id and (comments like "Braintree App: Void (%" or comments like "Braintree App: Refund (%") limit 1');
                $Qv->bindInt(':orders_id', $order['orders_id']);
                $Qv->bindInt(':orders_status_id', OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID);
                $Qv->execute();

                if ($Qv->fetch() === false) {
                    $output .= HTML::button($this->app->getDef('button_dialog_void'), 'fa fa-times-circle', '#', ['params' => 'data-button="braintreeButtonDoVoid"'], 'btn-warning');

                    $dialog_title = HTML::outputProtected($this->app->getDef('dialog_void_title'));
                    $dialog_body = $this->app->getDef('dialog_void_body');
                    $void_link = OSCOM::link('orders.php', 'page=' . $_GET['page'] . '&oID=' . $order['orders_id'] . '&action=edit&tabaction=doVoid');
                    $dialog_button_void = $this->app->getDef('dialog_void_button_void');
                    $dialog_button_cancel = $this->app->getDef('dialog_void_button_cancel');

                    $output .= <<<EOD
<div id="braintree-dialog-void" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">{$dialog_title}</h4>
      </div>

      <div class="modal-body">
        <p>{$dialog_body}</p>
      </div>

      <div class="modal-footer">
        <button id="braintree-dialog-void-button" type="button" class="btn btn-success">{$dialog_button_void}</button>
        <button type="button" class="btn btn-link" data-dismiss="modal">{$dialog_button_cancel}</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function() {
  $('a[data-button="braintreeButtonDoVoid"]').click(function(e) {
    e.preventDefault();

    $('#braintree-dialog-void').modal('show');
  });

  $('#braintree-dialog-void-button').on('click', function() {
    window.location = '{$void_link}';
  });
});
</script>
EOD;
                }
            }
        }

        return $output;
    }

    protected function getRefundButton($status, $order)
    {
        $output = '';

        $Qs = $this->app->db->prepare('select comments from :table_orders_status_history where orders_id = :orders_id and orders_status_id = :orders_status_id and comments not like "Braintree App: Refund (%" and comments like "%Payment Status:%" order by date_added desc limit 1');
        $Qs->bindInt(':orders_id', $order['orders_id']);
        $Qs->bindInt(':orders_status_id', OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID);
        $Qs->execute();

        if ($Qs->fetch() !== false) {
            $last_status = [];

            foreach (explode("\n", $Qs->value('comments')) as $s) {
                if (!empty($s) && (strpos($s, ':') !== false) && (substr($s, 0, 1) !== '[')) {
                    $entry = explode(':', $s, 2);

                    $key = trim($entry[0]);
                    $value = trim($entry[1]);

                    if ((strlen($key) > 0) && (strlen($value) > 0)) {
                        $last_status[$key] = $value;
                    }
                }
            }

            if (($last_status['Payment Status'] == 'settled') || ($last_status['Payment Status'] == 'settling')) {
                $refund_total = $this->app->formatCurrencyRaw($order['total'], $order['currency'], $order['currency_value']);

                $Qr = $this->app->db->prepare('select comments from :table_orders_status_history where orders_id = :orders_id and orders_status_id = :orders_status_id and comments like "Braintree App: Refund (%"');
                $Qr->bindInt(':orders_id', $order['orders_id']);
                $Qr->bindInt(':orders_status_id', OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID);
                $Qr->execute();

                while ($Qr->fetch()) {
                    if (preg_match('/^Braintree App\: Refund \(([0-9\.]+)\)\n/', $Qr->value('comments'), $r_matches)) {
                        $refund_total -= $this->app->formatCurrencyRaw($r_matches[1], $order['currency'], 1);
                    }
                }

                if ($refund_total > 0) {
                    $output .= HTML::button($this->app->getDef('button_dialog_refund'), 'fa fa-minus-circle', '#', ['params' => 'data-button="braintreeButtonDoRefund"'], 'btn-danger');

                    $dialog_title = HTML::outputProtected($this->app->getDef('dialog_refund_title'));
                    $dialog_body = $this->app->getDef('dialog_refund_body');
                    $field_amount_title = $this->app->getDef('dialog_refund_amount_field_title');
                    $refund_link = OSCOM::link('orders.php', 'page=' . $_GET['page'] . '&oID=' . $_GET['oID'] . '&action=edit&tabaction=doRefund');
                    $refund_currency = $order['currency'];
                    $dialog_button_refund = $this->app->getDef('dialog_refund_button_refund');
                    $dialog_button_cancel = $this->app->getDef('dialog_refund_button_cancel');

                    $output .= <<<EOD
<div id="braintree-dialog-refund" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">{$dialog_title}</h4>
      </div>

      <div class="modal-body">
        <form id="btRefundForm" action="{$refund_link}" method="post">
          <p>{$dialog_body}</p>

          <div class="form-group">
            <label for="btRefundAmount">{$field_amount_title}</label>

            <div class="input-group">
              <div class="input-group-addon">
                {$refund_currency}
              </div>

              <input type="text" name="btRefundAmount" value="{$refund_total}" id="btRefundAmount" class="form-control" />
            </div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button id="braintree-dialog-refund-button" type="button" class="btn btn-success">{$dialog_button_refund}</button>
        <button type="button" class="btn btn-link" data-dismiss="modal">{$dialog_button_cancel}</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function() {
  $('a[data-button="braintreeButtonDoRefund"]').click(function(e) {
    e.preventDefault();

    $('#braintree-dialog-refund').modal('show');
  });

  $('#braintree-dialog-refund-button').on('click', function() {
    $('#btRefundForm').submit();
  });
});
</script>
EOD;
                }
            }
        }

        return $output;
    }
}

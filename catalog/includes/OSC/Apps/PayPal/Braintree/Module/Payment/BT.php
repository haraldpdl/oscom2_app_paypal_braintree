<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Payment;

use OSC\OM\Hash;
use OSC\OM\HTML;
use OSC\OM\OSCOM;
use OSC\OM\Registry;

use OSC\Apps\PayPal\Braintree\Braintree as BraintreeApp;

class BT implements \OSC\OM\Modules\PaymentInterface {
    public $code;
    public $title;
    public $description;
    public $enabled;
    public $app;
    protected $payment_types = [];

    public function __construct()
    {
        global $PHP_SELF, $order;

        if (!Registry::exists('Braintree')) {
            Registry::set('Braintree', new BraintreeApp());
        }

        $this->app = Registry::get('Braintree');

        $this->app->loadDefinitionFile('Module/Payment/BT.txt');

        $this->code = 'BT';
        $this->title = $this->app->getDef('title');
        $this->public_title = $this->app->getDef('public_title');
        $this->description = '<div align="center">' . HTML::button($this->app->getDef('button_app_legacy'), null, $this->app->link('Configuration'), null, 'btn-primary') . '</div>';
        $this->sort_order = defined('OSCOM_APP_PAYPAL_BT_SORT_ORDER') ? OSCOM_APP_PAYPAL_BT_SORT_ORDER : 0;
        $this->enabled = defined('OSCOM_APP_PAYPAL_BT_STATUS') && in_array(OSCOM_APP_PAYPAL_BT_STATUS, ['1', '0']) ? true : false;
        $this->order_status = defined('OSCOM_APP_PAYPAL_BT_ORDER_STATUS_ID') && ((int)OSCOM_APP_PAYPAL_BT_ORDER_STATUS_ID > 0) ? (int)OSCOM_APP_PAYPAL_BT_ORDER_STATUS_ID : 0;

        if (defined('OSCOM_APP_PAYPAL_BT_STATUS')) {
            if (OSCOM_APP_PAYPAL_BT_STATUS == '0') {
                $this->title .= ' [Sandbox]';
                $this->public_title .= ' (' . $this->app->vendor . '\\' . $this->app->code . '\\' . $this->code . '; Sandbox)';
            }
        }

        $braintree_error = null;

        $requiredExtensions = [
            'xmlwriter',
            'openssl',
            'dom',
            'hash',
            'curl'
        ];

        $exts = [];

        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $exts[] = $ext;
            }
        }

        if (!empty($exts)) {
            $braintree_error = $this->app->getDef('error_php_extensions', [':extensions' => implode('<br />', $exts)]);
        }

        if (!isset($braintree_error) && defined('OSCOM_APP_PAYPAL_BT_STATUS')) {
            if (OSCOM_APP_PAYPAL_BT_STATUS === '1') {
                if (empty(OSCOM_APP_PAYPAL_BRAINTREE_MERCHANT_ID) || empty(OSCOM_APP_PAYPAL_BRAINTREE_PUBLIC_KEY) || empty(OSCOM_APP_PAYPAL_BRAINTREE_PRIVATE_KEY)) {
                    $braintree_error = $this->app->getDef('error_credentials');
                }
            } elseif (OSCOM_APP_PAYPAL_BT_STATUS === '0') {
                if (empty(OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_MERCHANT_ID) || empty(OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_PUBLIC_KEY) || empty(OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_PRIVATE_KEY)) {
                    $braintree_error = $this->app->getDef('error_credentials');
                }
            }
        }

        if (!isset($braintree_error) && defined('OSCOM_APP_PAYPAL_BT_STATUS')) {
            $ma_error = true;

            $currencies_ma = (OSCOM_APP_PAYPAL_BT_STATUS === '1') ? OSCOM_APP_PAYPAL_BRAINTREE_CURRENCIES_MA : OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_CURRENCIES_MA;

            if (!empty($currencies_ma)) {
                $mas = explode(';', $currencies_ma);

                foreach ($mas as $a) {
                    $ac = explode(':', $a, 2);

                    if (isset($ac[1]) && ($ac[1] == DEFAULT_CURRENCY)) {
                        $ma_error = false;
                        break;
                    }
                }
            }

            if ($ma_error === true) {
                $braintree_error = $this->app->getDef('error_merchant_account_currency', [':currency' => DEFAULT_CURRENCY]);
            }
        }

        if (!isset($braintree_error)) {
            $this->api_version = '[SDK ' . \Braintree\Version::get() . ']';
        } else {
            $this->description = '<div class="alert alert-warning">' . $braintree_error . '</div>' . $this->description;

            $this->enabled = false;
        }

        if (defined('OSCOM_APP_PAYPAL_BT_PAYMENT_TYPES') && !empty(OSCOM_APP_PAYPAL_BT_PAYMENT_TYPES)) {
            $this->payment_types = explode(';', OSCOM_APP_PAYPAL_BT_PAYMENT_TYPES);
        }

        if (isset($order) && is_object($order)) {
            $this->update_status();
        }

// When changing the shipping address due to no shipping rates being available, head straight to the checkout confirmation page
        if ((basename($PHP_SELF) == 'checkout_payment.php') && isset($_SESSION['appPayPalBtRightTurn'])) {
            unset($_SESSION['appPayPalBtRightTurn']);

            if (isset($_SESSION['payment']) && ($_SESSION['payment'] == $this->app->vendor . '\\' . $this->app->code . '\\' . $this->code)) {
                OSCOM::redirect('checkout_confirmation.php', '', 'SSL');
            }
        }
    }

    public function update_status()
    {
        global $order;

        if (($this->enabled == true) && ((int)OSCOM_APP_PAYPAL_BT_ZONE > 0)) {
            $check_flag = false;

            $Qcheck = $this->app->db->get('zones_to_geo_zones', 'zone_id', [
                'geo_zone_id' => OSCOM_APP_PAYPAL_BT_ZONE,
                'zone_country_id' => $order->delivery['country']['id']
            ], 'zone_id');

            while ($Qcheck->fetch()) {
                if ($Qcheck->valueInt('zone_id') < 1) {
                    $check_flag = true;
                    break;
                } elseif ($Qcheck->valueInt('zone_id') == $order->delivery['zone_id']) {
                    $check_flag = true;
                    break;
                }
            }

            if ($check_flag === false) {
                $this->enabled = false;
            }
        }
    }

    public function checkout_initialization_method()
    {
        global $oscTemplate;

        $content = '';

        if ($this->isPaymentTypeAccepted('paypal')) {
            $this->app->setupCredentials();

            $clientToken = \Braintree\ClientToken::generate([
                'merchantAccountId' => $this->getMerchantAccountId($_SESSION['currency'])
            ]);

            $amount = $this->app->formatCurrencyRaw($_SESSION['cart']->show_total(), $_SESSION['currency']);
            $currency = $_SESSION['currency'];

            $formUrl = OSCOM::link('index.php', 'order&callback&paypal&bt', 'SSL');
            $formHash = $_SESSION['appPayPalBtFormHash'] = Hash::getRandomString(16);

            $enableShippingAddress = in_array($_SESSION['cart']->get_content_type(), ['physical', 'mixed']) ? 'true' : 'false';

            $oscTemplate->addBlock('<script src="https://js.braintreegateway.com/web/3.2.0/js/client.min.js"></script><script src="https://js.braintreegateway.com/web/3.2.0/js/paypal.min.js"></script>', 'footer_scripts');

            $content = <<<EOD
<script src="https://www.paypalobjects.com/api/button.js?"
  data-merchant="braintree"
  data-id="paypal-button"
  data-button="checkout"
  data-color="blue"
  data-size="medium"
  data-shape="pill"
  data-button_type="submit"
  data-button_disabled="false"
></script>
<script>
$(function() {
  var paypalButton = document.querySelector('.paypal-button');

  braintree.client.create({
    authorization: '{$clientToken}'
  }, function (clientErr, clientInstance) {
    if (clientErr) {
      return;
    }

    braintree.paypal.create({
      client: clientInstance
    }, function (paypalErr, paypalInstance) {
      if (paypalErr) {
        return;
      }

      paypalButton.removeAttribute('disabled');

      paypalButton.addEventListener('click', function (event) {
        event.preventDefault();

        paypalInstance.tokenize({
          flow: 'checkout',
          amount: {$amount},
          currency: '{$currency}',
          enableShippingAddress: {$enableShippingAddress},
          enableBillingAddress: true,
        }, function (tokenizeErr, payload) {
          if (tokenizeErr) {
            return;
          }

          paypalButton.setAttribute('disabled', true);

          $('<form>').attr({
            name: 'bt_checkout_paypal',
            action: '{$formUrl}',
            method: 'post'
          }).insertAfter('form[name="cart_quantity"]');

          $('<input>').attr({
            type: 'hidden',
            name: 'bt_paypal_form_hash',
            value: '{$formHash}'
          }).appendTo('form[name="bt_checkout_paypal"]');

          $('<input>').attr({
            type: 'hidden',
            name: 'bt_paypal_nonce',
            value: payload.nonce
          }).appendTo('form[name="bt_checkout_paypal"]');

          $('form[name="bt_checkout_paypal"]').submit();
        });
      }, false);
    });
  });
});
</script>
EOD;
        }

        return $content;
    }

    public function javascript_validation()
    {
        return false;
    }

    public function selection()
    {
        if (isset($_SESSION['appPayPalBtNonce'])) {
            unset($_SESSION['appPayPalBtNonce']);
        }

        return [
            'id' => $this->app->vendor . '\\' . $this->app->code . '\\' . $this->code,
            'module' => $this->public_title
        ];
    }

    public function pre_confirmation_check()
    {
        global $oscTemplate;

        if (!isset($_SESSION['appPayPalBtNonce']) && (OSCOM_APP_PAYPAL_BT_ENTRY_FORM === '3')) {
            $oscTemplate->addBlock($this->getSubmitCardDetailsJavascript(), 'footer_scripts');
        }
    }

    public function confirmation()
    {
        global $oscTemplate, $order, $currencies;

        if (isset($_SESSION['appPayPalBtNonce'])) {
            return false;
        }

        if (OSCOM_APP_PAYPAL_BT_ENTRY_FORM === '3') {
            $content = '<h2>Card Payment</h2>
                        <div id="btCardStatus" class="alert alert-danger hidden"></div>';

            if (!$this->isValidCurrency($_SESSION['currency'])) {
                $content .= sprintf(MODULE_PAYMENT_BRAINTREE_CC_CURRENCY_CHARGE, $currencies->format($order->info['total'], true, DEFAULT_CURRENCY), DEFAULT_CURRENCY, $_SESSION['currency']);
            }

            $default_token = null;

            if ((OSCOM_APP_PAYPAL_BT_CC_TOKENS == '1') || (OSCOM_APP_PAYPAL_BT_CC_TOKENS == '2')) {
                $Qtokens = $this->app->db->get('customers_braintree_tokens', [
                    'id',
                    'card_type',
                    'number_filtered',
                    'expiry_date'
                ], [
                    'customers_id' => (int)$_SESSION['customer_id']
                ], 'date_added');

                if ($Qtokens->fetch() !== false) {
                    $tokens = [];

                    do {
                        $default_token = $Qtokens->valueInt('id');

                        $tokens[] = [
                            'id' => $Qtokens->valueInt('id'),
                            'text' => $Qtokens->value('card_type') . ' ending in ' . $Qtokens->value('number_filtered') . ' (expiry date ' . substr($Qtokens->value('expiry_date'), 0, 2) . '/' . substr($Qtokens->value('expiry_date'), 2) . ')'
                        ];
                    } while ($Qtokens->fetch());

                    $tokens[] = [
                        'id' => '0',
                        'text' => $this->app->getDef('token_new_card')
                    ];

                    $content .= '<div class="row" style="margin-bottom: 10px;">
                                   <div class="col-sm-12">
                                     <div class="input-group">
                                       <span class="input-group-addon"><span class="fa fa-database fa-fw"></span></span>' .
                                       HTML::selectField('braintree_cards', $tokens, $default_token, 'id="braintree_cards"') . '
                                     </div>
                                   </div>';

                    if (OSCOM_APP_PAYPAL_BT_VERIFY_CVV == '1') {
                        $content .= '<div id="braintree_stored_card_cvv" class="col-sm-6">
                                       <div class="input-group">
                                         <span class="input-group-addon"><span class="fa fa-lock fa-fw"></span></span>
                                         <div id="card-token-cvv" class="form-control"></div>
                                         <span class="input-group-addon cardSecurityCodeInfo"><span class="fa fa-info-circle text-primary"></span></span>
                                       </div>
                                     </div>';
                    }

                    $content .= '</div>';

                }
            }

            $content .= '<div id="braintree_new_card">
                           <div class="row" style="margin-bottom: 10px;">
                             <div class="col-sm-6">
                               <div class="input-group">
                                 <span class="input-group-addon"><span class="fa fa-credit-card fa-fw"></span></span>
                                 <div id="card-number" class="form-control"></div>
                               </div>
                             </div>

                             <div class="col-sm-6">
                               <div class="input-group">
                                 <span class="input-group-addon"><span class="fa fa-calendar fa-fw"></span></span>
                                 <div id="card-exp" class="form-control"></div>
                               </div>
                             </div>';

            if ((OSCOM_APP_PAYPAL_BT_VERIFY_CVV == '1') || (OSCOM_APP_PAYPAL_BT_VERIFY_CVV == '2')) {
                $content .= '<div class="col-sm-6">
                               <div class="input-group">
                                 <span class="input-group-addon"><span class="fa fa-lock fa-fw"></span></span>
                                 <div id="card-cvv" class="form-control"></div>
                                 <span class="input-group-addon cardSecurityCodeInfo"><span class="fa fa-info-circle text-primary"></span></span>
                               </div>
                             </div>';
            }

            if (OSCOM_APP_PAYPAL_BT_CC_TOKENS == '1') {
                $content .= '<div class="col-sm-6">
                               <div class="checkbox">
                                 <label>' . HTML::checkboxField('cc_save', 'true', true) . ' ' . $this->app->getDef('save_new_card') . '</label>
                               </div>
                             </div>';
            }

            $content .= '  </div>
                         </div>';

            $content .= <<<EOD
<input type="hidden" name="payment_method_nonce">

<div id="bt3dsmodal" class="modal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-body"></div>
    </div>
  </div>
</div>

<script>
if ($('#braintree_cards').length > 0) {
  $('#braintree_new_card').hide();
}
</script>
EOD;

            if ((OSCOM_APP_PAYPAL_BT_VERIFY_CVV == '1') || (OSCOM_APP_PAYPAL_BT_VERIFY_CVV == '2')) {
                $content .= <<<EOD
<script>
$(function() {
  $('.cardSecurityCodeInfo').popover({
    container: 'body',
    trigger: 'hover',
    content: 'The Security Code is a 3 or 4 digit code commonly found on the back of the payment card where the card is signed.'
  });
});
</script>
EOD;
            }
        } else {
            $this->app->setupCredentials();

            $clientToken = \Braintree\ClientToken::generate([
                'merchantAccountId' => $this->getMerchantAccountId($_SESSION['currency'])
            ]);

            $amount = $this->app->formatCurrencyRaw($order->info['total'], $_SESSION['currency']);
            $currency = $_SESSION['currency'];

            $oscTemplate->addBlock('<script src="https://js.braintreegateway.com/v2/braintree.js"></script>', 'footer_scripts');

            $content = <<<EOD
<script>
$(function() {
  braintree.setup('{$clientToken}', 'dropin', {
    container: 'checkout_bt',
    paypal: {
      singleUse: true,
      amount: {$amount},
      currency: '{$currency}'
    }
  });
});
</script>

<div id="checkout_bt"></div>
EOD;

        }

        if (isset($content)) {
            $confirmation = [
                'content' => $content
            ];

            return $confirmation;
        }

        return false;
    }

    public function process_button()
    {
        return false;
    }

    public function before_process()
    {
        global $order, $braintree_result, $braintree_token, $messageStack;

        $braintree_token = null;
        $braintree_error = null;

        if (!isset($_SESSION['appPayPalBtNonce']) && ((OSCOM_APP_PAYPAL_BT_CC_TOKENS == '1') || (OSCOM_APP_PAYPAL_BT_CC_TOKENS == '2'))) {
            if (isset($_POST['braintree_cards']) && is_numeric($_POST['braintree_cards']) && ($_POST['braintree_cards'] > 0)) {
                $Qtoken = $this->app->db->get('customers_braintree_tokens', 'braintree_token', [
                    'id' => (int)$_POST['braintree_cards'],
                    'customers_id' => (int)$_SESSION['customer_id']
                ]);

                if ($Qtoken->fetch()) {
                    $braintree_token = $Qtoken->value('braintree_token');
                }
            }
        }

        $braintree_result = null;

        $this->app->setupCredentials();

        $currency = $this->getTransactionCurrency();

        if (isset($_SESSION['appPayPalBtNonce'])) {
            $data = [
                'amount' => $this->app->formatCurrencyRaw($order->info['total'], $currency),
                'paymentMethodNonce' => $_SESSION['appPayPalBtNonce']
            ];
        } else {
            $data = [
                'paymentMethodNonce' => $_POST['payment_method_nonce'],
                'amount' => $this->app->formatCurrencyRaw($order->info['total'], $currency),
                'merchantAccountId' => $this->getMerchantAccountId($currency),
                'customer' => [
                    'firstName' => $order->customer['firstname'],
                    'lastName' => $order->customer['lastname'],
                    'company' => $order->customer['company'],
                    'phone' => $order->customer['telephone'],
                    'email' => $order->customer['email_address']
                ],
                'billing' => [
                    'firstName' => $order->billing['firstname'],
                    'lastName' => $order->billing['lastname'],
                    'company' => $order->billing['company'],
                    'streetAddress' => $order->billing['street_address'],
                    'extendedAddress' => $order->billing['suburb'],
                    'locality' => $order->billing['city'],
                    'region' => tep_get_zone_code($order->billing['country']['id'], $order->billing['zone_id'], $order->billing['state']),
                    'postalCode' => $order->billing['postcode'],
                    'countryCodeAlpha2' => $order->billing['country']['iso_code_2']
                ],
                'options' => []
            ];

            if (OSCOM_APP_PAYPAL_BT_TRANSACTION_METHOD == '1') {
                $data['options']['submitForSettlement'] = true;
            }

            if (!isset($braintree_token)) {
                if (((OSCOM_APP_PAYPAL_BT_CC_TOKENS == '1') && isset($_POST['cc_save']) && ($_POST['cc_save'] == 'true')) || (OSCOM_APP_PAYPAL_BT_CC_TOKENS === '2')) {
                    $data['options']['storeInVaultOnSuccess'] = true;
                }
            }
        }

        if ($order->content_type != 'virtual') {
            $data['shipping'] = [
                'firstName' => $order->delivery['firstname'],
                'lastName' => $order->delivery['lastname'],
                'company' => $order->delivery['company'],
                'streetAddress' => $order->delivery['street_address'],
                'extendedAddress' => $order->delivery['suburb'],
                'locality' => $order->delivery['city'],
                'region' => tep_get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], $order->delivery['state']),
                'postalCode' => $order->delivery['postcode'],
                'countryCodeAlpha2' => $order->delivery['country']['iso_code_2']
            ];
        }

        $data['channel'] = 'OSCOM_' . OSCOM::getVersion() . '-' . $this->app->getVersion() . '-' . \Braintree\Version::get();

        $error = false;

        try {
            $braintree_result = \Braintree\Transaction::sale($data);
        } catch (\Exception $e) {
            $error = true;
        }

        if (($error === false) && ($braintree_result->success === true)) {
            return true;
        }

        $message = 'There was a problem processing the payment card. Please verify the card information and try again.';

        if (isset($braintree_result->transaction)) {
            if (isset($braintree_result->transaction->gatewayRejectionReason)) {
                switch ($braintree_result->transaction->gatewayRejectionReason) {
                    case 'cvv':
                        $message = 'There was a problem processing the Security Code of the card. Please verify the Security Code and try again.';
                        break;

                    case 'avs':
                        $message = 'There was a problem processing the card with the billing address. Please verify the billing address and try again.';
                        break;

                    case 'avs_and_cvv':
                        $message = 'There was a problem processing the card with the billing address and Security Code. Please verify the billing address and the Security Code of the card and try again.';
                        break;
                }
            }
        }

        $messageStack->add_session('checkout_confirmation', $message);

        OSCOM::redirect('checkout_confirmation.php', null, 'SSL');
    }

    public function after_process()
    {
        global $insert_id, $braintree_result, $braintree_token;

        $status_comment = [
            'Transaction ID: ' . HTML::sanitize($braintree_result->transaction->id),
            'Payment Status: ' . HTML::sanitize($braintree_result->transaction->status),
            'Payment Type: ' . HTML::sanitize($braintree_result->transaction->paymentInstrumentType)
        ];

        if (\Braintree\Configuration::$global->getEnvironment() !== 'production') {
            $status_comment[] = 'Server: ' . HTML::sanitize(\Braintree\Configuration::$global->getEnvironment());
        }

        if (!isset($_SESSION['appPayPalBtNonce']) && (((OSCOM_APP_PAYPAL_BT_CC_TOKENS == '1') && isset($_POST['cc_save']) && ($_POST['cc_save'] == 'true')) || (OSCOM_APP_PAYPAL_BT_CC_TOKENS === '2')) && !isset($braintree_token) && isset($braintree_result->transaction->creditCard['token'])) {
            $token = $braintree_result->transaction->creditCard['token'];
            $type = $braintree_result->transaction->creditCard['cardType'];
            $number = $braintree_result->transaction->creditCard['last4'];
            $expiry = $braintree_result->transaction->creditCard['expirationMonth'] . $braintree_result->transaction->creditCard['expirationYear'];

            $Qcheck = $this->app->db->get('customers_braintree_tokens', 'id', [
                'customers_id' => (int)$_SESSION['customer_id'],
                'braintree_token' => $token
            ]);

            if ($Qcheck->fetch() === false) {
                $this->app->db->save('customers_braintree_tokens', [
                    'customers_id' => (int)$_SESSION['customer_id'],
                    'braintree_token' => $token,
                    'card_type' => $type,
                    'number_filtered' => $number,
                    'expiry_date' => $expiry,
                    'date_added' => 'now()'
                ]);
            }

            $status_comment[] = 'Token Created: Yes';
        } elseif (isset($braintree_token)) {
            $status_comment[] = 'Token Used: Yes';
        }

        $this->app->db->save('orders_status_history', [
            'orders_id' => $insert_id,
            'orders_status_id' => OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID,
            'date_added' => 'now()',
            'customer_notified' => '0',
            'comments' => implode("\n", $status_comment)
        ]);

        if (isset($_SESSION['appPayPalBtNonce'])) {
            unset($_SESSION['appPayPalBtNonce']);
        }

        if (isset($_SESSION['appPayPalBtFormHash'])) {
            unset($_SESSION['appPayPalBtFormHash']);
        }
    }

    function get_error() {
      return false;
    }

    public function check()
    {
        return defined('OSCOM_APP_PAYPAL_BT_STATUS') && (trim(OSCOM_APP_PAYPAL_BT_STATUS) != '');
    }

    public function install()
    {
        $this->app->redirect('Configuration');
    }

    public function remove()
    {
        $this->app->redirect('Configuration');
    }

    public function keys()
    {
        return [
            'OSCOM_APP_PAYPAL_BT_SORT_ORDER'
        ];
    }

    public function getTransactionCurrency()
    {
        return $this->isValidCurrency($_SESSION['currency']) ? $_SESSION['currency'] : DEFAULT_CURRENCY;
    }

    public function getMerchantAccountId($currency)
    {
        $currencies_ma = (OSCOM_APP_PAYPAL_BT_STATUS === '1') ? OSCOM_APP_PAYPAL_BRAINTREE_CURRENCIES_MA : OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_CURRENCIES_MA;

        foreach (explode(';', $currencies_ma) as $ma) {
            list($a, $c) = explode(':', $ma);

            if ($c == $currency) {
                return $a;
            }
        }

        return '';
    }

    public function isValidCurrency($currency)
    {
        global $currencies;

        $currencies_ma = (OSCOM_APP_PAYPAL_BT_STATUS === '1') ? OSCOM_APP_PAYPAL_BRAINTREE_CURRENCIES_MA : OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_CURRENCIES_MA;

        foreach (explode(';', $currencies_ma) as $combo) {
            list($id, $c) = explode(':', $combo);

            if ($c == $currency) {
                return $currencies->is_set($c);
            }
        }

        return false;
    }

    function deleteCard($token, $token_id) {
        $this->app->setupCredentials();

        try {
            \Braintree\CreditCard::delete($token);
        } catch (\Exception $e) {
        }

        $result = $this->app->db->delete('customers_braintree_tokens', [
            'id' => (int)$token_id,
            'customers_id' => (int)$_SESSION['customer_id'],
            'braintree_token' => $token
        ]);

        return $result === 1;
    }

    public function getSubmitCardDetailsJavascript()
    {
        global $oscTemplate, $order;

        $this->app->setupCredentials();

        $clientToken = \Braintree\ClientToken::generate([
            'merchantAccountId' => $this->getMerchantAccountId($_SESSION['currency'])
        ]);

        $order_total = $this->app->formatCurrencyRaw($order->info['total'], $_SESSION['currency']);

        $getCardTokenRpcUrl = OSCOM::link('index.php', 'order&callback&paypal&bt&getCardToken', 'SSL');

        if (OSCOM_APP_PAYPAL_BT_THREE_D_SECURE === '1') {
            $has3ds = 'all';
        } elseif (OSCOM_APP_PAYPAL_BT_THREE_D_SECURE === '2') {
            $has3ds = 'new';
        } else {
            $has3ds = 'none';
        }

        $js_scripts = '<script src="https://js.braintreegateway.com/web/3.2.0/js/client.min.js"></script>' .
                      '<script src="https://js.braintreegateway.com/web/3.2.0/js/hosted-fields.min.js"></script>';

        if ((OSCOM_APP_PAYPAL_BT_THREE_D_SECURE === '1') || (OSCOM_APP_PAYPAL_BT_THREE_D_SECURE === '2')) {
            $js_scripts .= '<script src="https://js.braintreegateway.com/web/3.2.0/js/three-d-secure.min.js"></script>';
        }

        $oscTemplate->addBlock($js_scripts, 'footer_scripts');

        $js = <<<EOD
<script>
$('form[name="checkout_confirmation"]').attr('id', 'braintree-payment-form');
$('#braintree-payment-form button[type="submit"]').prop('disabled', true);

$(function() {
  var form = document.querySelector('#braintree-payment-form');
  var submit = document.querySelector('#braintree-payment-form button[type="submit"]');

  var has3ds = '{$has3ds}';
  var do3ds = false;

  function doTokenize(hostedFieldsInstance, clientInstance, nonce) {
    if ((hostedFieldsInstance === undefined) && (nonce !== undefined)) {
      if (do3ds === true) {
        create3DS(clientInstance, nonce);
      } else {
        document.querySelector('input[name="payment_method_nonce"]').value = nonce;

        form.submit();
      }

      return;
    }

    hostedFieldsInstance.tokenize(function (tokenizeErr, payload) {
      if (tokenizeErr) {
        switch (tokenizeErr.code) {
          case 'HOSTED_FIELDS_FIELDS_EMPTY':
            $('#btCardStatus').html('Please fill out the payment information fields to purchase this order.');

            if ($('#btCardStatus').hasClass('hidden')) {
              $('#btCardStatus').removeClass('hidden');
            }

            if (($('#braintree_cards').length > 0) && ($('#braintree_cards').val() !== '0')) {
              $('#card-token-cvv').parent().addClass('has-error');
            } else {
              $('#card-number').parent().addClass('has-error');
              $('#card-exp').parent().addClass('has-error');

              if ($('#card-cvv').length === 1) {
                $('#card-cvv').parent().addClass('has-error');
              }
            }

            break;

          case 'HOSTED_FIELDS_FIELDS_INVALID':
            $('#btCardStatus').html('Please fill out the payment information fields to purchase this order.');

            if ($('#btCardStatus').hasClass('hidden')) {
              $('#btCardStatus').removeClass('hidden');
            }

            if (($('#braintree_cards').length > 0) && ($('#braintree_cards').val() !== '0')) {
              if ($.inArray('cvv', tokenizeErr.details.invalidFieldKeys) !== -1) {
                $('#card-token-cvv').parent().addClass('has-error');
              }
            } else {
              if ($.inArray('number', tokenizeErr.details.invalidFieldKeys) !== -1) {
                $('#card-number').parent().addClass('has-error');
              }

              if ($.inArray('expirationDate', tokenizeErr.details.invalidFieldKeys) !== -1) {
                $('#card-exp').parent().addClass('has-error');
              }

              if ($.inArray('cvv', tokenizeErr.details.invalidFieldKeys) !== -1) {
                if ($('#card-cvv').length === 1) {
                  $('#card-cvv').parent().addClass('has-error');
                }
              }
            }

            break;

          default:
            $('#btCardStatus').html('The card could not be processed at this time. Please try again and if problems persist, contact us or try with another card.');

            if ($('#btCardStatus').hasClass('hidden')) {
              $('#btCardStatus').removeClass('hidden');
            }
        }

        $('#braintree-payment-form button[data-button="payNow"]').html($('#braintree-payment-form button[data-button="payNow"]').data('orig-button-text')).prop('disabled', false);

        return;
      }

      if (nonce === undefined) {
        nonce = payload.nonce;
      }

      if (do3ds === true) {
        create3DS(clientInstance, nonce);
      } else {
        document.querySelector('input[name="payment_method_nonce"]').value = nonce;

        form.submit();
      }
    });
  }

  function create3DS(clientInstance, nonce) {
    braintree.threeDSecure.create({
      client: clientInstance
    }, function (threeDSecureErr, threeDSecureInstance) {
      if (threeDSecureErr) {
        return;
      }

      threeDSecureInstance.verifyCard({
        amount: {$order_total},
        nonce: nonce,
        addFrame: function (err, iframe) {
          $('#bt3dsmodal .modal-body').html(iframe);
          $('#bt3dsmodal').modal();
        },
        removeFrame: function () {
          $('#bt3dsmodal .modal-body').html('');
          $('#bt3dsmodal').modal('hide');
        }
      }, function (error, response) {
        if (error) {
          return;
        }

        document.querySelector('input[name="payment_method_nonce"]').value = response.nonce;

        form.submit();
      });
    });
  }

  var btClientInstance;
  var btHostedFieldsInstance;

  if ($('#braintree_cards').length > 0) {
    $('#braintree_cards').change(function() {
      $('#braintree-payment-form button[type="submit"]').prop('disabled', true);

      var selected = $(this).val();

      if (selected == '0') {
        braintreeShowNewCardFields();
      } else {
        braintreeShowStoredCardFields(selected);
      }
    });
  }

  braintree.client.create({
    authorization: '{$clientToken}'
  }, function (clientErr, clientInstance) {
    if (clientErr) {
      return;
    }

    btClientInstance = clientInstance;

    if (($('#braintree_cards').length > 0) && ($('#braintree_cards').val() !== '0')) {
      braintreeShowStoredCardFields($('#braintree_cards').val());
    } else {
      braintreeShowNewCardFields();
    }
  });

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var doTokenizeCall = true;

    if ($('#braintree_cards').length > 0) {
      if (($('#card-token-cvv').length === 1) && $('#card-token-cvv').parent().hasClass('has-error')) {
        $('#card-token-cvv').parent().removeClass('has-error');
      }
    }

    if ($('#card-number').parent().hasClass('has-error')) {
      $('#card-number').parent().removeClass('has-error');
    }

    if ($('#card-exp').parent().hasClass('has-error')) {
      $('#card-exp').parent().removeClass('has-error');
    }

    if (($('#card-cvv').length === 1) && $('#card-cvv').parent().hasClass('has-error')) {
      $('#card-cvv').parent().removeClass('has-error');
    }

    do3ds = false;

    if (($('#braintree_cards').length > 0) && ($('#braintree_cards').val() !== '0')) {
      if (has3ds === 'all') {
        do3ds = true;
      }
    } else {
      if ((has3ds === 'all') || (has3ds === 'new')) {
        do3ds = true;
      }
    }

    if ($('#braintree_cards').length > 0) {
      var cardsel = $('#braintree_cards').val();

      if (cardsel !== '0') {
        doTokenizeCall = false;

        $.post('{$getCardTokenRpcUrl}', {card_id: cardsel}, function(response) {
          if ((typeof response == 'object') && ('result' in response) && (response.result === 1)) {
            doTokenize(btHostedFieldsInstance, btClientInstance, response.token);
          }
        }, 'json');
      }
    }

    if (doTokenizeCall === true) {
      doTokenize(btHostedFieldsInstance, btClientInstance);
    }
  }, false);

  function braintreeShowNewCardFields() {
    if ($('#braintree_stored_card_cvv').length === 1) {
      if ($('#braintree_stored_card_cvv').is(':visible')) {
        $('#braintree_stored_card_cvv').hide();
      }
    }

    if ($('#card-number').parent().hasClass('has-error')) {
      $('#card-number').parent().removeClass('has-error');
    }

    if ($('#card-exp').parent().hasClass('has-error')) {
      $('#card-exp').parent().removeClass('has-error');
    }

    if (($('#card-cvv').length === 1) && $('#card-cvv').parent().hasClass('has-error')) {
      $('#card-cvv').parent().removeClass('has-error');
    }

    if ($('#braintree_new_card').not(':visible')) {
      $('#braintree_new_card').show();
    }

    if (btHostedFieldsInstance !== undefined) {
      btHostedFieldsInstance.teardown(function (teardownErr) {
        if (teardownErr) {
          return;
        }

        braintreeCreateInstance();
      });

      return;
    }

    braintreeCreateInstance();
  }

  function braintreeShowStoredCardFields(id) {
    if ($('#braintree_stored_card_cvv').length === 1) {
      if ($('#card-token-cvv').parent().hasClass('has-error')) {
        $('#card-token-cvv').parent().removeClass('has-error');
      }

      if ($('#braintree_stored_card_cvv').not(':visible')) {
        $('#braintree_stored_card_cvv').show();
      }
    }

    if ($('#braintree_new_card').is(':visible')) {
      $('#braintree_new_card').hide();

      if (btHostedFieldsInstance !== undefined) {
        btHostedFieldsInstance.teardown(function (teardownErr) {
          if (teardownErr) {
            return;
          }

          braintreeCreateStoredCardInstance();
        });

        return;
      }

      braintreeCreateStoredCardInstance();

      return;
    }

    if (btHostedFieldsInstance === undefined) {
      braintreeCreateStoredCardInstance();
    } else {
      $('#braintree-payment-form button[type="submit"]').prop('disabled', false);
    }
  }

  function braintreeCreateInstance() {
    var fields = {
      number: {
        selector: '#card-number',
        placeholder: 'Card Number'
      },
      expirationDate: {
        selector: '#card-exp',
        placeholder: 'Expiry Date MM / YYYY'
      }
    };

    if ($('#card-cvv').length === 1) {
      fields.cvv = {
        selector: '#card-cvv',
        placeholder: 'Security Code'
      };
    }

    braintree.hostedFields.create({
      client: btClientInstance,
      styles: {
        'input.invalid': {
          'color': 'red'
        },
        'input.valid': {
          'color': 'green'
        }
      },
      fields: fields
    }, function (hostedFieldsErr, hostedFieldsInstance) {
      if (hostedFieldsErr) {
        return;
      }

      btHostedFieldsInstance = hostedFieldsInstance;

      $('#braintree-payment-form button[type="submit"]').prop('disabled', false);
    });
  }

  function braintreeCreateStoredCardInstance() {
    if ($('#card-token-cvv').length === 1) {
      braintree.hostedFields.create({
        client: btClientInstance,
        styles: {
          'input.invalid': {
            'color': 'red'
          },
          'input.valid': {
            'color': 'green'
          }
        },
        fields: {
          cvv: {
            selector: '#card-token-cvv',
            placeholder: 'Security Code'
          }
        }
      }, function (hostedFieldsErr, hostedFieldsInstance) {
        if (hostedFieldsErr) {
          return;
        }

        btHostedFieldsInstance = hostedFieldsInstance;

        $('#braintree-payment-form button[type="submit"]').prop('disabled', false);
      });
    } else {
      btHostedFieldsInstance = undefined;

      $('#braintree-payment-form button[type="submit"]').prop('disabled', false);
    }
  }
});
</script>
EOD;

        return $js;
    }

    public function isPaymentTypeAccepted($type)
    {
        return in_array($type, $this->payment_types);
    }
}

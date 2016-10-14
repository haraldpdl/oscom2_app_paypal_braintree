<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree;

use OSC\OM\OSCOM;

class Braintree extends \OSC\OM\AppAbstract
{
    protected function init()
    {
        if (!class_exists('\Braintree', false)) {
            include(OSCOM::BASE_DIR . 'Apps/PayPal/Braintree/lib/Braintree.php');
        }

        $this->installCheck();
    }

    protected function installCheck()
    {
        $pm = explode(';', MODULE_PAYMENT_INSTALLED);
        $pos = array_search($this->vendor . '\\' . $this->code . '\\BT', $pm);

        if ($pos === false) {
            $pm[] = $this->vendor . '\\' . $this->code . '\\BT';

            $this->saveCfgParam('MODULE_PAYMENT_INSTALLED', implode(';', $pm));
        }

        $Qcheck = $this->db->query('show tables like ":table_customers_braintree_tokens"');

        if ($Qcheck->fetch() === false) {
            $sql = <<<EOD
CREATE TABLE :table_customers_braintree_tokens (
  id int NOT NULL auto_increment,
  customers_id int NOT NULL,
  braintree_token varchar(255) NOT NULL,
  card_type varchar(32) NOT NULL,
  number_filtered varchar(20) NOT NULL,
  expiry_date char(6) NOT NULL,
  date_added datetime NOT NULL,
  PRIMARY KEY (id),
  KEY idx_cbraintreet_customers_id (customers_id),
  KEY idx_cbraintreet_token (braintree_token)
) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
EOD;

            $this->db->exec($sql);
        }

        if (!defined('OSCOM_APP_PAYPAL_BT_TRANSACTION_ORDER_STATUS_ID')) {
            $Qcheck = $this->db->get('orders_status', 'orders_status_id', [
                'orders_status_name' => 'Braintree [Transactions]'
            ], null, 1);

            if ($Qcheck->fetch() === false) {
                $Qstatus = $this->db->get('orders_status', 'max(orders_status_id) as status_id');

                $status_id = $Qstatus->valueInt('status_id')+1;

                $languages = tep_get_languages();

                foreach ($languages as $lang) {
                    $this->db->save('orders_status', [
                        'orders_status_id' => (int)$status_id,
                        'language_id' => (int)$lang['id'],
                        'orders_status_name' => 'Braintree [Transactions]',
                        'public_flag' => '0',
                        'downloads_flag' => '0'
                    ]);
                }
            }
        }

        $cm = explode(';', MODULE_CONTENT_INSTALLED);
        $pos = array_search('account/' . $this->vendor . '\\' . $this->code . '\\BT', $cm);

        if ($pos === false) {
            $cm[] = 'account/' . $this->vendor . '\\' . $this->code . '\\BT';

            $this->saveCfgParam('MODULE_CONTENT_INSTALLED', implode(';', $cm));
        }
    }

    public function setupCredentials($server = null)
    {
        $status = ((isset($server) && ($server === 'live')) || (!isset($server) && (OSCOM_APP_PAYPAL_BT_STATUS === '1'))) ? '1' : '0';

        \Braintree\Configuration::environment($status === '1' ? 'production' : 'sandbox');
        \Braintree\Configuration::merchantId($status === '1' ? OSCOM_APP_PAYPAL_BRAINTREE_MERCHANT_ID : OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_MERCHANT_ID);
        \Braintree\Configuration::publicKey($status === '1' ? OSCOM_APP_PAYPAL_BRAINTREE_PUBLIC_KEY : OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_PUBLIC_KEY);
        \Braintree\Configuration::privateKey($status === '1' ? OSCOM_APP_PAYPAL_BRAINTREE_PRIVATE_KEY : OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_PRIVATE_KEY);
    }

    public function formatCurrencyRaw($total, $currency_code = null, $currency_value = null)
    {
        global $currencies;

        if (empty($currency_code)) {
            $currency_code = isset($_SESSION['currency']) ? $_SESSION['currency'] : DEFAULT_CURRENCY;
        }

        if (!isset($currency_value) || !is_numeric($currency_value)) {
            $currency_value = $currencies->currencies[$currency_code]['value'];
        }

        return number_format(tep_round($total * $currency_value, $currencies->currencies[$currency_code]['decimal_places']), $currencies->currencies[$currency_code]['decimal_places'], '.', '');
    }
}

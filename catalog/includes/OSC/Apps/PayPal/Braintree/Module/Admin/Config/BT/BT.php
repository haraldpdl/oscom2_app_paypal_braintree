<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT;

use OSC\OM\OSCOM;

class BT extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigAbstract
{
    protected $legacy_pm_code = 'braintree_cc';

    protected function init()
    {
        $this->title = 'Braintree';//$this->app->getDef('module_bt_title');
        $this->short_title = 'Braintree short';//$this->app->getDef('module_bt_short_title');
        $this->introduction = 'Braintree intro';//$this->app->getDef('module_bt_introduction');

        $this->is_installed = defined('OSCOM_APP_PAYPAL_BT_STATUS') && !empty(OSCOM_APP_PAYPAL_BT_STATUS);

//        if (!function_exists('curl_init')) {
//            $this->req_notes[] = $this->app->getDef('module_bt_error_curl');
//        }

//        if (!$this->app->hasCredentials('PS', 'email')) {
//            $this->req_notes[] = $this->app->getDef('module_ps_error_credentials');
//        }
    }

    public function install()
    {
        parent::install();

        $installed = explode(';', MODULE_PAYMENT_INSTALLED);
        $installed[] = $this->app->vendor . '\\' . $this->app->code . '\\' . $this->code;

        $this->app->saveCfgParam('MODULE_PAYMENT_INSTALLED', implode(';', $installed));
    }

    public function uninstall()
    {
        parent::uninstall();

        $installed = explode(';', MODULE_PAYMENT_INSTALLED);
        $installed_pos = array_search($this->app->vendor . '\\' . $this->app->code . '\\' . $this->code, $installed);

        if ($installed_pos !== false) {
            unset($installed[$installed_pos]);

            $this->app->saveCfgParam('MODULE_PAYMENT_INSTALLED', implode(';', $installed));
        }
    }

    public function canMigrate()
    {
        $installed = explode(';', MODULE_PAYMENT_INSTALLED);

        return in_array($this->legacy_pm_code, $installed);
    }

    public function migrate()
    {
        if (defined('MODULE_PAYMENT_BRAINTREE_CC_MERCHANT_ID')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BRAINTREE_MERCHANT_ID', MODULE_PAYMENT_BRAINTREE_CC_MERCHANT_ID);
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_MERCHANT_ID');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_PUBLIC_KEY')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BRAINTREE_PUBLIC_KEY', MODULE_PAYMENT_BRAINTREE_CC_PUBLIC_KEY);
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_PUBLIC_KEY');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_PRIVATE_KEY')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BRAINTREE_PRIVATE_KEY', MODULE_PAYMENT_BRAINTREE_CC_PRIVATE_KEY);
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_PRIVATE_KEY');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_CLIENT_KEY')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BRAINTREE_CLIENT_SIDE_ENCRYPTION_KEY', MODULE_PAYMENT_BRAINTREE_CC_CLIENT_KEY);
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_CLIENT_KEY');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_MERCHANT_ACCOUNTS')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BRAINTREE_CURRENCIES_MA', MODULE_PAYMENT_BRAINTREE_CC_MERCHANT_ACCOUNTS);
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_MERCHANT_ACCOUNTS');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_TOKENS')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BT_CC_TOKENS', (MODULE_PAYMENT_BRAINTREE_CC_TOKENS == 'True') ? '1' : '0');
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_TOKENS');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_VERIFY_WITH_CVV')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BT_VERIFY_CVV', (MODULE_PAYMENT_BRAINTREE_CC_VERIFY_WITH_CVV == 'True') ? '1' : '0');
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_VERIFY_WITH_CVV');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_METHOD')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BT_TRANSACTION_METHOD', (MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_METHOD == 'Payment') ? '1' : '0');
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_METHOD');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_ORDER_STATUS_ID')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BT_ORDER_STATUS_ID', MODULE_PAYMENT_BRAINTREE_CC_ORDER_STATUS_ID);
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_ORDER_STATUS_ID');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_ORDER_STATUS_ID')) {
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_ORDER_STATUS_ID');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_STATUS')) {
            $status = '-1';

            if ((MODULE_PAYMENT_BRAINTREE_CC_STATUS == 'True') && defined('MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_SERVER')) {
                if (MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_SERVER == 'Live') {
                    $status = '1';
                } else {
                    $status = '0';
                }
            }

            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BT_STATUS', $status);
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_STATUS');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_SERVER')) {
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_TRANSACTION_SERVER');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_ZONE')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BT_ZONE', MODULE_PAYMENT_BRAINTREE_CC_ZONE);
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_ZONE');
        }

        if (defined('MODULE_PAYMENT_BRAINTREE_CC_SORT_ORDER')) {
            $this->app->saveCfgParam('OSCOM_APP_PAYPAL_BT_SORT_ORDER', MODULE_PAYMENT_BRAINTREE_CC_SORT_ORDER, 'Sort Order', 'Sort order of display (lowest to highest).');
            $this->app->deleteCfgParam('MODULE_PAYMENT_BRAINTREE_CC_SORT_ORDER');
        }
    }
}

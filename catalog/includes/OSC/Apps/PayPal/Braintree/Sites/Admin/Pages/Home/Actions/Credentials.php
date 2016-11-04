<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Sites\Admin\Pages\Home\Actions;

class Credentials extends \OSC\OM\PagesActionsAbstract
{
    protected $file = 'credentials.php';

    public function execute()
    {
        foreach ($this->getCredentialsParameters() as $key) {
            if (!defined($key)) {
                $this->page->app->saveCfgParam($key, '');
            }
        }
    }

    public function getCredentialsParameters()
    {
        $params = [
            'OSCOM_APP_PAYPAL_BRAINTREE_MERCHANT_ID',
            'OSCOM_APP_PAYPAL_BRAINTREE_PUBLIC_KEY',
            'OSCOM_APP_PAYPAL_BRAINTREE_PRIVATE_KEY',
            'OSCOM_APP_PAYPAL_BRAINTREE_CURRENCIES_MA',
            'OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_MERCHANT_ID',
            'OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_PUBLIC_KEY',
            'OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_PRIVATE_KEY',
            'OSCOM_APP_PAYPAL_BRAINTREE_SANDBOX_CURRENCIES_MA'
        ];

        return $params;
    }
}

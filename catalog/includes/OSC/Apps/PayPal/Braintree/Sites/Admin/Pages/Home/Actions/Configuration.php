<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Sites\Admin\Pages\Home\Actions;

use OSC\OM\Registry;

class Configuration extends \OSC\OM\PagesActionsAbstract
{
    protected $file = 'configuration.php';

    public function execute()
    {
        $class = 'OSC\Apps\PayPal\Braintree\Module\Admin\Config\\BT\\BT';
        Registry::set('BraintreeAdminConfigBT', new $class());
    }
}

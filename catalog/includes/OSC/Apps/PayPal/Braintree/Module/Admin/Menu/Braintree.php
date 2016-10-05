<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Menu;

use OSC\OM\Registry;

use OSC\Apps\PayPal\Braintree\Braintree as BraintreeApp;

class Braintree implements \OSC\OM\Modules\AdminMenuInterface
{
    public static function execute()
    {
        if (!Registry::exists('Braintree')) {
            Registry::set('Braintree', new BraintreeApp());
        }

        $OSCOM_Braintree = Registry::get('Braintree');

        $OSCOM_Braintree->loadDefinitionFile('Module/Admin/Menu/Braintree.txt');

        $menu = [
            [
                'code' => $OSCOM_Braintree->getVendor() . '\\' . $OSCOM_Braintree->getCode(),
                'title' => $OSCOM_Braintree->getDef('admin_menu_configuration'),
                'link' => $OSCOM_Braintree->link('Configuration')
            ],
            [
                'code' => $OSCOM_Braintree->getVendor() . '\\' . $OSCOM_Braintree->getCode(),
                'title' => $OSCOM_Braintree->getDef('admin_menu_credentials'),
                'link' => $OSCOM_Braintree->link('Credentials')
            ]
        ];

        return [
            'heading' => $OSCOM_Braintree->getDef('admin_menu_title'),
            'apps' => $menu
        ];
    }
}

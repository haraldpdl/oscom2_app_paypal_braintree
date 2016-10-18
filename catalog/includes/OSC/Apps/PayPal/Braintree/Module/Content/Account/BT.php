<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Content\Account;

use OSC\OM\HTML;
use OSC\OM\OSCOM;
use OSC\OM\Registry;

use OSC\Apps\PayPal\Braintree\Braintree as BraintreeApp;

class BT implements \OSC\OM\Modules\ContentInterface
{
    public $code, $group, $title, $description, $sort_order, $enabled, $app;

    public function __construct()
    {
        if (!Registry::exists('Braintree')) {
            Registry::set('Braintree', new BraintreeApp());
        }

        $this->app = Registry::get('Braintree');
        $this->app->loadDefinitionFile('Module/Content/Account/BT.txt');

        $this->code = 'BT';
        $this->group = 'account';

        $this->title = $this->app->getDef('module_content_account_title');
        $this->description = '<div align="center">' . HTML::button($this->app->getDef('module_content_account_legacy_admin_app_button'), null, $this->app->link('Configuration'), null, 'btn-primary') . '</div>';

        $this->sort_order = defined('OSCOM_APP_PAYPAL_BT_CONTENT_ACCOUNT_SORT_ORDER') ? OSCOM_APP_PAYPAL_BT_CONTENT_ACCOUNT_SORT_ORDER : 0;
        $this->enabled = defined('OSCOM_APP_PAYPAL_BT_STATUS') && in_array(OSCOM_APP_PAYPAL_BT_STATUS, ['0', '1']);

        $this->public_title = $this->app->getDef('module_content_account_public_title');

        $braintree_enabled = false;

        if (in_array('PayPal\Braintree\BT', explode(';', MODULE_PAYMENT_INSTALLED))) {
            $braintree_enabled = true;

            if (OSCOM_APP_PAYPAL_BT_STATUS === '0') {
                $this->title .= ' [Sandbox]';
                $this->public_title .= ' (' . $this->app->vendor . '\\' . $this->app->code . '\\' . $this->code . '; Sandbox)';
            }
        }

        if ($braintree_enabled !== true) {
            $this->enabled = false;

            $this->description = '<div class="secWarning">' . $this->app->getDef('module_content_account_error_main_app') . '</div>' . $this->description;
        }
    }

    public function execute()
    {
        global $oscTemplate;

        $oscTemplate->_data['account']['account']['links']['braintree_cards'] = [
            'title' => $this->public_title,
            'link' => OSCOM::link('index.php', 'account&stored-cards', 'SSL'),
            'icon' => 'fa fa-fw fa-credit-card'
        ];
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function check()
    {
        return defined('OSCOM_APP_PAYPAL_BT_STATUS');
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
            'OSCOM_APP_PAYPAL_BT_CONTENT_ACCOUNT_SORT_ORDER'
        ];
    }
}

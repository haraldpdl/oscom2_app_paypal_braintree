<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Sites\Shop\Pages\StoredCards;

use OSC\OM\OSCOM;

use OSC\Apps\PayPal\Braintree\Module\Payment\BT as PaymentModuleBT;

class StoredCards extends \OSC\OM\PagesAbstract
{
    protected $file = 'cards.php';
    protected $pm;

    protected function init()
    {
        global $messageStack, $breadcrumb;

        if (!isset($_SESSION['customer_id'])) {
            $_SESSION['navigation']->set_snapshot();

            OSCOM::redirect('login.php');
        }

        if (defined('MODULE_PAYMENT_INSTALLED') && !empty(MODULE_PAYMENT_INSTALLED) && in_array('PayPal\Braintree\BT', explode(';', MODULE_PAYMENT_INSTALLED))) {
            $this->pm = new PaymentModuleBT();

            if ((!$this->pm->enabled) || (OSCOM_APP_PAYPAL_BT_CC_TOKENS == '0')) {
                OSCOM::redirect('account.php');
            }
        } else {
            OSCOM::redirect('account.php');
        }

        $this->pm->app->loadDefinitions('Module/Content/Account/BT');

        if (isset($_GET['action'])) {
            if (($_GET['action'] == 'delete') && isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['formid']) && ($_GET['formid'] == md5($_SESSION['sessiontoken']))) {
                $Qtoken = $this->pm->app->db->get('customers_braintree_tokens', [
                    'id',
                    'braintree_token'
                ], [
                    'id' => $_GET['id'],
                    'customers_id' => $_SESSION['customer_id']
                ]);

                if ($Qtoken->fetch() !== false) {
                    $this->pm->deleteCard($Qtoken->value('braintree_token'), $Qtoken->valueInt('id'));

                    $messageStack->add_session('cards', $this->pm->app->getDef('module_content_account_card_deleted'), 'success');
                }
            }

            OSCOM::redirect('index.php', 'account&stored-cards');
        }

        $breadcrumb->add($this->pm->app->getDef('module_content_account_navbar_title_1'), OSCOM::link('account.php'));
        $breadcrumb->add($this->pm->app->getDef('module_content_account_navbar_title_2'), OSCOM::link('index.php', 'account&stored-cards'));
    }
}

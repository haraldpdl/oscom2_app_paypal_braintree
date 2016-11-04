<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Sites\Admin\Pages\Home\Actions\Credentials;

use OSC\OM\Registry;

use OSC\Apps\PayPal\Braintree\Sites\Admin\Pages\Home\Actions\Credentials;

class Process extends \OSC\OM\PagesActionsAbstract
{
    public function execute()
    {
        $OSCOM_MessageStack = Registry::get('MessageStack');

        foreach (Credentials::getCredentialsParameters() as $key) {
            $p = strtolower($key);

            if (isset($_POST[$p])) {
                $this->page->app->saveCfgParam($key, $_POST[$p]);
            }
        }

        $OSCOM_MessageStack->add($this->page->app->getDef('alert_saved_success'), 'success');

        $this->page->app->redirect('Credentials');
    }
}

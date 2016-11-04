<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Sites\Admin\Pages\Home;

use OSC\OM\Registry;

use OSC\Apps\PayPal\Braintree\Braintree;

class Home extends \OSC\OM\PagesAbstract
{
    public $app;

    protected function init()
    {
        $OSCOM_Braintree = new Braintree();
        Registry::set('Braintree', $OSCOM_Braintree);

        $this->app = $OSCOM_Braintree;

        $this->app->loadDefinitions('Sites/Admin');

        if (!$this->isActionRequest()) {
            $this->runAction('Configuration');
        }
    }
}

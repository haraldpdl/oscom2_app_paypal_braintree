<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT\Params;

class sort_order extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract
{
    public $default = '0';
    public $app_configured = false;

    protected function init()
    {
        $this->title = $this->app->getDef('cfg_bt_sort_order_title');
        $this->description = $this->app->getDef('cfg_bt_sort_order_desc');
    }
}

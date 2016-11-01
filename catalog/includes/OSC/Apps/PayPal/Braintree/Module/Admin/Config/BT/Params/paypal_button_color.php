<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT\Params;

use OSC\OM\HTML;

class paypal_button_color extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract
{
    public $default = '1';
    public $sort_order = 160;

    protected function init()
    {
        $this->title = $this->app->getDef('cfg_bt_paypal_button_color_title');
        $this->description = $this->app->getDef('cfg_bt_paypal_button_color_desc');
    }

    public function getInputField()
    {
        $value = $this->getInputValue();

        $input = '<div class="btn-group" data-toggle="buttons">' .
                 '  <label class="btn btn-info' . ($value == '1' ? ' active' : '') . '">' . HTML::radioField($this->key, '1', ($value == '1')) . $this->app->getDef('cfg_bt_paypal_button_color_gold') . '</label>' .
                 '  <label class="btn btn-info' . ($value == '2' ? ' active' : '') . '">' . HTML::radioField($this->key, '2', ($value == '2')) . $this->app->getDef('cfg_bt_paypal_button_color_blue') . '</label>' .
                 '  <label class="btn btn-info' . ($value == '3' ? ' active' : '') . '">' . HTML::radioField($this->key, '3', ($value == '3')) . $this->app->getDef('cfg_bt_paypal_button_color_silver') . '</label>' .
                 '</div>';

        return $input;
    }
}

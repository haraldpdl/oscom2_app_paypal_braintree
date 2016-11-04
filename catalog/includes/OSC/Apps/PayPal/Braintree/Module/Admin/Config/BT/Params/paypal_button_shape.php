<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT\Params;

use OSC\OM\HTML;

class paypal_button_shape extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract
{
    public $default = '1';
    public $sort_order = 162;

    protected function init()
    {
        $this->title = $this->app->getDef('cfg_bt_paypal_button_shape_title');
        $this->description = $this->app->getDef('cfg_bt_paypal_button_shape_desc');
    }

    public function getInputField()
    {
        $value = $this->getInputValue();

        $input = '<div class="btn-group" data-toggle="buttons">' .
                 '  <label class="btn btn-info' . ($value == '1' ? ' active' : '') . '">' . HTML::radioField($this->key, '1', ($value == '1')) . $this->app->getDef('cfg_bt_paypal_button_shape_pill') . '</label>' .
                 '  <label class="btn btn-info' . ($value == '2' ? ' active' : '') . '">' . HTML::radioField($this->key, '2', ($value == '2')) . $this->app->getDef('cfg_bt_paypal_button_shape_rect') . '</label>' .
                 '</div>';

        return $input;
    }
}

<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT\Params;

use OSC\OM\HTML;

class transaction_method extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract
{
    public $default = '1';
    public $sort_order = 400;

    protected function init()
    {
        $this->title = $this->app->getDef('cfg_bt_transaction_method_title');
        $this->description = $this->app->getDef('cfg_bt_transaction_method_desc');
    }

    public function getInputField()
    {
        $value = $this->getInputValue();

        $input = '<div class="btn-group" data-toggle="buttons">' .
                 '  <label class="btn btn-info' . ($value == '0' ? ' active' : '') . '">' . HTML::radioField($this->key, '0', ($value == '0')) . $this->app->getDef('cfg_bt_transaction_method_authorize') . '</label>' .
                 '  <label class="btn btn-info' . ($value == '1' ? ' active' : '') . '">' . HTML::radioField($this->key, '1', ($value == '1')) . $this->app->getDef('cfg_bt_transaction_method_payment') . '</label>' .
                 '</div>';

        return $input;
    }
}

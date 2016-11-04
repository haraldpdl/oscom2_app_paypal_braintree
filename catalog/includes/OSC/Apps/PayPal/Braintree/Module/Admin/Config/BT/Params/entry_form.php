<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT\Params;

use OSC\OM\HTML;

class entry_form extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract
{
    public $default = '3';
    public $sort_order = 130;

    protected function init()
    {
        $this->title = $this->app->getDef('cfg_bt_entry_form_title');
        $this->description = $this->app->getDef('cfg_bt_entry_form_desc');
    }

    public function getInputField()
    {
        $value = $this->getInputValue();

        $input = '<div class="btn-group" data-toggle="buttons">' .
                 '  <label class="btn btn-info' . ($value == '3' ? ' active' : '') . '">' . HTML::radioField($this->key, '3', ($value == '3')) . $this->app->getDef('cfg_bt_entry_form_hosted_fields') . '</label>' .
                 '  <label class="btn btn-info' . ($value == '2' ? ' active' : '') . '">' . HTML::radioField($this->key, '2', ($value == '2')) . $this->app->getDef('cfg_bt_entry_form_drop_in') . '</label>' .
                 '</div>';

        return $input;
    }
}

<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT\Params;

use OSC\OM\HTML;

class payment_types extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract
{
    public $default = '';
    public $sort_order = 140;

    protected $types = [
        'paypal' => 'PayPal'
    ];

    protected function init()
    {
        $this->title = $this->app->getDef('cfg_bt_payment_types_title');
        $this->description = $this->app->getDef('cfg_bt_payment_types_desc');
    }

    public function getInputField()
    {
        $active = explode(';', $this->getInputValue());

        $input = '';

        foreach ($this->types as $key => $value) {
            $input .= '<div class="checkbox">' .
                      '  <label>' . HTML::checkboxField($this->key . '_cb', $key, in_array($key, $active)) . $value . '</label>' .
                      '</div>';
        }

        $input .= HTML::hiddenField($this->key);

        $result = <<<EOT
<div id="paymentTypesSelection">
  {$input}
</div>

<script>
$(function() {
  $('#paymentTypesSelection input').closest('form').submit(function() {
    $('#paymentTypesSelection input[name="{$this->key}"]').val($('input[name="{$this->key}_cb"]:checked').map(function() {
      return this.value;
    }).get().join(';'));
  });
});
</script>
EOT;

        return $result;
    }
}

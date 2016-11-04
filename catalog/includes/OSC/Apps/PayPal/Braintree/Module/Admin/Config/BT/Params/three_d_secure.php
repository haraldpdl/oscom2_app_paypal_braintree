<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT\Params;

use OSC\OM\OSCOM;
use OSC\OM\HTML;

class three_d_secure extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract
{
    public $default = '0';
    public $sort_order = 350;

    protected function init()
    {
        $this->title = $this->app->getDef('cfg_bt_three_d_secure_title');
        $this->description = $this->app->getDef('cfg_bt_three_d_secure_desc');
    }

    public function getInputField()
    {
        $value = $this->getInputValue();

        $input = '<div id="three_d_secure_ssl_notice" class="alert alert-danger hidden">' . $this->app->getDef('cfg_bt_three_d_secure_ssl_check') . '</div>' .
                 '<div class="btn-group" data-toggle="buttons">' .
                 '  <label class="btn btn-info' . ($value == '1' ? ' active' : '') . '">' . HTML::radioField($this->key, '1', ($value == '1')) . $this->app->getDef('cfg_bt_three_d_secure_all_cards') . '</label>' .
                 '  <label class="btn btn-info' . ($value == '2' ? ' active' : '') . '">' . HTML::radioField($this->key, '2', ($value == '2')) . $this->app->getDef('cfg_bt_three_d_secure_new_cards') . '</label>' .
                 '  <label class="btn btn-info' . ($value == '0' ? ' active' : '') . '">' . HTML::radioField($this->key, '0', ($value == '0')) . $this->app->getDef('cfg_bt_three_d_secure_disabled') . '</label>' .
                 '</div>';

        $has_ssl = (parse_url(OSCOM::getConfig('http_server'), PHP_URL_SCHEME) == 'https') ? 'true' : 'false';

        $input .= <<<EOD
<script>
$(function() {
  var has_ssl = '{$has_ssl}';

  if (has_ssl != 'true') {
    var value = $('input[type=radio][name="oscom_app_paypal_bt_three_d_secure"]:checked').val();

    if (value != '0') {
      if ($('#three_d_secure_ssl_notice').hasClass('hidden')) {
        $('#three_d_secure_ssl_notice').removeClass('hidden');
      }
    }

    $('input[type=radio][name="oscom_app_paypal_bt_three_d_secure"]').change(function() {
      if (this.value != '0') {
        if ($('#three_d_secure_ssl_notice').hasClass('hidden')) {
          $('#three_d_secure_ssl_notice').removeClass('hidden');
        }
      } else {
        if (!$('#three_d_secure_ssl_notice').hasClass('hidden')) {
          $('#three_d_secure_ssl_notice').addClass('hidden');
        }
      }
    });
  }
});
</script>
EOD;

        return $input;
    }
}

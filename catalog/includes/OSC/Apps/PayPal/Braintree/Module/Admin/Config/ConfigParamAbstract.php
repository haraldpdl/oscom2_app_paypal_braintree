<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config;

use OSC\OM\Registry;

abstract class ConfigParamAbstract extends \OSC\Sites\Admin\ConfigParamAbstract
{
    protected $app;
    protected $config_module;

    protected $key_prefix = 'oscom_app_paypal_';
    public $app_configured = true;

    public function __construct($config_module)
    {
        $this->app = Registry::get('Braintree');

        $this->key_prefix .= strtolower($config_module) . '_';

        $this->config_module = $config_module;

        $this->code = (new \ReflectionClass($this))->getShortName();

        $this->app->loadDefinitionFile('Module/Admin/Config/' . $config_module . '/Params/' . $this->code . '.txt');

        parent::__construct();
    }
}

<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config;

use OSC\OM\OSCOM;
use OSC\OM\Registry;

abstract class ConfigAbstract
{
    protected $app;

    public $code;
    public $title;
    public $short_title;
    public $introduction;
    public $req_notes = [];
    public $is_installed = false;
    public $is_uninstallable = false;
    public $is_migratable = false;
    public $sort_order = 0;

    abstract protected function init();

    final public function __construct()
    {
        $this->app = Registry::get('Braintree');

        $this->code = (new \ReflectionClass($this))->getShortName();

        $this->init();
    }

    public function canMigrate()
    {
        return false;
    }

    public function install()
    {
        $cut_length = strlen('OSCOM_APP_PAYPAL_' . $this->code . '_');

        foreach ($this->getParameters() as $key) {
            $p = strtolower(substr($key, $cut_length));

            $class = 'OSC\Apps\PayPal\Braintree\Module\Admin\Config\\' . $this->code . '\Params\\' . $p;

            $cfg = new $class($this->code);

            $this->app->saveCfgParam($key, $cfg->default, isset($cfg->title) ? $cfg->title : null, isset($cfg->description) ? $cfg->description : null, isset($cfg->set_func) ? $cfg->set_func : null);
        }
    }

    public function uninstall()
    {
        $Qdelete = $this->app->db->prepare('delete from :table_configuration where configuration_key like :configuration_key');
        $Qdelete->bindValue(':configuration_key', 'OSCOM_APP_PAYPAL_' . $this->code . '_%');
        $Qdelete->execute();

        return $Qdelete->rowCount();
    }

    public function getParameters()
    {
        $result = [];

        $directory = OSCOM::BASE_DIR . 'OSC/Apps/PayPal/Braintree/Module/Admin/Config/' . $this->code . '/Params';

        if ($dir = new \DirectoryIterator($directory)) {
            foreach ($dir as $file) {
                if (!$file->isDot() && !$file->isDir() && ($file->getExtension() == 'php')) {
                    $class = 'OSC\Apps\PayPal\Braintree\Module\Admin\Config\\' . $this->code . '\\Params\\' . $file->getBasename('.php');

                    if (is_subclass_of($class, 'OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract')) {
                        $result[] = 'OSCOM_APP_PAYPAL_' . $this->code . '_' . strtoupper($file->getBasename('.php'));
                    } else {
                        trigger_error('OSC\Apps\PayPal\Braintree\Module\Admin\Config\\ConfigAbstract::getParameters(): OSC\Apps\PayPal\Braintree\Module\Admin\Config\\' . $this->code . '\\Params\\' . $file->getBasename('.php') . ' is not a subclass of OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract and cannot be loaded.');
                    }
                }
            }
        }

        return $result;
    }

    public function getInputParameters()
    {
        $result = [];

        $cut = 'OSCOM_APP_PAYPAL_' . $this->code . '_';

        $cut_length = strlen($cut);

        foreach ($this->getParameters() as $key) {
            $p = strtolower(substr($key, $cut_length));

            $class = 'OSC\Apps\PayPal\Braintree\Module\Admin\Config\\' . $this->code . '\Params\\' . $p;

            $cfg = new $class($this->code);

            if (!defined($key)) {
              $this->app->saveCfgParam($key, $cfg->default, isset($cfg->title) ? $cfg->title : null, isset($cfg->description) ? $cfg->description : null, isset($cfg->set_func) ? $cfg->set_func : null);
            }

            if ($cfg->app_configured !== false) {
                if (is_numeric($cfg->sort_order)) {
                    $counter = (int)$cfg->sort_order;
                } else {
                    $counter = count($result);
                }

                while (true) {
                    if (isset($result[$counter])) {
                        $counter++;

                        continue;
                    }

                    $set_field = $cfg->getSetField();

                    if (!empty($set_field)) {
                        $result[$counter] = $set_field;
                    }

                    break;
                }
            }
        }

        ksort($result, SORT_NUMERIC);

        return $result;
    }
}

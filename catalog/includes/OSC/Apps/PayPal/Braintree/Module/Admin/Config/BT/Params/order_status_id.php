<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Module\Admin\Config\BT\Params;

use OSC\OM\HTML;

class order_status_id extends \OSC\Apps\PayPal\Braintree\Module\Admin\Config\ConfigParamAbstract
{
    public $default = '0';
    public $sort_order = 500;

    protected function init()
    {
        $this->title = $this->app->getDef('cfg_bt_order_status_id_title');
        $this->description = $this->app->getDef('cfg_bt_order_status_id_desc');
    }

    public function getInputField()
    {
        $statuses_array = [
            [
                'id' => '0',
                'text' => $this->app->getDef('cfg_bt_order_status_id_default')
            ]
        ];

        $Qstatuses = $this->app->db->get('orders_status', [
            'orders_status_id',
            'orders_status_name'
        ], [
            'language_id' => $_SESSION['languages_id']
        ], 'orders_status_name');

        while ($Qstatuses->fetch()) {
            $statuses_array[] = [
                'id' => $Qstatuses->valueInt('orders_status_id'),
                'text' => $Qstatuses->value('orders_status_name')
            ];
        }

        $input = HTML::selectField($this->key, $statuses_array, $this->getInputValue());

        return $input;
    }
}

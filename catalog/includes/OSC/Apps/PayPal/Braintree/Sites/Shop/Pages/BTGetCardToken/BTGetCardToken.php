<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license MIT; https://www.oscommerce.com/license/mit.txt
  */

namespace OSC\Apps\PayPal\Braintree\Sites\Shop\Pages\BTGetCardToken;

use OSC\Apps\PayPal\Braintree\Module\Payment\BT as PaymentModuleBT;

class BTGetCardToken extends \OSC\OM\PagesAbstract
{
    protected $file = null;
    protected $use_site_template = false;
    protected $pm;

    protected function init()
    {
        if (!isset($_SESSION['customer_id'])) {
            exit;
        }

        if (!isset($_POST['card_id']) || !is_numeric($_POST['card_id']) || ($_POST['card_id'] < 1)) {
            exit;
        }

        $this->pm = new PaymentModuleBT();

        $result = [];

        $Qcard = $this->pm->app->db->get('customers_braintree_tokens', 'braintree_token', [
            'id' => (int)$_POST['card_id'],
            'customers_id' => (int)$_SESSION['customer_id']
        ]);

        if ($Qcard->fetch() !== false) {
            $this->pm->app->setupCredentials();

            $pmn = \Braintree\PaymentMethodNonce::create($Qcard->value('braintree_token'));

            $result = [
                'result' => 1,
                'token' => $pmn->paymentMethodNonce->nonce
            ];
        }

        echo json_encode($result);

        exit;
    }
}

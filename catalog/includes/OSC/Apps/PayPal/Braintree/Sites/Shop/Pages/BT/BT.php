<?php
/**
  * Braintree App for osCommerce Online Merchant
  *
  * @copyright (c) 2016 osCommerce; https://www.oscommerce.com
  * @license BSD; https://www.oscommerce.com/bsdlicense.txt
  */

namespace OSC\Apps\PayPal\Braintree\Sites\Shop\Pages\BT;

use OSC\OM\HTML;
use OSC\OM\OSCOM;
use OSC\OM\Registry;

use OSC\Apps\PayPal\Braintree\Module\Payment\BT as PaymentModuleBT;

class BT extends \OSC\OM\PagesAbstract
{
    protected $file = null;
    protected $use_site_template = false;
    protected $pm;

    protected function init()
    {
        global $messageStack;

        if (
            isset($_SESSION['appPayPalBtFormHash']) &&
            isset($_POST['bt_paypal_form_hash']) &&
            $_POST['bt_paypal_form_hash'] == $_SESSION['appPayPalBtFormHash'] &&
            isset($_POST['bt_paypal_nonce']) &&
            !empty($_POST['bt_paypal_nonce'])
        ) {
            $this->pm = new PaymentModuleBT();

            unset($_SESSION['appPayPalBtFormHash']);

            $this->pm->app->setupCredentials();

            $bt = null;

            try {
                $bt = \Braintree\PaymentMethodNonce::find($_POST['bt_paypal_nonce']);
            } catch (\Exception $e) {
            }

            if (
                isset($bt) &&
                is_object($bt) &&
                isset($bt->nonce) &&
                $bt->nonce == $_POST['bt_paypal_nonce'] &&
                $bt->type == 'PayPalAccount' &&
                $bt->consumed === false
            ) {
                $_SESSION['payment'] = $this->pm->app->vendor . '\\' . $this->pm->app->code . '\\' . $this->pm->code;

                $_SESSION['appPayPalBtNonce'] = $bt->nonce;

                $force_login = false;

// check if e-mail address exists in database and login or create customer account
                if (!isset($_SESSION['customer_id'])) {
                    $force_login = true;

                    $email_address = HTML::sanitize($bt->details['payerInfo']['email']);

                    $Qcheck = $this->pm->app->db->get('customers', '*', [
                        'customers_email_address' => $email_address
                    ], null, 1);

                    if ($Qcheck->fetch() !== false) {
                        $_SESSION['customer_id'] = $Qcheck->valueInt('customers_id');
                        $_SESSION['customer_first_name'] = $customers_firstname = $Qcheck->value('customers_firstname');
                        $_SESSION['customer_default_address_id'] = $Qcheck->valueInt('customers_default_address_id');
                    } else {
                        $customers_firstname = HTML::sanitize($bt->details['payerInfo']['firstName']);
                        $customers_lastname = HTML::sanitize($bt->details['payerInfo']['lastName']);

                        $sql_data_array = [
                            'customers_firstname' => $customers_firstname,
                            'customers_lastname' => $customers_lastname,
                            'customers_email_address' => $email_address,
                            'customers_telephone' => '',
                            'customers_fax' => '',
                            'customers_newsletter' => '0',
                            'customers_password' => '',
                            'customers_gender' => ''
                        ];

                        if (isset($bt->details['payerInfo']['phone']) && !empty($bt->details['payerInfo']['phone'])) {
                            $customers_telephone = HTML::sanitize($bt->details['payerInfo']['phone']);

                            $sql_data_array['customers_telephone'] = $customers_telephone;
                        }

                        $this->pm->app->db->save('customers', $sql_data_array);

                        $_SESSION['customer_id'] = $this->pm->app->db->lastInsertId();
                        $_SESSION['customer_first_name'] = $customers_firstname;

                        $this->pm->app->db->save('customers_info', [
                            'customers_info_id' => $_SESSION['customer_id'],
                            'customers_info_number_of_logons' => '0',
                            'customers_info_date_account_created' => 'now()'
                        ]);

// Only generate a password and send an email if the Set Password Content Module is not enabled
                        if (!defined('MODULE_CONTENT_ACCOUNT_SET_PASSWORD_STATUS') || (MODULE_CONTENT_ACCOUNT_SET_PASSWORD_STATUS != 'True')) {
                            $customer_password = tep_create_random_value(max(ENTRY_PASSWORD_MIN_LENGTH, 8));

                            $this->pm->app->db->save('customers', [
                                'customers_password' => tep_encrypt_password($customer_password)
                            ], [
                                'customers_id' => $_SESSION['customer_id']
                            ]);

// build the message content
                            $name = $customers_firstname . ' ' . $customers_lastname;
                            $email_text = sprintf(EMAIL_GREET_NONE, $customers_firstname) .
                                          EMAIL_WELCOME .
                                          $this->pm->app->getDef('module_ec_email_account_password', [
                                              ':email_address' => $email_address,
                                              ':password' => $customer_password
                                          ]) . "\n\n" .
                                          EMAIL_TEXT .
                                          EMAIL_CONTACT .
                                          EMAIL_WARNING;
                            tep_mail($name, $email_address, EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
                        }
                    }

                    Registry::get('Session')->recreate();
                }

                $address_key = null;

                if (isset($bt->details['payerInfo']['shippingAddress'])) {
                    $address_key = 'shippingAddress';
                } elseif (isset($bt->details['payerInfo']['billingAddress'])) {
                    $address_key = 'billingAddress';
                }

                if (isset($address_key)) {
// check if paypal address exists in the address book
                    if (isset($bt->details['payerInfo'][$address_key]['recipientName'])) {
                        $name_array = explode(' ', $bt->details['payerInfo'][$address_key]['recipientName'], 2);

                        $ship_firstname = HTML::sanitize($name_array[0]);
                        $ship_lastname = isset($name_array[1]) ? HTML::sanitize($name_array[1]) : '';
                    } else {
                        $ship_firstname = HTML::sanitize($bt->details['payerInfo']['firstName']);
                        $ship_lastname = HTML::sanitize($bt->details['payerInfo']['lastName']);
                    }

                    $ship_address = HTML::sanitize($bt->details['payerInfo'][$address_key]['line1']);
                    $ship_city = HTML::sanitize($bt->details['payerInfo'][$address_key]['city']);
                    $ship_zone = HTML::sanitize($bt->details['payerInfo'][$address_key]['state']);
                    $ship_zone_id = 0;
                    $ship_postcode = HTML::sanitize($bt->details['payerInfo'][$address_key]['postalCode']);
                    $ship_country = HTML::sanitize($bt->details['payerInfo'][$address_key]['countryCode']);
                    $ship_country_id = 0;
                    $ship_address_format_id = 1;

                    $Qcountry = $this->pm->app->db->get('countries', [
                        'countries_id',
                        'address_format_id'
                    ], [
                        'countries_iso_code_2' => $ship_country
                    ], null, 1);

                    if ($Qcountry->fetch() !== false) {
                        $ship_country_id = $Qcountry->valueInt('countries_id');
                        $ship_address_format_id = $Qcountry->valueInt('address_format_id');
                    }

                    if ($ship_country_id > 0) {
                        $Qzone = $this->pm->app->db->prepare('select zone_id from :table_zones where zone_country_id = :zone_country_id and (zone_name = :zone_name or zone_code = :zone_code) limit 1');
                        $Qzone->bindInt(':zone_country_id', $ship_country_id);
                        $Qzone->bindValue(':zone_name', $ship_zone);
                        $Qzone->bindValue(':zone_code', $ship_zone);
                        $Qzone->execute();

                        if ($Qzone->fetch() !== false) {
                            $ship_zone_id = $Qzone->valueInt('zone_id');
                        }
                    }

                    $Qcheck = $this->pm->app->db->prepare('select address_book_id from :table_address_book where customers_id = :customers_id and entry_firstname = :entry_firstname and entry_lastname = :entry_lastname and entry_street_address = :entry_street_address and entry_postcode = :entry_postcode and entry_city = :entry_city and (entry_state = :entry_state or entry_zone_id = :entry_zone_id) and entry_country_id = :entry_country_id limit 1');
                    $Qcheck->bindInt(':customers_id', $_SESSION['customer_id']);
                    $Qcheck->bindValue(':entry_firstname', $ship_firstname);
                    $Qcheck->bindValue(':entry_lastname', $ship_lastname);
                    $Qcheck->bindValue(':entry_street_address', $ship_address);
                    $Qcheck->bindValue(':entry_postcode', $ship_postcode);
                    $Qcheck->bindValue(':entry_city', $ship_city);
                    $Qcheck->bindValue(':entry_state', $ship_zone);
                    $Qcheck->bindInt(':entry_zone_id', $ship_zone_id);
                    $Qcheck->bindInt(':entry_country_id', $ship_country_id);
                    $Qcheck->execute();

                    if ($Qcheck->fetch() !== false) {
                        $_SESSION['sendto'] = $Qcheck->valueInt('address_book_id');

                        if (!isset($_SESSION['customer_default_address_id'])) {
                            $_SESSION['customer_default_address_id'] = $Qcheck->valueInt('address_book_id');
                        }
                    } else {
                        $sql_data_array = [
                            'customers_id' => $_SESSION['customer_id'],
                            'entry_firstname' => $ship_firstname,
                            'entry_lastname' => $ship_lastname,
                            'entry_street_address' => $ship_address,
                            'entry_postcode' => $ship_postcode,
                            'entry_city' => $ship_city,
                            'entry_country_id' => $ship_country_id,
                            'entry_gender' => ''
                        ];

                        if (ACCOUNT_STATE == 'true') {
                            if ($ship_zone_id > 0) {
                                $sql_data_array['entry_zone_id'] = $ship_zone_id;
                                $sql_data_array['entry_state'] = '';
                            } else {
                                $sql_data_array['entry_zone_id'] = '0';
                                $sql_data_array['entry_state'] = $ship_zone;
                            }
                        }

                        $this->pm->app->db->save('address_book', $sql_data_array);

                        $address_id = $this->pm->app->db->lastInsertId();

                        $_SESSION['sendto'] = $address_id;

                        if (!isset($_SESSION['customer_default_address_id'])) {
                            $this->pm->app->db->save('customers', [
                                'customers_default_address_id' => $address_id
                            ], [
                                'customers_id' => $_SESSION['customer_id']
                            ]);

                            $_SESSION['customer_default_address_id'] = $address_id;
                        }
                    }

                    $_SESSION['billto'] = $_SESSION['sendto'];

                    if ($force_login == true) {
                        $_SESSION['customer_country_id'] = $ship_country_id;
                        $_SESSION['customer_zone_id'] = $ship_zone_id;
                    }

                    include(OSCOM::getConfig('dir_root', 'Shop') . 'includes/classes/order.php');
                    $order = new \order();

                    if ($_SESSION['cart']->get_content_type() != 'virtual') {
                        $total_weight = $_SESSION['cart']->show_weight();
                        $total_count = $_SESSION['cart']->count_contents();

// load all enabled shipping modules
                        include(OSCOM::getConfig('dir_root', 'Shop') . 'includes/classes/shipping.php');
                        $shipping_modules = new \shipping();

                        $free_shipping = false;

                        if (defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && (MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true')) {
                            $pass = false;

                            switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
                                case 'national':
                                    if ($order->delivery['country_id'] == STORE_COUNTRY) {
                                        $pass = true;
                                    }
                                    break;

                                case 'international':
                                    if ($order->delivery['country_id'] != STORE_COUNTRY) {
                                        $pass = true;
                                    }
                                    break;

                                case 'both':
                                    $pass = true;
                                    break;
                            }

                            if (($pass == true) && ($order->info['total'] >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER)) {
                                $free_shipping = true;

                                include(OSCOM::getConfig('dir_root', 'Shop') . 'includes/languages/' . $_SESSION['language'] . '/modules/order_total/ot_shipping.php');
                            }
                        }

                        $_SESSION['shipping'] = false;

                        if ((tep_count_shipping_modules() > 0) || ($free_shipping == true)) {
                            if ($free_shipping == true) {
                                $_SESSION['shipping'] = 'free_free';
                            } else {
                                $shipping_modules->quote();

                                $_SESSION['shipping'] = $shipping_modules->get_first();
                                $_SESSION['shipping'] = $_SESSION['shipping']['id'];
                            }
                        } else {
                            if (defined('SHIPPING_ALLOW_UNDEFINED_ZONES') && (SHIPPING_ALLOW_UNDEFINED_ZONES == 'False')) {
                                unset($_SESSION['shipping']);

                                $messageStack->add_session('checkout_address', $this->pm->app->getDef('module_ec_error_no_shipping_available'), 'error');

                                $_SESSION['appPayPalBtRightTurn'] = true;

                                OSCOM::redirect('checkout_shipping_address.php', '', 'SSL');
                            }
                        }

                        if (strpos($_SESSION['shipping'], '_')) {
                            list($module, $method) = explode('_', $_SESSION['shipping']);

                            if (is_object($GLOBALS[$module]) || ($_SESSION['shipping'] == 'free_free')) {
                                if ($_SESSION['shipping'] == 'free_free') {
                                    $quote[0]['methods'][0]['title'] = FREE_SHIPPING_TITLE;
                                    $quote[0]['methods'][0]['cost'] = '0';
                                } else {
                                    $quote = $shipping_modules->quote($method, $module);
                                }

                                if (isset($quote['error'])) {
                                    unset($_SESSION['shipping']);

                                    OSCOM::redirect('checkout_shipping.php', '', 'SSL');
                                } else {
                                    if ((isset($quote[0]['methods'][0]['title'])) && (isset($quote[0]['methods'][0]['cost']))) {
                                        $_SESSION['shipping'] = [
                                            'id' => $_SESSION['shipping'],
                                            'title' => (($free_shipping == true) ?  $quote[0]['methods'][0]['title'] : $quote[0]['module'] . ' ' . $quote[0]['methods'][0]['title']),
                                            'cost' => $quote[0]['methods'][0]['cost']
                                        ];
                                    }
                                }
                            }
                        }
                    } else {
                        $_SESSION['shipping'] = false;
                        $_SESSION['sendto'] = false;
                    }

                    if (isset($_SESSION['shipping'])) {
                        OSCOM::redirect('checkout_confirmation.php', '', 'SSL');
                    } else {
                        $_SESSION['appPayPalBtRightTurn'] = true;

                        OSCOM::redirect('checkout_shipping.php', '', 'SSL');
                    }
                }
            }
        }

        if (isset($_SESSION['appPayPalBtFormHash'])) {
            unset($_SESSION['appPayPalBtFormHash']);
        }

        OSCOM::redirect('checkout_shipping.php', '', 'SSL');
    }
}

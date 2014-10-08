<?php
/**
 Plugin Name: Printful Shipping Rates for WooCommerce
 Plugin URI: https://wordpress.org/plugins/printful-shipping-for-woocommerce/
 Description: Printful shipping rates
 Version: 1.0.1
 Author: Idea Bits LLC
 License: GPL2 http://www.gnu.org/licenses/gpl-2.0.html
 */

add_action('woocommerce_shipping_init', 'printful_shipping_init');

add_filter('woocommerce_shipping_methods', 'printful_shipping_add');

function printful_shipping_add($methods)
{
    $methods [] = 'printful_shipping';
    return $methods;
}

function printful_shipping_init()
{

    if (!class_exists('WC_Shipping_Method') ) {
        return;
    }

    class Printful_Shipping extends WC_Shipping_Method
    {
        public $currency_rate = 1;
        public $show_warnings = false;
        private $last_error = false;

        function __construct()
        {
            $this->id = 'printful_shipping';
            $this->method_title = 'Printful Shipping';
            $this->method_description = 'Calculate live shipping rates based on actual Printful shipping costs';

            $this->init_form_fields();
            $this->init_settings();

            add_action('woocommerce_update_options_shipping_' . $this->id, array(&$this, 'process_admin_options'));

            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            $this->show_warnings = $this->get_option('show_warnings') == 'yes';

            if (get_woocommerce_currency() != 'USD') {
                $currency_rate = (float)$this->get_option('rate');
                if ($currency_rate>0) {
                    $this->currency_rate = $currency_rate;
                }
            }
            $this->type = 'order';
        }

        function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title'			=> __( 'Enable/Disable', 'woocommerce' ),
                    'type'			=> 'checkbox',
                    'label'			=> __( 'Enable this shipping method', 'woocommerce' ),
                    'default'		=> 'no'
                ),
                'title' => array(
                    'title' 		=> __( 'Method Title', 'woocommerce' ),
                    'type' 			=> 'text',
                    'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
                    'default'		=> __( 'Printful Shipping', 'woocommerce' ),
                    'desc_tip'		=> true,
                ),
                'printful_key' => array(
                    'title' => 'Printful store API key',
                    'type' => 'text',
                    'desc_tip' => true,
                    'description'=> 'Your store\'s Printful API key. Create it in the Prinful dashboard',
                    'default' => '',
                ),
                'show_warnings' => array(
                    'title'			=> 'Show Printful warnings',
                    'type'			=> 'checkbox',
                    'label'			=> 'Display Printful status messages if rate API request fails',
                    'default'		=> 'yes'
                ),
            );
            $currency = get_woocommerce_currency();
            if ($currency != 'USD'){ //Require conversion rate
                $this->form_fields['rate'] = array(
                    'title' =>  'Currency conversion rate',
                    'type' => 'text',
                    'desc_tip' => true,
                    'description'	=>  'Currency rate used to convert Printful shipping prices from USD to ' . $currency .
                        '. For example if multiplier is 0.2, Printful price of 2 USD will be converted to 10 '.$currency,
                    'default' => '1',
                );
            }
        }

        function calculate_shipping($package = array())
        {
            if (!class_exists('PrintfulClient', false)) {
                require dirname(__FILE__) . '/PrintfulClient.php';
            }

            $request = array(
                'recipient' => array(
                    'address1' => $package['destination']['address'],
                    'address2' => $package['destination']['address_2'],
                    'city' => $package['destination']['city'],
                    'state_code' => $package['destination']['state'],
                    'country_code' => $package['destination']['country'],
                    'zip' => $package['destination']['postcode'],
                ),
                'items' => array()
            );

            foreach ($package['contents'] as $item) {
                $request['items'] []= array(
                    'external_variant_id' => $item['variation_id'] ? $item['variation_id'] : $item['product_id'],
                    'quantity' => $item['quantity']
                );
            }

            try {
                $printful = new PrintfulClient($this->get_option('printful_key'));
            } catch( PrintfulException $e) {
                $this->set_error($e);
                return false;
            }

            try {
                $response = $printful->post('shipping/rates', $request, array(
                    'expedited' => true,
                ));

                foreach ($response as $rate) {
                    $rateData = array(
                        'id' => $this->id . '_' . $rate['id'],
                        'label' => $rate['name'],
                        'cost' => round($rate['rate'] / $this->currency_rate, 2),
                        'taxes' => '',
                        'calc_tax' => 'per_order'
                    );
                    $this->add_rate($rateData);
                }
            } catch ( PrintfulException $e) {
                $this->set_error($e);
                return false;
            }
        }

        private function set_error($error)
        {
            if ($this->show_warnings){
                $this->last_error = $error;
                add_filter('woocommerce_cart_no_shipping_available_html', array($this, 'show_error'));
                add_filter('woocommerce_no_shipping_available_html', array($this, 'show_error'));
            }
        }
        public function show_error($data)
        {
            $error = $this->last_error;
            $message = $error->getMessage();
            if($error instanceof PrintfulApiException && $error->getCode() == 401){
                $message = 'Invalid API key';
            }
            return '<p>'.$this->title.': '.htmlspecialchars($message).'</p>';
        }
    }
}





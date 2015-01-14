<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Integration extends WC_Integration
{

    public static $_instance;

    public static function instance() {
        if ( is_null( self::$_instance ) )
            self::$_instance = new self();
        return self::$_instance;
    }

    public function __construct()
    {
        $this->id = 'printful';
        $this->method_title = 'Printful Integration';
        $this->method_description = 'Enable integration with Printful fulfillment service';

        add_action('woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options'));

        $this->init_form_fields();
        $this->init_settings();

        if ($this->get_option('calculate_tax') == 'yes')
        {
            //Update tax options if taxes are enabled
            if (get_option('woocommerce_calc_taxes') != 'yes')
            {
                update_option('woocommerce_calc_taxes', 'yes');
            }
            if (get_option('woocommerce_tax_based_on') != 'shipping')
            {
                update_option('woocommerce_tax_based_on', 'shipping');
            }

            //Show warning in the tax settings section
            add_action('woocommerce_settings_tax_options', array($this, 'show_tax_warning'));

            //Override tax rates calculated by Woocommerce
            add_filter('woocommerce_matched_tax_rates', array($this, 'calculate_tax'), 10, 6);
        }

        self::$_instance = $this;
    }

    public function get_client()
    {
        require_once 'class-printful-client.php';
        $client = new Printful_Client($this->get_option('printful_key'), $this->get_option('disable_ssl') == 'yes');
        return $client;
    }

    public function init_settings()
    {
        parent::init_settings();

        //Copy settings from old plugin settings location if upgraded from plugin version 1.0.2
        if ($this->get_option('printful_key') === false)
        {
            $oldsettings = get_option( $this->plugin_id . 'printful_shipping_settings', null );
            $this->settings['printful_key'] = '';

            if(!empty($oldsettings['printful_key']))
            {
                $this->settings['printful_key'] = $oldsettings['printful_key'];
            }
            if(!empty($oldsettings['disable_ssl']))
            {
                $this->settings['disable_ssl'] = $oldsettings['disable_ssl'];
            }

            update_option($this->plugin_id . 'printful_settings', $this->settings);
        }
    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'printful_key' => array(
                'title' => 'Printful store API key',
                'type' => 'text',
                'desc_tip' => true,
                'description'=> 'Your store\'s Printful API key. Create it in the Prinful dashboard',
                'default' => false,
            ),
            'calculate_tax' => array(
                'title'			=> 'Calculate sales tax',
                'type'			=> 'checkbox',
                'label'			=> 'Calculate sales tax for locations where it is required for Printful orders',
                'default'		=> 'no'
            ),
            'disable_ssl' => array(
                'title'			=> 'Disable SSL',
                'type'			=> 'checkbox',
                'label'			=> 'Use HTTP instead of HTTPS to connect to the Printful API (may be required if the plugin does not work for some hosting configurations)',
                'default'		=> 'no'
            ),
        );
    }

    public function generate_settings_html($form_fields = false)
    {
        parent::generate_settings_html($form_fields);

        ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label >Calculate shipping rates</label>
                </th>
                <td class="forminp">
                    Go to <b><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping&section=printful_shipping') ?>">Shipping → Printful Shipping</a></b> to enable Printful shipping rate calculation
                </td>
            </tr>
        <?php
    }

    public function calculate_tax($matched_tax_rates, $country, $state, $postcode, $city, $tax_class)
    {
        $countries = $this->get_tax_countries();
        if(isset($countries[$country][$state]))
        {
            $rate = false;
            try {
                $client = $this->get_client();
                $response = $client->post('tax/rates', array(
                    'recipient' => array(
                        'country_code' => $country,
                        'state_code' => $state,
                        'city' => $city,
                        'zip' => $postcode,
                    )
                ));
            }
            catch(Exception $e)
            {}

            if(isset($response['rate']))
            {
                $rate = $response['rate'];
            }

            if ($rate)
            {
                $id = $this->get_printful_rate_id($country, $state);
                return array(
                    $id => array(
                        'rate' => $rate * 100,
                        'label' => 'Sales Tax',
                        'shipping' => 'no',
                        'compound' => 'no',
                    )
                );
            }
        }
        //Return no taxes
        return array();
    }

    /**
     * Gets list of countries and states where Printful needs to calculate sales tax
     */
    private function get_tax_countries()
    {
        $countries = get_transient('printful_tax_countries');
        if (!$countries)
        {
            $countries = array();
            try {
                $client = $this->get_client();
                $list = $client->get('tax/countries');

                foreach($list as $country)
                {
                    $list[$country['country']] = array();
                    foreach($country['states'] as $state){
                        $countries[$country['code']][$state['code']] = 1;
                    }
                }
                if(!empty($countries))
                {
                    set_transient('printful_tax_countries', $countries, 86400);
                }
            }
            catch(Exception $e)
            {
                //Default to CA if can't get the actual state list
                return array('US' => array('CA' => 1));
            }
        }
        return $countries;
    }

    /**
     * Creates dummy tax rate ID to display Printful tax rates in the cart summary.
     */
    private function get_printful_rate_id($cc, $state)
    {
        global $wpdb;

        $states = WC()->countries->get_states($cc);
        $tax_title = (isset($states[$state]) ? $states[$state] .' ': '' ). 'Sales Tax';
        $id = $wpdb->get_var(
            $wpdb->prepare("SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class='printful'
            and tax_rate_country = %s AND tax_rate_state = %s  LIMIT 1",
            '_'.$cc,
            $state
        ));
        if(empty($id))
        {
            $wpdb->insert(
                $wpdb->prefix . "woocommerce_tax_rates",
                array(
                    'tax_rate_country'  => '_'.$cc,
                    'tax_rate_state'    => $state,
                    'tax_rate'          => 0,
                    'tax_rate_name'     => $tax_title,
                    'tax_rate_priority' => 1,
                    'tax_rate_compound' => 0,
                    'tax_rate_shipping' => 0,
                    'tax_rate_class'    => 'printful'
                )
            );
            $id = $wpdb->insert_id;
        }
        return $id;
    }

    public function show_tax_warning(){
        ?>
        <div class="error below-h2">
            <p>
                Warning: Tax rates are overriden by Printful Integration plugin. Go to
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=integration&section=printful') ?>">Printful Integration settings</a>
                to disable automatic tax calculation if you want to use your own settings.
            </p>
        </div>
        <?php
    }

}
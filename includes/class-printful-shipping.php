<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_Shipping extends WC_Shipping_Method
{
    public $show_warnings = false;
    public $calculate_tax = false;
    private $last_error = false;

    public function __construct()
    {
        $this->id = 'printful_shipping';
        $this->method_title = $this->title = 'Printful Shipping';
        $this->method_description = 'Calculate live shipping rates based on actual Printful shipping costs.';

        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, array(&$this, 'process_admin_options'));

        $this->enabled = $this->get_option('enabled');
        $this->show_warnings = $this->get_option('show_warnings') == 'yes';

        $this->type = 'order';
    }

    public function generate_settings_html($form_fields = false)
    {
        if (empty(Printful_Integration::instance()->settings['printful_key']))
        {
            ?>
                <tr><td colspan="2">
                       <div class="error below-h2" style="margin:0">
                        <p>
                            Please add Printful API key to the
                            <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=integration&section=printful') ?>">Printful Integration settings section</a>
                            to enable rate calculation.
                        </p>
                        </div>
                </td></tr>
            <?php
        }
        parent::generate_settings_html($form_fields);

    }

    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'			=> __( 'Enable/Disable', 'woocommerce' ),
                'type'			=> 'checkbox',
                'label'			=> __( 'Enable this shipping method', 'woocommerce' ),
                'default'		=> 'no'
            ),
            'show_warnings' => array(
                'title'			=> 'Show Printful warnings',
                'type'			=> 'checkbox',
                'label'			=> 'Display Printful status messages if rate API request fails',
                'default'		=> 'yes'
            ),
        );
    }

    public function calculate_shipping($package = array())
    {
        $request = array(
            'recipient' => array(
                'address1' => $package['destination']['address'],
                'address2' => $package['destination']['address_2'],
                'city' => $package['destination']['city'],
                'state_code' => $package['destination']['state'],
                'country_code' => $package['destination']['country'],
                'zip' => $package['destination']['postcode'],
            ),
            'items' => array(),
            'currency' => get_woocommerce_currency()
        );

        foreach ($package['contents'] as $item) {
            if (!empty($item['data']) && ($item['data']->is_virtual() || $item['data']->is_downloadable())) {
                continue;
            }
            $request['items'] []= array(
                'external_variant_id' => $item['variation_id'] ? $item['variation_id'] : $item['product_id'],
                'quantity' => $item['quantity']
            );
        }

        if (!$request['items']) {
            return false;
        }

        try {
            $client = Printful_Integration::instance()->get_client();
        } catch( PrintfulException $e) {
            $this->set_error($e);
            return false;
        }

        try {
            $response = $client->post('shipping/rates', $request, array(
                'expedited' => true,
            ));

            foreach ($response as $rate) {
                $rateData = array(
                    'id' => $this->id . '_' . $rate['id'],
                    'label' => $rate['name'],
                    'cost' => $rate['rate'],
                    'taxes' => false,
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
        return '<p>ERROR: '.htmlspecialchars($message).'</p>';
    }
}
<?php
/**
Plugin Name: Printful Integration for WooCommerce
Plugin URI: https://wordpress.org/plugins/printful-shipping-for-woocommerce/
Description: Calculate correct shipping and tax rates for your Printful-Woocommerce integration.
Version: 1.1.2
Author: Printful
Author URI: http://www.theprintful.com
License: GPL2 http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) exit;

new Printful_Base();


class Printful_Base {

    const VERSION = '1.1.2';

    /**
     * Construct the plugin.
     */
    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }
    /**
     * Initialize the plugin.
     */
    public function init() {
        if (!class_exists('WC_Integration')) {
            return;
        }
        //Register integration section
        add_filter('woocommerce_integrations', array($this, 'add_integration'));

        //Register shipping method
        add_filter('woocommerce_shipping_methods', array($this, 'add_shipping'));

        //Register API endpoint
        add_filter('woocommerce_api_classes', array($this, 'add_api_resource'));

    }

    public function add_shipping( $methods ) {
        require_once 'includes/class-printful-shipping.php';
        $methods[] = 'Printful_Shipping';
        return $methods;
    }

    public function add_integration( $integrations ) {
        require_once 'includes/class-printful-integration.php';
        $integrations[] = 'Printful_Integration';
        return $integrations;
    }

    public function add_api_resource($endpoints)
    {
        require_once 'includes/class-printful-api-resource.php';
        $endpoints[]= 'Printful_API_Resource';
        return $endpoints;
    }
}

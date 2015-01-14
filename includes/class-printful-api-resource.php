<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Printful_API_Resource extends WC_API_Resource {

    /** @var string $base the route base */
    protected $base = '/printful';

    public function register_routes( $routes ) {

        $routes[ $this->base.'/version' ] = array(
            array( array( $this, 'get_status' ), WC_API_Server::READABLE | WC_API_Server::HIDDEN_ENDPOINT),
        );
        return $routes;
    }

    /**
     * Allow remotely get plugin version for debug purposes
     */
    public function get_status(){
        $error = false;
        try {
            $client = Printful_Integration::instance()->get_client();
            $storeData = $client->get('store');
        }
        catch(Exception $e){
            $error = $e->getMessage();
        }
        return array(
            'version' => Printful_Base::VERSION,
            'api_key' => !empty(Printful_Integration::instance()->settings['printful_key']),
            'store_id' => !empty($storeData['id']) ? $storeData['id'] : false,
            'error'   => $error,
        );
    }

}
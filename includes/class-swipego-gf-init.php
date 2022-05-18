<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Swipego_GF_Init {

    // Register hooks
    public function __construct() {

        add_action( 'gform_loaded', array( $this, 'load_dependencies' ), 5 );

    }

    // Load required files
    public function load_dependencies() {

        GFForms::include_payment_addon_framework();

        require_once( SWIPEGO_GF_PATH . 'includes/class-swipego-gf-gateway.php' );

        GFAddOn::register( 'Swipego_GF_Gateway' );

    }

}
new Swipego_GF_Init();

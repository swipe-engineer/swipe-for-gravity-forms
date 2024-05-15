<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Swipego_GF {

    // Load dependencies
    public function __construct() {
        
        // Libraries
        require_once( SWIPEGO_GF_PATH . 'libraries/swipego/class-swipego.php' );

        if ( swipego_is_plugin_activated( 'gravityforms/gravityforms.php' ) ) {

            // Functions
            require_once( SWIPEGO_GF_PATH . 'includes/functions.php' );

            // Admin
            require_once( SWIPEGO_GF_PATH . 'admin/class-swipego-gf-admin.php' );
            
            // API
            require_once( SWIPEGO_GF_PATH . 'libraries/swipego/includes/abstracts/abstract-swipego-client.php' );
            require_once( SWIPEGO_GF_PATH . 'libraries/swipego/includes/class-swipego-api.php' );
            require_once( SWIPEGO_GF_PATH . 'includes/class-swipego-gf-api.php' );
        
            if ( swipego_get_integration() ) {
                
                // Initialize payment gateway
                require_once( SWIPEGO_GF_PATH . 'includes/class-swipego-gf-init.php' );
                
            }
            
            if ( swipego_is_logged_in() ) {
                
                // Settings
                require_once( SWIPEGO_GF_PATH . 'admin/class-swipego-gf-settings.php' );
                
            }
        }
        

    }

}
new Swipego_GF();

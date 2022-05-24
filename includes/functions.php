<?php
if ( !defined( 'ABSPATH' ) ) exit;

// Display notice
function swipego_gf_notice( $message, $type = 'success' ) {

    $plugin = esc_html__( 'Swipe for Gravity Forms', 'swipego-gf' );

    printf( '<div class="notice notice-%1$s"><p><strong>%2$s:</strong> %3$s</p></div>', esc_attr( $type ), $plugin, $message );

}

// Log a message in Gravity Forms logs
function swipego_gf_logger( $message ) {
    
    do_action( 'logger', $message );

    if ( class_exists( 'GFLogging' ) && class_exists( 'KLogger' ) ) {
        GFLogging::include_logger();
        GFLogging::log_message( 'gravityformsswipego', $message, KLogger::DEBUG );
    }

}

// Get approved businesses from Swipe
function swipego_gf_get_businesses() {

    if ( !class_exists( 'Swipego_GF_API' ) ) {
        return false;
    }

    try {

        $swipego = new Swipego_GF_API();
        $swipego->set_access_token( swipego_get_access_token() );

        list( $code, $response ) = $swipego->get_approved_businesses();

        $data = isset( $response['data'] ) ? $response['data'] : false;

        $businesses = array();

        if ( is_array( $data ) ) {

            foreach ( $data as $item ) {

                $business_id = isset( $item['id'] ) ? sanitize_text_field( $item['id'] ) : null;

                if ( !$business_id ) {
                    continue;
                }

                $businesses[ $business_id ] = array(
                    'id'             => $business_id,
                    'name'           => isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : null,
                    'integration_id' => isset( $item['integration']['id'] ) ? sanitize_text_field( $item['integration']['id'] ) : null,
                    'api_key'        => isset( $item['integration']['api_key'] ) ? sanitize_text_field( $item['integration']['api_key'] ) : null,
                    'signature_key'  => isset( $item['integration']['signature_key'] ) ? sanitize_text_field( $item['integration']['signature_key'] ) : null,
                );
            }
        }

        return $businesses;

    } catch ( Exception $e ) {
        return false;
    }

}

// Get business information from Swipe by its ID
function swipego_gf_get_business( $business_id ) {
    $businesses = swipego_gf_get_businesses();
    return isset( $businesses[ $business_id ] ) ? $businesses[ $business_id ] : false;
}

<?php
if ( !defined( 'ABSPATH' ) ) exit;

class Swipego_GF_API extends Swipego_API {

    // Log a message in Gravity Forms logs
    protected function log( $message ) {

        if ( $this->debug ) {
            swipego_gf_logger( $message );
        }

    }

}

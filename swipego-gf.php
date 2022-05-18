<?php
/**
 * Plugin Name:       Swipe for Gravity Forms
 * Description:       Swipe payment integration for Gravity Forms.
 * Version:           1.0.0
 * Requires at least: 4.6
 * Requires PHP:      7.0
 * Author:            Fintech Worldwide Sdn. Bhd.
 * Author URI:        https://swipego.io/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( !defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'Swipego_GF' ) ) return;

define( 'SWIPEGO_GF_FILE', __FILE__ );
define( 'SWIPEGO_GF_URL', plugin_dir_url( SWIPEGO_GF_FILE ) );
define( 'SWIPEGO_GF_PATH', plugin_dir_path( SWIPEGO_GF_FILE ) );
define( 'SWIPEGO_GF_BASENAME', plugin_basename( SWIPEGO_GF_FILE ) );
define( 'SWIPEGO_GF_VERSION', '1.0.0' );

// Plugin core class
require( SWIPEGO_GF_PATH . 'includes/class-swipego-gf.php' );

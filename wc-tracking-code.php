<?php
/*
 * Plugin Name: WC Tracking code
 * Version: 1.0
 * Plugin URI: #
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Yuri Sarzi
 * Author URI: #
 * Requires at least: 4.0
 *
 * Text Domain: wc-tracking-code
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Yuri Sarzi
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load plugin class files
require_once( 'includes/class-wc-tracking-code.php' );
require_once( 'includes/class-wc-tracking-code-settings.php' );

// Load plugin libraries
require_once( 'includes/lib/class-wc-tracking-code-admin-api.php' );
require_once( 'includes/lib/class-wc-tracking-code-post-type.php' );
require_once( 'includes/lib/class-wc-tracking-code-taxonomy.php' );

/**
 * Returns the main instance of WC_Tracking_code to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WC_Tracking_code
 */
function WC_Tracking_code () {
	$instance = WC_Tracking_code::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = WC_Tracking_code_Settings::instance( $instance );
	}

	return $instance;
}

WC_Tracking_code();

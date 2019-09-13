<?php
/**
 * Plugin Name: F9slider
 * Plugin URI: https://fervidum.github.io/f9slider/
 * Description: Image slider plugin for WordPress
 * Version: 1.0.0alpha1
 * Author: Fervidum
 * Author URI: https://fervidum.github.io/
 * Text Domain: f9slider
 * Domain Path: /languages/
 * Directory: https://fervidum.github.io/f9slider/
 *
 * @package F9slider
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'F9SLIDER_PLUGIN_FILE' ) ) {
	define( 'F9SLIDER_PLUGIN_FILE', __FILE__ );
}

// Include the main F9slider class.
if ( ! class_exists( 'F9slider', false ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-f9slider.php';
}

/**
 * Returns the main instance of F9slider.
 *
 * @return F9slider
 */
function f9slider() {
	return F9slider::instance();
}

f9slider();

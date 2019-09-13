<?php
/**
 * F9slider Admin
 *
 * @class    F9slider_Admin
 * @package  F9slider/Admin
 */

defined( 'ABSPATH' ) || exit;

/**
 * F9slider_Admin class.
 */
class F9slider_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'selfd_register', array( $this, 'register_selfdirectory' ) );
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		// Self Directory.
		include_once F9SLIDER_ABSPATH . 'includes/vendor/selfd/class-selfdirectory.php';
	}

	/**
	 * Use Selfd to updates.
	 */
	public function register_selfdirectory() {
		selfd( F9SLIDER_PLUGIN_FILE );
	}
}

return new F9slider_Admin();

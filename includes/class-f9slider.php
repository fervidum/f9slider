<?php
/**
 * F9slider setup
 *
 * @package F9slider
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main F9slider Class.
 *
 * @class F9slider
 */
final class F9slider {

	/**
	 * The single instance of the class.
	 *
	 * @var F9slider
	 */
	protected static $instance = null;

	/**
	 * F9slider Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Define F9slider Constants.
	 */
	private function define_constants() {
		$this->define( 'F9SLIDER_ABSPATH', dirname( F9SLIDER_PLUGIN_FILE ) . '/' );
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		if ( $this->is_request( 'admin' ) ) {
			include_once F9SLIDER_ABSPATH . 'includes/admin/class-f9slider-admin.php';
		}
	}

	/**
	 * Init F9slider when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_f9slider_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Init action.
		do_action( 'f9slider_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/f9slider/f9slider-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/f9slider-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			// @todo Remove when start supporting WP 5.0 or later.
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'f9slider' );

		unload_textdomain( 'f9slider' );
		load_textdomain( 'f9slider', WP_LANG_DIR . '/f9slider/f9slider-' . $locale . '.mo' );
		load_plugin_textdomain( 'f9slider', false, plugin_basename( dirname( F9SLIDER_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Main F9slider Instance.
	 *
	 * Ensures only one instance of F9slider is loaded or can be loaded.
	 *
	 * @static
	 * @see f9slider()
	 * @return F9slider - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

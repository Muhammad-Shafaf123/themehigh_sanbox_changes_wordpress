<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       themehigh.com
 * @since      1.0.0
 *
 * @package    THWPSB
 * @subpackage THWPSB/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    THWPSB
 * @subpackage THWPSB/includes
 * @author     ThemeHigh <info@themehigh.com>
 */
class THWPSB {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      THWPSB_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'THWPSB_VERSION' ) ) {
			$this->version = THWPSB_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'thwpsb';

		$this->load_dependencies();
		$this->set_locale();

		// Initiate admin object
		new THWPSB_Admin( $this->get_plugin_name(), $this->get_version() );

		// Initiate sandbox status object
		new THWPSB_Sandbox_Log( $this->get_plugin_name(), $this->get_version() );

		// Initiate sandbox status object
		new THWPSB_Public($this->get_plugin_name(), $this->get_version());

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - THWPSB_i18n. Defines internationalization functionality.
	 * - THWPSB_Autoloader. Orchestrates the hooks of the plugin.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwpsb-i18n.php';

		/**
		 * Action scheduler
		 */
		require_once ( plugin_dir_path( dirname( __FILE__ ) ) . 'includes/libraries/action-scheduler/action-scheduler.php' );

		/**
		 * The class responsible for including class files by calling Object
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-thwpsb-autoloader.php';

		$this->autoloader = new THWPSB_Autoloader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the THWPSB_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new THWPSB_i18n();
		add_action( 'plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain') );
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Write custom log messages to WordPress debug.log file.
	 *
	 * @since     1.0.0
	 * @return    none.
	 */
	public static function write_log ( $log )  {
		if ( true === WP_DEBUG ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}

}

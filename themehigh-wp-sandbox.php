<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              themehigh.com
 * @since             1.0.0
 * @package           THWPSB
 *
 * @wordpress-plugin
 * Plugin Name:       ThemeHigh WP Sandbox
 * Plugin URI:        themehigh.com
 * Description:       Generate sandboxes.
 * Version:           1.0.0
 * Author:            ThemeHigh
 * Author URI:        themehigh.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       thwpsb
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'THWPSB_VERSION', '1.0.0' );
!defined('THWPSB_FILE') && define('THWPSB_FILE', __FILE__);
!defined('THWPSB_PATH') && define('THWPSB_PATH', plugin_dir_path( __FILE__ ));
!defined('THWPSB_URL') && define('THWPSB_URL', plugins_url( '/', __FILE__ ));
!defined('THWPSB_BASE_NAME') && define('THWPSB_BASE_NAME', plugin_basename( __FILE__ ));

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-thwpsb-activator.php
 */
function activate_THWPSB() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-thwpsb-activator.php';
	THWPSB_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-thwpsb-deactivator.php
 */
function deactivate_THWPSB() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-thwpsb-deactivator.php';
	THWPSB_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_THWPSB' );
register_deactivation_hook( __FILE__, 'deactivate_THWPSB' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-thwpsb.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_THWPSB() {
	$plugin = new THWPSB();
	$plugin->run();

}
run_THWPSB();

/**
 * Change Action Scheduler default purge to 12 hour
 * to avoid unnessory log & old actions in Action Scheduler tables
 */
add_filter( 'action_scheduler_retention_period', 'wpb_action_scheduler_purge' );
function wpb_action_scheduler_purge() {
 return 12 * HOUR_IN_SECONDS;
}

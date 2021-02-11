<?php

/**
 * Fired during plugin activation
 *
 * @link       themehigh.com
 * @since      1.0.0
 *
 * @package    THWPSB
 * @subpackage THWPSB/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    THWPSB
 * @subpackage THWPSB/includes
 * @author     ThemeHigh <info@themehigh.com>
 */
class THWPSB_Activator {

	/**
	 * Activate function
	 *
	 * @return None
	 * @since    1.0.0
	 */
	public static function activate() {
		self::create_db_sandbox_data();
	}

	/**
	* Create custom table to save sandbox data
	*
	* @return None
	* @since 1.0.0
	*/
	public static function create_db_sandbox_data() {
			global $wpdb;
			$charset_collate = $wpdb->get_charset_collate();
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			//* Create the table
			$table_name = $wpdb->prefix . 'th_sandbox';
			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
				id int(11) NOT NULL AUTO_INCREMENT,
				source_id INTEGER NOT NULL,
				sandbox_id INTEGER NOT NULL,
				created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				expired_at datetime NULL,
				user_agent TEXT NULL,
				os TEXT NULL,
				ip TEXT NULL,
				country TEXT NULL,
	  			deleted_at datetime NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";
			dbDelta( $sql );
		}

}

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
class THWPSB_Cloner {



			public $is_subdomain = false;
			public $global_tables = array();

			/**
			 * YITH_Live_Demo_Cloner constructor.
			 */
			public function __construct() {
				// check if subdomain
				$this->is_subdomain = ( constant( 'VHOST' ) == 'yes' ) ? true : false;
				//define which tables to skip by default when cloning root site
				$this->global_tables = array(
					'blogs',
					'blog_versions',
					'registration_log',
					'signups',
					'site',
					'sitemeta', //default multisite tables
					'blogmeta', //New global multisite table
					'usermeta',
					'users', //don't copy users
					'bp_.*', //buddypress tables
					'3wp_broadcast_.*', //3wp broadcast tables
					'actionscheduler_*', //Action scheduler
					'th_sandbox' // Sandbox log
				);
			}

			/**
			 * Get the uploads folder for the target site
			 *
			 * @since 1.0.0
			 * @access public
			 * @param string|int $id
			 * @return string
			 * @author Francesco Licandro
			 */
			function get_upload_folder( $id ) {
				switch_to_blog( $id );
				// make sure there is no filter for upload path
				//remove_filter( 'upload_dir', array( YITH_WP_Test_Env(), 'filter_sandbox_upload_dir' ), 10 );

				$src_upload_dir = wp_upload_dir();

				restore_current_blog();

				$folder      = $src_upload_dir['basedir'];

				// validate the folder itself to handle cases where htaccess or themes alter wp_upload_dir() output
				if ( $id != 1 && ( strpos( $folder, '/' . $id ) === false || ! file_exists( $folder ) ) ) {

					$test_dir = WP_CONTENT_DIR . '/uploads/sites/' . $id;
					if ( file_exists( $test_dir ) ) {
						return $test_dir;
					}
				}
				// otherwise we have a standard folder OR could not find a normal folder and are stuck with
				// sending the original wp_upload_dir() back knowing the replace and copy should work
				return $folder;
			}

			/**
			 * Main process for create a sandbox
			 *
			 * @param array $options
			 * @return bool|int
			 */
			public function create_sandbox( $options ) {
				global $wpdb, $current_site;

				if( empty( $options ) ) {
					return false;
				}

				// Declare the locals that need to be available throughout the function:
				$target_subd        = '';
				$target_id          = 0;
				$user_id            = 0;
				$source_id          = $options['source_id'];
				$target_site        = $options['new_site_title'];
				$target_site_name   = sanitize_title_with_dashes( $options['new_site_name'] );

				// Check first for blank site name
				if ( $target_site === '' || $target_site_name === '' ) {
					return false;
				}

				try {

					// CREATE USER
					$user_id = apply_filters( 'ywtenv_current_user_id', get_current_user_id() );
					! $user_id && $user_id = $this->create_user( $target_site_name );
					// if error return
					if ( is_wp_error( $user_id ) ) {
						throw new Exception( sprintf( 'Error creating user for sandbox for site %s. %s', $source_id, $user_id->get_error_message() ) );
					}

					// CREATE THE SITE
					$target_id = $this->create_site( $target_site_name, $target_site, $user_id );
					// if error return
					if ( is_wp_error( $target_id ) ) {
						throw new Exception( sprintf( 'Error creating sandbox for site %s. %s', $source_id, $target_id->get_error_message() ) );
					}

					// first get blog details of source and target
					$source_blog_detail = get_blog_details( $source_id );

					// handle subdomain versus subdirectory modes
					if ( $this->is_subdomain ) {
						// source
						$source_subd = $source_blog_detail->domain;
						// target
						$target_subd = $target_site_name . '.' . $current_site->domain;
					} else {
						// don't want the trailing slash in path just in case there are replacements that don't have it
						// source
						$source_subd = untrailingslashit( $source_blog_detail->domain . $source_blog_detail->path );
						// target
						$target_subd = $current_site->domain . $current_site->path . $target_site_name;
					}

					// source table prefix
					$source_pre = $wpdb->get_blog_prefix( $source_id );
					// target table prefix
					$target_pre = $wpdb->get_blog_prefix( $target_id );

					// RUN CLONE DB
					$clone_result = $this->run_clone( $source_pre, $target_pre );

					// if error return
					if ( is_wp_error( $clone_result ) ) {
						throw new Exception( sprintf( 'Error clone db for site %s. %s', $source_id, $clone_result->get_error_message() ) );
					}

					$this->empty_action_scheduler_tables($target_id);

					// Build replacement array new-site-specific replacements
					$replace_array[ $source_subd ] = $target_subd;
					//reset the option_name = wp_#_user_roles row in the wp_#_options table back to the id of the target site
					$replace_array[ $source_pre . 'user_roles' ] = $target_pre . 'user_roles';

					// uploads location
					if ( $source_id == 1 ) {
						switch_to_blog( $source_id );

						// make sure there is no filter for upload path
						//remove_filter( 'upload_dir', array( YITH_WP_Test_Env(), 'filter_sandbox_upload_dir' ), 10 );

						$main_uploads_info = wp_upload_dir();
						restore_current_blog();

						$main_uploads_dir     = str_replace( get_site_url( '/' ), '', $main_uploads_info['baseurl'] );
						$main_uploads_replace = '/wp-content/uploads/sites/' . $target_id;

						$replace_array[ $main_uploads_dir ] = $main_uploads_replace;

					} else {
						// REPLACEMENTS FOR NON-ROOT SITE CLONING
						$replace_array[ '/sites/' . $source_id . '/' ] = '/sites/' . $target_id . '/';
					}

					// RUN REPLACE CONTENT ON DB
					$this->run_replace( $target_pre, $replace_array );

					// RUN CLONE WP UPLOAD
					//if( ywtenv_get_option( 'ywtenv-clone-wpuploads', $source_id ) === 'yes' ) {

						$clone_result = $this->clone_upload( $source_id, $target_id );

						// if error return
						if ( is_wp_error( $clone_result ) ) {
							throw new Exception( sprintf( 'Error clone wp-content for site %s. %s', $source_id, $clone_result->get_error_message() ) );
						}
					//}

					// Add sandbox to the global list
					//THWPSB_Utils::add_sandbox_to_list( $target_id, $source_id );

					$expired = THWPSB_Utils::calculate_sandbox_expiry($source_id);

					$db_helper = new THWPSB_Db_Helper('th_sandbox', 'multisite_main');
					$new_sandbox = array(
						'sandbox_id' => $target_id,
						'source_id' => $source_id,
						'created_at' => THWPSB_Utils::get_current_time(),
						'expired_at' => $expired,
					);
					$db_helper->insert($new_sandbox);

					// make sure that user is unique for blog
					$user_role = 'administrator';
					$users = get_users( array( 'blog_id' => $target_id ) );
					foreach ( $users as $user ) {
						if ( $user->ID == $user_id && $user_role == 'administrator' ) {
							continue;
						}
						remove_user_from_blog( $user->ID, $target_id, $user_id );
					}
					// then change user role to blog with selected role
					if( $user_role != 'administrator' ){
						add_user_to_blog( $target_id, $user_id, $user_role );
					}

					// Move all posts to the new user
					$wpdb->query( $wpdb->prepare( 'UPDATE ' . $target_pre . 'posts SET post_author = %d', $user_id ) );
					// At last change the blogname of the new site
					$wpdb->query( $wpdb->prepare( 'UPDATE ' . $target_pre . 'options SET option_value = %s WHERE option_name = %s', $target_site, 'blogname' ) );

					do_action('yith_after_clone_site', $source_id, $target_id, $user_id);

					return array( 'newBlogID' => $target_id, 'userID' => $user_id );
				}
				catch ( Exception $err ){
					// add error to error_log
					error_log( $err->getMessage() );
					//  then return error
					return false;
				}
			}

			private function empty_action_scheduler_tables($site_id){
		        global $wpdb;
		        $prefix = $wpdb->get_blog_prefix( $site_id );
		        $tables = array(
		            'actionscheduler_actions',
		            'actionscheduler_claims',
		            'actionscheduler_groups',
		            'actionscheduler_logs'
		        );
		        foreach($tables as $table){
		            $table  = $prefix . $table;
		            $delete = $wpdb->query("TRUNCATE TABLE $table");
		        }
		    }

			/**
			 * Create user and assign it to target site
			 *
			 * @since 1.0.0
			 * @param string $target_site_name The target site name
			 * @return mixed
			 * @author Francesco Licandro
			 */
			public function create_user( $target_site_name ){

				// set user email and user name
				$email = apply_filters( 'ywtenv_new_user_email', $target_site_name . '@email.com' );
				$user_name = preg_replace( '/\s+/', '', sanitize_user( $target_site_name, true ) );

				if( $user = get_user_by( 'email', $email ) ) {
					return $user->ID;
				}

				$user_id = wp_create_user( $user_name, $target_site_name, $email );
				if ( ! is_wp_error( $user_id ) ) {
					// Newly created users have no roles or caps until they are added to a blog.
					delete_user_option( $user_id, 'capabilities' );
					delete_user_option( $user_id, 'user_level' );
				}

				do_action( 'ywtenv_user_created', $user_id );

				return $user_id;
			}

			/**
			 * Create site
			 *
			 * @param string $site_name The site name
			 * @param string $site_title The site title
			 * @param string $user_id The user id
			 * @return mixed array if ok, false otherwise
			 * @author Francesco Licandro
			 */
			protected function create_site( $site_name, $site_title, $user_id = '' ) {
				global $current_site;

				$base       = PATH_CURRENT_SITE;
				$tmp_domain = strtolower( esc_html( $site_name ) );

				if ( $this->is_subdomain ) {
					$domain = $tmp_domain . '.' . $current_site->domain;
					$path   = $base;
				} else {
					$domain = $current_site->domain;
					$path   = $base . $tmp_domain . '/';
				}

				// set domain
				$domain     = preg_replace( '/\s+/', '', sanitize_user( $domain, true ) );
				if ( is_subdomain_install() ) {
					$domain = str_replace( '@', '', $domain );
				}
				// set path
				empty( $path ) && $path = '/';
				$current_site_id = $current_site->id;

				// create site and don't forget to make public:
				$meta['public'] = 1;

				$new_site_id    = wpmu_create_blog( $domain, $path, strip_tags( $site_title ), $user_id, $meta, $current_site_id );

				return $new_site_id;
			}

			/**
			 * Clone site content
			 *
			 * @param $source_prefix
			 * @param $target_prefix
			 * @return bool|object Return true if ok, Wp Error object otherwise
			 * @author Francesco Licandro
			 */
			protected function run_clone( $source_prefix, $target_prefix ) {
				global $db, $wpdb;
				// get list of source tables when cloning root
				if ( $source_prefix == $wpdb->base_prefix ) {
					$tables               = $wpdb->get_results( 'SHOW TABLES' );

					$global_table_pattern = "/^$wpdb->base_prefix(" . implode( '|', $this->global_tables ) . ")$/";
					$table_names          = array();
					foreach ( $tables as $table ) {
						$table         = (array) $table;
						$table_name    = array_pop( $table );
						$is_root_table = preg_match( "/$wpdb->base_prefix(?!\d+_)/", $table_name );
						if ( $is_root_table && ! preg_match( $global_table_pattern, $table_name ) ) {
							array_push( $table_names, $table_name );
						}
					}

					$SQL = "SHOW TABLES WHERE `Tables_in_$db` IN('" . implode( "','", $table_names ) . "')";
				} //get list of source tables when cloning non-root
				else {
					// MUST ESCAPE '_' characters otherwise they will be interpreted as wildcard
					// single chars in LIKE statement and can really hose up the database
					$SQL = 'SHOW TABLES LIKE \'' . str_replace( '_', '\_', $source_prefix ) . '%\'';
				}

				$tables = $wpdb->get_results( $SQL, ARRAY_N );

				// Go through each table and clone it
				$num_tables = 0;
				if ( ! empty( $tables ) ) {
					foreach ( $tables as $key => $value ) {
						if ( ! isset( $value[0] ) ) {
							continue;
						}

						$source_table = $value[0];
						$target_table = str_replace( $source_prefix, $target_prefix, $source_table );

						$num_tables ++;
						if ( $source_table != $target_table ) {
							$this->clone_table( $source_table, $target_table );
						}
					}
				} else {
					return new WP_Error( 'ywtenv_clone', sprintf( 'No data for sql to clone: Query is: %s ', $SQL ) );
				}

				return true;
			}

			/**
			 * Reads the Database table in $source_table and executes SQL Statements for
			 * cloning it to $target_table
			 *
			 * @param string $source_table The start table
			 * @param string $target_table The target table
			 * @author Francesco Licandro
			 */
			protected function clone_table( $source_table, $target_table ) {

				global $wpdb;

				// Drop the new site table if it already exists
				$query = "DROP TABLE IF EXISTS " . $target_table;
				$wpdb->query( $query );

				// Create new site tables
				$query = 'CREATE TABLE ' . $target_table . ' LIKE ' . $source_table;
				$wpdb->query( $query );

				// Copy the contents of the existing tables
				if ( stripos( $source_table, '_options' ) !== false ) {
					$wpdb->query( $wpdb->prepare( 'INSERT ' . $target_table . ' SELECT * FROM ' . $source_table . ' WHERE option_name NOT RLIKE %s AND option_name NOT RLIKE %s', "^_transient_feed_", "^_transient_rss_" ) );
				} else if ( stripos( $source_table, '_posts' ) !== false ) {
					$wpdb->query( $wpdb->prepare( 'INSERT ' . $target_table . ' SELECT * FROM ' . $source_table . ' WHERE post_type != %s', 'revision' ) );
				} else if ( stripos( $source_table, '_postmeta' ) !== false ) {
					$wpdb->query( $wpdb->prepare( 'INSERT ' . $target_table . ' SELECT * FROM ' . $source_table . ' WHERE meta_key != %s AND meta_key != %s', '_edit_lock', '_edit_last' ) );
				} else {
					$wpdb->query( 'INSERT ' . $target_table . ' SELECT * FROM ' . $source_table );
				}
			}

			/**
			 * Replace from cloned db old values with new
			 *
			 * @param $target_prefix
			 * @param $replace_array
			 * @author Francesco Licandro
			 */
			function run_replace( $target_prefix, $replace_array ) {
				global $wpdb;
				$tables_list = $wpdb->get_results( $wpdb->prepare( "SHOW TABLES LIKE %s", $target_prefix . '%' ), ARRAY_N );

				foreach ( $tables_list as $tableName ) {

					$testRow     = $wpdb->get_row( "SELECT * FROM " . $tableName[0] . " LIMIT 1", ARRAY_N );
					//$primaryKeys = $wpdb->get_col_info( 'primary_key', - 1 );
					$columnNames = $wpdb->get_col_info( 'name', - 1 );
					// Search for fields we can replace
					$where = '';
					foreach ( $replace_array as $oldValue => $newValue ) {
						foreach ( $columnNames as $columnName ) {
							$where .= empty( $where ) ? '' : ' OR ';
							$where .= $wpdb->prepare( $columnName . ' like %s ', '%' . $oldValue . '%' );
						}
					}

					$resultsToReplace = $wpdb->get_results( "SELECT * FROM " . $tableName[0] . " WHERE " . $where, ARRAY_A );

					// If we don't have anything to change, continue
					if ( ! count( $resultsToReplace ) ) {
						continue;
					}

					// Go through each row that we need to update and perform the string replace
					foreach ( $resultsToReplace as $rowToReplace ) {
						$query = 'UPDATE ' . $tableName[0];
						$set   = '';

						$performUpdateQuery = false;

						foreach ( $rowToReplace as $columnName => $resultToReplace ) {
							if ( ! is_string( $resultToReplace ) ) {
								continue;
							}

							$editedValue = $resultToReplace;
							if ( is_serialized( $resultToReplace ) ) {
								$unserialized = unserialize( $editedValue );
								foreach ( $replace_array as $oldValue => $newValue ) {
									$this->recursive_array_replace( $oldValue, $newValue, $unserialized );
								}
								$editedValue = serialize( $unserialized );
							} else { // normal string
								foreach ( $replace_array as $oldValue => $newValue ) {
									if ( stripos( $editedValue, $newValue ) === false ) {
										$editedValue = str_replace( $oldValue, $newValue, $editedValue );
									}
								}
							}

							if ( $editedValue != $resultToReplace ) {
								$set .= empty( $set ) ? '' : ', ';
								$set .= $wpdb->prepare( $columnName . ' = %s ', $editedValue );
								$performUpdateQuery = true;
							}
						}
						$query .= ' SET ' . $set;

						// Form where keys based on the primary keys ('ID' or '*_ID')
						$whereKeys = '';
						foreach ( $rowToReplace as $columnName => $resultToReplace ) {
							if ( strtolower( $columnName ) != 'id' || stripos( strtolower( $columnName ), '_id' ) === false ) {
								continue;
							}
							$whereKeys .= empty( $whereKeys ) ? '' : ' AND ';
							$whereKeys .= $wpdb->prepare( $columnName . ' = %s ', $resultToReplace );
						}
						// If no where clause yet, use everything
						if ( empty( $whereKeys ) ) {
							foreach ( $rowToReplace as $columnName => $resultToReplace ) {
								$whereKeys .= empty( $whereKeys ) ? '' : ' AND ';
								$whereKeys .= $wpdb->prepare( $columnName . ' = %s ', $resultToReplace );
							}
						}
						$query .= ' WHERE ' . $whereKeys;

						if ( $performUpdateQuery ) {
							$wpdb->query( $query );
						}
					}
				}
			}

			function recursive_array_replace( $find, $replace, &$data ) {
				if ( is_array( $data ) ) {
					foreach ( $data as $key => $value ) {
						// check for an array for recursion
						if ( is_array( $value ) ) {
							$this->recursive_array_replace( $find, $replace, $data[ $key ] );
						} else {
							// have to check if it's string to ensure no switching to string for booleans/numbers/nulls - don't need any nasty conversions
							if ( is_string( $value ) ) {
								$data[ $key ] = $value;
								if ( stripos( $data[ $key ], $replace ) === false ) {
									$data[ $key ] = str_replace( $find, $replace, $data[ $key ] );
								}
							}
						}
					}
				} else {
					if ( is_string( $data ) ) {
						if ( stripos( $data, $replace ) === false ) {
							$data = str_replace( $find, $replace, $data );
						}
					}
				}
			}

			/**
			 * Clone the upload dir
			 *
			 * @since 1.0.0
			 * @param int $source_id The source id
			 * @param int $target_id The target id
			 * @return mixed
			 * @author Francesco Licandro
			 */
			protected function clone_upload( $source_id, $target_id){
				// get the right paths to use
				// handle for uploads location when cloning root site
				$src_blogs_dir = $this->get_upload_folder( $source_id );
				if ( $source_id == 1 ) {
					$dst_blogs_dir = WP_CONTENT_DIR . '/uploads/sites/' . $target_id;;
				} else {
					$dst_blogs_dir = $this->get_upload_folder( $target_id );
				}

				if ( strpos( $src_blogs_dir, '/' ) !== false && strpos( $src_blogs_dir, '\\' ) !== false ) {
					$src_blogs_dir = str_replace( '/', '\\', $src_blogs_dir );
					$dst_blogs_dir = str_replace( '/', '\\', $dst_blogs_dir );
				}
				if ( is_dir( $src_blogs_dir ) ) {
					$num_files = self::recursive_file_copy( $src_blogs_dir, $dst_blogs_dir, 0 );
				} else {
					// Could not copy files
					return new WP_Error( 'ywtenv_clone', 'Could not copy files from parent wp-uploads directory' );
				}

				return $num_files;
			}

			/**
			 * Copy files and directories recursively and return number of copies executed
			 *
			 * @since 1.0.0
			 * @return mixed
			 */
			function recursive_file_copy( $src, $dst, $num ) {
				$num = $num + 1;
				if ( is_dir( $src ) ) {
					if ( ! file_exists( $dst ) ) {
						mkdir( $dst );
					}
					$files = scandir( $src );
					foreach ( $files as $file ) {
						if ( $file != "." && $file != ".." && $file != 'sites' ) {
							$num = self::recursive_file_copy( "$src/$file", "$dst/$file", $num );
						}
					}
				} else if ( file_exists( $src ) ) {
					copy( $src, $dst );
				}

				return $num;
			}

}

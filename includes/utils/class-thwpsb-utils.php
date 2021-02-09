<?php

/**
 * The file that defines the plugin utility functions
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
 * The helper class.
 *
 * This is used to define utility functions.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    THWPSB
 * @subpackage THWPSB/includes
 * @author     ThemeHigh <info@themehigh.com>
 */
class THWPSB_Utils {

    public static function get_expired_sandboxes(){

        $sandbox_db = new THWPSB_Db_Helper('th_sandbox', 'multisite_main');

        $exp_sandboxes = $sandbox_db->get_wheres(
            $column      = 'sandbox_id',
            $conditions  = array(
                                'source_id' => '',
                                'expired_at'     => self::get_current_time()
                            ),
            $operator    = array(
                                'source_id' => 'NOT NULL',
                                'expired_at' => '<',
                            ),
            $format      = array(
                                'source_id' => '%d',
                                'expired_at' => '%s',
                            ),
            $orderby     = 'id',
            $order       = 'ASC',
            $output_type = OBJECT_K
        );

		return $exp_sandboxes;
    }

    public static function remove_sb_upload_dir($site_id){
        $wp_upload_dir = wp_get_upload_dir();
		$sb_upload_dir = $wp_upload_dir['basedir'] . "/sites/" .$site_id;
        self::delete_files($sb_upload_dir);
        return true;
    }

    public static function get_current_time($format = 'mysql'){
        return current_time($format, true);
    }

    // public static function datetime_add_minutes($datetime_string, $minutes){
    //
    //     $new_date_time = date('Y-m-d H:i:s', strtotime( date($datetime_string) ) +  $minutes * MINUTE_IN_SECONDS );
    //     return $new_date_time;
    // }

    public static function calculate_sandbox_expiry($sb_id){
        $sb_lifetime = THWPSB_Utils::get_plugin_setting('sb_lifetime');
        return date('Y-m-d H:i:s', strtotime( current_time('mysql', true) ) +  $sb_lifetime * MINUTE_IN_SECONDS );
    }


    public static function get_site_tables($site_id){
        global $wpdb;
    	$prefix = $wpdb->get_blog_prefix( $site_id );
    	$sql = "SHOW TABLES LIKE '$prefix%'";
    	$tables = $wpdb->get_results( $sql, ARRAY_N );
        return $tables;
    }

    public static function drop_site_tables($tables){
        global $wpdb;
        foreach($tables as $table){
            $wpdb->query( "DROP TABLE IF EXISTS $table[0]" );
        }
        return true;
    }

    /*
     * php delete function that deals with directories recursively
     */
    protected static function delete_files($target) {
        $status = false;
        if(is_dir($target)){
            $files = glob( $target . '*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

            foreach( $files as $file ){
                self::delete_files( $file );
            }
            if(file_exists($target)){
                $status = rmdir( $target );
            }
            return $status;
        } elseif(is_file($target)) {
            if(file_exists($target)){
                $status = unlink( $target );
            }
            return $status;
        }
    }


    /**
     * Delete all users of the blog only if they haven't other sites
     *
     * @since 1.0.0
     * @param int|string $sandbox_id
     * @author Francesco Licandro
     */
    public static function delete_users( $sandbox_id ) {
        // get users
        $users = get_users( array( 'blog_id' => $sandbox_id ) );

        // check for wpmu_delete_user function
        if( ! function_exists( 'wpmu_delete_user' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/ms.php' );
        }

        // hook before users delete
        do_action( 'thwpsb_before_delete_user', $sandbox_id );

        foreach ( $users as $user ) {

            if( is_super_admin( $user->ID ) ) {
                continue;
            }

            $sites = get_blogs_of_user( $user->ID );
            // if have only one site
            if ( count( $sites ) <= 1 ) {
                wpmu_delete_user( $user->ID );
            }
        }
    }

    public static function delete_site($site_id){
        if( ! function_exists( 'wpmu_delete_blog' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/ms.php' );
		}

        //self::empty_action_scheduler_tables($site_id);

		// delete the blog
		wpmu_delete_blog( $site_id, true );
    }

    // private static function empty_action_scheduler_tables($site_id){
    //     global $wpdb;
    //     $prefix = $wpdb->get_blog_prefix( $site_id );
    //     $tables = array(
    //         'actionscheduler_actions',
    //         'actionscheduler_claims',
    //         'actionscheduler_groups',
    //         'actionscheduler_logs'
    //     );
    //     foreach($tables as $table){
    //         $table  = $prefix . $table;
    //         $delete = $wpdb->query("TRUNCATE TABLE $table");
    //     }
    // }

    public static function is_sandbox($site_id){
        $sandbox_db = new THWPSB_Db_Helper('th_sandbox', 'multisite_main');
        $sandbox  = $sandbox_db->get_row( $column = 'sandbox_id', $value = $site_id, $format = '%d', $output_type = OBJECT, $offset = 0 );
        if(!empty($sandbox) && $sandbox->source_id){
            return $sandbox->source_id;
        }
        return false;
    }

    public static function is_sandbox_enabled(){
        $options = get_option( 'sandbox_settings' );
        $enabled = isset($options['enabled']) ? $options['enabled'] : false;
        if($enabled == 1){
            return true;
        }
        return false;
    }

    // public static function get_site_setting($key){
    //     $options = get_option( 'sandbox_settings' );
    //     $value = isset($options[$key]) ? $options[$key] : '';
    //     return $value;
    // }

    public static function get_site_expiry($site_id){
        $sandbox_db = new THWPSB_Db_Helper('th_sandbox', 'multisite_main');
        $sandbox  = $sandbox_db->get_row( $column = 'sandbox_id', $value = $site_id, $format = '%d', $output_type = OBJECT, $offset = 0 );
        if(!empty($sandbox) && $sandbox->expired_at){
            return $sandbox->expired_at;
        }
        return false;
    }

    public static function get_time_diff($dt_1, $dt_2){
        $date1 = new DateTime($dt_1);
        $date2 = new DateTime($dt_2);
        $diff = $date2->getTimestamp() - $date1->getTimestamp();
        // if($diff->invert == 1 ){
        //     return '0';
        // }
        return $diff;
    }

    public static function get_plugin_setting($key){
        if($key == 'sb_lifetime'){
            return 5;
        }elseif($key == 'sb_optimize_interval' ){
            return 2;
        }
    }

    /**
     *
     * Group sub-arrays ( of multidimensional array ) by certain key
     * @return array
     *
     */
    public static function array_multi_group_by_key($input_array, $key, $remove_key = false, $flatten_output = false)
    {
        $output_array = [];
        foreach ($input_array as $array) {
            if ($flatten_output) {
                $output_array[$array[$key]] = $array;
                if ($remove_key) {
                    unset($output_array[$array[$key]][$key]);
                }
            } else {
                $output_array[$array[$key]][] = $array;
                if ($remove_key) {
                    unset($output_array[$array[$key]][0][$key]);
                }
            }
        }
        return $output_array;
    }

    public static function colors_array(){
        return array('#FF6633','#FFB399', '#FF33FF', '#FFFF99', '#00B3E6',
        				  '#E6B333', '#3366E6', '#999966', '#99FF99', '#B34D4D',
        				  '#80B300', '#809900', '#E6B3B3', '#6680B3', '#66991A',
        				  '#FF99E6', '#CCFF1A', '#FF1A66', '#E6331A', '#33FFCC',
        				  '#66994D', '#B366CC', '#4D8000', '#B33300', '#CC80CC',
        				  '#66664D', '#991AFF', '#E666FF', '#4DB3FF', '#1AB399',
        				  '#E666B3', '#33991A', '#CC9999', '#B3B31A', '#00E680',
        				  '#4D8066', '#809980', '#E6FF80', '#1AFF33', '#999933',
        				  '#FF3380', '#CCCC00', '#66E64D', '#4D80CC', '#9900B3',
        				  '#E64D66', '#4DB380', '#FF4D4D', '#99E6E6', '#6666FF');
    }

}

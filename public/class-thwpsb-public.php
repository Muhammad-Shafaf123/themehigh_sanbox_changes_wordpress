<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       themehigh.com
 * @since      1.0.0
 *
 * @package    THWPSB
 * @subpackage THWPSB/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    THWPSB
 * @subpackage THWPSB/public
 * @author     ThemeHigh <info@themehigh.com>
 */
class THWPSB_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles') );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );

		// add ajax call for create demo
		add_action( 'wp_ajax_thwpsb_create_sandbox', array( $this, 'create_new_sandbox' ) );
		add_action( 'wp_ajax_nopriv_thwpsb_create_sandbox', array( $this, 'create_new_sandbox' ) );
		add_action( 'wp_footer', array( $this, 'show_sandbox_details' ));
		add_filter( 'body_class', array( $this, 'custom_body_classes') );

		add_action('admin_bar_menu', array( $this, 'new_adminbar_item'), 999);

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$sandbox_enabled = THWPSB_Utils::is_sandbox_enabled();
		if(!$sandbox_enabled){
			return;
		}
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in THWPSB_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The THWPSB_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/thwpsb-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		$sandbox_enabled = THWPSB_Utils::is_sandbox_enabled();
		if(!$sandbox_enabled){
			return;
		}
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in THWPSB_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The THWPSB_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/thwpsb-public.js', array( 'jquery' ), $this->version, false );

		wp_localize_script( $this->plugin_name, 'thwpsb_args', array(
			'ajaxurl'       => admin_url( 'admin-ajax.php' ),
			'nonce'			=> wp_create_nonce('new-box-nonce'),
			'errorMsg'      => apply_filters( 'thwpsb_error_creation_msg', __( 'An error happened. Please try again.', 'thwpsb' ) ),
			'sb_lifetime'	=> THWPSB_Utils::get_plugin_setting('sb_lifetime'),
		) );

	}

	public function show_sandbox_details(){
		$sandbox_enabled = THWPSB_Utils::is_sandbox_enabled();
		if(!$sandbox_enabled){
			return;
		}
		$blogID = get_current_blog_id();
		$is_sandbox = THWPSB_Utils::is_sandbox($blogID);
		if($is_sandbox) {
			if(is_user_logged_in()){
				$html = THWPSB_Utils_Public::render_sandbox_expiry_warning($blogID);
			}
			// }else{
			// 	$html = THWPSB_Utils_Public::render_new_sandbox_bar();
			// }
		}else{
			if(!is_user_logged_in()){
				$html = THWPSB_Utils_Public::render_new_sandbox_bar();
			}
		}
		echo $html;
	}

	public function create_new_sandbox(){
		$sandbox_enabled = THWPSB_Utils::is_sandbox_enabled();
		if(!$sandbox_enabled){
			return;
		}

		if( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] != 'thwpsb_create_sandbox' ){
			die();
		}
		if(! check_ajax_referer( 'new-box-nonce')){
			die();
		}

		$response = $this->themehigh_create_sandbox();

		if( ! $response || ! isset( $response['userID'] ) || ! isset( $response['newBlogID'] ) ) {
			die();
		}

		do_action( 'ywtenv_new_sandbox_cretaed', $response );

		// first login user
		wp_set_auth_cookie( $response['userID'], true );

		// Redirect user based on site option
		switch_to_blog( $response['newBlogID'] );
		$options = get_option( 'sandbox_settings' );
		restore_current_blog();
		$redirect_url = $enabled = isset($options['redirect_url']) ? $options['redirect_url'] : false;
		if($redirect_url){
			echo get_site_url( $response['newBlogID'] ) . $redirect_url;
		}else{
			echo get_site_url( $response['newBlogID'] ) . '/wp-admin';
		}

		die();
	}


	/**
	 * This function create new sandbox
	 *
	 * @since 1.0.0
	 * @param int|string $blogID The original blog ID
	 * @return array
	 * @author ThemeHigh
	 */
	function themehigh_create_sandbox( $blogID = 0 ){

		global $host, $db, $usr, $pwd;

		if( ! $blogID ) {
			$blogID = get_current_blog_id();
		}

		// If new request from a sandbox, prepare sandbox for parent
		$parent_id = get_site_meta( $blogID, 'th_parent', true );
		if( $parent_id ){
			$blogID = $parent_id;
		}

		$sandboxName                = wp_generate_uuid4();
		$blogDetails                = get_blog_details( $blogID );

		// Set YITH Cloner variables
		$host                       = DB_HOST;
		$db                         = DB_NAME;
		$usr                        = DB_USER;
		$pwd                        = DB_PASSWORD;
		$options['source_id']       = $blogID;
		$options['new_site_title']  = sprintf( __( 'Sandbox for %s', 'yith-wordpress-test-environment' ), $blogDetails->blogname );
		$options['new_site_name']   = $sandboxName;
		$response                   = false;

		/*
		 * Make sure that site doesn't exists
		 */
		$site_id = get_id_from_blogname( $sandboxName );
		// return if exists
		if( ! is_null( $site_id ) ) {
			$response['newBlogID'] = $site_id;
			// get user from this blog
			$users = get_users( array( 'blog_id' => $site_id ) );
			foreach( $users as $user ) {
				$response['userID'] = $user->ID;
				break;
			}
		}
		// create new
		else {

			// if( ! ywtenv_can_site_have_sandbox( $blogID ) ) {
			// 	// if max number of sandbox are active exit
			// 	return false;
			// }

			$clone = new THWPSB_Cloner();

			$response = $clone->create_sandbox($options);

			// if response is positive save sandbox ID
			$sandboxes_active[$response['newBlogID']] = true;
			update_blog_option( $blogID, 'ywtenv-sandboxes-active', $sandboxes_active );
		}

		return $response;
	}

	function custom_body_classes( $classes ) {
		$sandbox_enabled = THWPSB_Utils::is_sandbox_enabled();
		if(!$sandbox_enabled){
			return;
		}

		$blogID = get_current_blog_id();
		$is_sandbox = THWPSB_Utils::is_sandbox($blogID);
		if(!$is_sandbox){
			if(!is_user_logged_in()){
				$classes[] = 'sandbox-ready';
			}
		}

	    return $classes;

	}

	// update toolbar
	public function new_adminbar_item($wp_adminbar) {
		if(!is_admin()){
	  $wp_adminbar->add_node([
		'id' => 'thwpsb',
		'title' => 'View Dashboard',
		'href' => get_admin_url(),
		'meta' => [
		  'target' => 'thwpsb'
		]
	  ]);
  		}
	}

}

<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       themehigh.com
 * @since      1.0.0
 *
 * @package    THWPSB
 * @subpackage THWPSB/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    THWPSB
 * @subpackage THWPSB/admin
 * @author     ThemeHigh <info@themehigh.com>
 */
class THWPSB_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );


		add_action('network_admin_menu', array($this, 'network_menu'));

		// Hook to columns on network sites listing
		add_filter( 'wpmu_blogs_columns', array($this, 'mfs_blogs_columns') );

		// Hook to manage column data on network sites listing
		add_action( 'manage_sites_custom_column', array($this, 'mfs_sites_custom_column'), 10, 2 );

		add_action( 'init', array($this, 'clean_sandbox_cron') );
		add_action('thwpsb_clean_sandbox_scheduler', array($this, 'prepare_cleaning_schedules') );
		add_action('thwpsb_delete_sandbox', array($this, 'scheduled_sandbox_delete_action') );

		add_action( 'admin_footer', array( $this, 'show_sandbox_details' ));

		add_action('admin_menu', array( $this, 'subsite_sandbox_menu' ));
		add_action('admin_init', array( $this, 'sandbox_settings_init'));
		// admin_bar_menu hook
		add_action('admin_bar_menu', array( $this, 'new_adminbar_item'), 999);
	}

	// public function your_function(){
	// 	add_settings_field(
	// 	    'Checkbox Element',
	// 	    'Checkbox Element',
	// 	    array($this, 'sandbox_checkbox_element_callback'),
	// 	    'sandbox_theme_input_examples',
	// 	    'input_examples_section'
	// 	);
	// }

	function sandbox_settings_init(  ) {
	    register_setting( 'sb_settings', 'sandbox_settings' );
	    add_settings_section(
	        'sandbox_settings_section',
	        __( 'General Settings', 'wordpress' ),
	        array($this, 'stp_api_settings_section_callback'),
	        'sb_settings'
	    );

		add_settings_field(
		    'enabled',
		    __( 'Enable Sandboxes', 'wordpress' ),
		    array($this, 'render_checkbox_field'),
		    'sb_settings',
		    'sandbox_settings_section'
		);

	    add_settings_field(
	        'redirect_url',
	        __( 'Redirect URL', 'wordpress' ),
	        array($this, 'render_text_field'),
	        'sb_settings',
	        'sandbox_settings_section'
	    );
	}

	function render_text_field() {
	    $options = get_option( 'sandbox_settings' );
		$url = isset($options['redirect_url']) ? $options['redirect_url'] : '';
	    ?>
	    <input type='text'
		name='sandbox_settings[redirect_url]'
		value='<?php echo $url; ?>'>
	    <?php
	}

	function render_checkbox_field() {
	    $options = get_option( 'sandbox_settings' );
		$enabled = isset($options['enabled']) ? $options['enabled'] : false; ?>
		<input type="checkbox" id="enable_sandbox" name="sandbox_settings[enabled]" value="1" <?php checked( $enabled, 1, true ); ?>/>
	    <label for="enable_sandbox"></label>
	    <?php
	}

	function stp_api_settings_section_callback(  ) {
	    echo __( 'This settings are each site specific.', 'wordpress' );
	}

	function stp_api_options_page(){
	    ?>
		<div class="wrap">
			<h2>Sandbox Settings</h2>
			<form action='options.php' method='post'>
		        <?php
		        settings_fields( 'sb_settings' );
		        do_settings_sections( 'sb_settings' );
		        submit_button();
		        ?>
		    </form>
		</div>
	    <?php
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/thwpsb-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/thwpsb-admin.js', array( 'jquery' ), $this->version, false );

	}


	public function network_menu() {
		$manage_sandbox = new THWPSB_Sandbox_Manage;
	    add_menu_page(
	        "Manage Sandbox",
	        "Sandbox",
	        'manage_network_options',
	        'th-sandbox',
	        array($manage_sandbox, 'list_table_page')
	    );
		add_submenu_page(
			'th-sandbox',
			'Manage Sandbox',
			'Manage Sandbox',
		    'manage_network_options',
			'th-sandbox'
		);
		add_submenu_page(
			'th-sandbox',
			'Sandbox Settings',
			'Settings',
		    'manage_network_options',
			'th-sandbox-settings',
			array($this, 'network_menu_callback')
		);

	}

	/**
	 * Adds a new top-level page to the administration menu.
	 */
	public function subsite_sandbox_menu() {
	     add_menu_page(
	        __( 'WP Sandbox', 'textdomain' ),
	        __( 'WP Sandbox','textdomain' ),
	        'manage_network_options',
	        'thwpsb-sandbox',
	        array($this, 'thwpsb_sandbox_callback'),
	        ''
	    );

		add_submenu_page(
		  'thwpsb-sandbox',
		   __( 'Sandbox Settings', 'textdomain' ),
		   __( 'Settings','textdomain' ),
		   'manage_network_options',
		   'thwpsb-sandbox-setting',
		   array($this, 'stp_api_options_page'),
		   5
	   );
	   //call register settings function
	//add_action( 'admin_init', array($this, 'register_my_cool_plugin_settings') );
	}

	public function thwpsb_sandbox_callback(){
		?>
		<div class="wrap">
			<h2>Sandboxes</h2>
		</div>
		<?php
	}

	function register_my_cool_plugin_settings() {
		//register our settings
		register_setting( 'my-cool-plugin-settings-group', 'new_option_name' );
		register_setting( 'my-cool-plugin-settings-group', 'some_other_option' );
		register_setting( 'my-cool-plugin-settings-group', 'option_etc' );
	}

	public function thwpsb_sandbox_setting_callback(){
		?>
		<div class="wrap">
			<h1>Sandbox Setting</h1>


			<form method="post" action="options.php">
			    <?php settings_fields( 'my-cool-plugin-settings-group' ); ?>
			    <?php do_settings_sections( 'my-cool-plugin-settings-group' ); ?>
			    <table class="form-table">
			        <tr valign="top">
			        <th scope="row">New Option Name</th>
			        <td><input type="text" name="new_option_name" value="<?php echo esc_attr( get_option('new_option_name') ); ?>" /></td>
			        </tr>

			        <tr valign="top">
			        <th scope="row">Some Other Option</th>
			        <td><input type="text" name="some_other_option" value="<?php echo esc_attr( get_option('some_other_option') ); ?>" /></td>
			        </tr>

			        <tr valign="top">
			        <th scope="row">Options, Etc.</th>
			        <td><input type="text" name="option_etc" value="<?php echo esc_attr( get_option('option_etc') ); ?>" /></td>
			        </tr>
			    </table>

			    <?php submit_button(); ?>

			</form>

		</div>
		<?php
	}



	public function network_menu_callback(){
		?>
		<div class="wrap">
			<h2>Sandboxes</h2>
		</div>
		<?php
	}


	/**
	* To add a columns to the sites columns
	*
	* @param array
	*
	* @return array
	*/
	public function mfs_blogs_columns($sites_columns)
	{
	    $columns_1 = array_slice( $sites_columns, 0, 2 );
	    $columns_2 = array_slice( $sites_columns, 2 );

	    $sites_columns = $columns_1 + array( 'expiry' => 'Expiry' ) + array( 'parent' => 'Parent' ) + $columns_2;

	    return $sites_columns;
	}

	/**
	* Show blog id
	*
	* @param string
	* @param integer
	*
	* @return void
	*/
	public function mfs_sites_custom_column($column_name, $blog_id)
	{
	    if ( $column_name == 'expiry' ) {
			$expiry = get_site_meta( $blog_id, 'th_expired', true);
			if($expiry){
				echo $expiry;
			}else{
				echo "";
			}
	    }elseif($column_name == 'parent'){
			$parent_id = get_site_meta( $blog_id, 'th_parent', true);
			if($parent_id){
				echo $parent_id;
			}else{
				echo "";
			}
		}
	}

	public function clean_sandbox_cron(){
		$site_id = get_current_blog_id();
		$is_sandbox = THWPSB_Utils::is_sandbox($site_id);
		if($is_sandbox){
			return;
		}
		$optimize_interval = THWPSB_Utils::get_plugin_setting('sb_optimize_interval');
		if ( false === as_next_scheduled_action( 'thwpsb_clean_sandbox_scheduler' ) ) {
			as_schedule_recurring_action( strtotime( 'today' ), $optimize_interval * MINUTE_IN_SECONDS, 'thwpsb_clean_sandbox_scheduler' );
		}
	}

	public function prepare_cleaning_schedules(){
		$e_sandboxes = THWPSB_Utils::get_expired_sandboxes();
		if(!empty($e_sandboxes) && is_array($e_sandboxes)){
			foreach($e_sandboxes as $e_sandbox){
				$data = array(
                        'sandbox_id' => $e_sandbox->sandbox_id,
                );
				as_schedule_single_action( time(), 'thwpsb_delete_sandbox', $data );
			}
		}
	}

	public function scheduled_sandbox_delete_action($site_id){

		$blogDetails = get_blog_details( $site_id );
		if(!$blogDetails){
			return true;
		}

		$sandbox_tables = THWPSB_Utils::get_site_tables($site_id);

		// Remove sandbox upload dir
		THWPSB_Utils::remove_sb_upload_dir($site_id);

		// Delete all users
		THWPSB_Utils::delete_users($site_id);

		// finally delete blog
		THWPSB_Utils::delete_site($site_id);

		THWPSB_Utils::drop_site_tables($sandbox_tables);

		return true;

	}

	public function show_sandbox_details(){
		$html = '';
		$blogID = get_current_blog_id();
		$is_sandbox = THWPSB_Utils::is_sandbox($blogID);
		if($is_sandbox) {
			if(is_user_logged_in()){
				$html = THWPSB_Utils_Public::render_sandbox_expiry_warning($blogID);
			}
		}
		echo $html;
	}

	// update toolbar
	public function new_adminbar_item($wp_adminbar) {
		if(is_admin()){
		  $wp_adminbar->add_node([
	  	    'id' => 'thwpsb',
	  	    'title' => 'View Frontend',
	  	    'href' => get_site_url(),
	  	    'meta' => [
	  	      'target' => 'thwpsb'
	  	    ]
	  	  ]);
		}
	}

}

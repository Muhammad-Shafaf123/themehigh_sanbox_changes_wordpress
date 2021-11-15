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

		// Enqueue scripts & styles in admin end
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );

		// Custom network menu
		add_action('network_admin_menu', array($this, 'network_menu'));

		// // Hook to columns on network sites listing
		// add_filter( 'wpmu_blogs_columns', array($this, 'mfs_blogs_columns') );
		//
		// // Hook to manage column data on network sites listing
		// add_action( 'manage_sites_custom_column', array($this, 'mfs_sites_custom_column'), 10, 2 );

		// Initalize scheduling
		add_action( 'init', array($this, 'schedule_sandbox_cleaning') );

		// Prepare sandbox cleaning schedules
		add_action('thwpsb_clean_sandbox_schedule', array($this, 'prepare_cleaning_schedules') );

		// Performing thwpsb_delete_sandbox actions
		add_action('thwpsb_delete_sandbox', array($this, 'scheduled_sandbox_delete_action') );

		// Show sandbox expiry box
		add_action( 'admin_footer', array( $this, 'show_sandbox_exiry_box' ));

		// Admin menu on subsite
		add_action('admin_menu', array( $this, 'subsite_sandbox_menu' ));

		// Initialize sandbox settings
		add_action('admin_init', array( $this, 'sandbox_settings_init'));

		// new menu item in admin bar
		add_action('admin_bar_menu', array( $this, 'new_adminbar_item'), 999);

		// Custom to add text in header.
		add_action( 'admin_head',  array( $this, 'new_admin_header_field'));

		// Custom to add text in footer.
		add_action( 'admin_footer',  array( $this, 'new_admin_footer_field'));

		// Codemirror enqueue scripts
		add_action('admin_enqueue_scripts',array($this, 'codemirror_enqueue_scripts'));


	}
	/**
	* Initialize settings API
	*
	* @since 1.0.0
	*/
	function sandbox_settings_init(  ) {
	    register_setting( 'sb_settings', 'sandbox_settings' );
			register_setting( 'sb_settings', 'admin_header_text' );
			register_setting( 'sb_settings', 'admin_footer_text' );
			register_setting( 'sb_settings', 'front_header_text' );
			register_setting( 'sb_settings', 'front_footer_text' );

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

			// personal edite - shafaf - Add settings label.
			add_settings_field(
					'code_id',
			 		__('code field','wordpress'),
			 		array($this, 'render_code_area'),
					'sb_settings',
					'sandbox_settings_section'
			);
	}

	/**
	* Render text field in settings API
	*/
	function render_text_field() {
	    $options = get_option( 'sandbox_settings' );
		$url = isset($options['redirect_url']) ? $options['redirect_url'] : '';
	    ?>
	    <input type='text'
		name='sandbox_settings[redirect_url]'
		value='<?php echo $url; ?>'>
	    <?php
	}

	/**
	* display field
	*/
	function render_code_area(){
	/*

		$admin_footer_text = get_option('admin_footer_text');
		$front_header_text = get_option('front_header_text');
		$front_footer_text = get_option('front_footer_text');
		echo __('<label>Admin Header Text</label>', 'wordpress');
		echo '<br><textarea name="admin_header_text" id="fancy_textarea_admin_header">' . esc_textarea($admin_header_text) . '</textarea><br>';
		echo __('<label>Admin Footer Text</label>', 'wordpress');
		echo '<br><textarea name="admin_footer_text" id="fancy_textarea_admin_footer">' . esc_textarea($admin_footer_text) . '</textarea><br>';
		echo __('<label>Front End Header Text</label>', 'wordpress');
		echo '<br><textarea name="front_header_text" id="fancy_textarea_frontend_header">' . esc_textarea($front_header_text) . '</textarea><br>';
		echo __('<label>Front End Footer Text</label>', 'wordpress');
		echo '<br><textarea name="front_footer_text" id="fancy_textarea_frontend_footer">' . esc_textarea($front_footer_text) . '</textarea>';



	*/
	$admin_header_text = get_option('admin_header_text');
	?>
	<div class="sandbox-show-field">
		<span  class="sandbox-field-plus-button dashicons dashicons-plus" onclick="sandbox_field_add_item()"></span>
	</div>

	<div id="thfaqf_faq_form" class="thfaqf_faq_form">
                <?php echo $this->textarea_markup(); ?>
            </div>

            <div id="thfaqf_new_faq_form" style="display:none;">
                <?php
                    $new_faq_form = $this->textarea_markup();
                    echo $new_faq_form;
                ?>
            </div>


	<?php
	}
 function textarea_markup(){
	 ?>
	 <div id="show_item"><?php
 	//	echo '<br><textarea name="admin_header_text" class="fancy_textarea_admin_header" id="textarea_admin_header">' . esc_textarea($admin_header_text) . '</textarea><br>'; ?>
 	<div class="sandbox-textarea-field">
 		<textarea style="float: left;" name="name" rows="8" cols="80"></textarea>
 	</div><!--
 	<div class="sandbox-select-option">
 		<label for="code show in">Code Show In :</label><br>
 		<select  name="">
 			<option value="Header and Footer">Header and Footer</option>
 			<option value="Header">Header Only</option>
 			<option value="Footer">Footer Only</option>
 		</select>
 	</div>
 	<div class="show-clone-checkbox">
 		<label for="Show In Clone Site :">Show In Clone Site :</label>
 		<label for="Yes"> Yes</label>
 		<input type="radio" name="yes" value="checked">
 		<label for="No"> No</label>
 		<input type="radio" name="yes" value="checked1">
 	</div>
 	<div class="sandbox-select-option">
 		<label for="code show in">select sanboxes</label><br>
 		<select  name="">
 			<option value="Sandbox One">Sandbox One</option>
 			<option value="Sandbox One">Sandbox One</option>
 			<option value="Sandbox One">Sandbox One</option>
 			<option value="Sandbox One">Sandbox One</option>

 		</select>
 	</div>
 -->
 	</div>
	 <?php
 }
	/**
	* Render checkbox field in settings API
	*/
	function render_checkbox_field() {
	    $options = get_option( 'sandbox_settings' );
		$enabled = isset($options['enabled']) ? $options['enabled'] : false; ?>
		<input type="checkbox" id="enable_sandbox" name="sandbox_settings[enabled]" value="1" <?php checked( $enabled, 1, true ); ?>/>
	    <label for="enable_sandbox"></label>
	    <?php
	}

	/**
	* Custom section in settings API
	*/
	function stp_api_settings_section_callback(  ) {
	    echo __( 'This settings are each site specific.', 'wordpress' );
	}

	/**
	* Render settings page using Settings API
	*/
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/thwpsb-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/thwpsb-admin.js', array( 'jquery' ), $this->version, false );

	}

	/**
	* This function will create network menu & sub menu for sandbox
	*/
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
	 * Adds top-level page to the administration menu in subsite.
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
	}

	public function thwpsb_sandbox_callback(){
		?>
		<div class="wrap">
			<h2>Sandboxes</h2>
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

	/**
	* This function will iniate scheduling &
	* create thwpsb_clean_sandbox_schedule as per interval settings
	*/
	public function schedule_sandbox_cleaning(){
		$site_id = get_current_blog_id();
		$is_sandbox = THWPSB_Utils::is_sandbox($site_id);
		if($is_sandbox){
			return;
		}
		$optimize_interval = THWPSB_Utils::get_plugin_setting('sb_optimize_interval');
		if ( false === as_next_scheduled_action( 'thwpsb_clean_sandbox_schedule' ) ) {
			as_schedule_recurring_action( strtotime( 'today' ), $optimize_interval * MINUTE_IN_SECONDS, 'thwpsb_clean_sandbox_schedule' );
		}
	}

	/**
	* This function will prepare cleaning schedules
	*/
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

	/**
	* This function will schedule sandbox delete action
	*/
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

	/**
	* Show sandbox expiry content
	*/
	public function show_sandbox_exiry_box(){
		$html = '';
		$blogID = get_current_blog_id();
		$is_sandbox = THWPSB_Utils::is_sandbox($blogID);
		if($is_sandbox) {  // CR_COMMENT: could have used conditional operator instead of nested if
			if(is_user_logged_in()){
				$html = THWPSB_Utils_Public::render_sandbox_expiry_warning($blogID);
			}
		}
		echo $html;
	}

	/**
	* This function add custom menu item in admin toolbar
	*/
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

	/**
	* This function will display contents in header.
	*/
	function new_admin_header_field(){
		$admin_head_text = get_option('admin_front_text');
		echo $admin_head_text;
	}

	/**
	* This function will display contents in footer.
	*/
	function new_admin_footer_field(){
	  $admin_footer_text = get_option('admin_footer_text');
		echo $admin_footer_text;
	}

	/**
	* CodeMirror enqueue script.
	*/
  function codemirror_enqueue_scripts($hook) {
    $cm_settings['codeEditor'] = wp_enqueue_code_editor(array('type' => 'text/css'));
    wp_localize_script('jquery', 'cm_settings', $cm_settings);

    wp_enqueue_script('wp-theme-plugin-editor');
    wp_enqueue_style('wp-codemirror');
  }

}

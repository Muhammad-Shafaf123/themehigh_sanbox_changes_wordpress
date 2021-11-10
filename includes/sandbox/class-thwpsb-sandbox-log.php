<?php
if (!defined('WPINC')) {
    die;
}

if (!class_exists('THWPSB_Sandbox_Log')):
class THWPSB_Sandbox_Log
{
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

    // class instance
    public static $instance;

    // customer WP_List_Table object
    public $sandbox_log_table_obj;

    public function __construct($plugin_name, $version)
    {
        // Custom admin menu
        add_action('network_admin_menu', array($this, 'network_menu'));

        // Ajax filtering
        add_action( 'wp_ajax_filter_sandbox_log', array( $this, 'filter_sandbox_log' ) );

        // Add style & script
        add_action( 'admin_enqueue_scripts', array($this, 'enqueue_styles') );
		// add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );

    }
    public function init()
    {

    }

    /**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'thwpsb-chartjs', THWPSB_URL . 'admin/js/Chart.bundle.min.js', array( 'jquery' ), $this->version, false );

	}

    /**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'thwpsb-chartjs', THWPSB_URL . 'admin/css/Chart.css', array(), $this->version, 'all' );

	}

    public function network_menu()
    {

        $hook = add_submenu_page(
            'th-sandbox',
            'Status',
            'Status',
            'manage_network_options',
            'th-sandbox-log',
            array($this, 'list_table_page')
        );

        //add_action("load-$hook", [ $this, 'screen_option' ]);
    }

    /**
    * Screen options
    */
    // public function screen_option()
    // {
    //     $option = 'per_page';
    //     $args   = [
    //         'label'   => 'Sandboxes',
    //         'default' => 20,
    //         'option'  => 'sandbox_per_page'
    //     ];
    //     add_screen_option($option, $args);
    //     //$sandbox_log_table_obj = new Sandbox_Log_List_Table();
    // }

    /**
     * Display the page
     *
     * @return Void
     */
    public function list_table_page()
    { ?>
        <div class="wrap">
            <div id="icon-users" class="icon32"></div>
            <h1>Status & Filters</h1>
            <form method="post" id="thwpsb-filter-form">
                <?php $this->show_filters(); ?>
                <input type="hidden" name="action" value="filter_sandbox_log" style="display: none; visibility: hidden; opacity: 0;">
                <?php wp_nonce_field( 'filter_sandbox_data' ); ?>
                <?php
                $other_attributes = array( 'id' => 'thwpsb-filter-button' );
                submit_button( __( 'Filter', 'textdomain' ), 'primary', 'thwpsb-filter-button', false, $other_attributes );
                ?>
            </form>
            <div class="chart-container" style="position: relative; height:40vh; width:80vw;">
                <canvas id="myChart"></canvas>
            </div>
        </div>
        <?php
    }

    public function admin_notices()
    {
        if (isset($_COOKIE['thlm_domain_deleted'])) { ?>
            <div class="notice notice-error is-dismissible">
        		<p><?php _e('The details was deleted.', 'text-domain'); ?></p>
        	</div>
        <?php
            unset($_COOKIE['thlm_domain_deleted']);
            setcookie('thlm_domain_deleted', '', time() - (15 * 60));
        }
    }

    public function show_filters(){
        $this->render_filter('source_id', 'All Source');
        $this->render_filter_year_month('created_at', 'All Months');
    }

    private function render_filter($column, $placeholder="All Items"){
        $sandbox_db = new THWPSB_Db_Helper('th_sandbox', 'multisite_main');
        $items = $sandbox_db->get_wheres(
            $column      = $column,
            $conditions  = array(),
            $operator    = array(),
            $format      = array(),
            $orderby     = 'id',
            $order       = 'ASC',
            $output_type = OBJECT_K
        );

        $selected = get_query_var($column);
        $output = "<select style='width:150px' name='$column' class='postform'>\n";
        $output .= '<option '.selected($selected, 0, false).' value="">'.__($placeholder, 'text-domain').'</option>';

        if (! empty($items)) {
            foreach ($items as $item):
                    if (!empty($item->$column)) {
                        $output .= "<option value='{$item->$column}' ".selected($selected, $item->$column, false).'>'.get_blog_details($item->$column)->blogname.'</option>';
                    }
            endforeach;
        }
        $output .= "</select>\n";
        echo $output;
    }

    private function render_filter_year_month($column, $placeholder="All Items"){
        $items =  $this->get_years_months($column);
        $selected = get_query_var($column);

        $output = "<select style='width:150px' name='$column' class='postform'>\n";
        $output .= '<option '.selected($selected, 0, false).' value="">'.__($placeholder, 'text-domain').'</option>';

        if (! empty($items)) {
            foreach ($items as $item):
                    if (!empty($item->year) or !empty($item->month)) {
                        $value = esc_attr($item->year.'/'.$item->month);
                        $month_dt = new DateTime($item->year.'-'.$item->month.'-01');
                        $output .= "<option value='{$value}' ".selected($selected, $value, false).'>'.$month_dt->format('F Y').'</option>';
                    }
            endforeach;
        }
        $output .= "</select>\n";
        echo $output;
    }

    public function get_years_months($column){
        $options = wp_cache_get("thwpsb44_".$column."_month");
        if (false === $options) {
            $Db_Helper = new THWPSB_Db_Helper('th_sandbox', 'multisite_main');
            $options = $Db_Helper->get_year_month_column($column);
            wp_cache_set('thwpsb44_created_month', $options);
        }
        return $options;
    }

    public function filter_sandbox_log(){
        if ( check_ajax_referer( 'filter_sandbox_data', 'nonce', false ) == false ) {
            wp_send_json_error();
        }
        $source = isset($_POST['source_id']) ? $_POST['source_id'] : '';
        $year_month = isset($_POST['created_at']) ? $_POST['created_at'] : '';

        if($year_month){
            $month = new DateTime($year_month.'/01');
            $month_start = $month->format('Y-m-d');
            $month_end = $month->format('Y-m-t');
        }

        global $wpdb;
        $prefix  = $wpdb->base_prefix;
        $tablename = $prefix . 'th_sandbox';
        $sql = "SELECT COUNT(id) AS y,source_id,created_at as x FROM $tablename WHERE 1=1";

        if($source){
            $sql .= " AND `source_id` = '$source'";
        }

        if($year_month){
            $month = new DateTime($year_month.'/01');
            $month_start = $month->format('Y-m-d');
            $month_end = $month->format('Y-m-t');
            $sql .= " AND `created_at` >= '$month_start' AND  `created_at` <= '$month_end'";
        }

        $sql .= " GROUP BY YEAR(created_at), MONTH(created_at), source_id";
        $query_result = $wpdb->get_results( $sql, ARRAY_A );
        $new_data = array();
        if(!empty($query_result)){
            $result = THWPSB_Utils::array_multi_group_by_key($query_result, 'source_id');
            $i = 0;
            foreach($result as $key => $data){
                $colors = THWPSB_Utils::colors_array();
                $key_1 = array_rand($colors);
                $key_2 = array_rand($colors);
                $main_color = $colors[$key_1];
                $sub_color = $colors[$key_2];
                $source_elm = array();
                $source_elm['label'] = get_blog_details($key)->blogname;
                $source_elm['backgroundColor'] = "transparent";
                $source_elm['borderColor'] = "$main_color";
                $source_elm['pointBackgroundColor'] = "$sub_color";
                $source_elm['pointBorderColor'] = "$sub_color";
                $source_elm['pointHoverBackgroundColor'] = "$sub_color";
                $source_elm['pointHoverBorderColor'] = "$sub_color";
                $source_elm['data'] = $data;
                $new_data[$i] = $source_elm;
                $i++;
            }
        }

        $myJSON = json_encode($new_data);
        wp_send_json_success($myJSON);

        //wp_send_json_success( __( 'Thanks for reporting!', 'reportabug' ) );
    }

}



endif;

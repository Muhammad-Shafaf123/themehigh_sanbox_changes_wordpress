<?php
if (!defined('WPINC')) {
    die;
}

// WP_List_Table is not loaded automatically so we need to load it in our application
if (! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

if (!class_exists('THWPSB_Sandbox_Manage')):

class THWPSB_Sandbox_Manage
{

    // class instance
    public static $instance;

    // customer WP_List_Table object
    public $invalid_use_table_obj;

    public function __construct()
    {
    }
    public function init()
    {

        add_action('network_admin_menu', array($this, 'network_menu'));
        add_filter('set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3);
        //add_action('admin_menu', array($this, 'sub_menu_invalid_domains' ));
        //add_action('admin_notices', array($this, 'admin_notices'));
        // Render Dropdown filter
        add_action('restrict_manage_posts', array($this,'render_expiry_month_dropdown'));
    }
    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    // add_submenu_page(
    //     'th-sandbox',
    //     'Sandbox Settings',
    //     'Settings',
    //     'manage_network_options',
    //     'th-sandbox-settings',
    //     array($this, 'network_menu_callback')
    // );

    public function network_menu()
    {
        // $hook = add_submenu_page(
        //     'edit.php?post_type=license',
        //     __('License Manager', 'themehigh-license-manager'),
        //     __('Invalid Use', 'themehigh-license-manager'),
        //     'manage_options',
        //     'thlm-invalid-use',
        //     array($this, 'list_table_page')
        // );

        $hook = add_submenu_page(
            'th-sandbox',
            'Sandbox Logs',
            'Sandbox Logs',
            'manage_network_options',
            'th-sandbox-log',
            array($this, 'list_table_page')
        );

        add_action("load-$hook", [ $this, 'screen_option' ]);
    }

    /**
    * Screen options
    */
    public function screen_option()
    {
        $option = 'per_page';
        $args   = [
            'label'   => 'Sandboxes',
            'default' => 20,
            'option'  => 'sandbox_per_page'
        ];
        add_screen_option($option, $args);
        $invalid_use_table_obj = new Invalid_Use_List_Table();
    }

    /**
     * Display the page
     *
     * @return Void
     */
    public function list_table_page()
    {
        $invalidUseListTable = new Invalid_Use_List_Table();
        $invalidUseListTable->prepare_items(); ?>
            <div class="wrap">
                <div id="icon-users" class="icon32"></div>
                <h1>Sandboxes</h1>
                <form method="post">
                    <?php $invalidUseListTable->display(); ?>
                </form>
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
}



/**
 * Create new table class that will extend the WP_List_Table
 */
class Invalid_Use_List_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $db_helper = new THWPSB_Db_Helper('th_sandbox', 'multisite_main');

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page('sandbox_per_page', 20);
        $current_page = $this->get_pagenum();

        $data = $db_helper->get_all('id', null, $per_page, $current_page);

        $total_items  = $db_helper->count();
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ));

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
    * Render the bulk edit checkbox
    *
    * @param array $item
    *
    * @return string
    */
    public function column_cb($item)
    {
        return sprintf(
          '<input type="checkbox" name="bulk-delete[]" value="%s" />',
          $item->id
      );
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'cb'      => '<input type="checkbox" />',
            'sandbox_id'       => 'Sandbox ID',
            'source_id' => 'Source',
            'created_at'        => 'Created at',
            'expired_at'    => 'Expired',
            'deleted_at'      => 'Deleted',
            //'refunded'      => 'Is refunded?'
        );

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array(
            'sandbox_id' => array('sandbox_id', false),
            'source_id' => array('source_id', false),
            'created_at' => array('created_at', false),
            'expired_at' => array('expired_at', false),
            'deleted_at' => array('deleted_at', false),
        );
    }

    /**
     * Retrieve data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_domains($per_page = 5, $page_number = 1)
    {
        $invalid_use = new THLM_License_Invalid_Use;
        $r = $invalid_use->fetch();
        return $r ;
    }

    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'id':
            case 'sandbox_id':
                return $item->sandbox_id;
            case 'source_id':
                return $item->source_id;
            case 'created_at':
                return $item->created_at;
            case 'expired_at':
                return $item->expired_at;
            case 'deleted_at':
                return $item->deleted_at;
            default:
                return print_r($item, true) ;
        }
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    public function column_domain($item)
    {

        // create a nonce
        $delete_nonce = wp_create_nonce('thlm_delete_invalid_domain');
        $current_url_params = $_GET;
        $delete_params = array(
            'action' => 'delete',
            'id' => absint($item->id),
            '_wpnonce' => $delete_nonce,
        );
        $url_params = array_merge($current_url_params, $delete_params);
        $url = add_query_arg($url_params, admin_url() . 'edit.php');

        $title = '<strong>' . $item->domain . '</strong>';

        $actions = [
        'delete' => sprintf('<a href="%s">Delete</a>', $url)
        ];

        return $title . $this->row_actions($actions);
    }

    /** Text displayed when no customer data is available */
    public function no_items()
    {
        _e('No details avaliable.', 'sp');
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
        'bulk-delete' => 'Delete'
      ];

        return $actions;
    }

    public function process_bulk_action()
    {

      //Detect when a bulk action is being triggered...
        if ('delete' === $this->current_action()) {

        // In our file that handles the request, verify the nonce.
            $nonce = esc_attr($_REQUEST['_wpnonce']);

            if (! wp_verify_nonce($nonce, 'thlm_delete_invalid_domain')) {
                die('Go get a life script kiddies');
            } else {
                //self::delete_customer( absint( $_GET['customer'] ) );
                $domain_id = isset($_GET['id']) ? absint($_GET['id']) : false;
                if ($domain_id) {
                    $invalid_use = new THLM_License_Invalid_Use;
                    $invalid_use->delete($domain_id);
                }

                setcookie("thlm_domain_deleted", true);
                wp_redirect(remove_query_arg(array('action', 'id', '_wpnonce')));
                exit;
            }
        }

        // If the delete bulk action is triggered
        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
           || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')
      ) {
            $delete_ids = esc_sql($_POST['bulk-delete']);

            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id) {
                $invalid_use = new THLM_License_Invalid_Use;
                //$invalid_use->delete( $id );
            }
            setcookie("thlm_domain_deleted", true);
            //$url = add_query_arg(array('thlm_domain_deleted' => true));
        //wp_redirect( remove_query_arg( array('action', 'id', '_wpnonce')) );
        //exit;
        }
    }
}



endif;

<?php
/**
 * Admin Setting Menu
 */

if ( !defined( 'ABSPATH' ) ) exit;
class Smart_Search_Control_Admin_Menu {

    /**
     * instance
     * @var 
     */
    private static $instance = null;

    /**
     * screen_id
     */
    private $screen_id = '';

    /**
     * Define instance
     * @return Smart_Search_Control_Admin_Menu
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Smart_Search_Control_Admin_Menu ) ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    /**
     * hooks
     */
    private function hooks() {

        add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
        add_action( 'current_screen', [ $this, 'setup_screen_id' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'smart_search_control_admin_assets' ] );
        add_action( 'wp_ajax_smart_search_control_setting', [ $this, 'smart_search_control_setting_add' ] );
        add_action( 'wp_ajax_smart_search_control_setting_delete', [ $this, 'smart_search_control_setting_delete' ] );
        add_action( 'wp_ajax_smart_search_control_setting_edit', [ $this, 'smart_search_control_setting_edit' ] );
        add_action( 'wp_ajax_create_database_table', [ $this, 'create_database_table' ] ); 
    }

    /**
     * Setup screen ID when the current screen is available.
     */
    public function setup_screen_id() {

        $screen = get_current_screen();
        if ( $screen ) {
            $this->screen_id = $screen->id;
        }
    }

    /**
     * Check if the current screen is the admin settings page.
     */
    public function is_admin_settings_page() {

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            
            if ( isset( $_POST['action'] ) && strpos( sanitize_text_field( wp_unslash( $_POST['action'] ) ), 'smart_search_control_setting' ) !== false ) {

                $nonce_action_map = [
                    'smart_search_control_setting'        => 'smart_search_control_setting_nonce_add',
                    'smart_search_control_setting_edit'   => 'smart_search_control_setting_nonce_edit',
                    'smart_search_control_setting_delete' => 'smart_search_control_setting_nonce_delete',
                ];
                $action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
                $nonce_action = isset( $nonce_action_map[ $action ] ) ? $nonce_action_map[ $action ] : '';
                if ( $nonce_action && isset( $_POST['nonce'] ) ) {
                    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), $nonce_action ) ) {
                        return false;
                    }
                }
                return true;
            }
            return false;
        }
        $screen = get_current_screen();
        return $screen && $screen->id === $this->screen_id;
    }

    /**
     * Show Admin Notice if Table Does Not Exist
     */
    public function get_admin_notice() {

        
        $table_exists = LD_Smart_Search_Control::smart_search_control_create_table() ;

        if ( !$table_exists ) {

            ob_start(); 
            ?>
            <div class="admin-msg">
                <p><?php echo esc_html__( 'The table for Smart Search Control does not exist. Click here to ' , 'smart-search-control' ); ?>
                    <strong class="create-table">
                        <a href="#">
                            <?php echo esc_html__( 'Create Table ' , 'smart-search-control' ) ; ?>
                        </a>
                    </strong>
                    <span id="database-loader" class="loading" style="display: none;"></span>
                </p>
            </div>
            <?php
            return ob_get_clean();
        }
        return '';
    }
    
    /**
     * add_admin_page
     */
    public function add_admin_page() {

        add_menu_page(
            __( 'Smart Search Control' , 'smart-search-control' ),
            __( 'Smart Search Control' , 'smart-search-control' ),
            'manage_options',
            'smart_search_control',
            [ $this, 'render_admin_page' ],
            'dashicons-search',
            80
        );
    }

    /**
     * render_admin_page
     */
    public function render_admin_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_attr( __( 'You do not have permission to access this page.' , 'smart-search-control' ) ) );
        }

        global $wpdb;
        $table_name     = $wpdb->prefix . 'smart_search_control_parameters';
        $admin_notice   = Smart_Search_Control_Admin_Menu::instance()->get_admin_notice();
        $items_per_page = 10;

        if ( isset( $_GET['paged'] ) && isset( $_GET['nonce'] ) ) {
            $ssc_pagination_nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );
            if ( wp_verify_nonce( $ssc_pagination_nonce, 'ssc_admin_pagination' ) ) {
                $page = absint( $_GET['paged'] );
            } else {
                $page = 1;
            }
        } else {
            $page = 1;
        }
        $offset         = ( $page - 1 ) * $items_per_page;
        $search_entries = [];
        $total_pages    = 1;

        if ( empty( $admin_notice ) ) {
            $total_items    = $this->ssc_get_total_items( $table_name );
            $total_pages    = ceil( $total_items / $items_per_page );
            $search_entries = $this->ssc_get_search_entries( $table_name, $items_per_page, $offset );
            $args = [
                'public'             => true,
                'publicly_queryable' => true,
            ];
            $visible_post_types = get_post_types( $args, 'objects' );
            if ( ! array_key_exists( 'page', $visible_post_types ) ) {
                $visible_post_types['page'] = get_post_type_object( 'page' );
            }
            $post_types = apply_filters( 'visible_post_types', $visible_post_types );
        }
        $template_path = SSC_TEMPLATES_DIR . 'admin/template-smart-search-control-admin-page.php';
        include $template_path;
    }

    /**
     * wp_search_admin_assets
     */
    public function smart_search_control_admin_assets() {

        if ( !$this->is_admin_settings_page() ) {
            return;
        }

        wp_enqueue_style(
            'smart-search-control-admin-style',
            SSC_ASSETS_URL . 'css/smart-search-control-admin-style.css',
            array(), SSC_VERSION, 'all'
        );
        wp_enqueue_script( 'smart-search-control-admin-js', SSC_ASSETS_URL . 'js/smart-search-control-admin.js', [ 'jquery' ], SSC_VERSION, true );
        wp_localize_script( 'smart-search-control-admin-js', 'SSC_SETTING', [

            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'nonce_add'    => wp_create_nonce( 'smart_search_control_setting_nonce_add' ),
            'nonce_edit'   => wp_create_nonce( 'smart_search_control_setting_nonce_edit' ),
            'nonce_delete' => wp_create_nonce( 'smart_search_control_setting_nonce_delete' ),
            'nonce_table'  => wp_create_nonce( 'create_database_table_nonce' ),
            'error_msg'    => __( 'Something went wrong. Please try again.' , 'smart-search-control' ),
            'confirm_msg'  => __( 'Are you sure you want to delete this search setting?' , 'smart-search-control' ),
        ]);   
    }

    /**
     * Add New Search Setting
     */
    public function smart_search_control_setting_add() {

        global $wpdb;

        if( !$this->is_admin_settings_page() ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized request' , 'smart-search-control' ) ] );
        }
        
        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash(  $_POST[ 'nonce' ] ) ), 'smart_search_control_setting_nonce_add' ) )  {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' , 'smart-search-control' ) ] );
        }

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';
        $place_holder = !empty( $_POST[ 'place_holder' ] ) ? sanitize_text_field(  wp_unslash( $_POST[ 'place_holder' ] ) ) : '';
        $css_id       = !empty( $_POST[ 'css_id' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'css_id' ] ) ) : '';
        $class        = !empty( $_POST[ 'class' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'class' ] ) ) : '';
        $post_types   = isset( $_POST[ 'post_type' ] ) && !empty(  $_POST[ 'post_type' ] ) 
            ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ 'post_type' ] ) ) 
            : get_post_types( [ 'public' => true ], 'names' );
        $data = json_encode([
            'place_holder' => $place_holder,
            'css_id'       => $css_id,
            'class'        => $class,
            'post_type'    => $post_types
        ]);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->insert( $table_name,[ 'data' => $data ], [ '%s' ] );
        if ( !empty( $result ) ) {
            wp_cache_delete( 'ssc_table_exists_' . md5( $table_name ), 'smart_search_control' );
            wp_cache_delete( 'ssc_total_items_count', 'smart_search_control' );
            wp_cache_delete( "ssc_entries_page_1", 'smart_search_control' );
            wp_cache_delete( 'ssc_all_settings', 'smart_search_control' );
        }
        if ( empty( $result ) ) {

            $notice = [
                'message' => __( 'Failed to save search settings. Please try again.' , 'smart-search-control' ),
                'type'    => 'error'
            ];
            wp_send_json_success( $notice );
        }
        $notice = [
            'message' => __( 'Search settings saved successfully!' , 'smart-search-control' ),
            'type'    => 'success'
        ];
        wp_send_json_success( $notice );
    }

    /**
     * Edit Search Setting
     */
    public function smart_search_control_setting_edit() {

        global $wpdb;
        if( !$this->is_admin_settings_page() ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized request' , 'smart-search-control' ) ] );
        }
        
        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ), 'smart_search_control_setting_nonce_edit' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' , 'smart-search-control' ) ] );
        }

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';
        
        $id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

        if ( $id === 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid ID' , 'smart-search-control' ) ] );
        }

        $place_holder = !empty( $_POST[ 'place_holder' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'place_holder' ] ) ) : __( 'Search...' , 'smart-search-control' );
        $css_id       = !empty( $_POST[ 'css_id' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'css_id' ] ) ) : '';
        $class        = !empty( $_POST[ 'class' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'class' ] ) ) : '';
        $post_types   = isset( $_POST[ 'post_type' ] ) && !empty( $_POST[ 'post_type' ] ) 
            ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ 'post_type' ] ) ) 
            : get_post_types( [ 'public' => true ], 'names' );

        $data = json_encode([
            'place_holder' => $place_holder,
            'css_id'       => $css_id,
            'class'        => $class,
            'post_type'    => $post_types
        ]);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result =  $wpdb->update(
            $table_name,
            [ 'data' => $data ],  
            [ 'id' => $id ],      
            [ '%s' ],              
            [ '%d' ]
            
        );

        if ( !empty( $result) ) {
            wp_cache_delete( 'ssc_table_exists_' . md5( $table_name ), 'smart_search_control' );
            wp_cache_delete( 'ssc_total_items_count', 'smart_search_control' );
            wp_cache_delete( "ssc_entries_page_1", 'smart_search_control' );
            wp_cache_delete( 'ssc_all_settings', 'smart_search_control' );
        }

        if ( $result === false ) {
            wp_send_json_success( [
                'message' => __( 'Failed to update data. Please try again.', 'smart-search-control' ),
                'type'    => 'error'
            ] );
            return;
        }

        wp_send_json_success( [
            'message' => __( 'Search settings updated successfully!', 'smart-search-control' ),
            'type'    => 'success'
        ] );
    }

    /**
     * Delete Search Setting
     */
    public function smart_search_control_setting_delete() {
        
        global $wpdb;

        if( !$this->is_admin_settings_page() ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized request' , 'smart-search-control' ) ] );
        }
        
        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ), 'smart_search_control_setting_nonce_delete' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' , 'smart-search-control' ) ] );
        }

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';
        
        $id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

        if ( $id === 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid ID' , 'smart-search-control' ) ] );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result =  $wpdb->delete( $table_name, [ 'id' => $id ], [ '%d' ] ) ;
        if ( !empty( $result) ) {
            wp_cache_delete( 'ssc_table_exists_' . md5( $table_name ), 'smart_search_control' );
            wp_cache_delete( 'ssc_total_items_count', 'smart_search_control' );
            wp_cache_delete( "ssc_entries_page_1", 'smart_search_control' );
            wp_cache_delete( 'ssc_all_settings', 'smart_search_control' );
        }

        if ( ! $result ) {
            wp_send_json_error( [
                'message' => __( 'Failed to delete search setting. Please try again.', 'smart-search-control' ),
                'type'    => 'error'
            ] );
            return;
        }
    
        wp_send_json_success( [
            'message' => __( 'Search setting deleted successfully!', 'smart-search-control' ),
            'type'    => 'success'
        ] );
    }

    /**
    * Get paginated search entries from the table with caching.
    */
    public function ssc_get_search_entries( $table_name, $items_per_page, $offset ) {

        global $wpdb;
        // Allow only this specific table for safety
        $allowed_table = $wpdb->prefix . 'smart_search_control_parameters';
        if ( $table_name !== $allowed_table ) {
            return [];
        }

        $cache_key = 'ssc_data_' . md5( $table_name . '_' . $items_per_page . '_' . $offset );
        $entries = wp_cache_get( $cache_key );

        if ( false === $entries ) {

            $table_name = $wpdb->prefix . 'smart_search_control_parameters';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $entries = $wpdb->get_results( $wpdb->prepare ( "SELECT id, data FROM `{$wpdb->prefix}smart_search_control_parameters` LIMIT %d OFFSET %d",  [ $items_per_page, $offset ] ) );
            wp_cache_set( $cache_key, $entries, '', 300 );
        }
        return $entries;
    }

    /**
     * Get total items from the table with caching.
     */
    public function ssc_get_total_items( $table_name ) {

        global $wpdb;
        $allowed_table = $wpdb->prefix . 'smart_search_control_parameters';
        if ( $table_name !== $allowed_table ) {
            return 0;
        }

        $cache_key = 'ssc_total_count_' . md5( $table_name );
        $total_items = wp_cache_get( $cache_key );

        if ( false === $total_items ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $total_items = (int) ($wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}smart_search_control_parameters") ?? 0);
            wp_cache_set( $cache_key, $total_items, '', 300 );
        }
        return $total_items;
    }

    /**
     * create_database_table on click
     */
    public function create_database_table() {
        
        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ 'nonce' ] ) ), 'create_database_table_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' , 'smart-search-control' ) ] );
        }
        LD_Smart_Search_Control::smart_search_control_create_table();
        wp_send_json_success( [
            'message' => __( 'Table for Smart Search Control created successfully!', 'smart-search-control' ),
            'type'    => 'success'
        ] );
    }
}
Smart_Search_Control_Admin_Menu::instance();
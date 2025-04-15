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
            return isset( $_POST[ 'action' ] ) && strpos( $_POST[ 'action' ], 'smart_search_control_setting' ) !== false;
        }
    
        $screen = get_current_screen();
        return $screen && $screen->id === $this->screen_id;
    }

    /**
     * Show Admin Notice if Table Does Not Exist
     */
    public function get_admin_notice() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_search_control_parameters';

        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

        if ( !$table_exists ) {

            ob_start(); 
            ?>
            <div class="admin-msg">
                <p><?php echo esc_html__( 'The table for Smart Search Control does not exist. Click here to ' , 'smart-search-control' ); ?>
                    <strong class="create-table">
                        <a href="#">
                            <?php echo esc_html__( 'Create Table' , 'smart-search-control' ); ?>
                        </a>
                    </strong>
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
            wp_die( __( 'You do not have permission to access this page.' , 'smart-search-control' ) );
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
            SSC_ASSETS_URL . 'css/smart-search-control-admin-style.css'
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

        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'smart_search_control_setting_nonce_add' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' , 'smart-search-control' ) ] );
        }

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';
    
        $place_holder = !empty( $_POST[ 'place_holder' ] ) ? sanitize_text_field( $_POST[ 'place_holder' ] ) : '';
        $css_id       = !empty( $_POST[ 'css_id' ] ) ? sanitize_text_field( $_POST[ 'css_id' ] ) : '';
        $class        = !empty( $_POST[ 'class' ] ) ? sanitize_text_field( $_POST[ 'class' ] ) : '';
        $post_types   = isset( $_POST[ 'post_type' ] ) && !empty( $_POST[ 'post_type' ] ) 
            ? array_map( 'sanitize_text_field', $_POST[ 'post_type' ] ) 
            : get_post_types( [ 'public' => true ], 'names' );
    
        $data = json_encode([
            'place_holder' => $place_holder,
            'css_id'       => $css_id,
            'class'        => $class,
            'post_type'    => $post_types
        ]);
    
        $result = $wpdb->insert(
            $table_name,
            [ 'data' => $data ],
            [ '%s' ]
        );

        if ( $result ) {

            $notice = [
                'message' => __( 'Search settings saved successfully!' , 'smart-search-control' ),
                'type'    => 'success'
            ];

            wp_send_json_success( $notice );

        } else {

            $notice = [
                'message' => __( 'Failed to save search settings. Please try again.' , 'smart-search-control' ),
                'type'    => 'error'
            ];

            wp_send_json_success( $notice );
        }
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

        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'smart_search_control_setting_nonce_edit' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' , 'smart-search-control' ) ] );
        }

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';
        
        $id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

        if ( $id === 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid ID' , 'smart-search-control' ) ] );
        }

        $place_holder = !empty( $_POST[ 'place_holder' ] ) ? sanitize_text_field( $_POST[ 'place_holder' ] ) : '';
        $css_id       = !empty( $_POST[ 'css_id' ] ) ? sanitize_text_field( $_POST[ 'css_id' ] ) : '';
        $class        = !empty( $_POST[ 'class' ] ) ? sanitize_text_field( $_POST[ 'class' ] ) : '';
        $post_types   = isset( $_POST[ 'post_type' ] ) && !empty( $_POST[ 'post_type' ] ) 
            ? array_map( 'sanitize_text_field', $_POST[ 'post_type' ] ) 
            : get_post_types( [ 'public' => true ], 'names' );

        $data = json_encode([
            'place_holder' => $place_holder,
            'css_id'       => $css_id,
            'class'        => $class,
            'post_type'    => $post_types
        ]);

        $result =  $wpdb->update(

            $table_name,
            [ 'data' => $data ],  
            [ 'id' => $id ],      
            [ '%s' ],              
            [ '%d' ]
            
        );

        if ( $result !== false ) {

            $notice = [
                'message' => __( 'Search settings updated successfully!' , 'smart-search-control' ),
                'type' => 'success'
            ];

            wp_send_json_success( $notice );

        } else {

            $notice = [
                'message' => __( 'Failed to update data. Please try again.' , 'smart-search-control' ),
                'type' => 'error'
            ];
            
            wp_send_json_success( $notice );
        }
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
        
        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'smart_search_control_setting_nonce_delete' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' , 'smart-search-control' ) ] );
        }

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';
        
        $id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

        if ( $id === 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid ID' , 'smart-search-control' ) ] );
        }

        $result = $wpdb->delete( $table_name, [ 'id' => $id ], [ '%d' ] );

        if ( $result ) {

            $notice = [
                'message' => __( 'Search setting deleted successfully!' , 'smart-search-control' ),
                'type' => 'success'
            ];

            wp_send_json_success( $notice );

        } else {

            $notice = [
                'message' => __( 'Failed to delete search setting. Please try again.' , 'smart-search-control' ),
                'type' => 'error'
            ];

            wp_send_json_error( $notice );
        }
    }

    /**
     * create_database_table on click
     */
    public function create_database_table() {

        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'create_database_table_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' , 'smart-search-control' ) ] );
        }
    
        $database_file = SSC_INCLUDES_DIR . 'smart-search-control-database.php';
    
        if ( file_exists( $database_file ) ) {

            require_once $database_file;

            $notice = [
                'message' => __( 'Table for Smart Search Control created successfully!' , 'smart-search-control' ),
                'type' => 'success'
            ];

            wp_send_json_success( $notice );
        } else { 

            $notice = [
                'message' => __( 'Database file not found!' , 'smart-search-control' ),
                'type' => 'error'
            ];

            wp_send_json_error( $notice );
        }
    }

}

Smart_Search_Control_Admin_Menu::instance();
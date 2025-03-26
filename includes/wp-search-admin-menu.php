<?php
/**
 * Admin Setting Menu
 */

if ( !defined( 'ABSPATH' ) ) exit;

class WP_Search_Admin_Settings {

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
     * @return WP_Search_Admin_Settings
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof WP_Search_Admin_Settings ) ) {
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
        add_action( 'admin_enqueue_scripts', [ $this, 'wp_search_admin_assets' ] );
        add_action( 'wp_ajax_wp_search_setting', [ $this, 'wp_search_setting_add' ] );
        add_action( 'wp_ajax_wp_search_setting_delete', [ $this, 'wp_search_setting_delete' ] );
        add_action( 'wp_ajax_wp_search_setting_edit', [ $this, 'wp_search_setting_edit' ] );
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
            return isset( $_POST['action'] ) && strpos( $_POST['action'], 'wp_search_setting' ) !== false;
        }
    
        $screen = get_current_screen();
        return $screen && $screen->id === $this->screen_id;
    }

    /**
     * Show Admin Notice if Table Does Not Exist
     */
    public function get_admin_notice() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'search_parameters';

        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;

        if ( !$table_exists ) {
            ob_start(); 
            ?>
            <div class="admin-msg">
                <p><?php echo esc_html__( 'The table for WP Search does not exist. Click here to ' ); ?>
                    <strong class="create-table">
                        <a href="#">
                            <?php echo esc_html__( 'Create Table' ); ?>
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
            __( 'WP Search' ),
            __( 'WP Search' ),
            'manage_options',
            'wp_search_settings',
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
            wp_die( __( 'You do not have permission to access this page.' ) );
        }

        $template_path = WP_SEARCH_TEMPLATES_DIR . 'template-wp-search-admin-page.php';
        include $template_path;

    }

    /**
     * wp_search_admin_assets
     */
    public function wp_search_admin_assets() {

        if ( !$this->is_admin_settings_page() ) {
            return;
        }

        wp_enqueue_style(
            'wp-search-admin-style',
            WP_SEARCH_ASSETS_URL . 'css/wp-search-admin-style.css'
        );
        wp_enqueue_script( 'wp-search-admin-js', WP_SEARCH_ASSETS_URL . 'js/wp-search-admin.js', [ 'jquery' ], WP_SEARCH_VERSION, true );
        wp_localize_script( 'wp-search-admin-js', 'WP_SEARCH_SETTING', [

            'ajaxurl'      => admin_url( 'admin-ajax.php' ),
            'nonce_add'    => wp_create_nonce( 'wp_search_setting_nonce_add' ),
            'nonce_edit'   => wp_create_nonce( 'wp_search_setting_nonce_edit' ),
            'nonce_delete' => wp_create_nonce( 'wp_search_setting_nonce_delete' ),
            'nonce_table'  => wp_create_nonce( 'create_database_table_nonce' ),
            'error_msg'    => __( 'Something went wrong. Please try again.' ),
            'confirm_msg'  => __( 'Are you sure you want to delete this search setting?' ),
            'add_title'    => __( 'Add New Setting' ),
            'setting_id'   => __( 'Search Setting - ID : ' ),
            'add_btn'      => __( 'Add Setting' ),
            'edit_btn'     => __( 'Update Setting' ),
            
        ]);   
    }

    /**
     * Add New Wp Search Setting
     */
    public function wp_search_setting_add() {

        global $wpdb;
        if( !$this->is_admin_settings_page() ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized request' ) ] );
        }

        $table_name = $wpdb->prefix . 'search_parameters';
    
        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'wp_search_setting_nonce_add' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' ) ] );
        }
    
        $place_holder = !empty( $_POST[ 'place_holder' ] ) ? sanitize_text_field( $_POST[ 'place_holder' ] ) : 'Search...';
        $class        = !empty( $_POST[ 'class' ] ) ? sanitize_text_field( $_POST[ 'class' ] ) : 'default-search';
        $type         = !empty( $_POST[ 'type' ] ) ? sanitize_text_field( $_POST[ 'type' ] ) : 'All';
        $post_types   = isset( $_POST[ 'post_type' ] ) && !empty( $_POST[ 'post_type' ] ) 
            ? array_map( 'sanitize_text_field', $_POST[ 'post_type' ] ) 
            : get_post_types( [ 'public' => true ], 'names' );
    
        $post_types_string = implode( ',', $post_types );
    
        $result = $wpdb->insert(
            $table_name,
            [
                'place_holder' => $place_holder,
                'class' => $class,
                'type' => $type,
                'post_type' => $post_types_string
            ],
            [ '%s', '%s', '%s', '%s' ]
        );
    
        if ( $result ) {
            wp_send_json_success( [ 'message' => __( 'Search settings saved successfully!' ) ] );
        } else {
            wp_send_json_error( [
                'message' => __( 'Failed to save search settings' ),
                'error' => $wpdb->last_error
            ] );
        }
    }

    /**
     * Edit WP Search Setting
     */
    public function wp_search_setting_edit() {

        global $wpdb;
        if( !$this->is_admin_settings_page() ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized request' ) ] );
        }

        $table_name = $wpdb->prefix . 'search_parameters';

        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'wp_search_setting_nonce_edit' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' ) ] );
        }

        $id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

        if ( $id === 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid ID' ) ] );
        }

        $place_holder = !empty( $_POST[ 'place_holder' ] ) ? sanitize_text_field( $_POST[ 'place_holder' ] ) : 'Search...';
        $class        = !empty( $_POST[ 'class' ] ) ? sanitize_text_field( $_POST[ 'class' ] ) : 'default-search';
        $type         = !empty( $_POST[ 'type' ] ) ? sanitize_text_field( $_POST[ 'type' ] ) : 'All';
        $post_types   = isset( $_POST[ 'post_type' ] ) && !empty( $_POST[ 'post_type' ] ) 
            ? array_map( 'sanitize_text_field', $_POST[ 'post_type' ] ) 
            : get_post_types( [ 'public' => true ], 'names' );

        $post_types_string = implode( ',', $post_types );

        $result = $wpdb->update(
            $table_name,
            [
                'place_holder' => $place_holder,
                'class' => $class,
                'type' => $type,
                'post_type' => $post_types_string
            ],
            [ 'id' => $id ],
            [ '%s', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        if ( $result !== false ) {
            wp_send_json_success( [ 'message' => __( 'Search settings updated successfully!' ) ] );
        } else {
            wp_send_json_error( [
                'message' =>  __( 'Failed to update search settings' ),
                'error' => $wpdb->last_error
            ] );
        }
    }

    /**
     * Delete WP Search Setting
     */
    public function wp_search_setting_delete() {
        
        global $wpdb;
        if( !$this->is_admin_settings_page() ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => __( 'Unauthorized request' ) ] );
        }

        $table_name = $wpdb->prefix . 'search_parameters';

        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'wp_search_setting_nonce_delete' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' ) ] );
        }

        $id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

        if ( $id === 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid ID' ) ] );
        }

        $result = $wpdb->delete( $table_name, [ 'id' => $id ], [ '%d' ] );

        if ( $result ) {
            wp_send_json_success( [ 'message' => __( 'Search setting deleted successfully!' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Failed to delete search setting' ) ] );
        }
    }

    /**
     * create_database_table on click
     */
    public function create_database_table() {

        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'create_database_table_nonce' ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid nonce' ) ] );
        }
    
        $database_file = WP_SEARCH_INCLUDES_DIR . 'wp-search-database.php';
    
        if ( file_exists( $database_file ) ) {

            require_once $database_file;

            wp_send_json_success( [ 'message' => __( 'Table for WP Search created successfully!' ) ] );
        } else { 

            wp_send_json_error( [ 'message' => __( 'Database file not found!' ) ] );
        }
    }
    
}

WP_Search_Admin_Settings::instance();
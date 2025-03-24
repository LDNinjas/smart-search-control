<?php
/**
 * Admin Setting Page
 */

if ( !defined( 'ABSPATH' ) ) exit;

class WP_Search_Admin_Settings {

    /**
     * instance
     * @var 
     */
    private static $instance = null;

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
        add_action( 'admin_enqueue_scripts', [ $this, 'wp_search_admin_assets' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
        add_action( 'wp_ajax_wp_search_setting', [ $this, 'wp_search_setting_add' ] );
        add_action( 'wp_ajax_wp_search_setting_delete', [ $this, 'wp_search_setting_delete' ] );
        add_action( 'wp_ajax_wp_search_setting_edit', [ $this, 'wp_search_setting_edit' ] );
    }

    /**
     * wp_search_admin_assets
     */
    public function wp_search_admin_assets() {

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
        ]);   
    }

    /**
     * add_admin_page
     */
    public function add_admin_page() {

        add_menu_page(
            'WP Search',
            'WP Search',
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
            wp_die( __( 'You do not have permission to access this page.', 'wp-search' ) );
        }

        $template_path = WP_SEARCH_TEMPLATES_DIR . 'template-wp-search-admin-page.php';
        include $template_path;
    }

    /**
     * Add New Wp Search Setting
     */
    public function wp_search_setting_add() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized request' ] );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'search_parameters';
    
        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'wp_search_setting_nonce_add' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
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
            wp_send_json_success( [ 'message' => 'Search settings saved successfully!' ] );
        } else {
            wp_send_json_error( [
                'message' => 'Failed to save search settings',
                'error' => $wpdb->last_error
            ] );
        }
    }

    /**
     * Edit WP Search Setting
     */
    public function wp_search_setting_edit() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized request' ] );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'search_parameters';

        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'wp_search_setting_nonce_edit' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
        }

        $id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

        if ( $id === 0 ) {
            wp_send_json_error( [ 'message' => 'Invalid ID' ] );
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
            wp_send_json_success( [ 'message' => 'Search settings updated successfully!' ] );
        } else {
            wp_send_json_error( [
                'message' => 'Failed to update search settings',
                'error' => $wpdb->last_error
            ] );
        }
    }

    /**
     * Delete WP Search Setting
     */
    public function wp_search_setting_delete() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized request' ] );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'search_parameters';

        if ( !isset( $_POST[ 'nonce' ] ) || !wp_verify_nonce( $_POST[ 'nonce' ], 'wp_search_setting_nonce_delete' ) ) {
            wp_send_json_error( [ 'message' => 'Invalid nonce' ] );
        }

        $id = isset( $_POST[ 'id' ] ) ? intval( $_POST[ 'id' ] ) : 0;

        if ( $id === 0 ) {
            wp_send_json_error( [ 'message' => 'Invalid ID' ] );
        }

        $result = $wpdb->delete( $table_name, [ 'id' => $id ], [ '%d' ] );

        if ( $result ) {
            wp_send_json_success( [ 'message' => 'Search setting deleted successfully!' ] );
        } else {
            wp_send_json_error( [ 'message' => 'Failed to delete search setting' ] );
        }
    }
}

WP_Search_Admin_Settings::instance();
<?php
/**
 * Plugin Name: WP Search
 * Plugin URI: http://www.wpsearch.com
 * Description: A simple search plugin for WordPress
 * Version: 1.0.0
 * Author: LDNinjas
 * Author URI: http://www.ldninjas.com
 * Text Domain: wp-search
 * License: GPL2
 */

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin class -  
 */
class LD_WP_Search {

    /**
     * Object Variable
     */
    private static $instance = null;
    
    /**
     * Constructor 
     */
    public static function instance() {

        if ( is_null( self::$instance ) && !( self::$instance instanceof LD_WP_Search ) ) {

            self::$instance = new self;
            self::$instance->constants_setup(); 
            self::$instance->includes_files();
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * Define plugin constants 
     */
    private function constants_setup() {

        define( 'WP_SEARCH_DIR', plugin_dir_path( __FILE__ ) );
        define( 'WP_SEARCH_URL', plugin_dir_url( __FILE__ ) );
        define( 'WP_SEARCH_BASE_DIR',  plugin_basename( __FILE__ ) );        
        define( 'WP_SEARCH_INCLUDES_DIR', WP_SEARCH_DIR . 'includes/' );
        define( 'WP_SEARCH_TEMPLATES_DIR', WP_SEARCH_DIR . 'templates/' );
        define( 'WP_SEARCH_ASSETS_URL', WP_SEARCH_URL . 'assets/' );
        define( 'WP_SEARCH_VERSION', '1.0.0' );
    }

    /**
     * Include necessary files
     */
    private function includes_files() {

        require_once WP_SEARCH_INCLUDES_DIR . 'wp-search-admin-menu.php';
        // require_once WP_SEARCH_INCLUDES_DIR . 'wp-search-admin-form-handler.php';

        if( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ){
            require_once WP_SEARCH_INCLUDES_DIR . 'wp-search-shortcode.php';
        }

    }

    /**
     * Plugin Hooks
     */
    public function hooks() {

        add_filter( 'plugin_action_links_' . WP_SEARCH_BASE_DIR, [ $this, 'wp_search_setting_links' ] );
        register_activation_hook( __FILE__, [ $this,  'wp_search_plugin_activate' ] );

    }

    /**
     * Add Settings Link to Plugins Page
     */
    public function wp_search_setting_links( $links ) {

        $settings_link = '<a href="' . admin_url( 'admin.php?page=wp_search_settings' ) . '">' . __( 'Settings', 'wp-search' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    /**
     * wp_search_plugin_activate cretae database table
     * 
     */
    function wp_search_plugin_activate() {

        require_once WP_SEARCH_INCLUDES_DIR . 'wp-search-database.php';
    
        if ( class_exists( 'WP_Search_Database' ) ) {
            WP_Search_Database::instance(); 
        }
    }
    
}

LD_WP_Search::instance();
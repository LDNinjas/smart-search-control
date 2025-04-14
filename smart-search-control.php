<?php
/**
 * Plugin Name: Smart Search Control
 * Plugin URI: http://www.smart-search-control.com
 * Description: A simple search plugin for WordPress
 * Version: 1.0.0
 * Author: LDNinjas
 * Author URI: http://www.ldninjas.com
 * Text Domain: smart-search-control
 * License: GPL2
 */

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin class -  
 */
class LD_Smart_Search_Control {

    /**
     * Object Variable
     */
    private static $instance = null;
    
    /**
     * Constructor 
     */
    public static function instance() {

        if ( is_null( self::$instance ) && !( self::$instance instanceof LD_Smart_Search_Control ) ) {

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

        define( 'SMART_SEARCH_CONTROL_DIR', plugin_dir_path( __FILE__ ) );
        define( 'SMART_SEARCH_CONTROL_URL', plugin_dir_url( __FILE__ ) );
        define( 'SMART_SEARCH_CONTROL_BASE_DIR',  plugin_basename( __FILE__ ) );        
        define( 'SMART_SEARCH_CONTROL_INCLUDES_DIR', SMART_SEARCH_CONTROL_DIR . 'includes/' );
        define( 'SMART_SEARCH_CONTROL_TEMPLATES_DIR', SMART_SEARCH_CONTROL_DIR . 'templates/' );
        define( 'SMART_SEARCH_CONTROL_ASSETS_URL', SMART_SEARCH_CONTROL_URL . 'assets/' );
        define( 'SMART_SEARCH_CONTROL_VERSION', '1.0.0' );
    }

    /**
     * Include necessary files
     */
    private function includes_files() {

        if ( is_admin() ) {
            require_once SMART_SEARCH_CONTROL_INCLUDES_DIR . 'admin/smart-search-control-admin-menu.php';
            require_once SMART_SEARCH_CONTROL_INCLUDES_DIR . 'admin/smart-search-control-admin-submenu-setting.php';
        }

        if( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ){
            require_once SMART_SEARCH_CONTROL_INCLUDES_DIR . 'smart-search-control-shortcode.php';
            require_once SMART_SEARCH_CONTROL_INCLUDES_DIR . 'smart-search-control-result-shortcode.php';
        }

    }

    /**
     * Plugin Hooks
     */
    public function hooks() {

        add_filter( 'plugin_action_links_' . SMART_SEARCH_CONTROL_BASE_DIR, [ $this, 'smart_search_control_setting_links' ] );
        register_activation_hook( __FILE__, [ $this,  'smart_search_control_plugin_activate' ] );

    }

    /**
     * Add Settings Link to Plugins Page
     */
    public function smart_search_control_setting_links( $links ) {

        $main_link = '<a href="' . admin_url( 'admin.php?page=smart_search_control' ) . '">' . __( 'Settings', 'smart-search-control' ) . '</a>';
        $setting_link = '<a href="' . admin_url( 'admin.php?page=smart_search_control_settings' ) . '">' . __( 'Settings', 'smart-search-control' ) . '</a>';
    
        array_unshift( $links, $main_link );

        return $links;
    }   

    /**
     * plugin_activate cretae database table
     * 
     */
    function smart_search_control_plugin_activate() {

        require_once SMART_SEARCH_CONTROL_INCLUDES_DIR . 'smart-search-control-database.php';
    
    }
    
}

LD_Smart_Search_Control::instance();
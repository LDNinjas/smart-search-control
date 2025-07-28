<?php
/**
 * Plugin Name: Smart Search Control
 * Plugin URI: https://ldninjas.com/smart-search-control/
 * Description: A powerful WordPress search plugin that enables advanced filtering by post types and custom criteria. Easily create multiple search fields with distinct rules for different sections, offering flexible and precise search functionality.
 * Version: 1.0.0
 * Author: LDNinjas
 * Author URI: https://ldninjas.com
 * Text Domain: smart-search-control
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

        define( 'SMARSECO_DIR', plugin_dir_path( __FILE__ ) );
        define( 'SMARSECO_URL', plugin_dir_url( __FILE__ ) );
        define( 'SMARSECO_BASE_DIR',  plugin_basename( __FILE__ ) );        
        define( 'SMARSECO_INCLUDES_DIR', SMARSECO_DIR . 'includes/' );
        define( 'SMARSECO_TEMPLATES_DIR', SMARSECO_DIR . 'templates/' );
        define( 'SMARSECO_ASSETS_URL', SMARSECO_URL . 'assets/' );
        define( 'SMARSECO_ASSETS_PATH', SMARSECO_DIR . 'assets/' );
        define( 'SMARSECO_VERSION', '1.0.0' );
    }

    /**
     * Include necessary files
     */
    private function includes_files() {

        if ( is_admin() ) {
            require_once SMARSECO_INCLUDES_DIR . 'admin/smart-search-control-admin-menu.php';
            require_once SMARSECO_INCLUDES_DIR . 'admin/smart-search-control-admin-submenu-setting.php';
        }

        if( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ){
            require_once SMARSECO_INCLUDES_DIR . 'smart-search-control-shortcode.php';
            require_once SMARSECO_INCLUDES_DIR . 'smart-search-control-result-shortcode.php';
        }

    }

    /**
     * Plugin Hooks
     */
    public function hooks() {

        add_filter( 'plugin_action_links_' . SMARSECO_BASE_DIR, [ $this, 'smart_search_control_setting_links' ] );
        register_activation_hook( __FILE__, [ $this,  'smart_search_control_plugin_activate' ] );
    }

    /**
     * Add Settings Link to Plugins Page
     */
    public function smart_search_control_setting_links( $links ) {

        $main_link = '<a href="' . admin_url( 'admin.php?page=smart_search_control' ) . '">' . __( 'Settings', 'smart-search-control' ) . '</a>';
        array_unshift( $links, $main_link );

        return $links;
    }   

    /**
     * Runs on plugin activation
     * 
     */
    public function smart_search_control_plugin_activate() {

        require_once SMARSECO_INCLUDES_DIR . 'smart-search-control-database.php';
    }

    /**
     * Main function to cretae database table
     * 
     */
    public static function smart_search_control_create_table() {

        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';        
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id MEDIUMINT( 9 ) NOT NULL AUTO_INCREMENT,
            data JSON NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY ( id )
        ) $charset_collate;"; 

        $exists = maybe_create_table( $table_name, $sql );

        return $exists;
    }
}

LD_Smart_Search_Control::instance();

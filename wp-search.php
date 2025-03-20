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
            
        }

        return self::$instance;
    }

    /**
     * Define plugin constants 
     */
    private function constants_setup() {

        define( 'WP_SEARCH_DIR', plugin_dir_path( __FILE__ ) );
        define( 'WP_SEARCH_URL', plugin_dir_url( __FILE__ ) );
        define( 'WP_SEARCH_INCLUDES_DIR', WP_SEARCH_DIR . 'includes/' );
        define( 'WP_SEARCH_TEMPLATES_DIR', WP_SEARCH_DIR . 'templates/' );
        define( 'WP_SEARCH_ASSETS_URL', WP_SEARCH_URL . 'assets/' );
        define( 'WP_SEARCH_VERSION', '1.0.0' );
    }

    /**
     * Include necessary files
     */
    private function includes_files() {

            if( !is_admin() || ( defined( 'DOING_AJAX') && DOING_AJAX ) ){
                require_once WP_SEARCH_INCLUDES_DIR . 'wp-search-shortcode.php';
            }

    }
}

LD_WP_Search::instance();
<?php

/**
 * Plugin Name: WP Search
 * Plugin URI: http://www.wpsearch.com
 * Description: A simple search plugin for WordPress
 * Version: 1.0
 * Author: LDNinjas
 * Author URI: http://www.ldninjas.com
 * License: GPL2
 */

if ( !defined( 'ABSPATH' ) ) exit;

class ld_wp_search {

    private static $instance = null;

    public static function instance() {

        if ( is_null( self::$instance ) && !( self::$instance instanceof ld_wp_search ) ) {

            self::$instance = new self;
            self::$instance->constants_setup(); 
            self::$instance->includes_files();
            self::$instance->setup_excerpt_length();
            
        }

        return self::$instance;

    }

    /**
     * Define plugin constants 
     */

    private function constants_setup() {

        define('WP_SEARCH_DIR', plugin_dir_path(__FILE__));
        define('WP_SEARCH_URL', plugin_dir_url(__FILE__));
        define('WP_SEARCH_INCLUDES_PATH', WP_SEARCH_DIR . 'includes/');
        define('WP_SEARCH_ASSETS_URL', WP_SEARCH_URL . 'assets/');

    }

    /**
     * Include necessary files
     */

    private function includes_files() {

        require_once WP_SEARCH_INCLUDES_PATH . 'wp-search-script.php';
        require_once WP_SEARCH_INCLUDES_PATH . 'wp-search-shortcode.php';

    }

    /**
     * Setup and set excerpt length
     */

    private function setup_excerpt_length() {

        add_filter('excerpt_length', function($length) {

            return 20;

        });
    }
}

ld_wp_search::instance();
<?php

/**
 *Plugin Name: WP Search
 *Plugin URI: http://www.wpsearch.com
 *Description: A simple search plugin for WordPress
 *Version: 1.0
 *Author: LDNinjas
 *Author URI: http://www.ldninjas.com
 *License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
if (!defined('WP_SEARCH_DIR')) {
    define('WP_SEARCH_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WP_SEARCH_URL')) {
    define('WP_SEARCH_URL', plugin_dir_url(__FILE__));
}
if (!defined('WP_SEARCH_INCLUDES_PATH')) {
    define('WP_SEARCH_INCLUDES_PATH', WP_SEARCH_DIR . 'includes/');
}
if (!defined('WP_SEARCH_ASSETS_URL')) {
    define('WP_SEARCH_ASSETS_URL', WP_SEARCH_URL . 'assets/');
}

if (!class_exists('WP_Search_Excerept_Length')) {
    class WP_Search_Excerept_Length
    {
        public function __construct()
        {
            add_filter('excerpt_length', array($this, 'set_excerpt_length'));
        }
        public function set_excerpt_length($length)
        {
            return 20;
        }
    }
    new WP_Search_Excerept_Length();
}


// Include the script file
require_once WP_SEARCH_INCLUDES_PATH . 'wp-search-script.php';
require_once WP_SEARCH_INCLUDES_PATH . 'wp-search-shortcode.php';

?>
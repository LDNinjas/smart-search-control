<?php

/**
 * Script file
 */



if (!class_exists('WP_Search_Assets')) {
    class WP_Search_Assets
    {

        // Initialize the hooks
        public function __construct()
        {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        }

        // Enqueue styles and scripts
        public function enqueue_assets()
        {
            // Enqueue the custom CSS file
            wp_enqueue_style(
                'wp-search-style',
                WP_SEARCH_ASSETS_URL . '/css/style.css'
            );

            // Enqueue the custom JavaScript file
            wp_enqueue_script(
                'wp-search-script',
                WP_SEARCH_ASSETS_URL . '/js/script.js',
                array('jquery'), // jQuery as a dependency
                '1.0', // Version
                true // Load in the footer
            );


            // Enqueue the ajax JavaScript file
            wp_enqueue_script(
                'wp-search-ajax',
                WP_SEARCH_ASSETS_URL . '/js/wp-search-ajax.js',
                array('jquery'), // jQuery as a dependency
                '1.0', // Version
                true // Load in the footer
            );

            wp_localize_script('wp-search-ajax', 'myAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'site_url' => get_site_url() 
            ));

        }
    }
}

// Instantiate the class
new WP_Search_Assets();

?>
<?php

/**
 * Scripts include
 */

class Wp_search_script {

    /**
     * Summary of instance
     * @var 
     */

    private static $instance = null;

    /**
     * Summary of instance
     * @return Wp_search_script
     */
    public static function instance() {
        if ( is_null( self::$instance ) && !( self::$instance instanceof Wp_search_script ) ) {
            self::$instance = new self;
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * hooks 
     */

    public function hooks() {
        add_action( 'wp_enqueue_scripts', [$this,'WP_Search_Assets'] );
    }

    /**
     * Link to Scripts
     */

    public function WP_Search_Assets() {
        
        /**
         * Enqueue the custom CSS file
         */
        
        wp_enqueue_style(
            'wp-search-style',
            WP_SEARCH_ASSETS_URL . '/css/style.css'
        );
        
        /**
         * Enqueue the custom JavaScript file
         */
    
        wp_enqueue_script(
            'wp-search-script',
            WP_SEARCH_ASSETS_URL . '/js/script.js',
            array( 'jquery' ),
            '1.0', 
            true
        );

        /**
         * Enqueue the ajax JavaScript file
         */
        
        wp_enqueue_script(
            'wp-search-ajax',
            WP_SEARCH_ASSETS_URL . '/js/wp-search-ajax.js',
            array( 'jquery' ),
            '1.0',
            true 
        );

        /**
         * Localize the ajax script to provide Ajax URL and site URL to the JavaScript
         */
        
        wp_localize_script( 'wp-search-ajax', 'myAjax', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'site_url' => get_site_url()
        ));
    }
}

Wp_search_script::instance();
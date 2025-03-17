<?php

/**
 * Wp Search Shortcode
 */
class WP_Search {
        
    /**
     * Summary of instance
     * @var 
     */
    private static $instance = null;

    /**
     * Attributes for the search shortcode.
     */
    private $atts = [];

    /**
     * Constructor
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof WP_Search ) ) {
            self::$instance = new self;
            self::$instance->hooks();
        }
        return self::$instance;
    }

    /**
     * All the required hooks are called here.
     */
    private function hooks() {

        add_action( 'wp_enqueue_scripts', [ $this, 'wp_search_assets' ] );
        add_action( 'wp_ajax_wp_search_result', [ $this , 'wp_search_result' ] );
        add_action( 'wp_ajax_nopriv_wp_search_result', [ $this , 'wp_search_result' ] );
        add_action( 'wp_ajax_wp_search_suggestion', [ $this, 'wp_search_suggestion' ] );
        add_action( 'wp_ajax_nopriv_wp_search_suggestion', [ $this, 'wp_search_suggestion' ] );
        add_shortcode( 'wp_search_bar', [ $this, 'render_search_shortcode' ] );

    }

    /**
     * Load the Assets
     */
    public function wp_search_assets() {

        wp_register_style(
            'wp-search-style',
            WP_SEARCH_ASSETS_URL . '/css/wp-search-style.css'
        );
        wp_register_script( 'wp-search-js', WP_SEARCH_ASSETS_URL . 'js/wp-search-ajax.js', ['jquery'], WP_SEARCH_VERSION, true );
        wp_localize_script( 'wp-search-js', 'WP_SEARCH', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'wp_search_result_nonce' ),
        ]);   
        
    }

    /**
     * Render the search shortcode
     */
    public function render_search_shortcode( $atts ) {

        $sanitized_atts = shortcode_atts(
            [
                'placeholder'       => 'Enter your search terms...',
                'class'             => 'default-search',
                'include_post_type' => '',
                'exclude_post_type' => '',
            ],
            $atts,
            'wp_search_bar'
        );
        
        foreach ( $sanitized_atts as $key => $value ) {
            $this->atts[ $key ] = sanitize_text_field( $value );
        }

        $all_post_types = get_post_types( [ 'public' => true ], 'names' );
        $include = !empty( $this->atts[ 'include_post_type' ] ) ? explode( ',', $this->atts[ 'include_post_type' ] ) : [];
        $exclude = !empty( $this->atts[ 'exclude_post_type' ] ) ? explode( ',', $this->atts[ 'exclude_post_type' ] ) : [];
        
        $class = esc_attr( $this->atts[ 'class' ] );
        $placeholder = esc_attr( $this->atts[ 'placeholder' ] );
        
        if ( !empty( $include ) ) {

            $post_types = array_intersect( $all_post_types, $include );

        } elseif ( !empty( $exclude ) ) {

            $post_types = array_diff( $all_post_types, $exclude );

        } else {

            $post_types = $all_post_types;

        }

        wp_enqueue_style( 'wp-search-style' );
        wp_enqueue_script( 'wp-search-js' );
        
        ob_start();
        $template_path = WP_SEARCH_TEMPLATES_DIR . 'template-wp-search-bar.php';
        include $template_path;
        $content = ob_get_clean();
        return $content;
        
    }

    /**
     * Summary of wp_search_suggestion
     * @return void
     */
    function wp_search_suggestion() {

        if ( !isset( $_POST['nonce'] ) || !check_ajax_referer( 'wp_search_result_nonce', 'nonce', false ) ) {

            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );

        }

        if ( empty( $_POST['search_query'] ) ) {

            wp_send_json_error( [ 'message' => 'Search query is empty.' ] );

        }

        $search_query = sanitize_text_field( $_POST[ 'search_query' ] );
        $post_types = !empty( $_POST[ 'post_types' ] ) ? 
            array_map( 'trim', explode( ',', sanitize_text_field( $_POST[ 'post_types' ] ) ) ) : 
            [];

        $args = [
            's' => $search_query,
            'post_type' => $post_types,
            'posts_per_page' => -1,
        ];

        $query = new WP_Query( $args );

        if ( !$query->have_posts() ) {

            if ( in_array( 'product', $post_types ) && !in_array( 'product_variation', $post_types ) ) {

                $modified_post_types = array_merge( $post_types, [ 'product_variation' ] );
                
                $args[ 'post_type' ] = $modified_post_types;
                $query = new WP_Query( $args );
            }
        }

        // Process results
        $posts = [];
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $posts[] = [
                    'id'        => $post_id,
                    'title'     => get_the_title(),
                    'permalink' => get_permalink(),
                ];


            }
            wp_reset_postdata();

        }

        if ( !empty( $posts ) ) {
            wp_send_json_success( [ 'search_results' => $posts ] );
        } else {
            wp_send_json_error(  [ 'message' => __( 'No results found.' )  ]  );
        }

        wp_die();
    }


    /**
     * Summary of wp_search_result
     * @return void
     */
    function wp_search_result() {

        if ( !isset( $_POST['nonce'] ) || !check_ajax_referer( 'wp_search_result_nonce', 'nonce', false ) ) {

            wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );

        }

        if ( empty( $_POST['search_query'] ) ) {

            wp_send_json_error( [ 'message' => 'Search query is empty.' ] );

        }

        $search_query = sanitize_text_field( $_POST[ 'search_query' ] );
        $post_types = !empty( $_POST[ 'post_types' ] ) ? 
            array_map( 'trim', explode( ',', sanitize_text_field( $_POST[ 'post_types' ] ) ) ) : 
            [];

        $args = [
            's' => $search_query,
            'post_type' => $post_types,
            'posts_per_page' => -1,
        ];

        $query = new WP_Query( $args );

        if ( !$query->have_posts() ) {

            if ( in_array( 'product', $post_types ) && !in_array( 'product_variation', $post_types ) ) {

                $modified_post_types = array_merge( $post_types, [ 'product_variation' ] );
                
                $args[ 'post_type' ] = $modified_post_types;
                $query = new WP_Query( $args );
            }
        }

        // Process results
        $posts = [];
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $posts[] = [
                    'id'        => $post_id,
                    'title'     => get_the_title(),
                    'content'   => get_the_excerpt(),
                    'permalink' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($post_id) ?: WP_SEARCH_ASSETS_URL . 'image/dummyImg.png',
                ];


            }

            wp_reset_postdata();
            ob_start();

            if(is_search(  )){
                $template_path = WP_SEARCH_TEMPLATES_DIR . 'template-row-wp-search.php';
                include $template_path;
                $html = ob_get_clean();
                wp_send_json_success( [ 'search' => $html ] );
            }
        } else {
            wp_send_json_error( [ 'message' => 'No results found.' ] );
        }
        wp_die();
    }
}
WP_Search::instance();

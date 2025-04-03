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
        add_action( 'wp_ajax_wp_search_suggestion', [ $this, 'wp_search_suggestion' ] );
        add_action( 'wp_ajax_nopriv_wp_search_suggestion', [ $this, 'wp_search_suggestion' ] );
        add_shortcode( 'wp_search_bar', [ $this, 'render_search_shortcode' ] );
        add_action( 'pre_get_posts', [ $this, 'wp_custom_search_query' ] );

    }

    /**
     * Load the Assets
     */
    public function wp_search_assets() {

        wp_register_style(
            'wp-search-style',
            WP_SEARCH_ASSETS_URL . '/css/wp-search-style.css'
        );
        wp_register_script( 'wp-search-js', WP_SEARCH_ASSETS_URL . 'js/wp-search-ajax.js', [ 'jquery' ], WP_SEARCH_VERSION, true );
        wp_localize_script( 'wp-search-js', 'WP_SEARCH', [
            'ajaxurl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'wp_search_result_nonce' ),
            'search_msg' => __( 'Searching...' ),
        ]);   
        
    }

    /**
     * Render the search shortcode
     */
    public function render_search_shortcode( $atts ) {

        global $wpdb;

        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'wp-search-style' );
        wp_enqueue_script( 'wp-search-js' );

        $sanitized_atts = shortcode_atts(
            [
                'id' => '1',
            ],
            $atts,
            'wp_search_bar'
        );
        
        $table_name = $wpdb->prefix . 'search_parameters';

        $query = "SELECT id, data FROM $table_name WHERE id = %d";
        
        $result = $wpdb->get_row( $wpdb->prepare( $query, $sanitized_atts[ 'id' ] ) );

        $data = json_decode( $result->data ); 

        $class = isset( $data->class ) ? $data->class : '';
        $css_id = isset( $data->css_id ) ? $data->css_id : '';
        $placeholder = isset( $data->place_holder ) ? $data->place_holder : '';
        $posts_types = isset( $data->post_type ) ? ( is_array( $data->post_type ) ? $data->post_type : explode( ',', $data->post_type ) ) : [];
        $posts_types = array_map( 'trim', $posts_types );
        
        ob_start();
        $template_path = WP_SEARCH_TEMPLATES_DIR . 'template-wp-search-bar.php';
        include $template_path;
        $content = ob_get_clean();
        return $content;
        
    }

    /**
     * Summary of wp_search_suggestion
     */
    function wp_search_suggestion() {

        if ( !isset( $_POST['nonce'] ) || !check_ajax_referer( 'wp_search_result_nonce', 'nonce', false ) ) {

            wp_send_json_error( [ 'message' => __( 'Nonce verification failed' ) ] );

        }

        if ( empty( $_POST['search_query'] ) ) {

            wp_send_json_error( [ 'message' => __( 'Search query is empty.' ) ] );

        }

        $search_query = sanitize_text_field( $_POST[ 'search_query' ] );
        $post_types = !empty( $_POST[ 'post_types' ] ) ? 
            array_map( 'trim', explode( ',', sanitize_text_field( $_POST[ 'post_types' ] ) ) ) : 
            [ 'post' ];

        $args = [
            's' => $search_query,
            'post_type' => $post_types,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        $query = new WP_Query( $args );

        if ( !$query->have_posts() ) {

            if ( in_array( 'product', $post_types ) && !in_array( 'product_variation', $post_types ) ) {

                $modified_post_types = array_merge( $post_types, [ 'product_variation' ] );
                
                $args[ 'post_type' ] = $modified_post_types;
                $query = new WP_Query( $args );
            }
        }

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
     * Override the default WordPress search page to custom search
     */
    function wp_custom_search_query( $query ) {

        if ( !is_admin() && $query->is_main_query() && $query->is_search() ) {

            if ( !isset( $_POST['post_type'] ) || empty( $_POST['post_type'] ) ) {

                $query->set( 'post_type', array( 'post' ) ); 
                return;
            }

            $post_types = array_map( 'trim', explode( ',', sanitize_text_field( $_POST[ 'post_type' ] ) ) );

            if ( in_array( 'product', $post_types ) && !in_array( 'product_variation', $post_types ) ) {
                $post_types[] = 'product_variation';
            }

            $query->set( 'post_type', $post_types );
        }
    }

}

WP_Search::instance();
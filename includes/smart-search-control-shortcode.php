<?php

/**
 * Smart_Search_Control Shortcode
 */
class Smart_Search_Control {
        
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

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Smart_Search_Control ) ) {

            self::$instance = new self;
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * All the required hooks are called here.
     */
    private function hooks() {

        add_action( 'wp_enqueue_scripts', [ $this, 'smart_search_control_assets' ] );
        add_action( 'wp_ajax_smart_search_control_suggestion', [ $this, 'smart_search_control_suggestion' ] );
        add_action( 'wp_ajax_nopriv_smart_search_control_suggestion', [ $this, 'smart_search_control_suggestion' ] );
        add_shortcode( 'smart_search_control', [ $this, 'render_search_shortcode' ] );
    
    }

    /**
     * Load the Assets
     */
    public function smart_search_control_assets() {

        wp_register_style(
            'smart-search-control-style',
            SMART_SEARCH_CONTROL_ASSETS_URL . '/css/smart-search-control-style.css'
        );
        wp_register_script( 'smart-search-control-js', SMART_SEARCH_CONTROL_ASSETS_URL . 'js/smart-search-control-ajax.js', [ 'jquery' ], SMART_SEARCH_CONTROL_VERSION, true );
        wp_localize_script( 'smart-search-control-js', 'SMART_SEARCH_CONTROL', [
            'ajaxurl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'smart_search_control_result_nonce' ),
            'search_msg' => __( 'Searching...' , 'smart-search-control' ),
            'more_msg'   => __( 'See more...', 'smart-search-control' ),
        ]);   
        
    }

    /**
     * Render the search shortcode
     */
    public function render_search_shortcode( $atts ) {

        global $wpdb;
    
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'smart-search-control-style' );
        wp_enqueue_script( 'smart-search-control-js' );
    
        $sanitized_atts = shortcode_atts(
            [
                'id'           => '',
                'css_id'       => '',
                'css_class'    => '',
                'place_holder' => '',
                'post_type'    => [],
            ],
            $atts,
            'smart_search_control'
        );
    
        $ssc_id = intval( $sanitized_atts['id'] );
    
        $all_public_post_types = get_post_types( [ 'public' => true ], 'names' );

        $posts_types = $all_public_post_types;
    
        $table_name = $wpdb->prefix . 'smart_search_control_parameters';
        $query      = "SELECT data FROM $table_name WHERE id = %d";
        $result     = $wpdb->get_row( $wpdb->prepare( $query, $ssc_id ) );
    
        $class       = $sanitized_atts[ 'css_class' ];
        $css_id      = $sanitized_atts[ 'css_id' ];
        $placeholder = $sanitized_atts[ 'place_holder' ];
    
        if ( ! empty( $result ) && isset( $result->data ) ) {

            $data = json_decode( $result->data );
    
            $class       = isset( $data->class ) ? $data->class : $class;
            $css_id      = isset( $data->css_id ) ? $data->css_id : $css_id;
            $placeholder = isset( $data->place_holder ) ? $data->place_holder : $placeholder;
    
            if ( isset( $data->post_type ) && !empty( $data->post_type ) ) {
                if ( is_array( $data->post_type ) ) {
                    $posts_types = $data->post_type;
                } elseif ( is_string( $data->post_type ) ) {
                    $posts_types = array_map( 'trim', explode( ',', $data->post_type ) );
                } else {
                    $posts_types = $all_public_post_types;
                }
            } else {
                $posts_types = $all_public_post_types;
            }
        } else {
            $posts_types = $all_public_post_types;
        }
    
        ob_start();
        $template_path = SMART_SEARCH_CONTROL_TEMPLATES_DIR . 'template-smart-search-control.php';
    
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    
        return ob_get_clean();
    }
    
    /**
     * Summary of search_suggestion
     */
    public function smart_search_control_suggestion() {

        global $wpdb;

        if ( !isset( $_POST['nonce'] ) || !check_ajax_referer( 'smart_search_control_result_nonce', 'nonce', false ) ) {

            wp_send_json_error( [ 'message' => __( 'Nonce verification failed' , 'smart-search-control' ) ] );

        }

        if ( empty( $_POST['search_query'] ) ) {

            wp_send_json_error( [ 'message' => __( 'Search query is empty.' , 'smart-search-control' ) ] );

        }

        $search_query = sanitize_text_field( $_POST[ 'search_query' ] );

        $ssc_id = sanitize_text_field( $_POST[ 'ssc_id' ] );

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';
        $query = "SELECT  data FROM $table_name WHERE id = %d";
        $result = $wpdb->get_row( $wpdb->prepare( $query, $ssc_id ) );

        $all_public_post_types = get_post_types( [ 'public' => true ], 'names' );

        if ( ! empty( $result ) && isset( $result->data ) ) {

            $data = json_decode( $result->data );
    
            if ( isset( $data->post_type ) && !empty( $data->post_type ) ) {
                
                if ( is_array( $data->post_type ) ) {

                    $posts_types = $data->post_type;
                } elseif ( is_string( $data->post_type ) ) {

                    $posts_types = array_map( 'trim', explode( ',', $data->post_type ) );
                } else {
                    $posts_types = $all_public_post_types;
                }
            } else {
                $posts_types = $all_public_post_types;
            }
        } else {
            $posts_types = $all_public_post_types;
        }

        $args = [
            's' => $search_query,
            'post_type' => $posts_types,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];

        $query = new WP_Query( $args );

        if ( !$query->have_posts() ) {

            if ( in_array( 'product', $posts_types ) && !in_array( 'product_variation', $posts_types ) ) {

                $modified_post_types = array_merge( $posts_types, [ 'product_variation' ] );
                
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
            wp_send_json_error(  [ 'message' => __( 'No results found.' , 'smart-search-control' )  ]  );
        }

        wp_die();
    }
    
}

Smart_Search_Control::instance();
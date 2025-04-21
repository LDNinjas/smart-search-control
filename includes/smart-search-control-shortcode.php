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
        add_action( 'wp_ajax_nopriv_smart_search_control_suggestion', [ $this, 'smart_search_control_suggestion' ] );
        add_shortcode( 'smart_search_control', [ $this, 'render_search_shortcode' ] );
    
    }

    /**
     * Load the Assets
     */
    public function smart_search_control_assets() {

        wp_register_style(
            'smart-search-control-style',
            SSC_ASSETS_URL . '/css/smart-search-control-style.css'
        );
        wp_register_script( 'smart-search-control-js', SSC_ASSETS_URL . 'js/smart-search-control-ajax.js', [ 'jquery' ], SSC_VERSION, true );
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

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';

        if ( ! $this->table_exists( $table_name ) ) {

            ob_start();
            echo '<h3>' . __( 'Table for Smart Search Control does not exist', 'smart-search-control' ) . '</h3>';
            return ob_get_clean();
            
        }
    
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'smart-search-control-style' );
        wp_enqueue_script( 'smart-search-control-js' );
    
        $sanitized_atts = shortcode_atts(
            [
                'id'           => '',
                'css_id'       => '',
                'css_class'    => '',
                'place_holder' => __( 'Search...' , 'smart-search-control' ),
                'post_type'    => [],
            ],
            $atts,
            'smart_search_control'
        );
    
        $ssc_id =  $sanitized_atts['id'] ;

        if ( filter_var( $ssc_id, FILTER_VALIDATE_INT ) === false || intval( $ssc_id ) < 0 ) {

            return '<p>' . __( 'Invalid ID provided.', 'smart-search-control' ) . '</p>';
            
        }
    
        $all_post_types = get_post_types( [], 'objects' );
        $all_public_post_types = [];
        
        foreach ( $all_post_types as $post_type => $obj ) {
            if ( $obj->public === true || $obj->show_in_menu === true ) {
                $all_public_post_types[] = $post_type;
            }
        }

        $posts_types = $all_public_post_types;
        
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
        }else {
            if ( $ssc_id === 0 ) {
                $posts_types = $all_public_post_types;
            } else {
                return '<p>' . __( 'Invalid ID provided.', 'smart-search-control' ) . '</p>';
            }
        }

        if ( in_array( 'product', $posts_types, true ) && ! in_array( 'product_variation', $posts_types, true ) ) {
            $posts_types[] = 'product_variation';
        }
    
        ob_start();
        $template_path = SSC_TEMPLATES_DIR . 'template-smart-search-control.php';
    
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

        if ( !isset( $_POST[ 'nonce' ] ) || !check_ajax_referer( 'smart_search_control_result_nonce', 'nonce', false ) ) {

            wp_send_json_error( [ 'message' => __( 'Nonce verification failed' , 'smart-search-control' ) ] );

        }

        if ( empty( $_POST[ 'search_query' ] ) ) {

            wp_send_json_error( [ 'message' => __( 'Search query is empty.' , 'smart-search-control' ) ] );

        }

        $search_query = sanitize_text_field( $_POST[ 'search_query' ] );

        $ssc_id = sanitize_text_field( $_POST[ 'ssc_id' ] );

        $table_name = $wpdb->prefix . 'smart_search_control_parameters';

        if ( ! $this->table_exists( $table_name ) ) {

            ob_start();
            echo '<h3>' . __( 'Table for Smart Search Control does not exist', 'smart-search-control' ) . '</h3>';
            return ob_get_clean();
            
        }
        
        $query = "SELECT  data FROM $table_name WHERE id = %d";
        $result = $wpdb->get_row( $wpdb->prepare( $query, $ssc_id ) );

        $all_post_types = get_post_types( [], 'objects' );
        $all_public_post_types = [];
        
        foreach ( $all_post_types as $post_type => $obj ) {
            if ( $obj->public === true || $obj->show_in_menu === true ) {
                $all_public_post_types[] = $post_type;
            }
        }

        $posts_types = $all_public_post_types;

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

        if ( in_array( 'product', $posts_types, true ) && ! in_array( 'product_variation', $posts_types, true ) ) {
            $posts_types[] = 'product_variation';
        }

        $args = [
            's' => $search_query,
            'post_type' => $posts_types,
            'posts_per_page' => 10,
            'post_status'    => 'publish',
        ];

        $query = new WP_Query( $args );

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

        if ( empty( $posts ) ) {

            wp_send_json_error(  [ 'message' => __( 'No results found.' , 'smart-search-control' )  ]  );
        }
        
        foreach ( $posts as &$post ) {

            $decoded_title = html_entity_decode( $post[ 'title' ], ENT_QUOTES | ENT_HTML5 );
            $parts = preg_split( '/\s*[-–—]\s*/u', $decoded_title );
            $post[ 'title' ] = implode( ' ', $parts );
        }
        unset( $post );
        
        wp_send_json_success( [ 'search_results' => $posts ] );
        

        wp_die();
    }

    /**
     * Checks if a table exists in the database.
     */
    public function table_exists( $table_name ) {

        global $wpdb;
        
        $query = $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        );
        return $wpdb->get_var( $query ) === $table_name;
    }
    
}

Smart_Search_Control::instance();
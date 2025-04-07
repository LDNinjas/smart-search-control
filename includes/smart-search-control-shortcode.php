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
        add_action( 'pre_get_posts', [ $this, 'smart_search_control_custom_search_query' ] );
        add_filter( 'the_content', [ $this, 'smart_search_control_custom_search_content' ]);
        add_filter( 'register_block_type_args',  [ $this, 'modify_result_search_block' ], 10, 2 );

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
    public function render_search_shortcode( $atts  ) {

        global $wpdb;

        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'smart-search-control-style' );
        wp_enqueue_script( 'smart-search-control-js' );

        $sanitized_atts = shortcode_atts(
            [
                'id' => '',
            ],
            $atts,
            'smart_search_control'
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
        $template_path = SMART_SEARCH_CONTROL_TEMPLATES_DIR . 'template-smart-search-control.php';
        include $template_path;
        $content = ob_get_contents();
        ob_get_clean();
        return $content;
        
    }

    /**
     * Modify the core search block to use our custom search form
     */
    public function modify_result_search_block( $args, $name ) {

        if ( $name === 'core/search' ) {
            $args['render_callback'] = [ $this , 'short_code_search'];
        }
        return $args;
    }
    
    public function short_code_search() {
        
        
        return $this->render_search_shortcode( 'id' );
    }
    

    /**
     * Summary of search_suggestion
     */
    function smart_search_control_suggestion() {

        if ( !isset( $_POST['nonce'] ) || !check_ajax_referer( 'smart_search_control_result_nonce', 'nonce', false ) ) {

            wp_send_json_error( [ 'message' => __( 'Nonce verification failed' , 'smart-search-control' ) ] );

        }

        if ( empty( $_POST['search_query'] ) ) {

            wp_send_json_error( [ 'message' => __( 'Search query is empty.' , 'smart-search-control' ) ] );

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
            wp_send_json_error(  [ 'message' => __( 'No results found.' , 'smart-search-control' )  ]  );
        }

        wp_die();
    }

    /**
     * Override the default WordPress search page to custom search
     */
    function smart_search_control_custom_search_query( $query ) {

        if ( !is_admin() && $query->is_main_query() && $query->is_search() ) {

            if ( !isset( $_POST[ 'post_type' ] ) || empty( $_POST[ 'post_type' ] ) ) {

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

Smart_Search_Control::instance();
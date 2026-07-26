<?php
/**
 * Smart_Search_Control Shortcode
 */

if( !defined( 'ABSPATH' ) ) {
    exit;
}
class SMARSECO_Smart_Search_Control_Short_Code {
        
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
     * Summary of visible_post_types
     * @var array
     */
    private static $visible_post_types = [];

    /**
     * Constructor
     */
    public static function instance() {

        if( is_null( self::$instance ) && ! ( self::$instance instanceof SMARSECO_Smart_Search_Control_Short_Code ) ) {

            self::$instance = new self;
            self::$instance->smarseco_hooks();
        }
        return self::$instance;
    }

    /**
     * All the required hooks are called here.
     */
    private function smarseco_hooks() {

        add_action( 'init', [ $this, 'smarseco_initialize_visible_post_types' ], 100 );
        add_action( 'wp_enqueue_scripts', [ $this, 'smarseco_smart_search_control_assets' ] );
        add_action( 'wp_ajax_smarseco_smart_search_control_suggestion', [ $this, 'smarseco_smart_search_control_suggestion' ] );
        add_action( 'wp_ajax_nopriv_smarseco_smart_search_control_suggestion', [ $this, 'smarseco_smart_search_control_suggestion' ] );
        add_shortcode( 'smart_search_control', [ $this, 'smarseco_render_search_shortcode' ] );
    }

    /**
     * Static method to initialize visible_post_types
     */
    public static function smarseco_initialize_visible_post_types() {

        if( empty( self::$visible_post_types ) ) {
            
            self::$visible_post_types = smarseco_get_visible_post_types();
        }
    }

    /**
     * Load the Assets
     */
    public function smarseco_smart_search_control_assets() {

        wp_register_style(
            'smart-search-control-style',
            SMARSECO_ASSETS_URL . 'css/smart-search-control-style.css',
            array(), SMARSECO_VERSION, 'all'
        );
        wp_register_script( 'smart-search-control-js', SMARSECO_ASSETS_URL . 'js/smart-search-control-ajax.js', [ 'jquery' ], SMARSECO_VERSION, true );
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
    public function smarseco_render_search_shortcode( $atts ) {

        global $wpdb;

        if( !SMARSECO_Smart_Search_Control::smarseco_smart_search_control_create_table() ) {

            ob_start();
            echo '<h3>' . esc_html( __( 'Table for Smart Search Control does not exist', 'smart-search-control' ) ). '</h3>';
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
        $ssc_id =  isset( $sanitized_atts['id'] ) ? $sanitized_atts['id'] : 0;
        $search_query = '';
        if( filter_var( $ssc_id, FILTER_VALIDATE_INT ) === false || intval( $ssc_id ) < 0 ) {

            return '<p>' . esc_html(__( 'Invalid ID provided.', 'smart-search-control' ) ) . '</p>';
        }

        $all_public_post_types = self::$visible_post_types;
        $posts_types = $all_public_post_types;
        $categories  = [];
        $tags        = [];

        $cache_key = 'ssc_data_' . $ssc_id;
        $result = wp_cache_get( $cache_key, 'smart_search_control' );

        if( false === $result ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $result = $wpdb->get_row( $wpdb->prepare( "SELECT data FROM `{$wpdb->prefix}smart_search_control_parameters` WHERE id = %d",$ssc_id  ) );
            wp_cache_set( $cache_key, $result, 'smart_search_control', 12 * HOUR_IN_SECONDS );
        }
            
        $class        = $sanitized_atts[ 'css_class' ];
        $css_id       = $sanitized_atts[ 'css_id' ];
        $placeholder  = $sanitized_atts[ 'place_holder' ];
        $ajax_enabled = (bool) get_option( 'smart_search_control_enable_ajax_all', 1 );

        if( !empty( $result ) && isset( $result->data ) ) {

            $data        = json_decode( $result->data );
            $class       = isset( $data->class ) ? $data->class : $class;
            $css_id      = isset( $data->css_id ) ? $data->css_id : $css_id;
            $placeholder = isset( $data->place_holder ) ? $data->place_holder : $placeholder;
            $categories  = isset( $data->categories ) ? $data->categories : [];
            $tags        = isset( $data->tags ) ? $data->tags : [];
            $posts_types = $this->smarseco_resolve_post_types_from_data( $data, $all_public_post_types );

            if( !empty( $data->disable_ajax ) ) {
                $ajax_enabled = false;
            }
        } else {
            if( '0' === $ssc_id ) {
                $posts_types = $all_public_post_types;
            } else {
                return '<p>' . esc_html( __( 'Invalid ID provided.', 'smart-search-control' ) ) . '</p>';
            }
        }

        $posts_types = smarseco_maybe_add_product_variation( $posts_types );

        $url = '';
        $fallback_page_id = get_option( 'smart_search_control_result_page' );

        if( $fallback_page_id ) {
            $url = get_permalink( $fallback_page_id );
        } else {
            $url = isset( $_SERVER['REQUEST_URI'] ) ? home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : home_url( '/' );
        }
        if( empty( $url ) ) {
            $url = home_url( '/' );
        }

        if( isset( $_GET['query'] ) && isset( $_GET['nonce'] ) && isset( $_GET['smartsearch'] )
            && (string) sanitize_text_field( wp_unslash( $_GET['smartsearch'] ) ) === (string) $ssc_id
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'ssc_search_nonce' ) ) {
            $search_query = sanitize_text_field( wp_unslash( $_GET[ 'query' ] ) );
        }

        ob_start();
        $template_path = SMARSECO_TEMPLATES_DIR . 'template-smart-search-control.php';
        if( file_exists( $template_path ) ) {
            include $template_path;
        }
        $output = ob_get_clean();

        if( empty( $fallback_page_id ) && !empty( $search_query ) ) {
            $output .= smarseco_render_inline_search_results( $search_query, $posts_types, $categories, $tags );
        }

        return $output;
    }
    
    /**
     * Summary of search_suggestion
     */
    public function smarseco_smart_search_control_suggestion() {

        global $wpdb;
        if( !isset( $_POST['nonce'] ) || !check_ajax_referer( 'smart_search_control_result_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Nonce verification failed', 'smart-search-control' ) ] );
        }
        if( empty( $_POST['search_query'] ) ) {
            wp_send_json_error( [ 'message' => __( 'Search query is empty.', 'smart-search-control' ) ] );
        }
        $search_query = sanitize_text_field( wp_unslash( $_POST['search_query'] ) );
        $ssc_id = isset( $_POST['ssc_id'] ) 
            ? intval( $_POST['ssc_id'] )
            : 0;

        $categories = [];
        $tags = [];

        // Check if this is a Gutenberg block search with post types
        if( isset( $_POST['block_post_types'] ) && !empty( $_POST['block_post_types'] ) ) {

            $block_post_types = sanitize_text_field( wp_unslash( $_POST['block_post_types'] ) );
            $requested_post_types = array_map( 'trim', explode( ',', $block_post_types ) );
            $posts_types = array_values( array_intersect( $requested_post_types, self::$visible_post_types ) );

            if( empty( $posts_types ) ) {
                $posts_types = self::$visible_post_types;
            }

            // Handle block categories
            if( isset( $_POST['block_categories'] ) && !empty( $_POST['block_categories'] ) ) {
                $block_categories_json = sanitize_text_field( wp_unslash( $_POST['block_categories'] ) );
                $decoded_categories = json_decode( $block_categories_json, true );
                if( is_array( $decoded_categories ) ) {
                    $categories = (object) $decoded_categories;
                }
            }
            
            // Handle block tags
            if( isset( $_POST['block_tags'] ) && !empty( $_POST['block_tags'] ) ) {
                $block_tags_json = sanitize_text_field( wp_unslash( $_POST['block_tags'] ) );
                $decoded_tags = json_decode( $block_tags_json, true );
                if( is_array( $decoded_tags ) ) {
                    $tags = (object) $decoded_tags;
                }
            }
            
        } else {
            // Original logic for shortcode-based searches
            if( !SMARSECO_Smart_Search_Control::smarseco_smart_search_control_create_table() ) {
                ob_start();
                echo '<h3>' . esc_html( __( 'Table for Smart Search Control does not exist', 'smart-search-control' ) ) . '</h3>';
                return ob_get_clean();
            }

            $cache_key = 'ssc_data_' . $ssc_id;
            $cache_group = 'smart_search_control';
            $result = wp_cache_get( $cache_key, $cache_group );
            if( false === $result ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $result = $wpdb->get_row( $wpdb->prepare( "SELECT data FROM `{$wpdb->prefix}smart_search_control_parameters` WHERE id = %d",  $ssc_id  ) );
                wp_cache_set( $cache_key, $result, $cache_group, 12 * HOUR_IN_SECONDS );
            }

            $all_public_post_types = self::$visible_post_types;
            $posts_types = $all_public_post_types;

            if( !empty( $result ) && isset( $result->data ) ) {
                $data = json_decode( $result->data );

                $categories = isset( $data->categories ) ? $data->categories : [];
                $tags = isset( $data->tags ) ? $data->tags : [];

                $posts_types = $this->smarseco_resolve_post_types_from_data( $data, $all_public_post_types );
            } else {
                $posts_types = $all_public_post_types;
            }
        } // End of else block for shortcode-based searches

        $posts_types = smarseco_maybe_add_product_variation( $posts_types );

        $post_per_page = apply_filters( 'smarseco_search_suggestion_per_page', 10 );

        $tax_query = smarseco_build_tax_query( $categories, $tags );

        $args = [
            's'             => $search_query,
            'post_type'     => $posts_types,
            'posts_per_page'=> $post_per_page,
            'post_status'   => 'publish',
        ];

        if( count( $tax_query ) > 1 ) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- tax_query is required for the category/tag search filtering feature.
            $args['tax_query'] = $tax_query;
        }

        $query = new WP_Query( $args );
        $posts = [];
        if( $query->have_posts() ) {
            while( $query->have_posts() ) {
                $query->the_post();
                $posts[] = [
                    'id'        => get_the_ID(),
                    'title'     => get_the_title(),
                    'permalink' => get_permalink(),
                ];
            }
            wp_reset_postdata();
        }
        if( empty( $posts ) ) {
            wp_send_json_error( [ 'message' => __( 'No results found.', 'smart-search-control' ) ] );
        }
        
        foreach( $posts as &$post ) {
            $decoded_title = html_entity_decode( $post['title'], ENT_QUOTES | ENT_HTML5 );
            $parts = preg_split( '/\s*[-–—]\s*/u', $decoded_title );
            $post['title'] = wp_strip_all_tags( implode( ' ', $parts ) );
        }
        unset( $post );
        wp_send_json_success( [ 'search_results' => $posts ] );
        wp_die();
    }

    /**
     * Resolve the post_type list stored on a decoded search-setting object,
     * falling back to the given default when unset, empty, or of an unexpected type.
     */
    private function smarseco_resolve_post_types_from_data( $data, $default_post_types ) {

        if( !isset( $data->post_type ) || empty( $data->post_type ) ) {
            return $default_post_types;
        }

        if( is_array( $data->post_type ) ) {
            return $data->post_type;
        }

        if( is_string( $data->post_type ) ) {
            return array_map( 'trim', explode( ',', $data->post_type ) );
        }

        return $default_post_types;
    }
}
SMARSECO_Smart_Search_Control_Short_Code::instance();

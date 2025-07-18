<?php

/**
 * Smart_Search_Control_Result Shortcode
*/
if ( ! defined( 'ABSPATH' ) ) exit;
class Smart_Search_Control_Result {
        
    /**
     * Summary of instance
     * @var 
     */
    private static $instance = null;

    /**
     * Summary of instance
     * @return Smart_Search_Control_Result
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Smart_Search_Control_Result) ) {
            self::$instance = new self;
            self::$instance->hooks();
        }
        return self::$instance;
    }

    /**
     * hooks
     * @return void
     */
    private function hooks() {

        add_action( 'wp_enqueue_scripts', [ $this, 'smart_search_control_result_assets' ] );
        add_shortcode( 'smart_search_result', [ $this, 'render_smart_search_result_shortcode' ] );
        add_filter( 'the_content', [ $this , 'override_selected_page_with_result_shortcode' ] );
    }

    /**
     * smart_search_control_result_assets
     * @return void
     */
    public function smart_search_control_result_assets() {

        wp_register_style(
            'smart-search-control-result-style',
            plugin_dir_url(__DIR__) . 'assets/css/smart-search-control-style.css',
            array(), SSC_VERSION, 'all'
            
        );
        wp_register_script( 'smart-search-control-result-js', SSC_ASSETS_URL . 'js/smart-search-control-ajax.js', [ 'jquery' ], SSC_VERSION, true );   
    }

    /**
     * smart_search_result_shortcode
     * @return bool|string
     */
    public function render_smart_search_result_shortcode() {

        global $wpdb;
        wp_enqueue_style( 'smart-search-control-result-style' );
        wp_enqueue_script( 'smart-search-control-result-js' );
        if ( ! $this->table_exists( ) ) {
            ob_start();
            echo '<h3>' . esc_html__( 'Table for Smart Search Control does not exist', 'smart-search-control' ) . '</h3>';
            return ob_get_clean();
        }
        $all_public_post_types = self::ssc_get_visible_post_types();
        $posts_types = $all_public_post_types;
        if ( isset( $_GET['query'] ) && ! empty( $_GET['query'] ) && isset( $_GET['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'ssc_search_nonce' ) ) {

            $search_query = sanitize_text_field( wp_unslash( $_GET['query'] ) );
            $ssc_id = isset( $_GET['smartsearch'] ) ? absint( $_GET['smartsearch'] ) : 0;

            $cache_key = 'ssc_data_' . md5( $ssc_id );
            $cache_group = 'smart_search_control';

            $data_cached = wp_cache_get( $cache_key, $cache_group );

            if ( false === $data_cached ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $result = $wpdb->get_row( $wpdb->prepare( "SELECT data FROM `{$wpdb->prefix}smart_search_control_parameters` WHERE id = %d", $ssc_id ) );

                if ( ! empty( $result ) && isset( $result->data ) ) {
                    wp_cache_set( $cache_key, $result->data, $cache_group, HOUR_IN_SECONDS );
                    $data_cached = $result->data;
                }
            }

            if ( ! empty( $data_cached ) ) {
                $data = json_decode( $data_cached );

                if ( isset( $data->post_type ) ) {
                    if ( is_array( $data->post_type ) ) {
                        $posts_types = $data->post_type;
                    } elseif ( is_string( $data->post_type ) ) {
                        $posts_types = array_map( 'trim', explode( ',', $data->post_type ) );
                    }
                }
            }

            if ( in_array( 'product', $posts_types, true ) && ! in_array( 'product_variation', $posts_types, true ) ) {
                $posts_types[] = 'product_variation';
            }
            $shortcode_content = '[smart_search_control id="' . esc_attr( $ssc_id ) . '"]';
        } else {
            $search_query = null;
            $posts_types = $all_public_post_types;
            $shortcode_content = '[smart_search_control id="0"]';
        }
                if ( is_object( $posts_types ) && isset( $posts_types->name ) ) {
            $posts_types = $posts_types->name;
        }
        if ( is_array( $posts_types ) ) {
            $posts_types = array_map( function( $post_type_object ) {
                return is_object( $post_type_object ) && isset( $post_type_object->name ) ? $post_type_object->name : $post_type_object;
            }, $posts_types );
        }
        $args = [
            's' => $search_query,
            'post_type' => $posts_types,
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'paged' => get_query_var( 'paged', 1 ),
        ];
        $query = new WP_Query( $args );

        ob_start();
        $template_path = SSC_TEMPLATES_DIR . 'template-smart-search-control-result.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
        return ob_get_clean();
    }
    
    /**
     * override selected page with result shortcode
     */
    public function override_selected_page_with_result_shortcode( $content ) {

        $selected_page_id = get_option( 'smart_search_control_result_page' );
        if ( is_page() && get_the_ID() == $selected_page_id ) {

            return do_shortcode( '[smart_search_result]' );
        }
        return $content;
    } 
    
    /**
     * Checks if a table exists in the database.
     */
    public function table_exists(  ) {

        $result = LD_Smart_Search_Control::smart_search_control_create_table();
        return $result;
    }

    /**
     * Get all visible post types
     */
    public function ssc_get_visible_post_types() {

        $args = [
            'public' => true,
            'publicly_queryable' => true,
        ];
        $visible_post_types = get_post_types( $args, 'objects' );
        if (!array_key_exists( 'page', $visible_post_types ) ) {
            $visible_post_types[ 'page' ] = get_post_type_object( 'page' );
        }
        $all_public_post_types = array_keys( $visible_post_types );
        return apply_filters( 'visible_post_types', $all_public_post_types );
    } 
}
Smart_Search_Control_Result::instance();
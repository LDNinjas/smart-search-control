<?php

/**
 * Smart_Search_Control_Result Shortcode
 */
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
            SSC_ASSETS_URL . '/css/smart-search-control-style.css'
        );
        wp_register_script( 'smart-search-control-result-js', SSC_ASSETS_URL . 'js/smart-search-control-ajax.js', [ 'jquery' ], SSC_VERSION, true );   
    }

    /**
     * smart_search_result_shortcode
     * @return bool|string
     */
    public function render_smart_search_result_shortcode( ) {

        global $wpdb;

        if ( isset( $_GET[ 'query' ] ) && !empty( $_GET[ 'query' ] ) ) {
            
            $search_query = sanitize_text_field( $_GET[ 'query' ] );
            
            $ssc_id = isset( $_GET[ 'smartsearch' ] ) ? sanitize_text_field( $_GET[ 'smartsearch' ] ) : '';

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
            
            $shortcode_content = '[smart_search_control id="' . esc_attr( $ssc_id ) . '"]';
            
            ob_start();
            $template_path = SSC_TEMPLATES_DIR . 'template-smart-search-control-result.php';
        
            if ( file_exists( $template_path ) ) {

                include $template_path;

            }
        
        }else{
            wp_redirect( home_url() );
            exit;
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
    
}

Smart_Search_Control_Result::instance();
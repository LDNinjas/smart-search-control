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
         * Summary of atts
         * @var array
         */
        private $has_search_code = false;
        private $atts = [];

        public static function instance() {

            if ( is_null( self::$instance ) && ! ( self::$instance instanceof WP_Search ) ) {
                self::$instance = new self;
                self::$instance->hooks();
            }

            return self::$instance;
        }

        /**
         * Hooks
         */
        private function hooks() {

            add_action( 'template_redirect', [ $this, 'check_has_shortcode' ] );
            add_action( 'wp_enqueue_scripts', [ $this, 'WP_Search_Assets' ] );
            add_action( 'wp_ajax_wp_search_result', [ $this , 'wp_search_result' ] );
            add_action( 'wp_ajax_nopriv_wp_search_result', [ $this , 'wp_search_result' ] );
            add_shortcode( 'wp_search_bar', [ $this, 'render_search_shortcode' ] );
        }
        
        /**
         * Check if the shortcode exist
         */
        public function check_has_shortcode() {

            global $post;
            if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'wp_search_bar' ) ) {
                $this->has_search_code = true;
            } 
        }

        /**
         * Load the Assets
         */
        public function WP_Search_Assets() {

            if ( !$this->has_search_code ) {
                return false;
            }

            wp_enqueue_style(
                'wp-search-style',
                WP_SEARCH_ASSETS_URL . '/css/style.css'
            );

            wp_enqueue_script( 'qas-search', WP_SEARCH_ASSETS_URL . 'js/wp-search-ajax.js', ['jquery'], '1.0', true );
            wp_localize_script( 'qas-search', 'WP_SEARCH', [
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'wp_search_result_nonce' ),
            ]);        
        }

        /**
         * Render the search shortcode
         */
        public function render_search_shortcode( $atts ) {

            /**
             * Set the global attributes
             */
            $this->atts = shortcode_atts(
                [
                    'placeholder' => 'Enter your search terms...',
                    'class'       => '',
                    'include'        => '',
                    'exclude'        => '',
                ],
                $atts,
                'wp_search_bar'
            );

            ob_start();

            /**
             * Get all post types
             */
            $all_post_types = get_post_types( [ 'public' => true ], 'names' );
            $include = !empty( $this->atts[ 'include' ] ) ? explode( ',', $this->atts[ 'include' ] ) : [];
            $exclude = !empty( $this->atts[ 'exclude' ] ) ? explode( ',', $this->atts[ 'exclude' ] ) : [];

            if ( !empty( $include ) ) {

                $post_types = array_intersect( $all_post_types, $include );
            } elseif ( !empty( $exclude ) ) {

                $post_types = array_diff( $all_post_types, $exclude );
            } else {

                $post_types = $all_post_types;
            }
            
            /**
             * Define template path
             */
            $template_path = WP_SEARCH_TEMPLATES_DIR . 'template-wp-search-bar.php';

            /**
             * Check if file exists before including
             */
            if ( file_exists( $template_path ) ) {
                include $template_path;
            }

            return ob_get_clean();
        }

        /**
        * Modify the search query
        */
        function wp_search_result() {
            if ( !isset( $_POST[ 'nonce' ] ) || !check_ajax_referer( 'wp_search_result_nonce', 'nonce', false ) ) {
                wp_send_json_error( [ 'message' => 'Nonce verification failed' ] );
            }
        
            if ( empty( $_POST[ 'search_query' ] ) ) {
                wp_send_json_error( [ 'message' => 'Search query is empty.' ] );
            }
        
            $search_query = sanitize_text_field( $_POST[ 'search_query' ] );
            $post_types = !empty( $_POST[ 'post_types' ] ) ? array_map( 'trim', explode( ',', sanitize_text_field( $_POST[ 'post_types' ] ) ) ) : [ 'post' ];
            $args = [
                's'              => $search_query,
                'post_type'      => $post_types,
            ];
        
            $query = new WP_Query( $args );
        
            if ( $query->have_posts() ) {
                ob_start();
                include WP_SEARCH_TEMPLATES_DIR . 'template-grid-wp-search.php';
                $html_output = ob_get_clean();
                wp_send_json_success( [ 'html' => $html_output ] );
            } else {
                wp_send_json_error( [ 'message' => 'No results found.' ] );
            }
        
            wp_die();
        }
}
WP_Search::instance();
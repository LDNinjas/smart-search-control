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
            add_shortcode('wp_search_bar', [$this, 'render_search_shortcode']);
            add_filter('get_the_excerpt', [$this, 'setup_excerpt_length']);
            // add_filter('template_include', [$this, 'wp_search_template_include']);
            add_action('wp_enqueue_scripts', [$this, 'WP_Search_Assets']);
            add_action('template_redirect', [$this, 'check_has_shortcode']);
            add_action('wp_ajax_modify_search_query', [$this, 'modify_search_query']);
            add_action('wp_ajax_nopriv_modify_search_query', [$this, 'modify_search_query']);
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
            wp_enqueue_script(
                'wp-search-ajax',
                WP_SEARCH_ASSETS_URL . '/js/wp-search-ajax.js',
                array( 'jquery' ),
                '1.0',
                true 
            );
            wp_localize_script( 'wp-search-ajax', 'SEARCH_FORM', array(
                'ajax_url'        => admin_url( 'admin-ajax.php' ),
                'site_url'        => get_site_url(),
                'error'           => __( 'Please enter a search query', 'wp_search' ),
                'search_nonce'    => wp_create_nonce( 'create_search_nonce' ),
            ));
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
                    'type'        => '',
                ],
                $atts,
                'wp_search_bar'
            );

            ob_start();

            /**
             * Get all post types
             */
            $post_types = get_post_types( [ 'public' => true ], 'objects' );

            $type = $this->atts[ 'type' ];

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
        public function modify_search_query() {

            wp_send_json_error( [ 'message' => 'Search query is empty.' ] );


            check_ajax_referer( 'create_search_nonce', 'nonce' );

                if ( empty( $_POST[ 'search_query' ] ) ) {
                    wp_send_json_error( [ 'message' => 'Search query is empty.' ] );
                    wp_die();
                }
            
                $search_query = sanitize_text_field( $_POST[ 'search_query' ] );    
                $post_types = isset( $_POST[ 'post_types' ] ) ? array_map( 'sanitize_text_field', $_POST[ 'post_types' ] ) : [ 'post' ];

                /**
                 * If 'product' is in the post types array, also include 'product_variation' 
                 */
                if ( in_array( 'product', $post_types ) ) {
                    $post_types[] = 'product_variation';
                }
                
                $args = [
                    's' => $search_query,
                    'post_type' => $post_types,
                ];
                
                $query = new WP_Query( $args );
            
                if ( $query->have_posts() ) {
                    ob_start();
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        echo '<div class="search-item"><a href="' . get_permalink() . '">' . get_the_title() . '</a></div>';
                    }
                    wp_reset_postdata();
                    wp_send_json_success(['html' => ob_get_clean()]);
                } else {
                    wp_send_json_error(['message' => 'No results found.']);
                }
                
                wp_die();
        }

        /**
        * Set the Excerpt Length
        */
        public function setup_excerpt_length( $excerpt ) {
            if ( !$this->has_search_code ) {
                return false;
            }
            if ( is_search() ) {
                return wp_trim_words( $excerpt, 10, '...' );
            }

            return $excerpt;
        }
}
WP_Search::instance();
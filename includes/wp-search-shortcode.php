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

        public function hooks() {

            /**
             * Register shortcode
             */

            add_shortcode( 'wp_search_bar', [ $this, 'render_search_shortcode' ] );


            /**
             * Add filter to process the search query
             */

            add_action( 'pre_get_posts', [ $this, 'modify_search_query' ] );

            /**
             * set the content length
             */

            add_filter( 'get_the_excerpt', [ $this,'setup_excerpt_length' ] );

            /**
             * Include the template
             */

            add_filter( 'template_include', [ $this, 'wp_search_template_include' ] );
        }

        /**
         * Check if the search is triggered by shortcode
         */
        public function is_shortcode_search() {
            global $post;
            if ( isset( $post->post_content ) && has_shortcode( $post->post_content, 'wp_search_bar' ) ) {
                return true;
            }
        
            $query = new WP_Query( [
                'post_type'      => 'page',
                'posts_per_page' => -1,
                'meta_query'     => [
                    [
                        'key'     => '_wp_page_template',
                        'compare' => 'EXISTS',
                    ],
                ],
            ]);
            if ( $query->have_posts() ) {
                foreach ( $query->posts as $p ) {
                    if ( has_shortcode( $p->post_content, 'wp_search_bar' ) ) {
                        wp_reset_postdata();
                        return true;
                    }
                }
            }
        
            wp_reset_postdata();

            return false;
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

            /**
             * get the post type of short code
             */

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

                public function modify_search_query( $query ) {
                    if ( $query->is_main_query() && $query->is_search() && ! is_admin() && $this->is_shortcode_search() ) {
                        $post_types = [];
                        if (! empty( $_GET['post-types'] )) {
                            $post_types = explode(  ',', sanitize_text_field( $_GET['post-types'] ) );
                        }

                        /**
                         * Apply post types to query if available
                         */

                        if (! empty ($post_types) ) {
                            $query->set( 'post_type', $post_types );
                        }

                        /**
                         *Process WooCommerce product variations 
                         */

                        if (isset( $_GET['include_variations'] ) && $_GET['include_variations'] === '1' && class_exists( 'WooCommerce') ) {
                            
                            /**
                             * Include 'product_variation' post type in the search
                             */

                            $query->set( 'post_type', ['product', 'product_variation'] );

                            $search_term = $query->get( 's' );
                            if ( $search_term ) {
                                add_filter( 'posts_where', function ( $where ) use ( $search_term ) {
                                    global $wpdb;
                        
                                    /**
                                     * Get the search term
                                     */
                        
                                    $search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
                                    
                                    /**
                                     * Split the search term into individual words
                                     */
                                    
                                    $search_terms = explode( ' ', $search_term );

                                    /**
                                     *Escape and prepare search terms for SQL
                                     */

                                    $search_term_1 = '%' . $wpdb->esc_like( $search_terms[0] ) . '%';
                                    $search_term_2 = isset( $search_terms[1] ) ? '%' . $wpdb->esc_like( $search_terms[1] ) . '%' : '';

                                    $where .= $wpdb->prepare(
                                        " AND {$wpdb->posts}.post_title LIKE %s AND {$wpdb->posts}.post_title LIKE %s AND {$wpdb->posts}.post_type = %s",
                                        $search_term_1,
                                        $search_term_2,
                                        'product_variation'
                                    );

                                    return $where;
                                });
                            }

                        }
                    }
            }
                /**
                * Set the Excerpt Length
                */

                public function setup_excerpt_length( $excerpt ) {
                    if ( $this->is_shortcode_search() && is_search() ) {
                        return wp_trim_words( $excerpt, 10, '...' );
                    }

                    return $excerpt;

                }

                /**
                 * Include the template
                 */

                public function wp_search_template_include( $template ) {


                    if ( $this->is_shortcode_search() && is_search() ) {

                        $new_template = plugin_dir_path( __FILE__ ) . '../templates/template-row-wp-search.php';
                        if ( file_exists( $new_template ) ) {

                            return $new_template;
                        }
                    }

                    return $template;
                }

            }
WP_Search::instance();
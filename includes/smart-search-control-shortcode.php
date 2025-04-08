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
        add_filter( 'render_block',  [ $this, 'modify_result_search_block' ], 10, 2 );
        add_filter( 'render_block', [ $this , 'customize_post_template_output' ], 11, 2 );
        add_filter( 'render_block', [ $this , 'customize_pagination_block' ], 12, 2 );
    
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
                'id' => '',
                'css_id' => '',
                'css_class' => '',
                'place_holder' => '',
                'post_type' => [],
            ],
            $atts,
            'smart_search_control'
        );
    
        $posts_types = array_map( 'trim', $sanitized_atts['post_type'] );
    
        $table_name = $wpdb->prefix . 'search_parameters';
        $query = "SELECT id, data FROM $table_name WHERE id = %d";
        $result = $wpdb->get_row( $wpdb->prepare( $query, $sanitized_atts[ 'id' ] ) );
    
        if ( !empty( $result ) ) {
            $stored_data = isset( $result->data ) ? $result->data : '';
            $data = json_decode( $stored_data );
        }
    
        $class = isset( $data->class ) ? $data->class : $sanitized_atts[ 'css_class' ];
        $css_id = isset( $data->css_id ) ? $data->css_id : $sanitized_atts[ 'css_id' ];
        $placeholder = isset( $data->place_holder ) ? $data->place_holder : $sanitized_atts[ 'place_holder' ];
        $posts_types = isset( $data->post_type ) ? ( is_array( $data->post_type ) ? $data->post_type : explode( ',', $data->post_type ) ) : $posts_types;
    
        ob_start();
        $template_path = SMART_SEARCH_CONTROL_TEMPLATES_DIR . 'template-smart-search-control.php';
        include $template_path;
        $content = ob_get_contents();
        ob_end_clean();
    
        return $content;
    }

    /**
     * Modify the core search block to use our custom search form
     */
    public function modify_result_search_block( $block_content, $block ) {

        if ( is_search() && $block['blockName'] === 'core/search' ) {
            return $this->short_code_search();
        }
        return $block_content;
    }
    
    
    public function short_code_search() {
    
        // Capture POST values and sanitize
        $css_id = isset( $_POST['css_id'] ) ? sanitize_text_field( $_POST['css_id'] ) : '';
        $class = isset( $_POST['css_class'] ) ? sanitize_text_field( $_POST['css_class'] ) : '';
        $placeholder = isset( $_POST['place_holder'] ) ? sanitize_text_field( $_POST['place_holder'] ) : '';
        $posts_types = isset( $_POST['post_type'] ) ? ( is_array( $_POST['post_type'] ) ? $_POST['post_type'] : explode( ',', $_POST['post_type'] ) ) : [];
        $posts_types = array_map( 'trim', $posts_types );
        
        return $this->render_search_shortcode( [
            'css_id' => $css_id,
            'css_class' => $class,
            'place_holder' => $placeholder,
            'post_type' => $posts_types,
        ]);
    }

    /**
     * Summary of search_suggestion
     */
    public function smart_search_control_suggestion() {

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
     * Override the Query for the search results
     */
    public function smart_search_control_custom_search_query( $query ) {

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

            if ( isset( $_POST['paged'] ) ) {
                $query->set( 'paged', intval( $_POST['paged'] ) );
            }

            $query->set( 'posts_per_page', 3 ); 
        }
    }

    /**
     * Summary of modify_result_block
     */
    public function customize_post_template_output( $block_content, $block ) {

        if ( is_search() && $block[ 'blockName' ] === 'core/post-template' ) {

            global $wp_query;
    
            if ( $wp_query->have_posts() ) {
                ob_start();
                echo '<div class="custom-search-results">';
    
                while ( $wp_query->have_posts() ) {
                    $wp_query->the_post();
                    ?>
                        <div class="search-result-item">
                            <div class="search-featured-image">
                            
                                <a href="<?php the_permalink(); ?>">

                                    <?php if( has_post_thumbnail()  ){
                                        the_post_thumbnail( 'medium' ); 
                                    }else{?>
                                        <img src="<?php echo SMART_SEARCH_CONTROL_ASSETS_URL . 'default-img/no-feature-image.jpg' ?>" alt=" <?php echo esc_attr( get_the_title() ) ?> "/>
                                    <?php }
                                    ?>
                                        
                                </a>
                            </div>
                            
                            <h2 class="search-title">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h2>
                            
                            <div class="search-excerpt">
                                <?php echo wp_trim_words( get_the_excerpt(), 25, '...' ); ?>
                            </div>
                        </div>

                    <?php
                }
                echo '</div>';
    
                wp_reset_postdata();
                return ob_get_clean();
            } else {
                return '<p>No results found.</p>';
            }
        }

        return $block_content;
    }

    /**
     * Summary of customize_pagination_block
     */
    public function customize_pagination_block( $block_content, $block ) {
        
        if ( is_search() && 'core/query-pagination' === $block['blockName'] ) {

            $block_content = str_replace( 'wp-block-query-pagination', 'wp-block-query-pagination ssc-pagination-class', $block_content );
        }
    
        return $block_content;
    }
    
}

Smart_Search_Control::instance();
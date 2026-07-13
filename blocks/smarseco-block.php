<?php

/**
 * Smart Search Control Gutenberg Block
 */

if( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Class SMARSECO_Gutenberg_Block
 */
class SMARSECO_Gutenberg_Block {
    
    private static $instance = null;

    /**
     * Counts how many block instances have rendered in this request, so each
     * instance gets a stable, request-repeatable ID (block order is identical
     * between the initial page load and the reload after a form submission).
     */
    private static $render_count = 0;

    public static function instance() {
        if( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor function
     */
    private function __construct() {

        add_action( 'init', [ $this, 'register_block' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'ssc_gutenberg_block_enqueue_scripts' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
    }
    
    /**
     * Register REST API routes for Gutenberg block
     */
    public function register_rest_routes() {
        register_rest_route( 'smart-search-control/v1', '/taxonomies/(?P<post_type>[a-zA-Z0-9_-]+)', array(
            'methods' => 'GET',
            'callback' => array( $this, 'get_taxonomies_for_post_type' ),
            'permission_callback' => array( $this, 'check_permissions' ),
            'args' => array(
                'post_type' => array(
                    'required' => true,
                    'validate_callback' => function( $param, $request, $key ) {
                        return is_string( $param ) && post_type_exists( $param );
                    }
                )
            )
        ) );
    }

    /**
     * Check permissions for REST API access
     */
    public function check_permissions() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * REST API callback to get taxonomies for a post type
     */
    public function get_taxonomies_for_post_type( $request ) {
        $post_type = $request->get_param( 'post_type' );
        
        if( !post_type_exists( $post_type ) ) {
            return new WP_Error( 'invalid_post_type', 'Invalid post type', array( 'status' => 400 ) );
        }

        // Use the existing admin method to get categories and tags
        $terms = smarseco_get_categories_and_tags( $post_type );
        
        // Format for REST API response
        $formatted_categories = array();
        $formatted_tags = array();
        
        // Process categories
        if( !empty( $terms['categories'] ) ) {
            foreach( $terms['categories'] as $taxonomy => $taxonomy_terms ) {
                $formatted_categories[ $taxonomy ] = array();
                foreach( $taxonomy_terms as $term_id => $term ) {
                    $formatted_categories[ $taxonomy ][] = array(
                        'id' => $term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
                    );
                }
            }
        }
        
        // Process tags
        if( !empty( $terms['tags'] ) ) {
            foreach( $terms['tags'] as $taxonomy => $taxonomy_terms ) {
                $formatted_tags[ $taxonomy ] = array();
                foreach( $taxonomy_terms as $term_id => $term ) {
                    $formatted_tags[ $taxonomy ][] = array(
                        'id' => $term_id,
                        'name' => $term->name,
                        'slug' => $term->slug
                    );
                }
            }
        }
        
        return rest_ensure_response( array(
            'categories' => $formatted_categories,
            'tags' => $formatted_tags,
            'post_type' => $post_type
        ) );
    }

    /**
     * Enqueue scripts for Gutenberg block editor
     */
    public function ssc_gutenberg_block_enqueue_scripts() {

        wp_enqueue_style(
            'smarseco-gutenberg-block-admin',
            SMARSECO_ASSETS_URL . 'css/smart-search-control-block.css',
            [],
            SMARSECO_VERSION,
            'all'
        );
        
        // Enqueue Select2 assets for block editor
        wp_enqueue_style(
            'smarseco-select2-css',
            SMARSECO_ASSETS_URL . 'css/smarseco-select2.min.css',
            [],
            SMARSECO_VERSION,
            'all'
        );
        
        wp_enqueue_script(
            'smarseco-select2-js',
            SMARSECO_ASSETS_URL . 'js/smarseco-select2.min.js',
            array( 'jquery' ),
            SMARSECO_VERSION,
            true
        );
    }

    /**
     * Register Smart Search Control Gutenberg Block
     */
    public function register_block() {

        wp_register_script(
            'smart-search-control-gutenberg-block',
            SMARSECO_URL . 'assets/js/smart-search-control-block.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-block-editor', 'jquery', 'smarseco-select2-js' ),
            SMARSECO_VERSION,
            true
        );
        
        wp_localize_script(
            'smart-search-control-gutenberg-block',
            'smarsecoBlockData',
            array( 'availablePostTypes' => $this->get_post_types() )
        );
        
        register_block_type( 'smart-search-control/search-block', array(
            'editor_script' => 'smart-search-control-gutenberg-block',
            'render_callback' => array( $this, 'ssc_render_block' ),
            'attributes' => array(
                'placeholder' => array( 'type' => 'string', 'default' => 'Search...' ),
                'cssId' => array( 'type' => 'string', 'default' => '' ),
                'cssClass' => array( 'type' => 'string', 'default' => '' ),
                'postTypes' => array( 'type' => 'array', 'default' => array() ),
                'categories' => array( 'type' => 'object', 'default' => array() ),
                'tags' => array( 'type' => 'object', 'default' => array() )
            )
        ) );
    }
    
    /**
     * Get available post types for the block
     * @return array
     */
    private function get_post_types() {

        $post_types = array();
        
        $post_types_data = smarseco_get_visible_post_types();
        if( is_array( $post_types_data ) ) {
            foreach( $post_types_data as $post_type ) {
                $post_types[] = array( 'value' => $post_type, 'label' => $post_type );
            }
        }
        
        return $post_types;
    }
    
    /**
     * Render the block on the front end
     * @param array $attributes
     * @return string
     */
    public function ssc_render_block( $attributes ) {

        $placeholder = isset( $attributes['placeholder'] ) ? sanitize_text_field( wp_unslash( $attributes['placeholder'] ) ) : 'Search...';
        $css_id = isset( $attributes['cssId'] ) ? sanitize_text_field( wp_unslash( $attributes['cssId'] ) ) : '';
        $css_class = isset( $attributes['cssClass'] ) ? sanitize_html_class( wp_unslash( $attributes['cssClass'] ) ) : '';
        $post_types = isset( $attributes['postTypes'] ) ? array_map( 'sanitize_text_field', wp_unslash( $attributes['postTypes'] ) ) : array();
        $categories = isset( $attributes['categories'] ) ? $attributes['categories'] : array();
        $tags = isset( $attributes['tags'] ) ? $attributes['tags'] : array();
        
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'smart-search-control-style' );
        wp_enqueue_script( 'smart-search-control-js' );
        
        if( empty( $post_types ) ) {
            $post_types = smarseco_get_visible_post_types();
        }
        
        $post_types = smarseco_maybe_add_product_variation( $post_types );
        
        $result_page_id = get_option( 'smart_search_control_result_page' );
        if( $result_page_id ) {
            $url = get_permalink( $result_page_id );
        } else {
            $url = isset( $_SERVER['REQUEST_URI'] ) ? home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : home_url( '/' );
        }
        if( empty( $url ) ) {
            $url = home_url( '/' );
        }

        $ssc_id = 'block_' . self::$render_count++;

        $search_query = '';
        if( isset( $_GET['query'] ) && isset( $_GET['nonce'] ) && isset( $_GET['smartsearch'] )
            && (string) sanitize_text_field( wp_unslash( $_GET['smartsearch'] ) ) === (string) $ssc_id
            && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'ssc_search_nonce' ) ) {
            $search_query = sanitize_text_field( wp_unslash( $_GET['query'] ) );
        }

        $class = $css_class;
        $block_posts_types = $post_types;
        $block_categories = $categories;
        $block_tags = $tags;
        
        ob_start();
        $template_path = SMARSECO_TEMPLATES_DIR . 'template-smart-search-control.php';
        if( file_exists( $template_path ) ) {
            include $template_path;
        }
        $output = ob_get_clean();

        if( empty( $result_page_id ) && !empty( $search_query ) ) {
            $output .= smarseco_render_inline_search_results( $search_query, $post_types, $categories, $tags );
        }

        return $output;
    }
}

SMARSECO_Gutenberg_Block::instance();

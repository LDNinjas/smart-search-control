<?php

/**
 * Smart Search Control Main Plugin functions
 */

if( !defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get all visible post types
 */
function smarseco_get_visible_post_types() {

    $args = [
        'public' => true,
        'publicly_queryable' => true,
    ];
    $visible_post_types = get_post_types( $args, 'objects' );
    if( !array_key_exists( 'page', $visible_post_types ) ) {
        $visible_post_types[ 'page' ] = get_post_type_object( 'page' );
    }
    $all_public_post_types = array_keys( $visible_post_types );
    return apply_filters( 'smarseco_visible_post_type_slugs', $all_public_post_types );
}

/**
 * Get categories and tags for a given post type
 */
function smarseco_get_categories_and_tags( $post_type ) {
    $categories = [];
    $tags       = [];

    $taxonomies = get_object_taxonomies( $post_type, 'objects' );

    foreach( $taxonomies as $taxonomy ) {
        
        // Skip non-public or hidden taxonomies
        if( !$taxonomy->public || !$taxonomy->show_ui ) {
            continue;
        }

        $terms = get_terms( [
            'taxonomy'   => $taxonomy->name,
            'hide_empty' => false,
        ] );

        if( is_wp_error( $terms ) || empty( $terms ) ) {
            continue;
        }

        foreach( $terms as $term ) {
            if( $taxonomy->hierarchical ) {
                $categories[ $taxonomy->name ][ $term->term_id ] = $term;
            } else {
                $tags[ $taxonomy->name ][ $term->term_id ] = $term;
            }
        }
    }

    return [
        'categories' => $categories,
        'tags'       => $tags,
    ];
}

/**
 * Build a WP_Query tax_query array from categories/tags data.
 * Accepts either an object (decoded from stored JSON) or an array (Gutenberg block attributes).
 */
function smarseco_build_tax_query( $categories, $tags ) {

    if( is_array( $categories ) ) {
        $categories = (object) $categories;
    }
    if( is_array( $tags ) ) {
        $tags = (object) $tags;
    }

    $tax_query = [ 'relation' => 'OR' ];

    if( !empty( $categories ) && is_object( $categories ) ) {
        foreach( $categories as $taxonomy => $term_ids ) {
            if( !empty( $term_ids ) && is_array( $term_ids ) ) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => array_map( 'intval', $term_ids ),
                    'operator' => 'IN',
                ];
            }
        }
    }

    if( !empty( $tags ) && is_object( $tags ) ) {
        foreach( $tags as $taxonomy => $term_ids ) {
            if( !empty( $term_ids ) && is_array( $term_ids ) ) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => array_map( 'intval', $term_ids ),
                    'operator' => 'IN',
                ];
            }
        }
    }

    return $tax_query;
}

/**
 * Ensure 'product_variation' is included whenever 'product' is queried.
 */
function smarseco_maybe_add_product_variation( $post_types ) {

    if( in_array( 'product', $post_types, true ) && !in_array( 'product_variation', $post_types, true ) ) {
        $post_types[] = 'product_variation';
    }

    return $post_types;
}

/**
 * Render search results inline (used when no dedicated Search Results Page is configured),
 * shared by the shortcode and Gutenberg block so both render the same markup.
 */
function smarseco_render_inline_search_results( $search_query, $posts_types, $categories, $tags ) {

    if( empty( $search_query ) ) {
        return '';
    }

    $tax_query = smarseco_build_tax_query( $categories, $tags );

    $args = [
        's'              => $search_query,
        'post_type'      => $posts_types,
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'paged'          => get_query_var( 'paged', 1 ),
    ];

    if( count( $tax_query ) > 1 ) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- tax_query is required for the category/tag search filtering feature.
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query( $args );

    ob_start();
    $shortcode_content = '';
    $template_path     = SMARSECO_TEMPLATES_DIR . 'template-smart-search-control-result.php';
    if( file_exists( $template_path ) ) {
        include $template_path;
    }
    $content = ob_get_clean();
    wp_reset_postdata();

    return $content;
}
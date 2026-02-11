<?php

/**
 * Smart Search Control Main Plugin functions
 */

if ( ! defined( 'ABSPATH' ) ) {
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
    if (!array_key_exists( 'page', $visible_post_types ) ) {
        $visible_post_types[ 'page' ] = get_post_type_object( 'page' );
    }
    $all_public_post_types = array_keys( $visible_post_types );
    return apply_filters( 'visible_post_types', $all_public_post_types );
}

/**
 * Get categories and tags for a given post type
 */
function eff_get_categories_and_tags( $post_type ) {
    $categories = [];
    $tags       = [];

    $taxonomies = get_object_taxonomies( $post_type, 'objects' );

    foreach ( $taxonomies as $taxonomy ) {
        // Skip non-public or hidden taxonomies
        if ( ! $taxonomy->public || ! $taxonomy->show_ui ) {
            continue;
        }

        $terms = get_terms( [
            'taxonomy'   => $taxonomy->name,
            'hide_empty' => false,
        ] );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            continue;
        }

        foreach ( $terms as $term ) {
            if ( $taxonomy->hierarchical ) {
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
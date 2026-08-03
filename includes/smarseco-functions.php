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
        'posts_per_page' => 20,
        'post_status'    => 'publish',
        'paged'          => get_query_var( 'paged', 1 ),
    ];

    if( count( $tax_query ) > 1 ) {
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- tax_query is required for the category/tag search filtering feature.
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query( $args );

    ob_start();
    $template_path = SMARSECO_TEMPLATES_DIR . 'template-smart-search-control-result.php';
    if( file_exists( $template_path ) ) {
        include $template_path;
    }
    $content = ob_get_clean();
    wp_reset_postdata();

    return $content;
}

/**
 * Build regex pattern from search words for highlighting keywords.
 *
 * @param string $search_query The search query containing keywords.
 * @return string Compiled regex pattern for case-insensitive keyword matching.
 */
function smarseco_build_highlight_pattern( $search_query ) {

    $search_words = array_filter( explode( ' ', $search_query ) );
    if( empty( $search_words ) ) {
        return '';
    }

    $patterns = [];
    foreach( $search_words as $word ) {
        $patterns[] = preg_quote( $word, '/' );
    }

    return '/(' . implode( '|', $patterns ) . ')/i';
}

/**
 * Highlight search keywords in text (case-insensitive).
 * Highlights ALL instances of all search keywords.
 *
 * @param string $text The text to highlight keywords in.
 * @param string $search_query The search query containing keywords.
 * @return string Text with keywords wrapped in <strong> tags.
 */
function smarseco_highlight_keywords( $text, $search_query ) {

    if( empty( $text ) || empty( $search_query ) ) {
        return esc_html( $text );
    }

    $text_escaped = esc_html( $text );
    $pattern = smarseco_build_highlight_pattern( $search_query );

    if( empty( $pattern ) ) {
        return $text_escaped;
    }

    return preg_replace( $pattern, '<strong>$1</strong>', $text_escaped );
}

/**
 * Extract excerpt snippet around search keyword(s).
 *
 * If keyword is found in excerpt/content, returns 25 words before + 25 words after.
 * If keyword is in title only, returns standard excerpt.
 *
 * @param string $search_query The search query containing one or more keywords.
 * @param int $post_id The post ID to extract snippet from.
 * @return string Excerpt snippet with highlighted keyword.
 */
function smarseco_get_smart_excerpt( $search_query, $post_id ) {

    if( empty( $search_query ) ) {
        return '';
    }

    $post = get_post( $post_id );
    if( !$post ) {
        return '';
    }

    // Get post content
    $post_title = $post->post_title;
    $post_excerpt = $post->post_excerpt;
    $post_content = wp_strip_all_tags( $post->post_content );

    // Split search query into words
    $search_words = array_filter( explode( ' ', $search_query ) );
    if( empty( $search_words ) ) {
        return '';
    }

    // Check if keyword is in title
    $keyword_in_title = false;
    foreach( $search_words as $word ) {
        if( stripos( $post_title, $word ) !== false ) {
            $keyword_in_title = true;
            break;
        }
    }

    // If keyword is only in title, return standard excerpt with highlighted keywords
    if( $keyword_in_title ) {
        $excerpt_text = !empty( $post_excerpt ) ? $post_excerpt : $post_content;
        $trimmed_excerpt = wp_trim_words( $excerpt_text, 25, '...' );
        $trimmed_escaped = esc_html( $trimmed_excerpt );

        $pattern = smarseco_build_highlight_pattern( $search_query );
        if( empty( $pattern ) ) {
            return $trimmed_escaped;
        }

        return preg_replace( $pattern, '<strong>$1</strong>', $trimmed_escaped );
    }

    // Search for keyword in excerpt/content and extract context
    $searchable_text = $post_excerpt . ' ' . $post_content;
    $searchable_text_lower = strtolower( $searchable_text );

    // Find first keyword in content
    $keyword_position = false;
    $found_keyword = '';

    foreach( $search_words as $word ) {
        $word_lower = strtolower( $word );
        $pos = stripos( $searchable_text_lower, $word_lower );

        if( $pos !== false ) {
            $keyword_position = $pos;
            $found_keyword = substr( $searchable_text, $pos, strlen( $word ) );
            break;
        }
    }

    // If keyword not found in excerpt/content, return standard excerpt with highlighted keywords
    if( $keyword_position === false ) {
        $excerpt_text = !empty( $post_excerpt ) ? $post_excerpt : $post_content;
        $trimmed_excerpt = wp_trim_words( $excerpt_text, 25, '...' );
        $trimmed_escaped = esc_html( $trimmed_excerpt );

        $pattern = smarseco_build_highlight_pattern( $search_query );
        if( empty( $pattern ) ) {
            return $trimmed_escaped;
        }

        return preg_replace( $pattern, '<strong>$1</strong>', $trimmed_escaped );
    }

    // Extract 25 words before + 25 words after keyword
    $words_array = preg_split( '/\s+/', $searchable_text, -1, PREG_SPLIT_NO_EMPTY );
    $keyword_word_position = 0;
    $current_char_pos = 0;

    // Find which word index contains our keyword
    foreach( $words_array as $index => $word ) {
        if( $current_char_pos <= $keyword_position && $current_char_pos + strlen( $word ) > $keyword_position ) {
            $keyword_word_position = $index;
            break;
        }
        $current_char_pos += strlen( $word ) + 1; // +1 for space
    }

    $start_word = max( 0, $keyword_word_position - 25 );
    $end_word = min( count( $words_array ) - 1, $keyword_word_position + 25 );

    $snippet_words = array_slice( $words_array, $start_word, $end_word - $start_word + 1 );
    $snippet = implode( ' ', $snippet_words );

    // Escape HTML first, then add highlights
    $snippet_escaped = esc_html( $snippet );

    // Add ellipsis at start/end if truncated
    $ellipsis_start = $start_word > 0 ? '... ' : '';
    $ellipsis_end = $end_word < count( $words_array ) - 1 ? ' ...' : '';

    $pattern = smarseco_build_highlight_pattern( $search_query );
    if( empty( $pattern ) ) {
        return $ellipsis_start . $snippet_escaped . $ellipsis_end;
    }

    $snippet_highlighted = preg_replace( $pattern, '<strong>$1</strong>', $snippet_escaped );

    return $ellipsis_start . $snippet_highlighted . $ellipsis_end;
}


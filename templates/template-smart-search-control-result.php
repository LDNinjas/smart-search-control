<?php

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Get the default image attachment ID from the option
 */
$attachment_id = get_option( 'my_plugin_default_image_id', 0 );

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

?>

<div class="smart-search-control-result-header">
    <?php if( $search_query != null ){ ?>
        <h2 class="result-header"><?php echo esc_attr( __( 'Search Results for:', 'smart-search-control' ) ) . ' ' . esc_html( $search_query ); ?></h2>
    <?php }?>
    <div class="result-header">
        <?php echo do_shortcode( $shortcode_content ); ?>
    </div>
</div>

    <div class="custom-search-results">

    <?php

    if ( $query->have_posts() && $search_query != null ) {

        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <div class="search-result-item">
                <div class="search-featured-image">

                    <a href="<?php the_permalink(); ?>">
                        <?php
                            if ( has_post_thumbnail() ) {
                                the_post_thumbnail( 'medium' );
                            }elseif ( $attachment_id ) {
                                echo wp_get_attachment_image(
                                    $attachment_id,
                                    'medium',
                                    false,
                                    [
                                        'alt' => esc_attr__( 'Default placeholder image', 'smart-search-control' ),
                                    ]
                                );
                            } else {
                                echo wp_kses_post(
                                    '<div class="no-image-placeholder">' .
                                    esc_html__( 'No image available', 'smart-search-control' ) .
                                    '</div>'
                                );
                            }
                        
                        ?>
                    </a>

                </div>

                <h2 class="search-title">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </h2>

                <div class="search-excerpt">
                    <?php echo esc_html( wp_trim_words( get_the_excerpt(), 25, '...' ) ); ?>
                </div>
            </div>
            <?php
        
    }
    echo'</div>';
    } 
    else {
        ?>
            <div class="smart-search-control-no-result">
                <h2><?php echo esc_attr( __( 'No Result Found ', 'smart-search-control' ) ); ?></h2>
            </div>
        
    <?php }
    ?>

<!-- Pagination -->
<?php

if ( $query->max_num_pages > 1 ) {
    ?>

    <div class="pagination">
        <?php
        echo esc_html( paginate_links( [
            'total' => $query->max_num_pages,
            'prev_text' => '&laquo; Previous',
            'next_text' => 'Next &raquo;',
        ] ) );
        ?>
    </div>

    <?php
}

wp_reset_postdata();
?>
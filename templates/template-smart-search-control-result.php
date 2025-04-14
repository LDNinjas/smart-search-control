<?php

if ( !defined( 'ABSPATH' ) ) exit;

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
    <h2 class="result-header"><?php echo __( 'Search Results for:', 'smart-search-control' ) . ' ' . esc_html( $search_query ); ?></h2>
    <div class="result-header">
        <?php echo do_shortcode( $shortcode_content ); ?>
    </div>
</div>

<div class="custom-search-results">

    <?php

    if ( $query->have_posts() ) {

        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <div class="search-result-item">
                <div class="search-featured-image">

                    <a href="<?php the_permalink(); ?>">
                        <?php
                        if ( has_post_thumbnail() ) {
                            the_post_thumbnail( 'medium' );
                        } else {
                            ?>
                            <img src="<?php echo SMART_SEARCH_CONTROL_ASSETS_URL . 'default-img/no-feature-image.jpg' ?>"
                                alt="<?php echo esc_attr( get_the_title() ); ?>" />
                            <?php
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
                    <?php echo wp_trim_words( get_the_excerpt(), 25, '...' ); ?>
                </div>
            </div>
            <?php
        }

    } else {
        ?>

            </div>
            <div class="smart-search-control-no-result">
                <h2><?php echo __( 'No Result Found ', 'smart-search-control' ); ?></h2>
            </div>
        
    <?php }
    ?>

<!-- Pagination -->
<?php

if ( $query->max_num_pages > 1 ) {
    ?>

    <div class="pagination">
        <?php
        echo paginate_links( [
            'total' => $query->max_num_pages,
            'prev_text' => '&laquo; Previous',
            'next_text' => 'Next &raquo;',
        ] );
        ?>
    </div>

    <?php
}

wp_reset_postdata();
?>
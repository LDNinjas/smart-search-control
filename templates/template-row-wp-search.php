<?php 
if ( !defined( 'ABSPATH' ) ) 
exit;
if ( $query->have_posts() ) { ?>
    <div id="search-results-container-row">
        <div class="search-results-header">
            <h1>Search Results for: <span class="text-info">
                <?php echo esc_html( $search_query ); ?>
            </span></h1>
        </div>

        <div id="wp-search-results-row">
            <?php while ( $query->have_posts() ) {
                $query->the_post(); ?>
                <hr>
                <div class="wp-search-post-row">
                    <img src="<?= esc_url( get_the_post_thumbnail_url() ) ?>" alt="<?= get_the_title()  ?>" class="wp-search-img-row">
                    <h2><a href="<?= esc_url( get_permalink() ) ?>"> <?= get_the_title() ?> </a></h2>
                    <p class="search-content"><?= get_the_excerpt() ?></p>
                </div>
            <?php } ?>
        </div>
    </div>
<?php } else { ?>
    <div id="search-results">
        <p>No posts found for "<?php echo esc_html( $search_query ); ?>"</p>
    </div>
<?php }
wp_reset_postdata(); ?>

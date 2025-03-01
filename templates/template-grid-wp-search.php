<?php 
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( $query->have_posts() ): ?>
    <div id="wp-search-results">
        <h2>Search Results for: <span class="text-info"><?php echo esc_html( $search_query ); ?></span></h2>
        <?php while ( $query->have_posts() ): 
            $query->the_post(); ?>
            <div class="wp-search-post">
                <img src="<?php echo esc_url ( get_the_post_thumbnail_url() ); ?>" 
                    alt="<?php echo esc_attr( get_the_title() ); ?>" 
                    class="wp-search-img">
                <div class="search-result-content">
                    <h3><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h3>
                    <p class="wp-search-post-content"><?php the_content(); ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div id="search-results">
        <p>No posts found for "<?php echo esc_html( $search_query ); ?>"</p>
    </div>
<?php endif; ?>

<?php wp_reset_postdata(); ?>

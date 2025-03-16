<?php 
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

if ( !empty( $posts ) ) : ?>
    <div class="search-results-container-row">
        <div class="search-results-header">
            <h1><?php esc_html_e( 'Search Results for:', 'your-textdomain' ); ?> 
                <span class="text-info"><?php echo esc_html( $search_query ); ?></span>
            </h1>
        </div>

        <div class="wp-search-results-row">
            <?php foreach ( $posts as $post ) : ?>
                <hr>
                <div class="wp-search-post-row">
                    <img src="<?php echo esc_url( $post[ 'thumbnail' ] ); ?>" 
                        alt="<?php echo esc_attr( $post[ 'title' ] ); ?>" 
                        class="wp-search-img-row">
                    <h2>
                        <a href="<?php echo esc_url( $post[ 'permalink' ] ); ?>">
                            <?php echo esc_html( $post[ 'title' ] ); ?>
                        </a>
                    </h2>
                    <p class="search-content"><?php echo esc_html( $post[ 'content' ] ); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

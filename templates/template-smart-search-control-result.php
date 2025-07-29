<?php

if ( !defined( 'ABSPATH' ) ) exit;
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
                        } else {
                            $result_img =  plugin_dir_url(__DIR__) . 'assets/default-img/no-feature-image.jpg' ;
                            // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
                            echo '<img src="' . esc_url( $result_img ). '"
                                    alt="' . esc_attr__( 'Default placeholder image', 'smart-search-control' ) . '">';
                            
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
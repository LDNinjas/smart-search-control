<?php

if( !defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="smart-search-control-result-header">
    <div class="ssc-header-inner-wrapper">
        <?php if( null !== $search_query ) { ?>
            <h2 class="result-header"><?php echo esc_attr( __( 'Search Results for:', 'smart-search-control' ) ) . ' ' . esc_html( $search_query ); ?></h2>
        <?php
        }

        $smarseco_selected_page_id = get_option( 'smart_search_control_result_page' );
        if( $smarseco_selected_page_id ) {
            ?>
            <div class="result-header">
                <?php echo do_shortcode( $shortcode_content ); ?>
            </div>
            <?php
        }
        ?>
    </div>
</div>
    <?php 
    if( $query->have_posts() && $search_query != null ) {
        ?>
        <div class="ssc-view-option-wrapper">
            <button class="ssc-button ssc-list-btn ssc-btn-background">
                <span class="dashicons dashicons-menu"></span>
                <?php echo esc_html__( 'list', 'smart-search-control' ); ?>
            </button>
            <button class="ssc-button ssc-grid-btn">
                <span class="dashicons dashicons-grid-view"></span>
                <?php echo esc_html__( 'Grid', 'smart-search-control' ); ?>
            </button>
        </div>
        <?php
    }
    ?>

    <div class="ssc-custom-search-results">

    <?php
    if( $query->have_posts() && $search_query != null ) {

        while( $query->have_posts() ) {
            $query->the_post();
            ?>
            <div class="ssc-search-result-item ssc-list-view">

                <div class="ssc-post-featured-wrapper ssc-responsive-width">
                    <a href="<?php the_permalink(); ?>">
                        <?php
                        $default_img = SMARSECO_ASSETS_URL . 'default-img/no-feature-image.jpg';
                        $post_id = get_the_ID();

                        // Allow filters to override the featured image completely
                        $custom_image_html = apply_filters( 'smarseco_result_featured_image', null, $post_id, $default_img );

                        if( $custom_image_html ) {
                            // If filter returns custom image, use it instead of default
                            echo wp_kses_post( $custom_image_html );
                        } elseif( has_post_thumbnail() ) {
                            // Use post thumbnail if available
                            the_post_thumbnail( 'thumbnail' );
                        } else {
                            // Fallback to default placeholder
                            echo '<img src="' . esc_url( $default_img ) . '"
                                    alt="' . esc_attr__( 'Default placeholder image', 'smart-search-control' ) . '">';
                        }
                        ?>
                    </a>
                </div>
                <div class="ssc-post-content-wrapper">
                    <h2 class="search-title">
                        <a href="<?php the_permalink(); ?>">
                            <?php
                            if( null !== $search_query ) {
                                echo wp_kses_post( smarseco_highlight_keywords( get_the_title(), $search_query ) );
                            } else {
                                the_title();
                            }
                            ?>
                        </a>
                    </h2>

                    <div class="search-excerpt">
                        <?php
                        if( null !== $search_query ) {
                            // Use smart excerpt that shows context around keyword
                            echo wp_kses_post( smarseco_get_smart_excerpt( $search_query, get_the_ID() ) );
                        } else {
                            // Fallback if no search query
                            if( has_excerpt() ) {
                                echo esc_html( wp_trim_words( get_the_excerpt(), 25, '...' ) );
                            } else {
                                echo esc_html( wp_trim_words( get_the_content(), 25, '...' ) );
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        
    }
    echo'</div>';
    } 
    else {
        ?>
            <div class="smart-search-control-no-result">
                <p><?php echo esc_attr( __( 'No Result Found ', 'smart-search-control' ) ); ?></p>
            </div>
        
    <?php }
    ?>

<!-- Pagination -->
<?php

if( $query->max_num_pages > 1 && $query->have_posts() && null !== $search_query ) {
    ?>

    <div class="pagination">
        <?php
        echo wp_kses_post( paginate_links( [
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
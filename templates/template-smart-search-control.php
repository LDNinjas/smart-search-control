<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="<?php echo esc_attr( ( $css_id ) ); ?>" class="smarseco-default-search-container parent-container <?php echo esc_attr( ( $class ) );?>">

    <div  class="smarseco-default-search-bar-container">
            <form  action="<?php echo esc_url( $url ); ?>" method="GET" class="smarseco-default-search-bar ssc-search-form" id="smarseco-search-form">

                <input type="text" name="query" class="smarseco-default-search-input search-query"
                value="<?php echo !empty( $search_query ) ? esc_attr( $search_query ) :  '' ?>"
                placeholder="<?php echo !empty( $placeholder ) ? esc_attr( $placeholder ) : esc_attr( __( 'Search...', 'smart-search-control' ) ); ?>" aria-label="Search">

                <!-- Hidden Post Type Input -->
                <input type="hidden" name="smartsearch" value="<?php echo esc_attr( $ssc_id); ?>">
                <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ssc_search_nonce' ) ); ?>">
                <!-- Search Button -->
                <button type="submit" class="smarseco-default-search-btn search-btn "> 
                    <span class="smarseco-default-search-icon">
                        <span class="dashicons dashicons-search"></span>
                    </span>
                </button>
            </form>
    </div>
    <div class="ssc-view-option-wrapper">
        <button class="ssc-button ssc-list-btn ssc-btn-background">
            <span class="dashicons dashicons-menu"></span>
            <?php echo __( 'list', 'smart-search-control' ); ?>
        </button>
        <button class="ssc-button ssc-grid-btn">
            <span class="dashicons dashicons-grid-view"></span>
            <?php echo __( 'grid', 'smart-search-control' ); ?>
        </button>
    </div>
    <!-- Search Suggestions Dropdown -->
    <div class="search-suggestions"></div>
</div>

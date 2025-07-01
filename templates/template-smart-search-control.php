<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="<?php echo esc_html( ( $css_id ) ) ?>" class="ssc-default-search-container parent-container <?php echo esc_html( ( $class ) )?>">

    <div  class="ssc-default-search-bar-container">
        <form  action="<?php echo esc_url( $url ); ?>" method="GET" class="ssc-default-search-bar ssc-search-form" id="ssc-search-form">

            <input type="text" name="query" class="ssc-default-search-input search-query"
                value="<?php echo !empty( $search_query ) ? esc_attr( $search_query ) :  '' ?>"
                placeholder="<?php echo !empty( $placeholder ) ? esc_attr( $placeholder ) : esc_attr( __( 'Search...', 'smart-search-control' ) ); ?>" aria-label="Search">

            <!-- Hidden Post Type Input -->
            <input type="hidden" name="smartsearch" value="<?php echo esc_attr( $ssc_id); ?>">
            <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'ssc_search_nonce' ) ); ?>">
            <!-- Search Button -->
            <button type="submit" class="ssc-default-search-btn search-btn "> 
                <span class="ssc-default-search-icon">
                    <span class="dashicons dashicons-search"></span>
                </span>
            </button>
        </form>

    </div>

    <!-- Search Suggestions Dropdown -->
    <div class="search-suggestions"></div>
</div>
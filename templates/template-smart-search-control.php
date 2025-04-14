<?php

if ( !defined( 'ABSPATH' ) ){
    exit;

}

$args = array(
    'post_type'   => 'page',
    'post_status' => 'publish',
    's'           => '[smart_search_result]',
    'numberposts' => 1
);

$pages = get_posts( $args );

$url = '';

if ( ! empty( $pages ) ) {
    
    $url = get_permalink( $pages[ 0 ]->ID );
} else {

    $fallback_page_id = get_option( 'smart_search_control_result_page' );
    if ( $fallback_page_id ) {
        $url = get_permalink( $fallback_page_id );
    }
}

if( isset( $_GET[ 'query' ] )  ){

    $search_query = sanitize_text_field( $_GET[ 'query' ] );
}
?>

<div id="<?php echo ( $css_id ) ?>" class="ssc-default-search-container parent-container <?php echo ( $class ) ?>">

    <div  class="ssc-default-search-bar-container">
        <form  action="<?php echo esc_url( $url ); ?>" method="GET" class="ssc-default-search-bar ssc-search-form" id="ssc-search-form">

            <input type="text" name="query" class="ssc-default-search-input search-query"
                value="<?php echo !empty( $search_query ) ? esc_attr( $search_query ) :  '' ?>"
                placeholder="<?php echo !empty( $placeholder ) ? esc_attr( $placeholder ) : __( 'Search...', 'smart-search-control' ); ?>" aria-label="Search">

            <!-- Hidden Post Type Input -->
            <input type="hidden" name="smartsearch" value="<?php echo esc_attr( $ssc_id); ?>">
            
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
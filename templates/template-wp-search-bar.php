<?php

if ( !defined( 'ABSPATH' ) )
    exit;
?>

<div id="<?php echo ( $css_id ) ?>" class="wp-default-search-container parent-container <?php echo ( $class ) ?>">

    <div  class="wp-default-search-bar-container">
        <form  action="<?php echo esc_url( home_url( '/' ) ); ?>"  method="POST" class="wp-default-search-bar wp-search-form" >

            <!-- Input Field -->
            <input type="text" name="s" class="wp-default-search-input search-query"
                placeholder="<?php echo !empty( $placeholder ) ? esc_attr( $placeholder ) : __( 'Search...', 'wp-search' ); ?>" aria-label="Search">

                <!-- Post Type Hidden Input -->
                <input type="hidden" name="post_type" value="<?php echo esc_attr( implode( ',', $posts_types ) ); ?>">
                
                <!-- Search Icon -->
                <button class="wp-default-search-btn search-btn "> 
                    <span class="wp-default-search-icon">
                        <span class="dashicons dashicons-search"></span>
                    </span>
                </button>
            </form>
        </div>

    <!-- Search Suggestions Dropdown -->
    <div class="search-suggestions"></div>
</div>

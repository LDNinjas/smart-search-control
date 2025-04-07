<?php

if ( !defined( 'ABSPATH' ) )
    exit;
?>

<div id="<?php echo ( $css_id ) ?>" class="ssc-default-search-container parent-container <?php echo ( $class ) ?>">

    <div  class="ssc-default-search-bar-container">
        <form  action="<?php echo esc_url( home_url( '/' ) ); ?>"  method="POST" class="ssc-default-search-bar ssc-search-form" >

            <!-- Input Field -->
            <input type="text" name="s" class="ssc-default-search-input search-query"
                placeholder="<?php echo !empty( $placeholder ) ? esc_attr( $placeholder ) : __( 'Search...', 'smart-search-control' ); ?>" aria-label="Search">

                <!-- Post Type Hidden Input -->
                <input type="hidden" name="post_type" value="<?php echo esc_attr( implode( ',', $posts_types ) ); ?>">
                
                <input type="hidden" name="css_id" value="<?php echo esc_attr( $css_id ); ?>">
                <input type="hidden" name="css_class" value="<?php echo esc_attr( $class ); ?>">
                <input type="hidden" name="place_holder" value="<?php echo esc_attr( $placeholder ); ?>">

                <!-- Search Icon -->
                <button class="ssc-default-search-btn search-btn "> 
                    <span class="ssc-default-search-icon">
                        <span class="dashicons dashicons-search"></span>
                    </span>
                </button>
            </form>
        </div>

    <!-- Search Suggestions Dropdown -->
    <div class="search-suggestions"></div>
</div>

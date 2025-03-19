<?php

if ( !defined( 'ABSPATH' ) )
    exit;
?>
<div class="<?php echo $class; ?>-container parent-container">

    <div class="<?php echo $class; ?>-bar-container">
        <form  action="<?php echo esc_url( home_url( '/' ) ); ?>"  method="POST" class="<?php echo $class; ?>-bar wp-search-form" >

            <!-- Input Field -->
            <input type="text" name="s" class="<?php echo $class; ?>-input search-query"
                placeholder="<?php echo $placeholder; ?>" aria-label="Search">

            <!-- Post Type Hidden Input -->
            <input type="hidden" name="post_type" value="<?php echo esc_attr( implode( ',', $post_types ) ); ?>">

            <!-- Search Icon -->
            <button class="<?php echo $class; ?>-btn search-btn"> 
                <span class="<?php echo $class; ?>-icon">
                    <span class="dashicons dashicons-search"></span>
                </span>
            </button>
        </form>
    </div>
    <!-- Search Suggestions Dropdown -->
    <div class="search-suggestions"></div>
</div>

<?php

if ( !defined( 'ABSPATH' ) )
    exit;
?>
<div class="search-container">

<!-- <div class="search-container<?php echo esc_attr( $this->atts[ 'class' ] ); ?>"> -->
    <div class="search-bar-container">
        <form action="<?php echo esc_url( home_url( '/' ) ) ; ?>" method="POST" class="search-bar" id="wp-search-form">

            <!-- Search Icon -->
            <span class="search-icon">
                <span class="dashicons dashicons-search"></span>
            </span>

            <!-- Input Field -->
            <input type="text" name="s" id="search-query" class="search-input"
                placeholder="Search " aria-label="Search">
                <!-- placeholder="<?php echo esc_attr( $this->atts[ 'placeholder' ] ); ?>" aria-label="Search"> -->

            <!-- Post Type Hidden Input -->
            <input type="hidden" name="post_type" value="<?php echo esc_attr( implode( ',', $post_types ) ); ?>">

            <!-- Submit Button -->
            <button class="search-btn">
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </form>
    </div>
</div>
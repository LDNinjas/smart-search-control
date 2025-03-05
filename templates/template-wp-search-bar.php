<?php

if ( !defined( 'ABSPATH' ) )
    exit;
?>
<div class="<?php echo $class; ?>-container">

    <div class="<?php echo $class; ?>-bar-container">
        <form action="<?php echo esc_url( home_url( '/' ) ) ; ?>" method="POST" class="<?php echo $class; ?>-bar" id="wp-search-form">

            <!-- Search Icon -->
            <span class="<?php echo $class; ?>-icon">
                <span class="dashicons dashicons-search"></span>
            </span>

            <!-- Input Field -->
            <input type="text" name="s" id="search-query" class="<?php echo $class; ?>-input"
                placeholder="<?php echo esc_attr( $this->atts[ 'placeholder' ] ); ?>" aria-label="Search">

            <!-- Post Type Hidden Input -->
            <input type="hidden" name="post_type" value="<?php echo esc_attr( implode( ',', $post_types ) ); ?>">

            <!-- Submit Button -->
            <button class="<?php echo $class; ?>-btn search-result-btn">
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </form>
    </div>
</div>
<div id="search-results"></div>
<?php

if ( !defined( 'ABSPATH' ) )
    exit;
?>
<div class="search-container<?php echo esc_attr( $this->atts[ 'class' ] ); ?>">
    <div class="search-bar-container">
        <form action="<?php echo esc_url( home_url( '/' ) ) ; ?>" method="POST" class="search-bar" id="wp-search-form">

            <!--
            Search Icon
            -->
            <span class="search-icon">
                <span class="dashicons dashicons-search"></span>
            </span>

            <!--
            Input Field 
            -->
            <input type="text" name="s" id="search-query" class="search-input"
                placeholder="<?php echo esc_attr( $this->atts[ 'placeholder' ] ); ?>" aria-label="Search">

            <!-- 
            post type pass 
            -->
            <input type="hidden" name="shortcode_post_type" value="<?php echo esc_attr( $type ); ?>">

            <!--
            Submit Button 
            -->
            <button type="submit" class="search-btn">
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </form>
    </div>
</div>
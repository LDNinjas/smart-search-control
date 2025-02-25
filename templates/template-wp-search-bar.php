<?php

if (!defined('ABSPATH'))
    exit;

?>
<div class="search-container<?php echo esc_attr( $this->atts[ 'class' ] ); ?>">
    <div class="search-bar-container">
        <form action="<?php echo esc_url( home_url( '/' ) ) ; ?>" method="get" class="search-bar" id="wp-search-form">

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

            <!-- Set wp_search=1 -->
            <input type="hidden" name="wp_search" value="1">


            <!--
            Submit Button 
            -->
            <button type="submit" class="search-btn">
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </form>
    </div>

    <!--
    addvanve btn 
    -->

    <button id="advanced-filters-toggle" class="advance-search-btn mb-3"
        style="display: <?php echo empty( $type ) ? 'block' : 'none'; ?>">
        Advanced Filters
    </button>
</div>

<!-- 
Advanced Filters 
-->

<div class="advance-container">
    <div class="advanced-filters">
        <div>
            <h3 class="mb-3">Advanced Filters</h3>

            <!-- 
            Filter by Post Types 
            -->

            <div>
                <label for="post-types">Filter by Post Types:</label>
                <div class="post-types-field">
                    <?php foreach ( $post_types as $post_type_slug => $post_type_obj ): ?>
                        <div class="form-check">
                            <input type="checkbox" name="post-types[]" value="<?php echo esc_attr( $post_type_slug ); ?>"
                                class="form-check-input" id="post-type-<?php echo esc_attr( $post_type_slug ); ?>">
                            <label class="form-check-label" for="post-type-<?php echo esc_attr( $post_type_slug ); ?>">
                                <?php echo esc_html( $post_type_obj->labels->name ); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <hr>
            </div>
            <!--
            Addional Filter
            -->
            <div id="additional-filters"></div>

            <!-- 
            Button to Add New Filter Row 
            -->

            <button type="submit" class="add-filter-row">
                Add Filter
            </button>
        </div>
    </div>
</div>
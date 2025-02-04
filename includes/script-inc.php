<?php
// Check if the class is already declared to prevent reloading
if (!class_exists('WP_Search')) {
    class WP_Search
    {
        private $atts = []; // Store shortcode attributes globally
        public function __construct()
        {
            // Register shortcode
            add_shortcode('wp_search_bar', array($this, 'render_search_shortcode'));

            // Add filter to process the search query
            add_action('pre_get_posts', array($this, 'modify_search_query'));

            // Include the template
            add_filter('template_include', array($this, 'wp_search_template_include'));
        }

        /**
         * Render the search shortcode.
         */
        public function render_search_shortcode($atts)
        {
            // Set the global attributes
            $this->atts = shortcode_atts(
                array(
                    'placeholder' => 'Enter your search terms...', // Default placeholder
                    'class' => '',
                    'type' => '', 
                ),
                $atts,
                'wp_search_bar'
            );

            ob_start();
            // Get all post types
            $post_types = get_post_types(['public' => true], 'objects');
            // get the post type of short code
            $type = $this->atts['type'];

            ?>
            <div class="search-container <?php echo esc_attr($this->atts['class']); ?>">
                <div class="search-bar-container">
                    <form action="<?php echo esc_url(home_url('/')); ?>" method="get" class="search-bar" id="wp-search-form">
                        <!-- Search Icon -->
                        <span class="search-icon">
                        <span class="dashicons dashicons-search"></span>
                        </span>
                        <!-- Input Field -->
                        <input type="text" name="s" id="search-query" class="search-input"
                            placeholder="<?php echo esc_attr($this->atts['placeholder']); ?>" aria-label="Search">
                        <!-- post type pass -->
                        <input type="hidden" name="shortcode_post_type" value="<?php echo esc_attr($type); ?>">

                        <!-- Submit Button -->
                        <button type="submit" class="search-btn">
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                            </button>
                        </form>
                    </div>


                        <!-- addvanve btn -->
                        <button id="advanced-filters-toggle" class="advance-search-btn mb-3" 
                            style="display: <?php echo empty($type) ? 'block' : 'none'; ?>">
                            Advanced Filters
                        </button>

            </div>

            <!-- Advanced Filters -->
            <div class="advance-container">
                <div class="advanced-filters">
                    <div>
                        <h3 class="mb-3">Advanced Filters</h3>

                        <!-- Filter by Post Types -->
                        <div >
                            <label for="post-types">Filter by Post Types:</label>
                            <div class="post-types-field">
                                <?php foreach ($post_types as $post_type_slug => $post_type_obj): ?>
                                    <div class="form-check">
                                        <input type="checkbox" name="post-types[]" value="<?php echo esc_attr($post_type_slug); ?>"
                                            class="form-check-input" id="post-type-<?php echo esc_attr($post_type_slug); ?>">
                                        <label class="form-check-label" for="post-type-<?php echo esc_attr($post_type_slug); ?>">
                                            <?php echo esc_html($post_type_obj->labels->name); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <hr>
                        </div>

                        <!-- WooCommerce Filters (if enabled) -->
                        <?php if (class_exists('WooCommerce')): ?>
                            <div class="woocommerce-filters">
                                <div class="form-check ">
                                    <input type="checkbox" name="include_variations" id="include-variations" class="form-check-input">
                                    <label class="form-check-label" for="include-variations">
                                        Include WooCommerce Variations
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div id="additional-filters"></div>

                        <!-- Button to Add New Filter Row -->
                        <button type="submit" class="add-filter-row">
                            Add Filter
                        </button>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        }

        /**
         * Modify the search query to include advanced filters.
         */
        public function modify_search_query($query){
            if ($query->is_main_query() && $query->is_search() && !is_admin()) {
                // Process post types
                $post_types = [];
                if (!empty($_GET['post-types'])) {
                    $post_types = explode(',', sanitize_text_field($_GET['post-types']));
                }
        
                // Apply post types to query if available
                if (!empty($post_types)) {
                    $query->set('post_type', $post_types);
                }
                // Process WooCommerce product variations
                if (isset($_GET['include_variations']) && $_GET['include_variations'] === '1' && class_exists('WooCommerce')) {
                    // Include 'product_variation' post type in the search
                    $query->set('post_type', array('product', 'product_variation'));

                    $search_term = $query->get('s');
                    if ($search_term) {
                        add_filter('posts_where', function ($where) use ($search_term) {
                            global $wpdb;

                            // Get the search term 
                            $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

                            // Split the search term into individual words
                            $search_terms = explode(' ', $search_term);

                            // Escape and prepare search terms for SQL
                            $search_term_1 = '%' . $wpdb->esc_like($search_terms[0]) . '%';
                            $search_term_2 = isset($search_terms[1]) ? '%' . $wpdb->esc_like($search_terms[1]) . '%' : '';

                            $where .= $wpdb->prepare(
                                " AND {$wpdb->posts}.post_title LIKE %s AND {$wpdb->posts}.post_title LIKE %s AND {$wpdb->posts}.post_type = %s",
                                $search_term_1,
                                $search_term_2,
                                'product_variation'
                            );

                            return $where;
                        });
                    }

                }
            }
        }
        
        /**
         * Include the custom search template.
         */
        public function wp_search_template_include($template)
        {
            if (is_search()) {
                $new_template = plugin_dir_path(__FILE__) . '../templates/template-row-wp-search.php';
                if (file_exists($new_template)) {
                    return $new_template;
                }
            }
            return $template;
        }
    }

    // Initialize the class
    new WP_Search();
}
?>
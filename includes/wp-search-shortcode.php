<?php
// Check if the class is already declared to prevent reloading
if (!class_exists('WP_Search')) {
    class WP_Search
    {

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

            // Set default placeholder text
            $atts = shortcode_atts(
                array(
                    'placeholder' => 'Enter your search terms...', // Default placeholder
                ),
                $atts,
                'wp_search_bar'
            );
            ob_start();
            // Get all post types
            $post_types = get_post_types(['public' => true], 'objects');
            ?>
            <div class="search-form p-3">
                <div class="search-container mt-5 d-flex justify-content-center">
                    <form action="<?php echo esc_url(home_url('/')); ?>" method="get"
                        class="search-bar d-flex align-items-center shadow" id="wp-search-form">
                        <!-- Search Icon -->
                        <span class="search-icon bg-white d-flex align-items-center justify-content-center">
                            <i class="fa fa-search text-muted"></i>
                        </span>
                        <!-- Input Field -->
                        <input type="text" name="s" id="search-query" class="search-input "
                            placeholder="<?php echo esc_attr($atts['placeholder']); ?>" aria-label="Search">
                        <!-- Submit Button -->
                        <button type="submit" class="search-btn d-flex align-items-center justify-content-center">
                            <i class="fa fa-arrow-right text-white"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Button to Toggle Advanced Filters -->
            <button id="advanced-filters-toggle" class="advance-search-btn mb-3">Advanced Filters</button>

            <!-- Advanced Filters -->
            <div class="container d-flex justify-content-center align-items-center">
                <div class="advanced-filters card shadow-sm p-4 w-75 mb-4" style="display: none;">
                    <div class="d-flex flex-column align-items-start text-start">
                        <h3 class="mb-3">Advanced Filters</h3>

                        <!-- Filter by Post Types -->
                        <div class="w-100">
                            <label for="post-types" class="form-label fw-bold">Filter by Post Types:</label>
                            <div class="d-flex flex-column border px-2" style="max-height: 80px; overflow-y: auto;">
                                <?php foreach ($post_types as $post_type_slug => $post_type_obj): ?>
                                    <div class="form-check">
                                        <input type="checkbox" name="post-types[]" value="<?php echo esc_attr($post_type_slug); ?>"
                                            class="form-check-input" id="post-type-<?php echo esc_attr($post_type_slug); ?>">
                                        <label class="form-check-label mb-2" for="post-type-<?php echo esc_attr($post_type_slug); ?>">
                                            <?php echo esc_html($post_type_obj->labels->name); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <hr>
                        </div>

                        <!-- WooCommerce Filters (if enabled) -->
                        <?php if (class_exists('WooCommerce')): ?>
                            <div class="woocommerce-filters w-100">
                                <div class="form-check mb-0">
                                    <input type="checkbox" name="include_variations" id="include-variations" class="form-check-input">
                                    <label class="form-check-label" for="include-variations">
                                        Include WooCommerce Variations
                                    </label>
                                </div>
                                <hr>
                            </div>
                        <?php endif; ?>

                        <!-- Additional Filters Section -->
                        <hr>
                        <div id="additional-filters" class="w-100"></div>

                        <!-- Button to Add New Filter Row -->
                        <button type="submit" class="add-filter-row btn btn-success">
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
        public function modify_search_query($query)
        {
            if ($query->is_main_query() && $query->is_search() && !is_admin()) {
                // Process post types filter
                if (isset($_GET['post-types'])) {
                    // Ensure 'post-types' is always an array (split by comma)
                    $post_types = explode(',', $_GET['post-types']); // Split string into array
                    $post_types = array_map('sanitize_text_field', $post_types); // Sanitize the array
                    $query->set('post_type', $post_types); // Set the query to include these post types
                }

                // Process WooCommerce product variations
                if (isset($_GET['include_variations']) && $_GET['include_variations'] === 'on' && class_exists('WooCommerce')) {
                    $meta_query = $query->get('meta_query') ?: array();
                    $meta_query[] = array(
                        'key' => '_is_variation',
                        'compare' => 'EXISTS',
                    );
                    $query->set('meta_query', $meta_query);
                }
            }
        }



        /**
         * Include the custom search template.
         */
        public function wp_search_template_include($template)
        {
            if (is_search()) {
                $new_template = plugin_dir_path(__FILE__) . '../template-wp-search.php';
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
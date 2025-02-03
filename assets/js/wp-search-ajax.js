jQuery(document).ready(function($) {
    $('#wp-search-form').on('submit', function(e) {
        e.preventDefault();

        var searchQuery = $('#search-query').val();
        var postTypes = [];
        // Add post from user checked
        $('input[name="post-types[]"]:checked').each(function() {
            postTypes.push($(this).val());
        });
        // Add post types from the shortcode if available
        var shortcodePostTypes = $('input[name="shortcode_post_type"]').val(); 
        if (shortcodePostTypes) {
        postTypes = postTypes.concat(shortcodePostTypes.split(','));
        }
        // Include Variations
        var includeVariations = $('#include-variations').is(':checked') ? 1 : 0;

        // Construct the URL
        if (searchQuery.trim() !== '') {
            var url = myAjax.site_url + '?s=' + encodeURIComponent(searchQuery);
            
            // Append post types if selected
            if (postTypes.length > 0) {
                url += '&post-types=' + encodeURIComponent(postTypes.join(','));
            }

            // Append the variations parameter
            if (includeVariations) {
                url += '&include_variations=1';
            }

            // Redirect to result page
            window.location.href = url;
        }else {
            // show alert if no search query
            var form = $('.search-bar-container');
            var alertMessage = $('<div class="alert">Please enter a search query</div>')
                .insertBefore(form);
            // hide alert after 5 seconds
            setTimeout(function() {
                alertMessage.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }                   
        
    });
});

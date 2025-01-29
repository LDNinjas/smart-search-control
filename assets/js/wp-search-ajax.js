jQuery(document).ready(function($) {
    // Listen for form submission
    $('#wp-search-form').on('submit', function(e) {
        e.preventDefault();

        var searchQuery = $('#search-query').val(); // Get the search query
        var postTypes = [];
        $('input[name="post-types[]"]:checked').each(function() {
            postTypes.push($(this).val());
        });
        var includeVariations = $('#include-variations').is(':checked') ? 1 : 0; // Check if variations are included

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

            // Redirect to the constructed URL
            window.location.href = url;
        }else {
            var form = $('.search-form');
            var alertMessage = $('<div class="alert alert-danger w-auto mx-auto" role="alert" style="font-size: 0.875rem;">Please enter a search query</div>')
                .insertAfter(form)
                .css({
                    'text-align': 'start', // Center the text inside the alert
                    'max-width': '500px' // Set a max width to keep the alert small
                });
        
            // After 5 seconds, fade out the alert
            setTimeout(function() {
                alertMessage.fadeOut(function() {
                    $(this).remove(); // Optionally remove the alert after fading out
                });
            }, 5000); // 5000ms = 5 seconds
        }                   
        
    });
});

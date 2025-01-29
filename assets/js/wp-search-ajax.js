jQuery(document).ready(function ($) {
    // Listen for form submission
    $('#wp-search-form').on('submit', function (e) {
        e.preventDefault();

        var searchQuery = $('#search-query').val(); // Get the search query
        var postTypes = [];
        $('input[name="post-types[]"]:checked').each(function () {
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
        }

    });
});

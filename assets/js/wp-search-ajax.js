( function( $ ) { 'use strict';
    $( document ).ready( function() {
        let SearchForm = {
            init: function() {
                this.handleSearchFormSubmit();
            },

            /**
             * Handle Search Form Submit
             */
            handleSearchFormSubmit: function() {
                $( '#wp-search-form').on( 'submit', function( e ) {
                    e.preventDefault();

                    let searchQuery = $( '#search-query' ).val();
                    let postTypes = [];
                    
                    /**
                     * Add post types from user checked
                     */
                    $(' input[ name="post-types[]" ]:checked' ).each( function() {
                        postTypes.push( $( this ).val() );
                    });

                    /**
                     * Add post types from the shortcode if available
                     */

                    let shortcodePostTypes = $( 'input[ name="shortcode_post_type" ]' ).val();
                    if ( shortcodePostTypes ) {
                        postTypes = postTypes.concat( shortcodePostTypes.split( ',' ) );
                    }

                    /**
                     * Construct the URL
                     */
                    if ( searchQuery.trim() !== '' ) {
                        let url = myAjax.site_url + '?s=' + encodeURIComponent( searchQuery );
                        
                        /**
                         * Append post types if selected
                         */
                        if ( postTypes.length > 0 ) {
                            url += '&post-types=' + encodeURIComponent( postTypes.join( ',' ) );
                        }

                        /**
                         * Redirect to result page
                         */
                        window.location.href = url;
                    } else {

                        /**
                         * Show alert if no search query
                         */
                        let form = $( '.search-bar-container' );
                        let alertMessage = $( '<div class="alert">Please enter a search query</div>' )
                            .insertBefore( form );

                        /**
                         * Hide alert after 5 seconds
                         */
                        setTimeout( function() {
                            alertMessage.fadeOut(function() {
                                $( this ).remove();
                            });
                        }, 5000);
                    }                   
                });
            }
        };
        SearchForm.init();
    });
})( jQuery );

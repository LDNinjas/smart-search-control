( function ( $ ) { 
    'use strict';
    $( document ).ready( function () {
        let SearchForm = {
            init: function () {
                this.handleSearchFormSubmit();
            },

            handleSearchFormSubmit: function () {
                $( '.search-btn' ).on( 'click', function ( e ) {
                    e.preventDefault();
                    let searchQuery = $( '#search-query' ).val();
                    let postTypes = $( 'input[name="post_type"]' ).val();
                    console.log( postTypes );
                    // let searchResultsContainer = $( "#search-result" );
                    // console.log( searchResultsContainer.val(  ) );

                    console.log( 'response send' )
                    $.ajax({
                        url: WP_SEARCH.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wp_search_result',
                            nonce: WP_SEARCH.nonce,
                            search_query: searchQuery,
                            post_types: postTypes,
                        },
                        success: function( response ) {
                            
                            if ( response.success && response.data ) {
                                console.log( "Response Success:", response.data.html );
                                // searchResultsContainer.html( response.data.html );
                            } else {
                                console.log( "Response Failed:", response );
                                // searchResultsContainer.html( '<p>' + ( response?.data?.message || "No results found." ) + '</p>' );
                            }
                        },
                        error: function( xhr, status, error ) {
                            console.error( "AJAX Error:", status, error );
                            // searchResultsContainer.html( '<p>Error fetching results.</p>' );
                        }
                    });
                    
                });
            }
        };
        SearchForm.init();
    });
})( jQuery );

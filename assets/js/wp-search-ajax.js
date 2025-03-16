( function ( $ ) {
    
    'use strict';
    
    $( document ).ready( function () {
        
        let SearchForm = {

            lastQuery: '',
            ajaxRequest: null,
            timer: null,

            init: function () {
                this.handleSearchFormSubmit();
                this.searchTimer();
            },

            /**
             * Check every 2 sec whether search should be initiated.
             */
            searchTimer: function() {
                SearchForm.timer = setTimeout(() => {  

                    $( '.search-query' ).on( 'keyup', function () {

                        let self = $( this );
                        let parentContainer = self.closest( '.parent-container' );
                        let searchResults = parentContainer.find( '.search-results' );

                        let searchQuery = self.val().trim();

                        if ( searchQuery.length === 0 ) {
                            searchResults.empty().change();
                            return;
                        }

                        if ( searchQuery.length <= 2 ) {
                            searchResults.empty().change();
                            SearchForm.lastQuery = '';
                            clearTimeout( SearchForm.timer );

                            if ( SearchForm.ajaxRequest ) {
                                SearchForm.ajaxRequest.abort();
                                SearchForm.ajaxRequest = null;
                            }
                            return;
                        }

                        clearTimeout( SearchForm.timer );

                        if ( searchQuery !== SearchForm.lastQuery && searchQuery.length >= 3 ) {
                            SearchForm.lastQuery = searchQuery;
                            SearchForm.performSearch( searchQuery, searchResults );
                        }
                    });

                }, 2000 );
            },

            /**
             * Handles search submissions.
             */
            handleSearchFormSubmit: function () {

                $( '.search-btn' ).on( 'click', function ( e ) {

                    e.preventDefault();
                    let self = $( this );
                    let parentContainer = self.closest( '.parent-container' );
                    let searchResults = parentContainer.find( '.search-results' );
                    let searchQuery = parentContainer.find( '.search-query' ).val().trim();

                    if ( searchQuery === SearchForm.lastQuery ) {
                        return;
                    }

                    SearchForm.lastQuery = searchQuery;
                    SearchForm.performSearch( searchQuery, searchResults );
                });
            },

            /**
             * 
             * Search perform here
             */
            performSearch: function ( searchQuery, searchResultsContainer ) {
                
                if ( !searchQuery ) {
                    return;
                }

                let postTypes = $( 'input[name="post_type"]' ).val();

                if ( SearchForm.ajaxRequest ) {
                    SearchForm.ajaxRequest.abort();
                }
                
                SearchForm.ajaxRequest = $.ajax( {
                    url: WP_SEARCH.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_search_result',
                        nonce: WP_SEARCH.nonce,
                        search_query: searchQuery,
                        post_types: postTypes,
                    },

                    beforeSend: function () {
                        searchResultsContainer.html(
                            `<div class="no-search-results">
                                <h4 class="text-info">Searching...</h4>
                            </div>`
                        );
                    },

                    success: function ( response ) {
                        searchResultsContainer.empty();
                        
                        if ( response.success && response.data.search ) {
                            console.log("Search Query : " + searchQuery)
                            console.log("Posts Includes :" + postTypes)
                            console.log("Result of "+ searchQuery + " is : " + response.data.search);
                            searchResultsContainer.append( response.data.search ).change();
                        } else {
                            searchResultsContainer.append(`
                                <div class="no-search-results">
                                    <p>No result found for "${ searchQuery }"</p>
                                </div>
                            `).change();
                        }
                    },

                    error: function ( xhr, status, error ) {
                        if ( status !== 'abort' ) {
                            console.error( "AJAX Error:", xhr, status, error );
                        }
                    }
                });
            }
        };

        SearchForm.init();
    });

})( jQuery );

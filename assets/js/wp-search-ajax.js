( function ( $ ) {
    'use strict';
    $( document ).ready( function () {

        let SearchForm = {

            lastQuery: '',
            ajaxRequest: null,
            selectedIndex: -1,

            /**
             * Initialize functions
             */
            init: function () {

                this.handleSearchInput();
                this.handleSearchFormSubmit();
                this.handleSuggestionNavigation();
            },

            /**
             * Handle search input field interactions
             */
            handleSearchInput: function () {

                $( '.search-query' ).on( 'input', function () {

                    let self = $( this );
                    let parentContainer = self.closest( '.parent-container' );
                    let searchSuggestion = parentContainer.find( '.search-suggestions' );
                    let searchQuery = self.val().trim();

                    if ( searchQuery.length === 0 ) {

                        searchSuggestion.empty().hide();
                        return;
                    }

                    if ( searchQuery.length <= 2 ) {

                        searchSuggestion.empty().hide();
                        SearchForm.lastQuery = '';

                        if ( SearchForm.ajaxRequest ) {

                            SearchForm.ajaxRequest.abort();
                            SearchForm.ajaxRequest = null;
                        }

                        return;
                    }

                    if ( searchQuery !== SearchForm.lastQuery && searchQuery.length >= 3 ) {

                        SearchForm.lastQuery = searchQuery;
                        SearchForm.performSearchSuggestion( searchQuery, searchSuggestion , parentContainer );
                    }
                
                });
            },

            /**
             * Handles search form submission
             */
            handleSearchFormSubmit: function () {

                /**
                 * Handle clicking on search suggestions
                 */
                $( document ).on( 'click', '.suggestion-item', function () {

                    let parentContainer = $( this ).closest( '.parent-container' );
                    let searchQuery = parentContainer.find( '.search-query' );
                    let selectedText = $( this ).text();
                    let selectedLink = $( this ).find( 'a' ).attr( 'href' );

                    searchQuery.val( selectedText );
                    $( '.search-suggestions' ).fadeOut( 200 );

                    if ( selectedLink ) {

                        window.location.href = selectedLink;
                    }
                });

                /**
                 * Close suggestions when clicking outside
                 */
                $( document ).on( 'click', function ( e ) {

                    if ( $( e.target ).closest( '.search-container' ).length === 0 ) {
                        $( '.search-suggestions' ).fadeOut( 200 );
                    }
                });
            },

            /**
             * Perform AJAX search Suggestions
             */
            performSearchSuggestion: function ( searchQuery , searchSuggestionContainer , parentContainer ) {

                if ( !searchQuery ) return;

                let postTypes = parentContainer.find(  'input[ name="post_type" ]' ).val() ;

                if ( SearchForm.ajaxRequest ) {

                    SearchForm.ajaxRequest.abort();
                }

                SearchForm.ajaxRequest = $.ajax( {
                    url: WP_SEARCH.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_search_suggestion',
                        nonce: WP_SEARCH.nonce,
                        search_query: searchQuery,
                        post_types: postTypes,
                    },

                    beforeSend: function () {
                        searchSuggestionContainer.html(
                            `<div class="no-results">${ WP_SEARCH.search_msg }</div>` 
                        ).show();
                    },

                    success: function( response ) {

                        searchSuggestionContainer.empty();
                        SearchForm.selectedIndex = -1;

                        if ( response.success && response.data.search_results ) {

                            let suggestionsHTML = response.data.search_results
                                .map(
                                    ( item ) =>

                                        `<li class="suggestion-item"><a href="${ item.permalink }">${ item.title }</a></li>`
                                )
                                .join( '' );
                            searchSuggestionContainer.html( `<ul class="suggestions-list">${ suggestionsHTML }</ul>` ).show();

                        } else {

                            let errorMessage = response.data.message;
                            searchSuggestionContainer.html( `<div class="no-results">${ errorMessage }</div>` ).show();
                        }
                    },

                    error: function ( xhr, status, error ) {

                        if ( status !== 'abort' ) {
                            console.error( 'AJAX Error:', xhr, status, error );
                        }
                    },
                });
            },

            /**
             * Handle keyboard navigation in search suggestions
             */
            handleSuggestionNavigation: function () {

                $( '.search-query' ).on( 'keydown', function ( e ) {

                    let searchSuggestions = $( this ).closest( '.parent-container' ).find( '.search-suggestions' );
                    let items = searchSuggestions.find( '.suggestion-item' );

                    if ( items.length === 0 ) return;

                    if ( e.key === 'ArrowDown' ) {

                        e.preventDefault();
                        SearchForm.selectedIndex = ( SearchForm.selectedIndex + 1 ) % items.length;

                    } else if ( e.key === 'ArrowUp' ) {

                        e.preventDefault();
                        SearchForm.selectedIndex = ( SearchForm.selectedIndex - 1 + items.length ) % items.length;
                    } 

                    items.removeClass( 'highlighted' );

                    if ( SearchForm.selectedIndex >= 0 ) {

                        let selectedItem = $( items[ SearchForm.selectedIndex ] );
                        $( items[ SearchForm.selectedIndex ] ).addClass( 'highlighted' );
                        $( this ).val( $( items[ SearchForm.selectedIndex ] ).text() );
                        selectedItem[ 0 ].scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
                    }

                });

            },
            
        };

        SearchForm.init();
    });

})( jQuery );
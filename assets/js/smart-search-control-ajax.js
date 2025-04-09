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
                this.handleSeeMore();
                this.pagination();
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

                    url: SMART_SEARCH_CONTROL.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'smart_search_control_suggestion',
                        nonce: SMART_SEARCH_CONTROL.nonce,
                        search_query: searchQuery,
                        post_types: postTypes,
                    },

                    beforeSend: function () {

                        searchSuggestionContainer.html(
                            `<div class="no-results">${ SMART_SEARCH_CONTROL.search_msg }</div>` 
                        ).show();
                    },

                    success: function( response ) {

                        searchSuggestionContainer.empty();
                        SearchForm.selectedIndex = -1;

                        if ( response.success && response.data.search_results ) {

                            let searchResults = response.data.search_results;
                            let suggestionsHTML = searchResults
                                .slice( 0, 10 )
                                .map(( item ) => 
                                    `<li class="suggestion-item"><a href="${ item.permalink }">${ item.title }</a></li>`
                                )
                                .join( '' );

                                if ( searchResults.length > 10 ) {

                                    suggestionsHTML += `<a  href="javascript:void( 0 );" class="see-more" >${ SMART_SEARCH_CONTROL.more_msg }</a>`;

                                }

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

                    $( document ).on( 'keydown', '.ssc-default-search-input', function ( e ) {

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

                });

            },

            /**
             * Handle clicking on "See More" link
             */
            handleSeeMore: function () {

                $( document ).on( 'click', '.see-more', function ( e ) {

                    e.preventDefault();

                    let parentContainer = $( this ).closest( '.parent-container' );

                    parentContainer.find( '.ssc-search-form' ).submit();

                });

            },

            /**
             * Handle the Search with Pagination
             */
            pagination: function() {

                $( '.ssc-search-form' ).on( 'submit', function( e ) {

                    e.preventDefault();

                    var url = $( this ).attr( 'action' );
                    var searchVal = $( this ).find( 'input[name="s"]' ).val().trim();
                    var post_type = $( this ).find( 'input[name="post_type"]' ).val();
                    var css_id = $( this ).find( 'input[name="css_id"]' ).val();
                    var css_class = $( this ).find( 'input[name="css_class"]' ).val();
                    var place_holder = $( this ).find( 'input[name="place_holder"]' ).val();

                    if ( searchVal !== '' ) {

                        SearchForm.clearCookie( 'search_post_type' );
                        SearchForm.clearCookie( 'search_css_id' );
                        SearchForm.clearCookie( 'search_css_class' );
                        SearchForm.clearCookie( 'search_place_holder' );

                        var expirationDate = new Date();
                        expirationDate.setTime( expirationDate.getTime() + ( 60 * 60 * 1000 ) );

                        document.cookie = `search_post_type=${ encodeURIComponent( post_type ) }; expires=${ expirationDate.toUTCString() }; path=/`;
                        document.cookie = `search_css_id=${ encodeURIComponent( css_id ) }; expires=${ expirationDate.toUTCString() }; path=/`;
                        document.cookie = `search_css_class=${ encodeURIComponent( css_class ) }; expires=${ expirationDate.toUTCString() }; path=/`;
                        document.cookie = `search_place_holder=${ encodeURIComponent( place_holder ) }; expires=${ expirationDate.toUTCString() }; path=/`;
                        
                        var newUrl = url + '?s=' + encodeURIComponent(  searchVal );
                        
                        window.location.href = newUrl;
                    }
                });
            },

            /**
             * Function to clear the cookies
             */
            clearCookie: function ( name ) {
                document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
            }
            
        };

        SearchForm.init();
    });

})( jQuery );
( function ( $ ) {
    'use strict';
    $( document ).ready( function () {
        let SearchForm = {
            lastQuery: '',
            ajaxRequest: null,
            searchTimer: null,

            init: function () {
                this.handleSearchFormSubmit();
                this.autoSearch();
            },

            autoSearch: function () {
                $( '#search-query' ).on( 'input', function () {
                    let searchQuery = $( this ).val().trim();

                    if ( searchQuery.length <= 2 ) {
                        $( '#search-results' ).empty(); 
                        SearchForm.lastQuery = '';
                        clearTimeout( SearchForm.searchTimer );
            
                        if ( SearchForm.ajaxRequest ) {
                            SearchForm.ajaxRequest.abort();
                            SearchForm.ajaxRequest = null;
                        }
                        return;
                    }

                    clearTimeout( SearchForm.searchTimer );

                    if ( searchQuery.length === 3 ) {
                        if ( searchQuery !== SearchForm.lastQuery ) {
                            SearchForm.lastQuery = searchQuery;
                            SearchForm.performSearch( searchQuery );
                        }
                        return;
                    }

                    SearchForm.searchTimer = setTimeout(() => {
                        if ( searchQuery !== SearchForm.lastQuery && searchQuery.length > 3 ) {
                            SearchForm.lastQuery = searchQuery;
                            SearchForm.performSearch( searchQuery );
                        }
                    }, 2000 );
                });
            },

            handleSearchFormSubmit: function () {
                $( '.search-result-btn' ).on( 'click', function ( e ) {
                    e.preventDefault();
                    let searchQuery = $( '#search-query' ).val().trim();
                    if ( searchQuery === SearchForm.lastQuery ) {
                        return;
                    }

                    SearchForm.lastQuery = searchQuery;
                    SearchForm.performSearch( searchQuery );
                });
            },

            performSearch: function ( searchQuery ) {
                if ( !searchQuery ) {
                    return;
                }
                let postTypes = $( 'input[ name="post_type" ]' ).val();

                if ( SearchForm.ajaxRequest ) {
                    SearchForm.ajaxRequest.abort();
                }
                
                SearchForm.ajaxRequest = $.ajax({
                    url: WP_SEARCH.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_search_result',
                        nonce: WP_SEARCH.nonce,
                        search_query: searchQuery,
                        post_types: postTypes,
                    },

                    beforeSend: function () {
                        $( "#search-results" ).html(
                            `<div class="no-search-results">
                                <h4 class="text-info">Searching...</h4>
                            </div>`
                        );
                    },

                    success: function ( response ) {
                        let searchResultsContainer = $( "#search-results" );
                        if ( response.success && response.data ) {
                            searchResultsContainer.empty();
                            let postHTML = `
                                <div id="search-results-container-row">
                                    <div class="search-results-header">
                                        <h4>Search Results for : 
                                        <span class="text-info">${ searchQuery }</span>
                                        </h4>
                                    </div>
                                    <hr>
                                    <div id="wp-search-results-row">
                            `;
                            response.data.search.forEach( post => {
                                postHTML += `
                                <div class="wp-search-post-row">
                                        <img src="${ post.thumbnail }" alt="${ post.title }" class="wp-search-img-row">
                                        <h2><a href="${ post.permalink }">${ post.title }</a></h2>
                                        <p class="search-content">${ post.content }</p>
                                        <hr>
                                        </div>
                                `;
                            });

                            postHTML += '</div></div>';
                            searchResultsContainer.html( postHTML );
                        } else {
                            searchResultsContainer.html(
                                `<div class="no-search-results">
                                    <h4>No results found for : 
                                    <span class="text-info">${ searchQuery }</span>
                                    </h4>
                                    </div>
                                    <hr>`
                            );
                        }
                    },
                    error: function ( xhr, status, error ) {
                        if (status !== 'abort') {
                            console.error("AJAX Error:", xhr, status, error);
                        }
                    }
                });
            }
        };

        SearchForm.init();
    });
})( jQuery );

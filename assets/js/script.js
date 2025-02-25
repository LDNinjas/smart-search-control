( function( $ ) { 'use strict';
    $( document ).ready( function() {
        /**
         * Define the object and its methods
         */
        let AdvancedFilters = {
            init: function() {
                this.toggleAdvancedFilters();
                this.addFilterRow();
            },

            /**
             * Toggle Advanced Filters Visibility
             */
            toggleAdvancedFilters: function() {
                $( '#advanced-filters-toggle' ).on( 'click', function() {
                    const advancedFilters = $( '.advanced-filters' );
                    advancedFilters.css( 'display', 
                        advancedFilters.css( 'display') === 'none' || advancedFilters.css( 'display' ) === '' 
                        ? 'block' 
                        : 'none'
                    );
                });
            },

            /**
             * Add a New Filter Row
             */
            addFilterRow: function() {
                $( '.add-filter-row' ).on( 'click', function() {
                    const filterRow = $( '<div>', { class: 'filter-row' } ).html(`
                        <div class="filter-row">
                            <input type="text" placeholder="Filter Name" class="form-control">
                            <button type="button" class="remove-filter">Remove</button>
                        </div>
                    `);

                    $( '#additional-filters' ).append( filterRow );

                    /**
                     * Add Event Listener to Remove Button
                     */
                    filterRow.find( '.remove-filter' ).on( 'click', function () {
                        filterRow.remove();
                    });
                });
            }
        };
        AdvancedFilters.init();
    });
})( jQuery );

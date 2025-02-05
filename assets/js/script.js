/**
 * Toggle Advanced Filters Visibility
 */

document.getElementById( "advanced-filters-toggle" )
    .addEventListener( "click", function () {
        const advancedFilters = document.querySelector( ".advanced-filters" );
        advancedFilters.style.display =
            advancedFilters.style.display === "none" ||
                advancedFilters.style.display === ""
                ? "block"
                : "none";
    });

    /**
     * Add a New Filter Row
     */

document.querySelector( ".add-filter-row" )
    .addEventListener( "click", function () {
        const filterRow = document.createElement( "div" );
        filterRow.classList.add( "filter-row" );
        filterRow.innerHTML = `
        <div class="filter-row">
            <input type="text" placeholder="Filter Name" class="form-control">
            <button type="button" class="remove-filter">Remove</button>
        </div>
        `;
        document.getElementById( "additional-filters" ).appendChild( filterRow );
        
        /**
         * Add Event Listener to Remove Button
         */
        
        filterRow
            .querySelector( ".remove-filter" )
            .addEventListener( "click", function () {
                filterRow.remove();
            });
    });

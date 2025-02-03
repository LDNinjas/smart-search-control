WP-Search Plugin Documentation

Shortcode Usage

Insert the shortcode into any page or post to display the search bar.

Basic Shortcode

[wp_search_bar]

This shortcode will display a search bar with an "Advanced Filters" button.

Shortcode Attributes

Customize the search bar using the following shortcode attributes:

Placeholder TextSet a custom placeholder in the search bar:

[wp_search_bar placeholder="Custom Placeholder"]

Custom CSS ClassesAdd your own CSS classes to style the search bar:

[wp_search_bar class="custom-class"]

Post Type FilteringRestrict the search to specific post types (e.g., pages):

[wp_search_bar type="page"]

Advanced Filters

Clicking the "Advanced Filters" button allows users to:

Select Post TypesChoose which post types to include in the search.

WooCommerce IntegrationIf WooCommerce is installed, a checkbox will appear in the advanced filters, enabling users to include product variations in their search.

Search Results Template

Create a template to display all search results. Ensure that the template is styled to match the overall design of your website for a seamless user experience.

Empty Search Handling

A message box will appear if the user attempts to search with an empty search bar. Customize this message to guide users appropriately.

Example Implementations

Basic Search Bar with Custom Placeholder:

[wp_search_bar placeholder="Search for articles..."]

Search Bar with Custom Class and Post Type Restriction:

[wp_search_bar class="my-custom-style" type="page,post"]

Full-Featured Search with WooCommerce Variations:

[wp_search_bar placeholder="Search products and posts" type="product"]


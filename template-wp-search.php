<?php
/**
 * Template Name: Search Results
 */
get_header(); // Include the header
?>

<div id="search-results-container">
    <div class="d-flex justify-content-center">

        <h1>Search Results for: <span class="text-info">
            <?php echo get_search_query(); ?>
        </span>
    </h1>
    </div>
    <div id="wp-search-results">
        <?php
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                echo '<div class="wp-search-post">';
                echo '<img src="' . get_the_post_thumbnail_url() . '" alt="' . get_the_title() . '" class="wp-search-img">';
                echo '<div class ="wp-search-post-content">';
                echo '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
                echo '<p>' . get_the_excerpt() . '</p>';
                echo '</div> </div>';
            }
        } else {
            echo '
            <div class = "d-flex justify-content-center"> 
            <h2 >No posts found.</h2>
            </div>
            ';
        }
        ?>
    
</div>

<?php
get_footer(); // Include the footer
?>
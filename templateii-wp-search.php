<?php
/**
 * Template Name: Search Results
 */
get_header(); // Include the header
?>

<div id="search-results-containerii">
    <div class="search-results-header">

        <h1>Search Results for: <span class="text-info">
                <?php echo get_search_query(); ?>
            </span>
        </h1>
    </div>
    <div id="wp-search-resultsii">
        <?php
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                ?>
                <hr>
                <div class="wp-search-postii">
                    <img src="<?= get_the_post_thumbnail_url() ?>" alt=" <?= get_the_title() ?>" class="wp-search-imgii">
                    <h2><a href="<?= get_permalink() ?>"> <?= get_the_title() ?> </a></h2>
                    <p class="search-content"><?= get_the_excerpt() ?></p>
                </div>
                <?php
            }
        } else {
            ?>
        </div>
        <div class="msg-container">
            <h2>
                <?php echo __('No post found'); ?>
            </h2>
        </div>
        <?php
        }
        ?>
</div>
<?php
get_footer(); // Include the footer
?>
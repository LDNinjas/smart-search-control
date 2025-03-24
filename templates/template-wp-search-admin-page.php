<?php

if (!defined('ABSPATH'))
    exit;

global $wpdb;
$table_name = $wpdb->prefix . 'search_parameters';

/**
 * get all the data from the table
 */
$search_entries = $wpdb->get_results("SELECT * FROM $table_name");

$post_types = get_post_types(['public' => true], 'objects');

?>
<div class="wrap">
    <h1><?php esc_html_e( 'WP Search Settings' ); ?></h1>
    <p><?php esc_html_e( 'Customize the search bar settings to enhance your website’s search functionality.' ); ?>
    </p>

    <div class="message-box">
    </div>
    <button id="openModal" class="button button-primary new-rec-btn">Add New Search</button>
    <table class="wp-list-table widefat fixed striped search-table">
        <thead>
            <tr>
                <th>Shortcode</th>
                <th>Place Holder</th>
                <th>Class</th>
                <th>Type (Include/Exclude/All)</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($search_entries): ?>
                <?php foreach ($search_entries as $entry): ?>
                    <tr>
                        <td>[wp_search_bar id="<?php echo esc_html($entry->id); ?>"]</td>
                        <td><?php echo esc_html($entry->place_holder); ?></td>
                        <td><?php echo esc_html($entry->class); ?></td>
                        <td><?php echo esc_html($entry->type); ?></td>
                        <td>
                            <a href="javascript:void( 0 );"
                                onclick='AdminSearchSetting.viewSetting( <?php echo json_encode($entry); ?> )'
                                class="button button-primary">View</a>
                            <a href="javascript:void( 0 );"
                                onclick='AdminSearchSetting.editSetting( <?php echo json_encode($entry); ?> )'
                                class="button button-warning">Edit</a>
                            <a href="#" class="button button-danger delete-setting"
                                data-id="<?php echo $entry->id; ?>">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5"><?php echo __('No search parameters found.', 'wp-search') ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal Structure -->
<div id="searchModal" class="modal-overlay">
    <div class="modal-content">
        <h2>Add New Search</h2>
        <form id="searchForm" method="post">
            <div class="form-group">
                <label for="place_holder">Place Holder</label>
                <input type="text" id="place_holder" name="place_holder">
            </div>

            <div class="form-group">
                <label for="class">Class</label>
                <input type="text" id="class" name="class">
            </div>

            <div class="form-group">
                <label>Type</label>
                <input type="radio" name="type" value="include"> Include
                <input type="radio" name="type" value="exclude"> Exclude
            </div>

            <div class="form-group">
                <label>Post Type</label>
                <div class="post-type-options">
                    <?php foreach ($post_types as $key => $post_type): ?>
                        <label>
                            <input type="checkbox" name="post_type[]" value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($post_type->label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-actions">
                <input type="submit" name="submit_search" class="button button-primary" value="Add Search">
                <button id="closeModal" class="button">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!--View Modal Structure -->
<div id="viewModal" class="modal-overlay">
    <div class="modal-content">
        <h2 class="view-model-title">View Search - ID <span id="view-model-id"></span></h2>
        <form id="searchForm" method="post">
            <div class="form-group">
                <label for="place_holder">Place Holder</label>
                <input type="text" id="view-model-place_holder" name="place_holder" readonly>
            </div>

            <div class="form-group">
                <label for="class">Class</label>
                <input type="text" id="view-model-class" name="class" readonly>
            </div>

            <div class="form-group">
                <label>Type</label>
                <input type="text" id="view-model-type" name="type" readonly>
            </div>

            <div class="form-group">
                <label>Post Type</label>
                <div class="view-model-post-type-options">
                    <div class="post-type-display">
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="button" id="closeViewModal">Close</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal Structure -->
<div id="editSearchModal" class="modal-overlay">
    <div class="modal-content">
        <h2>Edit Search - ID <span class="editModelId"></span></h2>
        <form id="editSearchForm" method="post">
            <div class="form-group">
                <label for="edit-place_holder">Place Holder</label>
                <input type="text" id="edit-place_holder" name="place_holder">
            </div>

            <div class="form-group">
                <label for="edit-class">Class</label>
                <input type="text" id="edit-class" name="class">
            </div>

            <div class="form-group">
                <label>Type</label>
                <input type="radio" name="edit-type" value="include"> Include
                <input type="radio" name="edit-type" value="exclude"> Exclude
            </div>

            <div class="form-group">
                <label>Post Type</label>
                <div class="post-type-options">
                    <?php foreach ($post_types as $key => $post_type): ?>
                        <label>
                            <input type="checkbox" name="edit-post_type[]" value="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($post_type->label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-actions">
                <input type="submit" name="submit_edit_search" class="button button-primary"
                    value="Update Search Settings">
                <button id="closeEditModal" class="button">Cancel</button>
            </div>
        </form>
    </div>
</div>
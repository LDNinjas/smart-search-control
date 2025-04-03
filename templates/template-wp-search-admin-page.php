<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'search_parameters';

$items_per_page = 12;
$page = isset( $_GET['paged'] ) ? absint( $_GET['paged']) : 1;
$offset = ( $page - 1 ) * $items_per_page;

$total_items = $wpdb->get_var( "SELECT COUNT( * ) FROM $table_name" );
$total_pages = ceil( $total_items / $items_per_page );

$search_entries = $wpdb->get_results( $wpdb->prepare( "SELECT id, data FROM $table_name LIMIT %d OFFSET %d", $items_per_page, $offset ) );

$post_types = get_post_types( ['public' => true], 'objects' );

$admin_notice = WP_Search_Admin_Settings::instance()->get_admin_notice();
?>

<div class="wrap">
    <?php echo  $admin_notice ?>
    <div class="page-header">
    <p class="page-title"><?php echo __( 'WP Search' ); ?></p>
    <button id="openModal" class="new-rec-btn"><?php echo __( 'Add New Search' ); ?></button>
</div>
    <p><?php echo __( 'Customize the search bar settings to enhance your website’s search functionality.' ); ?></p>

    <?php if ( empty( $admin_notice ) ){ ?>

        <table class="search-table">
            <thead>
                <tr>
                    <th><?php echo __( 'Shortcode' ); ?></th>
                    <th><?php echo __( 'Place Holder' ); ?></th>
                    <th><?php echo __( 'CSS ID' ); ?></th>
                    <th><?php echo __( 'CSS Class' ); ?></th>
                    <th><?php echo __( 'Action' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $search_entries ): ?>
                    <?php foreach ( $search_entries as $entry ): 
                    
                        $data = json_decode( $entry->data ); ?>
                        
                        <tr class="search-table-data">
                            <strong>
                                <td>[wp_search_bar id="<?php echo esc_html( $entry->id ); ?>"]</td>
                            </strong>
                            <td><?php echo esc_html( $data->place_holder ); ?></td>
                            <td><?php echo esc_html( $data->css_id ); ?></td>
                            <td><?php echo esc_html( implode(', ', explode(' ', $data->class)) ); ?></td>
                            <td>
                                <a href="#" data-entry='<?= json_encode( $entry ?: new stdClass() ); ?>' class="button edit-setting"><?php echo __( 'Edit' ); ?></a>
                                <a href="#" class="button delete-setting" data-id="<?php echo $entry->id; ?>"><?php echo __( 'Delete' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-search-parm"><?php echo __( 'No search parameters found.' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        <tfoot>
                <tr>
                    <th><?php echo __( 'Shortcode' ); ?></th>
                    <th><?php echo __( 'Place Holder' ); ?></th>
                    <th><?php echo __( 'CSS ID' ); ?></th>
                    <th><?php echo __( 'CSS Class' ); ?></th>
                    <th><?php echo __( 'Action' ); ?></th>
                </tr>
            </tfoot>
        </table>

    <?php }?>

    <!-- Pagination -->
    <div class="tablenav">

        <div class="tablenav-pages pagination">

            <?php if ( $total_pages > 1 ) {

                $prev_page = max( 1, $page - 1 );
                $next_page = min( $total_pages, $page + 1 );
            ?>

                <!-- Previous Button -->
                <?php if ( $page > 1 ) { ?>
                    <a class="prev page-numbers" href="<?php echo admin_url( 'admin.php?page=wp_search_settings&paged=' . $prev_page ); ?>">« Prev</a>
                <?php } ?>

                <!-- Numbered Pagination -->
                <?php for ( $i = 1; $i <= $total_pages; $i++ ) { ?>

                    <a class="page-numbers <?php echo ( $i == $page ) ? 'current' : ''; ?>" 
                    href="<?php echo admin_url( 'admin.php?page=wp_search_settings&paged=' . $i ); ?>">
                    <?php echo $i; ?>
                    </a>

                <?php } ?>

                <!-- Next Button -->
                <?php if ( $page < $total_pages ) { ?>
                    <a class="next page-numbers" href="<?php echo admin_url( 'admin.php?page=wp_search_settings&paged=' . $next_page ); ?>">Next »</a>
                <?php } ?>

            <?php } ?>

        </div>
    </div>

</div>

<!-- Modal Structure -->
<div id="searchModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><?php echo __( 'WP Search Settings' ); ?></h2>
            <button class="closeModal modal-header-close">×</button>
        </div>
        <hr>

        <?php echo $admin_notice; ?>

        <div class="modal-body">
            
            <!-- Short code display -->
            <div class="shortcode-container">
                <p class="short-code-copy">
                    <span id="copy-code">
                        <span class="dashicons dashicons-clipboard"></span>
                        <span class="copy-msg" ><?php echo __( 'Copied!' )?></span>
                    </span> 
                    <span id="shortcode-text">[wp_search_bar id="<span class="code-id">123</span>"]</span>
                </p>
            </div>

            <form id="searchForm" method="post">

                <div class="form-group">
                    <label for="place_holder"><?php echo __( 'Placeholder' ); ?></label>
                    <input type="text" id="place_holder" name="place_holder">
                    <span class="inputs-desc"><?= __( 'This text will appear inside the search field as a hint before the user types.' )?></span>
                </div>

                <div class="advance-container" id="advance-toggle">
                    <p><?php echo __( 'Advanced Settings' ); ?></p>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </div>

                <div class="advance-container-toggle" id="advance-content">
                    <div class="form-group">
                        <label for="id"><?php echo __( 'CSS ID' ); ?></label>
                        <input type="text" id="id" name="id">
                        <span class="inputs-desc"><?= __( 'Optional: Enter a unique ID for this element (for CSS or JavaScript).' )?></span>
                    </div>
                    <div class="form-group">
                        <label for="class"><?php echo __( 'CSS Class' ); ?></label>
                        <input type="text" id="class" name="class">
                        <span class="inputs-desc"><?= __( 'Optional: Enter custom CSS classes (separated by spaces).' )?></span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-container">
                        <label class="post-type-label"><?php echo __( 'Post Types' ); ?></label>
                        <label class="select-all-label">
                            <input type="checkbox" id="select-all">
                            <span><?php echo __( 'Select All' ); ?></span>
                        </label>
                    </div>
                    <div class="post-type-options">
                        <?php foreach ( $post_types as $key => $post_type ): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="post_type[]" value="<?php echo esc_attr( $key ); ?>" class="custom-checkbox">
                                <?php echo esc_html( $post_type->label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr>

                <div class="modal-actions">
                    <button type="button" class="closeModal button"><?php echo __( 'Cancel' ); ?></button>
                    <input type="submit" name="submit_search" class="button button-primary modal-btn" value="<?php echo __( 'Save' ); ?>">
                </div>
            </form>
        </div>
    </div>
</div>
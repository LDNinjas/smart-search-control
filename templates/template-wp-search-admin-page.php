<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'search_parameters';

$items_per_page = 2;
$page = isset( $_GET['paged'] ) ? absint( $_GET['paged']) : 1;
$offset = ( $page - 1 ) * $items_per_page;

$total_items = $wpdb->get_var( "SELECT COUNT( * ) FROM $table_name" );
$total_pages = ceil( $total_items / $items_per_page );

$search_entries = $wpdb->get_results( $wpdb->prepare( "SELECT id, place_holder, class, type, post_type FROM $table_name LIMIT %d OFFSET %d", $items_per_page, $offset ) );

$post_types = get_post_types( ['public' => true], 'objects' );

$admin_notice = WP_Search_Admin_Settings::instance()->get_admin_notice();
?>

<div class="wrap">
    <?php echo  $admin_notice ?>
    <div class="message-box"></div>
    <div class="page-header">
        <h1><?php echo __( 'WP Search Settings' ); ?></h1>
        <button id="openModal" class="new-rec-btn"><?php echo __( 'Add New Search' ); ?></button>
    </div>
    <p><?php echo __( 'Customize the search bar settings to enhance your website’s search functionality.' ); ?></p>

    <?php if ( empty( $admin_notice ) ){ ?>

        <table class="search-table">
            <thead>
                <tr>
                    <th><?php echo __( 'Shortcode' ); ?></th>
                    <th><?php echo __( 'Place Holder' ); ?></th>
                    <th><?php echo __( 'Class' ); ?></th>
                    <th><?php echo __( 'Action' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $search_entries ): ?>
                    <?php foreach ( $search_entries as $entry ): ?>
                        <tr>
                            <td>[wp_search_bar id="<?php echo esc_html( $entry->id ); ?>"]</td>
                            <td><?php echo esc_html( $entry->place_holder ); ?></td>
                            <td><?php echo esc_html( $entry->class ); ?></td>
                            <td>
                                <a href="#" data-entry='<?= json_encode( $entry ?: new stdClass() ); ?>' class="button button-warning edit-setting"><?php echo __( 'Edit' ); ?></a>
                                <a href="#" class="button button-danger delete-setting" data-id="<?php echo $entry->id; ?>"><?php echo __( 'Delete' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="no-search-parm"><?php echo __( 'No search parameters found.' ); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        <tfoot>
                <tr>
                    <th><?php echo __( 'Shortcode' ); ?></th>
                    <th><?php echo __( 'Place Holder' ); ?></th>
                    <th><?php echo __( 'Class' ); ?></th>
                    <th><?php echo __( 'Action' ); ?></th>
                </tr>
            </tfoot>
        </table>

    <?php }?>

    <!-- Pagination -->
    <div class="tablenav">
        <div class="tablenav-pages pagination">

            <?php if ( $total_pages > 1 ){ 

                for ( $i = 1; $i <= $total_pages; $i++ ){ ?>
                    <a class="page-numbers <?php echo ( $i == $page ) ? 'current' : ''; ?>" 
                    href="<?php echo admin_url( 'admin.php?page=wp_search_settings&paged=' . $i ); ?>">
                    <?php echo $i; ?> 
                    </a>
                    
                    <?php 
                } 
            } ?>

        </div>
    </div>
</div>

<!-- Modal Structure -->
<div id="searchModal" class="modal-overlay">
    <div class="modal-content">
        <h2 class="model-title"></h2>

        <?php echo  $admin_notice ?>

        <form id="searchForm" method="post">
            <div class="form-group">
                <label for="place_holder"><?php echo __( 'Place Holder' ) ?></label>
                <input type="text" id="place_holder" name="place_holder">
            </div>

            <div class="form-group">
                <label for="class"><?php echo __( 'Place Holder' ) ?></label>
                <input type="text" id="class" name="class">
            </div>

            <div class="form-group">
                <label><?php echo __( 'Type' ) ?></label>
                <input type="radio" name="type" value="include"> <?php echo __( 'Include' ) ?>
                <input type="radio" name="type" value="exclude"> <?php echo __( 'Exclude' ) ?>
            </div>

            <div class="form-group">
                <label><?php echo __( 'Post Types' ) ?></label>
                <div class="post-type-options">
                    <?php foreach ( $post_types as $key => $post_type ): ?>
                        <label>
                            <input type="checkbox" name="post_type[]" value="<?php echo esc_attr( $key ); ?>">
                            <?php echo esc_html( $post_type->label ); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-actions">
                <input type="submit" name="submit_search" class="button button-primary model-btn" value="">
                <button id="closeModal" class="button"><?php echo __( 'Cancel' ) ?></button>
            </div>
        </form>
    </div>
</div>

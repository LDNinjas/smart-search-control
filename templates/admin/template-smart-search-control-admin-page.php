<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

$admin_notice = Smart_Search_Control_Admin_Menu::instance()->get_admin_notice();
$table_name = $wpdb->prefix . 'smart_search_control_parameters';

$items_per_page = 10;
$page = isset( $_GET[ 'paged' ] ) ? absint( $_GET[ 'paged' ] ) : 1;
$offset = ( $page - 1 ) * $items_per_page;

$search_entries = [];

$total_pages = 1 ;

if ( empty( $admin_notice ) ){

    $total_items = $wpdb->get_var( "SELECT COUNT( id ) FROM $table_name" );
    $total_pages = ceil( $total_items / $items_per_page );

    $search_entries = $wpdb->get_results( $wpdb->prepare( "SELECT id, data FROM $table_name LIMIT %d OFFSET %d", $items_per_page, $offset ) );

    $post_types = get_post_types( [ 'public' => true ], 'objects' );

}
?>

<div class="wrap">
    <?php echo  $admin_notice ?>

    <div class="page-header">
    <p class="page-title"><?php echo __( 'Smart Search Control' , 'smart-search-control' ); ?></p>
    <a href="#" id="openModal" class="new-rec-btn"><?php echo __( 'Add New Search', 'smart-search-control' ); ?></a>

</div>

    <p><?php echo __( 'Customize the search bar settings to enhance your website’s search functionality.', 'smart-search-control' ); ?></p>

        <table class="search-table">
            <thead>
                <tr>
                    <th><?php echo __( 'Shortcode' , 'smart-search-control' ); ?></th>
                    <th><?php echo __( 'Place Holder' , 'smart-search-control' ); ?></th>
                    <th><?php echo __( 'CSS ID' , 'smart-search-control' ); ?></th>
                    <th><?php echo __( 'CSS Class' , 'smart-search-control' ); ?></th>
                    <th><?php echo __( 'Action' , 'smart-search-control' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( isset( $search_entries ) && is_array( $search_entries ) && $search_entries ) { ?>
                    <?php foreach ( $search_entries as $entry ){
                    
                        $data = json_decode( $entry->data ); ?>
                        
                        <tr class="search-table-data">
                            <strong>
                                <td>[smart_search_control id="<?php echo esc_html( $entry->id ); ?>"]</td>
                            </strong>
                            <td><?php echo esc_html( $data->place_holder ); ?></td>
                            <td><?php echo esc_html( $data->css_id ); ?></td>
                            <td><?php echo esc_html( implode( ', ', explode( ' ', $data->class ) ) ); ?></td>
                            <td>
                                <a href="#" data-entry='<?= json_encode( $entry ?: new stdClass() ); ?>' class="button edit-setting"><?php echo __( 'Edit' , 'smart-search-control' ); ?></a>
                                <a href="#" class="button delete-setting" data-id="<?php echo $entry->id; ?>"><?php echo __( 'Delete' , 'smart-search-control'); ?></a>
                            </td>
                        </tr>
                    <?php
                    } 
                    
                }
                else { ?>
                    <tr>
                        <td colspan="5" class="no-search-parm"><?php echo __( 'No search parameters found.', 'smart-search-control' ); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        <tfoot>
                <tr>
                    <th><?php echo __( 'Shortcode' , 'smart-search-control' ); ?></th>
                    <th><?php echo __( 'Place Holder' , 'smart-search-control' ); ?></th>
                    <th><?php echo __( 'CSS ID' , 'smart-search-control' ); ?></th>
                    <th><?php echo __( 'CSS Class' , 'smart-search-control' ); ?></th>
                    <th><?php echo __( 'Action' , 'smart-search-control' ); ?></th>
                </tr>
            </tfoot>
        </table>

    

    <!-- Pagination -->
    <div class="tablenav">

        <div class="tablenav-pages pagination">

            <?php if ( $total_pages > 1 ) {

                $prev_page = max( 1, $page - 1 );
                $next_page = min( $total_pages, $page + 1 );
                ?>

                <!-- Previous Button -->
                <?php if ( $page > 1 ) { ?>
                    <a class="prev page-numbers" href="<?php echo admin_url( 'admin.php?page=smart_search_control&paged=' . $prev_page ); ?>"><?php echo __( '« Prev' , 'smart-search-control' ); ?></a>
                <?php } ?>

                <!-- Numbered Pagination -->
                <?php for ( $i = 1; $i <= $total_pages; $i++ ) { ?>

                    <a class="page-numbers <?php echo ( $i == $page ) ? 'current' : ''; ?>" 
                    href="<?php echo admin_url( 'admin.php?page=smart_search_control&paged=' . $i ); ?>">
                    <?php echo $i; ?>
                    </a>

                <?php } ?>

                <!-- Next Button -->
                <?php if ( $page < $total_pages ) { ?>
                    <a class="next page-numbers" href="<?php echo admin_url( 'admin.php?page=smart_search_control&paged=' . $next_page ); ?>"><?php echo __( 'Next »' , 'smart-search-control' ); ?></a>
                <?php } ?>
            <?php } ?>

        </div>
    </div>

</div>

<!-- Modal Structure -->
<div id="searchModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title"><?php echo __( 'Smart Search Control Settings' , 'smart-search-control' ); ?></h2>
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
                        <span class="copy-msg" ><?php echo __( 'Copied!' , 'smart-search-control' ); ?></span>
                    </span> 
                    <span id="shortcode-text">[smart_search_control id="<span class="code-id"></span>"]</span>
                </p>
            </div>

            <form id="searchForm" method="post">

                <div class="form-group">
                    <label for="place_holder"><?php echo __( 'Placeholder' , 'smart-search-control' ); ?></label>
                    <input type="text" id="place_holder" name="place_holder" value="<?php  echo esc_attr( __( 'Search...' , 'smart-search-control' ) ) ?>">
                    <span class="inputs-desc"><?= __( 'This text will appear inside the search field as a hint before the user types.' , 'smart-search-control' ); ?></span>
                </div>

                <div class="advance-container" id="advance-toggle">
                    <p><?php echo __( 'Advanced Settings' , 'smart-search-control' ); ?></p>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </div>

                <div class="advance-container-toggle" id="advance-content">
                    <div class="form-group">
                        <label for="id"><?php echo __( 'CSS ID' , 'smart-search-control' ); ?></label>
                        <input type="text" id="id" name="id">
                        <span class="inputs-desc"><?= __( 'Optional: Enter a unique ID for this element (for CSS or JavaScript).' , 'smart-search-control' )?></span>
                    </div>
                    <div class="form-group">
                        <label for="class"><?php echo __( 'CSS Class' , 'smart-search-control' ); ?></label>
                        <input type="text" id="class" name="class">
                        <span class="inputs-desc"><?= __( 'Optional: Enter custom CSS classes (separated by spaces).' , 'smart-search-control' )?></span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-container">
                        <label class="post-type-label"><?php echo __( 'Post Types' , 'smart-search-control' ); ?></label>
                        <label class="select-all-label">
                            <input type="checkbox" id="select-all">
                            <span><?php echo __( 'Select All' , 'smart-search-control' ); ?></span>
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
                    <button type="button" class="closeModal button"><?php echo __( 'Cancel' , 'smart-search-control' ); ?></button>
                    <input type="submit" name="submit_search" class="button button-primary modal-btn" value="<?php echo __( 'Save' , 'smart-search-control' ); ?>">
                </div>
            </form>
        </div>
    </div>
</div>
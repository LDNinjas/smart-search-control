<?php
if ( !defined( 'ABSPATH' ) ) {
    exit;
}

$admin_notice = Smart_Search_Control_Admin_Menu::instance()->get_admin_notice();

/**
 * all pages
 */ 
$pages = get_pages();

/**
 * Get selected result page ID from options
 */
$selected_page = get_option( 'smart_search_control_result_page', '' );

$success_notice = '';
if ( isset( $_GET['ssc_saved'] ) && $_GET['ssc_saved'] === 'true' ) {
    $success_notice = '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'smart-search-control' ) . '</p></div>';
}

?>

<div class="wrap">

    <?php echo $admin_notice; ?>
    <?php echo $success_notice; ?>
    <h2 class="short-code-result_page-title"><?php echo __( 'Result Page Settings', 'smart-search-control' ); ?></h2>

    <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
        <div class="short-code-result-row">

                <h3 class="shortcode-label"><?php echo __( 'Result page', 'smart-search-control' ); ?></h3>

            <div class="select-page-container">
                <select name="selected_page" id="selected_page">
                    <option value=""><?php echo __( 'Select result page', 'smart-search-control' ); ?></option>
                    <?php
                        foreach ( $pages as $page ) {
                            $selected = ( $selected_page == $page->ID ) ? 'selected' : '';
                            echo '<option value="' . esc_attr( $page->ID ) . '" ' . $selected . '>' . esc_html( $page->post_title ) . '(ID: ' . esc_html( $page->ID ) . ')' . '</option>';
                        }
                    ?>
                </select>
                <p class="short-code-result-desc">
                <?php echo __( 'Page where search results will be displayed', 'smart-search-control' ); ?></p>
            </div>
        </div>
        <input type="hidden" name="action" value="ssc_save_action">
        <?php wp_nonce_field( 'smart_search_control_save_page', 'smart_search_control_nonce' ); ?>
        <div class="smart_search_result_btn">
            <input type="submit" value="<?php echo __( 'Save changes', 'smart-search-control' ); ?>"
                class="button button-primary" />
        </div>

    </form>

</div>
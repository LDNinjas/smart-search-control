<?php
/**
 *  Default Img set
 */
class Smart_Search_Control_Default_Img {

    /**
     * Holds the class instance.
     * @var Smart_Search_Control_Default_Img|null
     */
    private static $instance = null;

    /**
     * Returns the singleton instance.
     * @return Smart_Search_Control_Default_Img
     */
    public static function instance() {
        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Smart_Search_Control_Default_Img ) ) {
            self::$instance = new self();
            self::$instance->ssc_upload_default_image();
        }
        return self::$instance;
    }

    /**
     * Set The Default Image
     */
    public function ssc_upload_default_image() {

        if ( get_option( 'my_plugin_default_image_uploaded' ) ) {
            return;
        }

        $image_path =  SSC_ASSETS_PATH . 'default-img/no-feature-image.jpg';

        if ( !file_exists( $image_path ) ) {
            return;
        }

        $upload_dir = wp_upload_dir();
        $new_file = $upload_dir[ 'path' ] . '/' . basename( $image_path );

        if ( !copy( $image_path, $new_file ) ) {
            return;
        }

        $attachment = array(
            'guid'           => $upload_dir[ 'url' ] . '/' . basename( $image_path ),
            'post_mime_type' => mime_content_type( $new_file ),
            'post_title'     => sanitize_file_name( basename( $image_path ) ),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment( $attachment, $new_file );
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata( $attach_id, $new_file );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        update_option( 'my_plugin_default_image_id', $attach_id );    
        update_option( 'my_plugin_default_image_uploaded', true );
    }
}

Smart_Search_Control_Default_Img::instance();
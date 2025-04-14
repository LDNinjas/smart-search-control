<?php
/**
 * Admin Setting SubMenu
 */

if ( !defined( 'ABSPATH' ) ) exit;
class Smart_Search_Control_Admin_Submenu_Setting {

    /**
     * instance
     * @var 
     */
    private static $instance = null;

    /**
     * screen_id
     */
    private $screen_id = '';

    /**
     * Define instance
     * @return  Smart_Search_Control_Admin_Submenu_Setting
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof  Smart_Search_Control_Admin_Submenu_Setting ) ) {
            self::$instance = new self();
            self::$instance->hooks();
        }
        return self::$instance;
    }

    /**
     * hooks
     */
    private function hooks() {

        add_action( 'admin_menu', [ $this, 'add_admin_submenu_page' ] );
        add_action( 'current_screen', [ $this, 'setup_screen_id' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'smart_search_control_admin_assets' ] );
        
    }

    /**
     * Setup screen ID when the current screen is available.
     */
    public function setup_screen_id() {

        $screen = get_current_screen();

        if ( $screen ) {

            $this->screen_id = $screen->id;
        }

    }

    /**
     * Check if the current screen is the admin settings page.
     */
    public function is_admin_settings_page() {

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return isset( $_POST[ 'action' ] ) && strpos( $_POST[ 'action' ], 'smart_search_control_setting' ) !== false;
        }
    
        $screen = get_current_screen();
        return $screen && $screen->id === $this->screen_id;
    }
    
    /**
     * add_admin_submenu_page
     */
    public function add_admin_submenu_page() {

        add_submenu_page(

            'smart_search_control',
            __( 'Settings', 'smart-search-control' ),
            __( 'Settings', 'smart-search-control' ),
            'manage_options',
            'smart_search_control_settings',
            [ $this, 'render_admin_submenu_page']

        );
    }

    /**
     * render_admin_submenu_page
     */
    public function render_admin_submenu_page() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to access this page.' , 'smart-search-control' ) );
        }

        $template_path = SSC_TEMPLATES_DIR . 'admin/template-smart-search-control-admin-setting-page.php';
        include $template_path;

    }

    /**
     * wp_search_admin_assets
     */
    public function smart_search_control_admin_assets() {

        if ( !$this->is_admin_settings_page() ) {
            return;
        }

        wp_enqueue_style(
            'smart-search-control-admin-style',
            SSC_ASSETS_URL . 'css/smart-search-control-admin-style.css'
        );
        wp_enqueue_script( 'smart-search-control-admin-js', SSC_ASSETS_URL . 'js/smart-search-control-admin.js', [ 'jquery' ], SSC_VERSION, true );
    }
}
    
Smart_Search_Control_Admin_Submenu_Setting::instance();
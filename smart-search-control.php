<?php
/**
 * Plugin Name: Smart Search Control
 * Plugin URI: https://ldninjas.com/smartsearch-control/
 * Description: A simple search plugin for WordPress
 * Version: 1.0.0
 * Author: LDNinjas
 * Author URI: https://ldninjas.com/
 * Text Domain: smart-search-control
 */

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin class -  
 */
class LD_Smart_Search_Control {

    /**
     * Object Variable
     */
    private static $instance = null;
    
    /**
     * Constructor 
     */
    public static function instance() {

        if ( is_null( self::$instance ) && !( self::$instance instanceof LD_Smart_Search_Control ) ) {

            self::$instance = new self;
            self::$instance->constants_setup(); 
            self::$instance->includes_files();
            self::$instance->enable_freemius();
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     *  create a function enable freemius 
     */
    public function enable_freemius() {

        if ( ! function_exists( 'ssc_fs' ) ) {
            // Create a helper function for easy SDK access.
            function ssc_fs() {
                global $ssc_fs;

                if ( ! isset( $ssc_fs ) ) {
                    // Activate multisite network integration.
                    if ( ! defined( 'WP_FS__PRODUCT_19073_MULTISITE' ) ) {
                        define( 'WP_FS__PRODUCT_19073_MULTISITE', true );
                    }

                    // Include Freemius SDK.
                    require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                    $ssc_fs = fs_dynamic_init( array(
                        'id'                  => '19073',
                        'slug'                => 'smart-search-control',
                        'type'                => 'plugin',
                        'public_key'          => 'pk_060713b590b492dd1c8dd77854d88',
                        'is_premium'          => false,
                        'has_addons'          => false,
                        'has_paid_plans'      => false,
                        'menu'                => array(
                            'slug'           => 'smart-search-control',
                            'first-path'     => 'admin.php?page=smart_search_control',
                            'support'        => false,
                            'network'        => true,
                        ),
                    ) );
                }

                return $ssc_fs;
            }

            // Init Freemius.
            ssc_fs();
            // Signal that SDK was initiated.
            do_action( 'ssc_fs_loaded' );
        }
    } 

    /**
     * Define plugin constants 
     */
    private function constants_setup() {

        define( 'SSC_DIR', plugin_dir_path( __FILE__ ) );
        define( 'SSC_URL', plugin_dir_url( __FILE__ ) );
        define( 'SSC_BASE_DIR',  plugin_basename( __FILE__ ) );        
        define( 'SSC_INCLUDES_DIR', SSC_DIR . 'includes/' );
        define( 'SSC_TEMPLATES_DIR', SSC_DIR . 'templates/' );
        define( 'SSC_ASSETS_URL', SSC_URL . 'assets/' );
        define( 'SSC_VERSION', '1.0.0' );
    }

    /**
     * Include necessary files
     */
    private function includes_files() {

        if ( is_admin() ) {

            require_once SSC_INCLUDES_DIR . 'admin/smart-search-control-admin-menu.php';
            require_once SSC_INCLUDES_DIR . 'admin/smart-search-control-admin-submenu-setting.php';
        }

        if( !is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ){

            require_once SSC_INCLUDES_DIR . 'smart-search-control-shortcode.php';
            require_once SSC_INCLUDES_DIR . 'smart-search-control-result-shortcode.php';
        }

    }

    /**
     * Plugin Hooks
     */
    public function hooks() {

        add_filter( 'plugin_action_links_' . SSC_BASE_DIR, [ $this, 'smart_search_control_setting_links' ] );
        register_activation_hook( __FILE__, [ $this,  'smart_search_control_plugin_activate' ] );
    }

    /**
     * Add Settings Link to Plugins Page
     */
    public function smart_search_control_setting_links( $links ) {

        $main_link = '<a href="' . admin_url( 'admin.php?page=smart_search_control' ) . '">' . __( 'Settings', 'smart-search-control' ) . '</a>';
        $setting_link = '<a href="' . admin_url( 'admin.php?page=smart_search_control_settings' ) . '">' . __( 'Settings', 'smart-search-control' ) . '</a>';
    
        array_unshift( $links, $main_link );

        return $links;
    }   

    /**
     * plugin_activate cretae database table
     * 
     */
    public function smart_search_control_plugin_activate() {

        require_once SSC_INCLUDES_DIR . 'smart-search-control-database.php';
    
    }
    
}

LD_Smart_Search_Control::instance();
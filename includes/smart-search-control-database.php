<?php
/**
 * Create the Database
 */
class Smart_Search_Control_Database {

    /**
     * Holds the class instance.
     * @var Smart_Search_Control_Database|null
     */
    private static $instance = null;

    /**
     * Returns the singleton instance.
     * @return Smart_Search_Control_Database
     */
    public static function instance() {
        
        if ( is_null( self::$instance ) && ! ( self::$instance instanceof Smart_Search_Control_Database ) ) {
            self::$instance = new self();
            self::$instance->create_database_table();
        }
        return self::$instance;
    }

    /**
     * Creates the search parameters database table if it doesn't exist.
     */
    public function create_database_table() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_search_control_parameters';

        if ( LD_Smart_Search_Control::smart_search_control_create_table() ){
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();
        

        $sql = "CREATE TABLE $table_name (
            id MEDIUMINT( 9 ) NOT NULL AUTO_INCREMENT,
            data JSON NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY ( id )
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

Smart_Search_Control_Database::instance();

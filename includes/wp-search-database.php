<?php
/**
 * Create the Database
 */
class WP_Search_Database {

    /**
     * Holds the class instance.
     * @var WP_Search_Database|null
     */
    private static $instance = null;

    /**
     * Returns the singleton instance.
     * @return WP_Search_Database
     */
    public static function instance() {
        
        if ( is_null( self::$instance ) && ! ( self::$instance instanceof WP_Search_Database ) ) {
            self::$instance = new self();
            self::$instance->create_database_table();
        }
        return self::$instance;
    }

    /**
     * Creates the search parameters database table if it doesn't exist.
     */
    private function create_database_table() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'search_parameters';

        if ( $this->table_exists( $table_name ) ) {
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

    /**
     * Checks if a table exists in the database.
     */
    private function table_exists( $table_name ) {

        global $wpdb;
        
        $query = $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        );
        return $wpdb->get_var( $query ) === $table_name;
    }
}

WP_Search_Database::instance();
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Baidu_Booster_Logger {

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'baidu_booster_logs';
    }

    public static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url text NOT NULL,
            post_id bigint(20) unsigned NOT NULL DEFAULT 0,
            post_type varchar(60) NOT NULL DEFAULT '',
            method varchar(30) NOT NULL DEFAULT 'api',
            status varchar(20) NOT NULL DEFAULT 'pending',
            http_code smallint(5) unsigned NOT NULL DEFAULT 0,
            message text NULL,
            response longtext NULL,
            attempts smallint(5) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at),
            KEY post_id (post_id)
        ) {$charset};";

        dbDelta( $sql );
    }

    public static function add( $data ) {
        global $wpdb;
        $now = current_time( 'mysql' );

        $row = wp_parse_args(
            $data,
            array(
                'url'       => '',
                'post_id'   => 0,
                'post_type' => '',
                'method'    => 'api',
                'status'    => 'pending',
                'http_code' => 0,
                'message'   => '',
                'response'  => '',
                'attempts'  => 0,
                'created_at'=> $now,
                'updated_at'=> $now,
            )
        );

        $wpdb->insert(
            self::table_name(),
            array(
                'url'        => esc_url_raw( $row['url'] ),
                'post_id'    => absint( $row['post_id'] ),
                'post_type'  => sanitize_key( $row['post_type'] ),
                'method'     => sanitize_key( $row['method'] ),
                'status'     => sanitize_key( $row['status'] ),
                'http_code'  => absint( $row['http_code'] ),
                'message'    => sanitize_textarea_field( $row['message'] ),
                'response'   => is_string( $row['response'] ) ? $row['response'] : wp_json_encode( $row['response'] ),
                'attempts'   => absint( $row['attempts'] ),
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            )
        );

        return (int) $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $clean = array();

        if ( isset( $data['status'] ) ) {
            $clean['status'] = sanitize_key( $data['status'] );
        }
        if ( isset( $data['method'] ) ) {
            $clean['method'] = sanitize_key( $data['method'] );
        }
        if ( isset( $data['http_code'] ) ) {
            $clean['http_code'] = absint( $data['http_code'] );
        }
        if ( isset( $data['message'] ) ) {
            $clean['message'] = sanitize_textarea_field( $data['message'] );
        }
        if ( isset( $data['response'] ) ) {
            $clean['response'] = is_string( $data['response'] ) ? $data['response'] : wp_json_encode( $data['response'] );
        }
        if ( isset( $data['attempts'] ) ) {
            $clean['attempts'] = absint( $data['attempts'] );
        }

        $clean['updated_at'] = current_time( 'mysql' );
        return $wpdb->update( self::table_name(), $clean, array( 'id' => absint( $id ) ) );
    }

    public static function get( $limit = 100, $status = '' ) {
        global $wpdb;
        $table = self::table_name();
        $limit = max( 1, min( 500, absint( $limit ) ) );

        if ( $status ) {
            return $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d", sanitize_key( $status ), $limit )
            );
        }

        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ) );
    }

    public static function pending( $limit = 10 ) {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY id ASC LIMIT %d", max( 1, absint( $limit ) ) )
        );
    }

    public static function stats() {
        global $wpdb;
        $table = self::table_name();
        $today = current_time( 'Y-m-d' );

        return array(
            'today'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", $today ) ),
            'success' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'success'" ),
            'failed'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'failed'" ),
            'pending' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" ),
        );
    }

    public static function retry_failed() {
        global $wpdb;
        return $wpdb->query( "UPDATE " . self::table_name() . " SET status = 'pending', updated_at = '" . esc_sql( current_time( 'mysql' ) ) . "' WHERE status = 'failed'" );
    }

    public static function clear() {
        global $wpdb;
        return $wpdb->query( 'TRUNCATE TABLE ' . self::table_name() );
    }

    public static function cleanup_old() {
        global $wpdb;
        $options = get_option( 'baidu_booster_options', array() );
        $days    = isset( $options['log_retention_days'] ) ? max( 1, absint( $options['log_retention_days'] ) ) : 30;
        $cutoff  = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp', true ) - ( DAY_IN_SECONDS * $days ) );
        $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table_name() . ' WHERE created_at < %s', $cutoff ) );
    }
}

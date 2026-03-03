<?php
namespace SFW\Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFW_Database {

    const DB_VERSION = '1.0.0';
    const DB_VERSION_OPTION = 'sfw_db_version';

    /**
     * Create custom database tables on plugin activation.
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sfw_events';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            page_url text NOT NULL,
            page_title varchar(255) DEFAULT '',
            ip_address varchar(45) DEFAULT '',
            user_agent text DEFAULT '',
            referrer text DEFAULT '',
            device_type varchar(20) DEFAULT 'desktop',
            browser varchar(100) DEFAULT '',
            status varchar(20) DEFAULT 'new',
            notes text DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_created_at (created_at),
            KEY idx_device_type (device_type),
            KEY idx_ip_address (ip_address(45))
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Check if database needs updating.
     */
    public static function maybe_update() {
        $installed_version = get_option( self::DB_VERSION_OPTION, '0' );
        if ( version_compare( $installed_version, self::DB_VERSION, '<' ) ) {
            self::create_tables();
        }
    }

    /**
     * Get the events table name.
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'sfw_events';
    }

    /**
     * Insert a click event.
     */
    public static function insert_event( $data ) {
        global $wpdb;

        return $wpdb->insert(
            self::get_table_name(),
            [
                'page_url'    => sanitize_url( $data['page_url'] ?? '' ),
                'page_title'  => sanitize_text_field( $data['page_title'] ?? '' ),
                'ip_address'  => sanitize_text_field( $data['ip_address'] ?? '' ),
                'user_agent'  => sanitize_text_field( $data['user_agent'] ?? '' ),
                'referrer'    => sanitize_url( $data['referrer'] ?? '' ),
                'device_type' => sanitize_text_field( $data['device_type'] ?? 'desktop' ),
                'browser'     => sanitize_text_field( $data['browser'] ?? '' ),
                'status'      => 'new',
                'created_at'  => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Update lead status and notes.
     */
    public static function update_lead( $id, $data ) {
        global $wpdb;

        $update = [];
        $format = [];

        if ( isset( $data['status'] ) ) {
            $allowed = [ 'new', 'contacted', 'converted', 'closed' ];
            if ( in_array( $data['status'], $allowed, true ) ) {
                $update['status'] = $data['status'];
                $format[] = '%s';
            }
        }

        if ( isset( $data['notes'] ) ) {
            $update['notes'] = sanitize_textarea_field( $data['notes'] );
            $format[] = '%s';
        }

        if ( empty( $update ) ) {
            return false;
        }

        return $wpdb->update(
            self::get_table_name(),
            $update,
            [ 'id' => absint( $id ) ],
            $format,
            [ '%d' ]
        );
    }

    /**
     * Delete an event by ID.
     */
    public static function delete_event( $id ) {
        global $wpdb;

        return $wpdb->delete(
            self::get_table_name(),
            [ 'id' => absint( $id ) ],
            [ '%d' ]
        );
    }

    /**
     * Get events with pagination and filtering.
     */
    public static function get_events( $args = [] ) {
        global $wpdb;

        $defaults = [
            'per_page' => 20,
            'page'     => 1,
            'status'   => '',
            'search'   => '',
            'orderby'  => 'created_at',
            'order'    => 'DESC',
            'date_from' => '',
            'date_to'   => '',
        ];

        $args = wp_parse_args( $args, $defaults );
        $table = self::get_table_name();
        $where = [ '1=1' ];
        $values = [];

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }

        if ( ! empty( $args['search'] ) ) {
            $where[] = '(page_url LIKE %s OR page_title LIKE %s OR ip_address LIKE %s)';
            $search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }

        if ( ! empty( $args['date_from'] ) ) {
            $where[] = 'created_at >= %s';
            $values[] = $args['date_from'] . ' 00:00:00';
        }

        if ( ! empty( $args['date_to'] ) ) {
            $where[] = 'created_at <= %s';
            $values[] = $args['date_to'] . ' 23:59:59';
        }

        $where_clause = implode( ' AND ', $where );

        $allowed_orderby = [ 'id', 'created_at', 'status', 'page_title', 'device_type' ];
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
        $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $offset = ( absint( $args['page'] ) - 1 ) * absint( $args['per_page'] );
        $limit = absint( $args['per_page'] );

        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
        if ( ! empty( $values ) ) {
            $count_query = $wpdb->prepare( $count_query, $values );
        }
        $total = (int) $wpdb->get_var( $count_query );

        // Get results
        $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
        $all_values = array_merge( $values, [ $limit, $offset ] );
        $results = $wpdb->get_results( $wpdb->prepare( $query, $all_values ) );

        return [
            'items' => $results,
            'total' => $total,
        ];
    }

    /**
     * Get analytics data for a given period.
     */
    public static function get_analytics( $days = 30 ) {
        global $wpdb;
        $table = self::get_table_name();

        $date_from = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

        // Clicks per day
        $clicks_per_day = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as clicks
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $date_from . ' 00:00:00'
        ) );

        // Device breakdown
        $devices = $wpdb->get_results( $wpdb->prepare(
            "SELECT device_type, COUNT(*) as count
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY device_type
             ORDER BY count DESC",
            $date_from . ' 00:00:00'
        ) );

        // Top pages
        $top_pages = $wpdb->get_results( $wpdb->prepare(
            "SELECT page_url, page_title, COUNT(*) as clicks
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY page_url, page_title
             ORDER BY clicks DESC
             LIMIT 10",
            $date_from . ' 00:00:00'
        ) );

        // Browser breakdown
        $browsers = $wpdb->get_results( $wpdb->prepare(
            "SELECT browser, COUNT(*) as count
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY browser
             ORDER BY count DESC
             LIMIT 10",
            $date_from . ' 00:00:00'
        ) );

        // Status breakdown
        $statuses = $wpdb->get_results( $wpdb->prepare(
            "SELECT status, COUNT(*) as count
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY status
             ORDER BY count DESC",
            $date_from . ' 00:00:00'
        ) );

        // Total clicks
        $total_clicks = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
            $date_from . ' 00:00:00'
        ) );

        // Unique visitors (by IP)
        $unique_visitors = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT ip_address) FROM {$table} WHERE created_at >= %s",
            $date_from . ' 00:00:00'
        ) );

        // Today's clicks
        $today_clicks = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s",
            current_time( 'Y-m-d' )
        ) );

        // Conversion rate
        $converted = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'converted' AND created_at >= %s",
            $date_from . ' 00:00:00'
        ) );

        // Clicks per hour (for heatmap)
        $clicks_per_hour = $wpdb->get_results( $wpdb->prepare(
            "SELECT HOUR(created_at) as hour, COUNT(*) as clicks
             FROM {$table}
             WHERE created_at >= %s
             GROUP BY HOUR(created_at)
             ORDER BY hour ASC",
            $date_from . ' 00:00:00'
        ) );

        return [
            'clicks_per_day'  => $clicks_per_day,
            'devices'         => $devices,
            'top_pages'       => $top_pages,
            'browsers'        => $browsers,
            'statuses'        => $statuses,
            'total_clicks'    => $total_clicks,
            'unique_visitors' => $unique_visitors,
            'today_clicks'    => $today_clicks,
            'converted'       => $converted,
            'conversion_rate' => $total_clicks > 0 ? round( ( $converted / $total_clicks ) * 100, 1 ) : 0,
            'clicks_per_hour' => $clicks_per_hour,
        ];
    }

    /**
     * Drop tables on plugin uninstall.
     */
    public static function drop_tables() {
        global $wpdb;
        $table = self::get_table_name();
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        delete_option( self::DB_VERSION_OPTION );
    }
}

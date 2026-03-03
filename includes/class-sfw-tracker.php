<?php
namespace SFW\Tracker;

use SFW\Database\SFW_Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFW_Tracker {

    public function __construct() {
        add_action( 'wp_ajax_sfw_track_click', [ $this, 'handle_track_click' ] );
        add_action( 'wp_ajax_nopriv_sfw_track_click', [ $this, 'handle_track_click' ] );
    }

    /**
     * Handle AJAX click tracking request.
     */
    public function handle_track_click() {
        // Verify nonce
        if ( ! check_ajax_referer( 'sfw_tracking_nonce', 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => 'Invalid security token.' ], 403 );
        }

        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $ip_address = $this->get_client_ip();
        $referrer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

        $data = [
            'page_url'    => isset( $_POST['page_url'] ) ? sanitize_url( wp_unslash( $_POST['page_url'] ) ) : '',
            'page_title'  => isset( $_POST['page_title'] ) ? sanitize_text_field( wp_unslash( $_POST['page_title'] ) ) : '',
            'ip_address'  => $ip_address,
            'user_agent'  => $user_agent,
            'referrer'    => $referrer,
            'device_type' => $this->detect_device_type( $user_agent ),
            'browser'     => $this->detect_browser( $user_agent ),
        ];

        $result = SFW_Database::insert_event( $data );

        if ( false === $result ) {
            wp_send_json_error( [ 'message' => 'Failed to record event.' ], 500 );
        }

        wp_send_json_success( [ 'message' => 'Click tracked successfully.' ] );
    }

    /**
     * Get the client's real IP address.
     */
    private function get_client_ip() {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
                // Handle comma-separated IPs (X-Forwarded-For)
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Detect device type from user agent.
     */
    private function detect_device_type( $user_agent ) {
        $ua = strtolower( $user_agent );

        if ( preg_match( '/tablet|ipad|playbook|silk/i', $ua ) ) {
            return 'tablet';
        }

        if ( preg_match( '/mobile|iphone|ipod|android.*mobile|windows phone|opera mini|opera mobi/i', $ua ) ) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Detect browser from user agent.
     */
    private function detect_browser( $user_agent ) {
        $browsers = [
            'Edge'    => '/edg(e|a|ios)?/i',
            'Opera'   => '/opr\//i',
            'Chrome'  => '/chrome/i',
            'Safari'  => '/safari/i',
            'Firefox' => '/firefox/i',
            'Samsung' => '/samsungbrowser/i',
            'UCBrowser' => '/ucbrowser/i',
        ];

        foreach ( $browsers as $name => $pattern ) {
            if ( preg_match( $pattern, $user_agent ) ) {
                return $name;
            }
        }

        return 'Other';
    }
}

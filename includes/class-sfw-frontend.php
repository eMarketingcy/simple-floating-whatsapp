<?php
namespace SFW\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFW_Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'wp_footer', [ $this, 'render_button' ] );
    }

    public function enqueue_assets() {
        $options = get_option( 'sfw_options' );
        $enabled = $options['enabled'] ?? 1;
        $phone = $options['phone'] ?? '';

        // Only enqueue assets if button is enabled and phone number is set
        if ( ! $enabled || empty( $phone ) ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'sfw-style',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/css/style.css',
            [],
            '3.0.0'
        );

        // Enqueue JS
        wp_enqueue_script(
            'sfw-script',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/main.js',
            [],
            '3.0.0',
            true
        );

        // Pass data to JavaScript
        wp_localize_script( 'sfw-script', 'sfwData', [
            'phone' => $phone,
            'message' => $options['message'] ?? '',
        ] );
    }

    public function render_button() {
        $options = get_option( 'sfw_options' );
        $enabled = $options['enabled'] ?? 1;
        $phone = $options['phone'] ?? '';

        // Check if button is enabled and phone number is set
        if ( ! $enabled || empty( $phone ) ) {
            return;
        }

        $position = $options['position'] ?? 'right';
        $position_class = ( $position === 'left' ) ? 'sfw-left' : 'sfw-right';
        $message = $options['message'] ?? '';

        // Build WhatsApp URL
        $whatsapp_url = 'https://wa.me/' . esc_attr( $phone );
        if ( ! empty( $message ) ) {
            $whatsapp_url .= '?text=' . rawurlencode( $message );
        }

        // Modern WhatsApp SVG Icon
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="32" height="32"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-47.1-156.9zM223.9 438.6c-32.6 0-64.6-8.8-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-4-10.5-6.7z"/></svg>';

        echo sprintf(
            '<div class="sfw-wrapper %s" data-sfw-position="%s">
                <a href="%s" class="sfw-button" target="_blank" rel="noopener noreferrer" aria-label="Chat on WhatsApp" data-sfw-phone="%s">
                    <span class="sfw-icon">%s</span>
                    <span class="sfw-pulse"></span>
                </a>
            </div>',
            esc_attr( $position_class ),
            esc_attr( $position ),
            esc_url( $whatsapp_url ),
            esc_attr( $phone ),
            $icon
        );
    }
}
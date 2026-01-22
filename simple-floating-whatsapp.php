<?php
/**
 * Plugin Name: Simple Floating WhatsApp (Modular)
 * Description: A professional, modular, and lightweight WhatsApp floating button.
 * Version: 2.0.0
 * Author: eMarketing Cyprus by Saltpixel Ltd
 * Author URI: https://www.emarketingcyprus.cy
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Text Domain: simple-floating-whatsapp
 */

namespace SFW;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Autoloader-style require (Manual for lite plugins)
require_once plugin_dir_path( __FILE__ ) . 'includes/class-sfw-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-sfw-frontend.php';

class SFW_Plugin {
    
    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize Admin
        if ( is_admin() ) {
            new Admin\SFW_Admin();
        }

        // Initialize Frontend
        new Frontend\SFW_Frontend();
    }
}

// Boot the plugin
SFW_Plugin::get_instance();
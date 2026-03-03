<?php
/**
 * Plugin Name: eM WhatsApp - Floating Button
 * Description: A modern, professional, and fully customizable WhatsApp floating button with 2026 UI/UX design standards.
 * Version: 3.1.0
 * Author: eMarketing Cyprus by Saltpixel Ltd
 * Author URI: https://www.emarketingcyprus.cy
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: simple-floating-whatsapp
 */

namespace SFW;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'SFW_VERSION', '3.1.0' );
define( 'SFW_PLUGIN_FILE', __FILE__ );
define( 'SFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SFW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Autoloader-style require (Manual for lite plugins)
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-database.php';
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-admin.php';
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-frontend.php';
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-tracker.php';
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-leads.php';
require_once SFW_PLUGIN_DIR . 'includes/class-sfw-analytics.php';

class SFW_Plugin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Check for database updates
        Database\SFW_Database::maybe_update();

        // Initialize Tracker (AJAX handlers - needed on both admin and frontend)
        new Tracker\SFW_Tracker();

        // Initialize Admin
        if ( is_admin() ) {
            new Admin\SFW_Admin();
            new Admin\SFW_Leads();
        }

        // Initialize Frontend
        new Frontend\SFW_Frontend();
    }
}

// Activation hook - create database tables
register_activation_hook( __FILE__, function() {
    Database\SFW_Database::create_tables();
} );

// Boot the plugin
SFW_Plugin::get_instance();

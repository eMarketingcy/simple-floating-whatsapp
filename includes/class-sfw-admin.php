<?php
namespace SFW\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFW_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function add_menu_page() {
        add_options_page(
            'WhatsApp Settings',
            'WhatsApp Button',
            'manage_options',
            'sfw-settings',
            [ $this, 'render_admin_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'sfw_group', 'sfw_options' );

        add_settings_section( 'sfw_main_section', 'Display Settings', null, 'sfw-settings' );

        add_settings_field( 'phone', 'Phone Number', [ $this, 'field_phone' ], 'sfw-settings', 'sfw_main_section' );
        add_settings_field( 'position', 'Position', [ $this, 'field_position' ], 'sfw-settings', 'sfw_main_section' );
    }

    public function field_phone() {
        $options = get_option( 'sfw_options' );
        $value = $options['phone'] ?? '';
        echo '<input type="text" name="sfw_options[phone]" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="e.g. 15551234567">';
        echo '<p class="description">Format: Country code + Number (no spaces or +)</p>';
    }

    public function field_position() {
        $options = get_option( 'sfw_options' );
        $value = $options['position'] ?? 'right';
        ?>
        <select name="sfw_options[position]">
            <option value="right" <?php selected( $value, 'right' ); ?>>Bottom Right</option>
            <option value="left" <?php selected( $value, 'left' ); ?>>Bottom Left</option>
        </select>
        <?php
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WhatsApp Button Configuration</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'sfw_group' );
                do_settings_sections( 'sfw-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
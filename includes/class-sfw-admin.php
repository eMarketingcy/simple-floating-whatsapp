<?php
namespace SFW\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFW_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function add_menu_page() {
        add_options_page(
            'eM WhatsApp Settings',
            'eM WhatsApp',
            'manage_options',
            'sfw-settings',
            [ $this, 'render_admin_page' ]
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_sfw-settings' !== $hook ) {
            return;
        }

        wp_add_inline_style( 'wp-admin', $this->get_admin_custom_styles() );
        wp_add_inline_script( 'wp-admin', $this->get_admin_custom_scripts() );
    }

    private function get_admin_custom_styles() {
        return '
            .sfw-admin-container {
                max-width: 900px;
                margin: 30px 0;
            }
            .sfw-admin-header {
                background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
                color: white;
                padding: 30px;
                border-radius: 12px;
                margin-bottom: 30px;
                box-shadow: 0 4px 20px rgba(37, 211, 102, 0.3);
            }
            .sfw-admin-header h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
                color: white;
            }
            .sfw-admin-header p {
                margin: 0;
                opacity: 0.95;
                font-size: 15px;
            }
            .sfw-settings-card {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 30px;
                margin-bottom: 20px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }
            .sfw-settings-card h2 {
                margin-top: 0;
                font-size: 20px;
                color: #333;
                border-bottom: 2px solid #25d366;
                padding-bottom: 10px;
                margin-bottom: 25px;
            }
            .sfw-field-row {
                margin-bottom: 25px;
            }
            .sfw-field-row label {
                display: block;
                font-weight: 600;
                margin-bottom: 8px;
                color: #333;
                font-size: 14px;
            }
            .sfw-field-row input[type="text"],
            .sfw-field-row select,
            .sfw-field-row textarea {
                width: 100%;
                max-width: 500px;
                padding: 10px 15px;
                border: 2px solid #e0e0e0;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            .sfw-field-row input[type="text"]:focus,
            .sfw-field-row select:focus,
            .sfw-field-row textarea:focus {
                border-color: #25d366;
                outline: none;
                box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
            }
            .sfw-field-row textarea {
                min-height: 80px;
                resize: vertical;
            }
            .sfw-toggle-wrapper {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            .sfw-toggle {
                position: relative;
                display: inline-block;
                width: 60px;
                height: 32px;
            }
            .sfw-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .sfw-toggle-slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: 0.4s;
                border-radius: 32px;
            }
            .sfw-toggle-slider:before {
                position: absolute;
                content: "";
                height: 24px;
                width: 24px;
                left: 4px;
                bottom: 4px;
                background-color: white;
                transition: 0.4s;
                border-radius: 50%;
            }
            .sfw-toggle input:checked + .sfw-toggle-slider {
                background-color: #25d366;
            }
            .sfw-toggle input:checked + .sfw-toggle-slider:before {
                transform: translateX(28px);
            }
            .sfw-toggle-label {
                font-weight: 600;
                color: #555;
            }
            .sfw-toggle input:checked ~ .sfw-toggle-label {
                color: #25d366;
            }
            .sfw-description {
                margin-top: 8px;
                color: #666;
                font-size: 13px;
                font-style: italic;
            }
            .sfw-preview-badge {
                display: inline-block;
                background: #f0f9ff;
                color: #0369a1;
                padding: 4px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                margin-left: 10px;
            }
        ';
    }

    private function get_admin_custom_scripts() {
        return '
            document.addEventListener("DOMContentLoaded", function() {
                const enableToggle = document.querySelector("input[name=\"sfw_options[enabled]\"]");
                if (enableToggle) {
                    const updateStatus = () => {
                        const label = enableToggle.parentElement.querySelector(".sfw-toggle-label");
                        if (label) {
                            label.textContent = enableToggle.checked ? "Enabled" : "Disabled";
                        }
                    };
                    enableToggle.addEventListener("change", updateStatus);
                    updateStatus();
                }
            });
        ';
    }

    public function register_settings() {
        register_setting( 'sfw_group', 'sfw_options', [ $this, 'sanitize_options' ] );

        add_settings_section( 'sfw_main_section', 'General Settings', null, 'sfw-settings' );

        add_settings_field( 'enabled', 'Display Button', [ $this, 'field_enabled' ], 'sfw-settings', 'sfw_main_section' );
        add_settings_field( 'phone', 'Phone Number', [ $this, 'field_phone' ], 'sfw-settings', 'sfw_main_section' );
        add_settings_field( 'message', 'Default Message', [ $this, 'field_message' ], 'sfw-settings', 'sfw_main_section' );
        add_settings_field( 'position', 'Button Position', [ $this, 'field_position' ], 'sfw-settings', 'sfw_main_section' );
    }

    public function sanitize_options( $input ) {
        $sanitized = [];

        $sanitized['enabled'] = isset( $input['enabled'] ) ? 1 : 0;
        $sanitized['phone'] = sanitize_text_field( $input['phone'] ?? '' );
        $sanitized['message'] = sanitize_textarea_field( $input['message'] ?? '' );
        $sanitized['position'] = in_array( $input['position'] ?? '', [ 'left', 'right' ] ) ? $input['position'] : 'right';

        return $sanitized;
    }

    public function field_enabled() {
        $options = get_option( 'sfw_options' );
        $value = $options['enabled'] ?? 1;
        ?>
        <div class="sfw-toggle-wrapper">
            <label class="sfw-toggle">
                <input type="checkbox" name="sfw_options[enabled]" value="1" <?php checked( $value, 1 ); ?>>
                <span class="sfw-toggle-slider"></span>
            </label>
            <span class="sfw-toggle-label"><?php echo $value ? 'Enabled' : 'Disabled'; ?></span>
        </div>
        <p class="sfw-description">Toggle to show or hide the WhatsApp button on your website's frontend</p>
        <?php
    }

    public function field_phone() {
        $options = get_option( 'sfw_options' );
        $value = $options['phone'] ?? '';
        ?>
        <input type="text" name="sfw_options[phone]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="e.g., 15551234567">
        <p class="sfw-description">Format: Country code + Number (no spaces, dashes, or + symbol). Example: 35799123456</p>
        <?php
    }

    public function field_message() {
        $options = get_option( 'sfw_options' );
        $value = $options['message'] ?? '';
        ?>
        <textarea name="sfw_options[message]" rows="3" class="regular-text" placeholder="Hi! I'm interested in your services..."><?php echo esc_textarea( $value ); ?></textarea>
        <p class="sfw-description">Pre-filled message that appears when users click the WhatsApp button (optional)</p>
        <?php
    }

    public function field_position() {
        $options = get_option( 'sfw_options' );
        $value = $options['position'] ?? 'right';
        ?>
        <select name="sfw_options[position]">
            <option value="right" <?php selected( $value, 'right' ); ?>>Bottom Right</option>
            <option value="left" <?php selected( $value, 'left' ); ?>>Bottom Left</option>
        </select>
        <p class="sfw-description">Choose where the WhatsApp button appears on your website</p>
        <?php
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error( 'sfw_messages', 'sfw_message', 'Settings Saved Successfully!', 'updated' );
        }

        settings_errors( 'sfw_messages' );
        ?>
        <div class="wrap sfw-admin-container">
            <div class="sfw-admin-header">
                <h1>
                    eM WhatsApp - Floating Button
                    <span class="sfw-preview-badge">v3.0</span>
                </h1>
                <p>Configure your modern WhatsApp floating button with 2026 UI/UX design standards</p>
            </div>

            <div class="sfw-settings-card">
                <h2>Button Configuration</h2>
                <form action="options.php" method="post">
                    <?php
                    settings_fields( 'sfw_group' );
                    do_settings_sections( 'sfw-settings' );
                    submit_button( 'Save Settings', 'primary', 'submit', false );
                    ?>
                </form>
            </div>
        </div>
        <?php
    }
}
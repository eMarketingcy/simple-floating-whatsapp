<?php
namespace SFW\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFW_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function add_menu_pages() {
        // WhatsApp SVG icon for menu (base64-encoded)
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" fill="currentColor"><path d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-47.1-156.9z"/></svg>'
        );

        // Top-level menu
        add_menu_page(
            'eM WhatsApp',
            'eM WhatsApp',
            'manage_options',
            'sfw-settings',
            [ $this, 'render_settings_page' ],
            $icon_svg,
            80
        );

        // Settings submenu (replaces the auto-created top-level duplicate)
        add_submenu_page(
            'sfw-settings',
            'Settings',
            'Settings',
            'manage_options',
            'sfw-settings',
            [ $this, 'render_settings_page' ]
        );

        // Leads submenu
        add_submenu_page(
            'sfw-settings',
            'WhatsApp Leads',
            'Leads',
            'manage_options',
            'sfw-leads',
            [ $this, 'render_leads_page' ]
        );

        // Analytics submenu
        add_submenu_page(
            'sfw-settings',
            'Click Analytics',
            'Analytics',
            'manage_options',
            'sfw-analytics',
            [ $this, 'render_analytics_page' ]
        );
    }

    public function enqueue_admin_assets( $hook ) {
        $sfw_pages = [
            'toplevel_page_sfw-settings',
            'em-whatsapp_page_sfw-leads',
            'em-whatsapp_page_sfw-analytics',
        ];

        if ( ! in_array( $hook, $sfw_pages, true ) ) {
            return;
        }

        // Load dashicons for all pages
        wp_enqueue_style( 'dashicons' );

        wp_add_inline_style( 'wp-admin', $this->get_admin_styles() );
        wp_add_inline_script( 'wp-admin', $this->get_admin_scripts() );
    }

    private function get_admin_styles() {
        return '
            /* =================================================
               Shared Admin Styles
               ================================================= */
            .sfw-admin-container {
                max-width: 1200px;
                margin: 20px 0;
            }
            .sfw-admin-header {
                background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
                color: white;
                padding: 28px 32px;
                border-radius: 12px;
                margin-bottom: 24px;
                box-shadow: 0 4px 20px rgba(37, 211, 102, 0.3);
            }
            .sfw-admin-header h1 {
                margin: 0 0 6px 0;
                font-size: 26px;
                color: white;
            }
            .sfw-admin-header p {
                margin: 0;
                opacity: 0.92;
                font-size: 14px;
            }

            /* =================================================
               Settings Page
               ================================================= */
            .sfw-settings-card {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 30px;
                margin-bottom: 20px;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                max-width: 900px;
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
                top: 0; left: 0; right: 0; bottom: 0;
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
                background: rgba(255,255,255,0.2);
                color: white;
                padding: 3px 10px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                margin-left: 10px;
            }

            /* =================================================
               Leads Page
               ================================================= */
            .sfw-leads-toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 12px;
                margin-bottom: 16px;
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 16px 20px;
            }
            .sfw-status-filters {
                display: flex;
                gap: 4px;
                flex-wrap: wrap;
            }
            .sfw-filter-tab {
                display: inline-block;
                padding: 6px 14px;
                border-radius: 6px;
                text-decoration: none;
                font-size: 13px;
                color: #555;
                background: #f5f5f5;
                transition: all 0.2s;
            }
            .sfw-filter-tab:hover {
                background: #e8f5e9;
                color: #128c7e;
            }
            .sfw-filter-tab.active {
                background: #25d366;
                color: #fff;
                font-weight: 600;
            }
            .sfw-filter-tab .count {
                opacity: 0.75;
                font-size: 12px;
            }
            .sfw-leads-actions {
                display: flex;
                gap: 8px;
                align-items: center;
            }
            .sfw-search-form {
                display: flex;
                gap: 4px;
            }
            .sfw-search-input {
                padding: 6px 12px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 13px;
                width: 200px;
            }
            .sfw-search-input:focus {
                border-color: #25d366;
                outline: none;
                box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.1);
            }
            .sfw-export-btn {
                background: #128c7e !important;
                color: #fff !important;
                border-color: #128c7e !important;
            }
            .sfw-export-btn:hover {
                background: #0d7a6f !important;
            }

            /* Bulk Bar */
            .sfw-bulk-bar {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 10px 20px;
                background: #f8f9fa;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                margin-bottom: 12px;
                font-size: 13px;
            }
            .sfw-selected-count {
                color: #666;
                font-weight: 500;
            }

            /* Leads Table */
            .sfw-leads-table {
                width: 100%;
                border-collapse: collapse;
                background: #fff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                border: 1px solid #e0e0e0;
            }
            .sfw-leads-table thead {
                background: #f8f9fa;
            }
            .sfw-leads-table th {
                padding: 12px 14px;
                text-align: left;
                font-weight: 600;
                font-size: 13px;
                color: #555;
                border-bottom: 2px solid #e0e0e0;
            }
            .sfw-leads-table td {
                padding: 12px 14px;
                border-bottom: 1px solid #f0f0f0;
                font-size: 13px;
                vertical-align: middle;
            }
            .sfw-leads-table tbody tr:hover {
                background: #f8fffe;
            }
            .sfw-leads-table a {
                color: #128c7e;
                text-decoration: none;
            }
            .sfw-leads-table a:hover {
                text-decoration: underline;
            }
            .sfw-col-check { width: 35px; }
            .sfw-col-date { width: 120px; }
            .sfw-col-device { width: 90px; }
            .sfw-col-status { width: 130px; }
            .sfw-col-actions { width: 90px; white-space: nowrap; }

            .sfw-device-badge {
                display: inline-block;
                padding: 3px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.3px;
            }
            .sfw-device-mobile { background: #e0f7fa; color: #00838f; }
            .sfw-device-desktop { background: #e8eaf6; color: #3949ab; }
            .sfw-device-tablet { background: #fff3e0; color: #e65100; }

            .sfw-status-select {
                padding: 5px 8px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 12px;
                width: 100%;
            }
            .sfw-notes-input {
                width: 100%;
                min-width: 140px;
                padding: 6px 8px;
                border: 1px solid #ddd;
                border-radius: 6px;
                font-size: 12px;
                resize: vertical;
            }
            .sfw-notes-input:focus,
            .sfw-status-select:focus {
                border-color: #25d366;
                outline: none;
            }

            .sfw-save-lead,
            .sfw-delete-lead {
                padding: 2px 6px !important;
                min-height: 28px !important;
            }
            .sfw-save-lead .dashicons,
            .sfw-delete-lead .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                vertical-align: middle;
            }
            .sfw-delete-lead { color: #dc3545 !important; }

            /* Pagination */
            .sfw-pagination {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16px 0;
            }
            .sfw-pagination-info {
                color: #666;
                font-size: 13px;
            }
            .sfw-pagination-links {
                display: flex;
                gap: 4px;
            }

            /* Empty State */
            .sfw-empty-state {
                text-align: center;
                padding: 60px 20px;
                background: #fff;
                border-radius: 12px;
                border: 1px solid #e0e0e0;
            }
            .sfw-empty-state h3 {
                color: #333;
                margin: 16px 0 8px;
            }
            .sfw-empty-state p {
                color: #888;
            }

            /* =================================================
               Analytics Page
               ================================================= */
            .sfw-period-selector {
                display: flex;
                gap: 4px;
                margin-bottom: 24px;
                background: #fff;
                padding: 8px;
                border-radius: 10px;
                border: 1px solid #e0e0e0;
                display: inline-flex;
            }
            .sfw-period-btn {
                padding: 8px 18px;
                border-radius: 8px;
                text-decoration: none;
                font-size: 13px;
                color: #555;
                font-weight: 500;
                transition: all 0.2s;
            }
            .sfw-period-btn:hover {
                background: #e8f5e9;
                color: #128c7e;
            }
            .sfw-period-btn.active {
                background: #25d366;
                color: #fff;
                font-weight: 600;
            }

            /* KPI Grid */
            .sfw-kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 20px;
                margin-bottom: 24px;
            }
            .sfw-kpi-card {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 24px;
                display: flex;
                align-items: center;
                gap: 16px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .sfw-kpi-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            }
            .sfw-kpi-icon {
                width: 52px;
                height: 52px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            .sfw-kpi-icon .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
            }
            .sfw-kpi-content {
                display: flex;
                flex-direction: column;
            }
            .sfw-kpi-value {
                font-size: 28px;
                font-weight: 700;
                color: #1a1a2e;
                line-height: 1.2;
            }
            .sfw-kpi-label {
                font-size: 13px;
                color: #888;
                font-weight: 500;
            }

            /* Charts Grid */
            .sfw-charts-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 24px;
                margin-bottom: 24px;
            }
            .sfw-chart-wide {
                grid-column: 1 / -1;
            }
            .sfw-chart-card {
                background: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            }
            .sfw-chart-card h3 {
                margin: 0 0 16px 0;
                font-size: 16px;
                color: #333;
            }
            .sfw-chart-card canvas {
                max-height: 300px;
            }
            .sfw-no-data {
                text-align: center;
                color: #999;
                padding: 40px;
            }

            /* Heatmap */
            .sfw-heatmap {
                display: grid;
                grid-template-columns: repeat(12, 1fr);
                gap: 4px;
            }
            .sfw-heatmap-cell {
                border-radius: 6px;
                padding: 8px 4px;
                text-align: center;
                min-height: 52px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                transition: transform 0.2s;
            }
            .sfw-heatmap-cell:hover {
                transform: scale(1.05);
            }
            .sfw-heatmap-hour {
                font-size: 10px;
                color: #666;
                font-weight: 600;
            }
            .sfw-heatmap-count {
                font-size: 14px;
                font-weight: 700;
                color: #333;
            }

            /* Top Pages Table */
            .sfw-top-pages-table {
                width: 100%;
                border-collapse: collapse;
            }
            .sfw-top-pages-table th {
                text-align: left;
                padding: 10px 12px;
                font-size: 12px;
                color: #888;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-bottom: 1px solid #eee;
            }
            .sfw-top-pages-table td {
                padding: 10px 12px;
                font-size: 13px;
                border-bottom: 1px solid #f5f5f5;
            }
            .sfw-top-pages-table a {
                color: #128c7e;
                text-decoration: none;
            }
            .sfw-top-pages-table a:hover {
                text-decoration: underline;
            }

            /* Bar Chart (CSS) */
            .sfw-bar-wrapper {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .sfw-bar-wrapper .sfw-bar {
                height: 8px;
                background: linear-gradient(90deg, #25d366, #128c7e);
                border-radius: 4px;
                min-width: 4px;
                flex: 1;
                max-width: 120px;
            }
            .sfw-bar-wrapper span {
                font-size: 12px;
                color: #666;
                white-space: nowrap;
            }

            /* Browser Grid */
            .sfw-browser-grid {
                display: grid;
                gap: 12px;
            }
            .sfw-browser-item {
                display: grid;
                grid-template-columns: 100px 1fr auto;
                align-items: center;
                gap: 12px;
            }
            .sfw-browser-name {
                font-size: 13px;
                font-weight: 600;
                color: #333;
            }
            .sfw-browser-stats {
                font-size: 12px;
                color: #666;
                white-space: nowrap;
            }

            /* =================================================
               Responsive
               ================================================= */
            @media (max-width: 782px) {
                .sfw-kpi-grid {
                    grid-template-columns: 1fr 1fr;
                }
                .sfw-charts-grid {
                    grid-template-columns: 1fr;
                }
                .sfw-heatmap {
                    grid-template-columns: repeat(6, 1fr);
                }
                .sfw-leads-toolbar {
                    flex-direction: column;
                    align-items: flex-start;
                }
                .sfw-browser-item {
                    grid-template-columns: 1fr;
                }
            }
        ';
    }

    private function get_admin_scripts() {
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

    public function render_settings_page() {
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
                    <span class="sfw-preview-badge">v<?php echo esc_html( SFW_VERSION ); ?></span>
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

    public function render_leads_page() {
        $leads = new SFW_Leads();
        $leads->render_page();
    }

    public function render_analytics_page() {
        $analytics = new SFW_Analytics();
        $analytics->render_page();
    }
}

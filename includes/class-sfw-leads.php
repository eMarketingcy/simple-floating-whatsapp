<?php
namespace SFW\Admin;

use SFW\Database\SFW_Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFW_Leads {

    public function __construct() {
        add_action( 'wp_ajax_sfw_update_lead', [ $this, 'handle_update_lead' ] );
        add_action( 'wp_ajax_sfw_delete_lead', [ $this, 'handle_delete_lead' ] );
        add_action( 'wp_ajax_sfw_bulk_action_leads', [ $this, 'handle_bulk_action' ] );
        add_action( 'wp_ajax_sfw_export_leads', [ $this, 'handle_export' ] );
    }

    /**
     * Handle AJAX lead status/notes update.
     */
    public function handle_update_lead() {
        check_ajax_referer( 'sfw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }

        $id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => 'Invalid lead ID.' ], 400 );
        }

        $data = [];
        if ( isset( $_POST['status'] ) ) {
            $data['status'] = sanitize_text_field( wp_unslash( $_POST['status'] ) );
        }
        if ( isset( $_POST['notes'] ) ) {
            $data['notes'] = sanitize_textarea_field( wp_unslash( $_POST['notes'] ) );
        }

        $result = SFW_Database::update_lead( $id, $data );

        if ( false === $result ) {
            wp_send_json_error( [ 'message' => 'Failed to update lead.' ], 500 );
        }

        wp_send_json_success( [ 'message' => 'Lead updated.' ] );
    }

    /**
     * Handle AJAX lead deletion.
     */
    public function handle_delete_lead() {
        check_ajax_referer( 'sfw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }

        $id = isset( $_POST['lead_id'] ) ? absint( $_POST['lead_id'] ) : 0;
        if ( ! $id ) {
            wp_send_json_error( [ 'message' => 'Invalid lead ID.' ], 400 );
        }

        SFW_Database::delete_event( $id );
        wp_send_json_success( [ 'message' => 'Lead deleted.' ] );
    }

    /**
     * Handle bulk actions (delete, change status).
     */
    public function handle_bulk_action() {
        check_ajax_referer( 'sfw_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'Unauthorized.' ], 403 );
        }

        $action = isset( $_POST['bulk_action'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_action'] ) ) : '';
        $ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : [];

        if ( empty( $ids ) || empty( $action ) ) {
            wp_send_json_error( [ 'message' => 'No items selected.' ], 400 );
        }

        $count = 0;
        foreach ( $ids as $id ) {
            if ( $action === 'delete' ) {
                SFW_Database::delete_event( $id );
                $count++;
            } elseif ( in_array( $action, [ 'new', 'contacted', 'converted', 'closed' ], true ) ) {
                SFW_Database::update_lead( $id, [ 'status' => $action ] );
                $count++;
            }
        }

        wp_send_json_success( [ 'message' => "{$count} leads updated.", 'count' => $count ] );
    }

    /**
     * Handle CSV export of leads.
     */
    public function handle_export() {
        if ( ! check_ajax_referer( 'sfw_admin_nonce', 'nonce', false ) ) {
            wp_die( 'Invalid security token.' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized.' );
        }

        $args = [
            'per_page' => 999999,
            'page'     => 1,
        ];

        if ( ! empty( $_GET['status'] ) ) {
            $args['status'] = sanitize_text_field( wp_unslash( $_GET['status'] ) );
        }

        $result = SFW_Database::get_events( $args );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=whatsapp-leads-' . gmdate( 'Y-m-d' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, [ 'ID', 'Date', 'Page URL', 'Page Title', 'IP Address', 'Device', 'Browser', 'Status', 'Notes' ] );

        foreach ( $result['items'] as $item ) {
            fputcsv( $output, [
                $item->id,
                $item->created_at,
                $item->page_url,
                $item->page_title,
                $item->ip_address,
                $item->device_type,
                $item->browser,
                $item->status,
                $item->notes,
            ] );
        }

        fclose( $output );
        exit;
    }

    /**
     * Render the Leads admin page.
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $current_status = isset( $_GET['lead_status'] ) ? sanitize_text_field( wp_unslash( $_GET['lead_status'] ) ) : '';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $per_page = 20;

        $result = SFW_Database::get_events( [
            'per_page' => $per_page,
            'page'     => $paged,
            'status'   => $current_status,
            'search'   => $search,
        ] );

        $items = $result['items'];
        $total = $result['total'];
        $total_pages = ceil( $total / $per_page );

        // Get status counts for filters
        global $wpdb;
        $table = SFW_Database::get_table_name();
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
            OBJECT_K
        );
        $total_all = array_sum( wp_list_pluck( $status_counts, 'count' ) );

        $nonce = wp_create_nonce( 'sfw_admin_nonce' );
        ?>
        <div class="wrap sfw-admin-container">
            <div class="sfw-admin-header">
                <h1>WhatsApp Leads</h1>
                <p>Track and manage visitors who clicked your WhatsApp button</p>
            </div>

            <!-- Filters Bar -->
            <div class="sfw-leads-toolbar">
                <div class="sfw-status-filters">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=sfw-leads' ) ); ?>"
                       class="sfw-filter-tab <?php echo empty( $current_status ) ? 'active' : ''; ?>">
                        All <span class="count">(<?php echo esc_html( $total_all ); ?>)</span>
                    </a>
                    <?php
                    $status_labels = [
                        'new'       => 'New',
                        'contacted' => 'Contacted',
                        'converted' => 'Converted',
                        'closed'    => 'Closed',
                    ];
                    foreach ( $status_labels as $key => $label ) :
                        $count = isset( $status_counts[ $key ] ) ? $status_counts[ $key ]->count : 0;
                        ?>
                        <a href="<?php echo esc_url( add_query_arg( 'lead_status', $key, admin_url( 'admin.php?page=sfw-leads' ) ) ); ?>"
                           class="sfw-filter-tab <?php echo $current_status === $key ? 'active' : ''; ?>">
                            <?php echo esc_html( $label ); ?> <span class="count">(<?php echo esc_html( $count ); ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="sfw-leads-actions">
                    <form method="get" action="" class="sfw-search-form">
                        <input type="hidden" name="page" value="sfw-leads">
                        <?php if ( $current_status ) : ?>
                            <input type="hidden" name="lead_status" value="<?php echo esc_attr( $current_status ); ?>">
                        <?php endif; ?>
                        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search leads..." class="sfw-search-input">
                        <button type="submit" class="button">Search</button>
                    </form>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=sfw_export_leads&status=' . $current_status ), 'sfw_admin_nonce', 'nonce' ) ); ?>"
                       class="button sfw-export-btn">Export CSV</a>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="sfw-bulk-bar" id="sfw-bulk-bar" style="display: none;">
                <label><input type="checkbox" id="sfw-select-all"> Select All</label>
                <span class="sfw-selected-count">0 selected</span>
                <select id="sfw-bulk-action">
                    <option value="">Bulk Actions</option>
                    <option value="new">Mark as New</option>
                    <option value="contacted">Mark as Contacted</option>
                    <option value="converted">Mark as Converted</option>
                    <option value="closed">Mark as Closed</option>
                    <option value="delete">Delete</option>
                </select>
                <button class="button" id="sfw-apply-bulk">Apply</button>
            </div>

            <!-- Leads Table -->
            <?php if ( empty( $items ) ) : ?>
                <div class="sfw-empty-state">
                    <div class="sfw-empty-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <h3>No leads yet</h3>
                    <p>Leads will appear here when visitors click your WhatsApp button.</p>
                </div>
            <?php else : ?>
                <table class="sfw-leads-table">
                    <thead>
                        <tr>
                            <th class="sfw-col-check"><input type="checkbox" id="sfw-check-all"></th>
                            <th class="sfw-col-date">Date</th>
                            <th class="sfw-col-page">Page</th>
                            <th class="sfw-col-visitor">Visitor</th>
                            <th class="sfw-col-device">Device</th>
                            <th class="sfw-col-status">Status</th>
                            <th class="sfw-col-notes">Notes</th>
                            <th class="sfw-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $items as $item ) : ?>
                            <tr data-id="<?php echo esc_attr( $item->id ); ?>">
                                <td><input type="checkbox" class="sfw-lead-check" value="<?php echo esc_attr( $item->id ); ?>"></td>
                                <td class="sfw-col-date">
                                    <strong><?php echo esc_html( gmdate( 'M j, Y', strtotime( $item->created_at ) ) ); ?></strong>
                                    <br><small><?php echo esc_html( gmdate( 'g:i A', strtotime( $item->created_at ) ) ); ?></small>
                                </td>
                                <td class="sfw-col-page">
                                    <a href="<?php echo esc_url( $item->page_url ); ?>" target="_blank" title="<?php echo esc_attr( $item->page_url ); ?>">
                                        <?php echo esc_html( $item->page_title ?: wp_parse_url( $item->page_url, PHP_URL_PATH ) ?: '/' ); ?>
                                    </a>
                                </td>
                                <td class="sfw-col-visitor">
                                    <code><?php echo esc_html( $item->ip_address ); ?></code>
                                    <br><small><?php echo esc_html( $item->browser ); ?></small>
                                </td>
                                <td class="sfw-col-device">
                                    <span class="sfw-device-badge sfw-device-<?php echo esc_attr( $item->device_type ); ?>">
                                        <?php echo esc_html( ucfirst( $item->device_type ) ); ?>
                                    </span>
                                </td>
                                <td class="sfw-col-status">
                                    <select class="sfw-status-select" data-id="<?php echo esc_attr( $item->id ); ?>">
                                        <option value="new" <?php selected( $item->status, 'new' ); ?>>New</option>
                                        <option value="contacted" <?php selected( $item->status, 'contacted' ); ?>>Contacted</option>
                                        <option value="converted" <?php selected( $item->status, 'converted' ); ?>>Converted</option>
                                        <option value="closed" <?php selected( $item->status, 'closed' ); ?>>Closed</option>
                                    </select>
                                </td>
                                <td class="sfw-col-notes">
                                    <textarea class="sfw-notes-input" data-id="<?php echo esc_attr( $item->id ); ?>"
                                              placeholder="Add notes..." rows="2"><?php echo esc_textarea( $item->notes ); ?></textarea>
                                </td>
                                <td class="sfw-col-actions">
                                    <button class="button sfw-save-lead" data-id="<?php echo esc_attr( $item->id ); ?>" title="Save">
                                        <span class="dashicons dashicons-saved"></span>
                                    </button>
                                    <button class="button sfw-delete-lead" data-id="<?php echo esc_attr( $item->id ); ?>" title="Delete">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ( $total_pages > 1 ) : ?>
                    <div class="sfw-pagination">
                        <span class="sfw-pagination-info">
                            Showing <?php echo esc_html( ( ( $paged - 1 ) * $per_page ) + 1 ); ?>-<?php echo esc_html( min( $paged * $per_page, $total ) ); ?> of <?php echo esc_html( $total ); ?> leads
                        </span>
                        <div class="sfw-pagination-links">
                            <?php
                            $base_url = admin_url( 'admin.php?page=sfw-leads' );
                            if ( $current_status ) {
                                $base_url = add_query_arg( 'lead_status', $current_status, $base_url );
                            }
                            if ( $search ) {
                                $base_url = add_query_arg( 's', $search, $base_url );
                            }

                            if ( $paged > 1 ) :
                                ?>
                                <a href="<?php echo esc_url( add_query_arg( 'paged', $paged - 1, $base_url ) ); ?>" class="button">&laquo; Prev</a>
                            <?php endif; ?>

                            <?php for ( $i = max( 1, $paged - 2 ); $i <= min( $total_pages, $paged + 2 ); $i++ ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'paged', $i, $base_url ) ); ?>"
                                   class="button <?php echo $i === $paged ? 'button-primary' : ''; ?>">
                                    <?php echo esc_html( $i ); ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ( $paged < $total_pages ) : ?>
                                <a href="<?php echo esc_url( add_query_arg( 'paged', $paged + 1, $base_url ) ); ?>" class="button">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script>
        (function() {
            const nonce = '<?php echo esc_js( $nonce ); ?>';
            const ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

            // Show bulk bar when checkboxes exist
            const checks = document.querySelectorAll('.sfw-lead-check');
            const bulkBar = document.getElementById('sfw-bulk-bar');
            if (checks.length) bulkBar.style.display = 'flex';

            // Select all
            const checkAll = document.getElementById('sfw-check-all');
            const selectAll = document.getElementById('sfw-select-all');

            function syncChecks(checked) {
                checks.forEach(c => c.checked = checked);
                updateCount();
            }

            if (checkAll) checkAll.addEventListener('change', function() { syncChecks(this.checked); if (selectAll) selectAll.checked = this.checked; });
            if (selectAll) selectAll.addEventListener('change', function() { syncChecks(this.checked); if (checkAll) checkAll.checked = this.checked; });
            checks.forEach(c => c.addEventListener('change', updateCount));

            function updateCount() {
                const cnt = document.querySelectorAll('.sfw-lead-check:checked').length;
                const el = document.querySelector('.sfw-selected-count');
                if (el) el.textContent = cnt + ' selected';
            }

            // Save individual lead
            document.querySelectorAll('.sfw-save-lead').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const row = this.closest('tr');
                    const status = row.querySelector('.sfw-status-select').value;
                    const notes = row.querySelector('.sfw-notes-input').value;

                    const fd = new FormData();
                    fd.append('action', 'sfw_update_lead');
                    fd.append('nonce', nonce);
                    fd.append('lead_id', id);
                    fd.append('status', status);
                    fd.append('notes', notes);

                    fetch(ajaxUrl, { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                this.innerHTML = '<span class="dashicons dashicons-yes-alt" style="color:#25d366"></span>';
                                setTimeout(() => { this.innerHTML = '<span class="dashicons dashicons-saved"></span>'; }, 1500);
                            }
                        });
                });
            });

            // Delete individual lead
            document.querySelectorAll('.sfw-delete-lead').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!confirm('Delete this lead?')) return;
                    const id = this.dataset.id;

                    const fd = new FormData();
                    fd.append('action', 'sfw_delete_lead');
                    fd.append('nonce', nonce);
                    fd.append('lead_id', id);

                    fetch(ajaxUrl, { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                this.closest('tr').remove();
                            }
                        });
                });
            });

            // Bulk action
            document.getElementById('sfw-apply-bulk')?.addEventListener('click', function() {
                const action = document.getElementById('sfw-bulk-action').value;
                if (!action) return;

                const ids = Array.from(document.querySelectorAll('.sfw-lead-check:checked')).map(c => c.value);
                if (!ids.length) return;

                if (action === 'delete' && !confirm('Delete ' + ids.length + ' leads?')) return;

                const fd = new FormData();
                fd.append('action', 'sfw_bulk_action_leads');
                fd.append('nonce', nonce);
                fd.append('bulk_action', action);
                ids.forEach(id => fd.append('ids[]', id));

                fetch(ajaxUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) location.reload();
                    });
            });
        })();
        </script>
        <?php
    }
}

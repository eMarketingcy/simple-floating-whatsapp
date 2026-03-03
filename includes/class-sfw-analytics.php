<?php
namespace SFW\Admin;

use SFW\Database\SFW_Database;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFW_Analytics {

    /**
     * Render the Analytics dashboard page.
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $days = isset( $_GET['days'] ) ? absint( $_GET['days'] ) : 30;
        if ( ! in_array( $days, [ 7, 14, 30, 90, 365 ], true ) ) {
            $days = 30;
        }

        $analytics = SFW_Database::get_analytics( $days );

        // Prepare chart data
        $chart_labels = [];
        $chart_data = [];

        // Fill in all days (including zero-click days)
        $date_map = [];
        foreach ( $analytics['clicks_per_day'] as $row ) {
            $date_map[ $row->date ] = (int) $row->clicks;
        }

        for ( $i = $days - 1; $i >= 0; $i-- ) {
            $date = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
            $chart_labels[] = gmdate( 'M j', strtotime( $date ) );
            $chart_data[] = $date_map[ $date ] ?? 0;
        }

        // Device chart data
        $device_labels = [];
        $device_data = [];
        $device_colors = [ 'desktop' => '#25d366', 'mobile' => '#128c7e', 'tablet' => '#075e54' ];
        foreach ( $analytics['devices'] as $row ) {
            $device_labels[] = ucfirst( $row->device_type );
            $device_data[] = (int) $row->count;
        }

        // Status chart data
        $status_labels = [];
        $status_data = [];
        $status_colors_map = [ 'new' => '#3b82f6', 'contacted' => '#f59e0b', 'converted' => '#25d366', 'closed' => '#6b7280' ];
        $status_colors = [];
        foreach ( $analytics['statuses'] as $row ) {
            $status_labels[] = ucfirst( $row->status );
            $status_data[] = (int) $row->count;
            $status_colors[] = $status_colors_map[ $row->status ] ?? '#999';
        }

        // Hour heatmap data
        $hour_data = array_fill( 0, 24, 0 );
        foreach ( $analytics['clicks_per_hour'] as $row ) {
            $hour_data[ (int) $row->hour ] = (int) $row->clicks;
        }
        ?>
        <div class="wrap sfw-admin-container">
            <div class="sfw-admin-header">
                <h1>Click Analytics</h1>
                <p>Track the performance of your WhatsApp button</p>
            </div>

            <!-- Period Selector -->
            <div class="sfw-period-selector">
                <?php
                $periods = [ 7 => '7 Days', 14 => '14 Days', 30 => '30 Days', 90 => '90 Days', 365 => '1 Year' ];
                foreach ( $periods as $d => $label ) :
                    ?>
                    <a href="<?php echo esc_url( add_query_arg( 'days', $d, admin_url( 'admin.php?page=sfw-analytics' ) ) ); ?>"
                       class="sfw-period-btn <?php echo $days === $d ? 'active' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- KPI Cards -->
            <div class="sfw-kpi-grid">
                <div class="sfw-kpi-card">
                    <div class="sfw-kpi-icon" style="background: rgba(37, 211, 102, 0.1); color: #25d366;">
                        <span class="dashicons dashicons-phone"></span>
                    </div>
                    <div class="sfw-kpi-content">
                        <span class="sfw-kpi-value"><?php echo esc_html( number_format( $analytics['total_clicks'] ) ); ?></span>
                        <span class="sfw-kpi-label">Total Clicks</span>
                    </div>
                </div>
                <div class="sfw-kpi-card">
                    <div class="sfw-kpi-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="sfw-kpi-content">
                        <span class="sfw-kpi-value"><?php echo esc_html( number_format( $analytics['unique_visitors'] ) ); ?></span>
                        <span class="sfw-kpi-label">Unique Visitors</span>
                    </div>
                </div>
                <div class="sfw-kpi-card">
                    <div class="sfw-kpi-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="sfw-kpi-content">
                        <span class="sfw-kpi-value"><?php echo esc_html( $analytics['today_clicks'] ); ?></span>
                        <span class="sfw-kpi-label">Today's Clicks</span>
                    </div>
                </div>
                <div class="sfw-kpi-card">
                    <div class="sfw-kpi-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="sfw-kpi-content">
                        <span class="sfw-kpi-value"><?php echo esc_html( $analytics['conversion_rate'] ); ?>%</span>
                        <span class="sfw-kpi-label">Conversion Rate</span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="sfw-charts-grid">
                <!-- Clicks Over Time -->
                <div class="sfw-chart-card sfw-chart-wide">
                    <h3>Clicks Over Time</h3>
                    <canvas id="sfw-clicks-chart" height="300"></canvas>
                </div>

                <!-- Device Breakdown -->
                <div class="sfw-chart-card">
                    <h3>Device Breakdown</h3>
                    <canvas id="sfw-device-chart" height="260"></canvas>
                </div>

                <!-- Lead Status -->
                <div class="sfw-chart-card">
                    <h3>Lead Status</h3>
                    <canvas id="sfw-status-chart" height="260"></canvas>
                </div>
            </div>

            <!-- Hourly Activity Heatmap -->
            <div class="sfw-chart-card" style="margin-bottom: 24px;">
                <h3>Hourly Activity</h3>
                <div class="sfw-heatmap">
                    <?php
                    $max_hour = max( 1, max( $hour_data ) );
                    for ( $h = 0; $h < 24; $h++ ) :
                        $intensity = $hour_data[ $h ] / $max_hour;
                        $bg_opacity = max( 0.08, $intensity );
                        ?>
                        <div class="sfw-heatmap-cell" title="<?php echo esc_attr( $hour_data[ $h ] . ' clicks at ' . gmdate( 'gA', mktime( $h ) ) ); ?>"
                             style="background: rgba(37, 211, 102, <?php echo esc_attr( $bg_opacity ); ?>);">
                            <span class="sfw-heatmap-hour"><?php echo esc_html( gmdate( 'gA', mktime( $h ) ) ); ?></span>
                            <span class="sfw-heatmap-count"><?php echo esc_html( $hour_data[ $h ] ); ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Top Pages Table -->
            <div class="sfw-chart-card">
                <h3>Top Pages</h3>
                <?php if ( empty( $analytics['top_pages'] ) ) : ?>
                    <p class="sfw-no-data">No data available for this period.</p>
                <?php else : ?>
                    <table class="sfw-top-pages-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Page</th>
                                <th>Clicks</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $analytics['top_pages'] as $i => $page ) :
                                $share = $analytics['total_clicks'] > 0 ? round( ( $page->clicks / $analytics['total_clicks'] ) * 100, 1 ) : 0;
                                ?>
                                <tr>
                                    <td><?php echo esc_html( $i + 1 ); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url( $page->page_url ); ?>" target="_blank">
                                            <?php echo esc_html( $page->page_title ?: wp_parse_url( $page->page_url, PHP_URL_PATH ) ?: '/' ); ?>
                                        </a>
                                    </td>
                                    <td><strong><?php echo esc_html( $page->clicks ); ?></strong></td>
                                    <td>
                                        <div class="sfw-bar-wrapper">
                                            <div class="sfw-bar" style="width: <?php echo esc_attr( $share ); ?>%"></div>
                                            <span><?php echo esc_html( $share ); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Browser Breakdown -->
            <div class="sfw-chart-card" style="margin-top: 24px;">
                <h3>Browser Breakdown</h3>
                <?php if ( empty( $analytics['browsers'] ) ) : ?>
                    <p class="sfw-no-data">No data available for this period.</p>
                <?php else : ?>
                    <div class="sfw-browser-grid">
                        <?php foreach ( $analytics['browsers'] as $browser ) :
                            $pct = $analytics['total_clicks'] > 0 ? round( ( $browser->count / $analytics['total_clicks'] ) * 100, 1 ) : 0;
                            ?>
                            <div class="sfw-browser-item">
                                <span class="sfw-browser-name"><?php echo esc_html( $browser->browser ?: 'Unknown' ); ?></span>
                                <div class="sfw-bar-wrapper">
                                    <div class="sfw-bar" style="width: <?php echo esc_attr( $pct ); ?>%"></div>
                                </div>
                                <span class="sfw-browser-stats"><?php echo esc_html( $browser->count ); ?> (<?php echo esc_html( $pct ); ?>%)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chart.js (loaded from WP-bundled or CDN) -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
        <script>
        (function() {
            // Clicks Over Time - Area Chart
            const clicksCtx = document.getElementById('sfw-clicks-chart');
            if (clicksCtx) {
                new Chart(clicksCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?php echo wp_json_encode( $chart_labels ); ?>,
                        datasets: [{
                            label: 'Clicks',
                            data: <?php echo wp_json_encode( $chart_data ); ?>,
                            borderColor: '#25d366',
                            backgroundColor: 'rgba(37, 211, 102, 0.1)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#25d366',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1a1a2e',
                                titleColor: '#fff',
                                bodyColor: '#25d366',
                                cornerRadius: 8,
                                padding: 12
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { stepSize: 1, color: '#666' },
                                grid: { color: 'rgba(0,0,0,0.06)' }
                            },
                            x: {
                                ticks: { color: '#666', maxRotation: 45 },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Device Breakdown - Doughnut
            const deviceCtx = document.getElementById('sfw-device-chart');
            if (deviceCtx) {
                new Chart(deviceCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo wp_json_encode( $device_labels ); ?>,
                        datasets: [{
                            data: <?php echo wp_json_encode( $device_data ); ?>,
                            backgroundColor: ['#25d366', '#128c7e', '#075e54'],
                            borderWidth: 3,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' }
                            }
                        }
                    }
                });
            }

            // Status Breakdown - Doughnut
            const statusCtx = document.getElementById('sfw-status-chart');
            if (statusCtx) {
                new Chart(statusCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: <?php echo wp_json_encode( $status_labels ); ?>,
                        datasets: [{
                            data: <?php echo wp_json_encode( $status_data ); ?>,
                            backgroundColor: <?php echo wp_json_encode( $status_colors ); ?>,
                            borderWidth: 3,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { padding: 16, usePointStyle: true, pointStyle: 'circle' }
                            }
                        }
                    }
                });
            }
        })();
        </script>
        <?php
    }
}

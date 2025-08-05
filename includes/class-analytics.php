<?php
/**
 * Readinizer Pro Analytics Class
 * 
 * @package ReadinizerPro
 * @author WPezo
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ReadinizerPro_Analytics {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_readinizer_pro_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_readinizer_pro_export_analytics', array($this, 'ajax_export_analytics'));
        add_action('wp_ajax_readinizer_pro_track_engagement', array($this, 'ajax_track_engagement'));
    }

    /**
     * Create analytics tables
     */
    public function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'readinizer_pro_analytics';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            word_count mediumint(9) NOT NULL,
            reading_time mediumint(9) NOT NULL,
            views bigint(20) DEFAULT 0,
            completion_rate float DEFAULT 0,
            avg_time_spent mediumint(9) DEFAULT 0,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY date_created (date_created)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Track reading statistics
     */
    public function track_reading_stats($post_id, $word_count, $reading_time, $completion_rate = 0) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'readinizer_pro_analytics';

        // Check if record exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d",
            $post_id
        ));

        if ($existing) {
            // Update existing record
            $wpdb->update(
                $table_name,
                array(
                    'word_count' => $word_count,
                    'reading_time' => $reading_time,
                    'views' => $existing->views + 1,
                    'completion_rate' => $completion_rate > 0 ? $completion_rate : $existing->completion_rate
                ),
                array('post_id' => $post_id),
                array('%d', '%d', '%d', '%f'),
                array('%d')
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $table_name,
                array(
                    'post_id' => $post_id,
                    'word_count' => $word_count,
                    'reading_time' => $reading_time,
                    'views' => 1,
                    'completion_rate' => $completion_rate
                ),
                array('%d', '%d', '%d', '%d', '%f')
            );
        }
    }

    /**
     * Get analytics data
     */
    public function get_analytics_data() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'readinizer_pro_analytics';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return array(
                'totals' => (object) array('total_posts' => 0, 'avg_reading_time' => 0, 'avg_completion_rate' => 0, 'total_views' => 0),
                'top_content' => array(),
                'monthly_stats' => array()
            );
        }

        // Get total statistics
        $totals = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_posts,
                AVG(reading_time) as avg_reading_time,
                AVG(completion_rate) as avg_completion_rate,
                SUM(views) as total_views
            FROM $table_name
        ");

        // Get top content
        $top_content = $wpdb->get_results("
            SELECT 
                p.post_title,
                a.views,
                a.reading_time,
                a.completion_rate
            FROM $table_name a
            JOIN {$wpdb->posts} p ON a.post_id = p.ID
            WHERE p.post_status = 'publish'
            ORDER BY a.views DESC
            LIMIT 10
        ");

        // Get monthly statistics
        $monthly_stats = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(date_created, '%Y-%m') as month,
                SUM(views) as views,
                AVG(completion_rate) as engagement
            FROM $table_name
            WHERE date_created >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(date_created, '%Y-%m')
            ORDER BY month ASC
        ");

        return array(
            'totals' => $totals ?: (object) array('total_posts' => 0, 'avg_reading_time' => 0, 'avg_completion_rate' => 0, 'total_views' => 0),
            'top_content' => $top_content ?: array(),
            'monthly_stats' => $monthly_stats ?: array()
        );
    }

    /**
     * Render analytics dashboard
     */
    public function render_analytics_dashboard() {
        $data = $this->get_analytics_data();

        ob_start();
        ?>
        <div class="analytics-dashboard">
            <h2><?php _e('Reading Analytics Dashboard', 'readinizer-pro'); ?></h2>

            <!-- Summary Cards -->
            <div class="analytics-cards">
                <div class="analytics-card">
                    <div class="card-icon">📚</div>
                    <div class="card-content">
                        <h3><?php echo number_format($data['totals']->total_posts ?? 0); ?></h3>
                        <p><?php _e('Total Posts Tracked', 'readinizer-pro'); ?></p>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-icon">⏱️</div>
                    <div class="card-content">
                        <h3><?php echo number_format($data['totals']->avg_reading_time ?? 0, 1); ?> min</h3>
                        <p><?php _e('Average Reading Time', 'readinizer-pro'); ?></p>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-icon">📈</div>
                    <div class="card-content">
                        <h3><?php echo number_format($data['totals']->avg_completion_rate ?? 0, 1); ?>%</h3>
                        <p><?php _e('Average Completion Rate', 'readinizer-pro'); ?></p>
                    </div>
                </div>

                <div class="analytics-card">
                    <div class="card-icon">👁️</div>
                    <div class="card-content">
                        <h3><?php echo number_format($data['totals']->total_views ?? 0); ?></h3>
                        <p><?php _e('Total Views', 'readinizer-pro'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="analytics-charts">
                <div class="chart-container">
                    <h3><?php _e('Monthly Reading Engagement', 'readinizer-pro'); ?></h3>
                    <canvas id="monthlyEngagementChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Top Content Table -->
            <div class="top-content-section">
                <h3><?php _e('Top Performing Content', 'readinizer-pro'); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Post Title', 'readinizer-pro'); ?></th>
                            <th><?php _e('Views', 'readinizer-pro'); ?></th>
                            <th><?php _e('Reading Time', 'readinizer-pro'); ?></th>
                            <th><?php _e('Completion Rate', 'readinizer-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data['top_content'])): ?>
                            <?php foreach ($data['top_content'] as $content): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($content->post_title); ?></strong></td>
                                    <td><?php echo number_format($content->views); ?></td>
                                    <td><?php echo $content->reading_time; ?> min</td>
                                    <td><?php echo number_format($content->completion_rate, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4"><?php _e('No data available yet. Analytics will appear as your content gets views.', 'readinizer-pro'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Export Section -->
            <div class="analytics-export">
                <h3><?php _e('Export Analytics Data', 'readinizer-pro'); ?></h3>
                <p><?php _e('Download your reading analytics data in CSV format.', 'readinizer-pro'); ?></p>
                <button class="button button-primary" id="export-analytics">
                    <?php _e('Export to CSV', 'readinizer-pro'); ?>
                </button>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Monthly engagement chart
            const ctx = document.getElementById('monthlyEngagementChart');
            if (ctx && typeof Chart !== 'undefined') {
                const monthlyData = <?php echo json_encode($data['monthly_stats'] ?? array()); ?>;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: monthlyData.map(item => item.month),
                        datasets: [{
                            label: '<?php _e("Views", "readinizer-pro"); ?>',
                            data: monthlyData.map(item => item.views),
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: '<?php _e("Engagement %", "readinizer-pro"); ?>',
                            data: monthlyData.map(item => item.engagement),
                            borderColor: '#764ba2',
                            backgroundColor: 'rgba(118, 75, 162, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false,
                                },
                            }
                        }
                    }
                });
            }

            // Export functionality
            $('#export-analytics').on('click', function() {
                window.location.href = ajaxurl + '?action=readinizer_pro_export_analytics&nonce=' + '<?php echo wp_create_nonce("readinizer_pro_export"); ?>';
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX handler for tracking engagement
     */
    public function ajax_track_engagement() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'readinizer_pro_analytics')) {
            wp_die(__('Security check failed.', 'readinizer-pro'));
        }

        $post_id = intval($_POST['post_id']);
        $progress = floatval($_POST['progress']);
        $time_spent = intval($_POST['time_spent']);
        $completed = isset($_POST['completed']) && $_POST['completed'] === 'true';
        $total_words = intval($_POST['total_words']);
        $estimated_time = intval($_POST['estimated_time']);

        if ($post_id > 0) {
            $this->track_reading_stats($post_id, $total_words, $estimated_time, $progress);
        }

        wp_send_json_success(array('message' => 'Engagement tracked'));
    }

    /**
     * AJAX handler for getting analytics
     */
    public function ajax_get_analytics() {
        check_ajax_referer('readinizer_pro_analytics', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $data = $this->get_analytics_data();
        wp_send_json_success($data);
    }

    /**
     * AJAX handler for exporting analytics
     */
    public function ajax_export_analytics() {
        if (!wp_verify_nonce($_GET['nonce'], 'readinizer_pro_export')) {
            wp_die(__('Security check failed.'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'readinizer_pro_analytics';

        $results = $wpdb->get_results("
            SELECT 
                p.post_title,
                a.word_count,
                a.reading_time,
                a.views,
                a.completion_rate,
                a.date_created
            FROM $table_name a
            JOIN {$wpdb->posts} p ON a.post_id = p.ID
            WHERE p.post_status = 'publish'
            ORDER BY a.views DESC
        ", ARRAY_A);

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="readinizer-pro-analytics-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($output, array(
            'Post Title',
            'Word Count', 
            'Reading Time (minutes)',
            'Views',
            'Completion Rate (%)',
            'Date Created'
        ));

        // Add data rows
        foreach ($results as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}

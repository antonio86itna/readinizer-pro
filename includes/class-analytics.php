<?php
/**
 * Readinizer Pro Analytics Class
 *
 * @package ReadinizerPro
 * @author WPezo
 * @since 2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Readinizer Pro Analytics Handler
 */
class ReadinizerPro_Analytics {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_readinizer_pro_track_engagement', array( $this, 'track_engagement' ) );
		add_action( 'wp_ajax_nopriv_readinizer_pro_track_engagement', array( $this, 'track_engagement' ) );
		add_action( 'wp_ajax_readinizer_pro_export_analytics', array( $this, 'export_analytics' ) );
	}

	/**
	 * Create analytics tables
	 *
	 * @return void
	 */
        public function create_tables() {
                global $wpdb;

		$table_name = $wpdb->prefix . 'readinizer_analytics';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			user_id bigint(20) DEFAULT NULL,
			progress tinyint(3) NOT NULL DEFAULT 0,
			time_spent int(11) NOT NULL DEFAULT 0,
			completed tinyint(1) NOT NULL DEFAULT 0,
			total_words int(11) DEFAULT NULL,
			estimated_time int(11) DEFAULT NULL,
			session_id varchar(32) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY user_id (user_id),
			KEY completed (completed),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta( $sql );
        }

       /**
        * Track basic reading stats
        *
        * @param int $post_id Post ID.
        * @param int $word_count Total words in the post.
        * @param int $reading_time_minutes Estimated reading time in minutes.
        * @return void
        */
       public function track_reading_stats( $post_id, $word_count, $reading_time_minutes ) {
               global $wpdb;

               $post_id             = intval( $post_id );
               $word_count          = intval( $word_count );
               $reading_time_minutes = intval( $reading_time_minutes );

               if ( $post_id <= 0 ) {
                       return;
               }

               $user_id    = get_current_user_id();
               $session_id = $this->get_session_id();
               $ip_address = $this->get_client_ip();
               $user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' );

               $table_name = $wpdb->prefix . 'readinizer_analytics';

               // Check for an existing record for this session and post today.
               $existing = $wpdb->get_var(
                       $wpdb->prepare(
                               "SELECT id FROM $table_name WHERE post_id = %d AND session_id = %s AND DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 1",
                               $post_id,
                               $session_id
                       )
               );

               if ( $existing ) {
                       $wpdb->update(
                               $table_name,
                               array(
                                       'total_words'    => $word_count,
                                       'estimated_time' => $reading_time_minutes,
                                       'updated_at'     => current_time( 'mysql' ),
                               ),
                               array( 'id' => $existing ),
                               array( '%d', '%d', '%s' ),
                               array( '%d' )
                       );
               } else {
                       $wpdb->insert(
                               $table_name,
                               array(
                                       'post_id'        => $post_id,
                                       'user_id'        => $user_id ?: null,
                                       'total_words'    => $word_count,
                                       'estimated_time' => $reading_time_minutes,
                                       'session_id'     => $session_id,
                                       'ip_address'     => $ip_address,
                                       'user_agent'     => $user_agent,
                                       'created_at'     => current_time( 'mysql' ),
                               ),
                               array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
                       );
               }
       }

	/**
	 * Track reading engagement via AJAX
	 *
	 * @return void
	 */
	public function track_engagement() {
		// Verify nonce for security.
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'readinizer_pro_analytics' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		global $wpdb;

		$post_id        = intval( $_POST['post_id'] ?? 0 );
		$progress       = intval( $_POST['progress'] ?? 0 );
		$time_spent     = intval( $_POST['time_spent'] ?? 0 );
		$completed      = intval( $_POST['completed'] ?? 0 );
		$total_words    = intval( $_POST['total_words'] ?? 0 );
		$estimated_time = intval( $_POST['estimated_time'] ?? 0 );

		if ( $post_id <= 0 ) {
			wp_send_json_error( 'Invalid post ID' );
		}

		$user_id    = get_current_user_id();
		$session_id = $this->get_session_id();
		$ip_address = $this->get_client_ip();
		$user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' );

		$table_name = $wpdb->prefix . 'readinizer_analytics';

		// Try to update existing record for this session.
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $table_name WHERE post_id = %d AND session_id = %s AND DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 1",
				$post_id,
				$session_id
			)
		);

		if ( $existing ) {
			// Update existing record.
			$wpdb->update(
				$table_name,
				array(
					'progress'       => max( $progress, $wpdb->get_var( $wpdb->prepare( "SELECT progress FROM $table_name WHERE id = %d", $existing ) ) ),
					'time_spent'     => $time_spent,
					'completed'      => $completed,
					'total_words'    => $total_words,
					'estimated_time' => $estimated_time,
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'id' => $existing ),
				array( '%d', '%d', '%d', '%d', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			// Insert new record.
			$wpdb->insert(
				$table_name,
				array(
					'post_id'        => $post_id,
					'user_id'        => $user_id ?: null,
					'progress'       => $progress,
					'time_spent'     => $time_spent,
					'completed'      => $completed,
					'total_words'    => $total_words,
					'estimated_time' => $estimated_time,
					'session_id'     => $session_id,
					'ip_address'     => $ip_address,
					'user_agent'     => $user_agent,
					'created_at'     => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
			);
		}

		wp_send_json_success( 'Engagement tracked' );
	}

	/**
	 * Render analytics dashboard
	 *
	 * @return string
	 */
	public function render_analytics_dashboard() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'readinizer_analytics';

		// Get basic stats.
		$total_views      = $wpdb->get_var( "SELECT COUNT(DISTINCT session_id, post_id) FROM $table_name" ) ?: 0;
		$total_completed  = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE completed = 1" ) ?: 0;
		$avg_time         = $wpdb->get_var( "SELECT AVG(time_spent) FROM $table_name WHERE time_spent > 0" ) ?: 0;
		$completion_rate  = $total_views > 0 ? round( ( $total_completed / $total_views ) * 100, 1 ) : 0;

		// Get top content.
		$top_content = $wpdb->get_results(
			"SELECT p.ID, p.post_title, COUNT(DISTINCT a.session_id) as views, 
			AVG(a.progress) as avg_progress, AVG(a.time_spent) as avg_time
			FROM $table_name a 
			JOIN {$wpdb->posts} p ON a.post_id = p.ID 
			WHERE p.post_status = 'publish'
			GROUP BY p.ID 
			ORDER BY views DESC 
			LIMIT 10"
		);

		ob_start();
		?>
		<div class="analytics-dashboard">
			<h2><?php esc_html_e( 'Reading Analytics Dashboard', 'readinizer-pro' ); ?></h2>

			<!-- Analytics Cards -->
			<div class="analytics-cards">
				<div class="analytics-card">
					<div class="card-icon">📊</div>
					<div class="card-content">
						<h3><?php echo esc_html( number_format( $total_views ) ); ?></h3>
						<p><?php esc_html_e( 'Total Views', 'readinizer-pro' ); ?></p>
					</div>
				</div>

				<div class="analytics-card">
					<div class="card-icon">✅</div>
					<div class="card-content">
						<h3><?php echo esc_html( number_format( $total_completed ) ); ?></h3>
						<p><?php esc_html_e( 'Completed Reads', 'readinizer-pro' ); ?></p>
					</div>
				</div>

				<div class="analytics-card">
					<div class="card-icon">⏱️</div>
					<div class="card-content">
						<h3><?php echo esc_html( number_format( $avg_time ) ); ?>s</h3>
						<p><?php esc_html_e( 'Average Time', 'readinizer-pro' ); ?></p>
					</div>
				</div>

				<div class="analytics-card">
					<div class="card-icon">🎯</div>
					<div class="card-content">
						<h3><?php echo esc_html( $completion_rate ); ?>%</h3>
						<p><?php esc_html_e( 'Completion Rate', 'readinizer-pro' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Charts -->
			<div class="analytics-charts">
				<div class="chart-container">
					<h3><?php esc_html_e( 'Monthly Engagement Trends', 'readinizer-pro' ); ?></h3>
					<canvas id="monthlyEngagementChart" width="400" height="200"></canvas>
				</div>
			</div>

			<!-- Top Content -->
			<div class="top-content-section">
				<h3><?php esc_html_e( 'Top Performing Content', 'readinizer-pro' ); ?></h3>

				<?php if ( $top_content ) : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Post Title', 'readinizer-pro' ); ?></th>
								<th><?php esc_html_e( 'Views', 'readinizer-pro' ); ?></th>
								<th><?php esc_html_e( 'Avg Progress', 'readinizer-pro' ); ?></th>
								<th><?php esc_html_e( 'Avg Time', 'readinizer-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $top_content as $post ) : ?>
								<tr>
									<td>
										<strong>
											<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
												<?php echo esc_html( $post->post_title ); ?>
											</a>
										</strong>
									</td>
									<td><?php echo esc_html( number_format( $post->views ) ); ?></td>
									<td><?php echo esc_html( number_format( $post->avg_progress, 1 ) ); ?>%</td>
									<td><?php echo esc_html( number_format( $post->avg_time ) ); ?>s</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><?php esc_html_e( 'No analytics data available yet. Data will appear as visitors read your content.', 'readinizer-pro' ); ?></p>
				<?php endif; ?>

				<div style="margin-top: 20px;">
					<button class="button button-primary" id="export-analytics">
						<?php esc_html_e( 'Export CSV', 'readinizer-pro' ); ?>
					</button>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('#export-analytics').on('click', function() {
				window.location.href = ajaxurl + '?action=readinizer_pro_export_analytics&nonce=' + readinizerProAdmin.nonces.export;
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Export analytics data as CSV
	 *
	 * @return void
	 */
	public function export_analytics() {
		check_ajax_referer( 'readinizer_pro_export', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'readinizer_analytics';

		$results = $wpdb->get_results(
			"SELECT a.*, p.post_title 
			FROM $table_name a 
			LEFT JOIN {$wpdb->posts} p ON a.post_id = p.ID 
			ORDER BY a.created_at DESC",
			ARRAY_A
		);

		if ( empty( $results ) ) {
			wp_die( __( 'No data to export.', 'readinizer-pro' ) );
		}

		$filename = 'readinizer-analytics-' . date( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$output = fopen( 'php://output', 'w' );

		// Write CSV header.
		$header = array(
			'ID',
			'Post Title',
			'Post ID',
			'User ID',
			'Progress %',
			'Time Spent (s)',
			'Completed',
			'Total Words',
			'Estimated Time',
			'Session ID',
			'IP Address',
			'Created At',
		);

		fputcsv( $output, $header );

		// Write data rows.
		foreach ( $results as $row ) {
			$csv_row = array(
				$row['id'],
				$row['post_title'] ?: 'Unknown',
				$row['post_id'],
				$row['user_id'] ?: 'Guest',
				$row['progress'],
				$row['time_spent'],
				$row['completed'] ? 'Yes' : 'No',
				$row['total_words'],
				$row['estimated_time'],
				$row['session_id'],
				$row['ip_address'],
				$row['created_at'],
			);

			fputcsv( $output, $csv_row );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Get session ID
	 *
	 * @return string
	 */
	private function get_session_id() {
		if ( ! session_id() ) {
			session_start();
		}
		return session_id();
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );

		foreach ( $ip_keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( array_map( 'trim', explode( ',', $_SERVER[ $key ] ) ) as $ip ) {
					if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
						return $ip;
					}
				}
			}
		}

		return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
	}
}

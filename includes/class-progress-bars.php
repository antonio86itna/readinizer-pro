<?php
/**
 * Readinizer Pro Progress Bars Class
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
 * Readinizer Pro Progress Bars Handler
 */
class ReadinizerPro_Progress_Bars {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'render_progress_bar' ) );
		add_action( 'wp_ajax_readinizer_pro_save_progress_settings', array( $this, 'ajax_save_progress_settings' ) );
	}

	/**
	 * Render progress bar settings page
	 *
	 * @return string
	 */
	public function render_progress_settings() {
		$options = get_option( 'readinizer_pro_options', array() );

		ob_start();
		?>
		<div class="progress-bars-settings">
			<h2><?php esc_html_e( 'Reading Progress Bars', 'readinizer-pro' ); ?></h2>
			<p><?php esc_html_e( 'Configure visual reading progress indicators to enhance user engagement.', 'readinizer-pro' ); ?></p>

			<form class="progress-form" method="post" action="options.php">
				<?php settings_fields( 'readinizer_pro_settings' ); ?>

				<!-- Enable Progress Bars -->
				<div class="setting-group">
					<h3><?php esc_html_e( 'General Settings', 'readinizer-pro' ); ?></h3>

					<label class="setting-label">
						<input type="checkbox" id="enable_progress_bars" name="readinizer_pro_options[enable_progress_bars]" value="1" <?php checked( $options['enable_progress_bars'] ?? true ); ?>>
						<?php esc_html_e( 'Enable Reading Progress Bars', 'readinizer-pro' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'Show visual reading progress indicators on posts and pages.', 'readinizer-pro' ); ?></p>
				</div>

				<!-- Progress Bar Style -->
				<div class="setting-group">
					<h3><?php esc_html_e( 'Progress Bar Style', 'readinizer-pro' ); ?></h3>

					<div class="style-options">
						<div class="style-option">
							<input type="radio" id="style_linear" name="readinizer_pro_options[progress_bar_style]" value="linear" 
								<?php checked( $options['progress_bar_style'] ?? 'linear', 'linear' ); ?>>
							<label for="style_linear" class="style-card">
								<div class="style-preview">
									<div class="linear-preview">
										<div class="linear-bar" style="width: 100%; height: 4px; background: #ddd; border-radius: 2px;">
											<div style="width: 45%; height: 100%; background: #0073aa; border-radius: 2px;"></div>
										</div>
									</div>
								</div>
								<h4><?php esc_html_e( 'Linear Bar', 'readinizer-pro' ); ?></h4>
								<p><?php esc_html_e( 'Horizontal progress bar at top or bottom', 'readinizer-pro' ); ?></p>
							</label>
						</div>

						<div class="style-option">
							<input type="radio" id="style_circular" name="readinizer_pro_options[progress_bar_style]" value="circular"
								<?php checked( $options['progress_bar_style'] ?? 'linear', 'circular' ); ?>>
							<label for="style_circular" class="style-card">
								<div class="style-preview">
									<div class="circular-preview">
										<div class="circular-progress" style="width: 40px; height: 40px; border: 3px solid #ddd; border-top-color: #0073aa; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">75%</div>
									</div>
								</div>
								<h4><?php esc_html_e( 'Circular Progress', 'readinizer-pro' ); ?></h4>
								<p><?php esc_html_e( 'Circular progress indicator with percentage', 'readinizer-pro' ); ?></p>
							</label>
						</div>

						<div class="style-option">
							<input type="radio" id="style_floating" name="readinizer_pro_options[progress_bar_style]" value="floating"
								<?php checked( $options['progress_bar_style'] ?? 'linear', 'floating' ); ?>>
							<label for="style_floating" class="style-card">
								<div class="style-preview">
									<div class="floating-preview">
										<div class="floating-bubble" style="background: #0073aa; color: white; padding: 4px 8px; border-radius: 12px; font-size: 10px; display: inline-block;">📖 45%</div>
									</div>
								</div>
								<h4><?php esc_html_e( 'Floating Bubble', 'readinizer-pro' ); ?></h4>
								<p><?php esc_html_e( 'Floating progress bubble with reading stats', 'readinizer-pro' ); ?></p>
							</label>
						</div>
					</div>
				</div>

				<!-- Position Settings -->
				<div class="setting-group">
					<h3><?php esc_html_e( 'Position & Appearance', 'readinizer-pro' ); ?></h3>

					<div class="setting-row">
						<label for="progress_position"><?php esc_html_e( 'Position', 'readinizer-pro' ); ?></label>
						<select id="progress_position" name="readinizer_pro_options[progress_bar_position]">
							<option value="top" <?php selected( $options['progress_bar_position'] ?? 'top', 'top' ); ?>>
								<?php esc_html_e( 'Top of page', 'readinizer-pro' ); ?>
							</option>
							<option value="bottom" <?php selected( $options['progress_bar_position'] ?? 'top', 'bottom' ); ?>>
								<?php esc_html_e( 'Bottom of page', 'readinizer-pro' ); ?>
							</option>
							<option value="floating-left" <?php selected( $options['progress_bar_position'] ?? 'top', 'floating-left' ); ?>>
								<?php esc_html_e( 'Floating left', 'readinizer-pro' ); ?>
							</option>
							<option value="floating-right" <?php selected( $options['progress_bar_position'] ?? 'top', 'floating-right' ); ?>>
								<?php esc_html_e( 'Floating right', 'readinizer-pro' ); ?>
							</option>
						</select>
					</div>

					<div class="setting-row">
						<label for="progress_color"><?php esc_html_e( 'Progress Color', 'readinizer-pro' ); ?></label>
						<input type="color" id="progress_color" name="readinizer_pro_options[progress_bar_color]" 
							value="<?php echo esc_attr( $options['progress_bar_color'] ?? '#0073aa' ); ?>">
					</div>

					<div class="setting-row">
						<label for="progress_thickness"><?php esc_html_e( 'Bar Thickness (px)', 'readinizer-pro' ); ?></label>
						<input type="range" id="progress_thickness" name="readinizer_pro_options[progress_thickness]" 
							min="1" max="10" value="<?php echo esc_attr( $options['progress_thickness'] ?? '4' ); ?>" 
							class="range-input">
						<span class="range-value"><?php echo esc_attr( $options['progress_thickness'] ?? '4' ); ?>px</span>
					</div>
				</div>

				<!-- Animation Settings -->
				<div class="setting-group">
					<h3><?php esc_html_e( 'Animation & Behavior', 'readinizer-pro' ); ?></h3>

					<div class="setting-row">
						<label for="progress_animation"><?php esc_html_e( 'Animation Style', 'readinizer-pro' ); ?></label>
						<select id="progress_animation" name="readinizer_pro_options[progress_animation]">
							<option value="smooth" <?php selected( $options['progress_animation'] ?? 'smooth', 'smooth' ); ?>>
								<?php esc_html_e( 'Smooth', 'readinizer-pro' ); ?>
							</option>
							<option value="stepped" <?php selected( $options['progress_animation'] ?? 'smooth', 'stepped' ); ?>>
								<?php esc_html_e( 'Stepped', 'readinizer-pro' ); ?>
							</option>
							<option value="bouncy" <?php selected( $options['progress_animation'] ?? 'smooth', 'bouncy' ); ?>>
								<?php esc_html_e( 'Bouncy', 'readinizer-pro' ); ?>
							</option>
							<option value="none" <?php selected( $options['progress_animation'] ?? 'smooth', 'none' ); ?>>
								<?php esc_html_e( 'No animation', 'readinizer-pro' ); ?>
							</option>
						</select>
					</div>

					<div class="setting-row">
						<label class="setting-label">
							<input type="checkbox" id="show_percentage" name="readinizer_pro_options[show_percentage]" value="1"
								<?php checked( $options['show_percentage'] ?? true ); ?>>
							<?php esc_html_e( 'Show percentage text', 'readinizer-pro' ); ?>
						</label>
					</div>

					<div class="setting-row">
						<label class="setting-label">
							<input type="checkbox" id="show_reading_time_in_progress" name="readinizer_pro_options[show_reading_time_in_progress]" value="1"
								<?php checked( $options['show_reading_time_in_progress'] ?? false ); ?>>
							<?php esc_html_e( 'Show estimated time remaining', 'readinizer-pro' ); ?>
						</label>
					</div>

					<div class="setting-row">
						<label class="setting-label">
							<input type="checkbox" id="hide_when_complete" name="readinizer_pro_options[hide_when_complete]" value="1"
								<?php checked( $options['hide_when_complete'] ?? false ); ?>>
							<?php esc_html_e( 'Hide when reading is complete', 'readinizer-pro' ); ?>
						</label>
					</div>
				</div>

				<!-- Live Demo Preview -->
				<div class="setting-group">
					<h3><?php esc_html_e( 'Live Demo', 'readinizer-pro' ); ?></h3>
					<div id="progress-demo" class="progress-demo-container">
						<div class="demo-content">
							<h4><?php esc_html_e( 'Sample Article Content - Scroll to Test', 'readinizer-pro' ); ?></h4>
							<p><?php esc_html_e( 'This is a preview of how the progress bar will look on your posts. The progress indicator will show reading progress in real-time as you scroll through this demo content.', 'readinizer-pro' ); ?></p>
							<div class="fake-content">
								<?php for ( $i = 0; $i < 10; $i++ ) : ?>
									<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.', 'readinizer-pro' ); ?></p>
								<?php endfor; ?>
							</div>
						</div>
						<div id="demo-progress-bar"></div>
					</div>
				</div>

				<!-- Post Type Settings -->
				<div class="setting-group">
					<h3><?php esc_html_e( 'Post Type Settings', 'readinizer-pro' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Choose which post types should display reading progress bars.', 'readinizer-pro' ); ?></p>

					<?php
					$post_types         = get_post_types( array( 'public' => true ), 'objects' );
					$enabled_post_types = $options['progress_post_types'] ?? array( 'post' );

					foreach ( $post_types as $post_type ) :
						?>
						<label class="setting-label">
							<input type="checkbox" name="readinizer_pro_options[progress_post_types][]" value="<?php echo esc_attr( $post_type->name ); ?>"
								<?php checked( in_array( $post_type->name, $enabled_post_types, true ) ); ?>>
							<?php echo esc_html( $post_type->label ); ?>
						</label>
					<?php endforeach; ?>
				</div>

				<div class="form-actions">
					<?php submit_button( __( 'Save Progress Bar Settings', 'readinizer-pro' ), 'primary', 'submit', false ); ?>
					<button type="button" class="button" id="preview-progress">
						<?php esc_html_e( 'Update Demo', 'readinizer-pro' ); ?>
					</button>
					<button type="button" class="button" id="reset-progress-settings">
						<?php esc_html_e( 'Reset to Defaults', 'readinizer-pro' ); ?>
					</button>
				</div>
			</form>
		</div>

		<style>
		.progress-demo-container {
			background: #f8f9fa;
			border-radius: 12px;
			padding: 20px;
			border: 1px solid #e8ecf0;
			position: relative;
			max-height: 400px;
			overflow-y: auto;
			margin-top: 15px;
		}

		.demo-content {
			padding: 20px 0;
		}

		.fake-content p {
			margin-bottom: 20px;
			line-height: 1.6;
			text-align: justify;
		}

		#demo-progress-bar {
			position: sticky;
			top: 0;
			z-index: 10;
		}

		.setting-group {
			background: white;
			padding: 25px;
			border-radius: 12px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
			border: 1px solid #e8ecf0;
			margin-bottom: 25px;
		}

		.setting-group h3 {
			margin: 0 0 20px 0;
			color: #2c3e50;
			font-size: 20px;
			font-weight: 600;
		}

		.setting-label {
			display: flex;
			align-items: center;
			gap: 10px;
			font-weight: 500;
			color: #2c3e50;
			cursor: pointer;
			margin-bottom: 15px;
		}

		.setting-row {
			display: flex;
			align-items: center;
			gap: 15px;
			margin-bottom: 20px;
		}

		.setting-row label {
			min-width: 150px;
			font-weight: 500;
			color: #2c3e50;
		}

		.setting-row select,
		.setting-row input[type="color"] {
			padding: 8px 12px;
			border: 2px solid #e8ecf0;
			border-radius: 6px;
			transition: border-color 0.3s ease;
		}

		.style-options {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
			gap: 20px;
		}

		.style-option input[type="radio"] {
			display: none;
		}

		.style-card {
			display: block;
			padding: 20px;
			border: 2px solid #e8ecf0;
			border-radius: 12px;
			cursor: pointer;
			transition: all 0.3s ease;
			text-align: center;
		}

		.style-card:hover {
			border-color: #667eea;
			transform: translateY(-2px);
		}

		.style-option input[type="radio"]:checked + .style-card {
			border-color: #667eea;
			background: rgba(102, 126, 234, 0.05);
		}

		.style-preview {
			height: 60px;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-bottom: 15px;
		}

		.range-input {
			flex: 1;
		}

		.range-value {
			min-width: 50px;
			font-weight: 500;
			color: #667eea;
		}

		.form-actions {
			margin-top: 25px;
			display: flex;
			gap: 15px;
			flex-wrap: wrap;
		}
		</style>

		<script>
		jQuery(document).ready(function($) {
			// Demo progress bar functionality
			function updateDemo() {
				const style = $('input[name="readinizer_pro_options[progress_bar_style]"]:checked').val();
				const position = $('#progress_position').val();
				const color = $('#progress_color').val();
				const thickness = $('#progress_thickness').val();
				const showPercentage = $('#show_percentage').is(':checked');

				let demoHtml = '';

				switch(style) {
					case 'linear':
						demoHtml = `<div class="demo-progress-linear" style="background: rgba(0,0,0,0.1); height: ${thickness}px; position: relative; border-radius: 2px; margin: 10px 0;"><div class="demo-progress-fill" style="width: 65%; height: 100%; background: ${color}; border-radius: 2px; transition: width 0.3s ease;"></div></div>`;
						break;
					case 'circular':
						demoHtml = `<div class="demo-progress-circular" style="width: 60px; height: 60px; border: 3px solid rgba(0,0,0,0.1); border-top-color: ${color}; border-radius: 50%; position: relative; margin: 10px auto;"><span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px; font-weight: bold; color: ${color};">${showPercentage ? '65%' : ''}</span></div>`;
						break;
					case 'floating':
						demoHtml = `<div class="demo-progress-floating" style="background: ${color}; color: white; padding: 8px 12px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); margin: 10px;">📖 <span>${showPercentage ? '65%' : 'Reading...'}</span></div>`;
						break;
				}

				$('#demo-progress-bar').html(demoHtml);
			}

			// Update demo on change
			$('input, select').on('change', updateDemo);
			$('#progress_thickness').on('input', function() {
				$(this).next('.range-value').text($(this).val() + 'px');
				updateDemo();
			});

			// Initial demo
			updateDemo();

			// Reset settings
			$('#reset-progress-settings').on('click', function() {
				if (confirm('Are you sure you want to reset all progress bar settings to defaults?')) {
					$('#enable_progress_bars').prop('checked', true);
					$('input[name="readinizer_pro_options[progress_bar_style]"][value="linear"]').prop('checked', true);
					$('#progress_position').val('top');
					$('#progress_color').val('#0073aa');
					$('#progress_thickness').val('4');
					$('#progress_animation').val('smooth');
					$('#show_percentage').prop('checked', true);

					updateDemo();
				}
			});
		});
		</script>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render progress bar on frontend - COMPLETELY FIXED
	 *
	 * @return void
	 */
	public function render_progress_bar() {
		$options = get_option( 'readinizer_pro_options', array() );

		if ( ! isset( $options['enable_progress_bars'] ) || ! $options['enable_progress_bars'] ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post_type          = get_post_type();
		$enabled_post_types = $options['progress_post_types'] ?? array( 'post' );

		if ( ! in_array( $post_type, $enabled_post_types, true ) ) {
			return;
		}

		$style             = $options['progress_bar_style'] ?? 'linear';
		$position          = $options['progress_bar_position'] ?? 'top';
		$color             = $options['progress_bar_color'] ?? '#0073aa';
		$thickness         = $options['progress_thickness'] ?? '4';
		$animation         = $options['progress_animation'] ?? 'smooth';
		$show_percentage   = $options['show_percentage'] ?? true;
		$show_time_remaining = $options['show_reading_time_in_progress'] ?? false;
		$hide_when_complete = $options['hide_when_complete'] ?? false;

		$classes = array(
			'readinizer-progress-' . $style,
			'readinizer-progress-' . $position,
			'readinizer-progress-' . $animation,
		);

		if ( $hide_when_complete ) {
			$classes[] = 'hide-when-complete';
		}

		?>
		<div id="readinizer-pro-progress" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" 
			 style="--progress-color: <?php echo esc_attr( $color ); ?>; --progress-thickness: <?php echo esc_attr( $thickness ); ?>px;"
			 data-post-id="<?php echo esc_attr( get_the_ID() ); ?>">
			<?php if ( 'linear' === $style ) : ?>
				<div class="progress-bar"></div>
				<?php if ( $show_percentage ) : ?>
					<div class="progress-percentage">0%</div>
				<?php endif; ?>
			<?php elseif ( 'circular' === $style ) : ?>
				<div class="circular-progress">
					<svg class="circular-chart" viewBox="0 0 36 36">
						<path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
						<path class="circle" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
					</svg>
					<?php if ( $show_percentage ) : ?>
						<div class="percentage">0%</div>
					<?php endif; ?>
				</div>
			<?php elseif ( 'floating' === $style ) : ?>
				<div class="floating-progress">
					<span class="progress-icon">📖</span>
					<?php if ( $show_percentage ) : ?>
						<span class="progress-text">0%</span>
					<?php endif; ?>
					<?php if ( $show_time_remaining ) : ?>
						<span class="time-remaining"></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * AJAX handler for saving progress settings
	 *
	 * @return void
	 */
	public function ajax_save_progress_settings() {
		check_ajax_referer( 'readinizer_pro_progress', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		$settings = wp_unslash( $_POST['settings'] );
		parse_str( $settings, $parsed_settings );

		$options = get_option( 'readinizer_pro_options', array() );

		// Update progress bar settings.
		$options['enable_progress_bars']           = isset( $parsed_settings['readinizer_pro_options']['enable_progress_bars'] );
		$options['progress_bar_style']             = sanitize_text_field( $parsed_settings['readinizer_pro_options']['progress_bar_style'] ?? 'linear' );
		$options['progress_bar_position']          = sanitize_text_field( $parsed_settings['readinizer_pro_options']['progress_bar_position'] ?? 'top' );
		$options['progress_bar_color']             = sanitize_hex_color( $parsed_settings['readinizer_pro_options']['progress_bar_color'] ?? '#0073aa' );
		$options['progress_thickness']             = intval( $parsed_settings['readinizer_pro_options']['progress_thickness'] ?? 4 );
		$options['progress_animation']             = sanitize_text_field( $parsed_settings['readinizer_pro_options']['progress_animation'] ?? 'smooth' );
		$options['show_percentage']                = isset( $parsed_settings['readinizer_pro_options']['show_percentage'] );
		$options['show_reading_time_in_progress']  = isset( $parsed_settings['readinizer_pro_options']['show_reading_time_in_progress'] );
		$options['hide_when_complete']             = isset( $parsed_settings['readinizer_pro_options']['hide_when_complete'] );
		$options['progress_post_types']            = isset( $parsed_settings['readinizer_pro_options']['progress_post_types'] ) ? array_map( 'sanitize_text_field', $parsed_settings['readinizer_pro_options']['progress_post_types'] ) : array();

		update_option( 'readinizer_pro_options', $options );

		wp_send_json_success( array( 'message' => __( 'Progress bar settings saved successfully!', 'readinizer-pro' ) ) );
	}
}

<?php
/**
 * Readinizer Pro Templates Class
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
 * Readinizer Pro Templates Handler
 */
class ReadinizerPro_Templates {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_readinizer_pro_save_template', array( $this, 'ajax_save_template' ) );
		add_action( 'wp_ajax_readinizer_pro_delete_template', array( $this, 'ajax_delete_template' ) );
		add_action( 'wp_ajax_readinizer_pro_preview_template', array( $this, 'ajax_preview_template' ) );
		add_action( 'wp_ajax_readinizer_pro_save_assignment', array( $this, 'ajax_save_assignment' ) );
		add_action( 'wp_ajax_readinizer_pro_use_template', array( $this, 'ajax_use_template' ) );
	}

	/**
	 * Get available templates with REAL preview styles
	 *
	 * @return array
	 */
	public function get_templates() {
		$default_templates = array(
			'minimal'  => array(
				'name'        => __( 'Minimal', 'readinizer-pro' ),
				'description' => __( 'Clean text-only display', 'readinizer-pro' ),
				'html'        => '<div class="readinizer-minimal">{icon} {text}</div>',
				'css'         => '.readinizer-minimal { color: #666; font-size: 13px; margin: 15px 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }',
				'is_default'  => true,
			),
			'modern'   => array(
				'name'        => __( 'Modern', 'readinizer-pro' ),
				'description' => __( 'Stylish with gradients and shadows', 'readinizer-pro' ),
				'html'        => '<div class="readinizer-modern">{icon} {text}</div>',
				'css'         => '.readinizer-modern { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #495057; padding: 10px 16px; border-radius: 6px; border-left: 3px solid #007cba; box-shadow: 0 2px 4px rgba(0,0,0,0.05); font-weight: 500; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; display: inline-flex; align-items: center; gap: 8px; }',
				'is_default'  => true,
			),
			'badge'    => array(
				'name'        => __( 'Badge', 'readinizer-pro' ),
				'description' => __( 'Compact badge-style display', 'readinizer-pro' ),
				'html'        => '<div class="readinizer-badge">{icon} {text}</div>',
				'css'         => '.readinizer-badge { background: #007cba; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }',
				'is_default'  => true,
			),
			'card'     => array(
				'name'        => __( 'Card Pro', 'readinizer-pro' ),
				'description' => __( 'Full card with icon and detailed stats', 'readinizer-pro' ),
				'html'        => '<div class="readinizer-card"><div class="card-header">{icon} Reading Info</div><div class="card-body">{text}</div></div>',
				'css'         => '.readinizer-card { border: 1px solid #e1e5e9; border-radius: 12px; padding: 20px; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.07); margin: 20px 0; max-width: 300px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; } .readinizer-card .card-header { font-weight: 600; color: #2c3e50; margin-bottom: 10px; font-size: 16px; display: flex; align-items: center; gap: 8px; } .readinizer-card .card-body { color: #666; font-size: 14px; }',
				'is_default'  => false,
			),
			'floating' => array(
				'name'        => __( 'Floating Pro', 'readinizer-pro' ),
				'description' => __( 'Floating bubble with reading stats', 'readinizer-pro' ),
				'html'        => '<div class="readinizer-floating">{icon}<span class="floating-text">{text}</span></div>',
				'css'         => '.readinizer-floating { background: rgba(0,123,186,0.9); color: white; padding: 12px 16px; border-radius: 25px; font-size: 13px; backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,123,186,0.3); display: inline-flex; align-items: center; gap: 8px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; } .readinizer-floating .floating-text { margin-left: 4px; }',
				'is_default'  => false,
			),
		);

		// Get custom templates from database.
		$custom_templates = get_option( 'readinizer_pro_custom_templates', array() );

		return array_merge( $default_templates, $custom_templates );
	}

	/**
	 * Render templates management page
	 *
	 * @return string
	 */
	public function render_templates_page() {
		$templates = $this->get_templates();

		ob_start();
		?>
		<div class="templates-manager">
			<h2><?php esc_html_e( 'Custom Post Type Templates', 'readinizer-pro' ); ?></h2>
			<p><?php esc_html_e( 'Create and manage custom templates for different post types and display scenarios.', 'readinizer-pro' ); ?></p>

			<!-- Template Builder -->
			<div class="template-builder">
				<div class="builder-section">
					<h3><?php esc_html_e( 'Template Builder', 'readinizer-pro' ); ?></h3>

					<div class="builder-controls">
						<div class="control-group">
							<label for="template-name"><?php esc_html_e( 'Template Name', 'readinizer-pro' ); ?></label>
							<input type="text" id="template-name" placeholder="<?php esc_attr_e( 'Enter template name', 'readinizer-pro' ); ?>">
						</div>

						<div class="control-group">
							<label for="template-description"><?php esc_html_e( 'Description', 'readinizer-pro' ); ?></label>
							<input type="text" id="template-description" placeholder="<?php esc_attr_e( 'Template description', 'readinizer-pro' ); ?>">
						</div>
					</div>

					<div class="builder-tabs">
						<button class="tab-button active" data-tab="html"><?php esc_html_e( 'HTML Structure', 'readinizer-pro' ); ?></button>
						<button class="tab-button" data-tab="css"><?php esc_html_e( 'CSS Styles', 'readinizer-pro' ); ?></button>
						<button class="tab-button" data-tab="preview"><?php esc_html_e( 'Live Preview', 'readinizer-pro' ); ?></button>
					</div>

					<div class="builder-content">
						<div id="html-tab" class="tab-content active">
							<label for="template-html"><?php esc_html_e( 'HTML Template', 'readinizer-pro' ); ?></label>
							<textarea id="template-html" rows="10" placeholder="<?php esc_attr_e( 'HTML structure using {icon}, {text}, {time}, {words} placeholders', 'readinizer-pro' ); ?>"></textarea>
							<p class="description">
								<?php esc_html_e( 'Available placeholders:', 'readinizer-pro' ); ?>
								<code>{icon}</code>, <code>{text}</code>, <code>{time}</code>, <code>{words}</code>, <code>{title}</code>
							</p>
						</div>

						<div id="css-tab" class="tab-content">
							<label for="template-css"><?php esc_html_e( 'CSS Styles', 'readinizer-pro' ); ?></label>
							<textarea id="template-css" rows="10" placeholder="<?php esc_attr_e( 'CSS styles for your template', 'readinizer-pro' ); ?>"></textarea>
						</div>

						<div id="preview-tab" class="tab-content">
							<h4><?php esc_html_e( 'Live Preview', 'readinizer-pro' ); ?></h4>
							<div id="template-preview" class="template-preview-box">
								<p><?php esc_html_e( 'Template preview will appear here as you build it.', 'readinizer-pro' ); ?></p>
							</div>
						</div>
					</div>

					<div class="builder-actions">
						<button class="button button-primary" id="save-template"><?php esc_html_e( 'Save Template', 'readinizer-pro' ); ?></button>
						<button class="button" id="preview-template"><?php esc_html_e( 'Update Preview', 'readinizer-pro' ); ?></button>
						<button class="button" id="reset-builder"><?php esc_html_e( 'Reset', 'readinizer-pro' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Templates Library - FIXED with REAL previews -->
			<div class="templates-library">
				<h3><?php esc_html_e( 'Templates Library', 'readinizer-pro' ); ?></h3>

				<div class="templates-grid">
					<?php foreach ( $templates as $template_id => $template ) : ?>
						<div class="template-card" data-template="<?php echo esc_attr( $template_id ); ?>">
							<div class="template-header">
								<h4><?php echo esc_html( $template['name'] ); ?></h4>
								<?php if ( isset( $template['is_default'] ) && $template['is_default'] ) : ?>
									<span class="template-badge default"><?php esc_html_e( 'Default', 'readinizer-pro' ); ?></span>
								<?php else : ?>
									<span class="template-badge custom"><?php esc_html_e( 'Custom', 'readinizer-pro' ); ?></span>
								<?php endif; ?>
							</div>

							<div class="template-description">
								<p><?php echo esc_html( $template['description'] ); ?></p>
							</div>

							<!-- REAL Template Preview with actual CSS -->
							<div class="template-preview-mini">
								<style>
									.template-preview-<?php echo esc_attr( $template_id ); ?> <?php echo wp_strip_all_tags( $template['css'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</style>
								<div class="template-preview-<?php echo esc_attr( $template_id ); ?>">
									<?php
									$sample_html = str_replace(
										array( '{icon}', '{text}', '{time}', '{words}', '{title}' ),
										array( '📖', 'Reading time: 3 minutes • 650 words', '3 minutes', '650 words', 'Reading Info' ),
										$template['html']
									);
									echo wp_kses_post( $sample_html );
									?>
								</div>
							</div>

							<div class="template-actions">
								<button class="button button-small edit-template" data-template="<?php echo esc_attr( $template_id ); ?>">
									<?php esc_html_e( 'Edit', 'readinizer-pro' ); ?>
								</button>

								<?php if ( ! isset( $template['is_default'] ) || ! $template['is_default'] ) : ?>
									<button class="button button-small delete-template" data-template="<?php echo esc_attr( $template_id ); ?>">
										<?php esc_html_e( 'Delete', 'readinizer-pro' ); ?>
									</button>
								<?php endif; ?>

								<button class="button button-small button-primary use-template" data-template="<?php echo esc_attr( $template_id ); ?>">
									<?php esc_html_e( 'Use', 'readinizer-pro' ); ?>
								</button>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Post Type Assignments -->
			<div class="post-type-assignments">
				<h3><?php esc_html_e( 'Post Type Template Assignments', 'readinizer-pro' ); ?></h3>
				<p><?php esc_html_e( 'Assign specific templates to different post types.', 'readinizer-pro' ); ?></p>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Post Type', 'readinizer-pro' ); ?></th>
							<th><?php esc_html_e( 'Template', 'readinizer-pro' ); ?></th>
							<th><?php esc_html_e( 'Actions', 'readinizer-pro' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$post_types  = get_post_types( array( 'public' => true ), 'objects' );
						$assignments = get_option( 'readinizer_pro_template_assignments', array() );

						foreach ( $post_types as $post_type ) :
							?>
							<tr>
								<td><strong><?php echo esc_html( $post_type->label ); ?></strong> (<?php echo esc_html( $post_type->name ); ?>)</td>
								<td>
									<select name="template_assignment[<?php echo esc_attr( $post_type->name ); ?>]" class="template-assignment">
										<option value=""><?php esc_html_e( 'Default Template', 'readinizer-pro' ); ?></option>
										<?php foreach ( $templates as $template_id => $template ) : ?>
											<option value="<?php echo esc_attr( $template_id ); ?>" 
												<?php selected( isset( $assignments[ $post_type->name ] ) ? $assignments[ $post_type->name ] : '', $template_id ); ?>>
												<?php echo esc_html( $template['name'] ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
								<td>
									<button class="button button-small save-assignment" data-post-type="<?php echo esc_attr( $post_type->name ); ?>">
										<?php esc_html_e( 'Save', 'readinizer-pro' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * AJAX handler for using template
	 *
	 * @return void
	 */
	public function ajax_use_template() {
		check_ajax_referer( 'readinizer_pro_template', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		$template_id = sanitize_text_field( wp_unslash( $_POST['template_id'] ) );
		$templates   = $this->get_templates();

		if ( ! isset( $templates[ $template_id ] ) ) {
			wp_send_json_error( __( 'Template not found.', 'readinizer-pro' ) );
		}

		$template = $templates[ $template_id ];

		// Update plugin options to use this template style.
		$options = get_option( 'readinizer_pro_options', array() );
		$options['display_style'] = $template_id;

		update_option( 'readinizer_pro_options', $options );

		wp_send_json_success( array( 'message' => __( 'Template applied successfully!', 'readinizer-pro' ) ) );
	}

	/**
	 * AJAX handler for saving templates
	 *
	 * @return void
	 */
	public function ajax_save_template() {
		check_ajax_referer( 'readinizer_pro_template', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		$name        = sanitize_text_field( wp_unslash( $_POST['name'] ) );
		$description = sanitize_text_field( wp_unslash( $_POST['description'] ) );
		$html        = wp_kses_post( wp_unslash( $_POST['html'] ) );
		$css         = sanitize_textarea_field( wp_unslash( $_POST['css'] ) );

		if ( empty( $name ) || empty( $html ) ) {
			wp_send_json_error( __( 'Template name and HTML are required.', 'readinizer-pro' ) );
		}

		$templates   = get_option( 'readinizer_pro_custom_templates', array() );
		$template_id = sanitize_title( $name );

		$templates[ $template_id ] = array(
			'name'        => $name,
			'description' => $description,
			'html'        => $html,
			'css'         => $css,
			'is_default'  => false,
			'created'     => current_time( 'mysql' ),
		);

		update_option( 'readinizer_pro_custom_templates', $templates );

		wp_send_json_success( array( 'message' => __( 'Template saved successfully!', 'readinizer-pro' ) ) );
	}

	/**
	 * AJAX handler for deleting templates
	 *
	 * @return void
	 */
	public function ajax_delete_template() {
		check_ajax_referer( 'readinizer_pro_template', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		$template_id = sanitize_text_field( wp_unslash( $_POST['template_id'] ) );
		$templates   = get_option( 'readinizer_pro_custom_templates', array() );

		if ( isset( $templates[ $template_id ] ) ) {
			unset( $templates[ $template_id ] );
			update_option( 'readinizer_pro_custom_templates', $templates );
			wp_send_json_success( array( 'message' => __( 'Template deleted successfully!', 'readinizer-pro' ) ) );
		} else {
			wp_send_json_error( __( 'Template not found.', 'readinizer-pro' ) );
		}
	}

	/**
	 * AJAX handler for saving post type assignments
	 *
	 * @return void
	 */
	public function ajax_save_assignment() {
		check_ajax_referer( 'readinizer_pro_template', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );
		$template  = sanitize_text_field( wp_unslash( $_POST['template'] ) );

		$assignments                = get_option( 'readinizer_pro_template_assignments', array() );
		$assignments[ $post_type ]  = $template;

		update_option( 'readinizer_pro_template_assignments', $assignments );

		wp_send_json_success( array( 'message' => __( 'Assignment saved successfully!', 'readinizer-pro' ) ) );
	}

	/**
	 * AJAX handler for previewing templates
	 *
	 * @return void
	 */
	public function ajax_preview_template() {
		check_ajax_referer( 'readinizer_pro_template', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		$html = wp_kses_post( wp_unslash( $_POST['html'] ) );
		$css  = sanitize_textarea_field( wp_unslash( $_POST['css'] ) );

		$preview_html = str_replace(
			array( '{icon}', '{text}', '{time}', '{words}', '{title}' ),
			array( '📖', 'Reading time: 3 minutes • 650 words', '3 minutes', '650 words', 'Reading Info' ),
			$html
		);

		$full_preview = '<style>' . $css . '</style>' . $preview_html;

		wp_send_json_success( array( 'preview' => $full_preview ) );
	}
}

<?php
/**
 * Plugin Name: Readinizer Pro
 * Plugin URI: https://www.wpezo.com/plugins/readinizer-pro
 * Description: Professional reading time and analytics plugin for WordPress with advanced statistics, custom templates, and reading progress bars. Complete version with all features included.
 * Version: 2.0.1
 * Author: WPezo
 * Author URI: https://www.wpezo.com
 * Text Domain: readinizer-pro
 * Domain Path: /languages
 * Requires at least: 5.6
 * Tested up to: 6.8
 * Requires PHP: 8.0
 * Network: false
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ReadinizerPro
 * @author WPezo
 * @since 2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'READINIZER_PRO_VERSION', '2.0.1' );
define( 'READINIZER_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'READINIZER_PRO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'READINIZER_PRO_BRAND_URL', 'https://www.wpezo.com' );

/**
 * Main Readinizer Pro Class
 *
 * @package ReadinizerPro
 * @author WPezo
 * @since 2.0.0
 */
class ReadinizerPro {

	/**
	 * The single instance of the class
	 *
	 * @var ReadinizerPro|null
	 */
	private static $_instance = null;

	/**
	 * Plugin options
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Analytics handler
	 *
	 * @var ReadinizerPro_Analytics
	 */
	private $analytics;

	/**
	 * Templates handler
	 *
	 * @var ReadinizerPro_Templates
	 */
	private $templates;

	/**
	 * Progress bars handler
	 *
	 * @var ReadinizerPro_Progress_Bars
	 */
	private $progress_bars;

	/**
	 * Main ReadinizerPro Instance
	 *
	 * @return ReadinizerPro
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_hooks();
		$this->init_options();
		$this->load_dependencies();
	}

	/**
	 * Load plugin dependencies
	 *
	 * @return void
	 */
	private function load_dependencies() {
		require_once READINIZER_PRO_PLUGIN_PATH . 'includes/class-analytics.php';
		require_once READINIZER_PRO_PLUGIN_PATH . 'includes/class-templates.php';
		require_once READINIZER_PRO_PLUGIN_PATH . 'includes/class-progress-bars.php';

		$this->analytics      = new ReadinizerPro_Analytics();
		$this->templates      = new ReadinizerPro_Templates();
		$this->progress_bars  = new ReadinizerPro_Progress_Bars();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'the_content', array( $this, 'add_reading_time_to_content' ) );
		add_shortcode( 'readinizer_pro', array( $this, 'readinizer_shortcode' ) );
		add_shortcode( 'readinizer', array( $this, 'readinizer_shortcode' ) ); // Backward compatibility.
		add_shortcode( 'reading_time', array( $this, 'reading_time_shortcode' ) );
		add_shortcode( 'word_count', array( $this, 'word_count_shortcode' ) );
		add_shortcode( 'reading_progress', array( $this, 'reading_progress_shortcode' ) );

		// Plugin activation/deactivation.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Plugin action links (NO PRO UPGRADE LINKS).
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_row_meta' ), 10, 2 );

		// AJAX handlers.
		add_action( 'wp_ajax_readinizer_pro_update_options', array( $this, 'ajax_update_options' ) );
		add_action( 'wp_ajax_readinizer_pro_get_preview', array( $this, 'ajax_get_preview' ) );

		// Cache compatibility.
		add_action( 'wp_footer', array( $this, 'add_cache_busting_meta' ) );
	}

	/**
	 * Initialize plugin options
	 *
	 * @return void
	 */
	private function init_options() {
		$this->options = get_option(
			'readinizer_pro_options',
			array(
				'enabled'                        => true,
				'position'                       => 'before',
				'post_types'                     => array( 'post' ),
				'words_per_minute'               => 200,
				'show_word_count'                => true,
				'show_reading_time'              => true,
				'custom_text'                    => __( 'Reading time: {time} • {words} words', 'readinizer-pro' ),
				'display_style'                  => 'modern',
				'text_color'                     => '#666666',
				'background_color'               => '#f8f9fa',
				'border_radius'                  => '6',
				'font_size'                      => '14',
				'show_wpezo_credit'              => true,
				// PRO FEATURES.
				'enable_analytics'               => true,
				'enable_progress_bars'           => true,
				'progress_bar_style'             => 'linear',
				'progress_bar_position'          => 'top',
				'progress_bar_color'             => '#0073aa',
				'progress_thickness'             => '4',
				'progress_animation'             => 'smooth',
				'show_percentage'                => true,
				'show_reading_time_in_progress'  => false,
				'hide_when_complete'             => false,
				'custom_templates'               => array(),
				'analytics_tracking'             => true,
				'progress_post_types'            => array( 'post' ),
			)
		);
	}

	/**
	 * Add plugin action links (NO PRO UPGRADE - THIS IS THE PRO VERSION)
	 *
	 * @param array $links Plugin action links.
	 * @return array
	 */
	public function add_action_links( $links ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page=readinizer-pro' ) . '">' . __( 'Settings', 'readinizer-pro' ) . '</a>';
		$wpezo_link    = '<a href="' . READINIZER_PRO_BRAND_URL . '" target="_blank" style="color: #667eea; font-weight: bold;">' . __( 'More WPezo Plugins', 'readinizer-pro' ) . '</a>';

		array_unshift( $links, $settings_link, $wpezo_link );
		return $links;
	}

	/**
	 * Add plugin row meta
	 *
	 * @param array  $links Plugin row meta.
	 * @param string $file Plugin file.
	 * @return array
	 */
	public function add_row_meta( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$row_meta = array(
				'support' => '<a href="' . READINIZER_PRO_BRAND_URL . '/support" target="_blank">' . __( 'Support', 'readinizer-pro' ) . '</a>',
				'docs'    => '<a href="' . READINIZER_PRO_BRAND_URL . '/docs/readinizer-pro" target="_blank">' . __( 'Documentation', 'readinizer-pro' ) . '</a>',
				'wpezo'   => '<a href="' . READINIZER_PRO_BRAND_URL . '" target="_blank" style="color: #667eea;">' . __( 'WPezo Plugins', 'readinizer-pro' ) . '</a>',
			);
			return array_merge( $links, $row_meta );
		}
		return $links;
	}

	/**
	 * Load plugin textdomain
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'readinizer-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Enqueue frontend scripts and styles
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			'readinizer-pro-style',
			READINIZER_PRO_PLUGIN_URL . 'assets/css/readinizer-pro.css',
			array(),
			READINIZER_PRO_VERSION
		);

		// Progress bars script.
		if ( $this->options['enable_progress_bars'] && is_singular() ) {
			wp_enqueue_script(
				'readinizer-pro-progress',
				READINIZER_PRO_PLUGIN_URL . 'assets/js/progress-bars.js',
				array( 'jquery' ),
				READINIZER_PRO_VERSION,
				true
			);

			// Localize script for AJAX.
			wp_localize_script(
				'readinizer-pro-progress',
				'readinizerPro',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'readinizer_pro_analytics' ),
					'options' => array(
						'style'             => $this->options['progress_bar_style'],
						'position'          => $this->options['progress_bar_position'],
						'color'             => $this->options['progress_bar_color'],
						'thickness'         => $this->options['progress_thickness'],
						'animation'         => $this->options['progress_animation'],
						'showPercentage'    => $this->options['show_percentage'],
						'showTimeRemaining' => $this->options['show_reading_time_in_progress'],
						'hideWhenComplete'  => $this->options['hide_when_complete'],
					),
				)
			);
		}
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_readinizer-pro' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Chart.js for analytics.
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true );

		wp_enqueue_style(
			'readinizer-pro-admin-style',
			READINIZER_PRO_PLUGIN_URL . 'assets/css/admin-pro.css',
			array( 'wp-color-picker' ),
			READINIZER_PRO_VERSION
		);

		wp_enqueue_script(
			'readinizer-pro-admin-js',
			READINIZER_PRO_PLUGIN_URL . 'assets/js/admin-pro.js',
			array( 'jquery', 'wp-color-picker', 'chart-js' ),
			READINIZER_PRO_VERSION,
			true
		);

		// Localize admin script.
		wp_localize_script(
			'readinizer-pro-admin-js',
			'readinizerProAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonces'  => array(
					'analytics' => wp_create_nonce( 'readinizer_pro_analytics' ),
					'template'  => wp_create_nonce( 'readinizer_pro_template' ),
					'progress'  => wp_create_nonce( 'readinizer_pro_progress' ),
					'export'    => wp_create_nonce( 'readinizer_pro_export' ),
					'preview'   => wp_create_nonce( 'readinizer_pro_preview' ),
				),
				'strings' => array(
					'confirm_delete'  => __( 'Are you sure you want to delete this template?', 'readinizer-pro' ),
					'template_saved'  => __( 'Template saved successfully!', 'readinizer-pro' ),
					'settings_saved'  => __( 'Settings saved successfully!', 'readinizer-pro' ),
					'error_occurred'  => __( 'An error occurred. Please try again.', 'readinizer-pro' ),
				),
			)
		);
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		add_options_page(
			__( 'Readinizer Pro Settings', 'readinizer-pro' ),
			__( 'Readinizer Pro', 'readinizer-pro' ),
			'manage_options',
			'readinizer-pro',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Initialize admin settings
	 *
	 * @return void
	 */
	public function admin_init() {
		register_setting( 'readinizer_pro_settings', 'readinizer_pro_options', array( $this, 'sanitize_options' ) );

		// We will render the form fields manually in admin_page() method.
	}

	/**
	 * Calculate reading time and word count
	 *
	 * @param string $content Post content.
	 * @return array
	 */
	public function calculate_reading_stats( $content ) {
		// Remove HTML tags and shortcodes.
		$content = wp_strip_all_tags( strip_shortcodes( $content ) );

		// Count words.
		$word_count = str_word_count( $content );

		// Calculate reading time.
		$words_per_minute      = max( 1, intval( $this->options['words_per_minute'] ) );
		$reading_time_minutes  = ceil( $word_count / $words_per_minute );

		// Track analytics if enabled.
		if ( $this->options['analytics_tracking'] && $this->analytics ) {
			$this->analytics->track_reading_stats( get_the_ID(), $word_count, $reading_time_minutes );
		}

		return array(
			'word_count'   => $word_count,
			'reading_time' => $reading_time_minutes,
		);
	}

	/**
	 * Generate the reading time display HTML
	 *
	 * @param array $stats Reading statistics.
	 * @return string
	 */
	public function generate_reading_time_html( $stats ) {
		if ( ! $this->options['show_reading_time'] && ! $this->options['show_word_count'] ) {
			return '';
		}

		$reading_time_text = ( 1 === $stats['reading_time'] )
			? __( '1 minute', 'readinizer-pro' )
			: sprintf( __( '%d minutes', 'readinizer-pro' ), $stats['reading_time'] );

		$word_count_text = ( 1 === $stats['word_count'] )
			? __( '1 word', 'readinizer-pro' )
			: sprintf( __( '%d words', 'readinizer-pro' ), $stats['word_count'] );

		$custom_text = $this->options['custom_text'];
		$custom_text = str_replace( '{time}', $reading_time_text, $custom_text );
		$custom_text = str_replace( '{words}', $word_count_text, $custom_text );

		// Handle cases where only one type should be shown.
		if ( ! $this->options['show_reading_time'] ) {
			$custom_text = $word_count_text;
		} elseif ( ! $this->options['show_word_count'] ) {
			$custom_text = $reading_time_text;
		}

		$style_class = 'readinizer-display readinizer-' . esc_attr( $this->options['display_style'] );

		$custom_styles = '';
		if ( 'custom' === $this->options['display_style'] ) {
			$custom_styles = sprintf(
				'style="color: %s; background-color: %s; border-radius: %spx; font-size: %spx;"',
				esc_attr( $this->options['text_color'] ),
				esc_attr( $this->options['background_color'] ),
				esc_attr( $this->options['border_radius'] ),
				esc_attr( $this->options['font_size'] )
			);
		}

		// Add WPezo credit if enabled.
		$wpezo_credit = '';
		if ( $this->options['show_wpezo_credit'] ) {
			$wpezo_credit = ' <span class="readinizer-credit">by <a href="' . READINIZER_PRO_BRAND_URL . '" target="_blank">WPezo</a></span>';
		}

		return sprintf(
			'<div class="%s" %s>📖 %s%s</div>',
			esc_attr( $style_class ),
			$custom_styles,
			esc_html( $custom_text ),
			$wpezo_credit
		);
	}

	/**
	 * Add reading time to content
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function add_reading_time_to_content( $content ) {
		if ( ! $this->options['enabled'] || ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$post_type = get_post_type();
		if ( ! in_array( $post_type, $this->options['post_types'], true ) ) {
			return $content;
		}

		$stats              = $this->calculate_reading_stats( $content );
		$reading_time_html  = $this->generate_reading_time_html( $stats );

		if ( 'before' === $this->options['position'] ) {
			return $reading_time_html . $content;
		} elseif ( 'after' === $this->options['position'] ) {
			return $content . $reading_time_html;
		}

		return $content;
	}

	/**
	 * Main shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function readinizer_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id'            => get_the_ID(),
				'words_per_minute'   => $this->options['words_per_minute'],
				'show_word_count'    => $this->options['show_word_count'] ? 'true' : 'false',
				'show_reading_time'  => $this->options['show_reading_time'] ? 'true' : 'false',
				'style'              => $this->options['display_style'],
				'show_credit'        => $this->options['show_wpezo_credit'] ? 'true' : 'false',
			),
			$atts,
			'readinizer'
		);

		$post = get_post( $atts['post_id'] );
		if ( ! $post ) {
			return '';
		}

		// Temporarily override options for this shortcode.
		$original_options                         = $this->options;
		$this->options['words_per_minute']        = intval( $atts['words_per_minute'] );
		$this->options['show_word_count']         = ( 'true' === $atts['show_word_count'] );
		$this->options['show_reading_time']       = ( 'true' === $atts['show_reading_time'] );
		$this->options['display_style']           = sanitize_text_field( $atts['style'] );
		$this->options['show_wpezo_credit']       = ( 'true' === $atts['show_credit'] );

		$stats = $this->calculate_reading_stats( $post->post_content );
		$html  = $this->generate_reading_time_html( $stats );

		// Restore original options.
		$this->options = $original_options;

		return $html;
	}

	/**
	 * Reading time only shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function reading_time_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id'          => get_the_ID(),
				'words_per_minute' => $this->options['words_per_minute'],
			),
			$atts,
			'reading_time'
		);

		$post = get_post( $atts['post_id'] );
		if ( ! $post ) {
			return '';
		}

		$stats = $this->calculate_reading_stats( $post->post_content );
		return ( 1 === $stats['reading_time'] )
			? __( '1 minute', 'readinizer-pro' )
			: sprintf( __( '%d minutes', 'readinizer-pro' ), $stats['reading_time'] );
	}

	/**
	 * Word count only shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function word_count_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'post_id' => get_the_ID(),
			),
			$atts,
			'word_count'
		);

		$post = get_post( $atts['post_id'] );
		if ( ! $post ) {
			return '';
		}

		$stats = $this->calculate_reading_stats( $post->post_content );
		return number_format( $stats['word_count'] );
	}

	/**
	 * Reading progress shortcode
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function reading_progress_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'style'    => $this->options['progress_bar_style'],
				'position' => $this->options['progress_bar_position'],
				'color'    => $this->options['progress_bar_color'],
			),
			$atts,
			'reading_progress'
		);

		if ( ! $this->options['enable_progress_bars'] ) {
			return '';
		}

		return '<div id="readinizer-pro-progress-shortcode" class="readinizer-progress-' . esc_attr( $atts['style'] ) . '" style="--progress-color: ' . esc_attr( $atts['color'] ) . ';"></div>';
	}

	/**
	 * Plugin activation
	 *
	 * @return void
	 */
	public function activate() {
		// Set default options if they don't exist.
		if ( ! get_option( 'readinizer_pro_options' ) ) {
			update_option( 'readinizer_pro_options', $this->options );
		}

		// Create analytics tables.
		if ( $this->analytics ) {
			$this->analytics->create_tables();
		}

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation
	 *
	 * @return void
	 */
	public function deactivate() {
		// Clean up if necessary.
		flush_rewrite_rules();
	}

	/**
	 * Sanitize options
	 *
	 * @param array $input Input options.
	 * @return array
	 */
	public function sanitize_options( $input ) {
		$sanitized = array();

		$sanitized['enabled']                        = isset( $input['enabled'] ) ? true : false;
		$sanitized['position']                       = in_array( $input['position'], array( 'before', 'after', 'none' ), true ) ? $input['position'] : 'before';
		$sanitized['post_types']                     = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_text_field', $input['post_types'] ) : array( 'post' );
		$sanitized['words_per_minute']               = max( 1, intval( $input['words_per_minute'] ) );
		$sanitized['show_word_count']                = isset( $input['show_word_count'] ) ? true : false;
		$sanitized['show_reading_time']              = isset( $input['show_reading_time'] ) ? true : false;
		$sanitized['custom_text']                    = sanitize_text_field( $input['custom_text'] );
		$sanitized['display_style']                  = in_array( $input['display_style'], array( 'minimal', 'modern', 'badge', 'card', 'floating', 'custom' ), true ) ? $input['display_style'] : 'modern';
		$sanitized['text_color']                     = sanitize_hex_color( $input['text_color'] );
		$sanitized['background_color']               = sanitize_hex_color( $input['background_color'] );
		$sanitized['border_radius']                  = max( 0, intval( $input['border_radius'] ) );
		$sanitized['font_size']                      = max( 8, intval( $input['font_size'] ) );
		$sanitized['show_wpezo_credit']              = isset( $input['show_wpezo_credit'] ) ? true : false;

		// PRO OPTIONS.
		$sanitized['enable_analytics']               = isset( $input['enable_analytics'] ) ? true : false;
		$sanitized['enable_progress_bars']           = isset( $input['enable_progress_bars'] ) ? true : false;
		$sanitized['progress_bar_style']             = in_array( $input['progress_bar_style'], array( 'linear', 'circular', 'floating' ), true ) ? $input['progress_bar_style'] : 'linear';
		$sanitized['progress_bar_position']          = in_array( $input['progress_bar_position'], array( 'top', 'bottom', 'floating-left', 'floating-right' ), true ) ? $input['progress_bar_position'] : 'top';
		$sanitized['progress_bar_color']             = sanitize_hex_color( $input['progress_bar_color'] );
		$sanitized['progress_thickness']             = max( 1, min( 10, intval( $input['progress_thickness'] ) ) );
		$sanitized['progress_animation']             = in_array( $input['progress_animation'], array( 'smooth', 'stepped', 'bouncy', 'none' ), true ) ? $input['progress_animation'] : 'smooth';
		$sanitized['show_percentage']                = isset( $input['show_percentage'] ) ? true : false;
		$sanitized['show_reading_time_in_progress']  = isset( $input['show_reading_time_in_progress'] ) ? true : false;
		$sanitized['hide_when_complete']             = isset( $input['hide_when_complete'] ) ? true : false;
		$sanitized['analytics_tracking']             = isset( $input['analytics_tracking'] ) ? true : false;
		$sanitized['progress_post_types']            = isset( $input['progress_post_types'] ) && is_array( $input['progress_post_types'] ) ? array_map( 'sanitize_text_field', $input['progress_post_types'] ) : array( 'post' );

		// Keep existing custom templates and other complex options.
		if ( isset( $input['custom_templates'] ) ) {
			$sanitized['custom_templates'] = $input['custom_templates'];
		} else {
			$sanitized['custom_templates'] = $this->options['custom_templates'] ?? array();
		}

		return $sanitized;
	}

	/**
	 * AJAX handler for updating options
	 *
	 * @return void
	 */
	public function ajax_update_options() {
		check_ajax_referer( 'readinizer_pro_preview', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		$options = wp_unslash( $_POST['options'] );
		$sanitized_options = $this->sanitize_options( $options );

		// Temporarily update options for preview.
		$this->options = array_merge( $this->options, $sanitized_options );

		wp_send_json_success( array( 'message' => __( 'Options updated successfully!', 'readinizer-pro' ) ) );
	}

	/**
	 * AJAX handler for getting preview
	 *
	 * @return void
	 */
	public function ajax_get_preview() {
		check_ajax_referer( 'readinizer_pro_preview', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions.', 'readinizer-pro' ) );
		}

		// Get temporary options from request.
		$temp_options = wp_unslash( $_POST['options'] );
		$original_options = $this->options;

		// Temporarily update options.
		$this->options = array_merge( $this->options, $temp_options );

		// Generate preview HTML.
		$sample_stats = array(
			'reading_time' => 3,
			'word_count'   => 650,
		);

		$preview_html = $this->generate_reading_time_html( $sample_stats );

		// Restore original options.
		$this->options = $original_options;

		wp_send_json_success( array( 'html' => $preview_html ) );
	}

	/**
	 * Add cache busting meta for compatibility
	 *
	 * @return void
	 */
	public function add_cache_busting_meta() {
		if ( is_singular() && $this->options['enable_progress_bars'] ) {
			echo '<meta name="readinizer-cache-bust" content="' . esc_attr( time() ) . '">' . "\n";
		}
	}

	/**
	 * Admin page HTML with PRO tabs - FIXED VERSION
	 *
	 * @return void
	 */
	public function admin_page() {
		?>
		<div class="wrap readinizer-pro-admin">
			<div class="readinizer-pro-header">
				<div class="header-content">
					<div class="header-left">
						<h1>📚 Readinizer Pro <span class="pro-badge">PRO</span></h1>
						<p><?php esc_html_e( 'Professional Reading Time & Analytics for WordPress', 'readinizer-pro' ); ?></p>
						<div class="wpezo-brand">
							<?php
							printf(
								/* translators: %s: WPezo link */
								esc_html__( 'Powered by %s', 'readinizer-pro' ),
								'<a href="' . esc_url( READINIZER_PRO_BRAND_URL ) . '" target="_blank" class="wpezo-link">WPezo</a>'
							);
							?>
						</div>
					</div>
					<div class="header-right">
						<div class="readinizer-pro-version">
							<?php
							printf(
								/* translators: %s: Plugin version */
								esc_html__( 'Version %s', 'readinizer-pro' ),
								esc_html( READINIZER_PRO_VERSION )
							);
							?>
						</div>
					</div>
				</div>
			</div>

			<div class="nav-tab-wrapper">
				<a href="#general" class="nav-tab nav-tab-active" data-tab="general">⚙️ <?php esc_html_e( 'General', 'readinizer-pro' ); ?></a>
				<a href="#display" class="nav-tab" data-tab="display">🎨 <?php esc_html_e( 'Display', 'readinizer-pro' ); ?></a>
				<a href="#style" class="nav-tab" data-tab="style">✨ <?php esc_html_e( 'Style', 'readinizer-pro' ); ?></a>
				<a href="#analytics" class="nav-tab pro-tab" data-tab="analytics">📊 <?php esc_html_e( 'Analytics', 'readinizer-pro' ); ?></a>
				<a href="#templates" class="nav-tab pro-tab" data-tab="templates">🔧 <?php esc_html_e( 'Templates', 'readinizer-pro' ); ?></a>
				<a href="#progress" class="nav-tab pro-tab" data-tab="progress">📈 <?php esc_html_e( 'Progress Bars', 'readinizer-pro' ); ?></a>
			</div>

			<div class="readinizer-pro-content">
				<div class="readinizer-pro-main">
					<!-- General Tab -->
					<div id="tab-general" class="tab-content active">
						<form method="post" action="options.php" id="readinizer-pro-form">
							<?php settings_fields( 'readinizer_pro_settings' ); ?>
							<input type="hidden" name="option_page" value="readinizer_pro_settings" />
							<input type="hidden" name="action" value="update" />

							<h3><?php esc_html_e( 'General Settings', 'readinizer-pro' ); ?></h3>
							<p><?php esc_html_e( 'Configure the basic settings for Readinizer Pro.', 'readinizer-pro' ); ?></p>

							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Enable Readinizer Pro', 'readinizer-pro' ); ?></th>
									<td>
										<label>
											<input type="checkbox" id="enabled" name="readinizer_pro_options[enabled]" value="1" <?php checked( $this->options['enabled'], true ); ?>>
											<?php esc_html_e( 'Enable automatic display of reading time and word count', 'readinizer-pro' ); ?>
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Post Types', 'readinizer-pro' ); ?></th>
									<td>
										<?php
										$post_types = get_post_types( array( 'public' => true ), 'objects' );
										foreach ( $post_types as $post_type ) {
											$checked = in_array( $post_type->name, $this->options['post_types'], true ) ? 'checked' : '';
											printf(
												'<label><input type="checkbox" name="readinizer_pro_options[post_types][]" value="%s" %s> %s</label><br>',
												esc_attr( $post_type->name ),
												$checked,
												esc_html( $post_type->label )
											);
										}
										?>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Reading Speed (words per minute)', 'readinizer-pro' ); ?></th>
									<td>
										<input type="number" id="words_per_minute" name="readinizer_pro_options[words_per_minute]" value="<?php echo esc_attr( $this->options['words_per_minute'] ); ?>" min="1" max="1000" step="1">
										<p class="description"><?php esc_html_e( 'Average reading speed for calculating reading time. Default: 200 words per minute.', 'readinizer-pro' ); ?></p>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Show WPezo Credit', 'readinizer-pro' ); ?></th>
									<td>
										<label>
											<input type="checkbox" id="show_wpezo_credit" name="readinizer_pro_options[show_wpezo_credit]" value="1" <?php checked( $this->options['show_wpezo_credit'], true ); ?>>
											<?php esc_html_e( 'Show "by WPezo" credit link (helps support plugin development)', 'readinizer-pro' ); ?>
										</label>
									</td>
								</tr>
							</table>

							<!-- Live Preview Section -->
							<div class="preview-section">
								<h3><?php esc_html_e( 'Live Preview', 'readinizer-pro' ); ?></h3>
								<div id="readinizer-preview" class="preview-box">
									<?php
									$sample_stats = array(
										'reading_time' => 3,
										'word_count'   => 650,
									);
									echo $this->generate_reading_time_html( $sample_stats ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</div>
							</div>

							<?php submit_button( __( 'Save Settings', 'readinizer-pro' ) ); ?>
						</form>
					</div>

					<!-- Display Tab - FIXED -->
					<div id="tab-display" class="tab-content">
						<form method="post" action="options.php">
							<?php settings_fields( 'readinizer_pro_settings' ); ?>

							<h3><?php esc_html_e( 'Display Settings', 'readinizer-pro' ); ?></h3>
							<p><?php esc_html_e( 'Control how reading time and word count are displayed.', 'readinizer-pro' ); ?></p>

							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Display Position', 'readinizer-pro' ); ?></th>
									<td>
										<?php
										$positions = array(
											'before' => __( 'Before Content', 'readinizer-pro' ),
											'after'  => __( 'After Content', 'readinizer-pro' ),
											'none'   => __( 'Manual (shortcode only)', 'readinizer-pro' ),
										);

										foreach ( $positions as $value => $label ) {
											printf(
												'<label><input type="radio" name="readinizer_pro_options[position]" value="%s" %s> %s</label><br>',
												esc_attr( $value ),
												checked( $this->options['position'], $value, false ),
												esc_html( $label )
											);
										}
										?>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Show Reading Time', 'readinizer-pro' ); ?></th>
									<td>
										<label>
											<input type="checkbox" id="show_reading_time" name="readinizer_pro_options[show_reading_time]" value="1" <?php checked( $this->options['show_reading_time'], true ); ?>>
											<?php esc_html_e( 'Display estimated reading time', 'readinizer-pro' ); ?>
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Show Word Count', 'readinizer-pro' ); ?></th>
									<td>
										<label>
											<input type="checkbox" id="show_word_count" name="readinizer_pro_options[show_word_count]" value="1" <?php checked( $this->options['show_word_count'], true ); ?>>
											<?php esc_html_e( 'Display word count', 'readinizer-pro' ); ?>
										</label>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Custom Text Template', 'readinizer-pro' ); ?></th>
									<td>
										<input type="text" id="custom_text" name="readinizer_pro_options[custom_text]" value="<?php echo esc_attr( $this->options['custom_text'] ); ?>" class="regular-text">
										<p class="description"><?php esc_html_e( 'Use {time} for reading time and {words} for word count. Example: "Reading time: {time} • {words} words"', 'readinizer-pro' ); ?></p>
									</td>
								</tr>
							</table>

							<?php submit_button( __( 'Save Display Settings', 'readinizer-pro' ) ); ?>
						</form>
					</div>

					<!-- Style Tab - FIXED -->
					<div id="tab-style" class="tab-content">
						<form method="post" action="options.php">
							<?php settings_fields( 'readinizer_pro_settings' ); ?>

							<h3><?php esc_html_e( 'Style Settings', 'readinizer-pro' ); ?></h3>
							<p><?php esc_html_e( 'Customize the appearance of the reading time display.', 'readinizer-pro' ); ?></p>

							<table class="form-table">
								<tr>
									<th scope="row"><?php esc_html_e( 'Display Style', 'readinizer-pro' ); ?></th>
									<td>
										<?php
										$styles = array(
											'minimal'  => __( 'Minimal', 'readinizer-pro' ),
											'modern'   => __( 'Modern', 'readinizer-pro' ),
											'badge'    => __( 'Badge', 'readinizer-pro' ),
											'card'     => __( 'Card Pro', 'readinizer-pro' ),
											'floating' => __( 'Floating Pro', 'readinizer-pro' ),
											'custom'   => __( 'Custom', 'readinizer-pro' ),
										);

										foreach ( $styles as $value => $label ) {
											printf(
												'<label><input type="radio" name="readinizer_pro_options[display_style]" value="%s" %s> %s</label><br>',
												esc_attr( $value ),
												checked( $this->options['display_style'], $value, false ),
												esc_html( $label )
											);
										}
										?>
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Text Color', 'readinizer-pro' ); ?></th>
									<td>
										<input type="text" id="text_color" name="readinizer_pro_options[text_color]" value="<?php echo esc_attr( $this->options['text_color'] ); ?>" class="color-picker">
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Background Color', 'readinizer-pro' ); ?></th>
									<td>
										<input type="text" id="background_color" name="readinizer_pro_options[background_color]" value="<?php echo esc_attr( $this->options['background_color'] ); ?>" class="color-picker">
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Border Radius (px)', 'readinizer-pro' ); ?></th>
									<td>
										<input type="number" id="border_radius" name="readinizer_pro_options[border_radius]" value="<?php echo esc_attr( $this->options['border_radius'] ); ?>" min="0" max="50" step="1">
									</td>
								</tr>

								<tr>
									<th scope="row"><?php esc_html_e( 'Font Size (px)', 'readinizer-pro' ); ?></th>
									<td>
										<input type="number" id="font_size" name="readinizer_pro_options[font_size]" value="<?php echo esc_attr( $this->options['font_size'] ); ?>" min="8" max="32" step="1">
									</td>
								</tr>
							</table>

							<?php submit_button( __( 'Save Style Settings', 'readinizer-pro' ) ); ?>
						</form>
					</div>

					<!-- Analytics Tab -->
					<div id="tab-analytics" class="tab-content">
						<?php
						if ( $this->analytics ) {
							echo $this->analytics->render_analytics_dashboard(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>

					<!-- Templates Tab -->
					<div id="tab-templates" class="tab-content">
						<?php
						if ( $this->templates ) {
							echo $this->templates->render_templates_page(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>

					<!-- Progress Bars Tab -->
					<div id="tab-progress" class="tab-content">
						<?php
						if ( $this->progress_bars ) {
							echo $this->progress_bars->render_progress_settings(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</div>

				<div class="readinizer-pro-sidebar">
					<div class="readinizer-pro-box wpezo-support">
						<h3>💜 <?php esc_html_e( 'WPezo Plugins', 'readinizer-pro' ); ?></h3>
						<p><?php esc_html_e( 'Discover more professional WordPress plugins and themes by WPezo.', 'readinizer-pro' ); ?></p>
						<a href="<?php echo esc_url( READINIZER_PRO_BRAND_URL ); ?>" target="_blank" class="button">
							<?php esc_html_e( 'Explore WPezo Products', 'readinizer-pro' ); ?>
						</a>
					</div>

					<div class="readinizer-pro-box">
						<h3>📖 <?php esc_html_e( 'How to Use', 'readinizer-pro' ); ?></h3>
						<ul>
							<li><?php esc_html_e( 'Enable automatic display in General Settings', 'readinizer-pro' ); ?></li>
							<li><?php esc_html_e( 'Choose post types to display on', 'readinizer-pro' ); ?></li>
							<li><?php esc_html_e( 'Use shortcodes for manual placement:', 'readinizer-pro' ); ?></li>
						</ul>
						<p>
							<code>[readinizer_pro]</code><br>
							<code>[reading_time]</code><br>
							<code>[word_count]</code><br>
							<code>[reading_progress]</code>
						</p>
					</div>
				</div>
			</div>
		</div>

		<style>
		.preview-section {
			background: #f9f9f9;
			padding: 20px;
			border-radius: 8px;
			margin: 20px 0;
			border-left: 4px solid #667eea;
		}
		.preview-box {
			background: white;
			padding: 20px;
			border-radius: 6px;
			border: 2px dashed #ddd;
			text-align: center;
			margin-top: 10px;
		}
		</style>
		<?php
	}
}

// Initialize the plugin.
/**
 * Get the main instance of ReadinizerPro.
 *
 * @return ReadinizerPro
 */
function readinizer_pro() {
	return ReadinizerPro::instance();
}

// Global for backwards compatibility.
$GLOBALS['readinizer_pro'] = readinizer_pro();

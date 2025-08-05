<?php
/**
 * Readinizer Pro Progress Bars Class
 * 
 * @package ReadinizerPro
 * @author WPezo
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ReadinizerPro_Progress_Bars {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_footer', array($this, 'render_progress_bar'));
        add_action('wp_ajax_readinizer_pro_save_progress_settings', array($this, 'ajax_save_progress_settings'));
    }

    /**
     * Render progress bar settings page
     */
    public function render_progress_settings() {
        $options = get_option('readinizer_pro_options', array());

        ob_start();
        ?>
        <div class="progress-bars-settings">
            <h2><?php _e('Reading Progress Bars', 'readinizer-pro'); ?></h2>
            <p><?php _e('Configure visual reading progress indicators to enhance user engagement.', 'readinizer-pro'); ?></p>

            <form class="progress-form">
                <!-- Enable Progress Bars -->
                <div class="setting-group">
                    <h3><?php _e('General Settings', 'readinizer-pro'); ?></h3>

                    <label class="setting-label">
                        <input type="checkbox" id="enable_progress_bars" <?php checked($options['enable_progress_bars'] ?? true); ?>>
                        <?php _e('Enable Reading Progress Bars', 'readinizer-pro'); ?>
                    </label>
                    <p class="description"><?php _e('Show visual reading progress indicators on posts and pages.', 'readinizer-pro'); ?></p>
                </div>

                <!-- Progress Bar Style -->
                <div class="setting-group">
                    <h3><?php _e('Progress Bar Style', 'readinizer-pro'); ?></h3>

                    <div class="style-options">
                        <div class="style-option">
                            <input type="radio" id="style_linear" name="progress_style" value="linear" 
                                <?php checked($options['progress_bar_style'] ?? 'linear', 'linear'); ?>>
                            <label for="style_linear" class="style-card">
                                <div class="style-preview">
                                    <div class="linear-preview">
                                        <div class="linear-bar" style="width: 100%; height: 4px; background: #ddd; border-radius: 2px;">
                                            <div style="width: 45%; height: 100%; background: #0073aa; border-radius: 2px;"></div>
                                        </div>
                                    </div>
                                </div>
                                <h4><?php _e('Linear Bar', 'readinizer-pro'); ?></h4>
                                <p><?php _e('Horizontal progress bar at top or bottom', 'readinizer-pro'); ?></p>
                            </label>
                        </div>

                        <div class="style-option">
                            <input type="radio" id="style_circular" name="progress_style" value="circular"
                                <?php checked($options['progress_bar_style'] ?? 'linear', 'circular'); ?>>
                            <label for="style_circular" class="style-card">
                                <div class="style-preview">
                                    <div class="circular-preview">
                                        <div class="circular-progress" style="width: 40px; height: 40px; border: 3px solid #ddd; border-top-color: #0073aa; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: bold;">75%</div>
                                    </div>
                                </div>
                                <h4><?php _e('Circular Progress', 'readinizer-pro'); ?></h4>
                                <p><?php _e('Circular progress indicator with percentage', 'readinizer-pro'); ?></p>
                            </label>
                        </div>

                        <div class="style-option">
                            <input type="radio" id="style_floating" name="progress_style" value="floating"
                                <?php checked($options['progress_bar_style'] ?? 'linear', 'floating'); ?>>
                            <label for="style_floating" class="style-card">
                                <div class="style-preview">
                                    <div class="floating-preview">
                                        <div class="floating-bubble" style="background: #0073aa; color: white; padding: 4px 8px; border-radius: 12px; font-size: 10px; display: inline-block;">📖 45%</div>
                                    </div>
                                </div>
                                <h4><?php _e('Floating Bubble', 'readinizer-pro'); ?></h4>
                                <p><?php _e('Floating progress bubble with reading stats', 'readinizer-pro'); ?></p>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Position Settings -->
                <div class="setting-group">
                    <h3><?php _e('Position & Appearance', 'readinizer-pro'); ?></h3>

                    <div class="setting-row">
                        <label for="progress_position"><?php _e('Position', 'readinizer-pro'); ?></label>
                        <select id="progress_position" name="progress_position">
                            <option value="top" <?php selected($options['progress_bar_position'] ?? 'top', 'top'); ?>>
                                <?php _e('Top of page', 'readinizer-pro'); ?>
                            </option>
                            <option value="bottom" <?php selected($options['progress_bar_position'] ?? 'top', 'bottom'); ?>>
                                <?php _e('Bottom of page', 'readinizer-pro'); ?>
                            </option>
                            <option value="floating-left" <?php selected($options['progress_bar_position'] ?? 'top', 'floating-left'); ?>>
                                <?php _e('Floating left', 'readinizer-pro'); ?>
                            </option>
                            <option value="floating-right" <?php selected($options['progress_bar_position'] ?? 'top', 'floating-right'); ?>>
                                <?php _e('Floating right', 'readinizer-pro'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="setting-row">
                        <label for="progress_color"><?php _e('Progress Color', 'readinizer-pro'); ?></label>
                        <input type="color" id="progress_color" name="progress_color" 
                            value="<?php echo esc_attr($options['progress_bar_color'] ?? '#0073aa'); ?>">
                    </div>

                    <div class="setting-row">
                        <label for="progress_thickness"><?php _e('Bar Thickness (px)', 'readinizer-pro'); ?></label>
                        <input type="range" id="progress_thickness" name="progress_thickness" 
                            min="1" max="10" value="<?php echo esc_attr($options['progress_thickness'] ?? '4'); ?>" 
                            class="range-input">
                        <span class="range-value"><?php echo esc_attr($options['progress_thickness'] ?? '4'); ?>px</span>
                    </div>
                </div>

                <!-- Animation Settings -->
                <div class="setting-group">
                    <h3><?php _e('Animation & Behavior', 'readinizer-pro'); ?></h3>

                    <div class="setting-row">
                        <label for="progress_animation"><?php _e('Animation Style', 'readinizer-pro'); ?></label>
                        <select id="progress_animation" name="progress_animation">
                            <option value="smooth" <?php selected($options['progress_animation'] ?? 'smooth', 'smooth'); ?>>
                                <?php _e('Smooth', 'readinizer-pro'); ?>
                            </option>
                            <option value="stepped" <?php selected($options['progress_animation'] ?? 'smooth', 'stepped'); ?>>
                                <?php _e('Stepped', 'readinizer-pro'); ?>
                            </option>
                            <option value="bouncy" <?php selected($options['progress_animation'] ?? 'smooth', 'bouncy'); ?>>
                                <?php _e('Bouncy', 'readinizer-pro'); ?>
                            </option>
                            <option value="none" <?php selected($options['progress_animation'] ?? 'smooth', 'none'); ?>>
                                <?php _e('No animation', 'readinizer-pro'); ?>
                            </option>
                        </select>
                    </div>

                    <div class="setting-row">
                        <label class="setting-label">
                            <input type="checkbox" id="show_percentage" 
                                <?php checked($options['show_percentage'] ?? true); ?>>
                            <?php _e('Show percentage text', 'readinizer-pro'); ?>
                        </label>
                    </div>

                    <div class="setting-row">
                        <label class="setting-label">
                            <input type="checkbox" id="show_reading_time_in_progress" 
                                <?php checked($options['show_reading_time_in_progress'] ?? false); ?>>
                            <?php _e('Show estimated time remaining', 'readinizer-pro'); ?>
                        </label>
                    </div>

                    <div class="setting-row">
                        <label class="setting-label">
                            <input type="checkbox" id="hide_when_complete" 
                                <?php checked($options['hide_when_complete'] ?? false); ?>>
                            <?php _e('Hide when reading is complete', 'readinizer-pro'); ?>
                        </label>
                    </div>
                </div>

                <!-- Live Preview -->
                <div class="setting-group">
                    <h3><?php _e('Live Preview', 'readinizer-pro'); ?></h3>
                    <div id="progress-preview" class="progress-preview-container">
                        <div class="preview-content">
                            <h4><?php _e('Sample Article Content', 'readinizer-pro'); ?></h4>
                            <p><?php _e('This is a preview of how the progress bar will look on your posts. The progress indicator will show reading progress in real-time.', 'readinizer-pro'); ?></p>
                            <div class="fake-content">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <p><?php _e('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.', 'readinizer-pro'); ?></p>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div id="preview-progress-bar"></div>
                    </div>
                </div>

                <!-- Post Type Settings -->
                <div class="setting-group">
                    <h3><?php _e('Post Type Settings', 'readinizer-pro'); ?></h3>
                    <p class="description"><?php _e('Choose which post types should display reading progress bars.', 'readinizer-pro'); ?></p>

                    <?php
                    $post_types = get_post_types(array('public' => true), 'objects');
                    $enabled_post_types = $options['progress_post_types'] ?? array('post');

                    foreach ($post_types as $post_type):
                    ?>
                        <label class="setting-label">
                            <input type="checkbox" name="progress_post_types[]" value="<?php echo esc_attr($post_type->name); ?>"
                                <?php checked(in_array($post_type->name, $enabled_post_types)); ?>>
                            <?php echo esc_html($post_type->label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary" id="save-progress-settings">
                        <?php _e('Save Progress Bar Settings', 'readinizer-pro'); ?>
                    </button>
                    <button type="button" class="button" id="preview-progress">
                        <?php _e('Update Preview', 'readinizer-pro'); ?>
                    </button>
                    <button type="button" class="button" id="reset-progress-settings">
                        <?php _e('Reset to Defaults', 'readinizer-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render progress bar on frontend
     */
    public function render_progress_bar() {
        $options = get_option('readinizer_pro_options', array());

        if (!isset($options['enable_progress_bars']) || !$options['enable_progress_bars']) {
            return;
        }

        if (!is_singular()) {
            return;
        }

        $post_type = get_post_type();
        $enabled_post_types = $options['progress_post_types'] ?? array('post');

        if (!in_array($post_type, $enabled_post_types)) {
            return;
        }

        $style = $options['progress_bar_style'] ?? 'linear';
        $position = $options['progress_bar_position'] ?? 'top';
        $color = $options['progress_bar_color'] ?? '#0073aa';
        $thickness = $options['progress_thickness'] ?? '4';
        $animation = $options['progress_animation'] ?? 'smooth';
        $show_percentage = $options['show_percentage'] ?? true;
        $show_time_remaining = $options['show_reading_time_in_progress'] ?? false;
        $hide_when_complete = $options['hide_when_complete'] ?? false;

        $classes = array(
            'readinizer-progress-' . $style,
            'readinizer-progress-' . $position,
            'readinizer-progress-' . $animation
        );

        if ($hide_when_complete) {
            $classes[] = 'hide-when-complete';
        }

        ?>
        <div id="readinizer-pro-progress" class="<?php echo esc_attr(implode(' ', $classes)); ?>" 
             style="--progress-color: <?php echo esc_attr($color); ?>; --progress-thickness: <?php echo esc_attr($thickness); ?>px;">
            <?php if ($style === 'linear'): ?>
                <div class="progress-bar"></div>
            <?php elseif ($style === 'circular'): ?>
                <div class="circular-progress">
                    <svg class="circular-chart" viewBox="0 0 36 36">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path class="circle" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <?php if ($show_percentage): ?>
                        <div class="percentage">0%</div>
                    <?php endif; ?>
                </div>
            <?php elseif ($style === 'floating'): ?>
                <div class="floating-progress">
                    <span class="progress-icon">📖</span>
                    <?php if ($show_percentage): ?>
                        <span class="progress-text">0%</span>
                    <?php endif; ?>
                    <?php if ($show_time_remaining): ?>
                        <span class="time-remaining"></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX handler for saving progress settings
     */
    public function ajax_save_progress_settings() {
        check_ajax_referer('readinizer_pro_progress', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'readinizer-pro'));
        }

        parse_str($_POST['settings'], $settings);

        $options = get_option('readinizer_pro_options', array());

        // Update progress bar settings
        $options['enable_progress_bars'] = isset($settings['enable_progress_bars']);
        $options['progress_bar_style'] = sanitize_text_field($settings['progress_style'] ?? 'linear');
        $options['progress_bar_position'] = sanitize_text_field($settings['progress_position'] ?? 'top');
        $options['progress_bar_color'] = sanitize_hex_color($settings['progress_color'] ?? '#0073aa');
        $options['progress_thickness'] = intval($settings['progress_thickness'] ?? 4);
        $options['progress_animation'] = sanitize_text_field($settings['progress_animation'] ?? 'smooth');
        $options['show_percentage'] = isset($settings['show_percentage']);
        $options['show_reading_time_in_progress'] = isset($settings['show_reading_time_in_progress']);
        $options['hide_when_complete'] = isset($settings['hide_when_complete']);
        $options['progress_post_types'] = isset($settings['progress_post_types']) ? array_map('sanitize_text_field', $settings['progress_post_types']) : array();

        update_option('readinizer_pro_options', $options);

        wp_send_json_success(__('Progress bar settings saved successfully!', 'readinizer-pro'));
    }
}

<?php
/**
 * Readinizer Pro Templates Class
 * 
 * @package ReadinizerPro
 * @author WPezo
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ReadinizerPro_Templates {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_readinizer_pro_save_template', array($this, 'ajax_save_template'));
        add_action('wp_ajax_readinizer_pro_delete_template', array($this, 'ajax_delete_template'));
        add_action('wp_ajax_readinizer_pro_preview_template', array($this, 'ajax_preview_template'));
        add_action('wp_ajax_readinizer_pro_save_assignment', array($this, 'ajax_save_assignment'));
    }

    /**
     * Get available templates
     */
    public function get_templates() {
        $default_templates = array(
            'minimal' => array(
                'name' => __('Minimal', 'readinizer-pro'),
                'description' => __('Clean text-only display', 'readinizer-pro'),
                'html' => '<div class="readinizer-minimal">{icon} {text}</div>',
                'css' => '.readinizer-minimal { color: #666; font-size: 13px; margin: 15px 0; }',
                'is_default' => true
            ),
            'modern' => array(
                'name' => __('Modern', 'readinizer-pro'),
                'description' => __('Stylish with gradients and shadows', 'readinizer-pro'),
                'html' => '<div class="readinizer-modern">{icon} {text}</div>',
                'css' => '.readinizer-modern { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #495057; padding: 10px 16px; border-radius: 6px; border-left: 3px solid #007cba; box-shadow: 0 2px 4px rgba(0,0,0,0.05); font-weight: 500; }',
                'is_default' => true
            ),
            'badge' => array(
                'name' => __('Badge', 'readinizer-pro'),
                'description' => __('Compact badge-style display', 'readinizer-pro'),
                'html' => '<div class="readinizer-badge">{icon} {text}</div>',
                'css' => '.readinizer-badge { background: #007cba; color: white; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }',
                'is_default' => true
            ),
            'card' => array(
                'name' => __('Card Pro', 'readinizer-pro'),
                'description' => __('Full card with icon and detailed stats', 'readinizer-pro'),
                'html' => '<div class="readinizer-card"><div class="card-header">{icon} {title}</div><div class="card-body">{text}</div></div>',
                'css' => '.readinizer-card { border: 1px solid #e1e5e9; border-radius: 12px; padding: 20px; background: white; box-shadow: 0 4px 6px rgba(0,0,0,0.07); margin: 20px 0; } .readinizer-card .card-header { font-weight: 600; color: #2c3e50; margin-bottom: 10px; font-size: 16px; } .readinizer-card .card-body { color: #666; font-size: 14px; }',
                'is_default' => false
            ),
            'floating' => array(
                'name' => __('Floating Pro', 'readinizer-pro'),
                'description' => __('Floating bubble with reading stats', 'readinizer-pro'),
                'html' => '<div class="readinizer-floating">{icon}<span class="floating-text">{text}</span></div>',
                'css' => '.readinizer-floating { position: fixed; bottom: 20px; right: 20px; background: rgba(0,123,186,0.9); color: white; padding: 12px 16px; border-radius: 50px; font-size: 13px; z-index: 1000; backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,123,186,0.3); } .readinizer-floating .floating-text { margin-left: 8px; }',
                'is_default' => false
            )
        );

        // Get custom templates from database
        $custom_templates = get_option('readinizer_pro_custom_templates', array());

        return array_merge($default_templates, $custom_templates);
    }

    /**
     * Render templates management page
     */
    public function render_templates_page() {
        $templates = $this->get_templates();

        ob_start();
        ?>
        <div class="templates-manager">
            <h2><?php _e('Custom Post Type Templates', 'readinizer-pro'); ?></h2>
            <p><?php _e('Create and manage custom templates for different post types and display scenarios.', 'readinizer-pro'); ?></p>

            <!-- Template Builder -->
            <div class="template-builder">
                <div class="builder-section">
                    <h3><?php _e('Template Builder', 'readinizer-pro'); ?></h3>

                    <div class="builder-controls">
                        <div class="control-group">
                            <label for="template-name"><?php _e('Template Name', 'readinizer-pro'); ?></label>
                            <input type="text" id="template-name" placeholder="<?php _e('Enter template name', 'readinizer-pro'); ?>">
                        </div>

                        <div class="control-group">
                            <label for="template-description"><?php _e('Description', 'readinizer-pro'); ?></label>
                            <input type="text" id="template-description" placeholder="<?php _e('Template description', 'readinizer-pro'); ?>">
                        </div>
                    </div>

                    <div class="builder-tabs">
                        <button class="tab-button active" data-tab="html"><?php _e('HTML Structure', 'readinizer-pro'); ?></button>
                        <button class="tab-button" data-tab="css"><?php _e('CSS Styles', 'readinizer-pro'); ?></button>
                        <button class="tab-button" data-tab="preview"><?php _e('Live Preview', 'readinizer-pro'); ?></button>
                    </div>

                    <div class="builder-content">
                        <div id="html-tab" class="tab-content active">
                            <label for="template-html"><?php _e('HTML Template', 'readinizer-pro'); ?></label>
                            <textarea id="template-html" rows="10" placeholder="<?php _e('HTML structure using {icon}, {text}, {time}, {words} placeholders', 'readinizer-pro'); ?>"></textarea>
                            <p class="description">
                                <?php _e('Available placeholders:', 'readinizer-pro'); ?>
                                <code>{icon}</code>, <code>{text}</code>, <code>{time}</code>, <code>{words}</code>, <code>{title}</code>
                            </p>
                        </div>

                        <div id="css-tab" class="tab-content">
                            <label for="template-css"><?php _e('CSS Styles', 'readinizer-pro'); ?></label>
                            <textarea id="template-css" rows="10" placeholder="<?php _e('CSS styles for your template', 'readinizer-pro'); ?>"></textarea>
                        </div>

                        <div id="preview-tab" class="tab-content">
                            <h4><?php _e('Live Preview', 'readinizer-pro'); ?></h4>
                            <div id="template-preview" class="template-preview-box">
                                <p><?php _e('Template preview will appear here as you build it.', 'readinizer-pro'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="builder-actions">
                        <button class="button button-primary" id="save-template"><?php _e('Save Template', 'readinizer-pro'); ?></button>
                        <button class="button" id="preview-template"><?php _e('Update Preview', 'readinizer-pro'); ?></button>
                        <button class="button" id="reset-builder"><?php _e('Reset', 'readinizer-pro'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Templates Library -->
            <div class="templates-library">
                <h3><?php _e('Templates Library', 'readinizer-pro'); ?></h3>

                <div class="templates-grid">
                    <?php foreach ($templates as $template_id => $template): ?>
                        <div class="template-card" data-template="<?php echo esc_attr($template_id); ?>">
                            <div class="template-header">
                                <h4><?php echo esc_html($template['name']); ?></h4>
                                <?php if (isset($template['is_default']) && $template['is_default']): ?>
                                    <span class="template-badge default"><?php _e('Default', 'readinizer-pro'); ?></span>
                                <?php else: ?>
                                    <span class="template-badge custom"><?php _e('Custom', 'readinizer-pro'); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="template-description">
                                <p><?php echo esc_html($template['description']); ?></p>
                            </div>

                            <div class="template-preview-mini">
                                <?php 
                                $sample_html = str_replace(
                                    array('{icon}', '{text}', '{time}', '{words}', '{title}'),
                                    array('📖', 'Reading time: 3 minutes • 650 words', '3 minutes', '650 words', 'Reading Info'),
                                    $template['html']
                                );
                                echo $sample_html;
                                ?>
                            </div>

                            <div class="template-actions">
                                <button class="button button-small edit-template" data-template="<?php echo esc_attr($template_id); ?>">
                                    <?php _e('Edit', 'readinizer-pro'); ?>
                                </button>

                                <?php if (!isset($template['is_default']) || !$template['is_default']): ?>
                                    <button class="button button-small delete-template" data-template="<?php echo esc_attr($template_id); ?>">
                                        <?php _e('Delete', 'readinizer-pro'); ?>
                                    </button>
                                <?php endif; ?>

                                <button class="button button-small button-primary use-template" data-template="<?php echo esc_attr($template_id); ?>">
                                    <?php _e('Use', 'readinizer-pro'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Post Type Assignments -->
            <div class="post-type-assignments">
                <h3><?php _e('Post Type Template Assignments', 'readinizer-pro'); ?></h3>
                <p><?php _e('Assign specific templates to different post types.', 'readinizer-pro'); ?></p>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Post Type', 'readinizer-pro'); ?></th>
                            <th><?php _e('Template', 'readinizer-pro'); ?></th>
                            <th><?php _e('Actions', 'readinizer-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $post_types = get_post_types(array('public' => true), 'objects');
                        $assignments = get_option('readinizer_pro_template_assignments', array());

                        foreach ($post_types as $post_type):
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html($post_type->label); ?></strong> (<?php echo esc_html($post_type->name); ?>)</td>
                                <td>
                                    <select name="template_assignment[<?php echo esc_attr($post_type->name); ?>]" class="template-assignment">
                                        <option value=""><?php _e('Default Template', 'readinizer-pro'); ?></option>
                                        <?php foreach ($templates as $template_id => $template): ?>
                                            <option value="<?php echo esc_attr($template_id); ?>" 
                                                <?php selected(isset($assignments[$post_type->name]) ? $assignments[$post_type->name] : '', $template_id); ?>>
                                                <?php echo esc_html($template['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <button class="button button-small save-assignment" data-post-type="<?php echo esc_attr($post_type->name); ?>">
                                        <?php _e('Save', 'readinizer-pro'); ?>
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
     * AJAX handler for saving templates
     */
    public function ajax_save_template() {
        check_ajax_referer('readinizer_pro_template', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'readinizer-pro'));
        }

        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_text_field($_POST['description']);
        $html = wp_kses_post($_POST['html']);
        $css = sanitize_textarea_field($_POST['css']);

        $templates = get_option('readinizer_pro_custom_templates', array());
        $template_id = sanitize_title($name);

        $templates[$template_id] = array(
            'name' => $name,
            'description' => $description,
            'html' => $html,
            'css' => $css,
            'is_default' => false,
            'created' => current_time('mysql')
        );

        update_option('readinizer_pro_custom_templates', $templates);

        wp_send_json_success(__('Template saved successfully!', 'readinizer-pro'));
    }

    /**
     * AJAX handler for deleting templates
     */
    public function ajax_delete_template() {
        check_ajax_referer('readinizer_pro_template', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'readinizer-pro'));
        }

        $template_id = sanitize_text_field($_POST['template_id']);
        $templates = get_option('readinizer_pro_custom_templates', array());

        if (isset($templates[$template_id])) {
            unset($templates[$template_id]);
            update_option('readinizer_pro_custom_templates', $templates);
            wp_send_json_success(__('Template deleted successfully!', 'readinizer-pro'));
        } else {
            wp_send_json_error(__('Template not found.', 'readinizer-pro'));
        }
    }

    /**
     * AJAX handler for saving post type assignments
     */
    public function ajax_save_assignment() {
        check_ajax_referer('readinizer_pro_assignment', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'readinizer-pro'));
        }

        $post_type = sanitize_text_field($_POST['post_type']);
        $template = sanitize_text_field($_POST['template']);

        $assignments = get_option('readinizer_pro_template_assignments', array());
        $assignments[$post_type] = $template;

        update_option('readinizer_pro_template_assignments', $assignments);

        wp_send_json_success(__('Assignment saved successfully!', 'readinizer-pro'));
    }
}

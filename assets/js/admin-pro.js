(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize the Pro admin interface
        initProAdmin();

        // Initialize color pickers
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker({
                change: function(event, ui) {
                    updatePreview();
                }
            });
        }

        // Tab navigation
        setupTabNavigation();

        // Form handlers
        setupFormHandlers();

        // Preview updates - FIXED
        setupPreviewUpdates();

        // Templates functionality - FIXED
        setupTemplateBuilder();

        // Analytics initialization
        if (typeof Chart !== 'undefined') {
            setTimeout(initializeAnalytics, 500);
        }
    });

    function initProAdmin() {
        // Add Pro class to body
        $('body').addClass('readinizer-pro-admin');

        // Animate cards on load
        $('.analytics-card, .template-card, .readinizer-pro-box').each(function(index) {
            $(this).css({
                'opacity': '0',
                'transform': 'translateY(20px)'
            }).delay(index * 100).animate({
                'opacity': '1',
                'transform': 'translateY(0)'
            }, 500);
        });
    }

    function setupTabNavigation() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();

            const targetTab = $(this).data('tab');

            // Update active tab
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            // Show/hide tab content
            $('.tab-content').removeClass('active');
            $('#tab-' + targetTab).addClass('active');

            // Update URL hash
            window.location.hash = targetTab;

            // Trigger tab-specific initialization
            initTabContent(targetTab);
        });

        // Initialize based on URL hash
        const hash = window.location.hash.substring(1);
        if (hash && $('.nav-tab[data-tab="' + hash + '"]').length) {
            $('.nav-tab[data-tab="' + hash + '"]').click();
        } else {
            // Default to first tab
            $('.nav-tab').first().click();
        }
    }

    function initTabContent(tab) {
        switch(tab) {
            case 'analytics':
                if (typeof Chart !== 'undefined') {
                    setTimeout(initializeAnalytics, 100);
                }
                break;
            case 'templates':
                initTemplateBuilder();
                break;
            case 'progress':
                initProgressBarPreview();
                break;
        }

        updatePreview();
    }

    function setupFormHandlers() {
        // Form validation
        $('form').on('submit', function(e) {
            const errors = validateForm($(this));

            if (errors.length > 0) {
                e.preventDefault();
                showNotification('Please fix the following errors:\n\n' + errors.join('\n'), 'error');
                return false;
            }
        });
    }

    function setupPreviewUpdates() {
        // Update preview on any form change
        $(document).on('change keyup', '.tab-content input, .tab-content select, .tab-content textarea', debounce(updatePreview, 300));

        // Color picker specific handling
        $(document).on('change', '.wp-color-picker', updatePreview);

        // Range input display
        $(document).on('input', 'input[type="range"]', function() {
            const $next = $(this).next('.range-value');
            if ($next.length) {
                $next.text($(this).val() + 'px');
            }
            updatePreview();
        });

        // Initial preview update
        setTimeout(updatePreview, 500);
    }

    // COMPLETELY FIXED updatePreview function
    function updatePreview() {
        const enabled = $('#enabled').is(':checked');
        const showReadingTime = $('#show_reading_time').is(':checked');
        const showWordCount = $('#show_word_count').is(':checked');
        const showWpezoCredit = $('#show_wpezo_credit').is(':checked');
        const customText = $('#custom_text').val() || 'Reading time: {time} • {words} words';
        const displayStyle = $('input[name="readinizer_pro_options[display_style]"]:checked').val() || 'modern';
        const textColor = $('#text_color').val() || '#666666';
        const backgroundColor = $('#background_color').val() || '#f8f9fa';
        const borderRadius = $('#border_radius').val() || '6';
        const fontSize = $('#font_size').val() || '14';

        // Sample data
        const readingTime = '3 minutes';
        const wordCount = '650 words';

        // Build display text
        let displayText = customText;
        if (!showReadingTime && !showWordCount) {
            displayText = 'Please enable at least one display option';
        } else if (!showReadingTime) {
            displayText = wordCount;
        } else if (!showWordCount) {
            displayText = readingTime;
        } else {
            displayText = displayText.replace('{time}', readingTime).replace('{words}', wordCount);
        }

        // Build CSS classes
        const cssClass = 'readinizer-display readinizer-' + displayStyle;

        // Build inline styles for custom style
        let inlineStyles = '';
        if (displayStyle === 'custom') {
            inlineStyles = `color: ${textColor}; background-color: ${backgroundColor}; border-radius: ${borderRadius}px; font-size: ${fontSize}px; padding: 8px 14px; border: 1px solid rgba(0,0,0,0.1); font-weight: 500; display: inline-flex; align-items: center; gap: 8px;`;
        }

        // Add WPezo credit if enabled
        let wpezoCredit = '';
        if (showWpezoCredit) {
            wpezoCredit = ' <span class="readinizer-credit">by <a href="https://www.wpezo.com" target="_blank">WPezo</a></span>';
        }

        // Build final HTML
        const iconHtml = enabled ? '📖 ' : '';
        const previewHTML = `<div class="${cssClass}" style="${inlineStyles}">${iconHtml}${displayText}${wpezoCredit}</div>`;

        // Update all preview containers
        $('#readinizer-preview, .preview-box').html(previewHTML);
    }

    function setupTemplateBuilder() {
        // Tab switching for template builder
        $('.tab-button').off('click').on('click', function() {
            const tab = $(this).data('tab');
            $('.tab-button').removeClass('active');
            $('.tab-content').removeClass('active');
            $(this).addClass('active');
            $('#' + tab + '-tab').addClass('active');
        });

        // Live preview update for templates
        $('#template-html, #template-css').off('input').on('input', debounce(updateTemplatePreview, 300));

        // Template actions
        setupTemplateActions();

        // Load template for editing
        $('.edit-template').off('click').on('click', function() {
            const templateId = $(this).data('template');
            loadTemplateForEditing(templateId);
        });

        // Reset builder
        $('#reset-builder').off('click').on('click', function() {
            $('#template-name').val('');
            $('#template-description').val('');
            $('#template-html').val('');
            $('#template-css').val('');
            $('#template-preview').html('<p>Template preview will appear here as you build it.</p>');
        });
    }

    function updateTemplatePreview() {
        const html = $('#template-html').val();
        const css = $('#template-css').val();

        if (!html) {
            $('#template-preview').html('<p>Enter HTML template to see preview.</p>');
            return;
        }

        let previewHtml = html.replace(/{icon}/g, '📖')
                             .replace(/{text}/g, 'Reading time: 3 minutes • 650 words')
                             .replace(/{time}/g, '3 minutes')
                             .replace(/{words}/g, '650 words')
                             .replace(/{title}/g, 'Reading Info');

        const fullPreview = '<style>' + css + '</style>' + previewHtml;
        $('#template-preview').html(fullPreview);
    }

    function loadTemplateForEditing(templateId) {
        // This would typically load template data via AJAX
        // For now, we'll focus on the UI functionality
        console.log('Loading template for editing:', templateId);
    }

    function setupTemplateActions() {
        // Save template
        $('#save-template').off('click').on('click', function() {
            const name = $('#template-name').val().trim();
            const description = $('#template-description').val().trim();
            const html = $('#template-html').val().trim();
            const css = $('#template-css').val().trim();

            if (!name || !html) {
                showNotification('Please fill in template name and HTML structure.', 'error');
                return;
            }

            // Show loading state
            $(this).prop('disabled', true).text('Saving...');

            $.post(ajaxurl, {
                action: 'readinizer_pro_save_template',
                nonce: readinizerProAdmin.nonces.template,
                name: name,
                description: description,
                html: html,
                css: css
            }, function(response) {
                if (response.success) {
                    showNotification(response.data.message || 'Template saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(response.data || 'An error occurred while saving.', 'error');
                }
            }).fail(function() {
                showNotification('Network error occurred. Please try again.', 'error');
            }).always(function() {
                $('#save-template').prop('disabled', false).text('Save Template');
            });
        });

        // Delete template
        $(document).on('click', '.delete-template', function() {
            if (!confirm(readinizerProAdmin.strings.confirm_delete || 'Are you sure you want to delete this template?')) {
                return;
            }

            const $button = $(this);
            const templateId = $button.data('template');

            $button.prop('disabled', true).text('Deleting...');

            $.post(ajaxurl, {
                action: 'readinizer_pro_delete_template',
                nonce: readinizerProAdmin.nonces.template,
                template_id: templateId
            }, function(response) {
                if (response.success) {
                    showNotification('Template deleted successfully!', 'success');
                    $button.closest('.template-card').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showNotification(response.data || 'Error deleting template.', 'error');
                    $button.prop('disabled', false).text('Delete');
                }
            });
        });

        // Use template
        $(document).on('click', '.use-template', function() {
            const $button = $(this);
            const templateId = $button.data('template');

            $button.prop('disabled', true).text('Applying...');

            $.post(ajaxurl, {
                action: 'readinizer_pro_use_template',
                nonce: readinizerProAdmin.nonces.template,
                template_id: templateId
            }, function(response) {
                if (response.success) {
                    showNotification('Template applied successfully!', 'success');
                    // Update the style selection if we're on the style tab
                    $(`input[name="readinizer_pro_options[display_style]"][value="${templateId}"]`).prop('checked', true);
                    updatePreview();
                } else {
                    showNotification(response.data || 'Error applying template.', 'error');
                }
            }).always(function() {
                $button.prop('disabled', false).text('Use');
            });
        });

        // Save assignment
        $(document).on('click', '.save-assignment', function() {
            const $button = $(this);
            const postType = $button.data('post-type');
            const template = $button.closest('tr').find('select').val();

            $button.prop('disabled', true).text('Saving...');

            $.post(ajaxurl, {
                action: 'readinizer_pro_save_assignment',
                nonce: readinizerProAdmin.nonces.template,
                post_type: postType,
                template: template
            }, function(response) {
                if (response.success) {
                    showNotification('Assignment saved!', 'success');
                } else {
                    showNotification(response.data || 'Error saving assignment.', 'error');
                }
            }).always(function() {
                $button.prop('disabled', false).text('Save');
            });
        });
    }

    function initProgressBarPreview() {
        // This function will be called when the progress tab is opened
        updateProgressPreview();

        // Update demo when settings change
        $(document).on('change', '#tab-progress input, #tab-progress select', updateProgressPreview);

        // Range input display
        $(document).on('input', '#tab-progress input[type="range"]', function() {
            $(this).next('.range-value').text($(this).val() + 'px');
            updateProgressPreview();
        });
    }

    function updateProgressPreview() {
        const style = $('input[name="readinizer_pro_options[progress_bar_style]"]:checked').val() || 'linear';
        const position = $('#progress_position').val() || 'top';
        const color = $('#progress_color').val() || '#0073aa';
        const thickness = $('#progress_thickness').val() || '4';
        const showPercentage = $('#show_percentage').is(':checked');

        let previewHtml = '';

        switch(style) {
            case 'linear':
                previewHtml = `<div class="demo-progress-linear" style="background: rgba(0,0,0,0.1); height: ${thickness}px; position: relative; border-radius: 2px; margin: 10px 0; width: 100%;"><div class="demo-progress-fill" style="width: 65%; height: 100%; background: ${color}; border-radius: 2px; transition: width 0.3s ease;"></div></div>`;
                break;
            case 'circular':
                previewHtml = `<div class="demo-progress-circular" style="width: 60px; height: 60px; border: 3px solid rgba(0,0,0,0.1); border-top-color: ${color}; border-radius: 50%; position: relative; margin: 10px auto;"><span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 11px; font-weight: bold; color: ${color};">${showPercentage ? '65%' : ''}</span></div>`;
                break;
            case 'floating':
                previewHtml = `<div class="demo-progress-floating" style="background: ${color}; color: white; padding: 8px 12px; border-radius: 20px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); margin: 10px;">📖 <span>${showPercentage ? '65%' : 'Reading...'}</span></div>`;
                break;
        }

        $('#demo-progress-bar, #preview-progress-bar').html(previewHtml);
    }

    function initializeAnalytics() {
        // Check if chart canvas exists
        const ctx = document.getElementById('monthlyEngagementChart');
        if (!ctx) return;

        // Destroy existing chart if it exists
        if (window.readinizerChart) {
            window.readinizerChart.destroy();
        }

        $.ajax({
            url: readinizerProAdmin.ajaxurl,
            method: 'POST',
            data: {
                action: 'readinizer_pro_get_monthly_analytics',
                nonce: readinizerProAdmin.nonces.analytics
            }
        }).done(function(response) {
            if (!response.success || !Array.isArray(response.data)) {
                return;
            }

            const monthlyData = response.data;

            window.readinizerChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: monthlyData.map(item => item.month),
                    datasets: [{
                        label: 'Views',
                        data: monthlyData.map(item => item.views),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }, {
                        label: 'Engagement %',
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
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Reading Analytics',
                            font: {
                                size: 16,
                                weight: 'bold'
                            }
                        },
                        legend: {
                            display: true,
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Views'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Engagement %'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            // Animate numbers
            animateNumbers();
        });
    }

    function animateNumbers() {
        $('.analytics-card .card-content h3').each(function() {
            const $this = $(this);
            const countTo = parseInt($this.text().replace(/[^0-9]/g, ''));

            if (isNaN(countTo)) return;

            $({ countNum: 0 }).animate({
                countNum: countTo
            }, {
                duration: 2000,
                easing: 'swing',
                step: function() {
                    const num = Math.floor(this.countNum);
                    const originalText = $this.text();
                    const newText = originalText.replace(/[0-9,]+/, num.toLocaleString());
                    $this.text(newText);
                },
                complete: function() {
                    const originalText = $this.text();
                    const newText = originalText.replace(/[0-9,]+/, countTo.toLocaleString());
                    $this.text(newText);
                }
            });
        });
    }

    function validateForm($form) {
        const errors = [];

        // Check required fields
        $form.find('[required]').each(function() {
            if (!$(this).val()) {
                errors.push(`Field "${$(this).attr('name')}" is required.`);
            }
        });

        // Validate numeric fields
        $form.find('input[type="number"]').each(function() {
            const val = parseInt($(this).val());
            const min = parseInt($(this).attr('min'));
            const max = parseInt($(this).attr('max'));

            if (min && val < min) {
                errors.push(`Value must be at least ${min}`);
            }
            if (max && val > max) {
                errors.push(`Value must be no more than ${max}`);
            }
        });

        return errors;
    }

    function showNotification(message, type = 'info') {
        // Remove existing notifications
        $('.readinizer-notification').remove();

        const $notification = $(`
            <div class="notice notice-${type} is-dismissible readinizer-notification" style="margin: 10px 0;">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);

        $('.readinizer-pro-main').prepend($notification);

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            $notification.fadeOut(() => $notification.remove());
        }, 5000);

        // Manual dismiss
        $notification.find('.notice-dismiss').on('click', function() {
            $notification.fadeOut(() => $notification.remove());
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

})(jQuery);

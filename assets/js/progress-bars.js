(function($) {
    'use strict';

    /**
     * Reading Progress Tracker - FIXED VERSION
     */
    class ReadingProgressTracker {
        constructor() {
            this.progressContainer = null;
            this.progressBar = null;
            this.circularChart = null;
            this.percentage = null;
            this.timeRemaining = null;
            this.content = null;
            this.totalWords = 0;
            this.estimatedReadingTime = 0;
            this.contentHeight = 0;
            this.contentTop = 0;
            this.currentProgress = 0;
            this.readingComplete = false;
            this.startTime = Date.now();
            this.maxProgress = 0;

            this.init();
        }

        init() {
            if (!this.shouldShowProgress()) {
                return;
            }

            this.setupProgressBar();
            this.bindEvents();
            this.startTracking();
        }

        shouldShowProgress() {
            // Check if we're on a singular post/page and progress bar exists
            return document.getElementById('readinizer-pro-progress') !== null;
        }

        setupProgressBar() {
            this.progressContainer = document.getElementById('readinizer-pro-progress');
            if (!this.progressContainer) return;

            // Get progress elements
            this.progressBar = this.progressContainer.querySelector('.progress-bar');
            this.circularChart = this.progressContainer.querySelector('.circle');
            this.percentage = this.progressContainer.querySelector('.percentage, .progress-text, .progress-percentage');
            this.timeRemaining = this.progressContainer.querySelector('.time-remaining');

            // Find main content for calculation
            this.content = this.findMainContent();
            if (!this.content) {
                // Fallback to body if no content found
                this.content = document.body;
            }

            // Calculate reading stats
            this.calculateReadingStats();
        }

        findMainContent() {
            // Try to find the main content area with various selectors
            const selectors = [
                '.entry-content',
                '.post-content', 
                '.content',
                'article .content',
                '.single-content',
                'main article',
                '[role="main"] article',
                'article',
                '.wp-block-post-content',
                '.elementor-widget-theme-post-content',
                '.post-entry',
                '.post-body'
            ];

            for (const selector of selectors) {
                const element = document.querySelector(selector);
                if (element && this.getTextContent(element).length > 100) {
                    return element;
                }
            }

            return null;
        }

        getTextContent(element) {
            // Get clean text content, removing scripts and styles
            const clone = element.cloneNode(true);
            const scripts = clone.querySelectorAll('script, style, noscript');
            scripts.forEach(el => el.remove());
            return clone.textContent || clone.innerText || '';
        }

        calculateReadingStats() {
            if (!this.content) return;

            const text = this.getTextContent(this.content);
            const words = text.trim().split(/\s+/).filter(word => word.length > 0).length;
            const wordsPerMinute = 200; // Average reading speed

            this.totalWords = words;
            this.estimatedReadingTime = Math.ceil(words / wordsPerMinute);

            // Calculate content dimensions
            const rect = this.content.getBoundingClientRect();
            this.contentHeight = this.content.scrollHeight;
            this.contentTop = this.getElementTop(this.content);
        }

        bindEvents() {
            // Throttled scroll handler for better performance
            window.addEventListener('scroll', this.throttle(() => {
                this.updateProgress();
            }, 16)); // ~60fps

            window.addEventListener('resize', this.throttle(() => {
                this.calculateReadingStats();
                this.updateProgress();
            }, 250));
        }

        startTracking() {
            // Initial update
            this.updateProgress();

            // Track reading engagement
            this.trackEngagement();
        }

        updateProgress() {
            if (!this.content || !this.progressContainer) return;

            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const documentHeight = Math.max(
                document.body.scrollHeight,
                document.body.offsetHeight,
                document.documentElement.clientHeight,
                document.documentElement.scrollHeight,
                document.documentElement.offsetHeight
            );

            // Calculate progress based on page scroll
            let progress = 0;

            // Method 1: Based on total document scroll
            const maxScroll = documentHeight - windowHeight;
            if (maxScroll > 0) {
                progress = Math.min(100, (scrollTop / maxScroll) * 100);
            }

            // Method 2: Based on content area (more accurate for reading)
            const contentRect = this.content.getBoundingClientRect();
            const contentTop = this.contentTop;
            const contentHeight = this.contentHeight;

            if (contentHeight > windowHeight) {
                const contentStart = Math.max(0, contentTop - windowHeight * 0.2); // Start tracking a bit before content
                const contentEnd = contentTop + contentHeight - windowHeight * 0.8; // End tracking a bit before content ends

                if (scrollTop >= contentStart && contentEnd > contentStart) {
                    const contentProgress = ((scrollTop - contentStart) / (contentEnd - contentStart)) * 100;
                    progress = Math.max(progress, Math.min(100, contentProgress));
                }
            }

            // Ensure progress is within bounds
            progress = Math.max(0, Math.min(100, progress));

            this.currentProgress = progress;
            this.renderProgress(progress);

            // Update reading time remaining
            this.updateTimeRemaining(progress);

            // Check if reading is complete
            if (progress >= 95 && !this.readingComplete) {
                this.onReadingComplete();
            }
        }

        renderProgress(progress) {
            const progressPercent = Math.round(progress);

            // Update linear progress bar
            if (this.progressBar) {
                this.progressBar.style.width = progress + '%';
            }

            // Update circular progress
            if (this.circularChart) {
                const circumference = 2 * Math.PI * 15.9155; // Based on the SVG path radius
                const strokeDashoffset = circumference - (progress / 100) * circumference;
                this.circularChart.style.strokeDasharray = circumference;
                this.circularChart.style.strokeDashoffset = strokeDashoffset;
            }

            // Update percentage text
            if (this.percentage) {
                this.percentage.textContent = progressPercent + '%';
            }

            // Add completion class
            if (progress >= 100) {
                this.progressContainer.classList.add('readinizer-progress-complete');
            } else {
                this.progressContainer.classList.remove('readinizer-progress-complete');
            }

            // Update CSS custom property for other styling
            this.progressContainer.style.setProperty('--progress-value', progress + '%');
        }

        updateTimeRemaining(progress) {
            if (!this.timeRemaining || !this.estimatedReadingTime) return;

            const remainingProgress = Math.max(0, 100 - progress);
            const remainingTime = Math.ceil((remainingProgress / 100) * this.estimatedReadingTime);

            if (remainingTime <= 0) {
                this.timeRemaining.textContent = 'Complete!';
            } else if (remainingTime === 1) {
                this.timeRemaining.textContent = '1 min left';
            } else {
                this.timeRemaining.textContent = remainingTime + ' min left';
            }
        }

        onReadingComplete() {
            this.readingComplete = true;

            // Track completion event
            this.trackCompletion();

            // Hide progress bar if configured
            if (this.progressContainer.classList.contains('hide-when-complete')) {
                setTimeout(() => {
                    this.progressContainer.style.opacity = '0';
                    this.progressContainer.style.pointerEvents = 'none';
                }, 1000);
            }

            // Celebrate completion
            this.celebrateCompletion();
        }

        celebrateCompletion() {
            // Add a celebration animation
            if (this.progressContainer) {
                this.progressContainer.style.transform = 'scale(1.05)';
                this.progressContainer.style.transition = 'transform 0.2s ease';

                setTimeout(() => {
                    this.progressContainer.style.transform = 'scale(1)';
                }, 300);

                // Change color briefly to green
                const originalColor = this.progressContainer.style.getPropertyValue('--progress-color');
                this.progressContainer.style.setProperty('--progress-color', '#4CAF50');

                setTimeout(() => {
                    this.progressContainer.style.setProperty('--progress-color', originalColor || '#0073aa');
                }, 2000);
            }
        }

        trackEngagement() {
            // Track reading engagement every 30 seconds
            setInterval(() => {
                if (this.currentProgress > this.maxProgress) {
                    this.maxProgress = this.currentProgress;
                }

                const timeSpent = (Date.now() - this.startTime) / 1000; // seconds
                this.sendEngagementData(this.maxProgress, timeSpent);
            }, 30000);
        }

        trackCompletion() {
            const timeSpent = (Date.now() - this.startTime) / 1000;
            this.sendEngagementData(100, timeSpent, true);
        }

        sendEngagementData(progress, timeSpent, completed = false) {
            // Only send if we have meaningful data and global exists
            if (progress < 5 || typeof readinizerPro === 'undefined') return;

            const postId = this.progressContainer ? this.progressContainer.getAttribute('data-post-id') : this.getPostId();

            const data = {
                action: 'readinizer_pro_track_engagement',
                post_id: postId,
                progress: Math.round(progress),
                time_spent: Math.round(timeSpent),
                completed: completed,
                total_words: this.totalWords,
                estimated_time: this.estimatedReadingTime,
                nonce: readinizerPro.nonce || ''
            };

            // Send via fetch (non-blocking)
            if (window.fetch && readinizerPro.ajaxurl) {
                const formData = new URLSearchParams(data);

                fetch(readinizerPro.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                }).catch(() => {
                    // Silently fail - analytics shouldn't break the user experience
                });
            }
        }

        getPostId() {
            // Try to get post ID from various sources
            const bodyClasses = document.body.className;
            const postIdMatch = bodyClasses.match(/postid-(\d+)/);
            if (postIdMatch) {
                return postIdMatch[1];
            }

            // Try meta tag
            const postIdMeta = document.querySelector('meta[name="post-id"]');
            if (postIdMeta) {
                return postIdMeta.getAttribute('content');
            }

            // Try data attribute on progress bar
            if (this.progressContainer) {
                const dataPostId = this.progressContainer.getAttribute('data-post-id');
                if (dataPostId) {
                    return dataPostId;
                }
            }

            return 0;
        }

        getElementTop(element) {
            let offsetTop = 0;
            while (element) {
                offsetTop += element.offsetTop;
                element = element.offsetParent;
            }
            return offsetTop;
        }

        throttle(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            }
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Initialize progress tracker
        new ReadingProgressTracker();

        // Add smooth scroll behavior if supported
        if (CSS.supports('scroll-behavior', 'smooth')) {
            document.documentElement.style.scrollBehavior = 'smooth';
        }
    });

    // Export for global access
    window.ReadingProgressTracker = ReadingProgressTracker;

})(jQuery);

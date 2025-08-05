(function($) {
    'use strict';

    // Reading progress tracking
    class ReadingProgressTracker {
        constructor() {
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
            // Check if we're on a singular post/page
            return (document.body.classList.contains('single') || 
                   document.body.classList.contains('page')) &&
                   document.getElementById('readinizer-pro-progress');
        }

        setupProgressBar() {
            this.progressContainer = document.getElementById('readinizer-pro-progress');
            if (!this.progressContainer) return;

            this.progressBar = this.progressContainer.querySelector('.progress-bar');
            this.circularChart = this.progressContainer.querySelector('.circle');
            this.percentage = this.progressContainer.querySelector('.percentage, .progress-text');
            this.timeRemaining = this.progressContainer.querySelector('.time-remaining');

            // Get article content for calculation
            this.content = this.findMainContent();
            if (!this.content) return;

            // Calculate reading stats
            this.calculateReadingStats();
        }

        findMainContent() {
            // Try to find the main content area
            const selectors = [
                '.entry-content',
                '.post-content', 
                '.content',
                'article .content',
                '.single-content',
                'main article',
                '[role="main"] article',
                'article'
            ];

            for (const selector of selectors) {
                const element = document.querySelector(selector);
                if (element && element.innerText && element.innerText.trim().length > 200) {
                    return element;
                }
            }

            return null;
        }

        calculateReadingStats() {
            if (!this.content) return;

            const text = this.content.innerText || this.content.textContent;
            const words = text.trim().split(/\s+/).length;
            const wordsPerMinute = 200; // Average reading speed

            this.totalWords = words;
            this.estimatedReadingTime = Math.ceil(words / wordsPerMinute);
            this.contentHeight = this.content.scrollHeight;
            this.contentTop = this.getElementTop(this.content);
        }

        bindEvents() {
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

            // Track reading completion
            this.trackEngagement();
        }

        updateProgress() {
            if (!this.content) return;

            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const contentTop = this.contentTop;
            const contentHeight = this.content.scrollHeight;

            // Calculate progress through content
            const contentStart = contentTop;
            const contentEnd = contentTop + contentHeight;
            const viewportBottom = scrollTop + windowHeight;

            let progress = 0;

            if (scrollTop >= contentStart) {
                const readableHeight = contentHeight - windowHeight;
                if (readableHeight > 0) {
                    const scrolledContent = Math.min(scrollTop - contentStart, readableHeight);
                    progress = (scrolledContent / readableHeight) * 100;
                } else {
                    progress = 100;
                }
            }

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
                const circumference = 2 * Math.PI * 15.9155; // Based on the SVG path
                const strokeDashoffset = circumference - (progress / 100) * circumference;
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
        }

        updateTimeRemaining(progress) {
            if (!this.timeRemaining || !this.estimatedReadingTime) return;

            const remainingProgress = 100 - progress;
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
            // Add a subtle celebration animation
            if (this.progressContainer) {
                this.progressContainer.style.transform = 'scale(1.05)';
                setTimeout(() => {
                    this.progressContainer.style.transform = 'scale(1)';
                }, 200);

                // Change color briefly to green
                const originalColor = this.progressContainer.style.getPropertyValue('--progress-color');
                this.progressContainer.style.setProperty('--progress-color', '#4CAF50');

                setTimeout(() => {
                    this.progressContainer.style.setProperty('--progress-color', originalColor);
                }, 2000);
            }
        }

        trackEngagement() {
            // Track time spent reading
            this.startTime = Date.now();
            this.maxProgress = 0;

            // Send engagement data periodically
            setInterval(() => {
                if (this.currentProgress > this.maxProgress) {
                    this.maxProgress = this.currentProgress;
                }

                const timeSpent = (Date.now() - this.startTime) / 1000; // seconds
                this.sendEngagementData(this.maxProgress, timeSpent);
            }, 30000); // Every 30 seconds
        }

        trackCompletion() {
            const timeSpent = (Date.now() - this.startTime) / 1000;

            // Send completion data
            this.sendEngagementData(100, timeSpent, true);
        }

        sendEngagementData(progress, timeSpent, completed = false) {
            // Only send if we have meaningful data and readinizerPro global exists
            if (progress < 10 || typeof readinizerPro === 'undefined') return;

            const data = {
                action: 'readinizer_pro_track_engagement',
                post_id: this.getPostId(),
                progress: Math.round(progress),
                time_spent: Math.round(timeSpent),
                completed: completed,
                total_words: this.totalWords,
                estimated_time: this.estimatedReadingTime,
                nonce: readinizerPro.nonce || ''
            };

            // Send via AJAX (non-blocking)
            if (window.fetch && readinizerPro.ajaxurl) {
                fetch(readinizerPro.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
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

        // Add smooth scroll for better UX
        if (CSS.supports('scroll-behavior', 'smooth')) {
            document.documentElement.style.scrollBehavior = 'smooth';
        }
    });

    // Export for global access
    window.ReadingProgressTracker = ReadingProgressTracker;

})(jQuery);

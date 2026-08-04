<!-- Analytics Tracking Script -->
<!-- Place this code before the closing </body> tag on your website -->

<script>
(function() {
    // Configuration - Replace with your project's tracking code
    var TRACKING_CODE = '<?= $trackingCode ?>';
    var API_ENDPOINT = '<?= rtrim($apiEndpoint, '/') ?>/api/track.php';
    
    // Generate unique session ID
    function generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    // Get or create session ID
    function getSessionId() {
        var sessionId = sessionStorage.getItem('analytics_session_id');
        if (!sessionId) {
            sessionId = generateSessionId();
            sessionStorage.setItem('analytics_session_id', sessionId);
        }
        return sessionId;
    }
    
    // Get UTM parameters from URL
    function getUtmParams() {
        var params = {};
        var urlParams = new URLSearchParams(window.location.search);
        ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'].forEach(function(param) {
            if (urlParams.has(param)) {
                params[param] = urlParams.get(param);
            }
        });
        return params;
    }
    
    // Detect device type
    function getDeviceType() {
        var userAgent = navigator.userAgent;
        if (/tablet|ipad|playbook|silk|(android(?!.*mobi))/i.test(userAgent)) {
            return 'tablet';
        }
        if (/mobi|android|phone|iphone|ipod|blackberry|iemobile|opera mini/i.test(userAgent)) {
            return 'mobile';
        }
        return 'desktop';
    }
    
    // Send event to API
    function sendEvent(eventData) {
        var data = Object.assign({
            tracking_code: TRACKING_CODE,
            session_id: getSessionId(),
            timestamp: new Date().toISOString(),
            page_url: window.location.href,
            page_title: document.title,
            referrer: document.referrer,
            device_type: getDeviceType(),
            user_agent: navigator.userAgent
        }, eventData, getUtmParams());
        
        // Use beacon API if available, otherwise fallback to fetch/XMLHttpRequest
        if (navigator.sendBeacon) {
            navigator.sendBeacon(API_ENDPOINT, JSON.stringify(data));
        } else {
            fetch(API_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
                keepalive: true
            }).catch(function(err) {
                console.warn('Analytics event failed:', err);
            });
        }
    }
    
    // Track page view
    function trackPageView() {
        sendEvent({
            event_type: 'page_view',
            event_name: 'Page View'
        });
    }
    
    // Track clicks on specific elements
    function trackClicks() {
        document.addEventListener('click', function(e) {
            var target = e.target;
            
            // Check if element has data-track attribute
            var trackElement = target.closest('[data-track]');
            if (trackElement) {
                var eventName = trackElement.getAttribute('data-track') || 'click';
                sendEvent({
                    event_type: 'click',
                    event_name: eventName,
                    event_data: {
                        element: trackElement.tagName,
                        text: trackElement.textContent?.substr(0, 100),
                        href: trackElement.href || null
                    }
                });
            }
            
            // Track phone clicks
            if (target.closest('a[href^="tel:"]')) {
                sendEvent({
                    event_type: 'click',
                    event_name: 'phone_click',
                    event_data: {
                        phone: target.closest('a').href
                    }
                });
            }
            
            // Track email clicks
            if (target.closest('a[href^="mailto:"]')) {
                sendEvent({
                    event_type: 'click',
                    event_name: 'email_click',
                    event_data: {
                        email: target.closest('a').href
                    }
                });
            }
        });
    }
    
    // Track scroll depth
    function trackScroll() {
        var maxScroll = 0;
        var trackedPercentages = [25, 50, 75, 100];
        var tracked = {};
        
        window.addEventListener('scroll', function() {
            var scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
            var scrollPosition = window.scrollY || window.pageYOffset;
            var scrollPercent = scrollHeight > 0 ? Math.round((scrollPosition / scrollHeight) * 100) : 0;
            
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
                
                trackedPercentages.forEach(function(percent) {
                    if (scrollPercent >= percent && !tracked[percent]) {
                        tracked[percent] = true;
                        sendEvent({
                            event_type: 'scroll',
                            event_name: 'scroll_' + percent + '%',
                            event_data: {
                                scroll_percent: percent
                            }
                        });
                    }
                });
            }
        });
    }
    
    // Track time on page
    function trackTimeOnPage() {
        var startTime = Date.now();
        var intervals = [30, 60, 120, 300]; // seconds
        var tracked = {};
        
        setInterval(function() {
            var elapsed = Math.round((Date.now() - startTime) / 1000);
            
            intervals.forEach(function(seconds) {
                if (elapsed >= seconds && !tracked[seconds]) {
                    tracked[seconds] = true;
                    sendEvent({
                        event_type: 'time_on_page',
                        event_name: 'time_' + seconds + 's',
                        event_data: {
                            seconds: elapsed
                        }
                    });
                    
                    // Track 60 second goal (like original script)
                    if (seconds === 60) {
                        sendEvent({
                            event_type: 'goal_achieved',
                            event_name: '60sec',
                            event_data: {
                                seconds: elapsed
                            }
                        });
                    }
                }
            });
        }, 5000); // Check every 5 seconds
    }
    
    // Track form submissions
    function trackForms() {
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form.tagName === 'FORM') {
                var formId = form.id || 'unnamed_form';
                var formName = form.getAttribute('data-form-name') || formId;
                
                sendEvent({
                    event_type: 'form_submit',
                    event_name: 'form_' + formName,
                    event_data: {
                        form_id: formId,
                        form_action: form.action
                    }
                });
            }
        });
    }
    
    // Initialize tracking
    function init() {
        trackPageView();
        trackClicks();
        trackScroll();
        trackTimeOnPage();
        trackForms();
        
        console.log('[Analytics] Tracking initialized for project:', TRACKING_CODE.substr(0, 16) + '...');
    }
    
    // Start tracking when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Track page unload
    window.addEventListener('beforeunload', function() {
        sendEvent({
            event_type: 'page_exit',
            event_name: 'Page Exit'
        });
    });
})();
</script>

<script>
/**
 * Analytics Service - Universal Tracking Script
 * Отправляет события как в Яндекс.Метрику, так и в наш API
 */
(function() {
    'use strict';

    // Конфигурация (заменяется при установке)
    var CONFIG = {
        apiEndpoint: '/api/track.php',
        trackingCode: '{{TRACKING_CODE}}',
        yandexMetrikaId: null, // Опционально
        debug: false
    };

    // Logger
    function log(level, message, data) {
        if (!CONFIG.debug) return;
        var prefix = '[Analytics]';
        console[level](prefix + ' ' + message, data || '');
    }

    // Генерация уникального session ID
    function generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    // Получение или создание session ID
    function getSessionId() {
        var key = '_analytics_session_id';
        var sessionId = sessionStorage.getItem(key);
        if (!sessionId) {
            sessionId = generateSessionId();
            sessionStorage.setItem(key, sessionId);
        }
        return sessionId;
    }

    // Детектор устройства
    function getDeviceType() {
        var ua = navigator.userAgent;
        if (/tablet|ipad|playbook|silk|android(?!.*mobi)/i.test(ua)) return 'tablet';
        if (/mobi|android|phone|iphone|ipod|blackberry|iemobile|opera mini/i.test(ua)) return 'mobile';
        return 'desktop';
    }

    // Сбор данных о странице
    function getPageData() {
        return {
            url: window.location.href,
            title: document.title,
            referrer: document.referrer,
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight
            }
        };
    }

    // Сбор UTM меток из URL
    function getUtmParams() {
        var params = {};
        var utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        var urlParams = new URLSearchParams(window.location.search);
        
        utmKeys.forEach(function(key) {
            var value = urlParams.get(key);
            if (value) params[key] = value;
        });
        
        return params;
    }

    // Отправка события в API
    function sendEvent(eventType, eventName, eventData) {
        var sessionId = getSessionId();
        var pageData = getPageData();
        var utmParams = getUtmParams();
        
        var payload = {
            tracking_code: CONFIG.trackingCode,
            session_id: sessionId,
            event_type: eventType,
            event_name: eventName || null,
            event_data: eventData || {},
            page_url: pageData.url,
            page_title: pageData.title,
            referrer: pageData.referrer,
            device_type: getDeviceType(),
            timestamp: new Date().toISOString(),
            active_seconds: eventData && eventData.activeSeconds ? eventData.activeSeconds : 0
        };
        
        // Добавляем UTM параметры
        Object.assign(payload, utmParams);
        
        // Отправляем beacon (не блокирует загрузку страницы)
        if (navigator.sendBeacon) {
            var blob = new Blob([JSON.stringify(payload)], {type: 'application/json'});
            navigator.sendBeacon(CONFIG.apiEndpoint, blob);
        } else {
            // Fallback для старых браузеров
            fetch(CONFIG.apiEndpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(function(err) {
                log('error', 'Failed to send event', err);
            });
        }
        
        log('info', 'Event sent', {type: eventType, name: eventName});
    }

    // Отправка цели в Яндекс.Метрику (если настроена)
    function sendYandexGoal(number, target) {
        if (!number || !target) return;
        if (typeof window.ym !== 'function') {
            log('warn', 'Yandex Metrika not loaded');
            return;
        }
        window.ym(number, 'reachGoal', target);
        log('info', 'Yandex goal emitted', {number: number, target: target});
    }

    // Трекинг кликов
    function trackClicks() {
        document.addEventListener('click', function(e) {
            var target = e.target;
            var link = target.closest('a');
            var button = target.closest('button');
            
            if (link) {
                var href = link.href;
                var isMail = href.startsWith('mailto:');
                var isTel = href.startsWith('tel:');
                var isExternal = !href.includes(window.location.hostname);
                
                if (isMail) {
                    sendEvent('click', 'email_click', {email: href.replace('mailto:', '')});
                    if (CONFIG.yandexMetrikaId) sendYandexGoal(CONFIG.yandexMetrikaId, 'email_click');
                } else if (isTel) {
                    sendEvent('click', 'phone_click', {phone: href.replace('tel:', '')});
                    if (CONFIG.yandexMetrikaId) sendYandexGoal(CONFIG.yandexMetrikaId, 'phone_click');
                } else if (isExternal) {
                    sendEvent('click', 'external_link', {url: href});
                }
            } else if (button) {
                sendEvent('click', 'button_click', {
                    text: button.textContent.trim().substr(0, 100),
                    class: button.className
                });
            }
        });
    }

    // Трекинг скролла
    function trackScroll() {
        var maxScroll = 0;
        var scrollThresholds = [25, 50, 75, 100];
        var triggeredThresholds = [];
        
        window.addEventListener('scroll', function() {
            var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            var docHeight = document.documentElement.scrollHeight - window.innerHeight;
            var scrollPercent = docHeight > 0 ? Math.round((scrollTop / docHeight) * 100) : 0;
            
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
                
                scrollThresholds.forEach(function(threshold) {
                    if (scrollPercent >= threshold && !triggeredThresholds.includes(threshold)) {
                        triggeredThresholds.push(threshold);
                        sendEvent('scroll', 'scroll_depth', {depth: threshold});
                    }
                });
            }
        }, {passive: true});
    }

    // Трекинг времени на странице и активности
    function trackActivity() {
        var activeSeconds = 0;
        var lastActivity = Date.now();
        var isPageActive = true;
        var userInteracted = false;
        
        var activityEvents = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        activityEvents.forEach(function(event) {
            document.addEventListener(event, function() {
                if (!isPageActive) return;
                lastActivity = Date.now();
                if (!userInteracted) {
                    userInteracted = true;
                    sendEvent('interaction', 'first_interaction', {});
                }
            }, {passive: true});
        });
        
        // Видимость страницы
        document.addEventListener('visibilitychange', function() {
            isPageActive = document.visibilityState === 'visible';
        });
        
        // Подсчет активных секунд
        setInterval(function() {
            if (!isPageActive || !userInteracted) return;
            
            var timeSinceLastActivity = Date.now() - lastActivity;
            if (timeSinceLastActivity <= 3000) {
                activeSeconds++;
                
                // Отправляем каждые 10 секунд
                if (activeSeconds % 10 === 0) {
                    sendEvent('activity', 'active_time', {activeSeconds: activeSeconds});
                }
                
                // Цель после 60 секунд активности
                if (activeSeconds === 60) {
                    sendEvent('goal_achieved', '60sec_active', {activeSeconds: activeSeconds});
                    if (CONFIG.yandexMetrikaId) sendYandexGoal(CONFIG.yandexMetrikaId, '60sec');
                }
            }
        }, 1000);
        
        // Отправка при уходе со страницы
        window.addEventListener('beforeunload', function() {
            sendEvent('page_exit', 'beforeunload', {activeSeconds: activeSeconds});
        });
    }

    // Трекинг отправки форм
    function trackForms() {
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (!form.tagName || form.tagName !== 'FORM') return;
            
            var formData = {
                action: form.action,
                method: form.method,
                id: form.id,
                class: form.className
            };
            
            sendEvent('form_submit', 'form_submission', formData);
            if (CONFIG.yandexMetrikaId) sendYandexGoal(CONFIG.yandexMetrikaId, 'form_submit');
        });
    }

    // Инициализация
    function init() {
        log('info', 'Analytics script initialized', CONFIG);
        
        // Page view
        sendEvent('page_view', 'page_load', {});
        
        // Включаем трекинги
        trackClicks();
        trackScroll();
        trackActivity();
        trackForms();
    }

    // Запуск после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>

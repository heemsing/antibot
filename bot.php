<script>
// Проверяем, загружена ли Яндекс.Метрика
function isYandexMetrikaLoaded() {
    return typeof window.ym === 'function' && typeof window.ym.constructor === 'function';
}

// Ждем полной загрузки страницы и Яндекс.Метрики
document.addEventListener('DOMContentLoaded', function() {
    // Функция инициализации скрипта
    function initJSSter() {
        if (!isYandexMetrikaLoaded()) {
            console.warn('[JSSter] Yandex Metrika not loaded yet, retrying in 500ms');
            setTimeout(initJSSter, 500);
            return;
        }
        
        var JSSter = (function(e) {
            var t = [`debug`, `info`, `warn`, `error`],
                n = class {
                    constructor(e = ``, t = !0, n = `debug`) {
                        this.prefix = e, this.enabled = t, this.minLevel = n
                    }
                    log(e, ...n) {
                        if (!this.enabled || t.indexOf(e) < t.indexOf(this.minLevel)) return;
                        let r = this.prefix ? `[${this.prefix}]` : ``;
                        console[e](r, ...n)
                    }
                    debug(...e) {
                        this.log(`debug`, ...e)
                    }
                    info(...e) {
                        this.log(`info`, ...e)
                    }
                    warn(...e) {
                        this.log(`warn`, ...e)
                    }
                    error(...e) {
                        this.log(`error`, ...e)
                    }
                },
                r = class {
                    constructor(e) {
                        this.logger = e
                    }
                    get(e) {
                        try {
                            return localStorage.getItem(e)
                        } catch (e) {
                            return this.logger?.error(`LocalStorage.get error:`, e), null
                        }
                    }
                    set(e, t) {
                        try {
                            localStorage.setItem(e, t)
                        } catch (e) {
                            this.logger?.error(`LocalStorage.set error:`, e)
                        }
                    }
                    remove(e) {
                        try {
                            localStorage.removeItem(e)
                        } catch (e) {
                            this.logger?.error(`LocalStorage.remove error:`, e)
                        }
                    }
                    clear() {
                        try {
                            localStorage.clear()
                        } catch (e) {
                            this.logger?.error(`LocalStorage.clear error:`, e)
                        }
                    }
                },
                i = class {
                    constructor(e) {
                        this.logger = e
                    }
                    get(e) {
                        try {
                            return sessionStorage.getItem(e)
                        } catch (e) {
                            return this.logger?.error(`SessionStorage.get error:`, e), null
                        }
                    }
                    set(e, t) {
                        try {
                            sessionStorage.setItem(e, t)
                        } catch (e) {
                            this.logger?.error(`SessionStorage.set error:`, e)
                        }
                    }
                    remove(e) {
                        try {
                            sessionStorage.removeItem(e)
                        } catch (e) {
                            this.logger?.error(`SessionStorage.remove error:`, e)
                        }
                    }
                    clear() {
                        try {
                            sessionStorage.clear()
                        } catch (e) {
                            this.logger?.error(`SessionStorage.clear error:`, e)
                        }
                    }
                },
                a = class {
                    constructor() {
                        this.data = new Map
                    }
                    get(e) {
                        return this.data.get(e) ?? null
                    }
                    set(e, t) {
                        this.data.set(e, t)
                    }
                    remove(e) {
                        this.data.delete(e)
                    }
                    clear() {
                        this.data.clear()
                    }
                };

            function o(e) {
                let t = e.toLowerCase();
                if (/iphone|ipad|ipod/.test(t)) return `ios`;
                if (/android/.test(t)) return `android`;
                if (/windows|win32|win64|winnt/.test(t)) return `windows`;
                if (/macintosh|mac os x|macintel/.test(t)) return `macos`
            }

            function s(e, t, n) {
                switch (n) {
                    case `moreThan`:
                        return e > t;
                    default:
                        return !1
                }
            }

            function c(e) {
                return `jsster_${e}`
            }

            function l() {
                return window.crypto === void 0 ? `xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`.replace(/[xy]/g, e => {
                    let t = Math.random() * 16 | 0;
                    return (e === `x` ? t : t & 3 | 8).toString(16)
                }) : window.crypto.randomUUID()
            }
            var u = class e {
                static #e = this.breakpoints = {
                    desktop: [1024, 1 / 0],
                    tablet: [768, 1023],
                    mobile: [0, 767]
                };
                static #t = this.keys_storage = {
                    currentDevice: c(`currentDevice`),
                    currentSystem: c(`currentSystem`),
                    sessionNumber: c(`sessionNumber`),
                    referer: c(`referer`),
                    clicksOnPage: c(`clicksOnPage`),
                    clicksInSession: c(`clicksInSession`),
                    screenZoom: c(`screenZoom`),
                    viewUrl: c(`viewUrl`),
                    scrollOnPage: c(`scrollOnPage`),
                    viewDepth: c(`viewDepth`),
                    copySession: c(`copySession`),
                    clickOnPhoneSession: c(`clickOnPhoneSession`),
                    clickOnMailSession: c(`clickOnMailSession`),
                    sessionUUID: c(`sessionUUID`),
                    sessionUUIDs: c(`sessionUUIDs`),
                    screenZoomData: c(`screenZoomData`),
                    phoneElements: c(`phoneElements`),
                    mailElements: c(`mailElements`),
                    lastActivity: c(`lastActivity`),
                    activeSeconds: c(`activeSeconds`),
                    userInteracted: c(`userInteracted`)
                };
                constructor(t) {
                    this.map_parameters = {
                        sessionNumber: {
                            init: () => this.initSessionNumber(),
                            check: () => this.checkSessionNumber(),
                            destroy: null
                        },
                        referer: {
                            init: () => this.initReferrer(),
                            check: () => this.checkReferrer(),
                            destroy: null
                        },
                        clicksOnPage: {
                            init: () => this.initClicksOnPage(),
                            check: () => this.checkClicksOnPage(),
                            destroy: () => this.destroyClicksOnPage()
                        },
                        clicksInSession: {
                            init: () => this.initClicksInSession(),
                            check: () => this.checkClicksInSession(),
                            destroy: () => this.destroyClicksInSession()
                        },
                        screenZoom: {
                            init: () => this.initScreenZoom(),
                            check: () => this.checkScreenZoom(),
                            destroy: () => this.destroyScreenZoom()
                        },
                        viewUrl: {
                            init: () => this.initViewUrl(),
                            check: () => this.checkViewUrl(),
                            destroy: null
                        },
                        scrollOnPage: {
                            init: () => this.initScrollOnPage(),
                            check: () => this.checkScrollOnPage(),
                            destroy: () => this.destroyScrollOnPage()
                        },
                        viewDepth: {
                            init: () => this.initViewDepth(),
                            check: () => this.checkViewDepth(),
                            destroy: () => this.destroyViewDepth()
                        },
                        copySession: {
                            init: () => this.initCopySession(),
                            check: () => this.checkCopySession(),
                            destroy: () => this.destroyCopySession()
                        },
                        clickOnPhoneSession: {
                            init: () => this.initClickOnPhoneSession(),
                            check: () => this.checkClickOnPhoneSession(),
                            destroy: () => this.destroyClickOnPhoneSession()
                        },
                        clickOnMailSession: {
                            init: () => this.initClickOnMailSession(),
                            check: () => this.checkClickOnMailSession(),
                            destroy: () => this.destroyClickOnMailSession()
                        }
                    }, this.log = new n(`JSSter`, !0, `info`), this.ls = new r(this.log), this.ss = new i(this.log), this.ms = new a, this.wasGoalEmitted = !1, this.checkers = [], this.activityEvents = ['mousemove', 'keydown', 'touchstart', 'scroll', 'click'], this.scrollChecked = false, this.activityTimer = null, this.isPageActive = true, this.userHasInteracted = false, this.init = () => {
                        // Привязываем методы
                        this.handleUserActivity = this.handleUserActivity.bind(this);
                        this.handleScrollOnPage = this.handleScrollOnPage.bind(this);
                        this.activityInterval = this.activityInterval.bind(this);
                        
                        // Проверяем наличие Яндекс.Метрики
                        if (!isYandexMetrikaLoaded()) {
                            this.log.warn(`Yandex Metrika not found, engine not initialized`);
                            return !1;
                        }
                        
                        document.addEventListener('visibilitychange', () => {
                            this.isPageActive = document.visibilityState === 'visible';
                            this.log.debug(`Page visibility changed`, { isVisible: this.isPageActive });
                            if (this.isPageActive && this.userHasInteracted) {
                                this.ss.set(e.keys_storage.lastActivity, Date.now().toString());
                            }
                        });
                        
                        // Инициализация базовых компонентов
                        if (!this.initSession() || !this.initCurrentDevice() || !this.initCurrentSystem()) {
                            this.log.warn(`Failed to initialize basic components`);
                            return !1;
                        }
                        
                        // Проверка устройств и систем
                        if (!this.checkDevices()) {
                            this.log.warn(`Device check failed`);
                            return !1;
                        }
                        if (!this.checkSystems()) {
                            this.log.warn(`System check failed`);
                            return !1;
                        }
                        
                        this.checkers.push(this.checkDevices);
                        this.checkers.push(this.checkSystems);
                        
                        // Инициализация параметров
                        Object.keys(this.config.parameters).forEach(e => {
                            if (this.map_parameters[e]?.init) {
                                this.map_parameters[e].init();
                            }
                            if (this.map_parameters[e]?.check) {
                                this.checkers.push(this.map_parameters[e].check);
                            }
                        });
                        
                        // Инициализация отслеживания активности
                        this.initActivityTracking();
                        
                        this.log.info(`Engine successfully initialized`);
                        return !0;
                    }, this.destroy = () => {
                        Object.keys(this.map_parameters).forEach(e => {
                            if (this.map_parameters[e]?.destroy) {
                                this.map_parameters[e].destroy();
                            }
                        });
                        this.destroyActivityTracking();
                        this.log.info(`Engine destroyed`);
                    }, this.initSession = () => {
                        let t = this.ss.get(e.keys_storage.sessionUUID);
                        t || (t = l(), this.ss.set(e.keys_storage.sessionUUID, t), this.ms.set(e.keys_storage.sessionUUID, t), this.ls.set(e.keys_storage.sessionUUIDs, JSON.stringify([...JSON.parse(this.ls.get(e.keys_storage.sessionUUIDs) ?? `[]`), t]))), this.ms.set(e.keys_storage.sessionUUID, t), this.log.debug(`Session initialized`, {
                            sessionUUID: t
                        });
                        return !0;
                    }, this.initCurrentDevice = () => {
                        let t = window.innerWidth,
                            n = t >= e.breakpoints.desktop[0] && t <= e.breakpoints.desktop[1] ? `desktop` : t >= e.breakpoints.tablet[0] && t <= e.breakpoints.tablet[1] ? `tablet` : `mobile`;
                        this.ms.set(e.keys_storage.currentDevice, n), this.log.debug(`Current device initialized`, {
                            deviceId: n
                        });
                        return !0;
                    }, this.initCurrentSystem = () => {
                        let t = navigator.userAgent;
                        this.ms.set(e.keys_storage.currentSystem, t), this.log.debug(`Current system initialized`, {
                            os: t
                        });
                        return !0;
                    }, this.check = () => {
                        if (this.log.debug(`Checking...`), this.wasGoalEmitted) return this.log.debug(`Goal already emitted`), !1;
                        if (this.scrollChecked) {
                            this.log.info(`Scroll condition passed`);
                            this.emitGoal();
                            return !0;
                        }
                        const activeSeconds = Number(this.ss.get(e.keys_storage.activeSeconds) || '0');
                        if (activeSeconds >= 60) {
                            this.log.info(`Activity condition passed - ${activeSeconds} active seconds`);
                            this.emitGoal();
                            return !0;
                        }
                        return !1
                    }, this.checkDevices = () => {
                        let t = this.config.devices;
                        if (t.length === 0) {
                            this.log.debug(`No devices configured`);
                            return !1;
                        }
                        let n = window.innerWidth,
                            r = n >= e.breakpoints.desktop[0] && n <= e.breakpoints.desktop[1] ? `desktop` : n >= e.breakpoints.tablet[0] && n <= e.breakpoints.tablet[1] ? `tablet` : `mobile`;
                        if (t.includes(r)) {
                            this.log.debug(`Device configured`, {
                                deviceId: r
                            });
                            return !0;
                        } else {
                            this.log.debug(`Device not configured`, {
                                deviceId: r
                            });
                            return !1;
                        }
                    }, this.checkSystems = () => {
                        let t = this.config.systems;
                        if (t.length === 0) {
                            this.log.debug(`No systems configured`);
                            return !1;
                        }
                        let n = o(this.ms.get(e.keys_storage.currentSystem));
                        if (n && t.includes(n)) {
                            this.log.debug(`System configured`, {
                                osName: n
                            });
                            return !0;
                        } else {
                            this.log.debug(`System not configured`, {
                                osName: n || 'unknown'
                            });
                            return !1;
                        }
                    }, this.initSessionNumber = () => {
                        let t = Number(JSON.parse(this.ls.get(e.keys_storage.sessionUUIDs) ?? `[]`).length ?? 0);
                        this.ms.set(e.keys_storage.sessionNumber, t.toString()), this.log.debug(`Session number initialized`, {
                            currentSessionNumber: t
                        });
                        return !0;
                    }, this.checkSessionNumber = () => {
                        let {
                            operator: t,
                            value: n
                        } = this.config.parameters.sessionNumber || {}, r = this.ms.get(e.keys_storage.sessionNumber), i = n ? s(Number(r), n, t) : !0;
                        return this.log.debug(`Session number checked`, {
                            sessionNumber: r,
                            result: i
                        }), i
                    }, this.initReferrer = () => {
                        let t = document.referrer;
                        this.ms.set(e.keys_storage.referrer, t), this.log.debug(`Referrer initialized`, {
                            referrer: t
                        });
                        return !0;
                    }, this.checkReferrer = () => {
                        let {
                            value: t
                        } = this.config.parameters.referer || {}, n = this.ms.get(e.keys_storage.referrer);
                        if (!t || !n) {
                            this.log.debug(`Referrer check skipped`);
                            return !0;
                        }
                        let r = n.toLowerCase(),
                            i = e => e.match(/^(?:https?:\/\/)?(?:www\.)?([^/:]+)/i)?.[1] || ``,
                            a = e => e.replace(/^www\./, ``).split(`:`)[0],
                            o = t.some(e => {
                                let t = e.toLowerCase().trim();
                                if (/^https?:\/\//i.test(t)) {
                                    if (a(i(t)) !== a(i(r))) return !1;
                                    let e = t.match(/^https?:\/\/[^/]+(\/.*)?$/i)?.[1] || `/`;
                                    return e === `/` || r.match(RegExp(`^https?://[^/]+${e.replace(/[.*+?^${}()|[\]\\]/g,`\\$&`)}`, `i`))
                                }
                                let n = a(t),
                                    o = a(i(r));
                                return o === n || o.endsWith(`.${n}`)
                            });
                        return this.log.debug(`Referrer checked`, {
                            referrer: n,
                            value: t,
                            result: o
                        }), o
                    }, this.handleClickOnPage = () => {
                        let t = Number(this.ms.get(e.keys_storage.clicksOnPage)) + 1;
                        this.ms.set(e.keys_storage.clicksOnPage, t.toString());
                        this.checkClicksOnPage() && (this.offClicksOnPage(), this.check());
                    }, this.initClicksOnPage = () => {
                        let t = Number(this.ms.get(e.keys_storage.clicksOnPage)) || 0;
                        this.ms.set(e.keys_storage.clicksOnPage, t.toString());
                        document.addEventListener(`click`, this.handleClickOnPage);
                        document.addEventListener(`touchstart`, this.handleClickOnPage);
                        this.log.debug(`Clicks on page initialized`, {
                            clicksOnPage: t
                        });
                        return !0;
                    }, this.checkClicksOnPage = () => {
                        let {
                            operator: t,
                            value: n
                        } = this.config.parameters.clicksOnPage || {}, r = Number(this.ms.get(e.keys_storage.clicksOnPage)), i = n ? s(Number(r), n, t) : !0;
                        return this.log.debug(`Clicks on page checked`, {
                            clicksOnPage: r,
                            result: i
                        }), i
                    }, this.offClicksOnPage = () => {
                        document.removeEventListener(`click`, this.handleClickOnPage);
                        document.removeEventListener(`touchstart`, this.handleClickOnPage);
                        this.log.debug(`Clicks on page off`);
                    }, this.destroyClicksOnPage = () => {
                        this.offClicksOnPage();
                        this.log.debug(`Clicks on page destroyed`);
                    }, this.handleClickInSession = () => {
                        let t = Number(this.ss.get(e.keys_storage.clicksInSession)) + 1;
                        this.ss.set(e.keys_storage.clicksInSession, t.toString());
                        this.checkClicksInSession() && (this.offClicksInSession(), this.check());
                    }, this.initClicksInSession = () => {
                        let t = Number(this.ss.get(e.keys_storage.clicksInSession)) || 0;
                        this.ss.set(e.keys_storage.clicksInSession, t.toString());
                        document.addEventListener(`click`, this.handleClickInSession);
                        document.addEventListener(`touchstart`, this.handleClickInSession);
                        this.log.debug(`Clicks in session initialized`, {
                            clicksInSession: t
                        });
                        return !0;
                    }, this.checkClicksInSession = () => {
                        let {
                            operator: t,
                            value: n
                        } = this.config.parameters.clicksInSession || {}, r = Number(this.ss.get(e.keys_storage.clicksInSession)), i = n ? s(Number(r), n, t) : !0;
                        return this.log.debug(`Clicks in session checked`, {
                            clicksInSession: r,
                            result: i
                        }), i
                    }, this.offClicksInSession = () => {
                        document.removeEventListener(`click`, this.handleClickInSession);
                        document.removeEventListener(`touchstart`, this.handleClickInSession);
                        this.log.debug(`Clicks in session off`);
                    }, this.destroyClicksInSession = () => {
                        this.offClicksInSession();
                        this.ss.remove(e.keys_storage.clicksInSession);
                        this.log.debug(`Clicks in session destroyed`);
                    }, this.getScreenZoomData = () => this.supportsVisualViewport && window.visualViewport ? {
                        width: window.visualViewport.width,
                        height: window.visualViewport.height,
                        scale: window.visualViewport.scale
                    } : {
                        width: window.innerWidth,
                        height: window.innerHeight,
                        scale: 1
                    }, this.updateScreenZoomData = t => {
                        let n = JSON.parse(this.ms.get(e.keys_storage.screenZoomData) ?? `{}`);
                        this.ms.set(e.keys_storage.screenZoomData, JSON.stringify({
                            ...n,
                            ...t
                        }))
                    }, this.initScreenZoom = () => {
                        let t = this.ss.get(e.keys_storage.screenZoom) === `true`;
                        if (!t) {
                            let t = this.getScreenZoomData();
                            this.ms.set(e.keys_storage.screenZoomData, JSON.stringify(t)), this.ss.set(e.keys_storage.screenZoom, `false`), (this.supportsVisualViewport ? window.visualViewport : window)?.addEventListener(`resize`, this.handleScreenZoom.bind(this))
                        }
                        this.log.debug(`Screen zoom initialized`, {
                            isScreenZoomActive: t
                        });
                        return !0;
                    }, this.isOrientationChanged = (e, t) => this.supportsVisualViewport && screen.orientation ? screen.orientation.angle % 180 != 0 && e.scale === 1 : e.width === t.height && e.height === t.width || Math.abs(e.width - (t.width ?? 0)) > 200, this.handleScreenZoom = () => {
                        try {
                            let t = JSON.parse(this.ms.get(e.keys_storage.screenZoomData) ?? `{}`),
                                n = this.getScreenZoomData(),
                                r = t.scale ?? 1,
                                i = this.supportsVisualViewport ? n.scale : null,
                                a = t.width && t.width > 0 ? t.width / n.width : 1;
                            if (n.scale = i !== null && i !== r ? i : a, this.isOrientationChanged(n, t)) return void this.updateScreenZoomData({
                                width: n.width,
                                height: n.height,
                                scale: n.scale
                            });
                            n.scale !== r && (this.updateScreenZoomData({
                                width: n.width,
                                height: n.height,
                                scale: n.scale
                            }), this.triggerScreenZoom())
                        } catch (e) {
                            this.log.error(`Error handling screen zoom`, {
                                error: e
                            })
                        }
                    }, this.triggerScreenZoom = () => {
                        this.ss.set(e.keys_storage.screenZoom, `true`), this.ms.remove(e.keys_storage.screenZoomData), this.offScreenZoom(), this.check()
                    }, this.checkScreenZoom = () => {
                        let t = this.ss.get(e.keys_storage.screenZoom) === `true`;
                        return this.log.debug(`Screen zoom checked`, {
                            result: t
                        }), t
                    }, this.offScreenZoom = () => {
                        window.visualViewport?.removeEventListener(`resize`, this.handleScreenZoom), window.removeEventListener(`resize`, this.handleScreenZoom), this.log.debug(`Screen zoom off`)
                    }, this.destroyScreenZoom = () => {
                        this.offScreenZoom(), this.ss.remove(e.keys_storage.screenZoom), this.log.debug(`Screen zoom destroyed`)
                    }, this.initViewUrl = () => {
                        let t = window.location.href;
                        this.ms.set(e.keys_storage.viewUrl, t), this.log.debug(`View url initialized`, {
                            viewUrl: t
                        });
                        return !0;
                    }, this.checkViewUrl = () => {
                        let {
                            value: t
                        } = this.config.parameters.viewUrl || {}, n = this.ms.get(e.keys_storage.viewUrl);
                        if (!t || !n) {
                            this.log.debug(`View url check skipped`);
                            return !0;
                        }
                        let r = n.toLowerCase(),
                            i = window.location,
                            a = `${i.protocol}//${i.host}`;
                        try {
                            a = new URL(n).origin
                        } catch {}
                        let o = e => {
                                let t = e.toLowerCase().trim();
                                return t.includes(`://`) ? new RegExp(s(t), `i`) : t.startsWith(`/`) ? new RegExp(s(a + t), `i`) : (t.includes(`.`) || t.includes(`:`)) && !t.startsWith(`.`) && !t.startsWith(`/`) ? new RegExp(s(`${i.protocol}//${t}`), `i`) : new RegExp(s(`${a}/${t}`), `i`)
                            },
                            s = e => e.replace(/[.*+?^${}()|[\]\\]/g, `\\$&`),
                            c = t.map(o).some(e => e.test(r));
                        return this.log.debug(`View url checked`, {
                            viewUrl: n,
                            result: c
                        }), c
                    }, this.initScrollOnPage = () => {
                        let t = Number(this.ss.get(e.keys_storage.scrollOnPage)) || 0;
                        this.checkScrollOnPage() || (this.ss.set(e.keys_storage.scrollOnPage, t.toString()), window.addEventListener(`scroll`, this.handleScrollOnPage)), this.log.debug(`Scroll on page initialized`, {
                            currentScrollPercent: t
                        });
                        return !0;
                    }, this.handleScrollOnPage = t => {
                        let n = Number(this.ss.get(e.keys_storage.scrollOnPage)) || 0,
                            r = window.scrollY || document.documentElement.scrollTop,
                            i = document.documentElement.scrollHeight - window.innerHeight,
                            a = i > 0 ? Math.round(r / i * 100) : 0;
                        a > n && (this.ss.set(e.keys_storage.scrollOnPage, a.toString()), this.checkScrollOnPage() && (this.offScrollOnPage(), this.scrollChecked = true, this.check()))
                    }, this.checkScrollOnPage = () => {
                        let {
                            operator: t,
                            value: n
                        } = this.config.parameters.scrollOnPage || {}, r = Number(this.ss.get(e.keys_storage.scrollOnPage)) || 0, i = n ? s(Number(r), n, t) : !0;
                        if (i) this.scrollChecked = true;
                        return this.log.debug(`Scroll on page checked`, {
                            scrollOnPage: r,
                            result: i
                        }), i
                    }, this.offScrollOnPage = () => {
                        window.removeEventListener(`scroll`, this.handleScrollOnPage);
                        this.log.debug(`Scroll on page off`);
                    }, this.destroyScrollOnPage = () => {
                        this.offScrollOnPage();
                        this.ss.remove(e.keys_storage.scrollOnPage);
                        this.log.debug(`Scroll on page destroyed`);
                    }, this.initViewDepth = () => {
                        let t = JSON.parse(this.ss.get(e.keys_storage.viewDepth) || `[]`);
                        t.includes(window.location.href) || t.push(window.location.href), this.ss.set(e.keys_storage.viewDepth, JSON.stringify(t)), this.log.debug(`View depth initialized`, {
                            viewDepthArray: t
                        });
                        return !0;
                    }, this.checkViewDepth = () => {
                        let {
                            operator: t,
                            value: n
                        } = this.config.parameters.viewDepth || {}, r = JSON.parse(this.ss.get(e.keys_storage.viewDepth) || `[]`).length, i = n ? s(Number(r), n, t) : !0;
                        return this.log.debug(`View depth checked`, {
                            viewDepth: r,
                            result: i
                        }), i
                    }, this.destroyViewDepth = () => {
                        this.ss.remove(e.keys_storage.viewDepth);
                        this.log.debug(`View depth destroyed`);
                    }, this.handleCopySession = () => {
                        this.ss.set(e.keys_storage.copySession, `true`);
                        this.checkCopySession() && (this.offCopySession(), this.check());
                    }, this.initCopySession = () => {
                        let t = this.ss.get(e.keys_storage.copySession) === `true`;
                        t || (this.ss.set(e.keys_storage.copySession, `false`), document.addEventListener(`copy`, this.handleCopySession)), this.log.debug(`Copy session initialized`, {
                            copySession: t
                        });
                        return !0;
                    }, this.checkCopySession = () => {
                        let t = this.ss.get(e.keys_storage.copySession) === `true`;
                        return this.log.debug(`Copy session checked`, {
                            result: t
                        }), t
                    }, this.offCopySession = () => {
                        document.removeEventListener(`copy`, this.handleCopySession);
                        this.log.debug(`Copy session off`);
                    }, this.destroyCopySession = () => {
                        this.offCopySession();
                        this.ss.remove(e.keys_storage.copySession);
                        this.log.debug(`Copy session destroyed`);
                    }, this.handleClickOnPhoneSession = t => {
                        if (t.target instanceof HTMLAnchorElement && t.target.href.startsWith(`tel:`)) {
                            this.ss.set(e.keys_storage.clickOnPhoneSession, `true`);
                            this.checkClickOnPhoneSession() && (this.offClickOnPhoneSession(), this.check());
                        }
                    }, this.initClickOnPhoneSession = () => {
                        let t = this.ss.get(e.keys_storage.clickOnPhoneSession) === `true`;
                        t || (this.ss.set(e.keys_storage.clickOnPhoneSession, `false`), document.addEventListener(`click`, this.handleClickOnPhoneSession)), this.log.debug(`Click on phone session initialized`, {
                            clickOnPhoneSession: t
                        });
                        return !0;
                    }, this.checkClickOnPhoneSession = () => {
                        let t = this.ss.get(e.keys_storage.clickOnPhoneSession) === `true`;
                        return this.log.debug(`Click on phone session checked`, {
                            result: t
                        }), t
                    }, this.offClickOnPhoneSession = () => {
                        document.removeEventListener(`click`, this.handleClickOnPhoneSession);
                        this.log.debug(`Click on phone session off`);
                    }, this.destroyClickOnPhoneSession = () => {
                        this.offClickOnPhoneSession();
                        this.ss.remove(e.keys_storage.clickOnPhoneSession);
                        this.log.debug(`Click on phone session destroyed`);
                    }, this.handleClickOnMailSession = t => {
                        if (t.target instanceof HTMLAnchorElement && t.target.href.startsWith(`mailto:`)) {
                            this.ss.set(e.keys_storage.clickOnMailSession, `true`);
                            this.checkClickOnMailSession() && (this.offClickOnMailSession(), this.check());
                        }
                    }, this.initClickOnMailSession = () => {
                        let t = this.ss.get(e.keys_storage.clickOnMailSession) === `true`;
                        t || (this.ss.set(e.keys_storage.clickOnMailSession, `false`), document.addEventListener(`click`, this.handleClickOnMailSession)), this.log.debug(`Click on mail session initialized`, {
                            clickOnMailSession: t
                        });
                        return !0;
                    }, this.checkClickOnMailSession = () => {
                        let t = this.ss.get(e.keys_storage.clickOnMailSession) === `true`;
                        return this.log.debug(`Click on mail session checked`, {
                            result: t
                        }), t
                    }, this.offClickOnMailSession = () => {
                        document.removeEventListener(`click`, this.handleClickOnMailSession);
                        this.log.debug(`Click on mail session off`);
                    }, this.destroyClickOnMailSession = () => {
                        this.offClickOnMailSession();
                        this.ss.remove(e.keys_storage.clickOnMailSession);
                        this.log.debug(`Click on mail session destroyed`);
                    }, this.emitGoal = () => {
                        if (window.ym && this.config.scriptData.number && this.config.scriptData.target) {
                            this.log.info(`GOAL EMITTED`, {
                                number: this.config.scriptData.number,
                                target: this.config.scriptData.target
                            });
                            window.ym(this.config.scriptData.number, `reachGoal`, this.config.scriptData.target);
                            this.wasGoalEmitted = !0;
                            this.destroy();
                        }
                    }, this.initActivityTracking = () => {
                        this.ss.set(e.keys_storage.activeSeconds, '0');
                        this.ss.set(e.keys_storage.userInteracted, 'false');
                        
                        this.activityEvents.forEach(event => {
                            document.addEventListener(event, this.handleUserActivity);
                        });
                        
                        this.activityTimer = setInterval(this.activityInterval, 1000);
                        this.log.debug(`Activity tracking initialized`);
                    }, this.handleUserActivity = () => {
                        if (!this.isPageActive) return;
                        
                        const now = Date.now();
                        this.ss.set(e.keys_storage.lastActivity, now.toString());
                        
                        if (!this.userHasInteracted) {
                            this.userHasInteracted = true;
                            this.ss.set(e.keys_storage.userInteracted, 'true');
                            this.log.debug(`✅ First user interaction detected`);
                        }
                        
                        this.log.debug(`User activity detected`, {
                            time: new Date(now).toISOString()
                        });
                    }, this.activityInterval = () => {
                        if (!this.isPageActive || !this.userHasInteracted) return;
                        
                        const now = Date.now();
                        const lastActivity = Number(this.ss.get(e.keys_storage.lastActivity));
                        let activeSeconds = Number(this.ss.get(e.keys_storage.activeSeconds) || '0');
                        
                        const timeSinceLastActivity = now - lastActivity;
                        
                        if (timeSinceLastActivity <= 3000) {
                            activeSeconds++;
                            this.ss.set(e.keys_storage.activeSeconds, activeSeconds.toString());
                            this.log.debug(`✅ Active second counted`, {
                                activeSeconds: activeSeconds,
                                timeSinceLastActivity: timeSinceLastActivity
                            });
                            
                            if (activeSeconds >= 60) {
                                this.check();
                            }
                        } else {
                            this.log.debug(`⏳ No recent activity`, {
                                timeSinceLastActivity: timeSinceLastActivity
                            });
                        }
                    }, this.destroyActivityTracking = () => {
                        clearInterval(this.activityTimer);
                        
                        this.activityEvents.forEach(event => {
                            document.removeEventListener(event, this.handleUserActivity);
                        });
                        
                        this.ss.remove(e.keys_storage.lastActivity);
                        this.ss.remove(e.keys_storage.activeSeconds);
                        this.ss.remove(e.keys_storage.userInteracted);
                        this.log.debug(`Activity tracking destroyed`);
                    }, this.config = JSON.parse(t), this.supportsVisualViewport = !!window.visualViewport, this.init() || this.destroy()
                }
            };
            
            // Создаем экземпляр движка
            new u(`{"devices":["desktop","tablet","mobile"],"systems":["windows","macos","ios","android"],"parameters":{},"scriptData":{"number":"105559927","target":"60sec"}}`);
        })();
        
        console.log('[JSSter] Script fully initialized and active');
    }
    
    // Начинаем инициализацию
    initJSSter();
});
</script>

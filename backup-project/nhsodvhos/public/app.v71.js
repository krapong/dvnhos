// =========================================================
// NHSO Executive Dashboard - JavaScript Application Logic
// Supports Role-Based Access Control (RBAC) & Backend Sync
// =========================================================

function normalizeBaseUrl(url) {
    return String(url || '').trim().replace(/\/+$/, '');
}

function normalizeApiRoot(url) {
    const clean = normalizeBaseUrl(url);
    if (!clean) return '';
    if (clean === '/api') return clean;
    if (/^https?:\/\//i.test(clean)) {
        return clean.endsWith('/api') ? clean : `${clean}/api`;
    }
    return clean.startsWith('/') ? clean : `/${clean}`;
}

function isLoopbackBackendUrl(url) {
    const value = String(url || '').trim().toLowerCase();
    return value.includes('localhost') || value.includes('127.0.0.1') || value.includes('[::1]');
}

function getConfiguredBackendUrl() {
    const currentOriginApi = window.location.protocol === 'http:' || window.location.protocol === 'https:'
        ? `${window.location.origin}/api`
        : '';
    const configured = normalizeApiRoot(window.__NHSO_API_ROOT__ || window.__NHSO_BACKEND_URL__ || '');
    if (currentOriginApi) return currentOriginApi;
    if (configured) return configured;
    return '/api';
}

let BACKEND_URL = getConfiguredBackendUrl();
window.setBackendApiUrl = function(url) {
    const clean = normalizeApiRoot(url);
    BACKEND_URL = clean || getConfiguredBackendUrl();
    window.__NHSO_API_ROOT__ = BACKEND_URL;
    return BACKEND_URL;
};
window.getBackendApiUrl = function() {
    return BACKEND_URL;
};


if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
    Chart.register(ChartDataLabels);
}

const HOSPITAL_DB_MAP = {
    '05831': 'เธจเธเนเธเธ.เนเธเธเนเธเธเธฒเธเนเธเธ',
    '05840': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเธญเธเนเธเธ',
    '05832': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธ',
    '5832': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธ',
    '05839': 'เธฃเธ.เธชเธ•.เนเธเธเธฒเธเนเธเธเธดเธเนเธเธเนเธเธ',
    '05834': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเธญเธเนเธเธเธฒเธ',
    '05833': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธ',
    '05836': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเนเธเธเธฒเธเนเธเธ',
    '05835': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    '05838': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    '13987': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเธฅเธเนเธเธเนเธเธ',
    '10266': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธ',
    '22380': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเธญเธ',
    '42313': 'เธฃเธ.เธชเธ•.เนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    '11120': 'โรงพยาบาล 11120' // Added 11120 based on previous context
};

const SEAMLESS_SUBTAB_KEYS = new Set([
    'ckd',
    'ktp',
    'napp',
    'tb',
    'downs',
    'thal_screen',
    'cervical',
    'psych',
    'thal_disease',
    'anc',
    'rehab',
    'thai_med',
    'morprom',
    'ncd'
]);

let activeTab = 'smartmoney';
let database = []; // In-memory cache of dashboard data
let currentSession = null; // Stores { token, username, fullName, role }
let mockSystemUsers = {
    admin: { fullName: 'ผู้ดูแลระบบ (Sandbox)', role: 'ADMIN', password: 'CHANGE_ME_BEFORE_USE', status: 'approved' },
    officer: { fullName: 'เจ้าหน้าที่รับข้อมูล (Sandbox)', role: 'OFFICER', password: 'CHANGE_ME_BEFORE_USE', status: 'approved' },
    executive: { fullName: 'ผู้บริหาร (Sandbox)', role: 'EXECUTIVE', password: 'CHANGE_ME_BEFORE_USE', status: 'approved' },
    user: { fullName: 'ผู้ใช้งานทั่วไป (Sandbox)', role: 'USER', password: 'CHANGE_ME_BEFORE_USE', status: 'approved' }
};
let mockRegisteredUsers = {}; // Stores registered Sandbox users
let isBackendActive = false;
let sharedUiPreferences = {};

const SHARED_PREF_KEYS = {
    activeTab: 'activeTab',
    yearSelect: 'yearSelect',
    hospitalInput: 'hospitalInput',
    startMonthSelect: 'startMonthSelect',
    endMonthSelect: 'endMonthSelect',
    dashboardFile: 'dashboardFile',
    seamlessSubtab: 'seamlessSubtab',
    seamlessSubtabName: 'seamlessSubtabName'
};

const MENU_PAGE_META = {
    smartmoney: {
        eyebrow: 'Smart Money',
        title: 'ภาพรวมระบบ',
        subtitle: 'แสดงภาพรวมการเงิน การนำเข้าไฟล์ และสถานะรายการล่าสุด',
        badge: 'พร้อมใช้งาน'
    },
    op: {
        eyebrow: 'ผู้ป่วยนอก (OP)',
        title: 'เมนูผู้ป่วยนอก',
        subtitle: 'สรุปยอด การนำเข้า และรายการผิดพลาดของเมนู OP',
        badge: 'OP'
    },
    ip: {
        eyebrow: 'ผู้ป่วยใน (IP)',
        title: 'เมนูผู้ป่วยใน',
        subtitle: 'สรุปยอด การนำเข้า และรายการผิดพลาดของเมนู IP',
        badge: 'IP'
    },
    ppfs: {
        eyebrow: 'ส่งเสริมป้องกัน (PPFS)',
        title: 'เมนู PPFS',
        subtitle: 'สรุปข้อมูลและรายการย่อยของงานส่งเสริมป้องกัน',
        badge: 'PPFS'
    },
    ppfs_sub: {
        eyebrow: 'PPFS รพ.สต.',
        title: 'เมนู PPFS รพ.สต.',
        subtitle: 'แสดงข้อมูลการทำงานฝั่ง PPFS รพ.สต.',
        badge: 'PPFS'
    },
    seamless: {
        eyebrow: 'จ่ายชดเชย Seamless',
        title: 'เมนู Seamless',
        subtitle: 'สลับดูข้อมูลราย sub menu ของงาน Seamless',
        badge: 'Seamless'
    },
    'how-to-use': {
        eyebrow: 'วิธีใช้งาน',
        title: 'คู่มือการใช้งาน',
        subtitle: 'แนะนำการใช้งานระบบและเมนูสำคัญ',
        badge: 'Help'
    },
    about: {
        eyebrow: 'เกี่ยวกับระบบ',
        title: 'เกี่ยวกับระบบ',
        subtitle: 'ข้อมูลเวอร์ชันและรายละเอียดระบบ',
        badge: 'Info'
    },
    'google-sync': {
        eyebrow: 'ตั้งค่าระบบ & ผู้ใช้งาน',
        title: 'ตั้งค่าระบบและผู้ใช้งาน',
        subtitle: 'จัดการบัญชีผู้ใช้ การซิงก์ และการเชื่อมต่อ',
        badge: 'Admin'
    },
    'log-history': {
        eyebrow: 'ประวัติ log',
        title: 'ประวัติการทำงาน',
        subtitle: 'ตรวจสอบเหตุการณ์และการทำงานย้อนหลังของระบบ',
        badge: 'Logs'
    },
    'year-audit': {
        eyebrow: 'ตรวจสอบปีงบประมาณ',
        title: 'ตรวจสอบปีงบประมาณ',
        subtitle: 'ตรวจสอบและซ่อมแซมข้อมูลปีงบประมาณ',
        badge: 'Audit'
    },
    overview: {
        eyebrow: 'ภาพรวม',
        title: 'ภาพรวมยอดเงิน',
        subtitle: 'แสดงข้อมูลภาพรวมทุกกองทุน',
        badge: 'Overview'
    }
};

const FUNDS = {
    smartmoney: 'Smart Money',
    overview: 'ภาพรวม',
    op: 'ผู้ป่วยนอก (OP)',
    ip: 'ผู้ป่วยใน (IP)',
    ppfs: 'ส่งเสริมป้องกัน (PPFS)',
    ppfs_sub: 'PPFS รพ.สต.',
    seamless: 'จ่ายชดเชย Seamless',
    'how-to-use': 'วิธีใช้งาน',
    about: 'เกี่ยวกับระบบ',
    'google-sync': 'ตั้งค่าระบบ & ผู้ใช้งาน',
    'log-history': 'ประวัติ log',
    'year-audit': 'ประวัติการตรวจสอบ',
};

function updateMenuPageHeader(tabName = activeTab) {
    const meta = MENU_PAGE_META[tabName] || MENU_PAGE_META.smartmoney;
    const eyebrowEl = document.getElementById('menu-page-eyebrow');
    const titleEl = document.getElementById('menu-page-title');
    const subtitleEl = document.getElementById('menu-page-subtitle');
    const badgeEl = document.getElementById('menu-page-badge');
    if (eyebrowEl) eyebrowEl.textContent = meta.eyebrow;
    if (titleEl) titleEl.textContent = meta.title;
    if (subtitleEl) subtitleEl.textContent = meta.subtitle;
    if (badgeEl) badgeEl.textContent = meta.badge;
}

function getLocalCachedSharedPrefs() {
    try {
        const raw = localStorage.getItem('nhso_shared_ui_prefs');
        const parsed = raw ? JSON.parse(raw) : {};
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
    } catch (err) {
        return {};
    }
}

function persistSharedPrefsCache() {
    try {
        localStorage.setItem('nhso_shared_ui_prefs', JSON.stringify(sharedUiPreferences || {}));
    } catch (err) {}
}

function saveDatabaseToLocalStorage() {
    try {
        localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database || [])));
    } catch (err) {
        console.warn('[Database] Failed to persist local cache', err);
    }
}

function getDatabaseStoragePayload(records = []) {
    return Array.isArray(records) ? records : [];
}

function extractDatabaseStorageRecords(rawPayload) {
    if (Array.isArray(rawPayload)) return rawPayload;
    if (rawPayload && typeof rawPayload === 'object') {
        if (Array.isArray(rawPayload.records)) return rawPayload.records;
        if (Array.isArray(rawPayload.data)) return rawPayload.data;
    }
    return [];
}

function getSharedUiPreference(key, fallback = '') {
    const fromServer = sharedUiPreferences && Object.prototype.hasOwnProperty.call(sharedUiPreferences, key)
        ? sharedUiPreferences[key]
        : undefined;
    if (fromServer !== undefined && fromServer !== null && fromServer !== '') return fromServer;
    const hasAuthenticatedSession = Boolean(currentSession && currentSession.token && currentSession.token !== 'mock-sandbox-token');
    if (hasAuthenticatedSession) return fallback;
    const cached = getLocalCachedSharedPrefs();
    if (cached && Object.prototype.hasOwnProperty.call(cached, key)) {
        const value = cached[key];
        if (value !== undefined && value !== null && value !== '') return value;
    }
    return fallback;
}

function setSharedUiPreferenceLocal(key, value) {
    if (!key) return;
    sharedUiPreferences = { ...(sharedUiPreferences || {}), [key]: value };
    persistSharedPrefsCache();
}

async function saveSharedUiPreference(key, value) {
    setSharedUiPreferenceLocal(key, value);
    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (!token || token === 'mock-sandbox-token') return;

    try {
        await fetch(`${BACKEND_URL}/user/preferences`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ key, value })
        });
    } catch (err) {
        console.warn('[SharedPrefs] Failed to save preference', key, err);
    }
}

function applySharedUiPreferencesToDom() {
    const activeTabPref = String(getSharedUiPreference(SHARED_PREF_KEYS.activeTab, '') || '').trim();
    if (activeTabPref) {
        activeTab = activeTabPref;
    } else if (currentSession && currentSession.token && currentSession.token !== 'mock-sandbox-token') {
        activeTab = 'smartmoney';
    }

    const yearSelect = document.getElementById('year-select');
    const yearPref = String(getSharedUiPreference(SHARED_PREF_KEYS.yearSelect, '') || '').trim();
    if (yearSelect && yearPref) {
        yearSelect.value = yearPref;
    } else if (yearSelect && currentSession && currentSession.token && currentSession.token !== 'mock-sandbox-token') {
        yearSelect.value = 'all';
    }

    const dashboardFileSelect = document.getElementById('dashboard-file-select');
    const filePref = String(getSharedUiPreference(SHARED_PREF_KEYS.dashboardFile, '') || '').trim();
    if (dashboardFileSelect && filePref) {
        dashboardFileSelect.value = filePref;
    }

    const hospitalInput = document.getElementById('hospital-input');
    const hospitalPref = String(getSharedUiPreference(SHARED_PREF_KEYS.hospitalInput, '') || '').trim();
    if (hospitalInput && hospitalPref) {
        hospitalInput.value = hospitalPref;
    }

    const startMonthSelect = document.getElementById('start-month-select');
    const startMonthPref = String(getSharedUiPreference(SHARED_PREF_KEYS.startMonthSelect, '') || '').trim();
    if (startMonthSelect && startMonthPref) {
        startMonthSelect.value = startMonthPref;
    } else if (startMonthSelect && currentSession && currentSession.token && currentSession.token !== 'mock-sandbox-token') {
        startMonthSelect.value = '1';
    }

    const endMonthSelect = document.getElementById('end-month-select');
    const endMonthPref = String(getSharedUiPreference(SHARED_PREF_KEYS.endMonthSelect, '') || '').trim();
    if (endMonthSelect && endMonthPref) {
        endMonthSelect.value = endMonthPref;
    } else if (endMonthSelect && currentSession && currentSession.token && currentSession.token !== 'mock-sandbox-token') {
        endMonthSelect.value = '12';
    }

    const seamlessPref = String(getSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtab, '') || '').trim();
    const seamlessNamePref = String(getSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtabName, '') || '').trim();
    window.currentSeamlessSubtab = seamlessPref || ((currentSession && currentSession.token && currentSession.token !== 'mock-sandbox-token') ? null : window.currentSeamlessSubtab || null);
    window.currentSeamlessSubtabName = seamlessNamePref || ((currentSession && currentSession.token && currentSession.token !== 'mock-sandbox-token') ? null : window.currentSeamlessSubtabName || null);
    if (seamlessPref) {
        localStorage.setItem('nhso_current_seamless_subtab', seamlessPref);
    }
    if (seamlessNamePref) {
        localStorage.setItem('nhso_current_seamless_subtab_name', seamlessNamePref);
    }

    syncSeamlessSidebarVisibility();
}

function cleanupSeamlessSidebarExtras() {
    const seamlessMenu = document.querySelector('.menu-item.has-submenu[data-tab="seamless"]');
    const accordion = seamlessMenu?.closest('.menu-accordion') || null;
    if (!accordion) return;
    accordion.querySelectorAll('.seamless-submenu-heading, .seamless-submenu-group-label').forEach(node => {
        try { node.remove(); } catch (err) {}
    });
}

function getSeamlessSubmenuCollapsed() {
    try {
        return localStorage.getItem('nhso_seamless_submenu_collapsed') === '1';
    } catch (err) {
        return false;
    }
}

function setSeamlessSubmenuCollapsed(collapsed) {
    try {
        localStorage.setItem('nhso_seamless_submenu_collapsed', collapsed ? '1' : '0');
    } catch (err) {}
}

function syncSeamlessSidebarVisibility() {
    const seamlessMenu = document.querySelector('.menu-item.has-submenu[data-tab="seamless"]');
    const accordion = seamlessMenu?.closest('.menu-accordion') || null;
    const submenuList = accordion ? accordion.querySelector('.submenu-list') : null;
    cleanupSeamlessSidebarExtras();
    const collapsed = getSeamlessSubmenuCollapsed();
    if (seamlessMenu) {
        seamlessMenu.classList.toggle('open', !collapsed);
        seamlessMenu.classList.add('active');
    }
    if (submenuList && submenuList.classList && submenuList.classList.contains('submenu-list')) {
        submenuList.classList.toggle('open', !collapsed);
        submenuList.style.display = collapsed ? 'none' : 'flex';
        submenuList.style.visibility = 'visible';
        submenuList.style.maxHeight = collapsed ? '0' : 'none';
        submenuList.style.overflow = collapsed ? 'hidden' : 'visible';
    }
}

function toggleSeamlessSubmenu(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
        }
    }

    const seamlessMenu = document.querySelector('.menu-item.has-submenu[data-tab="seamless"]');
    const accordion = seamlessMenu?.closest('.menu-accordion') || null;
    const submenuList = accordion ? accordion.querySelector('.submenu-list') : null;
    if (!seamlessMenu || !submenuList) return false;

    const currentlyOpen = submenuList.classList.contains('open');
    const willOpen = !currentlyOpen;

    submenuList.classList.toggle('open', willOpen);
    submenuList.style.display = willOpen ? 'flex' : 'none';
    submenuList.style.visibility = 'visible';
    submenuList.style.maxHeight = willOpen ? 'none' : '0';
    submenuList.style.overflow = willOpen ? 'visible' : 'hidden';
    seamlessMenu.classList.toggle('open', willOpen);
    seamlessMenu.classList.add('active');
    setSeamlessSubmenuCollapsed(!willOpen);

    return false;
}
window.toggleSeamlessSubmenu = toggleSeamlessSubmenu;

async function syncSharedUiPreferencesFromServer() {
    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (!token || token === 'mock-sandbox-token') {
        sharedUiPreferences = getLocalCachedSharedPrefs();
        applySharedUiPreferencesToDom();
        return sharedUiPreferences;
    }

    try {
        sharedUiPreferences = {};
        const res = await fetch(`${BACKEND_URL}/user/preferences`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await res.json();
        if (result.status === 'success' && result.preferences && typeof result.preferences === 'object') {
            sharedUiPreferences = result.preferences;
        }
        persistSharedPrefsCache();
        applySharedUiPreferencesToDom();
    } catch (err) {
        console.warn('[SharedPrefs] Unable to sync preferences', err);
        sharedUiPreferences = {};
        applySharedUiPreferencesToDom();
    }
    return sharedUiPreferences;
}

async function syncDashboardStateFromServer() {
    if (!currentSession || currentSession.token === 'mock-sandbox-token') return;
    if (dashboardSyncInFlight) return;
    dashboardSyncInFlight = true;

    try {
        const sessionOk = await validateCurrentSession();
        if (!sessionOk) return;
        await syncSharedUiPreferencesFromServer();
        await loadDashboardData();
        await syncUploadHistoryFromServer();
        handleTabChange(activeTab);
    } catch (err) {
        console.warn('[DashboardSync] Unable to refresh shared state', err);
    } finally {
        dashboardSyncInFlight = false;
    }
}

function startDashboardSyncPolling() {
    if (dashboardSyncTimer) return;
    dashboardSyncTimer = setInterval(() => {
        syncDashboardStateFromServer().catch(() => {});
    }, 5000);
}

function stopDashboardSyncPolling() {
    if (!dashboardSyncTimer) return;
    clearInterval(dashboardSyncTimer);
    dashboardSyncTimer = null;
}

function clearPersistedSession() {
    currentSession = null;
    try { localStorage.removeItem('nhso_token'); } catch (e) {}
    try { localStorage.removeItem('nhso_user'); } catch (e) {}
    stopDashboardSyncPolling();
}

async function validateCurrentSession() {
    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    const userStr = currentSession ? JSON.stringify(currentSession) : localStorage.getItem('nhso_user');
    if (!token || !userStr) return false;
    if (token === 'mock-sandbox-token') return true;

    try {
        const res = await fetch(`${BACKEND_URL}/auth/me`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await res.json().catch(() => ({}));
        if (!res.ok || result.status !== 'success' || !result.user) {
            throw new Error(result.message || `HTTP ${res.status}`);
        }

        currentSession = { token, ...result.user };
        localStorage.setItem('nhso_user', JSON.stringify(result.user));
        applyRBACLayout(currentSession);
        return true;
    } catch (err) {
        console.warn('[Session] Invalid or expired session detected:', err?.message || err);
        clearPersistedSession();
        document.getElementById('login-overlay').className = 'login-overlay-visible';
        return false;
    }
}

function getCurrentFiscalYearBE() {
    const now = new Date();
    const buddhistYear = now.getFullYear() + 543;
    return now.getMonth() >= 9 ? buddhistYear + 1 : buddhistYear;
}

const FISCAL_YEAR_LOOKBACK = 20;

function isPlausibleFiscalYear(value) {
    const fiscalYearNum = parseInt(value, 10);
    if (!Number.isFinite(fiscalYearNum)) return false;
    const currentFiscalYear = getCurrentFiscalYearBE();
    return fiscalYearNum >= (currentFiscalYear - FISCAL_YEAR_LOOKBACK) && fiscalYearNum <= (currentFiscalYear + 1);
}

function getPlausibleFiscalYears(values = []) {
    return sortFiscalYears([...new Set(values
        .map(value => String(value))
        .filter(value => value && value !== 'undefined' && value !== 'null')
        .filter(isPlausibleFiscalYear))], 'asc');
}

function normalizeFiscalYearRecord(item) {
    const currentFiscalYear = parseInt(item?.fiscalYear, 10);
    if (isPlausibleFiscalYear(currentFiscalYear)) return item;
    const inferred = parseMonthAndYearFromFileName(item?.fileName || '');
    if (isPlausibleFiscalYear(inferred.fileYear)) {
        return { ...item, fiscalYear: inferred.fileYear };
    }
    return item;
}

function normalizeDashboardRecord(item) {
    const normalized = normalizeFiscalYearRecord(item || {});
    const normalizedMenuKey = normalizeUploadMenuKey(normalized.menuKey || normalized.fund || normalized.baseFund || '');
    const normalizedFund = normalizeUploadMenuKey(normalized.fund || '');
    const isSeamlessSubtype = SEAMLESS_SUBTAB_KEYS.has(normalizedMenuKey) || SEAMLESS_SUBTAB_KEYS.has(normalizedFund);
    const baseFund = normalized.baseFund
        ? normalizeUploadMenuKey(normalized.baseFund) || String(normalized.baseFund)
        : (isSeamlessSubtype ? 'seamless' : (normalizedFund || normalizedMenuKey || ''));

    return {
        ...normalized,
        menuKey: normalizedMenuKey || String(normalized.menuKey || ''),
        baseFund
    };
}

function normalizeLoadedDatabase(records = []) {
    return records.map(normalizeDashboardRecord);
}

let managedYears = [];
try {
    managedYears = JSON.parse(localStorage.getItem('nhso_managed_years')) || [];
} catch (error) {
    managedYears = [];
}
managedYears = getPlausibleFiscalYears(managedYears);
try {
    localStorage.setItem('nhso_managed_years', JSON.stringify(managedYears));
} catch (error) {
    console.warn('Unable to persist cleaned managed years:', error);
}

let trendChart = null;
let compareChart = null;
let ipCmiChart = null;
let focusYearlyChartInstance = null;
let focusMonthlyChartInstance = null;
let ppfsGaugeChart = null;
let ppfsCChart = null;
let ppfsYearlyChart = null;
let ppfsProportionChart = null;
let dashboardSyncTimer = null;
let dashboardSyncInFlight = false;

function getCurrentSeamlessSubtab() {
    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (token && token !== 'mock-sandbox-token') {
        return window.currentSeamlessSubtab || getSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtab, null) || null;
    }
    return window.currentSeamlessSubtab || getSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtab, localStorage.getItem('nhso_current_seamless_subtab') || null) || null;
}

function getCurrentSeamlessSubtabName() {
    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (token && token !== 'mock-sandbox-token') {
        return window.currentSeamlessSubtabName || getSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtabName, null) || null;
    }
    return window.currentSeamlessSubtabName || getSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtabName, localStorage.getItem('nhso_current_seamless_subtab_name') || null) || null;
}

function getDashboardFileSelectionKey(tabName = activeTab, seamlessSubtab = getCurrentSeamlessSubtab()) {
    return `nhso_dashboard_selected_file_${String(tabName || 'all')}_${String(seamlessSubtab || 'all')}`;
}

function normalizeDashboardFileValue(value) {
    return String(value || '').trim().toLowerCase();
}

function isMonthWithinSelectedRange(monthValue, startMonth, endMonth) {
    const month = parseInt(monthValue, 10);
    const start = parseInt(startMonth, 10);
    const end = parseInt(endMonth, 10);
    if (!Number.isFinite(month)) return false;
    if (!Number.isFinite(start) || !Number.isFinite(end)) return true;
    if (start <= end) return month >= start && month <= end;
    return month >= start || month <= end;
}

function matchesCurrentTabScope(item, tabName = activeTab, seamlessSubtab = getCurrentSeamlessSubtab()) {
    if (tabName === 'overview') return true;
    if (tabName === 'seamless') {
        const recordMenuKey = getRecordMenuKey(item);
        if (seamlessSubtab) {
            if (recordMenuKey && recordMenuKey !== seamlessSubtab) return false;
            const prop = seamlessSubtab + '_amt';
            if (item.subFundAmount > 0) {
                if (item.mainCategoryName && getCurrentSeamlessSubtabName() && item.mainCategoryName !== getCurrentSeamlessSubtabName()) return false;
            } else if (Number(item[prop] || 0) <= 0 && recordMenuKey !== seamlessSubtab) {
                return false;
            }
            return true;
        }
        return SEAMLESS_SUBTAB_KEYS.has(recordMenuKey);
    }
    if (tabName === 'op') return (item.fund === 'OPBKK' || item.fund === 'op' || item.baseFund === 'OPBKK');
    if (tabName === 'ip') return (item.fund === 'IPBKK' || item.fund === 'ip' || item.baseFund === 'IPBKK');
    if (tabName === 'ppfs') {
        const fundValue = String(item.fund || '').toLowerCase();
        const baseFundValue = String(item.baseFund || '').toLowerCase();
        return (
            ['pp', 'ppfs', 'ppfs_sub', 'opae', 'opfs'].includes(fundValue) ||
            ['pp', 'ppfs', 'ppfs_sub', 'opae', 'opfs'].includes(baseFundValue)
        );
    }

    const currentFund = String(item.baseFund || item.fund || '').trim();
    if (currentFund !== tabName) return false;

    return true;
}

function getDashboardMenuScopedRecords(tabName = activeTab) {
    const seamlessSubtab = tabName === 'seamless' ? getCurrentSeamlessSubtab() : null;
    return database.filter(item => matchesCurrentTabScope(item, tabName, seamlessSubtab));
}

function buildDashboardFileOptions(tabName = activeTab) {
    const records = getDashboardMenuScopedRecords(tabName);
    const options = new Map();

    records.forEach(item => {
        const fileName = String(item.fileName || '').trim();
        if (!fileName) return;
        const key = normalizeDashboardFileValue(fileName);
        const importedAtValue = item.importedAt ? new Date(item.importedAt).getTime() : 0;
        const label = `${fileName}${item.hospital ? ` | ${String(item.hospital).split(' - ')[0]}` : ''}${item.fiscalYear ? ` | ปีงบประมาณ ${item.fiscalYear}` : ''}${item.month ? ` / เดือน ${item.month}` : ''}`;
        const existing = options.get(key);

        if (!existing || importedAtValue > existing.sortValue) {
            options.set(key, {
                value: fileName,
                label,
                sortValue: importedAtValue,
                count: 1
            });
        } else {
            existing.count += 1;
        }
    });

    return [...options.values()].sort((a, b) => b.sortValue - a.sortValue);
}

function getSelectedDashboardFileValue() {
    const select = document.getElementById('dashboard-file-select');
    if (select && select.value) return String(select.value);
    const key = getDashboardFileSelectionKey();
    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (token && token !== 'mock-sandbox-token') {
        return getSharedUiPreference(`${key}`, 'all') || 'all';
    }
    return getSharedUiPreference(`${key}`, localStorage.getItem(key) || 'all') || 'all';
}

function setSelectedDashboardFileValue(value) {
    const key = getDashboardFileSelectionKey();
    const cleanValue = String(value || 'all');
    try {
        localStorage.setItem(key, cleanValue);
    } catch (e) {}
    saveSharedUiPreference(SHARED_PREF_KEYS.dashboardFile, cleanValue);
}

function updateDashboardFileSelect() {
    const select = document.getElementById('dashboard-file-select');
    if (!select) return;

    const options = buildDashboardFileOptions();
    const key = getDashboardFileSelectionKey();
    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    const storedValue = String(
        token && token !== 'mock-sandbox-token'
            ? getSharedUiPreference(SHARED_PREF_KEYS.dashboardFile, 'all')
            : getSharedUiPreference(SHARED_PREF_KEYS.dashboardFile, localStorage.getItem(key) || 'all')
    || 'all');

    select.innerHTML = '<option value="all">ทั้งหมด</option>';
    options.forEach(option => {
        const opt = document.createElement('option');
        opt.value = option.value;
        opt.textContent = option.label;
        select.appendChild(opt);
    });

    const normalizedStored = normalizeDashboardFileValue(storedValue);
    const hasStoredOption = storedValue === 'all' || options.some(option => normalizeDashboardFileValue(option.value) === normalizedStored);
    const nextValue = hasStoredOption ? storedValue : (options[0]?.value || 'all');

    select.value = nextValue;
    setSelectedDashboardFileValue(nextValue);
}

function matchesDashboardFileSelection(item) {
    const selected = normalizeDashboardFileValue(getSelectedDashboardFileValue());
    if (!selected || selected === 'all') return true;
    return normalizeDashboardFileValue(item.fileName || '') === selected;
}

function getRecordMenuKey(item) {
    const inferred = inferUploadHistoryMenuKey(item);
    if (inferred) return inferred;

    const directCandidates = [
        item?.menuKey,
        item?.subtab,
        item?.seamlessSubtab,
        item?.seamlessSubtabName,
        item?.mainCategoryName,
        item?.subFundId
    ];
    for (const candidate of directCandidates) {
        const normalized = normalizeUploadMenuKey(candidate);
        if (normalized && ['overview', 'smartmoney', 'op', 'ip', 'ppfs', 'ppfs_sub', 'seamless', 'ckd', 'ktp', 'napp', 'tb', 'downs', 'thal_screen', 'cervical', 'psych', 'thal_disease', 'anc', 'rehab', 'thai_med', 'morprom', 'ncd'].includes(normalized)) {
            return normalized;
        }
    }
    return '';
}

window.getCurrentSeamlessSubtab = getCurrentSeamlessSubtab;
window.getCurrentSeamlessSubtabName = getCurrentSeamlessSubtabName;

const LOG_HISTORY_KEY = 'nhso_log_history';
const MAX_LOG_HISTORY = 500;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function loadLogHistory() {
    try {
        const raw = localStorage.getItem(LOG_HISTORY_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
        console.warn('[LogHistory] Failed to load history', err);
        return [];
    }
}

function saveLogHistory(history) {
    try {
        const trimmed = Array.isArray(history) ? history.slice(0, MAX_LOG_HISTORY) : [];
        localStorage.setItem(LOG_HISTORY_KEY, JSON.stringify(trimmed));
        return true;
    } catch (err) {
        console.error('[LogHistory] Failed to save history', err);
        return false;
    }
}

function formatLogTimestamp(ts) {
    const date = new Date(ts);
    if (isNaN(date.getTime())) return '-';
    return date.toLocaleString('th-TH', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function recordAppLog(level, action, message, meta = {}) {
    const normalizedLevel = String(level || 'info').toLowerCase();
    const entry = {
        id: `${Date.now()}-${Math.random().toString(36).slice(2, 10)}`,
        ts: new Date().toISOString(),
        level: ['success', 'warn', 'error'].includes(normalizedLevel) ? normalizedLevel : 'info',
        action: String(action || 'general'),
        message: String(message || ''),
        fileName: String(meta.fileName || ''),
        fund: String(meta.fund || ''),
        fiscalYear: String(meta.fiscalYear || ''),
        month: String(meta.month || ''),
        tab: String(meta.tab || activeTab || ''),
        user: String(meta.user || (currentSession?.username || '')),
        details: meta.details || null
    };

    const history = loadLogHistory();
    history.unshift(entry);
    saveLogHistory(history);

    if (document.getElementById('log-history-tbody')) {
        renderLogHistory();
    }

    fetch(`${BACKEND_URL}/dashboard/log`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(entry)
    }).catch(() => {});

    return entry;
}

function getLogHistoryFilterValue() {
    const filter = document.getElementById('log-history-filter');
    return filter ? filter.value : 'all';
}

function getLogHistorySearchValue() {
    const search = document.getElementById('log-history-search');
    return search ? search.value.trim().toLowerCase() : '';
}

function getLogHistoryActionFilterValue() {
    const filter = document.getElementById('log-history-action-filter');
    return filter ? filter.value : 'all';
}

function getLogHistoryStartDateValue() {
    const input = document.getElementById('log-history-start-date');
    return input ? input.value : '';
}

function getLogHistoryEndDateValue() {
    const input = document.getElementById('log-history-end-date');
    return input ? input.value : '';
}

function getFilteredLogHistory() {
    const filterLevel = getLogHistoryFilterValue();
    const filterAction = getLogHistoryActionFilterValue();
    const searchValue = getLogHistorySearchValue();
    const startDate = getLogHistoryStartDateValue();
    const endDate = getLogHistoryEndDateValue();
    const history = loadLogHistory();

    const startTime = startDate ? new Date(`${startDate}T00:00:00`).getTime() : null;
    const endTime = endDate ? new Date(`${endDate}T23:59:59.999`).getTime() : null;

    return history.filter(entry => {
        if (filterLevel !== 'all' && entry.level !== filterLevel) return false;
        if (filterAction !== 'all' && !String(entry.action || '').toLowerCase().includes(filterAction)) return false;

        const entryTime = new Date(entry.ts).getTime();
        if (startTime !== null && !Number.isNaN(entryTime) && entryTime < startTime) return false;
        if (endTime !== null && !Number.isNaN(entryTime) && entryTime > endTime) return false;
        if (!searchValue) return true;

        const haystack = [
            entry.ts,
            entry.level,
            entry.action,
            entry.message,
            entry.fileName,
            entry.fund,
            entry.fiscalYear,
            entry.month,
            entry.tab,
            entry.user
        ].join(' ').toLowerCase();

        return haystack.includes(searchValue);
    });
}

function renderLogHistory() {
    const tbody = document.getElementById('log-history-tbody');
    const countEl = document.getElementById('log-history-count');
    if (!tbody) return;

    const history = loadLogHistory();
    const filtered = getFilteredLogHistory();

    if (countEl) {
        countEl.textContent = `พบ ${history.length} รายการ | แสดง ${filtered.length} รายการ`;
    }

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" style="padding: 16px; text-align: center; color: #64748b;">ยังไม่มีประวัติ log</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = filtered.map(entry => {
        const badgeStyles = {
            success: 'background:#dcfce7;color:#166534;',
            warn: 'background:#fef3c7;color:#92400e;',
            error: 'background:#fee2e2;color:#991b1b;',
            info: 'background:#dbeafe;color:#1d4ed8;'
        };
        const badgeStyle = badgeStyles[entry.level] || badgeStyles.info;
        const metaParts = [];
        if (entry.fileName) metaParts.push(`ไฟล์: ${escapeHtml(entry.fileName)}`);
        if (entry.fund) metaParts.push(`กองทุน: ${escapeHtml(entry.fund)}`);
        if (entry.fiscalYear) metaParts.push(`ปีงบประมาณ: ${escapeHtml(entry.fiscalYear)}`);
        if (entry.month) metaParts.push(`เดือน: ${escapeHtml(entry.month)}`);
        if (entry.user) metaParts.push(`ผู้ใช้งาน: ${escapeHtml(entry.user)}`);
        if (entry.tab) metaParts.push(`แท็บ: ${escapeHtml(entry.tab)}`);

        return `
            <tr>
                <td style="padding: 12px 14px; color: #334155; white-space: nowrap;">${escapeHtml(formatLogTimestamp(entry.ts))}</td>
                <td style="padding: 12px 14px;">
                    <span style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:600; ${badgeStyle}">
                        ${escapeHtml(entry.level.toUpperCase())}
                    </span>
                </td>
                <td style="padding: 12px 14px; color: #0f172a;">
                    <div style="font-weight: 600;">${escapeHtml(entry.action)}</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">${escapeHtml(entry.message)}</div>
                </td>
                <td style="padding: 12px 14px; color: #475569; font-size: 12px; line-height: 1.5;">${metaParts.length ? metaParts.join('<br>') : '-'}</td>
                <td style="padding: 12px 14px; color: #64748b; font-size: 12px;">${entry.details ? escapeHtml(typeof entry.details === 'string' ? entry.details : JSON.stringify(entry.details)) : '-'}</td>
            </tr>
        `;
    }).join('');
}

const YEAR_AUDIT_THRESHOLD = 2531;

function getYearAuditRows() {
    const rows = [];
    const seen = new Set();
    const pushRow = (row) => {
        const key = [
            row.sourceType || '',
            row.fiscalYear || '',
            row.fileName || '',
            row.hospital || '',
            row.fund || '',
            row.month || '',
            row.detail || ''
        ].join('|');
        if (seen.has(key)) return;
        seen.add(key);
        rows.push(row);
    };

    database.forEach(item => {
        const fiscalYearNum = parseInt(item?.fiscalYear, 10);
        if (!Number.isFinite(fiscalYearNum) || fiscalYearNum > YEAR_AUDIT_THRESHOLD) return;
        pushRow({
            sourceType: 'database',
            sourceLabel: 'ประวัติการอัปโหลด/ไฟล์นำเข้า',
            fiscalYear: String(item.fiscalYear || ''),
            fileName: String(item.fileName || ''),
            hospital: String(item.hospital || ''),
            fund: String(item.fund || ''),
            month: String(item.month || ''),
            detail: item.fileName ? 'มีไฟล์แนบในรายการนี้' : 'ไม่มีไฟล์แนบกับรายการนี้'
        });
    });

    managedYears.forEach(yr => {
        const fiscalYearNum = parseInt(yr, 10);
        if (!Number.isFinite(fiscalYearNum) || fiscalYearNum > YEAR_AUDIT_THRESHOLD) return;
        pushRow({
            sourceType: 'managedYears',
            sourceLabel: 'ระบบปีงบประมาณ',
            fiscalYear: String(yr),
            fileName: '-',
            hospital: '-',
            fund: '-',
            month: '-',
            detail: 'nhso_managed_years'
        });
    });

    loadLogHistory().forEach(log => {
        const fiscalYearNum = parseInt(log?.fiscalYear, 10);
        if (!Number.isFinite(fiscalYearNum) || fiscalYearNum > YEAR_AUDIT_THRESHOLD) return;
        if (!['upload-start', 'upload-complete', 'add-fiscal-year', 'delete-fiscal-year'].includes(String(log.action || ''))) return;
        pushRow({
            sourceType: 'log',
            sourceLabel: 'ประวัติ log',
            fiscalYear: String(log.fiscalYear || ''),
            fileName: String(log.fileName || ''),
            hospital: '-',
            fund: String(log.fund || ''),
            month: String(log.month || ''),
            detail: String(log.message || '')
        });
    });

    return rows.sort((a, b) => {
        const ay = parseInt(a.fiscalYear, 10) || 0;
        const by = parseInt(b.fiscalYear, 10) || 0;
        if (by !== ay) return by - ay;
        return String(a.fileName || '').localeCompare(String(b.fileName || ''));
    });
}

function renderYearAudit() {
    const tbody = document.getElementById('year-audit-tbody');
    const countEl = document.getElementById('year-audit-count');
    if (!tbody) return;

    const rows = getYearAuditRows();
    if (countEl) {
        countEl.textContent = `พบ ${rows.length} รายการภายใต้ ${YEAR_AUDIT_THRESHOLD}`;
    }

    if (rows.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="padding: 16px; text-align: center; color: #64748b;">ไม่พบข้อมูลปีงบประมาณที่ปิดแล้ว</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = rows.map(row => `
        <tr>
            <td style="padding: 12px 14px; color: #0f172a; font-weight: 600;">${escapeHtml(row.sourceLabel || row.sourceType || '-')}</td>
            <td style="padding: 12px 14px; color: #b91c1c; font-weight: 700;">${escapeHtml(row.fiscalYear || '-')}</td>
            <td style="padding: 12px 14px; color: #334155; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(row.fileName || '-')}">${escapeHtml(row.fileName || '-')}</td>
            <td style="padding: 12px 14px; color: #334155; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(row.hospital || '-')}">${escapeHtml(row.hospital || '-')}</td>
            <td style="padding: 12px 14px; color: #334155;">${escapeHtml(row.fund || '-')}</td>
            <td style="padding: 12px 14px; color: #334155;">${escapeHtml(row.month || '-')}</td>
            <td style="padding: 12px 14px; color: #475569;">${escapeHtml(row.detail || '-')}</td>
        </tr>
    `).join('');
}

function exportYearAudit(format = 'csv') {
    const rows = getYearAuditRows().map(row => ({
        'แหล่งที่มา': row.sourceLabel || row.sourceType || '',
        'ปีงบประมาณ': row.fiscalYear || '',
        'ชื่อไฟล์': row.fileName || '',
        'โรงพยาบาล': row.hospital || '',
        'กองทุน': row.fund || '',
        'เดือน': row.month || '',
        'รายละเอียด': row.detail || ''
    }));

    if (rows.length === 0) {
        showToast('ไม่พบข้อมูลสำหรับส่งออก', false);
        return;
    }

    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Year Audit');
    XLSX.writeFile(wb, `NHSO_Year_Audit_${new Date().toISOString().slice(0, 10)}.${format === 'xlsx' ? 'xlsx' : 'csv'}`);
    showToast('ส่งออกปีงบประมาณเรียบร้อยแล้ว');
}

function handleLogHistoryExport(format = 'csv') {
    const rows = getFilteredLogHistory().map(entry => ({
        'เวลา': formatLogTimestamp(entry.ts),
        'ระดับ': String(entry.level || '').toUpperCase(),
        'เหตุการณ์': entry.action || '',
        'ข้อความ': entry.message || '',
        'ชื่อไฟล์': entry.fileName || '',
        'กองทุน': entry.fund || '',
        'ปีงบประมาณ': entry.fiscalYear || '',
        'เดือน': entry.month || '',
        'แท็บ': entry.tab || '',
        'ผู้ใช้งาน': entry.user || '',
        'รายละเอียด': entry.details ? (typeof entry.details === 'string' ? entry.details : JSON.stringify(entry.details)) : ''
    }));

    if (rows.length === 0) {
            showToast('ไม่พบข้อมูลสำหรับส่งออก', false);
        return;
    }

    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Log History');
    const ext = format === 'xlsx' ? 'xlsx' : 'csv';
    XLSX.writeFile(wb, `NHSO_Log_History_${new Date().toISOString().slice(0,10)}.${ext}`);
    showToast(`ส่งออกประวัติ log เป็น ${ext.toUpperCase()} สำเร็จ`);
}

function clearLogHistory() {
    if (!confirm('คุณต้องการล้างประวัติ log หรือไม่?')) return;
    localStorage.removeItem(LOG_HISTORY_KEY);
    const token = localStorage.getItem('nhso_token');
    fetch(`${BACKEND_URL}/dashboard/log/clear`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        }
    }).catch(() => {});
    renderLogHistory();
    showToast('ล้างประวัติ log เรียบร้อยแล้ว');
}

async function syncLogHistoryFromServer() {
    const token = localStorage.getItem('nhso_token');
    if (!token || token === 'mock-sandbox-token') return;

    try {
        const res = await fetch(`${BACKEND_URL}/dashboard/log?limit=200`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await res.json();
        if (!result || result.status !== 'success' || !Array.isArray(result.logs)) return;

        const serverHistory = normalizeLogHistory(result.logs.map(log => ({
            id: log.id,
            ts: log.ts || log.createdAt || new Date().toISOString(),
            level: log.level,
            action: log.action,
            message: log.message,
            fileName: log.fileName,
            fund: log.fund,
            fiscalYear: log.fiscalYear,
            month: log.month,
            tab: log.tab,
            user: log.user,
            details: log.details
        })));

        saveLogHistory(serverHistory);
        renderLogHistory();
    } catch (err) {
        console.warn('[LogHistory] Unable to sync from server', err);
    }
}

window.recordAppLog = recordAppLog;
window.renderLogHistory = renderLogHistory;
window.clearLogHistory = clearLogHistory;
window.syncLogHistoryFromServer = syncLogHistoryFromServer;
const UPLOAD_HISTORY_KEY = 'nhso_upload_history';
const MAX_UPLOAD_HISTORY = 500;
const UPLOAD_DELETION_SELECTION = new Set();
let sharedUploadHistoryCache = [];

function getLocalCachedUploadHistory() {
    try {
        const raw = localStorage.getItem(UPLOAD_HISTORY_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
        console.warn('[UploadHistory] Failed to load local cache', err);
        return [];
    }
}

function normalizeLogHistory(history) {
    return (Array.isArray(history) ? history : [])
        .map(item => ({
            id: String(item.id || `${item.ts}|${item.action}|${item.message}`),
            ts: item.ts || new Date().toISOString(),
            level: String(item.level || 'info').toLowerCase(),
            action: String(item.action || 'general'),
            message: String(item.message || ''),
            fileName: String(item.fileName || ''),
            fund: String(item.fund || ''),
            fiscalYear: String(item.fiscalYear || ''),
            month: String(item.month || ''),
            tab: String(item.tab || ''),
            user: String(item.user || ''),
            details: item.details || null
        }))
        .sort((a, b) => String(b.ts).localeCompare(String(a.ts)))
        .slice(0, MAX_LOG_HISTORY);
}

function persistUploadHistoryCache() {
    try {
        localStorage.setItem(UPLOAD_HISTORY_KEY, JSON.stringify(sharedUploadHistoryCache.slice(0, MAX_UPLOAD_HISTORY)));
    } catch (err) {
        console.warn('[UploadHistory] Failed to persist cache', err);
    }
}

function setUploadHistoryCache(history) {
    sharedUploadHistoryCache = Array.isArray(history) ? history.slice(0, MAX_UPLOAD_HISTORY) : [];
    persistUploadHistoryCache();
    return sharedUploadHistoryCache;
}

function loadUploadHistory() {
    if (Array.isArray(sharedUploadHistoryCache) && sharedUploadHistoryCache.length > 0) {
        return sharedUploadHistoryCache;
    }
    sharedUploadHistoryCache = getLocalCachedUploadHistory();
    return sharedUploadHistoryCache;
}

function saveUploadHistory(history) {
    try {
        setUploadHistoryCache(history);
        return true;
    } catch (err) {
        console.error('[UploadHistory] Failed to save history', err);
        return false;
    }
}

function buildUploadHistoryFromDashboardCache() {
    const snapshot = Array.isArray(database) && database.length > 0
        ? database
        : (() => {
            try {
                const raw = localStorage.getItem('nhso_database');
                const parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed?.records) ? parsed.records : (Array.isArray(parsed) ? parsed : []);
            } catch (err) {
                return [];
            }
        })();

    if (!Array.isArray(snapshot) || snapshot.length === 0) return [];

    const candidates = snapshot
        .map(item => ({
            id: String(item.id || '').trim(),
            importedAt: item.importedAt || item.imported_at || new Date().toISOString(),
            fileName: String(item.fileName || '').trim(),
            uploadedBy: String(item.uploadedBy || item.imported_by || 'SYSTEM').trim(),
            hospitalCode: String(item.hospitalCode || item.hospital_code || '').trim(),
            hospitalName: String(item.hospitalName || item.hospital_name || '').trim(),
            fiscalYear: String(item.fiscalYear || item.fiscal_year || '').trim(),
            monthNum: String(item.monthNum || item.month_num || item.month || '').trim(),
            subFundId: String(item.subFundId || item.sub_fund_id || item.fund || '').trim(),
            menuKey: String(item.menuKey || item.menu_key || inferUploadHistoryMenuKey(item) || '').trim(),
            claimAmount: Number(item.claimAmount || item.claim_amount || 0),
            approvedAmount: Number(item.approvedAmount || item.approved_amount || 0),
            pendingAmount: Number(item.pendingAmount || item.pending_amount || 0),
            recordCount: Number(item.recordCount || item.record_count || 1),
            status: String(item.status || 'SUCCESS').trim(),
            fileSizeBytes: Number(item.fileSizeBytes || item.file_size_bytes || 0),
            fileDataStored: Boolean(item.fileDataStored ?? item.file_data_stored ?? false),
            subFundsBreakdown: Array.isArray(item.subFundsBreakdown) ? item.subFundsBreakdown : (Array.isArray(item.sub_funds_breakdown) ? item.sub_funds_breakdown : []),
            errors: Array.isArray(item.errors) ? item.errors : []
        }))
        .filter(item => item.fileName || item.hospitalCode || item.fiscalYear || item.monthNum || item.subFundId);

    if (candidates.length === 0) return [];

    return mergeUploadHistoryRecords([], candidates);
}

function getUploadHistoryRecordKey(item = {}) {
    const id = String(item?.id || '').trim();
    if (id) return `id:${id}`;
    return [
        String(item?.importedAt || '').trim(),
        String(item?.fileName || '').trim().toLowerCase(),
        String(item?.hospitalCode || '').trim(),
        String(item?.fiscalYear || '').trim(),
        String(item?.monthNum || '').trim(),
        String(item?.subFundId || '').trim(),
    ].join('|');
}

function mergeUploadHistoryRecords(existingRecords = [], incomingRecords = []) {
    const merged = Array.isArray(existingRecords) ? existingRecords.map(item => ({ ...item })) : [];
    const indexByKey = new Map();

    merged.forEach((item, idx) => {
        indexByKey.set(getUploadHistoryRecordKey(item), idx);
    });

    (Array.isArray(incomingRecords) ? incomingRecords : []).forEach(item => {
        if (!item || typeof item !== 'object') return;
        const normalized = {
            ...item,
            id: String(item.id || '').trim(),
            importedAt: item.importedAt || new Date().toISOString(),
            fileName: String(item.fileName || '').trim(),
            uploadedBy: String(item.uploadedBy || '').trim(),
            hospitalCode: String(item.hospitalCode || '').trim(),
            hospitalName: String(item.hospitalName || '').trim(),
            fiscalYear: String(item.fiscalYear || '').trim(),
            monthNum: String(item.monthNum || '').trim(),
            subFundId: String(item.subFundId || '').trim(),
            menuKey: String(item.menuKey || '').trim(),
            claimAmount: Number(item.claimAmount || 0),
            approvedAmount: Number(item.approvedAmount || 0),
            pendingAmount: Number(item.pendingAmount || 0),
            recordCount: Number(item.recordCount || 0),
            status: item.status || 'SUCCESS',
            fileSizeBytes: Number(item.fileSizeBytes || 0),
            fileDataStored: Boolean(item.fileDataStored)
        };

        const key = getUploadHistoryRecordKey(normalized);
        const existingIndex = indexByKey.has(key) ? indexByKey.get(key) : -1;
        if (existingIndex >= 0) {
            merged[existingIndex] = {
                ...merged[existingIndex],
                ...normalized,
                fileName: normalized.fileName || merged[existingIndex].fileName || '',
                menuKey: normalized.menuKey || merged[existingIndex].menuKey || '',
                uploadedBy: normalized.uploadedBy || merged[existingIndex].uploadedBy || '',
                hospitalName: normalized.hospitalName || merged[existingIndex].hospitalName || '',
                fileSizeBytes: Number.isFinite(normalized.fileSizeBytes) ? normalized.fileSizeBytes : Number(merged[existingIndex].fileSizeBytes || 0)
            };
        } else {
            merged.unshift(normalized);
            indexByKey.set(key, 0);
        }
    });

    return merged
        .filter(Boolean)
        .sort((a, b) => String(b.importedAt || '').localeCompare(String(a.importedAt || '')))
        .slice(0, MAX_UPLOAD_HISTORY);
}

function formatUploadHistorySize(bytes) {
    const value = Number(bytes || 0);
    if (!Number.isFinite(value) || value <= 0) return '-';
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}

function normalizeUploadHistoryText(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function matchesUploadHistorySearch(item, query) {
    if (!query) return true;
    const haystack = normalizeUploadHistoryText([
        item.fileName,
        item.hospitalCode,
        item.hospitalName,
        item.fiscalYear,
        item.monthNum,
        item.subFundId,
        item.status,
        item.importedAt
    ].join(' '));
    return haystack.includes(query);
}

function normalizeUploadMenuKey(value) {
    const key = normalizeUploadHistoryText(value);
    if (!key) return '';
    if (['opbkk', 'op'].includes(key)) return 'op';
    if (['ipbkk', 'ip'].includes(key)) return 'ip';
    if (['ppfs', 'pp', 'opae', 'opfs'].includes(key)) return 'ppfs';
    if (['ppfs_sub', 'ppfs sub', 'ppfs-sub'].includes(key)) return 'ppfs_sub';
    if (['smartmoney', 'smart money'].includes(key)) return 'smartmoney';
    if (['overview', 'all', 'ภาพรวม'].includes(key)) return 'overview';
    if (['ckd', 'dckd'].includes(key)) return 'ckd';
    if (['ktp', 'dktp'].includes(key)) return 'ktp';
    if (['napp', 'dnap'].includes(key)) return 'napp';
    if (['tb', 'dmtb'].includes(key)) return 'tb';
    if (['downs', 'ddow', 'คัดกรองดาวน์'].includes(key)) return 'downs';
    if (['thal_screen', 'dtlm', 'คัดกรองธาลัสซีเมีย'].includes(key)) return 'thal_screen';
    if (['cervical', 'dccs', 'คัดกรองมะเร็งปากมดลูก'].includes(key)) return 'cervical';
    if (['psych', 'dcmh', 'จิตเวช'].includes(key)) return 'psych';
    if (['thal_disease', 'dtdt', 'ธาลัสซีเมีย'].includes(key)) return 'thal_disease';
    if (['anc', 'danc', 'ฝากครรภ์'].includes(key)) return 'anc';
    if (['rehab', 'ddsa', 'ฟื้นฟู'].includes(key)) return 'rehab';
    if (['thai_med', 'dtm', 'แพทย์แผนไทย'].includes(key)) return 'thai_med';
    if (['morprom', 'dmor', 'หมอพร้อม'].includes(key)) return 'morprom';
    if (['ncd', 'เบาหวาน/ความดัน'].includes(key)) return 'ncd';
    if (key === 'seamless') return 'seamless';
    return key;
}

function formatUploadMenuLabel(menuKey) {
    const normalized = normalizeUploadMenuKey(menuKey);
    const labelMap = {
        overview: 'ภาพรวม',
        smartmoney: 'Smart Money',
        op: 'ผู้ป่วยนอก (OP)',
        ip: 'ผู้ป่วยใน (IP)',
        ppfs: 'PPFS',
        ppfs_sub: 'PPFS รพ.สต.',
        seamless: 'Seamless',
        ckd: 'CKD',
        ktp: 'KTP',
        napp: 'NAPP',
        tb: 'TB',
        downs: 'คัดกรองดาวน์',
        thal_screen: 'คัดกรองธาลัสซีเมีย',
        cervical: 'คัดกรองมะเร็งปากมดลูก',
        psych: 'จิตเวช',
        thal_disease: 'ธาลัสซีเมีย',
        anc: 'ฝากครรภ์',
        rehab: 'ฟื้นฟู',
        thai_med: 'แพทย์แผนไทย',
        morprom: 'หมอพร้อม',
        ncd: 'NCD'
    };
    return labelMap[normalized] || (normalized ? String(normalized).toUpperCase() : '-');
}

function deriveUploadMenuKey(record, fallbackFileName = '') {
    const fileName = String(fallbackFileName || record?.fileName || '').trim();
    const candidates = [
        record?.menuKey,
        record?.subtab,
        record?.seamlessSubtab,
        record?.seamlessSubtabName,
        record?.mainCategoryName,
        record?.fund,
        record?.baseFund,
        fileName
    ];

    for (const candidate of candidates) {
        const normalized = normalizeUploadMenuKey(candidate);
        if (!normalized) continue;
        if (['11120', '05831', '42313'].includes(normalized)) continue;
        if (normalized === 'seamless') continue;
        if (normalized !== 'seamless') {
            return normalized;
        }
    }

    const normalizedFileName = normalizeUploadHistoryText(fileName);
    if (normalizedFileName.includes('dckd') || normalizedFileName.includes('ckd')) return 'ckd';
    if (normalizedFileName.includes('dktp') || normalizedFileName.includes('ktp')) return 'ktp';
    if (normalizedFileName.includes('dnap') || normalizedFileName.includes('napp')) return 'napp';
    if (normalizedFileName.includes('dmtb') || normalizedFileName.includes('tb')) return 'tb';
    if (normalizedFileName.includes('ddow') || normalizedFileName.includes('down')) return 'downs';
    if (normalizedFileName.includes('dtlm') || normalizedFileName.includes('thal')) return 'thal_screen';
    if (normalizedFileName.includes('dccs') || normalizedFileName.includes('cervical')) return 'cervical';
    if (normalizedFileName.includes('dcmh') || normalizedFileName.includes('psych')) return 'psych';
    if (normalizedFileName.includes('dtdt') || normalizedFileName.includes('thal_disease')) return 'thal_disease';
    if (normalizedFileName.includes('danc') || normalizedFileName.includes('anc')) return 'anc';
    if (normalizedFileName.includes('ddsa') || normalizedFileName.includes('rehab')) return 'rehab';
    if (normalizedFileName.includes('dmor') || normalizedFileName.includes('morprom')) return 'morprom';
    if (normalizedFileName.includes('dttm') || normalizedFileName.includes('thai')) return 'thai_med';
    if (normalizedFileName.includes('ncd')) return 'ncd';
    const fallbackMenu = normalizeUploadMenuKey(record?.fund || record?.baseFund || '');
    return fallbackMenu || 'seamless';
}

function getCurrentUploadDeletionMenuKey() {
    if (activeTab === 'seamless') {
        const seamlessSubtab = getCurrentSeamlessSubtab();
        if (seamlessSubtab) return `seamless:${seamlessSubtab}`;
        return 'seamless';
    }
    return normalizeUploadMenuKey(activeTab);
}

function inferUploadHistoryMenuKey(item) {
    const recognizedMenuKeys = new Set([
        'overview',
        'smartmoney',
        'op',
        'ip',
        'ppfs',
        'ppfs_sub',
        'seamless',
        'ckd',
        'ktp',
        'napp',
        'tb',
        'downs',
        'thal_screen',
        'cervical',
        'psych',
        'thal_disease',
        'anc',
        'rehab',
        'thai_med',
        'morprom',
        'ncd'
    ]);

    const directMenuKey = String(item?.menuKey || '').trim();
    if (directMenuKey) {
        if (directMenuKey.startsWith('seamless:')) {
            return `seamless:${normalizeUploadMenuKey(directMenuKey.replace(/^seamless:/, ''))}`;
        }
        const normalizedDirect = normalizeUploadMenuKey(directMenuKey);
        if (normalizedDirect && normalizedDirect !== 'seamless' && recognizedMenuKeys.has(normalizedDirect)) return normalizedDirect;
    }

    const explicitCandidates = [
        item?.tab,
        item?.subtab,
        item?.seamlessSubtab,
        item?.seamlessSubtabName,
        item?.mainCategoryName,
        item?.subFundId,
        item?.baseFund,
        item?.fund
    ];

    for (const candidate of explicitCandidates) {
        const normalized = normalizeUploadMenuKey(candidate);
        if (!normalized) continue;
        if (normalized !== 'seamless' && recognizedMenuKeys.has(normalized)) return normalized;
    }

    const fileName = normalizeUploadHistoryText(item?.fileName || '');
    if (!fileName) return '';
    if (fileName.includes('dckd') || fileName.includes('ckd')) return 'ckd';
    if (fileName.includes('dktp') || fileName.includes('ktp')) return 'ktp';
    if (fileName.includes('dnap') || fileName.includes('napp')) return 'napp';
    if (fileName.includes('dmtb') || fileName.includes('tb')) return 'tb';
    if (fileName.includes('ddow') || fileName.includes('down')) return 'downs';
    if (fileName.includes('dtlm') || fileName.includes('thal')) return 'thal_screen';
    if (fileName.includes('dccs') || fileName.includes('cervical')) return 'cervical';
    if (fileName.includes('dcmh') || fileName.includes('psych')) return 'psych';
    if (fileName.includes('dtdt') || fileName.includes('thal_disease')) return 'thal_disease';
    if (fileName.includes('danc') || fileName.includes('anc')) return 'anc';
    if (fileName.includes('ddsa') || fileName.includes('rehab')) return 'rehab';
    if (fileName.includes('dmor') || fileName.includes('morprom')) return 'morprom';
    if (fileName.includes('dtm') || fileName.includes('thai')) return 'thai_med';
    if (fileName.includes('ncd')) return 'ncd';

    const amountCategoryMap = [
        ['ckd_amt', 'ckd'],
        ['ktp_amt', 'ktp'],
        ['napp_amt', 'napp'],
        ['tb_amt', 'tb'],
        ['downs_amt', 'downs'],
        ['thal_screen_amt', 'thal_screen'],
        ['cervical_amt', 'cervical'],
        ['psych_amt', 'psych'],
        ['thal_disease_amt', 'thal_disease'],
        ['rehab_amt', 'rehab'],
        ['thai_med_amt', 'thai_med'],
        ['morprom_amt', 'morprom'],
        ['anc_amt', 'anc'],
        ['ncd_amt', 'ncd']
    ];
    for (const [field, category] of amountCategoryMap) {
        if (Number(item?.[field] || 0) > 0) return category;
    }

    if (directMenuKey) {
        const normalizedDirect = normalizeUploadMenuKey(directMenuKey);
        if (normalizedDirect && recognizedMenuKeys.has(normalizedDirect)) return normalizedDirect;
    }
    return '';
}

function matchesUploadDeletionMenu(item, menuKey = getCurrentUploadDeletionMenuKey()) {
    const currentKey = normalizeUploadMenuKey(menuKey);
    if (!currentKey) return true;

    const itemKey = inferUploadHistoryMenuKey(item);
    if (!itemKey) return false;

    if (currentKey === 'overview') return true;

    if (currentKey.startsWith('seamless:')) {
        const subtabKey = currentKey.replace(/^seamless:/, '');
        return itemKey === subtabKey;
    }

    if (currentKey === 'seamless') {
        return itemKey === 'seamless' || ['ckd', 'ktp', 'napp', 'tb', 'downs', 'thal_screen', 'cervical', 'psych', 'thal_disease', 'anc', 'rehab', 'thai_med', 'morprom', 'ncd'].includes(itemKey);
    }

    if (['op', 'ip', 'ppfs', 'smartmoney'].includes(currentKey)) {
        return itemKey === currentKey;
    }

    return itemKey === currentKey;
}

function renderUploadHistory() {
    const tbody = document.getElementById('upload-history-tbody');
    const countEl = document.getElementById('upload-history-count');
    if (!tbody) return;

    const history = loadUploadHistory();
    if (countEl) countEl.textContent = `พบ ${history.length} รายการ`;

    if (history.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="padding: 16px; text-align: center; color: #64748b;">ยังไม่มีประวัติการอัปโหลด</td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = history.map(item => `
        <tr>
            <td style="padding: 12px 14px; white-space: nowrap;">${escapeHtml(item.importedAt ? new Date(item.importedAt).toLocaleString('th-TH') : '-')}</td>
            <td style="padding: 12px 14px;">${escapeHtml(item.fileName || '-')}</td>
            <td style="padding: 12px 14px;">${escapeHtml(item.hospitalCode || '-')} ${item.hospitalName ? `- ${escapeHtml(item.hospitalName)}` : ''}</td>
            <td style="padding: 12px 14px;">${escapeHtml(item.fiscalYear || '-')}</td>
            <td style="padding: 12px 14px;">${escapeHtml(item.monthNum || '-')}</td>
            <td style="padding: 12px 14px;">${escapeHtml(item.subFundId || '-')}</td>
            <td style="padding: 12px 14px;">${escapeHtml(formatUploadMenuLabel(item.menuKey || inferUploadHistoryMenuKey(item) || item.subFundId || ''))}</td>
            <td style="padding: 12px 14px;">${escapeHtml(item.status || '-')}</td>
            <td style="padding: 12px 14px; text-align: right;">${escapeHtml(formatUploadHistorySize(item.fileSizeBytes))}</td>
            <td style="padding: 12px 14px; text-align: center; white-space: nowrap;">
                <button type="button" class="btn btn-danger" style="padding: 6px 12px;" onclick='deleteUploadHistoryEntry(${JSON.stringify(String(item.id || ""))}, ${JSON.stringify(String(item.fileName || ""))})'>เธฅเธ</button>
            </td>
        </tr>
    `).join('');

    renderUploadHistorySelect();
}

function getUploadDeletionCandidates() {
    const history = loadUploadHistory();
    const candidates = [];
    const seenKeys = new Set();
    const historyFileNames = new Set();
    const menuKey = getCurrentUploadDeletionMenuKey();

    history.forEach(item => {
        if (!matchesUploadDeletionMenu(item, menuKey)) return;
        const id = String(item.id || '').trim();
        const fileName = String(item.fileName || '').trim();
        const key = id ? `history:${id}` : `history-file:${fileName}`;
        if (!fileName || seenKeys.has(key)) return;
        seenKeys.add(key);
        historyFileNames.add(fileName.toLowerCase());
        candidates.push({
            type: 'history',
            key,
            value: `history:${id || encodeURIComponent(fileName)}`,
            label: `${item.importedAt ? new Date(item.importedAt).toLocaleString('th-TH') : '-'} | ${fileName} | ${item.hospitalCode || '-'}${item.hospitalName ? ` ${item.hospitalName}` : ''} | ${item.fiscalYear || '-'} / ${item.monthNum || '-'}`,
            sortKey: item.importedAt || '',
            fileName
        });
    });

    const fileGroups = new Map();
    (Array.isArray(database) ? database : [])
        .filter(item => matchesUploadDeletionMenu(item, menuKey))
        .forEach(item => {
        const rowId = String(item.id || '').trim();
        const fileName = String(item.fileName || '').trim();
        const displayName = fileName || `ไฟล์ ${String(item.hospitalCode || item.hospital || '-').trim()} ${String(item.fiscalYear || '-').trim()}/${String(item.monthNum || item.month || '-').trim()}`;

        if (fileName) {
            const key = fileName.toLowerCase();
            if (historyFileNames.has(key)) return;
            if (seenKeys.has(`file:${key}`)) return;

            if (!fileGroups.has(key)) {
                fileGroups.set(key, {
                    fileName,
                    count: 0,
                    hospitals: new Set(),
                    years: new Set(),
                    funds: new Set(),
                    value: `file:${encodeURIComponent(fileName)}`
                });
            }

            const group = fileGroups.get(key);
            group.count += 1;
            if (item.hospital) group.hospitals.add(String(item.hospital));
            if (item.fiscalYear) group.years.add(String(item.fiscalYear));
            if (item.fund) group.funds.add(String(item.fund));
            return;
        }

        const fallbackKey = rowId || `${item.importedAt || ''}|${displayName}`;
        if (!fallbackKey) return;
        if (seenKeys.has(`history:${fallbackKey}`)) return;
        fileGroups.set(`__row__${fallbackKey}`, {
            fileName: displayName,
            count: 1,
            hospitals: new Set(item.hospital ? [String(item.hospital)] : []),
            years: new Set(item.fiscalYear ? [String(item.fiscalYear)] : []),
            funds: new Set(item.fund ? [String(item.fund)] : []),
            value: `history:${rowId || encodeURIComponent(displayName)}`,
            labelPrefix: 'รายการ'
        });
    });

    for (const group of fileGroups.values()) {
        const key = String(group.fileName || '').toLowerCase();
        const years = [...group.years].sort((a, b) => parseInt(b, 10) - parseInt(a, 10));
        const funds = [...group.funds].sort();
        const hospitalPreview = [...group.hospitals].slice(0, 2).join(' , ');
        const hasRealFileName = Boolean(key);
        const labelPrefix = group.labelPrefix || 'รายการ';
        candidates.push({
            type: hasRealFileName ? 'file' : 'history',
            key: hasRealFileName ? `file:${key}` : group.value,
            value: group.value,
            label: `${labelPrefix}: ${group.fileName} (${group.count} รายการ${years.length ? `, ปี ${years.join(', ')}` : ''}${funds.length ? `, กองทุน ${funds.join(', ')}` : ''}${hospitalPreview ? `, ${hospitalPreview}` : ''})`,
            sortKey: group.fileName,
            fileName: group.fileName
        });
    }

    return candidates.sort((a, b) => String(b.sortKey || b.label).localeCompare(String(a.sortKey || a.label)));
}

function renderUploadHistorySelect() {
    const list = document.getElementById('upload-history-delete-list');
    const summary = document.getElementById('upload-history-selection-summary');
    const searchInput = document.getElementById('upload-history-search');
    if (!list) return;

    const searchQuery = normalizeUploadHistoryText(searchInput?.value || '');
    const allCandidates = getUploadDeletionCandidates();
    const candidates = allCandidates.filter(item => matchesUploadHistorySearch(item, searchQuery));
    const validValues = new Set(candidates.map(item => item.value));
    Array.from(UPLOAD_DELETION_SELECTION).forEach(value => {
        if (!validValues.has(value)) {
            UPLOAD_DELETION_SELECTION.delete(value);
        }
    });

    if (candidates.length === 0) {
        list.innerHTML = searchQuery
            ? '<div style="color:#64748b; font-size:13px; grid-column:1 / -1;">ไม่พบรายการที่ตรงกับเงื่อนไข</div>'
            : '<div style="color:#64748b; font-size:13px; grid-column:1 / -1;">กำลังแสดงข้อมูลในระบบ</div>';
        if (!searchQuery) UPLOAD_DELETION_SELECTION.clear();
        if (summary) {
            summary.textContent = searchQuery
                ? `พบ 0 จาก ${allCandidates.length} รายการ`
                : 'พบ 0 รายการ';
        }
        return;
    }

    const currentSelection = new Set(UPLOAD_DELETION_SELECTION);
    list.innerHTML = candidates.map(item => {
        const checked = currentSelection.has(item.value) ? 'checked' : '';
        return `
            <label style="display:flex; align-items:flex-start; gap:12px; padding:12px 14px; border:1px solid #e2e8f0; border-radius:10px; cursor:pointer; background:#fff; width:100%; min-height: 76px;">
                <input type="checkbox" data-upload-delete-value="${escapeHtml(String(item.value || ''))}" ${checked} style="margin-top:4px; width:18px; height:18px; flex-shrink:0;">
                <span style="font-size:14px; color:#0f172a; line-height:1.55; white-space:normal; word-break:break-word; flex:1;">${escapeHtml(item.label || '')}</span>
            </label>
        `;
    }).join('');

    if (summary) {
        const selectedVisibleCount = candidates.reduce((count, item) => count + (UPLOAD_DELETION_SELECTION.has(item.value) ? 1 : 0), 0);
        summary.textContent = searchQuery
            ? `พบ ${candidates.length} จาก ${allCandidates.length} รายการ | แสดง ${selectedVisibleCount} รายการ`
            : `พบ ${candidates.length} รายการ | แสดง ${selectedVisibleCount} รายการ`;
    }
}

async function deleteUploadHistoryEntry(uploadHistoryId, fileNameOrSkipConfirm = false, maybeSkipConfirm = false) {
    const historyId = String(uploadHistoryId || '').trim();
    if (!historyId) return false;
    const localHistoryRecord = loadUploadHistory().find(item => {
        const itemId = String(item.id || '').trim();
        const itemFileName = String(item.fileName || '').trim();
        return itemId === historyId || itemFileName === historyId;
    });
    const explicitFileName = typeof fileNameOrSkipConfirm === 'string'
        ? String(fileNameOrSkipConfirm || '').trim()
        : String(localHistoryRecord?.fileName || '').trim();
    const skipConfirm = typeof fileNameOrSkipConfirm === 'boolean' ? fileNameOrSkipConfirm : Boolean(maybeSkipConfirm);

    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (!token) {
        showToast('บันทึกข้อมูลเรียบร้อยแล้ว', false);
        return false;
    }

    if (!skipConfirm && !confirm('คุณต้องการลบรายการที่เลือกหรือไม่?')) return false;

    try {
        const fallbackQuery = explicitFileName ? `?fileName=${encodeURIComponent(explicitFileName)}` : '';
        const res = await fetch(`${BACKEND_URL}/dashboard/upload-history/${encodeURIComponent(historyId)}${fallbackQuery}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`
            }
        });
        const result = await res.json();
        if (!res.ok || result.status !== 'success') {
            if (explicitFileName) {
                const fallbackOk = await deleteUploadFileByName(explicitFileName, true);
                if (fallbackOk) {
                    await syncUploadHistoryFromServer();
                    await loadDashboardData();
                    refreshDashboard();
                    showToast(`ลบรายการสำเร็จ: ${explicitFileName}`);
                    return true;
                }
            }
            throw new Error(result.message || `HTTP ${res.status}`);
        }

        const message = `ลบรายการสำเร็จ: history ${result.removedHistory || 0} รายการ, summary ${result.removedSummaries || 0} รายการ`;
        showToast(message);
        await syncUploadHistoryFromServer();
        await loadDashboardData();
        refreshDashboard();
        return true;
    } catch (err) {
        showToast(`ลบรายการไม่สำเร็จ: ${err.message}`, false);
        return false;
    }
}

async function deleteSelectedUploadHistoryEntry() {
    const selectedValues = Array.from(UPLOAD_DELETION_SELECTION);

    if (selectedValues.length === 0) {
        showToast('เลือกอย่างน้อยหนึ่งรายการก่อนดำเนินการ', false);
        return;
    }

    const label = selectedValues.length === 1 ? '1 รายการ' : `${selectedValues.length} รายการ`;
    if (!confirm(`คุณต้องการลบ ${label} ใช่หรือไม่?`)) return;

    let successCount = 0;
    let failCount = 0;
    for (const selectedId of selectedValues) {
        const ok = await deleteUploadSelectionByValue(selectedId, true);
        if (ok) successCount += 1;
        else failCount += 1;
    }

    renderUploadHistorySelect();
    if (successCount > 0) {
        showToast(`ลบรายการสำเร็จ ${successCount} รายการ${failCount > 0 ? `, ล้มเหลว ${failCount} รายการ` : ''}`);
    } else if (failCount > 0) {
        showToast(`ลบรายการล้มเหลว ${failCount} รายการ`, false);
    }
}

function resolveUploadDeletionTarget(selectedId) {
    const value = String(selectedId || '').trim();
    if (!value) return null;

    const history = loadUploadHistory();
    if (value.startsWith('history:')) {
        const raw = value.replace(/^history:/, '');
        const decoded = decodeURIComponent(raw);
        const match = history.find(item => {
            const itemId = String(item.id || '').trim();
            const itemFileName = String(item.fileName || '').trim();
            return itemId === raw || itemId === decoded || itemFileName === raw || itemFileName === decoded;
        });

        return {
            kind: 'history',
            historyId: match ? String(match.id || '').trim() : decoded || raw,
            fileName: match ? String(match.fileName || '').trim() : decoded || raw
        };
    }

    if (value.startsWith('file:')) {
        return {
            kind: 'file',
            fileName: decodeURIComponent(value.replace(/^file:/, ''))
        };
    }

    return null;
}

async function deleteUploadSelectionByValue(selectedId, skipConfirm = false) {
    const target = resolveUploadDeletionTarget(selectedId);
    if (!target) return false;

    if (target.kind === 'history') {
        if (!skipConfirm && !confirm('คุณต้องการลบไฟล์ที่เลือกหรือไม่?')) return false;
        const ok = await deleteUploadHistoryEntry(target.historyId || target.fileName, target.fileName, true);
        if (ok) UPLOAD_DELETION_SELECTION.delete(String(selectedId || '').trim());
        return ok;
    }

    if (target.kind === 'file') {
        if (!skipConfirm && !confirm('คุณต้องการลบชุดข้อมูลนี้หรือไม่?')) return false;
        const ok = await deleteUploadFileByName(target.fileName, true);
        if (ok) UPLOAD_DELETION_SELECTION.delete(String(selectedId || '').trim());
        return ok;
    }

    return false;
}

function syncUploadDeletionSelectionFromDom() {
    const list = document.getElementById('upload-history-delete-list');
    if (!list) return;

    const checkedValues = Array.from(list.querySelectorAll('input[data-upload-delete-value]:checked'))
        .map(input => input.getAttribute('data-upload-delete-value'))
        .filter(Boolean);

    UPLOAD_DELETION_SELECTION.clear();
    checkedValues.forEach(value => UPLOAD_DELETION_SELECTION.add(value));
}

function handleUploadDeletionListChange(e) {
    const input = e.target;
    if (!input || input.type !== 'checkbox') return;
    const value = String(input.getAttribute('data-upload-delete-value') || '').trim();
    if (!value) return;

    if (input.checked) {
        UPLOAD_DELETION_SELECTION.add(value);
    } else {
        UPLOAD_DELETION_SELECTION.delete(value);
    }
}

function selectAllUploadDeletionItems() {
    const list = document.getElementById('upload-history-delete-list');
    if (!list) return;

    list.querySelectorAll('input[data-upload-delete-value]').forEach(input => {
        input.checked = true;
        const value = String(input.getAttribute('data-upload-delete-value') || '').trim();
        if (value) UPLOAD_DELETION_SELECTION.add(value);
    });
}

function clearUploadDeletionItemsSelection() {
    UPLOAD_DELETION_SELECTION.clear();
    const list = document.getElementById('upload-history-delete-list');
    if (!list) return;
    list.querySelectorAll('input[data-upload-delete-value]').forEach(input => {
        input.checked = false;
    });
}

function removeRecordsFromDatabaseByFileName(fileName) {
    const target = String(fileName || '').trim().toLowerCase();
    if (!target) return 0;

    const before = database.length;
    database = database.filter(item => String(item.fileName || '').trim().toLowerCase() !== target);
    saveDatabaseToLocalStorage();
    return before - database.length;
}

async function deleteUploadFileByName(fileName, skipConfirm = false) {
    const cleanFileName = String(fileName || '').trim();
    if (!cleanFileName) return false;

    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (!skipConfirm && !confirm(`คุณต้องการลบไฟล์ "${cleanFileName}" ทั้งชุดหรือไม่?`)) return false;

    let backendSuccess = false;
    if (token && token !== 'mock-sandbox-token') {
        try {
            const res = await fetch(`${BACKEND_URL}/dashboard/upload-history/file/${encodeURIComponent(cleanFileName)}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${token}` }
            });
            const result = await res.json().catch(() => ({}));
            if (res.ok && result.status === 'success') {
                backendSuccess = true;
            }
        } catch (err) {
            console.warn('[UploadHistory] backend delete-by-file failed', err);
        }
    }

    const removedDatabase = removeRecordsFromDatabaseByFileName(cleanFileName);
    const localHistory = loadUploadHistory().filter(item => String(item.fileName || '').trim().toLowerCase() !== cleanFileName.toLowerCase());
    saveUploadHistory(localHistory);
    renderUploadHistory();
    refreshDashboard();
    showToast(backendSuccess || removedDatabase > 0 ? `ลบไฟล์ "${cleanFileName}" เรียบร้อยแล้ว` : `ไม่พบไฟล์ "${cleanFileName}" สำหรับลบ`, backendSuccess || removedDatabase > 0);
    return backendSuccess || removedDatabase > 0;
}

async function syncUploadHistoryFromServer() {
    const token = localStorage.getItem('nhso_token');
    if (!token || token === 'mock-sandbox-token') return;

    try {
        const res = await fetch(`${BACKEND_URL}/dashboard/upload-history?limit=200`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const result = await res.json();
        if (!result || result.status !== 'success' || !Array.isArray(result.history)) return;

        const normalizedServerHistory = result.history.map(item => ({
            id: String(item.id || '').trim(),
            importedAt: item.importedAt || new Date().toISOString(),
            fileName: item.fileName || '',
            uploadedBy: item.uploadedBy || '',
            hospitalCode: item.hospitalCode || '',
            hospitalName: item.hospitalName || '',
            fiscalYear: item.fiscalYear || '',
            monthNum: item.monthNum || '',
            subFundId: item.subFundId || '',
            menuKey: item.menuKey || '',
            claimAmount: Number(item.claimAmount || 0),
            approvedAmount: Number(item.approvedAmount || 0),
            pendingAmount: Number(item.pendingAmount || 0),
            recordCount: Number(item.recordCount || 0),
            status: item.status || 'SUCCESS',
            fileSizeBytes: Number(item.fileSizeBytes || 0),
            fileDataStored: Boolean(item.fileDataStored)
        }));
        saveUploadHistory(normalizedServerHistory);
        renderUploadHistory();
    } catch (err) {
        console.warn('[UploadHistory] Unable to sync from server', err);
    }
}

async function cleanupOldDataFromUi() {
    const retentionInput = document.getElementById('cleanup-retention-days');
    const resultEl = document.getElementById('cleanup-old-data-result');
    const scopeSelect = document.getElementById('cleanup-scope');
    const retentionDays = Math.max(1, parseInt(retentionInput?.value || '180', 10) || 180);
    const scope = scopeSelect?.value || 'all';
    const scopeLabel = {
        all: 'ทั้งหมด',
        uploads: 'รายการอัปโหลด',
        logs: 'ประวัติ log'
    }[scope] || scope;

    if (!confirm(`คุณต้องการล้างข้อมูลเก่ากว่า ${retentionDays} วัน ใน "${scopeLabel}" ใช่หรือไม่?`)) return;

    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (!token) {
        showToast('ไม่พบ session ผู้ใช้งาน', false);
        return;
    }

    try {
        const res = await fetch(`${BACKEND_URL}/dashboard/cleanup-old-data`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ retentionDays, scope })
        });
        const result = await res.json();
        if (!res.ok || result.status !== 'success') {
            throw new Error(result.message || `HTTP ${res.status}`);
        }
        const message = `ล้างข้อมูลสำเร็จ: files ${result.prunedUploads || 0} รายการ, logs ${result.removedLogs || 0} รายการ`;
        if (resultEl) resultEl.textContent = message;
        showToast(message);
        await syncUploadHistoryFromServer();
        await syncLogHistoryFromServer();
    } catch (err) {
        const message = `ล้างข้อมูลไม่สำเร็จ: ${err.message}`;
        if (resultEl) resultEl.textContent = message;
        showToast(message, false);
    }
}

async function purgeDashboardDataBeforeImport() {
    const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
    if (!token || token === 'mock-sandbox-token') {
        database = [];
        saveDatabaseToLocalStorage();
        return { purged: false, sandboxOnly: true };
    }

    const res = await fetch(`${BACKEND_URL}/dashboard/purge`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });

    const result = await res.json().catch(() => ({}));
    if (!res.ok || result.status !== 'success') {
        throw new Error(result.message || 'ซิงก์ไม่สำเร็จ');
    }

    database = [];
    saveDatabaseToLocalStorage();
    return { purged: true, sandboxOnly: false };
}

function getDashboardPurgeScopeFromUi() {
    return {
        hospitalFilter: document.getElementById('hospital-input')?.value.trim() || '',
        yearFilter: document.getElementById('year-select')?.value || 'all',
        startMonth: Number.parseInt(document.getElementById('start-month-select')?.value, 10) || 1,
        endMonth: Number.parseInt(document.getElementById('end-month-select')?.value, 10) || 12,
        fundId: activeTab === 'overview' ? '' : activeTab
    };
}

function getDatabaseRecordKey(item) {
    return [
        item.id ?? '',
        item.fileName ?? '',
        item.hospital ?? '',
        item.fund ?? '',
        item.baseFund ?? '',
        item.fiscalYear ?? '',
        item.month ?? '',
        item.claimAmount ?? '',
        item.approvedAmount ?? ''
    ].join('|');
}

function removeRecordsFromDatabaseByKeys(keys) {
    if (!(keys instanceof Set) || keys.size === 0) return 0;
    const before = database.length;
    const kept = database.filter(item => !keys.has(getDatabaseRecordKey(item)));
    database.length = 0;
    database.push(...kept);
    saveDatabaseToLocalStorage();
    return before - kept.length;
}

async function purgeSelectedDataFromUi() {
    const confirmed = confirm('คุณต้องการลบข้อมูลทั้งหมดหรือไม่? การทำรายการนี้ไม่สามารถย้อนกลับได้');
    if (!confirmed) return false;

    try {
        const result = await purgeDashboardDataBeforeImport();
        const removedRecords = result?.removedRecords || 0;
        const message = removedRecords > 0
            ? `ลบข้อมูลสำเร็จ ${removedRecords} รายการ`
            : 'ลบข้อมูลไม่สำเร็จ';
        showToast(message);
        recordAppLog('warn', 'purge-selected', 'ลบข้อมูลที่เลือก', {
            tab: activeTab,
            removedRecords
        });
        await loadDashboardData();
        refreshDashboard();
        return true;
    } catch (err) {
        alert(`นำเข้าข้อมูลไม่สำเร็จ: ${err.message}`);
            showToast(`นำเข้าข้อมูลไม่สำเร็จ: ${err.message}`, false);
        recordAppLog('error', 'purge-selected-failed', `ลบข้อมูลที่เลือกไม่สำเร็จ: ${err.message}`, {
            tab: activeTab,
            details: err.stack || err.message
        });
        return false;
    }
}

async function purgeAllDashboardData() {
    const confirmed = confirm('คุณต้องการล้างแดชบอร์ดทั้งหมดใช่หรือไม่? การทำรายการนี้ไม่สามารถย้อนกลับได้');
    if (!confirmed) return false;

    try {
        const token = currentSession?.token || localStorage.getItem('nhso_token') || '';
        if (token && token !== 'mock-sandbox-token') {
            const res = await fetch(`${BACKEND_URL}/dashboard/purge`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                }
            });
            const result = await res.json().catch(() => ({}));
            if (!res.ok || result.status !== 'success') {
                throw new Error(result.message || 'ล้างข้อมูลไม่สำเร็จ');
            }
        } else {
            database = [];
            saveDatabaseToLocalStorage();
        }

        localStorage.removeItem('nhso_database');
        localStorage.removeItem('nhso_managed_years');
        database = [];
        refreshDashboard();
        showToast('ล้างข้อมูลสำเร็จ', false);
        recordAppLog('warn', 'purge-all', 'ล้างข้อมูลทั้งหมด', {
            tab: activeTab
        });
        return true;
    } catch (err) {
        alert(`ล้างข้อมูลไม่สำเร็จ: ${err.message}`);
        showToast(`ล้างข้อมูลไม่สำเร็จ: ${err.message}`, false);
        recordAppLog('error', 'purge-all-failed', `ล้างข้อมูลทั้งหมดไม่สำเร็จ: ${err.message}`, {
            tab: activeTab,
            details: err.stack || err.message
        });
        return false;
    }
}

window.purgeAllDashboardData = purgeAllDashboardData;
window.purgeSelectedDataFromUi = purgeSelectedDataFromUi;

function generateMockData() {
    return [];
}

// Format numbers
function formatCurrency(value) {
    return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(value);
}

function formatPercent(value) {
    return new Intl.NumberFormat('th-TH', { style: 'percent', minimumFractionDigits: 1 }).format(value / 100);
}

function getDisplayFullName(username, fullName) {
    const systemNames = {
        admin: 'ผู้ดูแลระบบสูงสุด',
        officer: 'เจ้าหน้าที่รับข้อมูล สปสช.',
        executive: 'ผู้บริหารหน่วยงานภาครัฐ'
    };

    const key = String(username || '').toLowerCase();
    if (systemNames[key]) {
        return systemNames[key];
    }

    return fullName || '-';
}

const THAI_CALENDAR_MONTH_NAMES = [
    "มกราคม",
    "กุมภาพันธ์",
    "มีนาคม",
    "เมษายน",
    "พฤษภาคม",
    "มิถุนายน",
    "กรกฎาคม",
    "สิงหาคม",
    "กันยายน",
    "ตุลาคม",
    "พฤศจิกายน",
    "ธันวาคม"
];

function fiscalMonthToCalendarMonthIndex(m) {
    const fiscalMonth = parseInt(m, 10);
    if (!Number.isFinite(fiscalMonth) || fiscalMonth < 1 || fiscalMonth > 12) return null;
    return fiscalMonth <= 3 ? fiscalMonth + 9 : fiscalMonth - 3;
}

function getThaiMonthNameFromFiscalMonth(m, fiscalYear = null) {
    const calendarMonthIndex = fiscalMonthToCalendarMonthIndex(m);
    if (!calendarMonthIndex) return '-';
    const monthName = THAI_CALENDAR_MONTH_NAMES[calendarMonthIndex - 1] || '-';
    if (fiscalYear === null || fiscalYear === undefined || fiscalYear === '') return monthName;
    const fiscalYearNum = parseInt(fiscalYear, 10);
    if (!Number.isFinite(fiscalYearNum)) return monthName;
    return `${monthName} ${fiscalYearNum}`;
}

function getThaiMonthNameFromCalendarMonth(calendarMonth, fiscalYear = null) {
    const monthNum = parseInt(calendarMonth, 10);
    if (!Number.isFinite(monthNum) || monthNum < 1 || monthNum > 12) return '-';
    const monthName = THAI_CALENDAR_MONTH_NAMES[monthNum - 1] || '-';
    if (fiscalYear === null || fiscalYear === undefined || fiscalYear === '') return monthName;
    const fiscalYearNum = parseInt(fiscalYear, 10);
    if (!Number.isFinite(fiscalYearNum)) return monthName;
    return `${monthName} ${fiscalYearNum}`;
}

function getFiscalOrderFromCalendarMonth(calendarMonth, fiscalYear) {
    const monthNum = parseInt(calendarMonth, 10);
    const fiscalYearNum = parseInt(fiscalYear, 10);
    if (!Number.isFinite(monthNum) || !Number.isFinite(fiscalYearNum)) return Number.MAX_SAFE_INTEGER;
    const displayYear = monthNum >= 10 ? fiscalYearNum - 1 : fiscalYearNum;
    const fiscalMonthOrder = monthNum >= 10 ? monthNum - 9 : monthNum + 3;
    return (displayYear * 12) + fiscalMonthOrder;
}

function getMonthAbbr(m) {
    return getThaiMonthNameFromFiscalMonth(m);
}

// Parse month and fiscal year from filename (e.g., Hsub_Report_6809_02 -> Sep, FY 2569)
function parseMonthAndYearFromFileName(fileName) {
    let fileYear = new Date().getFullYear() + 543;
    let calMonth = new Date().getMonth() + 1;
    let fileMonth = (calMonth >= 10) ? calMonth - 9 : calMonth + 3;
    
    if (!fileName) return { fileYear, fileMonth };

    const strName = String(fileName);
    const lowerName = strName.toLowerCase();

    // 0a. Check 6-digit Buddhist date pattern YYMMDD after a known fund token
    // Example: DCKD69013100067 -> FY 2569, month 1
    const token6Match = strName.match(/(?:DCKD|DKTP|DNAP|DMTB|DDOW|DTLM|DCCS|DCMH|DTDT|DANC|DDSA|DMOR|DTTM|NCD)(\d{2})(\d{2})(\d{2})/i);
    if (token6Match) {
        const yy = parseInt(token6Match[1], 10);
        const mm = parseInt(token6Match[2], 10);
        if (yy >= 50 && yy <= 99 && mm >= 1 && mm <= 12) {
            fileMonth = (mm >= 10) ? (mm - 9) : (mm + 3);
            fileYear = 2500 + yy + (mm >= 10 ? 1 : 0);
            return { fileYear, fileMonth };
        }
    }

    // 0. Check explicit date pattern YYYYMMDD or YYYY-MM-DD in Buddhist year
    // Example: 25670801 => FY 2567, 25671001 => FY 2568
    const dateMatch = strName.match(/(25\d{2})[-_]?(\d{2})[-_]?(\d{2})/);
    if (dateMatch) {
        const yr = parseInt(dateMatch[1], 10);
        const mm = parseInt(dateMatch[2], 10);
        if (mm >= 1 && mm <= 12) {
            fileMonth = (mm >= 10) ? (mm - 9) : (mm + 3);
            fileYear = yr + (mm >= 10 ? 1 : 0);
            return { fileYear, fileMonth };
        }
    }

    // 1. Check pattern: Hsub_Report_YYMM_XX or Report_YYMM_XX or YYMM (e.g., 6809_02 or 6809)
    const hsubMatch = strName.match(/(?:hsub_report_|report_|_|^)(\d{2})(\d{2})(?:_|\b|\.)/i);
    if (hsubMatch) {
        const yy = parseInt(hsubMatch[1]);
        const mm = parseInt(hsubMatch[2]);
        if (yy >= 50 && yy <= 99 && mm >= 1 && mm <= 12) {
            const calM = mm;
            fileMonth = (calM >= 10) ? (calM - 9) : (calM + 3);
            fileYear = 2500 + yy + (calM >= 9 ? 1 : 0);
            return { fileYear, fileMonth };
        }
    }

    // 2. Check 4-digit year 25XX with month 01-12 (e.g. 256809, 2568_09, 2568-09)
    const y4m2Match = strName.match(/(25\d{2})[-_]?(\d{2})/);
    if (y4m2Match) {
        const yr = parseInt(y4m2Match[1]);
        const mm = parseInt(y4m2Match[2]);
        if (mm >= 1 && mm <= 12) {
            const calM = mm;
            fileMonth = (calM >= 10) ? (calM - 9) : (calM + 3);
            fileYear = yr + (calM >= 10 ? 1 : 0);
            return { fileYear, fileMonth };
        }
    }

    // 3. Fallback: 4-digit year 25XX
    const yMatch = strName.match(/(25\d{2})/);
    if (yMatch) {
        fileYear = parseInt(yMatch[1]);
    }

    // 4. Fallback month by Thai month names
    const thaiMonths = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    const thaiShortMonths = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    
    for (let i = 0; i < 12; i++) {
        if (lowerName.includes(thaiMonths[i].toLowerCase()) || lowerName.includes(thaiShortMonths[i].toLowerCase())) {
            const calM = i + 1; // 1..12
            fileMonth = (calM >= 10) ? (calM - 9) : (calM + 3);
            break;
        }
    }

    return { fileYear, fileMonth };
}

// Filter dataset
function getFilteredData() {
    const hospFilter = document.getElementById('hospital-input').value.trim();
    const yearSelect = document.getElementById('year-select');
    const yearFilter = yearSelect ? yearSelect.value : 'all';
    const startMonth = parseInt(document.getElementById('start-month-select').value);
    const endMonth = parseInt(document.getElementById('end-month-select').value);
    const selectedFile = getSelectedDashboardFileValue();

    const filtered = database.filter(item => {
        if (!matchesCurrentTabScope(item)) return false;

        let itemYear = item.fiscalYear;
        let itemMonth = item.month;
        const hasStoredYear = itemYear !== undefined && itemYear !== null && String(itemYear).trim() !== '' && String(itemYear) !== 'undefined' && String(itemYear) !== 'null';
        const hasStoredMonth = itemMonth !== undefined && itemMonth !== null && String(itemMonth).trim() !== '' && !isNaN(parseInt(itemMonth, 10));

        if ((!hasStoredYear || !hasStoredMonth) && item.fileName) {
            const ym = parseMonthAndYearFromFileName(item.fileName);
            if (ym) {
                if (!hasStoredYear && ym.fileYear) itemYear = ym.fileYear;
                if (!hasStoredMonth && ym.fileMonth) itemMonth = ym.fileMonth;
            }
        }

        if (yearFilter !== 'all' && String(itemYear) !== String(yearFilter)) return false;
        if (hospFilter !== '') {
            if (!item.hospital.toLowerCase().includes(hospFilter.toLowerCase())) return false;
        }
        if (!isMonthWithinSelectedRange(itemMonth, startMonth, endMonth)) return false;
        if (selectedFile !== 'all' && normalizeDashboardFileValue(item.fileName || '') !== normalizeDashboardFileValue(selectedFile)) return false;

        const seamlessSubtab = getCurrentSeamlessSubtab();
        if (activeTab === 'seamless' && seamlessSubtab) {
            const recordMenuKey = getRecordMenuKey(item);
            if (recordMenuKey && recordMenuKey !== seamlessSubtab) return false;
            const prop = seamlessSubtab + '_amt';
            if (item.subFundAmount > 0) {
                if (item.mainCategoryName !== getCurrentSeamlessSubtabName()) return false;
            } else {
                if (!item[prop] || item[prop] <= 0) return false;
            }
        }
        
        return true;
    });

    // Deduplicate: Keep only the latest uploaded record for each unique (hospital, fund, year, month, fileName)
    const seen = new Set();
    const deduplicated = [];
    for (let i = filtered.length - 1; i >= 0; i--) {
        const item = filtered[i];
        const key = `${item.hospital}-${item.fund}-${item.fiscalYear}-${item.month}-${item.fileName || ''}`;
        if (!seen.has(key)) {
            seen.add(key);
            deduplicated.unshift(item);
        }
    }
    return deduplicated;
}

// Refresh KPI values
// Refresh KPI values
function updateKPICards(filteredData) {
    document.getElementById('ppfs-sub-kpis').style.display = 'none';
    document.getElementById('standard-kpis').style.display = 'grid';
    
    let totalClaim = 0;
    let totalApprove = 0;
    let totalPending = 0;

    filteredData.forEach(item => {
        const seamlessSubtab = getCurrentSeamlessSubtab();
        if (activeTab === 'seamless' && seamlessSubtab) {
            const prop = seamlessSubtab + '_amt';
            const amt = item[prop] || 0;
            totalClaim += amt;
            totalApprove += amt; // Assuming 100% approval for sub-categories for now
            // totalPending remains 0
        } else {
            totalClaim += item.claimAmount;
            totalApprove += item.approvedAmount;
            totalPending += item.pendingAmount;
        }
    });

    const rate = totalClaim > 0 ? (totalApprove / totalClaim) * 100 : 0;

    document.getElementById('kpi-total').textContent = formatCurrency(totalClaim);
    document.getElementById('kpi-approved').textContent = formatCurrency(totalApprove);
    document.getElementById('kpi-pending').textContent = formatCurrency(totalPending);
    document.getElementById('kpi-rate').textContent = formatPercent(rate);

        const yearVal = document.getElementById('year-select').value;
        const yearText = yearVal === 'all' ? 'ทุกปีงบประมาณ' : `ปีงบประมาณ ${yearVal}`;
        document.getElementById('kpi-total-sub').textContent = yearText;
        document.getElementById('kpi-approved-sub').textContent = 'ยอดอนุมัติสะสม';
        document.getElementById('kpi-pending-sub').textContent = 'มีข้อผิดพลาดข้อมูล';
        document.getElementById('kpi-rate-sub').textContent = 'อิงจากข้อมูลที่ประมวลผล';
}

// Update Top Errors Widget
function updateTopErrorsWidget() {
    const errorData = getFilteredData();

    const errorCounts = {};
    errorData.forEach(item => {
        if (item.errors && Array.isArray(item.errors)) {
            item.errors.forEach(err => {
                if (!errorCounts[err.code]) {
                    errorCounts[err.code] = { code: err.code, desc: err.desc, count: 0 };
                }
                errorCounts[err.code].count += err.count;
            });
        }
    });

    const sortedErrors = Object.values(errorCounts).sort((a, b) => b.count - a.count);
    const titleNode = document.getElementById('error-tab-title');
    titleNode.textContent = 'ภาพรวมข้อมูลผิดพลาด (OP & IP)';

    const listContainer = document.getElementById('top-errors-list');
    listContainer.innerHTML = '';

    if (sortedErrors.length === 0) {
        listContainer.innerHTML = `<li class="error-item" style="border-left-color: #64748b; background: rgba(255,255,255,0.02);"><div class="error-info"><span class="error-code">ไม่ระบุ</span><span class="error-desc">ไม่พบคำอธิบายข้อผิดพลาด</span></div><span class="error-count" style="color: #64748b; background: rgba(255,255,255,0.05)">0</span></li>`;



        return;
    }

    sortedErrors.forEach(err => {
        const isIPError = activeTab === 'ip' || err.code.startsWith('S') || err.code.startsWith('M');
        const itemClass = isIPError ? 'error-item ip' : 'error-item';
        const li = document.createElement('li');
        li.className = itemClass;
        li.innerHTML = `
            <div class="error-info" title="${err.desc}">
                <span class="error-code">${err.code}</span>
                <span class="error-desc">${err.desc}</span>
            </div>
            <span class="error-count">${err.count.toLocaleString()} รายการ</span>
        `;
        listContainer.appendChild(li);
    });
}

// Get filtered data excluding the activeTab filter for comparison chart
function getFilteredDataForComparison() {
    const hospFilter = document.getElementById('hospital-input').value.trim();
    const yearSelect = document.getElementById('year-select');
    const yearFilter = yearSelect ? yearSelect.value : 'all';
    const startMonth = parseInt(document.getElementById('start-month-select').value);
    const endMonth = parseInt(document.getElementById('end-month-select').value);
    const selectedFile = getSelectedDashboardFileValue();

    return database.filter(item => {
        if (!matchesCurrentTabScope(item)) return false;
        if (yearFilter !== 'all' && String(item.fiscalYear) !== String(yearFilter)) return false;
        if (hospFilter !== '') {
            if (!item.hospital.toLowerCase().includes(hospFilter.toLowerCase())) return false;
        }
        if (!isMonthWithinSelectedRange(item.month, startMonth, endMonth)) return false;
        if (selectedFile !== 'all' && normalizeDashboardFileValue(item.fileName || '') !== normalizeDashboardFileValue(selectedFile)) return false;
        return true;
    });
}

// Update Chart.js Graphics
function updateCharts(filteredData) {
    // 1. Left Chart: Trend by Year (เนเธเธเนเธเธเนเธเธเนเธเธเธฑเธเนเธเธเธญเธเนเธเธเธดเธเนเธเธเธฒเธ เธชเธเธชเธ. เนเธเธเนเธเธเนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธ)
    const trendChartTitle = document.getElementById('trend-chart-title');
    const trendChartSub = document.getElementById('trend-chart-subtitle');
    if (trendChartTitle) trendChartTitle.textContent = 'แนวโน้มสถานะทางการเงินชดเชยแยกรายปีงบประมาณ';

    const yearTotals = {};
    const hospFilter = document.getElementById('hospital-input').value.trim();
    const yearFilter = document.getElementById('year-select').value;
    const selectedYear = yearFilter === 'all' ? null : String(yearFilter);

    filteredData.forEach(item => {
        if (hospFilter !== '' && !item.hospital.toLowerCase().includes(hospFilter.toLowerCase())) return;
        const year = String(item.fiscalYear);
        if (selectedYear && year !== selectedYear) return;
        if (!yearTotals[year]) yearTotals[year] = 0;
        yearTotals[year] += Number(item.approvedAmount || 0);
    });

    const sortedYears = sortFiscalYears(Object.keys(yearTotals), 'asc');
    if (trendChartSub) {
        if (selectedYear) {
            trendChartSub.textContent = getFiscalYearRangeText([selectedYear]);
        } else {
            trendChartSub.textContent = getFiscalYearRangeText(sortedYears.length > 0 ? sortedYears : filteredData.map(item => item.fiscalYear));
        }
    }
    const trendLabels = sortedYears;
    const approvedTrend = sortedYears.map(yr => yearTotals[yr]);

    if (trendChart) trendChart.destroy();
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    trendChart = new Chart(ctxTrend, {
        type: 'bar',
        data: {
            labels: trendLabels,
            datasets: [{ 
                label: 'เนเธเธเธญเธ”เธญเธเนเธเธเนเธเธเธฑเธ•เธดเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธ เนเธเธเนเธเธ)', 
                data: approvedTrend, 
                backgroundColor: '#4a85a0', 
                borderRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) { return (value / 1000000).toFixed(2); },
                    font: { size: 10 }
                }
            },
            scales: {
                y: { title: { display: true, text: 'ล้านบาท' }, ticks: { callback: function(value) { return (value / 1000000); } } },
                x: { title: { display: true, text: 'ปีงบประมาณ' } }
            }
        },
        plugins: window.ChartDataLabels ? [window.ChartDataLabels] : [] // Ensure datalabels plugin is used if loaded
    });

    // 2. Right Chart: Trend by Year-Month (เนเธเธเนเธเธเนเธเธเนเธเธเธญเธเนเธเธเธดเธเนเธเธเนเธเธเนเธเธเธขเธเนเธเธเธฒเธเนเธเธเธดเธเนเธเธเนเธเธ)
    const compareTitle = document.getElementById('compare-chart-title');
    const compareSubtitle = document.getElementById('compare-chart-subtitle');
    if (compareTitle) compareTitle.textContent = 'เปรียบเทียบยอดการจ่ายชดเชยแยกรายเดือน';
    if (compareSubtitle) compareSubtitle.textContent = 'สัดส่วนงบชดเชยที่อนุมัติจริงรายกองทุน';

    // Build year-month labels based on selected filters to ensure empty months are shown
    const monthMap = {};
    const monthLabelMap = {};
    const monthSortMap = {};
    const startM = parseInt(document.getElementById('start-month-select').value) || 1;
    const endM = parseInt(document.getElementById('end-month-select').value) || 12;

    let targetYears = [];
    if (yearFilter === 'all') {
        targetYears = getPlausibleFiscalYears(database.map(item => item.fiscalYear)).map(yr => parseInt(yr, 10));
        if (targetYears.length === 0) targetYears = [getCurrentFiscalYearBE()];
    } else {
        targetYears = [parseInt(yearFilter)];
    }

    const selectedMonths = [];
    let monthCursor = startM;
    while (true) {
        selectedMonths.push(monthCursor);
        if (monthCursor === endM) break;
        monthCursor = monthCursor === 12 ? 1 : monthCursor + 1;
        if (selectedMonths.length > 12) break;
    }

    targetYears.forEach(y => {
        selectedMonths.forEach(m => {
            // Convert fiscal month (1-12) to calendar month & year
            let calMonth = (m <= 3) ? m + 9 : m - 3;
            let calYear = (m <= 3) ? y - 1 : y;
            let ym = `${calYear}-${calMonth.toString().padStart(2, '0')}`;
            monthMap[ym] = 0;
            monthLabelMap[ym] = getThaiMonthNameFromCalendarMonth(calMonth, y);
            monthSortMap[ym] = getFiscalOrderFromCalendarMonth(calMonth, y);
        });
    });

    filteredData.forEach(item => {
        let calMonth = (item.month <= 3) ? item.month + 9 : item.month - 3;
        if (!isPlausibleFiscalYear(item.fiscalYear)) return;
        let calYear = (item.month <= 3) ? parseInt(item.fiscalYear, 10) - 1 : parseInt(item.fiscalYear, 10);
        let ym = `${calYear}-${calMonth.toString().padStart(2, '0')}`;
        
        if (monthMap[ym] === undefined) {
            monthMap[ym] = 0;
            monthLabelMap[ym] = getThaiMonthNameFromCalendarMonth(calMonth, item.fiscalYear);
            monthSortMap[ym] = getFiscalOrderFromCalendarMonth(calMonth, item.fiscalYear);
        }
        monthMap[ym] += item.approvedAmount;
    });

    const sortedYM = Object.keys(monthMap).sort((a, b) => (monthSortMap[a] || 0) - (monthSortMap[b] || 0));
    let compareData = sortedYM.map(ym => monthMap[ym]);
    let compareLabels = sortedYM.map(ym => monthLabelMap[ym] || ym);

    if (compareChart) compareChart.destroy();
    const ctxCompare = document.getElementById('compareChart').getContext('2d');
    compareChart = new Chart(ctxCompare, {
        type: 'bar',
        data: {
            labels: compareLabels,
            datasets: [{ 
                label: 'เนเธเธเธญเธ”เธญเธเนเธเธเนเธเธเธฑเธ•เธดเธเนเธเธเนเธเธเนเธเธ เนเธเธเนเธเธ)', 
                data: compareData, 
                backgroundColor: '#86b7ba', 
                borderRadius: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) { return (value / 1000000).toFixed(2); },
                    font: { size: 10 }
                }
            },
            scales: {
                y: { title: { display: true, text: 'เนเธเธเนเธเธเธฒเธเนเธเธเธฒเธ—' }, ticks: { callback: function(value) { return (value / 1000000); } } },
                x: { title: { display: true, text: 'เนเธเธเนเธเธเธญเธ' } }
            }
        },
        plugins: window.ChartDataLabels ? [window.ChartDataLabels] : []
    });

    // 3. IP CMI-AdjRW Chart & Table
    if (activeTab === 'ip') {
        const selectedYear = document.getElementById('year-select') ? document.getElementById('year-select').value : 'all';
        const selectedYearNum = selectedYear !== 'all' ? parseInt(selectedYear, 10) : null;
        const filteredDataset = typeof getFilteredData === 'function' ? getFilteredData() : database;
        const ipItems = filteredDataset.filter(item =>
            (item.fund === 'ip' || item.fund === 'IPBKK' || item.baseFund === 'IPBKK')
        );

        let ipYears = getPlausibleFiscalYears(ipItems.map(item => item.fiscalYear));
        if (selectedYearNum && !isNaN(selectedYearNum)) {
            ipYears = ipYears.filter(yr => parseInt(yr, 10) <= selectedYearNum);
            if (ipYears.length === 0) ipYears = [String(selectedYearNum)];
        } else if (ipYears.length === 0) {
            ipYears = ["2563", "2564", "2565", "2566", "2567", "2568", "2569"];
        }

        const mockCmiMap = {
            "2563": 0.5736,
            "2564": 0.6078,
            "2565": 0.6292,
            "2566": 0.5736,
            "2567": 0.6553,
            "2568": 0.6072,
            "2569": 0.6128
        };

        const cmiValues = ipYears.map(yr => {
            const yearItems = ipItems.filter(item => 
                (item.fiscalYear === parseInt(yr) || String(item.fiscalYear) === String(yr))
            );

            let sumCmi = 0;
            let countCmi = 0;
            let sumAdjRw = 0;
            let sumCases = 0;

            yearItems.forEach(item => {
                if (item.cmi !== undefined && item.cmi > 0) {
                    sumCmi += item.cmi;
                    countCmi++;
                }
                if (item.adjRwSum !== undefined && item.totalRecords !== undefined && item.totalRecords > 0) {
                    sumAdjRw += item.adjRwSum;
                    sumCases += item.totalRecords;
                }
            });

            if (countCmi > 0) {
                return parseFloat((sumCmi / countCmi).toFixed(4));
            } else if (sumCases > 0 && sumAdjRw > 0) {
                return parseFloat((sumAdjRw / sumCases).toFixed(4));
            } else {
                return mockCmiMap[yr] !== undefined ? mockCmiMap[yr] : 0.6000;
            }
        });

        if (ipCmiChart) ipCmiChart.destroy();
        const ctxCmi = document.getElementById('ipCmiChart').getContext('2d');

        ipCmiChart = new Chart(ctxCmi, {
            type: 'line',
            data: {
                labels: ipYears,
                datasets: [{
                    label: 'Case Mix Index (CMI)',
                    data: cmiValues,
                    borderColor: '#425847', // dark green line from screenshot
                    backgroundColor: '#58755e', // dark green fill from screenshot
                    borderWidth: 1.5,
                    pointBackgroundColor: '#ffffff', // white center
                    pointBorderColor: '#425847', // dark green border
                    pointBorderWidth: 1.5,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0 // straight line segments as shown in screenshot
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        display: true,
                        anchor: 'end',
                        align: 'top',
                        offset: 4,
                        formatter: function(value) {
                            if (value === null || value === undefined || isNaN(value)) return '';
                            return Number(value).toFixed(4);
                        },
                        font: { size: 10, weight: 'normal', family: "'Inter', 'Sarabun', sans-serif" },
                        color: '#000000'
                    }
                },
                scales: {
                    y: { 
                        title: { display: true, text: 'adjrw CMI', align: 'end', font: { weight: 'normal', size: 11 }, color: '#333333' },
                        min: 0,
                        max: 0.7,
                        grid: { color: '#e2e8f0' },
                        ticks: {
                            stepSize: 0.1,
                            font: { size: 11 },
                            color: '#333333',
                            callback: function(value) {
                                return value % 1 === 0 ? value : value.toFixed(1);
                            }
                        }
                    },
                    x: { 
                        title: { display: true, text: 'เดือน', align: 'end', font: { size: 12, weight: 'normal' }, color: '#333333' },
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 },
                            color: '#333333'
                        }
                    }
                }
            },
            plugins: (typeof ChartDataLabels !== 'undefined') ? [ChartDataLabels] : []
        });

        // Render Dynamic CMI Summary Table
        const tbodyCmi = document.getElementById('ip-cmi-tbody');
        if (tbodyCmi) {
            tbodyCmi.innerHTML = '';
            let prevCmiVal = null;

            ipYears.forEach((yr, idx) => {
                const cmiVal = cmiValues[idx];
                const yearItems = ipItems.filter(item => 
                    (item.fiscalYear === parseInt(yr) || String(item.fiscalYear) === String(yr))
                );

                let totalCases = 0;
                let totalAdjRw = 0;

                yearItems.forEach(item => {
                    if (item.totalRecords) totalCases += item.totalRecords;
                    if (item.adjRwSum) totalAdjRw += item.adjRwSum;
                });

                if (totalCases === 0) {
                    const mockCasesMap = { "2563": 1240, "2564": 1380, "2565": 1450, "2566": 1320, "2567": 1560, "2568": 1490, "2569": 1520 };
                    totalCases = mockCasesMap[yr] || 1500;
                    totalAdjRw = parseFloat((totalCases * cmiVal).toFixed(2));
                }

                let changeBadge = '<span style="color: #64748b;">-</span>';
                if (prevCmiVal !== null && prevCmiVal > 0) {
                    const diffPct = (((cmiVal - prevCmiVal) / prevCmiVal) * 100).toFixed(2);
                    if (diffPct > 0) {
                        changeBadge = `<span style="color: #16a34a; font-weight: 600;">เน€เธโ€ +${diffPct}%</span>`;
                    } else if (diffPct < 0) {
                        changeBadge = `<span style="color: #dc2626; font-weight: 600;">เนเธเธ ${diffPct}%</span>`;
                    } else {
                        changeBadge = `<span style="color: #64748b;">0.00%</span>`;
                    }
                }
                prevCmiVal = cmiVal;

                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #e2e8f0';
                tr.style.textAlign = 'center';
                if (idx % 2 === 1) tr.style.backgroundColor = '#f8fafc';

                tr.innerHTML = `
                    <td style="padding: 10px; font-weight: 600; color: #1e293b;">${yr}</td>
                    <td style="padding: 10px; color: #334155;">${totalCases.toLocaleString('th-TH')}</td>
                    <td style="padding: 10px; color: #334155;">${totalAdjRw.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td style="padding: 10px; font-weight: 700; color: #425847; background-color: #f0fdf4; font-size: 14px;">${cmiVal.toFixed(4)}</td>
                    <td style="padding: 10px;">${changeBadge}</td>
                `;
                tbodyCmi.appendChild(tr);
            });
        }

        // Render IP Submission Delay Table (IP01/IP02)
        renderIpSubmissionDelayTable();
    }
}

let ipSubmissionDelayData = JSON.parse(localStorage.getItem('nhso_ip_delay_data')) || [
    { month: '2568-10', d30Count: 392, d30Rw: 242.43, d60Count: 3, d60Rw: 1.11, d90Count: 0, d90Rw: 0.00, gt90Count: 0, gt90Rw: 0.00 },
    { month: '2568-11', d30Count: 343, d30Rw: 203.88, d60Count: 5, d60Rw: 3.83, d90Count: 0, d90Rw: 0.00, gt90Count: 0, gt90Rw: 0.00 },
    { month: '2568-12', d30Count: 549, d30Rw: 300.45, d60Count: 3, d60Rw: 1.49, d90Count: 1, d90Rw: 2.71, gt90Count: 0, gt90Rw: 0.00 },
    { month: '2569-01', d30Count: 377, d30Rw: 231.57, d60Count: 6, d60Rw: 4.35, d90Count: 1, d90Rw: 0.51, gt90Count: 0, gt90Rw: 0.00 },
    { month: '2569-02', d30Count: 375, d30Rw: 202.45, d60Count: 5, d60Rw: 3.71, d90Count: 2, d90Rw: 0.98, gt90Count: 0, gt90Rw: 0.00 },
    { month: '2569-03', d30Count: 406, d30Rw: 244.02, d60Count: 7, d60Rw: 7.08, d90Count: 0, d90Rw: 0.00, gt90Count: 1, gt90Rw: 0.83 },
    { month: '2569-04', d30Count: 228, d30Rw: 148.62, d60Count: 7, d60Rw: 5.85, d90Count: 0, d90Rw: 0.00, gt90Count: 4, gt90Rw: 2.00 },
    { month: '2569-05', d30Count: 427, d30Rw: 311.84, d60Count: 12, d60Rw: 6.57, d90Count: 0, d90Rw: 0.00, gt90Count: 9, gt90Rw: 4.06 },
    { month: '2569-06', d30Count: 340, d30Rw: 245.85, d60Count: 1, d60Rw: 0.60, d90Count: 0, d90Rw: 0.00, gt90Count: 0, gt90Rw: 0.00 },
    { month: '2569-07', d30Count: 136, d30Rw: 99.18, d60Count: 1, d60Rw: 0.58, d90Count: 0, d90Rw: 0.00, gt90Count: 0, gt90Rw: 0.00 }
];

function renderIpSubmissionDelayTable() {
    const tbody = document.getElementById('ip-delay-tbody');
    const tfoot = document.getElementById('ip-delay-tfoot');
    if (!tbody || !tfoot) return;

    tbody.innerHTML = '';
    tfoot.innerHTML = '';

    let sumD30Count = 0, sumD30Rw = 0;
    let sumD60Count = 0, sumD60Rw = 0;
    let sumD90Count = 0, sumD90Rw = 0;
    let sumGt90Count = 0, sumGt90Rw = 0;
    let sumTotalCount = 0, sumTotalRw = 0;

    ipSubmissionDelayData.forEach((row, idx) => {
        const d30C = Number(row.d30Count) || 0;
        const d30R = Number(row.d30Rw) || 0;
        const d60C = Number(row.d60Count) || 0;
        const d60R = Number(row.d60Rw) || 0;
        const d90C = Number(row.d90Count) || 0;
        const d90R = Number(row.d90Rw) || 0;
        const gt90C = Number(row.gt90Count) || 0;
        const gt90R = Number(row.gt90Rw) || 0;

        const rowTotalCount = d30C + d60C + d90C + gt90C;
        const rowTotalRw = d30R + d60R + d90R + gt90R;

        sumD30Count += d30C; sumD30Rw += d30R;
        sumD60Count += d60C; sumD60Rw += d60R;
        sumD90Count += d90C; sumD90Rw += d90R;
        sumGt90Count += gt90C; sumGt90Rw += gt90R;
        sumTotalCount += rowTotalCount; sumTotalRw += rowTotalRw;

        const tr = document.createElement('tr');
        tr.style.borderBottom = '1px solid #e2e8f0';
        tr.style.textAlign = 'center';
        if (idx % 2 === 1) tr.style.backgroundColor = '#f8fafc';

        tr.innerHTML = `
            <td style="padding: 8px 6px; font-weight: 600; color: #1e293b;">${row.month}</td>
            <td style="padding: 8px 6px; color: #334155;">${d30C.toLocaleString('th-TH')}</td>
            <td style="padding: 8px 6px; color: #334155;">${d30R.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td style="padding: 8px 6px; color: #334155;">${d60C.toLocaleString('th-TH')}</td>
            <td style="padding: 8px 6px; color: #334155;">${d60R.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td style="padding: 8px 6px; color: #334155;">${d90C.toLocaleString('th-TH')}</td>
            <td style="padding: 8px 6px; color: #334155;">${d90R.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td style="padding: 8px 6px; color: #334155;">${gt90C.toLocaleString('th-TH')}</td>
            <td style="padding: 8px 6px; color: #334155;">${gt90R.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td style="padding: 8px 6px; font-weight: 600; background-color: #f8fafc; color: #0f172a;">${rowTotalCount.toLocaleString('th-TH')}</td>
            <td style="padding: 8px 6px; font-weight: 600; background-color: #f8fafc; color: #0f172a;">${rowTotalRw.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        `;
        tbody.appendChild(tr);
    });

    const footTr = document.createElement('tr');
    footTr.style.textAlign = 'center';
    footTr.innerHTML = `
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">เน€เธเธเน€เธเธเน€เธเธ</td>
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">${sumD30Count.toLocaleString('th-TH')}</td>
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">${sumD30Rw.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">${sumD60Count.toLocaleString('th-TH')}</td>
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">${sumD60Rw.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">${sumD90Count.toLocaleString('th-TH')}</td>
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">${sumD90Rw.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">${sumGt90Count.toLocaleString('th-TH')}</td>
        <td style="padding: 10px 6px; font-weight: 700; color: #0f172a;">${sumGt90Rw.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
        <td style="padding: 10px 6px; font-weight: 700; background-color: #e2e8f0; color: #0f172a; font-size: 13px;">${sumTotalCount.toLocaleString('th-TH')}</td>
        <td style="padding: 10px 6px; font-weight: 700; background-color: #e2e8f0; color: #0f172a; font-size: 13px;">${sumTotalRw.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
    `;
    tfoot.appendChild(footTr);
}


function updateSummaryTable(filteredData) {
    const table = document.querySelector('#summary-table');
    if (!table) return;
    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    if (!tbody || !thead) return;
    
    tbody.innerHTML = '';
    const formatMoney = (num) => new Intl.NumberFormat('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);

    const batchWrapper = document.getElementById('batch-select-wrapper');
    const batchSelect = document.getElementById('batch-period-select');

    if (activeTab === 'ppfs_sub') {
        if (batchWrapper) batchWrapper.style.display = 'flex';

        // Populate batch select options dynamically
        if (batchSelect) {
            const currentSelected = batchSelect.value || 'all';
            const rawUnique = Array.from(new Set(filteredData.map(item => {
                let name = item.fileName || 'Hsub_Report_6810_02';
                if (name.includes('.')) name = name.split('.')[0];
                return name;
            }))).filter(Boolean);

            batchSelect.innerHTML = '<option value="all">ทั้งหมด (ทุกงวด)</option>';
            rawUnique.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b;
                opt.textContent = b;
                if (b === currentSelected) opt.selected = true;
                batchSelect.appendChild(opt);
            });

            if (!batchSelect.dataset.hasListener) {
                batchSelect.dataset.hasListener = 'true';
                batchSelect.addEventListener('change', () => {
                    updateSummaryTable(getFilteredData());
                });
            }
        }

        thead.innerHTML = `
            <tr>
                <th style="text-align: left; white-space: nowrap;">เนเธเธเธฅเธเนเธเธเนเธเธเนเธเธเธงเธ”เนเธเธเธฒเธ</th>
                <th style="text-align: center; white-space: nowrap;">เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเนเธเธ</th>
                <th style="text-align: left; white-space: nowrap;">เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธ</th>
                <th style="text-align: right;">OP</th>
                <th style="text-align: right;">HC DRUG</th>
                <th style="text-align: right;">PP</th>
                <th style="text-align: right;">FS</th>
                <th style="text-align: right; white-space: nowrap;">เนเธเธเธญเธ”เนเธเธเนเธเธเนเธเธเธขเธ—เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
            </tr>
        `;
        
        if (filteredData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธ</td></tr>';
            return;
        }

        const selectedBatchVal = batchSelect ? batchSelect.value : 'all';
        const renderAmt = (val) => val > 0 ? formatMoney(val) : '-';
        const hospAgg = {};
        let grandOp = 0;
        let grandHcDrug = 0;
        let grandPp = 0;
        let grandFs = 0;
        let grandTotal = 0;
        
        filteredData.forEach(item => {
            let batchName = item.fileName || 'Hsub_Report_6810_02';
            if (batchName.includes('.')) batchName = batchName.split('.')[0];

            if (selectedBatchVal !== 'all' && batchName !== selectedBatchVal) return;

            let hospCode = item.hospital.includes(' - ') ? item.hospital.split(' - ')[0] : item.hospital;
            let hospName = item.hospital.includes(' - ') ? item.hospital.split(' - ')[1] : (HOSPITAL_DB_MAP[hospCode] || item.hospital);

            const cleanCodeMatch = hospCode.match(/\d+/);
            if (cleanCodeMatch) {
                const rawDigits = cleanCodeMatch[0];
                hospCode = rawDigits.length === 4 ? '0' + rawDigits : rawDigits;
                if (HOSPITAL_DB_MAP[hospCode]) {
                    hospName = HOSPITAL_DB_MAP[hospCode];
                }
            }

            const groupKey = `${batchName}||${hospCode}`;
            if (!hospAgg[groupKey]) {
                hospAgg[groupKey] = { 
                    batch: batchName,
                    code: hospCode, 
                    name: hospName, 
                    op: 0, 
                    hcDrug: 0, 
                    pp: 0, 
                    fs: 0, 
                    total: 0 
                };
            }
            const itemOp = item.op_amt || 0;
            const itemHcDrug = item.hc_drug_amt || 0;
            const itemPp = item.pp_amt || 0;
            const itemFs = item.fs_amt || 0;
            const itemTotal = (itemOp + itemHcDrug + itemPp + itemFs) || item.approvedAmount || 0;

            hospAgg[groupKey].op += itemOp;
            hospAgg[groupKey].hcDrug += itemHcDrug;
            hospAgg[groupKey].pp += itemPp;
            hospAgg[groupKey].fs += itemFs;
            hospAgg[groupKey].total += itemTotal;

            grandOp += itemOp;
            grandHcDrug += itemHcDrug;
            grandPp += itemPp;
            grandFs += itemFs;
            grandTotal += itemTotal;
        });

        const sortedData = Object.values(hospAgg).sort((a, b) => b.total - a.total);
        
        if (sortedData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธงเธ”เนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธญเธ</td></tr>';
            return;
        }

        sortedData.forEach(item => {
            tbody.innerHTML += `
                <tr>
                    <td style="text-align: left; font-weight: 500; color: #475569; white-space: nowrap;">${item.batch}</td>
                    <td class="text-center font-bold" style="color:var(--text-secondary); text-align: center;">${item.code}</td>
                    <td style="text-align: left;"><strong>${item.name}</strong></td>
                    <td class="numeric" style="text-align: right;">${renderAmt(item.op)}</td>
                    <td class="numeric" style="text-align: right;">${renderAmt(item.hcDrug)}</td>
                    <td class="numeric" style="text-align: right;">${renderAmt(item.pp)}</td>
                    <td class="numeric" style="text-align: right;">${renderAmt(item.fs)}</td>
                    <td class="numeric-bold text-blue" style="text-align: right; background: rgba(14, 165, 233, 0.05);">${formatMoney(item.total)}</td>
                </tr>
            `;
        });
        
        tbody.innerHTML += `
            <tr style="background-color: #f8fafc; font-weight: 600;">
                <td colspan="3" style="text-align: center;">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</td>
                <td style="text-align: right;">${formatMoney(grandOp)}</td>
                <td style="text-align: right;">${formatMoney(grandHcDrug)}</td>
                <td style="text-align: right;">${formatMoney(grandPp)}</td>
                <td style="text-align: right;">${formatMoney(grandFs)}</td>
                <td style="text-align: right; color: #10b981; font-weight: bold; background: rgba(16, 185, 129, 0.08);">${formatMoney(grandTotal)}</td>
            </tr>
        `;
        return;
    } else {
        if (batchWrapper) batchWrapper.style.display = 'none';
    }

    const isIpOp = activeTab === 'ip' || activeTab === 'op';
    const isSmart = activeTab === 'smartmoney';

    if (isIpOp) {
        thead.innerHTML = `
            <tr>
                <th>รหัส</th>
                <th>ชื่อกองทุนหลัก</th>
                <th>ชื่อกองทุนย่อย</th>
                <th style="text-align: right;">ยอดขอเบิก (บาท)</th>
                <th style="text-align: right;">ยอดอนุมัติจ่ายจริง (บาท)</th>
            </tr>
        `;
    } else if (isSmart) {
        thead.innerHTML = `
            <tr>
                <th>ชื่อกองทุนหลัก</th>
                <th>ชื่อกองทุนย่อย</th>
                <th style="text-align: right;">จำนวนเงิน (บาท)</th>
            </tr>
        `;
    } else {
        thead.innerHTML = `
            <tr>
                <th>รหัส</th>
                <th>ชื่อกองทุนหลัก</th>
                <th>รหัสกองทุนย่อย</th>
                <th>ชื่อกองทุนย่อย</th>
                <th style="text-align: right;">จำนวนเงิน (บาท)</th>
            </tr>
        `;
    }

    if (filteredData.length === 0) {
        const colSpanCount = isIpOp ? 5 : (isSmart ? 3 : 5);
        tbody.innerHTML = `<tr><td colspan="${colSpanCount}" class="text-center">ไม่พบข้อมูลในตาราง</td></tr>`;
        return;
    }

    const subFunds = [];
    let grandTotal = 0;
    let grandClaim = 0;
    let fundAgg = {};

    filteredData.forEach(item => {
        const fundName = FUNDS[item.fund] || item.fund;
        const shortFund = (fundName || '').toString().split(' (')[0];
        
        const isItemIpOp = item.fund === 'ip' || item.fund === 'op' || item.fund === 'IPBKK' || item.fund === 'OPBKK';
        
        if ((item.fund === 'smartmoney' || isItemIpOp) && item.subFundsBreakdown) {
            item.subFundsBreakdown.forEach(sub => {
                // Group ONLY by เนเธเธเนเธเธเนเธเธเธญเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฑเธ and เนเธเธเนเธเธเนเธเธเธญเธเธญเธเนเธเธเธธเธเนเธเธเนเธเธเนเธเธเนเธเธ
                const aggKey = sub.main + '||' + sub.subName;
                if (!fundAgg[aggKey]) {
                    fundAgg[aggKey] = { 
                        code: sub.code, 
                        main: sub.main, 
                        subCode: sub.subCode, 
                        subName: sub.subName, 
                        amt: 0,
                        claimAmt: 0
                    };
                }
                fundAgg[aggKey].amt += sub.amt;
                fundAgg[aggKey].claimAmt += sub.claimAmt || 0;
            });
        } else if (item.fund === 'seamless') {
            if (item.subFundAmount > 0) {
                let sName = item.subFundName || '-';
                let mainName = item.mainCategoryName || getCurrentSeamlessSubtabName() || 'Seamless';
                const aggKey = mainName + '_' + sName;
                if (!fundAgg[aggKey]) fundAgg[aggKey] = { code: '1', main: mainName, subCode: '-', subName: sName, amt: 0, claimAmt: 0 };
                fundAgg[aggKey].amt += item.subFundAmount;
            } else {
                const map = { CKD: item.ckd_amt, KTP: item.ktp_amt, NAPP: item.napp_amt, TB: item.tb_amt, 'คัดกรองดาวน์': item.downs_amt, 'คัดกรองธาลัสซีเมีย': item.thal_screen_amt, 'คัดกรองมะเร็งปากมดลูก': item.cervical_amt, 'จิตเวช': item.psych_amt, 'ธาลัสซีเมีย': item.thal_disease_amt, 'ฟื้นฟู': item.rehab_amt, 'แพทย์แผนไทย': item.thai_med_amt, 'หมอพร้อม': item.morprom_amt, 'ฝากครรภ์': item.anc_amt, 'เบาหวาน/ความดัน': item.ncd_amt };
                for (const [k, v] of Object.entries(map)) {
                    if (v > 0) {
                        let sName = item.subFundName || '-';
                        const aggKey = k + '_' + sName;
                        if (!fundAgg[aggKey]) fundAgg[aggKey] = { code: '1', main: k, subCode: '-', subName: sName, amt: 0, claimAmt: 0 };
                        fundAgg[aggKey].amt += v;
                    }
                }
            }
        } else {
            if (!fundAgg[item.fund]) {
                let code = '1';
                if (item.fund === 'smartmoney') code = '2';
                
                let sCode = item.fund.toUpperCase();
                let sName = shortFund;
                if (item.fund.includes('||')) {
                    const parts = item.fund.split('||');
                    sCode = parts[0];
                    sName = parts[1];
                }
                
                fundAgg[item.fund] = { code: code, main: sName, subCode: sCode, subName: sName, amt: 0, claimAmt: 0 };
            }
            fundAgg[item.fund].amt += item.approvedAmount;
            fundAgg[item.fund].claimAmt += item.claimAmount;
        }
    });

    Object.values(fundAgg).forEach(sf => {
        if (sf.amt > 0 || sf.claimAmt > 0) {
            subFunds.push(sf);
            grandTotal += sf.amt;
            grandClaim += sf.claimAmt;
        }
    });

    subFunds.forEach(sf => {
        if (isIpOp) {
            tbody.innerHTML += `
                <tr>
                    <td>${sf.code}</td>
                    <td>${mapMainFundName(sf.main)}</td>
                    <td>${mapSubFundName(sf.subName)}</td>
                    <td style="text-align: right; font-weight: 500;">${formatMoney(sf.claimAmt)}</td>
                    <td style="text-align: right;" class="text-success">${formatMoney(sf.amt)}</td>
                </tr>
            `;
        } else if (isSmart) {
            tbody.innerHTML += `
                <tr>
                    <td>${mapMainFundName(sf.main)}</td>
                    <td>${mapSubFundName(sf.subName)}</td>
                    <td style="text-align: right;">${formatMoney(sf.amt)}</td>
                </tr>
            `;
        } else {
            tbody.innerHTML += `
                <tr>
                    <td>${sf.code}</td>
                    <td>${mapMainFundName(sf.main)}</td>
                    <td>${sf.subCode}</td>
                    <td>${mapSubFundName(sf.subName)}</td>
                    <td style="text-align: right;">${formatMoney(sf.amt)}</td>
                </tr>
            `;
        }
    });

    if (isIpOp) {
        tbody.innerHTML += `
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td colspan="3" style="text-align: right;">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</td>
                <td style="text-align: right; color: #0284c7;">${formatMoney(grandClaim)}</td>
                <td style="text-align: right; color: #10b981;">${formatMoney(grandTotal)}</td>
            </tr>
        `;
    } else {
        const colspanVal = isSmart ? 2 : 4;
        tbody.innerHTML += `
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td colspan="${colspanVal}" style="text-align: right;">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</td>
                <td style="text-align: right; color: #10b981;">${formatMoney(grandTotal)}</td>
            </tr>
        `;
    }
}

function updateTable(filteredData) {
    const tableTitle = document.getElementById('table-fund-title');
    if (tableTitle) {
        tableTitle.textContent = activeTab === 'overview' ? 'ภาพรวมยอดเงินแยกตามกองทุน' : 'ยอดเงินของกองทุน: ' + (FUNDS[activeTab] || activeTab);
    }
    const table = document.getElementById('compensation-table');
    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    const searchVal = document.getElementById('table-search-input').value.toLowerCase();

    tbody.innerHTML = '';

    if (activeTab === 'ppfs_sub') {
        thead.innerHTML = `
            <tr>
                <th style="text-align: center;">เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเนเธเธ</th>
                <th style="text-align: left;">เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธ</th>
                <th style="text-align: right;">เนเธเธเนเธเธเนเธเธเธขเธเนเธเธเนเธเธ</th>
                <th style="text-align: right;">เนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th style="text-align: right;">เนเธเธเนเธเธเนเธเธเนเธเธ (เนเธเธเธฒเธ—)</th>
            </tr>
        `;

        const hospAgg = {};
        filteredData.forEach(item => {
            if (searchVal && !item.hospital.toLowerCase().includes(searchVal)) return;
            const hospCode = item.hospital.includes(' - ') ? item.hospital.split(' - ')[0] : '-';
            const hospName = item.hospital.includes(' - ') ? item.hospital.split(' - ')[1] : item.hospital;
            if (!hospAgg[hospCode]) {
                hospAgg[hospCode] = { code: hospCode, name: hospName, claim: 0, count: 0, approve: 0 };
            }
            hospAgg[hospCode].claim += item.claimAmount || 0;
            hospAgg[hospCode].approve += item.approvedAmount || 0;
            hospAgg[hospCode].count += 1; 
        });

        const sortedData = Object.values(hospAgg).sort((a, b) => b.approve - a.approve);
        sortedData.forEach(item => {
            tbody.innerHTML += `
                <tr>
                    <td class="text-center font-bold" style="color:var(--text-secondary); text-align: center;">${item.code}</td>
                    <td style="text-align: left;"><strong>${item.name}</strong></td>
                    <td class="numeric text-warning" style="text-align: right;">${formatCurrency(item.claim)}</td>
                    <td class="numeric" style="text-align: right;">${item.count.toLocaleString()}</td>
                    <td class="numeric-bold text-blue" style="text-align: right;">${formatCurrency(item.approve)}</td>
                </tr>
            `;
        });
    } else if (activeTab === 'seamless') {
        thead.innerHTML = `
            <tr>
                <th>เธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเธตเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเนเธเธเธญเธ</th>
                <th>CKD</th>
                <th>KTP</th>
                <th>NAPP</th>
                <th>TB</th>
                <th>เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเธงเธเนเธเธ</th>
                <th>เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเธชเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเธนเธ</th>
                <th>เนเธเธเธดเธ•เนเธเธเธงเธ</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเธชเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเนเธเธเนเธเธเธญเธเนเธเธเนเธเธเธฑเธ•เธดเธเนเธเธเนเธเธเนเธเธ เนเธเธเนเธเธ)</th>
            </tr>
        `;

        const sortedData = [...filteredData].sort((a, b) => b.approvedAmount - a.approvedAmount);
        sortedData.forEach(item => {
            if (searchVal && !item.hospital.toLowerCase().includes(searchVal)) return;

            const hospName = item.hospital.includes(' - ') ? item.hospital.split(' - ')[1] : item.hospital;
            tbody.innerHTML += `
                <tr>
                    <td><strong>${hospName}</strong></td>
                    <td style="max-width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${item.fileName}">${item.fileName || '-'}</td>
                    <td class="numeric">${item.fiscalYear}</td>
                    <td>${getThaiMonthNameFromFiscalMonth(item.month, item.fiscalYear)}</td>
                    <td class="numeric text-success">${formatCurrency(item.ckd_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.ktp_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.napp_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.tb_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.downs_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.thal_screen_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.cervical_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.psych_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.thal_disease_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.rehab_amt || 0)}</td>
                    <td class="numeric text-success">${formatCurrency(item.morprom_amt || 0)}</td>
                    <td class="numeric-bold text-blue">${formatCurrency(item.approvedAmount || 0)}</td>
                </tr>
            `;
        });
    } else if (activeTab === 'overview') {
        thead.innerHTML = `
            <tr>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธญเธเนเธเธเธธเธเนเธเธเนเธเธเนเธเธเนเธเธ เธชเธเธชเธ.</th>
                <th>เนเธเธเธณเธเธงเธเนเธเธเธณเธเธญเธ—เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเธญเธ”เนเธเธเนเธเธเนเธเธเธขเธเนเธเธเนเธเธเธดเธ (เนเธเธ)</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเธฑเธ•เธดเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธ (เนเธเธ)</th>
                <th>เนเธเธเธดเธ” C / เนเธเธเธญเธ•เนเธเธเธงเธเนเธเธเธญเธ (เนเธเธ)</th>
                <th>เนเธเธเธฑเธ•เนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฑเธ•เนเธเธ</th>
            </tr>
        `;

        const aggregations = {};
        Object.keys(FUNDS).forEach(fundKey => {
            aggregations[fundKey] = { name: FUNDS[fundKey], count: 0, claim: 0, approve: 0, pending: 0 };
        });

        filteredData.forEach(item => {
            let aggKey = item.fund;
            if (!aggregations[aggKey]) {
                let dispName = aggKey;
                if (aggKey.includes('||')) {
                    dispName = aggKey.split('||')[1];
                }
                aggregations[aggKey] = { name: dispName, count: 0, claim: 0, approve: 0, pending: 0 };
            }
            aggregations[aggKey].count += 1;
            aggregations[aggKey].claim += item.claimAmount;
            aggregations[aggKey].approve += item.approvedAmount;
            aggregations[aggKey].pending += item.pendingAmount;
        });

        Object.keys(aggregations).forEach(key => {
            const agg = aggregations[key];
            if (searchVal && !agg.name.toLowerCase().includes(searchVal)) return;

            const successRate = agg.claim > 0 ? (agg.approve / agg.claim) * 100 : 0;
            const badgeClass = successRate > 90 ? 'badge-green' : (successRate > 80 ? 'badge-blue' : 'badge-warning');

            tbody.innerHTML += `
                <tr>
                    <td><strong>${agg.name}</strong></td>
                    <td class="numeric">${agg.count.toLocaleString()} เนเธเธเนเธเธเธขเธเนเธเธเนเธเธ</td>
                    <td class="numeric-bold">${formatCurrency(agg.claim)}</td>
                    <td class="numeric text-success">${formatCurrency(agg.approve)}</td>
                    <td class="numeric text-warning">${formatCurrency(agg.pending)}</td>
                    <td><span class="badge ${badgeClass}">${formatPercent(successRate)}</span></td>
                </tr>
            `;
        });
    } else if (activeTab === 'ip' || activeTab === 'op') {
        thead.innerHTML = `
            <tr>
                <th>เธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเธฃเธ“เนเธเธเนเธเธเนเธเธ</th>
                <th>REP No.</th>
                <th>เนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“</th>
                <th>เนเธเธเนเธเธเธงเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเธญเธ”เนเธเธเนเธเธเนเธเธเธดเธเนเธเธเนเธเธเนเธเธเนเธเธ (เนเธเธ)</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเธฑเธ•เธดเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธ (เนเธเธ)</th>
                <th>เนเธเธเนเธเธเธงเธเนเธเธเนเธเธเธฒเธ เนเธเธเนเธเธ)</th>
            </tr>
        `;

        const sortedData = [...filteredData].sort((a, b) => b.claimAmount - a.claimAmount);
        sortedData.forEach(item => {
            if (searchVal && !item.hospital.toLowerCase().includes(searchVal)) return;

            const diff = (item.claimAmount || 0) - (item.approvedAmount || 0);

            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.hospital}</strong></td>
                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${item.fileName || '-'}">
                        ${item.fileName || '-'}
                    </td>
                    <td>${item.fiscalYear || '2569'}</td>
                    <td>${getThaiMonthNameFromFiscalMonth(item.month, item.fiscalYear)}</td>
                    <td class="numeric"><strong>${formatCurrency(item.claimAmount)}</strong></td>
                    <td class="numeric-bold text-blue">${formatCurrency(item.approvedAmount)}</td>
                    <td class="numeric text-warning">${formatCurrency(diff)}</td>
                </tr>
            `;
        });
    } else if (activeTab === 'smartmoney') {
        thead.innerHTML = `
            <tr>
                <th>เธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเธฃเธ“เนเธเธเธธเธ</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“</th>
                <th>เนเธเธเธญเธ”เนเธเธเนเธเธเนเธเธเธดเธเนเธเธเนเธเธเนเธเธเนเธเธ เนเธเธเนเธเธ)</th>
                <th>เธญเธเนเธเธเนเธเธเธฑเธ•เธดเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธ เนเธเธเนเธเธ)</th>
                <th style="width: 60px; text-align: center;">เนเธเธเธฑเธ”เนเธเธเนเธเธเนเธเธ</th>
            </tr>
        `;

        const smartMoneyAgg = {};
        filteredData.forEach(item => {
            if (searchVal) {
                const hospMatch = item.hospital.toLowerCase().includes(searchVal);
                if (!hospMatch) return;
            }
            const aggKey = `${item.hospital}||${item.fileName}||${item.fiscalYear}`;
            if (!smartMoneyAgg[aggKey]) {
                smartMoneyAgg[aggKey] = {
                    hospital: item.hospital,
                    fileName: item.fileName,
                    fiscalYear: item.fiscalYear,
                    claimAmount: 0,
                    approvedAmount: 0
                };
            }
            smartMoneyAgg[aggKey].claimAmount += item.claimAmount || 0;
            smartMoneyAgg[aggKey].approvedAmount += item.approvedAmount || 0;
        });

        const sortedData = Object.values(smartMoneyAgg).sort((a, b) => b.claimAmount - a.claimAmount);
        sortedData.forEach(item => {
            const fName = item.fileName || '';
            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.hospital}</strong></td>
                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${item.fileName || 'เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธ (เนเธเธเนเธเธเนเธเธเธญเธ)'}">
                        ${item.fileName || 'เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธ (เนเธเธเนเธเธเนเธเธเธญเธ)'}
                    </td>
                    <td class="numeric">${item.fiscalYear}</td>
                    <td class="numeric-bold">${formatCurrency(item.claimAmount)}</td>
                    <td class="numeric text-success">${formatCurrency(item.approvedAmount)}</td>
                    <td style="text-align: center;">
                        <button onclick="deleteFile('${fName}')" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px; transition: 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">เธฅเธ</button>
                    </td>
                </tr>
            `;
        });
    } else {
        thead.innerHTML = `
            <tr>
                <th>เธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเธฃเธ“เนเธเธเธธเธ</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“</th>
                <th>เนเธเธเนเธเธเธงเธเนเธเธเนเธเธเนเธเธเนเธเธ</th>
                <th>เนเธเธเธญเธ”เนเธเธเนเธเธเนเธเธเธดเธเนเธเธเนเธเธเนเธเธเนเธเธ (เนเธเธ)</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเธฑเธ•เธดเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธ (เนเธเธ)</th>
                <th>เนเธเธเธดเธ” C/Error (เนเธเธ)</th>
                <th>เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธดเธ”เนเธเธเนเธเธเธฒเธ”เนเธเธเนเธเธเธฑเธ (Top Error)</th>
            </tr>
        `;

        const sortedData = [...filteredData].sort((a, b) => b.claimAmount - a.claimAmount);
        sortedData.forEach(item => {
            if (searchVal) {
                const hospMatch = item.hospital.toLowerCase().includes(searchVal);
                if (!hospMatch) return;
            }

            let topErrString = "ข้อผิดพลาดข้อมูล";
            if (item.errors && item.errors.length > 0) {
                const sortedErrs = [...item.errors].sort((a, b) => b.count - a.count);
                const errParts = sortedErrs
                    .filter(err => err && err.code && Number(err.count || 0) > 0)
                    .map(err => `<span class="text-warning font-bold">${err.code}</span> (${Number(err.count || 0).toLocaleString()} รายการ)`);
                if (errParts.length > 0) {
                    topErrString = errParts.join('<br>');
                }
            }

            tbody.innerHTML += `
                <tr>
                    <td><strong>${item.hospital}</strong></td>
                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${item.fileName || 'เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธ (เนเธเธเนเธเธเนเธเธเธญเธ)'}">
                        ${item.fileName || 'เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธ (เนเธเธเนเธเธเนเธเธเธญเธ)'}
                    </td>
                    <td class="numeric">${item.fiscalYear}</td>
                    <td>${getThaiMonthNameFromFiscalMonth(item.month, item.fiscalYear)}</td>
                    <td class="numeric-bold">${formatCurrency(item.claimAmount)}</td>
                    <td class="numeric text-success">${formatCurrency(item.approvedAmount)}</td>
                    <td class="numeric text-warning">${formatCurrency(item.pendingAmount)}</td>
                    <td>${topErrString}</td>
                </tr>
            `;
        });
    }

    // Add Grand Total Row
    let grandHtml = '';
    if (activeTab === 'ppfs_sub') {
        grandHtml = '';
    } else if (activeTab === 'seamless') {
        let sCkd=0, sKtp=0, sNapp=0, sTb=0, sDowns=0, sThalS=0, sCerv=0, sPsych=0, sThalD=0, sRehab=0, sMor=0, sAnc=0, sumApprove=0;
        filteredData.forEach(item => {
            sCkd+=(item.ckd_amt||0); sKtp+=(item.ktp_amt||0); sNapp+=(item.napp_amt||0);
            sTb+=(item.tb_amt||0); sDowns+=(item.downs_amt||0); sThalS+=(item.thal_screen_amt||0);
            sCerv+=(item.cervical_amt||0); sPsych+=(item.psych_amt||0); sThalD+=(item.thal_disease_amt||0);
            sRehab+=(item.rehab_amt||0); sMor+=(item.morprom_amt||0); sAnc+=(item.anc_amt||0); sumApprove+=(item.approvedAmount||0);
        });
        grandHtml = `
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td colspan="4" style="text-align: right;">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</td>
                <td class="numeric text-success">${formatCurrency(sCkd)}</td>
                <td class="numeric text-success">${formatCurrency(sKtp)}</td>
                <td class="numeric text-success">${formatCurrency(sNapp)}</td>
                <td class="numeric text-success">${formatCurrency(sTb)}</td>
                <td class="numeric text-success">${formatCurrency(sDowns)}</td>
                <td class="numeric text-success">${formatCurrency(sThalS)}</td>
                <td class="numeric text-success">${formatCurrency(sCerv)}</td>
                <td class="numeric text-success">${formatCurrency(sPsych)}</td>
                <td class="numeric text-success">${formatCurrency(sThalD)}</td>
                <td class="numeric text-success">${formatCurrency(sRehab)}</td>
                <td class="numeric text-success">${formatCurrency(sMor)}</td>
                <td class="numeric-bold text-blue">${formatCurrency(sumApprove)}</td>
            </tr>
        `;
    } else if (activeTab === 'overview') {
        let sumCount=0, sumClaim=0, sumApprove=0, sumPending=0;
        filteredData.forEach(item => {
            sumCount++; sumClaim+=item.claimAmount; sumApprove+=item.approvedAmount; sumPending+=item.pendingAmount;
        });
        const successRate = sumClaim > 0 ? (sumApprove / sumClaim) * 100 : 0;
        grandHtml = `
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td style="text-align: right;">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</td>
                <td class="numeric">${sumCount.toLocaleString()} เนเธเธเนเธเธเธขเธเนเธเธเนเธเธ</td>
                <td class="numeric-bold">${formatCurrency(sumClaim)}</td>
                <td class="numeric text-success">${formatCurrency(sumApprove)}</td>
                <td class="numeric text-warning">${formatCurrency(sumPending)}</td>
                <td><span class="badge badge-green">${formatPercent(successRate)}</span></td>
            </tr>
        `;
    } else if (activeTab === 'smartmoney') {
        let sumClaim=0, sumApprove=0;
        filteredData.forEach(item => {
            sumClaim+=item.claimAmount; sumApprove+=item.approvedAmount;
        });
        grandHtml = `
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td colspan="3" style="text-align: right;">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</td>
                <td class="numeric-bold">${formatCurrency(sumClaim)}</td>
                <td class="numeric text-success">${formatCurrency(sumApprove)}</td>
            </tr>
        `;
    } else {
        let sumClaim=0, sumApprove=0, sumPending=0;
        filteredData.forEach(item => {
            sumClaim+=item.claimAmount; sumApprove+=item.approvedAmount; sumPending+=item.pendingAmount;
        });
        grandHtml = `
            <tr style="font-weight: bold; background-color: #f8fafc;">
                <td colspan="4" style="text-align: right;">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ</td>
                <td class="numeric-bold">${formatCurrency(sumClaim)}</td>
                <td class="numeric text-success">${formatCurrency(sumApprove)}</td>
                <td class="numeric text-warning">${formatCurrency(sumPending)}</td>
                <td></td>
            </tr>
        `;
    }
    
    if (filteredData.length > 0) {
        tbody.innerHTML += grandHtml;
    }

    applyReadableTableLabels(activeTab);

}

function applyReadableTableLabels(tabName = activeTab) {
    const table = document.getElementById('compensation-table');
    if (!table) return;

    const thead = table.querySelector('thead');
    const headers = thead ? Array.from(thead.querySelectorAll('th')) : [];

    const setHeaders = (labels) => {
        labels.forEach((label, index) => {
            if (headers[index]) headers[index].textContent = label;
        });
    };

    if (tabName === 'ppfs_sub') {
        setHeaders(['รหัส', 'ชื่อหน่วยบริการ', 'ยอดขอเบิก', 'จำนวนราย', 'ยอดอนุมัติจ่ายจริง (บาท)']);
    } else if (tabName === 'seamless') {
        setHeaders([
            'ชื่อหน่วยบริการ', 'ชื่อไฟล์', 'ปีงบประมาณ', 'เดือน',
            'CKD', 'KTP', 'NAPP', 'TB',
            'คัดกรองดาวน์', 'คัดกรองธาลัสซีเมีย', 'คัดกรองมะเร็งปากมดลูก',
            'จิตเวช', 'ธาลัสซีเมีย', 'ฝากครรภ์', 'ฟื้นฟู',
            'หมอพร้อม', 'ยอดอนุมัติจ่ายจริง (บาท)'
        ]);
    } else if (tabName === 'overview') {
        setHeaders([
            'ชื่อกองทุน', 'จำนวนรายการ', 'ยอดขอเบิก (บาท)', 'ยอดอนุมัติจ่ายจริง (บาท)',
            'ยอด C / รอจ่าย (บาท)', 'อัตราการอนุมัติ (%)'
        ]);
    } else if (tabName === 'smartmoney') {
        setHeaders([
            'ชื่อหน่วยบริการ', 'ชื่อไฟล์', 'ปีงบประมาณ', 'ยอดขอเบิก (บาท)', 'ยอดอนุมัติจ่ายจริง (บาท)'
        ]);
    } else if (tabName === 'op' || tabName === 'ip') {
        setHeaders([
            'ชื่อหน่วยบริการ', 'REP No.', 'ปีงบประมาณ', 'เดือน',
            'ยอดขอเบิก (บาท)', 'ยอดอนุมัติจ่ายจริง (บาท)', 'ยอด C (บาท)'
        ]);
    }

    const tbody = table.querySelector('tbody');
    const lastRow = tbody ? tbody.querySelector('tr:last-child') : null;
    if (lastRow) {
        const firstCell = lastRow.querySelector('td');
        if (firstCell) {
            firstCell.textContent = 'รวมทั้งหมด';
        }
    }
}

function getActiveTabItems(tabName = activeTab) {
    return database.filter(item => {
        if (tabName === 'overview') return true;
        if (tabName === 'op') return (item.fund === 'OPBKK' || item.fund === 'op' || item.baseFund === 'OPBKK');
        if (tabName === 'ip') return (item.fund === 'IPBKK' || item.fund === 'ip' || item.baseFund === 'IPBKK');
        if (tabName === 'ppfs') {
            const fundValue = String(item.fund || '').toLowerCase();
            const baseFundValue = String(item.baseFund || '').toLowerCase();
            return (
                ['pp', 'ppfs', 'ppfs_sub', 'opae', 'opfs'].includes(fundValue) ||
                ['pp', 'ppfs', 'ppfs_sub', 'opae', 'opfs'].includes(baseFundValue)
            );
        }
        return (item.baseFund || item.fund) === tabName;
    });
}

function getPreferredYearForActiveTab(tabName = activeTab) {
    const activeItems = getActiveTabItems(tabName);
    const yearStats = {};

    activeItems.forEach(item => {
        const year = String(item.fiscalYear);
        if (!year || year === 'undefined' || year === 'null') return;
        if (!yearStats[year]) {
            yearStats[year] = { total: 0, count: 0 };
        }
        yearStats[year].total += Number(item.claimAmount || 0) + Number(item.approvedAmount || 0) + Number(item.pendingAmount || 0);
        yearStats[year].count += 1;
    });

    const sortedYears = Object.keys(yearStats).sort((a, b) => parseInt(b) - parseInt(a));
    const yearsWithValue = sortedYears.filter(year => yearStats[year].total > 0);
    return yearsWithValue[0] || sortedYears[0] || null;
}

function sortFiscalYears(values, direction = 'asc') {
    return [...new Set(values)]
        .filter(yr => yr != null && yr !== '' && yr !== 'undefined' && yr !== 'null')
        .sort((a, b) => {
            const aNum = parseInt(a, 10);
            const bNum = parseInt(b, 10);
            if (Number.isNaN(aNum) && Number.isNaN(bNum)) return String(a).localeCompare(String(b));
            if (Number.isNaN(aNum)) return direction === 'asc' ? 1 : -1;
            if (Number.isNaN(bNum)) return direction === 'asc' ? -1 : 1;
            return direction === 'asc' ? aNum - bNum : bNum - aNum;
        });
}

function sortYearMonthKeys(values, direction = 'asc') {
    return [...new Set(values)]
        .filter(key => key != null && key !== '' && key !== 'undefined' && key !== 'null')
        .sort((a, b) => {
            const [aYear, aMonth] = String(a).split('-').map(v => parseInt(v, 10));
            const [bYear, bMonth] = String(b).split('-').map(v => parseInt(v, 10));
            const aValid = Number.isFinite(aYear) && Number.isFinite(aMonth);
            const bValid = Number.isFinite(bYear) && Number.isFinite(bMonth);
            if (!aValid && !bValid) return String(a).localeCompare(String(b));
            if (!aValid) return direction === 'asc' ? 1 : -1;
            if (!bValid) return direction === 'asc' ? -1 : 1;
            const aValue = aYear * 12 + aMonth;
            const bValue = bYear * 12 + bMonth;
            return direction === 'asc' ? aValue - bValue : bValue - aValue;
        });
}

function splitHospitalDisplay(hospitalValue) {
    const raw = String(hospitalValue || '').trim();
    if (!raw) return { code: 'UNKNOWN', name: '' };

    if (raw.includes(' - ')) {
        const parts = raw.split(' - ');
        const code = (parts.shift() || '').trim() || 'UNKNOWN';
        const name = parts.join(' - ').trim();
        return { code, name };
    }

    const match = raw.match(/^(\d{4,5})\s*(.*)$/);
    if (match) {
        return {
            code: match[1].trim() || 'UNKNOWN',
            name: (match[2] || '').trim()
        };
    }

    return { code: raw, name: '' };
}

function normalizeHospitalCode(code, fallbackCode = 'UNKNOWN') {
    const raw = String(code || '').trim();
    const match = raw.match(/^(\d{4,5})$/) || raw.match(/^(\d{4,5})\b/);
    if (match) {
        return match[1].length === 4 ? `0${match[1]}` : match[1];
    }
    return String(fallbackCode || 'UNKNOWN').trim() || 'UNKNOWN';
}

function getAllFiscalYears() {
    const dbYears = getPlausibleFiscalYears(database.map(item => item.fiscalYear));
    const managedYearsStr = getPlausibleFiscalYears(managedYears);
    return sortFiscalYears([...new Set([...dbYears, ...managedYearsStr])], 'asc');
}

function getFiscalYearRangeText(years = []) {
    const cleanYears = years
        .map(yr => String(yr))
        .filter(yr => yr && yr !== 'undefined' && yr !== 'null');
    if (cleanYears.length === 0) return 'ทุกปีงบประมาณ';
    const sortedYears = sortFiscalYears([...new Set(cleanYears)], 'asc');
    if (sortedYears.length === 1) return `ปีงบประมาณ ${sortedYears[0]}`;
    return `เนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“ ${sortedYears[0]} - ${sortedYears[sortedYears.length - 1]}`;
}

function updateYearDropdown() {
    const yearSelect = document.getElementById('year-select');
    if (!yearSelect) return;
    
    const currentSelected = yearSelect.value;
    const activeItems = getActiveTabItems();
    const activeYears = activeItems
        .map(item => String(item.fiscalYear))
        .filter(yr => yr && yr !== 'undefined' && yr !== 'null');

    // Extract unique fiscal years from database and merge with manually managed years
    const dbYears = getPlausibleFiscalYears(database.map(item => item.fiscalYear));
    const dbUniqueYears = sortFiscalYears(dbYears, 'desc');
    const activeUniqueYears = sortFiscalYears(activeYears, 'desc');
    const uniqueYears = getAllFiscalYears().sort((a, b) => Number(b) - Number(a));
    const preferredYear = getPreferredYearForActiveTab();
    const selectedYearHasValue = currentSelected && yearSelect.value && currentSelected !== 'all'
        ? activeItems
            .filter(item => String(item.fiscalYear) === String(currentSelected))
            .reduce((sum, item) => sum + Number(item.claimAmount || 0) + Number(item.approvedAmount || 0) + Number(item.pendingAmount || 0), 0)
        : null;
    
    // Clear and rebuild options
    yearSelect.innerHTML = '<option value="all">ทุกปีงบประมาณ</option>';
    
    uniqueYears.forEach(yr => {
        const option = document.createElement('option');
        option.value = yr;
        option.textContent = yr;
        yearSelect.appendChild(option);
    });
    
    // Restore previous selection, or select the newest year by default
    if (currentSelected === 'all') {
        yearSelect.value = 'all';
    } else if (preferredYear && (selectedYearHasValue === 0 || selectedYearHasValue == null) && preferredYear !== currentSelected) {
        yearSelect.value = preferredYear;
    } else if (activeUniqueYears.length > 0 && !activeUniqueYears.includes(currentSelected)) {
        yearSelect.value = preferredYear || activeUniqueYears[0];
    } else if (dbUniqueYears.length > 0 && !dbUniqueYears.includes(currentSelected)) {
        yearSelect.value = preferredYear || dbUniqueYears[0];
    } else if (uniqueYears.includes(currentSelected)) {
        yearSelect.value = currentSelected;
    } else if (uniqueYears.length > 0) {
        yearSelect.value = preferredYear || uniqueYears[0];
    } else {
        yearSelect.value = 'all';
    }
}

// Refresh whole dashboard UI
function refreshDashboard() {
    // Keep dropdown options matching the database years
    updateYearDropdown();
    updateDashboardFileSelect();
    renderUploadHistorySelect();

    const filteredData = getFilteredData();
    
    if (typeof updateSubmenuDashboard === 'function') updateSubmenuDashboard(filteredData);
    
    updateKPICards(filteredData);
    updateTopErrorsWidget();
    updateCharts(filteredData);
    updateTable(filteredData);
    updateSummaryTable(filteredData);
    if (typeof updateOpIpDetails === 'function') updateOpIpDetails(filteredData);
    if (document.getElementById('year-audit-tbody')) renderYearAudit();
}

function updateSubmenuDashboard(filteredData) {
    const compareChartCard = document.getElementById('compare-chart-card');
    const categoryGridCard = document.getElementById('category-grid-card');
    const hospBreakdownCard = document.getElementById('hospital-breakdown-card');
    const focusModeContainer = document.getElementById('seamless-focus-mode-container');
    const mainChartsGrid = document.querySelector('.charts-grid');
    const mainTable = document.querySelector('section.table-container');
    const ipCmiCard = document.getElementById('ip-cmi-chart-card');
    const ppfsCustomContainer = document.getElementById('ppfs-custom-container');
    const standardKpis = document.getElementById('standard-kpis');
    const ppfsSubKpis = document.getElementById('ppfs-sub-kpis');
    const summaryTableSec = document.querySelector('#summary-table') ? document.querySelector('#summary-table').closest('section.table-container') : null;
    
    if (ipCmiCard) ipCmiCard.style.display = 'none';
    if (ppfsCustomContainer) ppfsCustomContainer.style.display = 'none';
    if (standardKpis) standardKpis.style.display = 'grid';
    if (ppfsSubKpis) ppfsSubKpis.style.display = 'none';
    
    if (!compareChartCard || !categoryGridCard) return;

    if (activeTab === 'ppfs') {
        if (mainTable) mainTable.style.display = 'none';
        if (mainChartsGrid) mainChartsGrid.style.display = 'none';
        if (standardKpis) standardKpis.style.display = 'none';
        if (summaryTableSec) summaryTableSec.style.display = 'none';
        if (focusModeContainer) focusModeContainer.style.display = 'none';
        if (compareChartCard) compareChartCard.style.display = 'none';
        if (categoryGridCard) categoryGridCard.style.display = 'none';
        if (hospBreakdownCard) hospBreakdownCard.style.display = 'none';
        
        if (ppfsCustomContainer) {
            ppfsCustomContainer.style.display = 'flex';
            updatePpfsCustomDashboard(filteredData);
        }
    } else if (activeTab === 'seamless') {
        if (mainTable) mainTable.style.display = 'none'; // Hide the old main table

        if (!getCurrentSeamlessSubtab()) {
            mainChartsGrid.style.display = 'grid';
            if(focusModeContainer) focusModeContainer.style.display = 'none';
            
            compareChartCard.style.display = 'none';
            if(hospBreakdownCard) hospBreakdownCard.style.display = 'none';
            categoryGridCard.style.display = 'block';
            syncSeamlessMenuState(null, null, filteredData);
            
            const categories = [
                { id: 'ckd', name: 'CKD', icon: '🩺', color: '#3b82f6', bg: '#eff6ff' },
                { id: 'ktp', name: 'KTP', icon: '🏥', color: '#10b981', bg: '#ecfdf5' },
                { id: 'napp', name: 'NAPP', icon: '📋', color: '#f59e0b', bg: '#fffbeb' },
                { id: 'tb', name: 'TB', icon: '🫁', color: '#ef4444', bg: '#fef2f2' },
                { id: 'downs', name: 'คัดกรองดาวน์', icon: '🎗️', color: '#8b5cf6', bg: '#f5f3ff' },
                { id: 'thal_screen', name: 'คัดกรองธาลัสซีเมีย', icon: '🧬', color: '#ec4899', bg: '#fdf2f8' },
                { id: 'cervical', name: 'คัดกรองมะเร็งปากมดลูก', icon: '🧪', color: '#14b8a6', bg: '#f0fdfa' },
                { id: 'psych', name: 'จิตเวช', icon: '🧠', color: '#6366f1', bg: '#eef2ff' },
                { id: 'thal_disease', name: 'ธาลัสซีเมีย', icon: '💉', color: '#f43f5e', bg: '#fff1f2' },
                { id: 'anc', name: 'ฝากครรภ์', icon: '🤰', color: '#0ea5e9', bg: '#f0f9ff' },
                { id: 'rehab', name: 'ฟื้นฟู', icon: '♿', color: '#84cc16', bg: '#f7fee7' },
                { id: 'thai_med', name: 'แพทย์แผนไทย', icon: '🌿', color: '#0ea5e9', bg: '#ecfeff' },
                { id: 'ncd', name: 'เบาหวาน/ความดัน', icon: '❤️', color: '#f97316', bg: '#fff7ed' },
                { id: 'morprom', name: 'หมอพร้อม', icon: '👶', color: '#06b6d4', bg: '#ecfeff' }
            ];
            
            let gridHtml = '';
            categories.forEach(cat => {
                let total = 0;
                let prop = cat.id + '_amt';
                
                filteredData.forEach(g => {
                    if (g.fund === 'seamless' && getRecordMenuKey(g) === cat.id) {
                        total += (g[prop] || 0);
                    }
                });
                
                gridHtml += `<div style="background: ${cat.bg}; border: 1px solid ${cat.color}33; border-radius: 8px; padding: 12px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s ease;"
                         onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 6px -1px rgba(0,0,0,0.1)'"
                         onmouseout="this.style.transform='none'; this.style.boxShadow='none'"
                         onclick="window.selectSeamlessSubtab && window.selectSeamlessSubtab('${cat.id}')">
                        <div style="font-size: 1.5rem;">${cat.icon}</div>
                        <div>
                            <div style="font-size: 0.8rem; color: #64748b; margin-bottom: 2px;">${cat.name}</div>
                            <div style="font-size: 1.05rem; font-weight: 600; color: ${cat.color};"เนเธเธเนเธเธ ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                        </div>
                    </div>`;
            });
            document.getElementById('category-grid-container').innerHTML = gridHtml;
        } else {
            mainChartsGrid.style.display = 'none';
            if(focusModeContainer) focusModeContainer.style.display = 'block';
            
            const subtab = getCurrentSeamlessSubtab();
            const prop = subtab + '_amt';
            
            document.getElementById('focus-hospital-table-title').textContent = `${getCurrentSeamlessSubtabName()} แยกตามหน่วยบริการ`;
            syncSeamlessMenuState(subtab, getCurrentSeamlessSubtabName(), filteredData);
            updateSeamlessFocusModeData(filteredData, subtab, prop);
        }
    } else {
        if (mainTable) mainTable.style.display = 'block'; // Show the old main table again
        updateMenuPageHeader(activeTab);
        mainChartsGrid.style.display = 'grid';
        if(focusModeContainer) focusModeContainer.style.display = 'none';
        compareChartCard.style.display = 'block';
        categoryGridCard.style.display = 'none';
        if(hospBreakdownCard) hospBreakdownCard.style.display = 'none';
        if (ipCmiCard) {
            ipCmiCard.style.display = (activeTab === 'ip') ? 'block' : 'none';
        }
    }
}

function syncSeamlessMenuState(subtab = getCurrentSeamlessSubtab(), subtabName = getCurrentSeamlessSubtabName(), filteredData = database) {
    const seamlessMenu = document.querySelector('.menu-item.has-submenu[data-tab="seamless"]');
    const accordion = seamlessMenu?.closest('.menu-accordion') || null;
    const submenuList = accordion ? accordion.querySelector('.submenu-list') : null;
    const hasSubmenuList = submenuList && submenuList.classList && submenuList.classList.contains('submenu-list');
    const banner = document.getElementById('seamless-current-subtab-banner');
    const bannerName = document.getElementById('seamless-current-subtab-name');
    const bannerCount = document.getElementById('seamless-current-subtab-count');
    const yearlyTitle = document.getElementById('focus-yearly-chart-title');
    const monthlyTitle = document.getElementById('focus-monthly-chart-title');
    const hospitalTitle = document.getElementById('focus-hospital-table-title');

    syncSeamlessSidebarVisibility();

    document.querySelectorAll('.submenu-item').forEach(btn => btn.classList.remove('active'));
    if (subtab) {
        const activeBtn = document.querySelector(`.submenu-item[data-subtab="${subtab}"]`);
        if (activeBtn) activeBtn.classList.add('active');
    }

    const activeLabel = subtabName || 'ทั้งหมด';
    const selectedCount = Array.isArray(filteredData)
        ? filteredData.reduce((sum, record) => {
            if (!record || record.fund !== 'seamless') return sum;
            if (!subtab) return sum + 1;
            return getRecordMenuKey(record) === subtab ? sum + 1 : sum;
        }, 0)
        : 0;

    if (banner && bannerName && bannerCount) {
        banner.style.display = subtab ? 'flex' : 'none';
        bannerName.textContent = subtab ? activeLabel : 'ภาพรวม Seamless';
        bannerCount.textContent = `${Number(selectedCount || 0).toLocaleString()} รายการ`;
    }

    if (yearlyTitle) {
        yearlyTitle.textContent = subtab
            ? `${activeLabel} - ภาพรวมรายปีงบประมาณ`
            : 'ภาพรวมรายปีงบประมาณ';
    }
    if (monthlyTitle) {
        monthlyTitle.textContent = subtab
            ? `${activeLabel} - ภาพรวมรายเดือน`
            : 'ภาพรวมรายเดือน';
    }
    if (hospitalTitle && subtab) {
        hospitalTitle.textContent = `${activeLabel} แยกตามหน่วยบริการ`;
    }

    if (seamlessMenu && submenuList) {
        setTimeout(() => {
            try {
                seamlessMenu.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } catch (err) {}
        }, 0);
    }
}

function selectSeamlessSubtab(subtab) {
    const btn = document.querySelector(`.submenu-item[data-subtab="${subtab}"]`);
    if (!btn) return;
    document.querySelectorAll('.submenu-item').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    updateMenuPageHeader('seamless');
    document.querySelectorAll('.sidebar-menu .menu-item').forEach(b => b.classList.remove('active'));
    const parent = btn.closest('.menu-accordion')?.querySelector('.has-submenu');
    if (parent) parent.classList.add('active');
    try { localStorage.setItem('nhso_current_seamless_subtab', subtab); } catch (e) {}
    window.currentSeamlessSubtab = subtab;
    const spans = btn.querySelectorAll('span');
    const subtabName = spans.length > 1 ? spans[1].innerText : spans[0].innerText;
    try { localStorage.setItem('nhso_current_seamless_subtab_name', subtabName); } catch (e) {}
    window.currentSeamlessSubtabName = subtabName;
    setSharedUiPreferenceLocal(SHARED_PREF_KEYS.seamlessSubtab, subtab);
    setSharedUiPreferenceLocal(SHARED_PREF_KEYS.seamlessSubtabName, subtabName);
    saveSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtab, subtab);
    saveSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtabName, subtabName);
    handleTabChange('seamless');
    syncSeamlessMenuState(subtab, subtabName, database);
    refreshDashboard();
}
window.selectSeamlessSubtab = selectSeamlessSubtab;

function updateSeamlessFocusModeData(filteredData, subtab, prop) {
    let yearlyData = {};
    const yearSelect = document.getElementById('year-select');
    const selectedFilterYr = yearSelect ? yearSelect.value : 'all';
    let baseYr = new Date().getFullYear() + 543;
    if (selectedFilterYr && selectedFilterYr !== 'all') baseYr = parseInt(selectedFilterYr);

    const seamlessYears = sortFiscalYears(
        filteredData
            .filter(g => g && g.fund === 'seamless' && Number(g[prop] || 0) > 0)
            .map(g => String(g.fiscalYear)),
        'asc'
    );
    const fallbackYears = sortFiscalYears(
        [selectedFilterYr, String(baseYr - 1), String(baseYr), String(baseYr + 1), ...getAllFiscalYears()],
        'asc'
    );
    const yLabels = seamlessYears.length > 0 ? seamlessYears : fallbackYears;
        
    yLabels.forEach(y => yearlyData[y] = { count: 0, amt: 0 });
    
    let monthlyData = {};
    let hospMap = {};
    
    const months = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
    months.forEach(m => monthlyData[m] = { count: 0, amt: 0 });

    filteredData.forEach(g => {
        if (g.fund === 'seamless' && getRecordMenuKey(g) === subtab) {
            const amt = g[prop] || 0;
            if (amt > 0) {
                const count = Math.ceil(amt / 1500); 
                const claimAmt = amt * 1.1; 
                
                if (yearlyData[g.fiscalYear]) {
                    yearlyData[g.fiscalYear].amt += amt;
                    yearlyData[g.fiscalYear].count += count;
                }
                
                if (monthlyData[g.month]) {
                    monthlyData[g.month].amt += amt;
                    monthlyData[g.month].count += count;
                }
                
                if (!hospMap[g.hospital]) {
                    hospMap[g.hospital] = { req: 0, count: 0, amt: 0, type: g.subFundName || getCurrentSeamlessSubtabName() };
                }
                hospMap[g.hospital].req += claimAmt;
                hospMap[g.hospital].count += count;
                hospMap[g.hospital].amt += amt;
            }
        }
    });

    // Yearly
    let yAmts = yLabels.map(y => yearlyData[y].amt);
    
    let yHtml = '';
    let yTotalCount = 0;
    let yTotalAmt = 0;
    yLabels.forEach(y => {
        yTotalCount += yearlyData[y].count;
        yTotalAmt += yearlyData[y].amt;
        yHtml += `<tr>
            <td>${y}</td>
            <td style="text-align: right;">${yearlyData[y].count.toLocaleString()}</td>
            <td style="text-align: right;">${yearlyData[y].amt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        </tr>`;
    });
    document.getElementById('focus-yearly-tbody').innerHTML = yHtml;
    document.getElementById('focus-yearly-total-count').textContent = yTotalCount.toLocaleString();
    document.getElementById('focus-yearly-total-amt').textContent = yTotalAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    if (focusYearlyChartInstance) focusYearlyChartInstance.destroy();
    const ctxY = document.getElementById('focusYearlyChart').getContext('2d');
    focusYearlyChartInstance = new Chart(ctxY, {
        type: 'bar',
        data: {
            labels: yLabels,
            datasets: [{
                label: 'เนเธเธเธญเธ”เนเธเธเนเธเธเนเธเธเนเธเธ (เนเธเธเธฒเธ—)',
                data: yAmts,
                backgroundColor: '#38bdf8',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Monthly
    const displayFiscalYear = selectedFilterYr !== 'all' ? selectedFilterYr : String(baseYr);
    let mLabels = months.map(m => getThaiMonthNameFromFiscalMonth(m, displayFiscalYear));
    let mAmts = months.map(m => monthlyData[m].amt);
    
    let mHtml = '';
    let mTotalCount = 0;
    let mTotalAmt = 0;
    months.forEach(m => {
        mTotalCount += monthlyData[m].count;
        mTotalAmt += monthlyData[m].amt;
        mHtml += `<tr>
            <td>${getThaiMonthNameFromFiscalMonth(m, displayFiscalYear)}</td>
            <td style="text-align: right;">${monthlyData[m].count.toLocaleString()}</td>
            <td style="text-align: right;">${monthlyData[m].amt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        </tr>`;
    });
    document.getElementById('focus-monthly-tbody').innerHTML = mHtml;
    document.getElementById('focus-monthly-total-count').textContent = mTotalCount.toLocaleString();
    document.getElementById('focus-monthly-total-amt').textContent = mTotalAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    if (focusMonthlyChartInstance) focusMonthlyChartInstance.destroy();
    const ctxM = document.getElementById('focusMonthlyChart').getContext('2d');
    focusMonthlyChartInstance = new Chart(ctxM, {
        type: 'bar',
        data: {
            labels: mLabels,
            datasets: [{
                label: 'เนเธเธเธญเธ”เนเธเธเนเธเธเนเธเธเนเธเธ (เนเธเธเธฒเธ—)',
                data: mAmts,
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Hospital
    const hospArray = Object.keys(hospMap).map(k => ({ hospital: k, ...hospMap[k] }));
    hospArray.sort((a, b) => b.amt - a.amt);
    
    let hHtml = '';
    let hTotalReq = 0;
    let hTotalCount = 0;
    let hTotalAmt = 0;
    
    if (hospArray.length === 0) {
        hHtml = '<tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 2rem;">ยังไม่มีข้อมูล</td></tr>';
    } else {
        hospArray.forEach((h, i) => {
            let hCode = '-';
            let hName = h.hospital;
            const m = h.hospital.match(/^(\d{5})/);
            if (m) {
                hCode = m[1];
                hName = h.hospital.replace(hCode, '').replace(/^[-\s]+/, '').trim();
            }
            hTotalReq += h.req;
            hTotalCount += h.count;
            hTotalAmt += h.amt;
            hHtml += `<tr>
                <td>${hCode}</td>
                <td>${hName}</td>

                <td style="text-align: right;">${h.req.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="text-align: right;">${h.count.toLocaleString()}</td>
                <td style="text-align: right; color: #0ea5e9; font-weight: 500;">${h.amt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>`;
        });
    }
    document.getElementById('focus-hospital-tbody').innerHTML = hHtml;
    document.getElementById('focus-hosp-total-req').textContent = hTotalReq.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('focus-hosp-total-count').textContent = hTotalCount.toLocaleString();
    document.getElementById('focus-hosp-total-amt').textContent = hTotalAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}


// Handle navigation tab clicks
function handleTabChange(tabName) {
    activeTab = tabName;
    setSharedUiPreferenceLocal(SHARED_PREF_KEYS.activeTab, tabName);
    saveSharedUiPreference(SHARED_PREF_KEYS.activeTab, tabName);
    updateMenuPageHeader(tabName);
    document.querySelectorAll('.sidebar-menu .menu-item').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
    });

    const dashboardTabContent = document.getElementById('dashboard-tab-content');
    const logHistoryTabContent = document.getElementById('log-history-tab-content');
    const yearAuditTabContent = document.getElementById('year-audit-tab-content');
    const syncTabContent = document.getElementById('google-sync-tab-content');
    const howToUseTabContent = document.getElementById('how-to-use-tab-content');
    const aboutTabContent = document.getElementById('about-tab-content');

    if (dashboardTabContent) dashboardTabContent.classList.remove('active');
    if (logHistoryTabContent) logHistoryTabContent.classList.remove('active');
    if (yearAuditTabContent) yearAuditTabContent.classList.remove('active');
    if (syncTabContent) syncTabContent.classList.remove('active');
    if (howToUseTabContent) howToUseTabContent.classList.remove('active');
    if (aboutTabContent) aboutTabContent.classList.remove('active');

    if (tabName === 'log-history') {
        if (logHistoryTabContent) logHistoryTabContent.classList.add('active');
        renderLogHistory();
    } else if (tabName === 'year-audit') {
        if (yearAuditTabContent) yearAuditTabContent.classList.add('active');
        renderYearAudit();
    } else if (tabName === 'google-sync') {
        if (syncTabContent) syncTabContent.classList.add('active');
        if (currentSession && currentSession.role === 'ADMIN') {
            loadUsersList();
            loadManagedYears();
        }
        renderUploadHistory();
    } else if (tabName === 'how-to-use') {
        if (howToUseTabContent) howToUseTabContent.classList.add('active');
    } else if (tabName === 'about') {
        if (aboutTabContent) aboutTabContent.classList.add('active');
    } else {
        if (dashboardTabContent) dashboardTabContent.classList.add('active');
        const importBtn = document.getElementById('btn-import-label');
        if (importBtn) {
            importBtn.style.display = 'flex';
        }
        document.getElementById('table-fund-title').textContent = 
            tabName === 'overview' ? 'ภาพรวมยอดเงินแยกตามกองทุน สปสช. ทั้งหมด' : `ยอดเงินของกองทุน: ${FUNDS[tabName]}`;
        const yearSelect = document.getElementById('year-select');
        if (yearSelect) {
            const activeYears = database
                .filter(item => {
                    if (tabName === 'overview') return true;
                    if (tabName === 'op') return (item.fund === 'OPBKK' || item.fund === 'op' || item.baseFund === 'OPBKK');
                    if (tabName === 'ip') return (item.fund === 'IPBKK' || item.fund === 'ip' || item.baseFund === 'IPBKK');
                    if (tabName === 'ppfs') return ['PP', 'OPAE', 'OPFS'].includes(item.baseFund || item.fund);
                    return (item.baseFund || item.fund) === tabName;
                })
                .map(item => String(item.fiscalYear))
                .filter(yr => yr && yr !== 'undefined' && yr !== 'null');
            const uniqueActiveYears = sortFiscalYears(activeYears, 'desc');
            if (uniqueActiveYears.length > 0 && !uniqueActiveYears.includes(yearSelect.value)) {
                yearSelect.value = uniqueActiveYears[0];
            }
        }
        refreshDashboard();
    }
}

// Toast notification helper
function showToast(message, isSuccess = true) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');
    const toastIcon = document.getElementById('toast-icon');

    toastMessage.textContent = message;
    toast.style.borderLeftColor = isSuccess ? 'var(--success-primary)' : 'var(--danger-primary)';
    toastIcon.setAttribute('data-lucide', isSuccess ? 'check-circle' : 'x-circle');
    toastIcon.style.color = isSuccess ? 'var(--success-primary)' : 'var(--danger-primary)';
    
    lucide.createIcons();
    
    toast.classList.remove('toast-hidden');
    setTimeout(() => { toast.classList.add('toast-hidden'); }, 4000);
}

// Intelligently deduce Fiscal Year and month index from Date object
function getFiscalDetails(date) {
    let year = date.getFullYear();
    let month = date.getMonth() + 1; // 1-12

    let fiscalYear = year;
    if (month >= 10) {
        fiscalYear = year + 1;
    }

    const fiscalYearBE = (fiscalYear + 543).toString();

    let fiscalMonthIndex = month - 9;
    if (month < 10) {
        fiscalMonthIndex = month + 3;
    }

    return {
        fiscalYear: fiscalYearBE,
        monthIndex: fiscalMonthIndex
    };
}

// Helper to parse Thai Buddhist Era dates or CE dates safely
function parseThaiDate(dateVal) {
    if (!dateVal) return new Date();
    if (dateVal instanceof Date) return dateVal;
    
    let dateStr = String(dateVal).trim();
    
    // Parse parts using separators: / or - or space or dot
    const parts = dateStr.split(/[\/\-\s.]+/);
    if (parts.length === 3) {
        let day, month, year;
        // Detect format: YYYY-MM-DD vs DD/MM/YYYY
        if (parts[0].length === 4) {
            year = parseInt(parts[0]);
            month = parseInt(parts[1]);
            day = parseInt(parts[2]);
        } else if (parts[2].length === 4) {
            day = parseInt(parts[0]);
            month = parseInt(parts[1]);
            year = parseInt(parts[2]);
        }
        
        if (year && month && day) {
            // Convert BE to CE
            if (year > 2400) {
                year -= 543;
            }
            const d = new Date(year, month - 1, day);
            if (!isNaN(d.getTime())) return d;
        }
    }
    
    // Default fallback
    const d = new Date(dateVal);
    return isNaN(d.getTime()) ? new Date() : d;
}

function parseThaiDateStrict(dateVal) {
    if (dateVal === undefined || dateVal === null || dateVal === '') return null;
    if (dateVal instanceof Date) {
        return isNaN(dateVal.getTime()) ? null : dateVal;
    }

    if (typeof dateVal === 'number' && isFinite(dateVal)) {
        if (dateVal > 59 && dateVal < 60000) {
            const excelDate = new Date(Date.UTC(1899, 11, 30) + (dateVal * 86400000));
            if (!isNaN(excelDate.getTime())) {
                return new Date(excelDate.getUTCFullYear(), excelDate.getUTCMonth(), excelDate.getUTCDate());
            }
        }
    }

    const dateStr = String(dateVal).trim();
    if (!dateStr) return null;

    const lowered = dateStr.toLowerCase().replace(/\s+/g, ' ').trim();
    const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    const thaiShortMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const monthTokens = thaiMonths.concat(thaiShortMonths);
    const escapeRegExp = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    for (let i = 0; i < monthTokens.length; i++) {
        const token = monthTokens[i];
        if (!lowered.includes(token)) continue;

        const monthIndex = (i % 12) + 1;
        const tokenEsc = escapeRegExp(token);
        const dayBefore = lowered.match(new RegExp(`(?:^|\\D)(\\d{1,2})\\s*${tokenEsc}`));
        const dayAfter = lowered.match(new RegExp(`${tokenEsc}\\s*(\\d{1,2})(?:\\D|$)`));
        const yearMatch = lowered.match(/(25\d{2}|24\d{2}|20\d{2})/);
        const shortYearMatches = lowered.match(/(?:^|\\D)(\\d{2})(?:\\D|$)/g);

        let day = 1;
        if (dayBefore) {
            day = parseInt(dayBefore[1], 10);
        } else if (dayAfter) {
            day = parseInt(dayAfter[1], 10);
        }

        let year = yearMatch ? parseInt(yearMatch[1], 10) : null;
        if (year === null && shortYearMatches && shortYearMatches.length > 0) {
            const shortYear = shortYearMatches[shortYearMatches.length - 1].match(/(\d{2})/);
            if (shortYear) year = parseInt(shortYear[1], 10);
        }
        if (year !== null) {
            if (year < 100) year += 2500;
            if (year > 2400) year -= 543;
            const d = new Date(year, monthIndex - 1, day);
            if (!isNaN(d.getTime())) return d;
        }
    }

    const parts = lowered.split(/[\/\-\s.]+/).filter(Boolean);
    if (parts.length === 3) {
        let day;
        let month;
        let year;

        if (parts[0].length === 4) {
            year = parseInt(parts[0], 10);
            month = parseInt(parts[1], 10);
            day = parseInt(parts[2], 10);
        } else if (parts[2].length === 4 || parts[2].length === 2) {
            day = parseInt(parts[0], 10);
            month = parseInt(parts[1], 10);
            year = parseInt(parts[2], 10);
        }

        if (year && month && day) {
            if (year < 100) year += 2500;
            if (year > 2400) year -= 543;
            const d = new Date(year, month - 1, day);
            if (!isNaN(d.getTime())) return d;
        }
    }

    return null;
}

// Excel Client-side File Parsing (with dynamic column mapping, PDPA Masking, multi-file support, and row-level (HCODE, Year, Month) grouping)
async function handleFileUpload(e) {
    const files = e.target.files;
    if (!files || files.length === 0) return;

    const replaceExisting = document.getElementById('import-replace-existing')?.checked;
    if (replaceExisting) {
        const shouldContinue = confirm('คุณต้องการลบข้อมูลเดิมก่อนนำเข้าไฟล์ใหม่หรือไม่?');
        if (!shouldContinue) {
            e.target.value = '';
            return;
        }

        try {
            await purgeDashboardDataBeforeImport();
            showToast('ล้างข้อมูลก่อนนำเข้าเสร็จแล้ว');
            recordAppLog('warn', 'purge-before-import', 'ล้างข้อมูลก่อนนำเข้า', {
                tab: activeTab
            });
        } catch (err) {
            alert(`เนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ: ${err.message}`);
            showToast(`เนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ: ${err.message}`, false);
            recordAppLog('error', 'purge-before-import-failed', `นำเข้าข้อมูลไม่สำเร็จ: ${err.message}`, {
                tab: activeTab,
                details: err.stack || err.message
            });
            e.target.value = '';
            return;
        }
    }

    let processedCount = 0;
    let lastUploadedYear = null;
    let lastUploadedFund = null;

    Array.from(files).forEach(file => {
        const base64Reader = new FileReader();
        base64Reader.onload = function(eBase) {
            const fileBase64 = eBase.target.result;
            const reader = new FileReader();
            reader.onload = function(evt) {
            try {
                const data = new Uint8Array(evt.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                
                // Smart Sheet Selection: Prioritize EXACT names the user requested
                let sheetName = workbook.SheetNames[0];
                
                // 1. HIGHEST PRIORITY: Look for "๏ฟฝ๏ฟฝยงาน๏ฟฝ๏ฟฝุป OP" or "๏ฟฝ๏ฟฝยงาน๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ IP" exactly
                let foundExact = false;
                for (let sheet of workbook.SheetNames) {
                    const s = sheet.toLowerCase().replace(/\s+/g, '');
                    if (activeTab === 'op' && s.includes('เนเธเธเนเธเธเธขเธเธฒเธเนเธเธเนเธเธเธธเธ') && (s.includes('op') || s.includes('เนเธเธเธญเธเนเธเธ'))) {
                        sheetName = sheet;
                        foundExact = true;
                        break;
                    }
                    if (activeTab === 'ip' && s.includes('เนเธเธเนเธเธเธขเธเธฒเธเนเธเธเนเธเธเธธเธ') && (s.includes('ip') || s.includes('เนเธเธเธญเธเนเธเธ'))) {
                        sheetName = sheet;
                        foundExact = true;
                        break;
                    }
                }
                
                // 2. SECOND PRIORITY: If not found, look for ANY sheet containing "๏ฟฝ๏ฟฝยงาน๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ"
                if (!foundExact) {
                    for (let sheet of workbook.SheetNames) {
                        const s = sheet.toLowerCase().replace(/\s+/g, '');
                        if (s.includes('๏ฟฝ๏ฟฝยงาน๏ฟฝ๏ฟฝุป') || s.includes('summary')) {
                            sheetName = sheet;
                            foundExact = true;
                            break;
                        }
                    }
                }

                // 3. LOWEST PRIORITY: Just look for 'op' or 'ip' anywhere in the name (if it's not the first sheet)
                if (!foundExact) {
                    for (let sheet of workbook.SheetNames) {
                        const s = sheet.toLowerCase().replace(/\s+/g, '');
                        if (activeTab === 'op' && (s.includes('op') || s.includes('เนเธเธเธญเธเนเธเธ'))) {
                            sheetName = sheet;
                            break;
                        }
                        if (activeTab === 'ip' && (s.includes('ip') || s.includes('เนเธเธเธญเธเนเธเธ'))) {
                            sheetName = sheet;
                            break;
                        }
                        if (activeTab === 'seamless' && s.includes('individual')) {
                            sheetName = sheet;
                            foundExact = true;
                            break;
                        }
                    }
                }
                
                const worksheet = workbook.Sheets[sheetName];
                let jsonData = XLSX.utils.sheet_to_json(worksheet);

                                // BULLETPROOF STM PARSER
                const rawRows = XLSX.utils.sheet_to_json(worksheet, { header: 1, defval: '' });
                let headerRowIdx = -1;
                let isSTM = false;
                
                // Find the header row (look for claim/approve keywords, and ensure it has many columns to avoid summary rows)
                for (let i = 0; i < Math.min(50, rawRows.length); i++) {
                    const r = rawRows[i];
                    const hasClaimOrApprove = r.some(c => {
                        const s = String(c);
                        return s.includes('๏ฟฝ๏ฟฝ๏ฟฝยก๏ฟฝ๏ฟฝ') || s.includes('๏ฟฝิก') || s.includes('approve') || s.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || s.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || s.includes('๏ฟฝ๏ฟฝยก๏ฟฝ๏ฟฝ');
                    });
                    const nonEmpties = r.filter(c => String(c).trim() !== '').length;
                    if (hasClaimOrApprove && nonEmpties > 3) {
                        headerRowIdx = i;
                        isSTM = true;
                        break;
                    }
                }
                
                // If not found, try fallback for Error sheets
                if (headerRowIdx === -1) {
                    for (let i = 0; i < Math.min(20, rawRows.length); i++) {
                        const r = rawRows[i];
                        if (r.some(c => String(c).includes('๏ฟฝ๏ฟฝยก๏ฟฝ๏ฟฝ')) && r.some(c => String(c).includes('๏ฟฝำนวน')) && r.some(c => String(c).toUpperCase().includes('CODE'))) {
                            headerRowIdx = i;
                            break;
                        }
                    }
                }
                
                jsonData = [];
                let mergedHeaders = [];
                if (headerRowIdx >= 0 && isSTM) {
                    let headers1 = rawRows[headerRowIdx];
                    let headers2 = rawRows[headerRowIdx + 1] || [];

                    let lastH1 = '';
                    for (let i = 0; i < Math.max(headers1.length, headers2.length); i++) {
                        let h1 = headers1[i] ? String(headers1[i]).trim() : '';
                        if (h1) lastH1 = h1; else h1 = lastH1; 
                        
                        let h2 = headers2[i] ? String(headers2[i]).trim() : '';
                        let finalH = h1;
                        if (h2 && h2 !== h1 && !h2.match(/^\d/)) { 
                            finalH += '_' + h2; 
                        }
                        mergedHeaders.push(finalH || `Column_${i}`);
                    }
                    
                    let startRow = headerRowIdx + (headers2.some(c => String(c).includes('๏ฟฝ๏ฟฝ๏ฟฝยจ๏ฟฝิง') || String(c).includes('DRUG') || String(c).includes('HC')) ? 2 : 1);
                    
                    for (let i = startRow; i < rawRows.length; i++) {
                        const row = rawRows[i];
                        if (row.filter(c => String(c).trim() !== '').length === 0) continue; 
                        
                        const obj = {};
                        mergedHeaders.forEach((h, colIdx) => {
                            if (row[colIdx] !== undefined && row[colIdx] !== '') {
                                obj[h] = row[colIdx];
                            }
                        });
                        obj._rawArray = row;
                        jsonData.push(obj);
                    }
                } else {
                    // Normal files (not STM)
                    jsonData = XLSX.utils.sheet_to_json(worksheet);
                }

                if (jsonData.length === 0) {
                    showToast(`เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ ${file.name}`, false);
                    recordAppLog('warn', 'upload-empty', `เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ ${file.name}`, {
                        fileName: file.name,
                        tab: activeTab
                    });
                    return;
                }
                
                const parsedYM = parseMonthAndYearFromFileName(file.name);
                let fileYear = parsedYM.fileYear;
                let fileMonth = parsedYM.fileMonth;
                
                let fileHospCode = 'UNKNOWN';
                const hMatch = file.name.match(/(?:_|^)(\d{5})(?:_|\b)/);
                if (hMatch) fileHospCode = hMatch[1];
                
                let fileFund = activeTab === 'op' ? 'OPBKK' : (activeTab === 'ip' ? 'IPBKK' : activeTab);
                
                // Allow filename to override fund type ONLY if in overview or ppfs tabs
                if (activeTab === 'ppfs' || activeTab === 'overview') {
                    if (file.name.toLowerCase().includes('pp')) fileFund = 'PP';
                    else if (file.name.toLowerCase().includes('ae')) fileFund = 'OPAE';
                    else if (file.name.toLowerCase().includes('fs')) fileFund = 'OPFS';
                    else if (activeTab === 'ppfs') fileFund = 'PP'; // default for ppfs tab
                }
                
                if (activeTab === 'seamless') fileFund = 'seamless';
                if (activeTab === 'smartmoney') fileFund = 'smartmoney';
                
                const lowerFileName = file.name.toLowerCase();
                const isPPFSSubFile = isSTM;
                const isSeamlessFile = (
                    activeTab === 'seamless' ||
                    ['๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝยต๏ฟฝ๏ฟฝ', 'seamless', 'dckd', 'dktp', 'dnap', 'dmtb', 'ddow', 'dtlm', 'dccs', 'dcmh', 'dtdt', 'danc', 'ddsa', 'dmor', 'dttm', 'ncd', 'ckd', 'mor'].some(token => lowerFileName.includes(token))
                );
                const seamlessContext = activeTab === 'seamless' || fileFund === 'seamless' || isSeamlessFile;

                if (seamlessContext) {
                    const selectedYearRaw = document.getElementById('year-select')?.value || 'all';
                    const selectedYear = selectedYearRaw === 'all' ? null : parseInt(selectedYearRaw, 10);
                    if (Number.isFinite(selectedYear) && selectedYear && String(fileYear) !== String(selectedYear)) {
                        const mismatchMessage = `ไฟล์ ${file.name} ปีงบประมาณ ${fileYear} ไม่ตรงกับปีที่เลือก ${selectedYear} จึงไม่ถูกนำเข้า`;
                        if (activeTab === 'seamless') {
                            showToast(`ปีงบประมาณ ${fileYear} ไม่ตรงกับปีที่เลือก ${selectedYear} จึงไม่ถูกนำเข้า`, true);
                            recordAppLog('warn', 'upload-year-mismatch-soft', mismatchMessage, {
                                fileName: file.name,
                                tab: activeTab,
                                fileYear: String(fileYear),
                                selectedYear: String(selectedYear)
                            });
                        } else {
                            showToast(mismatchMessage, false);
                            recordAppLog('warn', 'upload-year-mismatch', mismatchMessage, {
                                fileName: file.name,
                                tab: activeTab,
                                fileYear: String(fileYear),
                                selectedYear: String(selectedYear)
                            });
                            return;
                        }
                    }
                }

                recordAppLog('info', 'upload-start', `เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธ ${file.name}`, {
                    fileName: file.name,
                    fund: fileFund,
                    tab: activeTab
                });

                let fileSeamlessCategory = null;
                if (isSeamlessFile) {
                    const fileCategoryMatchers = [
                        ['dckd', 'ckd'],
                        ['dktp', 'ktp'],
                        ['dnap', 'napp'],
                        ['dmtb', 'tb'],
                        ['ddow', 'downs'],
                        ['dccs', 'cervical'],
                        ['dcmh', 'psych'],
                        ['dtdt', 'thal_disease'],
                        ['dtlm', 'thal_screen'],
                        ['danc', 'anc'],
                        ['ddsa', 'rehab'],
                        ['dmor', 'morprom'],
                        ['dttm', 'thai_med'],
                        ['ncd', 'ncd']
                    ];
                    for (const [token, category] of fileCategoryMatchers) {
                        if (lowerFileName.includes(token)) {
                            fileSeamlessCategory = category;
                            break;
                        }
                    }
                    if (!fileSeamlessCategory) {
                        if (lowerFileName.includes('psych') || lowerFileName.includes('จิตเวช')) fileSeamlessCategory = 'psych';
                        else if (lowerFileName.includes('down') || lowerFileName.includes('เนเธเธเนเธเธเธงเธเนเธเธ')) fileSeamlessCategory = 'downs';
                        else if (lowerFileName.includes('rehab') || lowerFileName.includes('เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ')) fileSeamlessCategory = 'rehab';
                        else if (lowerFileName.includes('mor') || lowerFileName.includes('เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธ')) fileSeamlessCategory = 'morprom';
                        else if (lowerFileName.includes('thal')) fileSeamlessCategory = 'thal_screen'; // Default Thal to screen
                    }
                }
                
                let groups = {};
                let isIndividualSheetProcessed = false;
                
                                let indSheetName = workbook.SheetNames.find(s => s.toLowerCase().includes('indivi') || s.includes('รายงาน'));
                let sumSheetName = workbook.SheetNames.find(s => s.toLowerCase().includes('summary') || s.includes('สรุป'));
                
                if (!indSheetName && workbook.SheetNames.length > 0) indSheetName = workbook.SheetNames[0];
                if (!sumSheetName && workbook.SheetNames.length > 1) sumSheetName = workbook.SheetNames[1];
                else if (!sumSheetName) sumSheetName = workbook.SheetNames[0];
                
                if (activeTab === 'smartmoney') {
                    const sheetName = workbook.SheetNames[0];
                    const rows = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], { header: "A", defval: '' });
                    
                    // 1. Hospital name from row 3, column A
                    let extractedHospName = '';
                    if (rows[2] && rows[2]['A']) {
                        extractedHospName = String(rows[2]['A']).replace(/^(เธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธ\s*:\s*|เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธ\s*:\s*|เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธ\s*:\s*)/i, '').trim();
                    }
                    
                    let smartHospCode = fileHospCode !== 'UNKNOWN' ? fileHospCode : 'UNKNOWN';
                    const hMatchSmart = extractedHospName.match(/(\d{5})/);
                    if (hMatchSmart) smartHospCode = hMatchSmart[1];
                    
                    let hospNameFromSummary = extractedHospName || smartHospCode;
                    if (smartHospCode !== 'UNKNOWN' && typeof HOSPITAL_DB_MAP !== 'undefined' && HOSPITAL_DB_MAP[smartHospCode]) {
                        hospNameFromSummary = smartHospCode + ' - ' + HOSPITAL_DB_MAP[smartHospCode];
                    }
                    
                    // 2. Extract monthly data using Column B (Month) and Amount from O
                    let monthlyData = {}; // key: YYYY-MM -> { claimSum, approveSum, subFunds, fileYear, fileMonth }
                    
                    for (let i = 3; i < rows.length; i++) {
                        const row = rows[i];
                        const colA = String(row['A'] || '').trim();
                        const isDataRow = /^\d+$/.test(colA);
                        
                        if (isDataRow) {
                            // Determine row month from Column B
                            let rowMonth = fileMonth;
                            let rowYear = fileYear;
                            const rawB = String(row['B'] || '').trim().toLowerCase();
                            const parsedRowDate = parseThaiDateStrict(rawB);
                            if (parsedRowDate) {
                                const fiscal = getFiscalDetails(parsedRowDate);
                                rowYear = parseInt(fiscal.fiscalYear, 10);
                                rowMonth = fiscal.monthIndex;
                            }
                            
                            if (!parsedRowDate && rawB !== '') {
                                const thaiMonths = ["เนเธเธเนเธเธเนเธเธเธฒเธเนเธเธ", "เนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเธฑเธเนเธเธเนเธเธ", "เนเธเธเธตเธเธฒเธเนเธเธ", "เนเธเธเนเธเธเนเธเธเนเธเธเธขเธ", "เนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธ", "เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธขเธ", "เนเธเธเธฃเธเนเธเธเธฒเธเนเธเธ", "เนเธเธเธดเธเนเธเธเธฒเธเนเธเธ", "เนเธเธเธฑเธเนเธเธเนเธเธเธขเธ", "เนเธเธเนเธเธเนเธเธเธฒเธเนเธเธ", "เนเธเธเนเธเธเธจเธเธดเธเนเธเธเธขเธ", "เนเธเธเธฑเธเนเธเธเธฒเธเนเธเธ"];
                                const thaiShortMonths = ["เนเธเธ.เนเธเธ.", "เนเธเธ.เนเธเธ.", "เนเธเธเนเธเธ.เนเธเธ.", "เนเธเธเนเธเธ.เนเธเธ.", "เนเธเธ.เนเธเธ.", "เนเธเธเนเธเธ.เนเธเธ.", "เนเธเธ.เนเธเธ.", "เนเธเธ.เนเธเธ.", "เนเธเธ.เนเธเธ.", "เนเธเธ.เนเธเธ.", "เนเธเธ.เนเธเธ.", "เนเธเธ.เนเธเธ."];
                                
                                let foundMonth = false;
                                for (let m = 0; m < 12; m++) {
                                    if (rawB.includes(thaiMonths[m]) || rawB.includes(thaiShortMonths[m])) {
                                        const calM = m + 1;
                                        rowMonth = (calM >= 10) ? calM - 9 : calM + 3; // convert to fiscal month 1-12
                                        foundMonth = true;
                                        break;
                                    }
                                }
                                
                                if (!foundMonth) {
                                    const matchDate = rawB.match(/(?:^|\D)(\d{1,2})(?:\/|\s|-|_)(\d{2,4})(?:$|\D)/);
                                    if (matchDate) {
                                        const mm = parseInt(matchDate[1]);
                                        const yy = parseInt(matchDate[2]);
                                        if (mm >= 1 && mm <= 12) {
                                            const calM = mm;
                                            rowMonth = (calM >= 10) ? calM - 9 : calM + 3;
                                            rowYear = yy > 2500 ? yy : (yy > 50 ? 2500 + yy : fileYear);
                                        }
                                    } else {
                                        const matchNum = rawB.match(/^(\d{1,2})$/);
                                        if (matchNum) {
                                            const mm = parseInt(matchNum[1]);
                                            if (mm >= 1 && mm <= 12) {
                                                const calM = mm;
                                                rowMonth = (calM >= 10) ? calM - 9 : calM + 3;
                                            }
                                        }
                                    }
                                }
                            }
                            
                            let mKey = `${rowYear}-${rowMonth}`;
                            if (!monthlyData[mKey]) {
                                monthlyData[mKey] = {
                                    fileYear: rowYear,
                                    fileMonth: rowMonth,
                                    claimSum: 0,
                                    approveSum: 0,
                                    subFunds: {}
                                };
                            }
                            
                            // Sum column H (Claim Amount)
                            const rawH = row['H'];
                            let numH = 0;
                            if (rawH !== undefined && rawH !== '') {
                                numH = typeof rawH === 'number' ? rawH : parseFloat(String(rawH).replace(/,/g, ''));
                                if (!isNaN(numH)) monthlyData[mKey].claimSum += numH;
                            }
                            
                            // Sum column O (Approved Amount)
                            const rawO = row['O'];
                            let numO = 0;
                            if (rawO !== undefined && rawO !== '') {
                                numO = typeof rawO === 'number' ? rawO : parseFloat(String(rawO).replace(/,/g, ''));
                                if (!isNaN(numO)) monthlyData[mKey].approveSum += numO;
                            }
                            
                            // Extract sub-fund details:
                            const rawF = row['F'];
                            const rawD = row['D'];
                            const rawG = row['G'];
                            
                            const mainFundName = rawF ? String(rawF).trim() : 'Smart Money';
                            const subFundCode = rawD ? String(rawD).trim() : 'SMARTMONEY';
                            const subFundName = rawG ? String(rawG).trim() : 'Smart Money';
                            
                            if (subFundCode) {
                                if (!monthlyData[mKey].subFunds[subFundCode]) {
                                    monthlyData[mKey].subFunds[subFundCode] = {
                                        code: colA,
                                        main: mainFundName,
                                        subCode: subFundCode,
                                        subName: subFundName,
                                        amt: 0
                                    };
                                }
                                monthlyData[mKey].subFunds[subFundCode].amt += numO;
                            }
                        }
                    }
                    
                    Object.values(monthlyData).forEach(mData => {
                        const subFundsList = Object.values(mData.subFunds);
                        const groupKey = `${smartHospCode}-${mData.fileYear}-${mData.fileMonth}-${activeTab}-${file.name}`;
                        
                        groups[groupKey] = {
                            fiscalYear: mData.fileYear, month: mData.fileMonth, hospital: hospNameFromSummary,
                            fund: activeTab, baseFund: activeTab,
                            fileName: file.name,
                            fileBase64: fileBase64,
                            claimAmount: mData.claimSum, approvedAmount: mData.approveSum, pendingAmount: 0, errorsMap: {},
                            subFundsBreakdown: subFundsList,
                            op_amt: 0, hc_amt: 0, hc_drug_amt: 0, ae_amt: 0, ae_drug_amt: 0, pp_amt: 0, fs_amt: 0,
                            ckd_amt: 0, ktp_amt: 0, napp_amt: 0, tb_amt: 0, downs_amt: 0, thal_screen_amt: 0, cervical_amt: 0, psych_amt: 0, thal_disease_amt: 0, rehab_amt: 0, thai_med_amt: 0, morprom_amt: 0, anc_amt: 0, ncd_amt: 0
                        };
                    });
                    
                    isIndividualSheetProcessed = true;
                } else if (activeTab === 'ip' || activeTab === 'op' || activeTab === 'ppfs') {
                    let detailSheetName = workbook.SheetNames.find(s => s.toLowerCase().includes('detail') || s.includes('เธฃเธฒเธขเธฅเธฐ')) || workbook.SheetNames[1] || workbook.SheetNames[0];
                    const detailRows = XLSX.utils.sheet_to_json(workbook.Sheets[detailSheetName], { header: "A", defval: '' });
                    const sumRows = XLSX.utils.sheet_to_json(workbook.Sheets[sumSheetName], { header: "A", defval: '' });
                    
                    let claimSum = 0;
                    let approveSum = 0;
                    let fileNameFromDetail = '';
                    let hospNameFromSummary = fileHospCode !== 'UNKNOWN' ? fileHospCode : 'UNKNOWN';
                    let ipOpErrorsMap = {};
                    let totalRecords = 0;
                    let failRecords = 0;
                    
                    console.log('[IP/OP] Detail sheet:', detailSheetName, '| Summary sheet:', sumSheetName);
                    console.log('[IP/OP] Detail rows count:', detailRows.length);
                    
                    // Step 1: Find the header row (the row where column A = "REP No." or similar)
                    let headerRowIdx = -1;
                    for (let i = 0; i < detailRows.length; i++) {
                        const colAStr = String(detailRows[i]['A'] || '').trim().toUpperCase();
                        if (colAStr.includes('REP') || colAStr === 'NO' || colAStr === 'NO.' || colAStr.includes('๏ฟฝำดับ')) {
                            headerRowIdx = i;
                            console.log('[IP/OP] Found header row at index', i, '| col A:', colAStr);
                            break;
                        }
                    }
                    
                    // Start reading data from the row after the header (skip sub-header rows too)
                    let dataStartIdx = headerRowIdx >= 0 ? headerRowIdx + 1 : 0;
                    
                    // Skip any sub-header rows (e.g. row with "สปสช." / "๏ฟฝ๏ฟฝ๏ฟฝัง๏ฟฝัด")
                    for (let i = dataStartIdx; i < detailRows.length; i++) {
                        const colA = detailRows[i]['A'];
                        // If column A is a number, we've found the first data row
                        if (typeof colA === 'number') {
                            dataStartIdx = i;
                            break;
                        }
                        const colAStr = String(colA || '').trim();
                        if (/^\d+$/.test(colAStr)) {
                            dataStartIdx = i;
                            break;
                        }
                    }
                    
                    console.log('[IP/OP] Data starts at row index:', dataStartIdx);
                    
                    let ipOpSubFunds = {}; // key: mainName + '||' + subName -> { code, main, subCode, subName, claimAmt, amt }
                    
                    // Step 2: Process only data rows
                    for (let i = dataStartIdx; i < detailRows.length; i++) {
                        const row = detailRows[i];
                        const colA = row['A'];
                        
                        // Stop if we hit empty rows (end of data)
                        if (colA === undefined || colA === null || colA === '') continue;
                        
                        // Count all rows that have a number in column A
                        const colAStr = String(colA).trim();
                        if (!/^\d+$/.test(colAStr)) {
                            continue; // Skip if col A is not a number
                        }
                        
                        totalRecords++;
                        
                        // Pick REP No. from column A (first data row)
                        if (!fileNameFromDetail) {
                            fileNameFromDetail = colAStr;
                            console.log('[IP/OP] Found REP No.:', fileNameFromDetail);
                        }
                        
                        // Sum column AO (claim amount)
                        const rawAO = row['AO'];
                        let numAO = 0;
                        if (rawAO !== undefined && rawAO !== '') {
                            const numAOVal = typeof rawAO === 'number' ? rawAO : parseFloat(String(rawAO).replace(/,/g, ''));
                            if (!isNaN(numAOVal)) {
                                claimSum += numAOVal;
                                numAO = numAOVal;
                            }
                        }
                        
                        // Sum column K (approved amount)
                        const rawK = row['K'];
                        let numK = 0;
                        if (rawK !== undefined && rawK !== '') {
                            const numKVal = typeof rawK === 'number' ? rawK : parseFloat(String(rawK).replace(/,/g, ''));
                            if (!isNaN(numKVal)) {
                                approveSum += numKVal;
                                numK = numKVal;
                            }
                        }
                        
                        // Column "๏ฟฝ๏ฟฝาน" "๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝาน" based on column N:
                        // if column N has "-" or is empty, it is "๏ฟฝ๏ฟฝาน"
                        // if it has letters or numbers, it is "๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝาน"
                        const rawN = row['N'];
                        const colNStr = String(rawN !== undefined && rawN !== null ? rawN : '').trim();
                        
                        let isPass = true;
                        if (colNStr === '' || colNStr === '-') {
                            isPass = true;
                        } else if (/[a-zA-Z0-9\u0e00-\u0e7f]/.test(colNStr)) {
                            isPass = false;
                        }
                        
                        if (!isPass) {
                            failRecords++;
                            const errCodeRaw = colNStr.toUpperCase();
                            if (errCodeRaw && errCodeRaw !== 'ERROR' && errCodeRaw !== 'ERROR CODE' && !errCodeRaw.includes('เธฃเธซเธฑเธช')) {
                                const codes = errCodeRaw.split(',').map(c => c.trim()).filter(c => c);
                                codes.forEach(code => {
                                    ipOpErrorsMap[code] = (ipOpErrorsMap[code] || 0) + 1;
                                });
                            }
                        }
                        
                        // Extract sub-fund details:
                        // Main Fund: Col O, Sub-Fund Name: Col P
                        const rawO = row['O'];
                        const rawP = row['P'];
                        const colOStr = rawO ? String(rawO).trim() : '';
                        const colPStr = rawP ? String(rawP).trim() : '';
                        
                        if (colOStr || colPStr) {
                            const mainParts = colOStr ? colOStr.split(',').map(s => s.trim()).filter(s => s) : [];
                            const subParts = colPStr ? colPStr.split(',').map(s => s.trim()).filter(s => s) : [];
                            
                            if (mainParts.length === 0) mainParts.push(activeTab === 'ip' ? '๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ' : '๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝยนอก');
                            if (subParts.length === 0) subParts.push(activeTab === 'ip' ? '๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ' : '๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝยนอก');
                            
                            const N = subParts.length;
                            for (let idx = 0; idx < N; idx++) {
                                const subFundName = subParts[idx];
                                const rawMainPart = mainParts[idx] || mainParts[0] || (activeTab === 'ip' ? '๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ' : '๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝยนอก');
                                const mainFundName = mapMainFundName(rawMainPart);
                                const subKey = mainFundName + '||' + subFundName;
                                
                                if (!ipOpSubFunds[subKey]) {
                                    ipOpSubFunds[subKey] = {
                                        code: colAStr, // sequence number/code from column A
                                        main: mainFundName,
                                        subCode: activeTab.toUpperCase(),
                                        subName: subFundName,
                                        claimAmt: 0,
                                        amt: 0,
                                        count: 0
                                    };
                                }
                                ipOpSubFunds[subKey].claimAmt += numAO / N;
                                ipOpSubFunds[subKey].amt += numK / N;
                                ipOpSubFunds[subKey].count += 1;
                            }
                        }
                    }
                    const passRecords = totalRecords - failRecords;
                    
                    console.log('[IP/OP] Final REP No.:', fileNameFromDetail, '| claimSum:', claimSum, '| approveSum:', approveSum);
                    console.log('[IP/OP] Error codes found:', Object.keys(ipOpErrorsMap).length, ipOpErrorsMap);
                    console.log('[IP/OP] Records count:', totalRecords, '| pass:', passRecords, '| fail:', failRecords);
                    
                    let extractedHospName = null;
                    for (let i = 0; i < sumRows.length; i++) {
                        const valB = String(sumRows[i]['B'] || '').trim();
                        if (valB && valB.length > 5 && !valB.includes('หน๏ฟฝ๏ฟฝยบ๏ฟฝิก๏ฟฝ๏ฟฝ') && !valB.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') && !valB.includes('HCODE')) {
                            extractedHospName = valB;
                            break;
                        }
                    }
                    
                    if (!extractedHospName && fileHospCode !== 'UNKNOWN' && typeof HOSPITALS !== 'undefined') {
                        const known = HOSPITALS.find(h => h.startsWith(fileHospCode));
                        if (known) extractedHospName = known;
                    }
                    
                    hospNameFromSummary = extractedHospName || fileHospCode;
                    
                    const groupKey = `${fileHospCode}-${fileYear}-${fileMonth}-${activeTab}-${file.name}`;
                    groups[groupKey] = {
                        fiscalYear: fileYear, month: fileMonth, hospital: hospNameFromSummary,
                        fund: activeTab, baseFund: activeTab === 'ip' ? 'IPBKK' : 'OPBKK',
                        fileName: fileNameFromDetail,
                        fileBase64: fileBase64,
                        claimAmount: claimSum, approvedAmount: approveSum, pendingAmount: 0, errorsMap: ipOpErrorsMap,
                        totalRecords: totalRecords, passRecords: passRecords, failRecords: failRecords,
                        subFundsBreakdown: Object.values(ipOpSubFunds),
                        op_amt: 0, hc_amt: 0, hc_drug_amt: 0, ae_amt: 0, ae_drug_amt: 0, pp_amt: 0, fs_amt: 0,
                        ckd_amt: 0, ktp_amt: 0, napp_amt: 0, tb_amt: 0, downs_amt: 0, thal_screen_amt: 0, cervical_amt: 0, psych_amt: 0, thal_disease_amt: 0, rehab_amt: 0, thai_med_amt: 0, morprom_amt: 0, anc_amt: 0, ncd_amt: 0
                    };
                    isIndividualSheetProcessed = true;
                } else if (seamlessContext && indSheetName && sumSheetName) {
                    // Step 1: Detect category and sub-fund mapping from Individual sheet
                    let hospitalMetaMap = {};
                    let globalCategoryProp = null; // Fallback category if we can't find one for a specific hospital
                    let globalSubFundName = ''; // Fallback sub-fund name
                    const indRows = XLSX.utils.sheet_to_json(workbook.Sheets[indSheetName], { header: "A", defval: '' });
                    for (const row of indRows) {
                        const colB = String(row['B'] || '').trim().toUpperCase();
                        const colM = String(row['M'] || '').trim();
                        const colZ = String(row['Z'] || '').trim();
                        const colAA = String(row['AA'] || '').trim();
                        
                        let hospCode = null;
                        let fullHospString = colAA || colZ;
                        
                        let m = colAA.match(/(\d{5})/);
                        if (m) hospCode = m[1];
                        else {
                            m = colZ.match(/(\d{5})/);
                            if (m) hospCode = m[1];
                        }
                        if (hospCode && !fullHospString.includes(hospCode)) {
                            fullHospString = hospCode + ' - ' + fullHospString;
                        }
                        hospCode = normalizeHospitalCode(hospCode, fileHospCode);
                        if (!fullHospString || fullHospString === hospCode) {
                            fullHospString = fullHospString && fullHospString !== 'UNKNOWN' ? fullHospString : hospCode;
                        }
                        
                        let detectedCategoryProp = null;
                        let mainCategoryName = null;
                        if (isSeamlessFile && fileSeamlessCategory) {
                            detectedCategoryProp = `${fileSeamlessCategory}_amt`;
                            const seamlessCategoryNames = {
    ckd: 'CKD',
    ktp: 'KTP',
    napp: 'napp',
    tb: 'TB',
    downs: 'เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเธงเธเนเธเธ',
    thal_screen: 'เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเธชเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    cervical: 'เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    psych: 'เนเธเธเธดเธ•เนเธเธเธงเธ',
    thal_disease: 'เนเธเธเนเธเธเนเธเธเนเธเธเธชเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    rehab: 'เนเธเธเนเธเธเนเธเธเนเธเธ',
    thai_med: 'เนเธเธ—เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    morprom: 'เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    anc: 'เนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
    ncd: 'เนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ/เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฑเธ'
};
                            mainCategoryName = seamlessCategoryNames[fileSeamlessCategory] || fileSeamlessCategory;
                        } else if (colB.includes('DCKD')) { detectedCategoryProp = 'ckd_amt'; mainCategoryName = 'CKD'; }
                        else if (colB.includes('DKTP')) { detectedCategoryProp = 'ktp_amt'; mainCategoryName = 'KTP'; }
                        else if (colB.includes('DNAP')) { detectedCategoryProp = 'napp_amt'; mainCategoryName = 'NAPP'; }
                        else if (colB.includes('DMTB')) { detectedCategoryProp = 'tb_amt'; mainCategoryName = 'TB'; }
                        else if (colB.includes('DDOW')) { detectedCategoryProp = 'downs_amt'; mainCategoryName = 'เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเธงเธเนเธเธ'; }
                        else if (colB.includes('DTLM')) { detectedCategoryProp = 'thal_screen_amt'; mainCategoryName = 'เนเธเธเนเธเธเนเธเธเนเธเธเธชเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ'; }
                        else if (colB.includes('DCCS')) { detectedCategoryProp = 'cervical_amt'; mainCategoryName = 'เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเธนเธ'; }
                        else if (colB.includes('DCMH')) { detectedCategoryProp = 'psych_amt'; mainCategoryName = 'เนเธเธเธดเธ•เนเธเธเธงเธ'; }
                        else if (colB.includes('DTDT')) { detectedCategoryProp = 'thal_disease_amt'; mainCategoryName = 'เนเธเธเนเธเธเนเธเธเนเธเธเธชเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ'; }
                        else if (colB.includes('DANC')) { detectedCategoryProp = 'anc_amt'; mainCategoryName = 'เนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ'; }
                        else if (colB.includes('DDSA')) { detectedCategoryProp = 'rehab_amt'; mainCategoryName = 'เนเธเธเนเธเธเนเธเธเนเธเธ'; }
                        else if (colB.includes('DMOR')) { detectedCategoryProp = 'morprom_amt'; mainCategoryName = 'เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธ'; }
                        else if (colB.includes('DTM')) { detectedCategoryProp = 'thai_med_amt'; mainCategoryName = 'เนเธเธ—เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ'; }
                        else if (getCurrentSeamlessSubtab()) {
                            detectedCategoryProp = getCurrentSeamlessSubtab() + '_amt';
                            mainCategoryName = getCurrentSeamlessSubtabName();
                        } else {
                            detectedCategoryProp = 'ckd_amt'; // fallback
                            mainCategoryName = 'CKD';
                        }
                        
                        if (detectedCategoryProp) {
                            if (!globalCategoryProp) {
                                globalCategoryProp = detectedCategoryProp;
                                globalSubFundName = colM;
                            }
                            
                            const mapKey = hospCode || fullHospString || fileHospCode;
                            if (mapKey) {
                                hospitalMetaMap[mapKey] = { categoryProp: detectedCategoryProp, subFundName: colM, fullHospString: fullHospString, mainCategoryName: mainCategoryName };
                            }
                            if (hospCode) {
                                hospitalMetaMap[hospCode] = { categoryProp: detectedCategoryProp, subFundName: colM, fullHospString: fullHospString, mainCategoryName: mainCategoryName };
                            }
                            
                            let indAmt = 0;
                            const colT = String(row['T'] || '').replace(/[^0-9.-]/g, '');
                            if (!isNaN(parseFloat(colT)) && parseFloat(colT) > 0) {
                                indAmt = parseFloat(colT);
                            } else {
                                const fallbackCols = ['Q', 'R', 'S', 'U', 'V', 'W', 'P', 'O', 'N'];
                                for (let fc of fallbackCols) {
                                    const val = String(row[fc] || '').replace(/[^0-9.-]/g, '');
                                    if (!isNaN(parseFloat(val)) && parseFloat(val) > 0 && parseFloat(val) !== parseInt(hospCode)) {
                                        indAmt = parseFloat(val);
                                        break;
                                    }
                                }
                            }
                            
                            if (indAmt > 0 && mapKey) {
                                const groupKey = `${mapKey}-${fileYear}-${fileMonth}-seamless-${colM || 'default'}`;
                                if (!groups[groupKey]) {
                                    groups[groupKey] = {
                                        id: groupKey,
                                        fiscalYear: fileYear, month: fileMonth, hospital: fullHospString,
                                        fund: 'seamless', baseFund: 'seamless',
                                        subFundName: colM,
                                        mainCategoryName: mainCategoryName,
                                        subFundAmount: 0,
                                        claimAmount: 0, approvedAmount: 0, pendingAmount: 0,
                                        fileName: file.name,
                                        fileBase64: fileBase64,
                                        op_amt: 0, hc_amt: 0, hc_drug_amt: 0, ae_amt: 0, ae_drug_amt: 0, pp_amt: 0, fs_amt: 0,
                                        ckd_amt: 0, ktp_amt: 0, napp_amt: 0, tb_amt: 0, downs_amt: 0, thal_screen_amt: 0, cervical_amt: 0, psych_amt: 0, thal_disease_amt: 0, rehab_amt: 0, thai_med_amt: 0, morprom_amt: 0, anc_amt: 0, ncd_amt: 0,
                                        errorsMap: {}
                                    };
                                }
                                groups[groupKey].subFundAmount += indAmt;
                            }
                            
                            isIndividualSheetProcessed = true;
                        }
                    }
                    
                    // Step 2: Extract amounts from Summary sheet
                    const sumRows = XLSX.utils.sheet_to_json(workbook.Sheets[sumSheetName], { header: "A", defval: '' });
                    sumRows.forEach(row => {
                        let hospCode = null;
                        let hospString = null;
                            
                            // Try J first
                            const colJ = String(row['J'] || '').trim();
                            if (colJ) {
                                hospString = colJ;
                                const m = hospString.match(/(\d{5})/);
                                if (m) hospCode = m[1];
                            }
                        
                        // If J doesn't have a 5-digit code, scan all columns
                        if (!hospCode && !colJ) {
                            for (const key of Object.keys(row)) {
                                const val = String(row[key] || '').trim();
                                const m = val.match(/^(\d{5})/);
                                if (m && !val.includes('DCKD')) { // Avoid matching category prefixes if they have 5 digits
                                    hospCode = m[1];
                                    hospString = val;
                                    break;
                                }
                            }
                        }
                        
                        let amt = 0;
                        const colK_val = String(row['K'] || '').replace(/,/g, '');
                        amt = parseFloat(colK_val) || 0;
                        
                        // If K is 0 or NaN, try finding the first positive number in the row
                        if (amt === 0) {
                            for (const key of Object.keys(row)) {
                                if (key !== 'J' && key !== 'Z') { // Skip known string columns just in case
                                    const val = parseFloat(String(row[key] || '').replace(/,/g, ''));
                                    if (val > 0 && val !== parseInt(hospCode)) {
                                        amt = val;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        if (amt > 0 && hospString) {
                            if (!hospCode) {
                                hospCode = fileHospCode !== 'UNKNOWN' ? fileHospCode : 'UNKNOWN';
                            } else {
                                if (hospString === hospCode && typeof HOSPITALS !== 'undefined') {
                                    const known = HOSPITALS.find(h => h.startsWith(hospCode));
                                    if (known) hospString = known;
                                }
                            }
                            hospCode = normalizeHospitalCode(hospCode, fileHospCode);
                            
                            const lookupKey = hospCode || hospString || fileHospCode;
                            const meta = hospitalMetaMap[lookupKey] || hospitalMetaMap[hospCode] || { categoryProp: globalCategoryProp, subFundName: globalSubFundName || getCurrentSeamlessSubtabName() || '' };
                            const currentProp = getCurrentSeamlessSubtab() ? getCurrentSeamlessSubtab() + '_amt' : 'ckd_amt';
                            const categoryPropToUse = meta.categoryProp || globalCategoryProp || currentProp;
                            
                            if (categoryPropToUse) {
                                const groupKey = `${hospCode}-${fileYear}-${fileMonth}-seamless-${meta.subFundName || ''}`;
                                if (!groups[groupKey]) {
                                    groups[groupKey] = {
                                        fiscalYear: fileYear, month: fileMonth, hospital: meta.fullHospString || hospString,
                                        fund: 'seamless', baseFund: 'seamless',
                                        subFundName: meta.subFundName,
                                        fileName: file.name,
                                        fileBase64: fileBase64,
                                        claimAmount: 0, approvedAmount: 0, pendingAmount: 0, errorsMap: {},
                                        op_amt: 0, hc_amt: 0, hc_drug_amt: 0, ae_amt: 0, ae_drug_amt: 0, pp_amt: 0, fs_amt: 0,
                                        ckd_amt: 0, ktp_amt: 0, napp_amt: 0, tb_amt: 0, downs_amt: 0, thal_screen_amt: 0, cervical_amt: 0, psych_amt: 0, thal_disease_amt: 0, rehab_amt: 0, thai_med_amt: 0, morprom_amt: 0, anc_amt: 0,
                                    };
                                }
                                groups[groupKey].claimAmount += amt;
                                groups[groupKey].approvedAmount += amt;
                                groups[groupKey][categoryPropToUse] += amt;
                                
                                if (meta.subFundName && !groups[groupKey].subFundName) {
                                    groups[groupKey].subFundName = meta.subFundName;
                                }
                                
                                isIndividualSheetProcessed = true;
                            }
                        }
                    });
                } else if (seamlessContext && indSheetName) {
                    const rawRowsAlpha = XLSX.utils.sheet_to_json(workbook.Sheets[indSheetName], { header: "A", defval: '' });
                    const seamlessCategoryKey = fileSeamlessCategory || normalizeUploadMenuKey(getCurrentSeamlessSubtab()) || 'morprom';
                    const seamlessAmountProp = `${seamlessCategoryKey}_amt`;

                    rawRowsAlpha.forEach(row => {
                        const rowIndex = String(row['A'] || '').trim();
                        const repNo = String(row['B'] || '').trim();
                        const isDataRow = /^\d+$/.test(rowIndex) || /^\d+$/.test(repNo);
                        if (!isDataRow) return;

                        const claimRaw = String(row['Q'] || '').replace(/,/g, '').trim();
                        const approvedRaw = String(row['T'] || '').replace(/,/g, '').trim();
                        const countRaw = String(row['N'] || '').replace(/,/g, '').trim();
                        let claimAmt = parseFloat(claimRaw) || 0;
                        let approvedAmt = parseFloat(approvedRaw) || 0;
                        let rowAmt = approvedAmt > 0 ? approvedAmt : claimAmt;
                        let approvedByFlag = false;

                        // Some Seamless workbooks (notably DCCS/DCMH) store the claim / approval
                        // amounts in H/I and the pass flag in G instead of Q/T.
                        if (rowAmt <= 0) {
                            const altH = parseFloat(String(row['H'] || '').replace(/,/g, '').trim()) || 0;
                            const altI = parseFloat(String(row['I'] || '').replace(/,/g, '').trim()) || 0;
                            const altAmt = Math.max(altH, altI);
                            if (altAmt > 0) {
                                rowAmt = altAmt;
                                claimAmt = altAmt;
                                const passFlag = String(row['G'] || '').trim().toUpperCase();
                                approvedByFlag = true;
                                approvedAmt = (passFlag === '1' || passFlag === 'Y' || passFlag === 'YES' || passFlag === 'PASS') ? altAmt : 0;
                            }
                        }

                        if (rowAmt <= 0) return;

                        let hospCode = fileHospCode !== 'UNKNOWN' ? fileHospCode : 'UNKNOWN';
                        let hospString = String(row['I'] || row['Z'] || '').trim() || 'UNKNOWN';
                        const hospMatch = hospString.match(/^(\d{5})/);
                        if (hospMatch) hospCode = hospMatch[1];
                        else {
                            const altMatch = String(row['I'] || row['Z'] || '').match(/(\d{5})/);
                            if (altMatch) hospCode = altMatch[1];
                        }
                        hospCode = normalizeHospitalCode(hospCode, fileHospCode);

                        if (hospCode && HOSPITAL_DB_MAP[hospCode]) {
                            hospString = `${hospCode} - ${HOSPITAL_DB_MAP[hospCode]}`;
                        } else if (hospCode && hospString === 'UNKNOWN') {
                            hospString = hospCode;
                        }

                        let rowYear = fileYear;
                        let rowMonth = fileMonth;
                        const serviceDate = parseThaiDateStrict(String(row['K'] || '').trim());
                        if (serviceDate) {
                            const fiscal = getFiscalDetails(serviceDate);
                            rowYear = parseInt(fiscal.fiscalYear, 10);
                            rowMonth = fiscal.monthIndex;
                        }

                        const groupKey = `${hospCode}-${rowYear}-${rowMonth}-seamless-${seamlessCategoryKey}-${file.name}`;
                        if (!groups[groupKey]) {
                            groups[groupKey] = {
                                fiscalYear: rowYear,
                                month: rowMonth,
                                hospital: hospString,
                                fund: 'seamless',
                                baseFund: 'seamless',
                                subFundName: String(row['M'] || globalSubFundName || getCurrentSeamlessSubtabName() || seamlessCategoryKey).trim(),
                                mainCategoryName: getCurrentSeamlessSubtabName() || seamlessCategoryKey,
                                fileName: file.name,
                                fileBase64: fileBase64,
                                claimAmount: 0,
                                approvedAmount: 0,
                                pendingAmount: 0,
                                errorsMap: {},
                                recordCount: 0,
                                op_amt: 0, hc_amt: 0, hc_drug_amt: 0, ae_amt: 0, ae_drug_amt: 0, pp_amt: 0, fs_amt: 0,
                                ckd_amt: 0, ktp_amt: 0, napp_amt: 0, tb_amt: 0, downs_amt: 0, thal_screen_amt: 0, cervical_amt: 0, psych_amt: 0, thal_disease_amt: 0, rehab_amt: 0, thai_med_amt: 0, morprom_amt: 0, anc_amt: 0, ncd_amt: 0
                            };
                        }

                        const currentGroup = groups[groupKey];
                        currentGroup.claimAmount += claimAmt > 0 ? claimAmt : rowAmt;
                        currentGroup.approvedAmount += approvedByFlag ? approvedAmt : (approvedAmt > 0 ? approvedAmt : rowAmt);
                        currentGroup.recordCount += parseFloat(countRaw) > 0 ? parseFloat(countRaw) : 1;
                        currentGroup.subFundName = currentGroup.subFundName || String(row['M'] || '').trim();
                        currentGroup[seamlessAmountProp] = (currentGroup[seamlessAmountProp] || 0) + (approvedByFlag ? approvedAmt : (approvedAmt > 0 ? approvedAmt : rowAmt));
                        isIndividualSheetProcessed = true;
                    });
                }
                
                if (!isIndividualSheetProcessed) {
                    jsonData.forEach(row => {
                    let claim = 0, approved = 0, numCases = 1;
                    let rowOp = 0, rowHc = 0, rowHcDrug = 0, rowAe = 0, rowAeDrug = 0, rowPp = 0, rowFs = 0;
                    let rowCkd = 0, rowKtp = 0, rowNapp = 0, rowTb = 0, rowDowns = 0, rowThalScreen = 0, rowCervical = 0, rowPsych = 0, rowThalDisease = 0, rowRehab = 0, rowMorprom = 0, rowAnc = 0, rowNcd = 0;
                    let rowCmi = 0;
                    let rowErrorCode = null;
                    let rowHospCode = null;
                    let rowHospString = null;
                    let rowYear = fileYear;
                    let rowMonth = fileMonth;
                    let dynamicFundName = null;
                    let dynamicFundCode = null;
                    let rowDate = null;
                    
                    Object.keys(row).forEach(key => {
                        const val = row[key];
                        if (val === undefined || val === null || String(val).trim() === '') return;
                        
                        const cleanKeyRaw = String(key).toLowerCase();
                        const cleanKeyNorm = cleanKeyRaw.replace(/[\s_\-\.]+/g, '').replace('๏ฟฝึง๏ฟฝับ', '');
                        
                        let numVal = 0;
                        let cleanValStr = String(val).replace(/[^\d\.\-\(\)]/g, '').trim();
                        if (cleanValStr.startsWith('(') && cleanValStr.endsWith(')')) { 
                            cleanValStr = '-' + cleanValStr.substring(1, cleanValStr.length - 1);
                        }
                        if (cleanValStr !== '' && !isNaN(Number(cleanValStr))) {
                            numVal = Number(cleanValStr);
                        }
                        
                        // Extract CMI value if column contains CMI or AdjRW
                        if (cleanKeyRaw === 'cmi' || cleanKeyRaw === 'cmi_val' || cleanKeyRaw.includes('cmi') || cleanKeyRaw.includes('adjrw') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝหนัก๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝัท๏ฟฝ๏ฟฝ')) {
                            if (numVal > 0) {
                                rowCmi = numVal;
                            }
                        }

                        const isErrorCol = cleanKeyRaw.includes('error') || cleanKeyRaw.includes('err') || cleanKeyRaw.includes('deny') || cleanKeyRaw === 'error_code' || cleanKeyRaw === 'c_code';
                        const isMoneyCol = cleanKeyRaw.includes('๏ฟฝิน') || cleanKeyRaw.includes('๏ฟฝาท') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝยก๏ฟฝ๏ฟฝ') || cleanKeyRaw.includes('๏ฟฝิก') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝัต๏ฟฝ') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ');
                        const isCountCol = (cleanKeyRaw.includes('๏ฟฝำนวน') || cleanKeyRaw.includes('case') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || cleanKeyRaw.includes('visit') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ')) && !isMoneyCol;
                        
                        if (!isErrorCol && !isCountCol) {
                            if (cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝยก๏ฟฝ๏ฟฝ') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝิก') || cleanKeyRaw === 'claim' || cleanKeyRaw.includes('๏ฟฝิก')) { 
                                claim = Math.max(claim, numVal);
                            } else if (cleanKeyRaw.includes('approve') || cleanKeyRaw.includes('paid') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝัต๏ฟฝ') || cleanKeyRaw.includes('๏ฟฝอน') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝยจ๏ฟฝิง') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ')) {
                                approved = Math.max(approved, numVal);
                            }
                            
                            // Sub-categories matching (STM merged headers support)
                            let norm = cleanKeyNorm.replace('๏ฟฝึง๏ฟฝับ', ''); // Strip prefix
                            if (norm === 'op') rowOp = numVal;
                            else if (norm === 'hc' || norm === 'hchc') rowHc = numVal;
                            else if (norm === 'hcdrug' || norm === 'hchcdrug') rowHcDrug = numVal;
                            else if (norm === 'ae' || norm === 'aeae') rowAe = numVal;
                            else if (norm === 'aedrug' || norm === 'aeaedrug') rowAeDrug = numVal;
                            else if (norm === 'pp' || norm === 'pppp') rowPp = numVal;
                            else if (norm === 'fs' || norm === 'fsfs') rowFs = numVal;
                            
                            // Seamless sub-categories
                            else if (cleanKeyRaw.includes('ckd') || cleanKeyRaw.includes('๏ฟฝ')) rowCkd = numVal;
                            else if (cleanKeyRaw.includes('ktp') || cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ')) rowKtp = numVal;
                            else if (cleanKeyRaw.includes('napp') || cleanKeyRaw.includes('๏ฟฝิต๏ฟฝวช')) rowNapp = numVal;
                            else if (cleanKeyRaw.includes('tb') || cleanKeyRaw.includes('๏ฟฝัณ๏ฟฝรค')) rowTb = numVal;
                            else if (cleanKeyRaw.includes('๏ฟฝ๏ฟฝวน๏ฟฝ')) rowDowns = numVal;
                            else if (cleanKeyRaw.includes('๏ฟฝัด๏ฟฝ๏ฟฝอง๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ')) rowThalScreen = numVal;
                            else if (cleanKeyRaw.includes('๏ฟฝาก๏ฟฝ๏ฟฝ๏ฟฝูก') || cleanKeyRaw.includes('cervical')) rowCervical = numVal;
                            else if (cleanKeyRaw.includes('๏ฟฝิต๏ฟฝวช') || cleanKeyRaw.includes('psych')) rowPsych = numVal;
                            else if (cleanKeyRaw.includes('๏ฟฝรค๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ')) rowThalDisease = numVal;
                            else if (cleanKeyRaw.includes('๏ฟฝ๏ฟฝ้นฟ๏ฟฝ') || cleanKeyRaw.includes('rehab')) rowRehab = numVal;
                            else if (cleanKeyRaw.includes('๏ฟฝ๏ฟฝอพ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || cleanKeyRaw.includes('morprom')) rowMorprom = numVal;
                        }
                        
                        if (isErrorCol) {
                            if (val && typeof val === 'string' && val.trim() !== '' && val !== '[PROTECTED BY PDPA]') {
                                const code = val.trim().toUpperCase();
                                if (code.length >= 3) {
                                    rowErrorCode = code;
                                }
                            }
                        }
                        
                        if (cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝอกอง๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || cleanKeyRaw === 'subfund' || cleanKeyRaw === '๏ฟฝอง๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') {
                            if (val) dynamicFundName = String(val).trim();
                        }
                        if (cleanKeyRaw.includes('๏ฟฝ๏ฟฝ๏ฟฝสกอง๏ฟฝุน๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || cleanKeyRaw === 'subfundcode') {
                            if (val) dynamicFundCode = String(val).trim();
                        }
                        
                        if (cleanKeyRaw.includes('date') || cleanKeyRaw.includes('๏ฟฝัน')) {
                            const parsedRowDate = parseThaiDateStrict(val);
                            if (parsedRowDate) rowDate = parsedRowDate;
                        }
                        
                        if (cleanKeyRaw.includes('hosp') || cleanKeyRaw.includes('สถาน๏ฟฝ๏ฟฝาบ๏ฟฝ๏ฟฝ') || (cleanKeyRaw.includes('code') && cleanKeyRaw.includes('h'))) {
                            if (numVal > 1000) {
                                rowHospCode = numVal;
                                rowHospString = val;
                            }
                        }
                        
                        if (isSeamlessFile) {
                            if (cleanKeyRaw.includes('หน่วยบริการ') || cleanKeyRaw.includes('hsub') || cleanKeyRaw.includes('หน่วยบริการ')) {
                                const strVal = String(val).trim();
                                const m = strVal.match(/^(\d{5})/);
                                if (m) {
                                    rowHospCode = m[1];
                                    rowHospString = strVal;
                                }
                            }
                        }
                        
                        if ((cleanKeyRaw.includes('เดือน') || cleanKeyRaw.includes('month') || cleanKeyRaw === 'm') && numVal >= 1 && numVal <= 12) {
                            rowMonth = numVal;
                        }
                        if ((cleanKeyRaw.includes('ปี') || cleanKeyRaw.includes('year') || cleanKeyRaw === 'y') && numVal >= 2560) {
                            rowYear = numVal;
                        }
                    });
                    
                    // Extract Column M (Report / Sub-Fund Name & Year override)
                    const rawMVal = String(row['M'] || row['m'] || (row._rawArray ? row._rawArray[12] : '') || '').trim();
                    if (rawMVal) {
                        dynamicFundName = rawMVal;
                        const yMatchColM = rawMVal.match(/(25\d{2})/);
                        if (yMatchColM) {
                            rowYear = parseInt(yMatchColM[1]);
                        }
                    }
                    
                    if (activeTab === 'ppfs_sub') {
                        let colNVal = '';
                        if (row._rawArray) {
                            colNVal = String(row._rawArray[13] || '').trim(); // 13 is N
                        } else {
                            const rNum = row.__rowNum__ !== undefined ? row.__rowNum__ + 1 : null;
                            if (rNum && worksheet['N' + rNum]) colNVal = String(worksheet['N' + rNum].v || '').trim();
                        }
                        
                        if (colNVal.toUpperCase() === 'HCODE') return;
                        
                        if (colNVal !== '') {
                            const match = colNVal.match(/^(\d{5})/);
                            rowHospCode = match ? match[1] : colNVal;
                            rowHospString = colNVal;
                        }
                    }

                    if (rowDate) {
                        const fiscal = getFiscalDetails(rowDate);
                        rowYear = parseInt(fiscal.fiscalYear, 10);
                        rowMonth = fiscal.monthIndex;
                    }

                    if (!rowHospCode && !rowDate && claim === 0 && approved === 0) {
                        return; // Skip completely empty semantic rows
                    }
                    if (!rowHospCode) {
                        rowHospCode = fileHospCode !== 'UNKNOWN' ? fileHospCode : (Object.keys(groups).length > 0 ? Object.values(groups)[0].hospital.split(' - ')[0] : 'UNKNOWN');
                    }
                    if (!rowHospString) rowHospString = rowHospCode + ' - โรงพยาบาล (ไม่ระบุ)';
                    
                    if (rowHospCode && HOSPITAL_DB_MAP[rowHospCode]) {
                        rowHospString = rowHospCode + ' - ' + HOSPITAL_DB_MAP[rowHospCode];
                    }
                    
                    let currentFund = fileFund;
                    if (dynamicFundName && activeTab !== 'ppfs_sub' && !isPPFSSubFile) {
                        currentFund = dynamicFundCode ? `${dynamicFundCode}||${dynamicFundName}` : dynamicFundName;
                    }
                    if (isSeamlessFile) {
                        currentFund = 'seamless';
                    }
                    // STM File specific: If we found sub-category amounts, their sum IS the total approved!
                    const stmSum = rowOp + rowHc + rowHcDrug + rowAe + rowAeDrug + rowPp + rowFs;
                    if (stmSum > 0) {
                        approved = stmSum;
                    }
                    
                    if (activeTab === 'ppfs_sub') {
                        const getRawVal = (colIdx, colChar) => {
                            let valStr = '';
                            if (row._rawArray) {
                                valStr = String(row._rawArray[colIdx] || '');
                            } else {
                                const rNum = row.__rowNum__ !== undefined ? row.__rowNum__ + 1 : null;
                                if (rNum && worksheet[colChar + rNum]) valStr = String(worksheet[colChar + rNum].v || '');
                            }
                            valStr = valStr.replace(/[^\d\.\-\(\)]/g, '');
                            return valStr !== '' ? Number(valStr) : 0;
                        };
                        
                        rowOp = getRawVal(27, 'AB');
                        rowHc = getRawVal(30, 'AE');
                        rowHcDrug = getRawVal(31, 'AF');
                        rowAe = getRawVal(32, 'AG');
                        rowAeDrug = getRawVal(33, 'AH');
                        rowPp = getRawVal(40, 'AO');
                        rowFs = getRawVal(41, 'AP');

                        let colAQVal = '';
                        if (row._rawArray) {
                            colAQVal = String(row._rawArray[42] || ''); // 42 is AQ
                        } else {
                            const rNum = row.__rowNum__ !== undefined ? row.__rowNum__ + 1 : null;
                            if (rNum && worksheet['AQ' + rNum]) colAQVal = String(worksheet['AQ' + rNum].v || '');
                        }
                        colAQVal = colAQVal.replace(/[^\d\.\-\(\)]/g, '');
                        approved = colAQVal !== '' ? Number(colAQVal) : 0;
                    } else {
                        if (claim > 0 && approved === 0) approved = claim;
                        if (approved > 0 && claim === 0) claim = approved;
                    }

                    if (isSeamlessFile) {
                        const readSeamlessAmount = (colChar) => {
                            let rawVal = '';
                            if (row._rawArray) {
                                const idx = colChar.charCodeAt(0) - 65;
                                rawVal = String(row._rawArray[idx] || '');
                            } else {
                                const rNum = row.__rowNum__ !== undefined ? row.__rowNum__ + 1 : null;
                                if (rNum && worksheet[colChar + rNum]) rawVal = String(worksheet[colChar + rNum].v || '');
                            }
                            rawVal = rawVal.replace(/[^\d\.\-\(\)]/g, '').trim();
                            if (rawVal.startsWith('(') && rawVal.endsWith(')')) {
                                rawVal = '-' + rawVal.substring(1, rawVal.length - 1);
                            }
                            const parsed = rawVal !== '' && !isNaN(Number(rawVal)) ? Number(rawVal) : 0;
                            return Number.isFinite(parsed) ? parsed : 0;
                        };

                        const seamlessFallbackAmount = Math.max(
                            readSeamlessAmount('Q'),
                            readSeamlessAmount('T'),
                            readSeamlessAmount('U'),
                            readSeamlessAmount('AO'),
                            readSeamlessAmount('AQ')
                        );

                        if (seamlessFallbackAmount > 0) {
                            if (claim <= 0) claim = seamlessFallbackAmount;
                            if (approved <= 0) approved = seamlessFallbackAmount;
                        }
                    }

                    if (isSeamlessFile) {
                        const typeVal = dynamicFundName ? String(dynamicFundName).toLowerCase() : '';
                        const amtToUse = approved || claim || 0;
                        if (typeVal.includes('ckd') || typeVal.includes('๏ฟฝ') || fileSeamlessCategory === 'ckd') rowCkd += amtToUse;
                        else if (typeVal.includes('ktp') || typeVal.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || fileSeamlessCategory === 'ktp') rowKtp += amtToUse;
                        else if (typeVal.includes('napp') || fileSeamlessCategory === 'napp') rowNapp += amtToUse;
                        else if (typeVal.includes('tb') || typeVal.includes('๏ฟฝัณ๏ฟฝรค') || fileSeamlessCategory === 'tb') rowTb += amtToUse;
                        else if (typeVal.includes('๏ฟฝ๏ฟฝวน๏ฟฝ') || fileSeamlessCategory === 'downs') rowDowns += amtToUse;
                        else if (typeVal.includes('๏ฟฝัด๏ฟฝ๏ฟฝอง๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝสซ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || fileSeamlessCategory === 'thal_screen') rowThalScreen += amtToUse;
                        else if (typeVal.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ็งปาก๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || typeVal.includes('cervical') || fileSeamlessCategory === 'cervical') rowCervical += amtToUse;
                        else if (typeVal.includes('๏ฟฝิต๏ฟฝวช') || fileSeamlessCategory === 'psych') rowPsych += amtToUse;
                        else if (typeVal.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝสซ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || fileSeamlessCategory === 'thal_disease') rowThalDisease += amtToUse;
                        else if (typeVal.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || typeVal.includes('rehab') || fileSeamlessCategory === 'rehab') rowRehab += amtToUse;
                        else if (typeVal.includes('๏ฟฝ๏ฟฝอพ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ') || fileSeamlessCategory === 'morprom' || rowHasMorpromRepNo) rowMorprom += amtToUse;
                        else if (typeVal.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝาน') || typeVal.includes('๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝัน') || fileSeamlessCategory === 'ncd') rowNcd += amtToUse;
                        
                        currentFund = 'seamless';
                    }


                    const groupKey = `${rowHospCode}-${rowYear}-${rowMonth}-${currentFund}`;
                    if (!groups[groupKey]) {
                        groups[groupKey] = {
                            fiscalYear: rowYear,
                            month: rowMonth,
                            hospital: rowHospString,
                            fund: currentFund,
                            baseFund: activeTab === 'ppfs_sub' ? 'ppfs_sub' : 'seamless',
                            subFundName: dynamicFundName || '',
                            reportName: dynamicFundName || '',
                            claimAmount: 0,
                            approvedAmount: 0,
                            pendingAmount: 0,
                            errorsMap: {},
                            op_amt: 0,
                            hc_amt: 0,
                            hc_drug_amt: 0,
                            ae_amt: 0,
                            ae_drug_amt: 0,
                            pp_amt: 0,
                            fs_amt: 0,
                            ckd_amt: 0,
                            ktp_amt: 0,
                            napp_amt: 0,
                            tb_amt: 0,
                            downs_amt: 0,
                            thal_screen_amt: 0,
                            cervical_amt: 0,
                            psych_amt: 0,
                            thal_disease_amt: 0,
                            rehab_amt: 0,
                            thai_med_amt: 0, morprom_amt: 0, anc_amt: 0, ncd_amt: 0,
                            cmi_sum: 0, cmi_count: 0
                        };
                    }

                    const g = groups[groupKey];
                    g.claimAmount += claim;
                    g.approvedAmount += approved;
                    if (rowCmi > 0) {
                        g.cmi_sum = (g.cmi_sum || 0) + rowCmi;
                        g.cmi_count = (g.cmi_count || 0) + 1;
                    }
                    
                    if (isPPFSSubFile) {
                        g.op_amt += rowOp;
                        g.hc_amt += rowHc;
                        g.hc_drug_amt += rowHcDrug;
                        g.ae_amt += rowAe;
                        g.ae_drug_amt += rowAeDrug;
                        g.pp_amt += rowPp;
                        g.fs_amt += rowFs;
                    }
                    if (isSeamlessFile) {
                        g.ckd_amt += rowCkd;
                        g.ktp_amt += rowKtp;
                        g.napp_amt += rowNapp;
                        g.tb_amt += rowTb;
                        g.downs_amt += rowDowns;
                        g.thal_screen_amt += rowThalScreen;
                        g.cervical_amt += rowCervical;
                        g.psych_amt += rowPsych;
                        g.thal_disease_amt += rowThalDisease;
                        g.rehab_amt += rowRehab;
                        g.morprom_amt += rowMorprom; g.anc_amt += rowAnc; g.ncd_amt += rowNcd;
                    }

                    if (rowErrorCode && fileFund !== 'smartmoney') { // Smart Money เนเธเธเนเธเธเนเธเธเนเธเธเธญเธเนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเธฅเธ•เธดเธ” C
                        const codes = rowErrorCode.toString().toUpperCase().split(',').map(c => c.trim()).filter(c => c);
                        codes.forEach(code => {
                            g.errorsMap[code] = (g.errorsMap[code] || 0) + 1;
                        });
                    }
                });
                } // End if !isIndividualSheetProcessed

                let groupList = Object.values(groups);
                if (isSeamlessFile && fileSeamlessCategory) {
                    const seamlessProp = `${fileSeamlessCategory}_amt`;
                    groupList.forEach(g => {
                        let fallbackAmt = 0;
                        const currentAmt = Number(g[seamlessProp]);
                        if (!Number.isFinite(currentAmt) || currentAmt === 0) {
                            fallbackAmt = Number(g.subFundAmount || g.approvedAmount || g.claimAmount || 0);
                            if (fallbackAmt > 0) g[seamlessProp] = fallbackAmt;
                        }
                        if ((!g.claimAmount || g.claimAmount === 0) && fallbackAmt > 0) g.claimAmount = fallbackAmt;
                        if ((!g.approvedAmount || g.approvedAmount === 0) && fallbackAmt > 0) g.approvedAmount = fallbackAmt;
                        if ((!g.pendingAmount || g.pendingAmount < 0) && g.claimAmount >= g.approvedAmount) {
                            g.pendingAmount = Math.max(0, (g.claimAmount || 0) - (g.approvedAmount || 0));
                        }
                    });
                }
                console.log('[Upload context]', JSON.stringify({
                    file: file.name,
                    activeTab,
                    fileFund,
                    isSeamlessFile,
                    seamlessContext,
                    indSheetName,
                    sumSheetName,
                    groupCount: groupList.length,
                    groupKeys: Object.keys(groups).slice(0, 5)
                }));

                if (groupList.length === 0 && seamlessContext) {
                    try {
                        console.log('[Seamless fallback] start', {
                            file: file.name,
                            fileFund,
                            fileSeamlessCategory,
                            fileHospCode,
                            sumSheetName,
                            groupCountBefore: Object.keys(groups).length
                        });
                        const seamlessCategoryNames = {
                            ckd: 'CKD',
                            ktp: 'KTP',
                            napp: 'napp',
                            tb: 'TB',
                            downs: 'เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเธงเธเนเธเธ',
                            thal_screen: 'เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเธชเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
                            cervical: 'เนเธเธเธฑเธ”เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเธนเธ',
                            psych: 'เนเธเธเธดเธ•เนเธเธเธงเธ',
                            thal_disease: 'เนเธเธเนเธเธเนเธเธเนเธเธเธชเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
                            rehab: 'เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
                            thai_med: 'เนเธเธ—เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
                            morprom: 'เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธ',
                            anc: 'เนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ',
                            ncd: 'เนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ/เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฑเธ'
                        };
                        const fallbackCategory = fileSeamlessCategory || 'ckd';
                        const fallbackMainName = seamlessCategoryNames[fallbackCategory] || fallbackCategory;
                        const fallbackGroupKey = `${fileHospCode || 'UNKNOWN'}-${fileYear}-${fileMonth}-seamless-fallback-${fallbackCategory}`;
                        let fallbackClaim = 0;
                        let fallbackApproved = 0;
                        let fallbackHospital = fileHospCode !== 'UNKNOWN' ? fileHospCode : '';
                        let fallbackSubFundName = fallbackMainName;
                        if (sumSheetName) {
                            const sumRows = XLSX.utils.sheet_to_json(workbook.Sheets[sumSheetName], { header: "A", defval: '' });
                            const matchedRow = sumRows.find(row => {
                                const fileBase = file.name.replace(/\.xlsx$/i, '').toUpperCase();
                                const rowText = Object.values(row).map(v => String(v || '').toUpperCase()).join(' ');
                                return rowText.includes(fileBase) || rowText.includes(fileHospCode) || rowText.includes(fallbackCategory.toUpperCase());
                            }) || sumRows.find(row => String(row.C || '').trim() || String(row.D || '').trim()) || null;
                            console.log('[Seamless fallback] matchedRow?', !!matchedRow);
                            if (matchedRow) {
                                const rowHospCode = String(matchedRow.C || fileHospCode || '').trim();
                                const rowHospName = String(matchedRow.D || '').trim();
                                const claimRaw = String(matchedRow.I || matchedRow.K || matchedRow.H || '').replace(/,/g, '').trim();
                                const approveRaw = String(matchedRow.K || matchedRow.J || matchedRow.I || '').replace(/,/g, '').trim();
                                console.log('[Seamless fallback] values', { rowHospCode, rowHospName, claimRaw, approveRaw });
                                fallbackHospital = rowHospCode || fallbackHospital || rowHospName || 'UNKNOWN';
                                fallbackSubFundName = rowHospName || fallbackSubFundName;
                                fallbackClaim = parseFloat(claimRaw) || 0;
                                fallbackApproved = parseFloat(approveRaw) || fallbackClaim;
                            }
                        }
                        if (fallbackClaim > 0 || fallbackApproved > 0) {
                            groups[fallbackGroupKey] = {
                                id: fallbackGroupKey,
                                fiscalYear: fileYear,
                                month: fileMonth,
                                hospital: fallbackHospital || 'UNKNOWN',
                                fund: 'seamless',
                                baseFund: 'seamless',
                                subFundName: fallbackSubFundName,
                                mainCategoryName: fallbackMainName,
                                subFundAmount: fallbackApproved || fallbackClaim,
                                claimAmount: fallbackClaim,
                                approvedAmount: fallbackApproved,
                                pendingAmount: Math.max(0, fallbackClaim - fallbackApproved),
                                fileName: file.name,
                                fileBase64: fileBase64,
                                op_amt: 0, hc_amt: 0, hc_drug_amt: 0, ae_amt: 0, ae_drug_amt: 0, pp_amt: 0, fs_amt: 0,
                                ckd_amt: 0, ktp_amt: 0, napp_amt: 0, tb_amt: 0, downs_amt: 0, thal_screen_amt: 0, cervical_amt: 0, psych_amt: 0, thal_disease_amt: 0, rehab_amt: 0, thai_med_amt: 0, morprom_amt: 0, anc_amt: 0, ncd_amt: 0,
                                errorsMap: {}
                            };
                            groups[fallbackGroupKey][`${fallbackCategory}_amt`] = fallbackApproved || fallbackClaim;
                            console.log('[Seamless fallback] created group', fallbackGroupKey, groups[fallbackGroupKey]);
                        } else {
                            console.log('[Seamless fallback] no numeric totals found');
                        }
                    } catch (fallbackErr) {
                        console.warn('Seamless fallback parse failed for', file.name, fallbackErr);
                    }
                }

                groupList = Object.values(groups);

                if (groupList.length === 0) {
                    let debugInfo = `DEBUG INFO:\nFile: ${file.name}\nisSTM: ${isSTM}\nheaderRowIdx: ${headerRowIdx}\n`;
                    if (headerRowIdx >= 0) {
                        debugInfo += `Detected Headers:\n${mergedHeaders.slice(0, 15).join(', ')}\n`;
                        debugInfo += `Row ${headerRowIdx + 1} data:\n${JSON.stringify(rawRows[headerRowIdx + 1] || []).substring(0, 100)}\n`;
                        if (jsonData.length > 0) {
                            debugInfo += `Parsed Key Sample:\n${Object.keys(jsonData[0]).slice(0, 10).join(', ')}\n`;
                        }
                    } else {
                        debugInfo += `Top 3 rows:\n${JSON.stringify(rawRows.slice(0, 3)).substring(0, 200)}`;
                    }
                    console.error(debugInfo);
                    throw new Error(`FILE_PARSE_FAILED:${file.name}`);
                }

                const syncPromises = [];
                groupList.forEach(g => {
                    g.pendingAmount = Math.max(0, g.claimAmount - g.approvedAmount);
                    const hospitalParts = splitHospitalDisplay(g.hospital);
                    const fiscalYearNum = parseInt(g.fiscalYear, 10);
                    const monthNum = parseInt(g.month, 10);
                    
                    // Compile errors
                    let compileErrors = Object.entries(g.errorsMap || {}).map(([code, count]) => {
                        let matchedCatalog = null;
                        const primaryCatalog = ERROR_CATALOG[fileFund] || ERROR_CATALOG[activeTab] || ERROR_CATALOG['op'] || [];
                        matchedCatalog = primaryCatalog.find(c => c.code === code);
                        
                        if (!matchedCatalog) {
                            for (const key in ERROR_CATALOG) {
                                matchedCatalog = ERROR_CATALOG[key].find(c => c.code === code);
                                if (matchedCatalog) break;
                            }
                        }
                        
                        return {
                            code: code,
                            desc: matchedCatalog ? matchedCatalog.desc : code,
                            count: count
                        };
                    });

                    // Only generate fallback mock errors for tabs that don't extract real error data
                    if (compileErrors.length === 0 && activeTab !== 'ip' && activeTab !== 'op') {
                        const catalog = ERROR_CATALOG[fileFund] || ERROR_CATALOG[activeTab] || ERROR_CATALOG['op'] || [];
                        compileErrors = catalog.slice(0, 3).map(err => ({
                            code: err.code,
                            desc: err.desc,
                            count: 3 + Math.round(Math.random() * 15)
                        }));
                    }
                    g.errors = compileErrors;
                    g.id = `upload-${Date.now()}-${file.name}-${g.hospital.split(' - ')[0]}-${g.fiscalYear}-${g.month}`;
                    g.cmi = (g.cmi_count && g.cmi_count > 0) ? parseFloat((g.cmi_sum / g.cmi_count).toFixed(4)) : 0;
                    g.fileName = g.fileName || file.name;

                    // Fill defaults for non PPFS Sub activities
                    if (!isPPFSSubFile) {
                        g.op_amt = Math.round(g.approvedAmount * 0.25);
                        g.hc_amt = Math.round(g.approvedAmount * 0.15);
                        g.hc_drug_amt = Math.round(g.approvedAmount * 0.1);
                        g.ae_amt = Math.round(g.approvedAmount * 0.15);
                        g.ae_drug_amt = Math.round(g.approvedAmount * 0.05);
                        g.pp_amt = Math.round(g.approvedAmount * 0.15);
                        g.fs_amt = Math.round(g.approvedAmount * 0.15);
                    }

                    database.push(g);

                    lastUploadedYear = g.fiscalYear;
                    lastUploadedFund = g.fund || g.baseFund;

                    // Sync database for this grouped record
                    if (currentSession && currentSession.token !== 'mock-sandbox-token') {
                        if (!hospitalParts.code || !Number.isFinite(fiscalYearNum) || !Number.isFinite(monthNum) || !g.fund) {
                            console.warn('[Sync skip] Missing required upload fields', {
                                hospital: g.hospital,
                                fiscalYear: g.fiscalYear,
                                month: g.month,
                                fund: g.fund
                            });
                            return;
                        }

                        const uploadBody = {
                            hospitalCode: hospitalParts.code,
                            hospitalName: hospitalParts.name || g.hospital,
                            fiscalYear: fiscalYearNum,
                            monthNum: monthNum,
                            subFundId: g.fund,
                            menuKey: deriveUploadMenuKey(g, file.name),
                            claimAmount: g.claimAmount,
                            approvedAmount: g.approvedAmount,
                            pendingAmount: g.pendingAmount,
                            errors: g.errors,
                            op_amt: g.op_amt,
                            hc_amt: g.hc_amt,
                            hc_drug_amt: g.hc_drug_amt,
                            ae_amt: g.ae_amt,
                            ae_drug_amt: g.ae_drug_amt,
                            pp_amt: g.pp_amt,
                            fs_amt: g.fs_amt,
                            ckd_amt: g.ckd_amt,
                            ktp_amt: g.ktp_amt,
                            napp_amt: g.napp_amt,
                            tb_amt: g.tb_amt,
                            downs_amt: g.downs_amt,
                            thal_screen_amt: g.thal_screen_amt,
                            cervical_amt: g.cervical_amt,
                            psych_amt: g.psych_amt,
                            thal_disease_amt: g.thal_disease_amt,
                            rehab_amt: g.rehab_amt,
                            morprom_amt: g.morprom_amt, anc_amt: g.anc_amt, ncd_amt: g.ncd_amt,
                            fileName: file.name,
                            fileBase64: g.fileBase64
                        };

                        const syncPromise = fetch(`${BACKEND_URL}/dashboard/upload`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${currentSession.token}`
                            },
                            body: JSON.stringify(uploadBody)
                        })
                        .then(res => res.json())
                        .then(resData => {
                            if (resData.status === 'success') {
                                console.log(resData.message || `๏ฟฝัน๏ฟฝึกลง MySQL ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ: ${hospitalParts.code}`);
                                console.log('MySQL upload result:', resData);
                            }
                        })
                        .catch(err => {
                            console.error("Sync error:", err);
                        });
                        syncPromises.push(syncPromise);
                    }

                });

                processedCount++;

                if (processedCount === files.length) {
                    const finalizeUpload = () => {
                        if (files[0] && files[0].name.toLowerCase().includes('stm')) {
                            const sNames = workbook.SheetNames.join(', ');
                            let sHeaders = 'None';
                            if (jsonData && jsonData.length > 0) {
                                 sHeaders = Object.keys(jsonData[0]).slice(0,15).join(', ');
                            }
                            alert(`DEBUG:\nSheets: ${sNames}\nHeaders: ${sHeaders}`);
                        }
                        
                        showToast(`เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ—เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ ${files.length} เนเธเธเนเธเธเนเธเธ เนเธเธเธณเธเนเธเธเธฃเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธขเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเธขเธเธฒเธเนเธเธเนเธเธเธดเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“เนเธเธเนเธเธเนเธเธเธขเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ`);
                        recordAppLog('success', 'upload-complete', `เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ ${files.length} เนเธเธเนเธเธเนเธเธ`, {
                            fileName: file.name,
                            fund: fileFund,
                            fiscalYear: fileYear,
                            month: fileMonth,
                            tab: activeTab,
                            details: {
                                totalFiles: files.length,
                                totalGroups: groupList.length,
                                totalRecords: database.length
                            }
                        });
                        saveDatabaseToLocalStorage();
                        syncUploadHistoryFromServer();

                        setSelectedDashboardFileValue(file.name);
                        if (document.getElementById('dashboard-file-select')) document.getElementById('dashboard-file-select').value = file.name;
                        if (activeTab === 'seamless' && fileSeamlessCategory) {
                            const seamlessSubtabName = formatUploadMenuLabel(fileSeamlessCategory);
                            window.currentSeamlessSubtab = fileSeamlessCategory;
                            window.currentSeamlessSubtabName = seamlessSubtabName;
                            try { localStorage.setItem('nhso_current_seamless_subtab', fileSeamlessCategory); } catch (e) {}
                            try { localStorage.setItem('nhso_current_seamless_subtab_name', seamlessSubtabName); } catch (e) {}
                            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.seamlessSubtab, fileSeamlessCategory);
                            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.seamlessSubtabName, seamlessSubtabName);
                            saveSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtab, fileSeamlessCategory);
                            saveSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtabName, seamlessSubtabName);
                        }

                        setSelectedDashboardFileValue(file.name);
                        if (document.getElementById('dashboard-file-select')) document.getElementById('dashboard-file-select').value = file.name;
                        
                        document.getElementById('hospital-input').value = "";
                        
                        if (lastUploadedYear) {
                            updateYearDropdown();
                            document.getElementById('year-select').value = String(lastUploadedYear);
                        }
                        
                        if (lastUploadedFund) {
                            let targetTab = lastUploadedFund;
                            if (lastUploadedFund === 'OPBKK' || lastUploadedFund.toLowerCase() === 'op') targetTab = 'op';
                            else if (lastUploadedFund === 'IPBKK' || lastUploadedFund.toLowerCase() === 'ip') targetTab = 'ip';
                            else if (lastUploadedFund === 'ppfs_sub' || lastUploadedFund.toLowerCase() === 'ppfs_sub') targetTab = 'ppfs_sub';
                            else if (['PP', 'OPAE', 'OPFS'].includes(lastUploadedFund)) targetTab = 'ppfs';
                            
                            handleTabChange(targetTab);
                            refreshDashboard();
                        } else {
                            refreshDashboard();
                        }

                        if (lastUploadedYear) {
                            const yearSelectEl = document.getElementById('year-select');
                            if (yearSelectEl) yearSelectEl.value = String(lastUploadedYear);
                            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.yearSelect, String(lastUploadedYear));
                            saveSharedUiPreference(SHARED_PREF_KEYS.yearSelect, String(lastUploadedYear));
                        }

                        setSelectedDashboardFileValue(file.name);
                        const dashboardFileSelectEl = document.getElementById('dashboard-file-select');
                        if (dashboardFileSelectEl) dashboardFileSelectEl.value = file.name;
                        refreshDashboard();
                    };

                    if (syncPromises.length > 0) {
                        Promise.allSettled(syncPromises).then(finalizeUpload);
                    } else {
                        finalizeUpload();
                    }
                }

            } catch (err) {
                console.error(err);
                alert(`เนเธเธเธดเธ”เนเธเธเนเธเธเธญเธเธดเธ”เนเธเธเนเธเธเธฒเธ”เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธ ${file.name}:\n\n${err.message}\n\nเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเธฒเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฑเธ’เนเธเธเธฒเธเนเธเธเธฑเธ`);
                showToast(`Error ${file.name}: ${err.message}`, false);
                recordAppLog('error', 'upload-error', `เนเธเธเธดเธ”เนเธเธเนเธเธเธญเธเธดเธ”เนเธเธเนเธเธเธฒเธ”เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธ ${file.name}: ${err.message}`, {
                    fileName: file.name,
                    tab: activeTab,
                    details: err.stack || err.message
                });
                fetch(`${BACKEND_URL}/dashboard/log`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ error: err.stack || err.message, file: file.name })
                }).catch(e => console.error(e));
            }
        };
        reader.readAsArrayBuffer(file);
    };
    base64Reader.readAsDataURL(file);
    });
}

// Client-side Excel Exporting
function handleFileExport(format = 'xlsx') {
    const filteredData = getFilteredData();
    if (filteredData.length === 0) {
        showToast("เนเธเธเนเธเธเนเธเธเนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธญเธ", false);
        return;
    }

    let exportRows = [];
    if (activeTab === 'overview') {
        const aggregations = {};
        Object.keys(FUNDS).forEach(fundKey => {
            aggregations[fundKey] = {
                'กองทุน': FUNDS[fundKey],
                'ยอดเบิก (บาท)': 0,
                'ยอดอนุมัติ (บาท)': 0,
                'ยอด C / รอการจ่ายชดเชย (บาท)': 0,
                'อัตราอนุมัติ (%)': 0
            };
        });

        filteredData.forEach(item => {
            if (aggregations[item.fund]) {
                aggregations[item.fund]['ยอดเบิก (บาท)'] += item.claimAmount;
                aggregations[item.fund]['ยอดอนุมัติ (บาท)'] += item.approvedAmount;
                aggregations[item.fund]['ยอด C / รอการจ่ายชดเชย (บาท)'] += item.pendingAmount;
            }
        });

        exportRows = Object.values(aggregations).map(row => {
            const claim = row['ยอดเบิก (บาท)'];
            const approve = row['ยอดอนุมัติ (บาท)'];
            row['อัตราอนุมัติ (%)'] = claim > 0 ? parseFloat(((approve / claim) * 100).toFixed(2)) : 0;
            return row;
        });
    } else {
        exportRows = filteredData.map(item => {
            let topError = "ไม่ระบุ";
            if (item.errors && item.errors.length > 0) {
                const sorted = [...item.errors].sort((a,b) => b.count - a.count);
                if (sorted[0]) topError = `${sorted[0].code}: ${sorted[0].desc}`;
            }
            return {
                'โรงพยาบาล': item.hospital,
                'กองทุน': FUNDS[item.fund],
                'ปีงบประมาณ': item.fiscalYear,
                'เดือน': getThaiMonthNameFromCalendarMonth(item.month, item.fiscalYear),
                'ยอดเบิก (บาท)': item.claimAmount,
                'ยอดอนุมัติ (บาท)': item.approvedAmount,
                'ยอด C (บาท)': item.pendingAmount,
                'ข้อผิดพลาดสูงสุด': topError
            };
        });
    }

    const ws = XLSX.utils.json_to_sheet(exportRows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "NHSO Report");
    XLSX.writeFile(wb, `NHSO_Executive_Report_${activeTab.toUpperCase()}.${format}`);
    showToast(`เนเธเธเนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ!`);
}

// Print Handler
function handlePrint() {
    window.print();
}

// Google Drive Sync Callback
// =========================================================
// Authenticated and RBAC Logic Actions
// =========================================================
// Public Registration Controller (from login screen)
async function publicRegisterUser(e) {
    e.preventDefault();
    const username = document.getElementById('reg-username').value.trim();
    const password = document.getElementById('reg-password').value.trim();
    const fullName = document.getElementById('reg-fullname').value.trim();
    const phone = document.getElementById('reg-phone').value.trim();
    const position = document.getElementById('reg-position').value.trim() || 'เจ้าหน้าที่';
    const role = document.getElementById('reg-role').value;

    if (!username || !password || !fullName || !phone || !position) {
        showToast('กรุณากรอกข้อมูลให้ครบถ้วน', false);
        return;
    }

    try {
        const res = await fetch(`${BACKEND_URL}/auth/register`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password, fullName, phone, position, role })
        });
        const result = await res.json();

        if (result.status === 'success') {
            showToast('ลงทะเบียนสำเร็จ');
            recordAppLog('success', 'public-register', `ลงทะเบียนผู้ใช้ ${username}`, {
                user: username,
                tab: activeTab
            });
            document.getElementById('register-form').reset();
            document.getElementById('register-form').style.display = 'none';
            document.getElementById('login-form').style.display = 'block';
        } else {
            showToast(result.message || 'ไม่สำเร็จ', false);
        }
    } catch (err) {
        mockRegisteredUsers[username] = { fullName, role, password, phone, position, status: 'pending' };
        showToast('ลงทะเบียนสำเร็จ (Sandbox)');
        recordAppLog('warn', 'public-register-fallback', `ลงทะเบียนผู้ใช้ ${username} (Sandbox)`, {
            user: username,
            tab: activeTab
        });
        document.getElementById('register-form').reset();
        document.getElementById('register-form').style.display = 'none';
        document.getElementById('login-form').style.display = 'block';
    }
}

// Run check backend server activity
async function checkBackendStatus() {
    try {
        const res = await fetch(`${BACKEND_URL}/health`);
        isBackendActive = res.ok;
    } catch (err) {
        isBackendActive = false;
    }
}

// Login verification controller
async function handleLogin(username, password) {
    try {
        const response = await fetch(`${BACKEND_URL}/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        const result = await response.json();

        if (result.status === 'success') {
            storeSession(result.token, result.user);
            await syncSharedUiPreferencesFromServer();
            await loadDashboardData();
            handleTabChange(activeTab);
            await syncUploadHistoryFromServer();
            return true;
        }

        showToast(result.message || 'ไม่สำเร็จ', false);
        return false;
    } catch (err) {
        console.warn('Backend server connection failed, entering client Sandbox authentication.');
        const mockUsers = {
            admin: { fullName: 'ผู้ดูแลระบบ (Sandbox)', role: 'ADMIN', password: 'CHANGE_ME_BEFORE_USE' },
            officer: { fullName: 'เจ้าหน้าที่ (Sandbox)', role: 'OFFICER', password: 'CHANGE_ME_BEFORE_USE' },
            executive: { fullName: 'ผู้บริหาร (Sandbox)', role: 'EXECUTIVE', password: 'CHANGE_ME_BEFORE_USE' },
            user: { fullName: 'ผู้ใช้งานทั่วไป (Sandbox)', role: 'USER', password: 'CHANGE_ME_BEFORE_USE' },
            ...mockRegisteredUsers
        };

        const localUser = mockUsers[username];
        if (!localUser) {
            showToast('ไม่พบผู้ใช้งาน', false);
            return false;
        }
        if (localUser.password !== password) {
            showToast('รหัสผ่านไม่ถูกต้อง', false);
            return false;
        }

        const localStatus = String(localUser.status || 'approved').toLowerCase();
        if (localStatus === 'disabled') {
            showToast('บัญชีผู้ใช้นี้ถูกปิดการใช้งาน', false);
            return false;
        }

        storeSession("mock-sandbox-token", { username, fullName: localUser.fullName, role: localUser.role });
        await syncSharedUiPreferencesFromServer();
        await loadDashboardData();
        handleTabChange(activeTab);
        showToast('เข้าสู่ระบบ Sandbox สำเร็จ');
        return true;
    }
}

function storeSession(token, user) {
    currentSession = { token, ...user };
    localStorage.setItem('nhso_token', token);
    localStorage.setItem('nhso_user', JSON.stringify(user));
    startDashboardSyncPolling();
    
    applyRBACLayout(user);
    document.getElementById('login-overlay').className = 'login-overlay-hidden';
    recordAppLog('success', 'login', `เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ ${user?.username || ''}`, {
        user: user?.username || '',
        tab: activeTab
    });
}

function checkActiveSession() {
    const token = localStorage.getItem('nhso_token');
    const userStr = localStorage.getItem('nhso_user');
    
    if (token && userStr) {
        currentSession = { token, ...JSON.parse(userStr) };
        startDashboardSyncPolling();
        validateCurrentSession().then((ok) => {
            if (!ok) return;
            document.getElementById('login-overlay').className = 'login-overlay-hidden';
            syncSharedUiPreferencesFromServer().then(() => {
                loadDashboardData();
                handleTabChange(activeTab);
                syncUploadHistoryFromServer();
            });
        });
    } else {
        document.getElementById('login-overlay').className = 'login-overlay-visible';
    }
}

function applyRBACLayout(user) {
    document.body.className = `role-${user.role.toLowerCase()}`;
    document.getElementById('user-display-name').textContent = getDisplayFullName(user.username, user.fullName);
    
    const badge = document.getElementById('user-display-role');
    badge.textContent = user.role;
    badge.className = `user-role-badge ${user.role.toLowerCase()}`;
    
    const btnImport = document.getElementById('btn-import-wrapper');
    if (user.role === 'EXECUTIVE' || user.role === 'USER') {
        btnImport.style.display = 'none';
    } else {
        btnImport.style.display = 'block';
    }

    document.querySelectorAll('.sys-admin-only').forEach(el => {
        if (user.role === 'ADMIN') {
            el.classList.remove('sys-admin-only');
        }
    });
}

// Fetch dashboard dataset from MySQL backend or use Mock
async function loadDashboardData() {
    let success = false;
    const localDbRaw = localStorage.getItem('nhso_database');
    let cachedDb = null;
    if (localDbRaw) {
        try {
            cachedDb = normalizeLoadedDatabase(extractDatabaseStorageRecords(JSON.parse(localDbRaw)));
        } catch (e) {
            cachedDb = null;
        }
    }
    
    if (currentSession && currentSession.token !== 'mock-sandbox-token') {
        try {
            const res = await fetch(`${BACKEND_URL}/dashboard/data`, {
                headers: { 'Authorization': `Bearer ${currentSession.token}` }
            });
            const result = await res.json();
            if (res.status === 401 || res.status === 403) {
                throw new Error('session_invalid');
            }
            if (result.status === 'success') {
                const serverData = Array.isArray(result.data) ? normalizeLoadedDatabase(result.data) : [];
                if (serverData.length > 0) {
                    database = serverData;
                    localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database)));
                    console.log("Loaded data from MySQL Server Database.");
                } else if (cachedDb && cachedDb.length > 0) {
                    database = cachedDb;
                    localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database)));
                    console.log("Loaded dashboard database from localStorage fallback. Count:", database.length);
                } else {
                    database = [];
                    localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database)));
                    console.log("Loaded empty dashboard dataset.");
                }
                success = true;
            } else {
                if (cachedDb && cachedDb.length > 0) {
                    database = cachedDb;
                    localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database)));
                    console.log("Loaded dashboard database from localStorage fallback. Count:", database.length);
                } else {
                    database = [];
                    localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database)));
                }
                success = true;
                console.log('MySQL database is empty.');
            }
        } catch (err) {
            console.error("Failed fetching database dashboard data from backend.", err);
            if (String(err?.message || '').includes('session_invalid')) {
                clearPersistedSession();
                database = [];
                saveDatabaseToLocalStorage();
                document.getElementById('login-overlay').className = 'login-overlay-visible';
                showToast('session เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ เนเธเธเนเธเธเธธเธ“เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ', false);
            } else {
                database = [];
                saveDatabaseToLocalStorage();
                showToast('เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฃเธ–เนเธเธเนเธเธเธฒเธเนเธเธเธฒเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธ เนเธเธเนเธเธเธธเธ“เธฒเธ•เนเธเธเธงเธเนเธเธเธญเธ Host Backend/เนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ', false);
            }
        }
    }
    
    if (!success) {
        if (currentSession && currentSession.token !== 'mock-sandbox-token') {
            if (cachedDb && cachedDb.length > 0) {
                database = cachedDb;
                localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database)));
                console.log("Authenticated session fell back to localStorage. Count:", database.length);
            } else {
                database = [];
                saveDatabaseToLocalStorage();
                console.log("Authenticated session requires MySQL data; using empty dataset until backend is available.");
            }
        } else if (cachedDb && cachedDb.length > 0) {
            database = cachedDb;
            localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database)));
            console.log("Loaded dashboard database from localStorage. Count:", database.length);
        } else {
            database = [];
            saveDatabaseToLocalStorage();
            console.log("Loaded empty local dataset.");
        }
    }
    
    renderUploadHistory();
    refreshDashboard();
}

// User manager register controller (Admin Only)
async function registerUser(e) {
    e.preventDefault();
    const form = e.currentTarget || document.getElementById('register-user-form');
    const username = form.querySelector('#reg-username').value.trim();
    const password = form.querySelector('#reg-password').value.trim();
    const fullName = form.querySelector('#reg-fullname').value.trim();
    const phone = (form.querySelector('#reg-phone')?.value || '').trim() || '0000000000';
    const position = (form.querySelector('#reg-position')?.value || '').trim() || '๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝาน๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ';
    const role = form.querySelector('#reg-role').value;
    
    if (currentSession.token === 'mock-sandbox-token') {
        mockRegisteredUsers[username] = { fullName, role, password, phone, position, status: 'approved' };
        showToast(`เนเธเธเธฑเธเนเธเธเธถเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ (เนเธเธเนเธเธเนเธเธเธญเธ): เนเธเธเธฑเธเนเธเธเนเธเธ ${username} เนเธเธเนเธเธเนเธเธเนเธเธเธฑเธเนเธเธเธถเธเนเธเธเนเธเธเนเธเธเนเธเธ`);
        recordAppLog('success', 'admin-register-fallback', `เนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเธฑเธเนเธเธเนเธเธ ${username} เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธญเธ`, {
            user: username,
            tab: activeTab
        });
        form.reset();
        loadUsersList();
        return;
    }

    try {
        const res = await fetch(`${BACKEND_URL}/auth/register`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentSession.token}`
            },
            body: JSON.stringify({ username, password, fullName, phone, position, role })
        });
        const result = await res.json();
        
        if (result.status === 'success') {
            showToast("เนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเธฑเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ");
            recordAppLog('success', 'admin-register', `เนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเธฑเธเนเธเธเนเธเธ ${username} เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ`, {
                user: username,
                tab: activeTab
            });
            form.reset();
            loadUsersList();
        } else {
            showToast(result.message, false);
        }
    } catch (err) {
        showToast("เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฃเธ–เนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ Sandbox Mode", false);
        recordAppLog('error', 'admin-register-error', `เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฃเธ–เนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเธฑเธเนเธเธเนเธเธ ${username} เนเธเธเนเธเธ`, {
            user: username,
            tab: activeTab,
            details: err.message
        });
    }
}

function getUserStatusLabelHtml(status) {
    if (status === 'pending') return '<span style="color:#f59e0b; font-weight:600;">รออนุมัติ</span>';
    if (status === 'rejected') return '<span style="color:#ef4444; font-weight:600;">ปฏิเสธแล้ว</span>';
    if (status === 'disabled') return '<span style="color:#6b7280; font-weight:600;">ปิดการใช้งาน</span>';
    return '<span style="color:#10b981; font-weight:600;">อนุมัติแล้ว</span>';
}

function renderUserActionButtons(username, status) {
    const safeUsername = escapeHtml(String(username || ''));
    const safeStatus = escapeHtml(String(status || 'approved'));
    const disableLabel = status === 'disabled' ? 'เปิดใช้งาน' : 'ปิดการใช้งาน';
    const disableAction = status === 'disabled' ? 'enable' : 'disable';
    const manageBtn = `<button type="button" data-user-action="manage" data-username="${safeUsername}" data-status="${safeStatus}" onclick="handleUserAction(this.dataset.userAction, this.dataset.username, this.dataset.status)" style="background:#6366f1; color:white; border:none; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; margin-right:4px;">จัดการ</button>`;
    const disableBtn = `<button type="button" data-user-action="${disableAction}" data-username="${safeUsername}" data-status="${safeStatus}" onclick="handleUserAction(this.dataset.userAction, this.dataset.username, this.dataset.status)" style="background:#6b7280; color:white; border:none; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; margin-right:4px;">${disableLabel}</button>`;
    const resetBtn = `<button type="button" data-user-action="reset-password" data-username="${safeUsername}" onclick="handleUserAction(this.dataset.userAction, this.dataset.username, this.dataset.status)" style="background:#f59e0b; color:white; border:none; padding:5px 10px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600; margin-right:4px;">รีเซ็ตรหัสผ่าน</button>`;
    if (status === 'pending') {
        return `
            <div style="display:inline-flex; gap:4px; flex-wrap:wrap; justify-content:center;">
                ${manageBtn}
                ${disableBtn}
                ${resetBtn}
            </div>`;
    }
    return `
        <div style="display:inline-flex; gap:4px; flex-wrap:wrap; justify-content:center;">
            ${manageBtn}
            ${disableBtn}
            ${resetBtn}
        </div>`;
}

async function loadUsersList() {
    if (currentSession.token === 'mock-sandbox-token') {
        const tbody = document.querySelector('#users-admin-table tbody');
        tbody.innerHTML = '';
        Object.entries(mockSystemUsers).forEach(([uname, user]) => {
            tbody.innerHTML += `
                <tr>
                    <td><strong>${escapeHtml(uname)}</strong></td>
                    <td>${getDisplayFullName(uname, user.fullName)}</td>
                    <td><span class="user-role-badge ${escapeHtml(String(user.role || '').toLowerCase())}">${escapeHtml(String(user.role || ''))}</span></td>
                    <td style="text-align: center;">${getUserStatusLabelHtml(user.status)}</td>
                    <td style="text-align: center;">${renderUserActionButtons(uname, user.status)}</td>
                </tr>
            `;
        });
        // Append newly registered mock users
        Object.keys(mockRegisteredUsers).forEach(uname => {
            const u = mockRegisteredUsers[uname];
            tbody.innerHTML += `
                <tr>
                    <td><strong>${uname}</strong></td>
                    <td>${getDisplayFullName(uname, u.fullName)}</td>
                    <td><span class="user-role-badge ${u.role.toLowerCase()}">${u.role}</span></td>
                    <td style="text-align: center;">${getUserStatusLabelHtml(u.status)}</td>
                    <td style="text-align: center;">${renderUserActionButtons(uname, u.status)}</td>
                </tr>
            `;
        });
        return;
    }

    try {
        const res = await fetch(`${BACKEND_URL}/admin/users`, {
            headers: { 'Authorization': `Bearer ${currentSession.token}` }
        });
        const result = await res.json();
        if (result.status === 'success') {
            const tbody = document.querySelector('#users-admin-table tbody');
            tbody.innerHTML = '';
            result.users.forEach(u => {
                tbody.innerHTML += `
                    <tr>
                        <td><strong>${u.username}</strong></td>
                        <td>${getDisplayFullName(u.username, u.full_name)}</td>
                        <td><span class="user-role-badge ${u.role.toLowerCase()}">${u.role}</span></td>
                        <td style="text-align: center;">${getUserStatusLabelHtml(u.status)}</td>
                        <td style="text-align: center;">${renderUserActionButtons(u.username, u.status)}</td>
                    </tr>
                `;
            });
        }
    } catch (err) {
        console.error("Users list load failed", err);
    }
}

async function approveUser(username) {
    if (!confirm(`คุณต้องการอนุมัติผู้ใช้ ${username} ใช่หรือไม่?`)) return;
    updateUserStatus(username, 'approved');
}

async function rejectUser(username) {
    if (!confirm(`คุณต้องการปฏิเสธผู้ใช้ ${username} ใช่หรือไม่?`)) return;
    updateUserStatus(username, 'rejected');
}

function toggleUserAccess(username, currentStatus) {
    const normalizedCurrent = String(currentStatus || 'approved').toLowerCase();
    const nextStatus = normalizedCurrent === 'disabled' ? 'approved' : 'disabled';
    const label = nextStatus === 'disabled' ? 'ปิดการใช้งาน' : 'เปิดการใช้งาน';
    if (!confirm(`คุณต้องการ ${label} ผู้ใช้ ${username} ใช่หรือไม่?`)) return;
    updateUserStatus(username, nextStatus);
}

async function resetUserPassword(username) {
    if (!confirm(`คุณต้องการรีเซ็ตรหัสผ่านผู้ใช้ ${username} ใช่หรือไม่?`)) return;

    if (currentSession.token === 'mock-sandbox-token') {
        const tempPassword = 'CHANGE_ME_BEFORE_USE';
        if (mockSystemUsers[username]) {
            mockSystemUsers[username].password = tempPassword;
            showToast(`รีเซ็ตรหัสผ่านผู้ใช้ ${username} เป็น ${tempPassword}`);
            return;
        }
        if (mockRegisteredUsers[username]) {
            mockRegisteredUsers[username].password = tempPassword;
            showToast(`รีเซ็ตรหัสผ่านผู้ใช้ ${username} เป็น ${tempPassword}`);
            return;
        }
        showToast('รีเซ็ตรหัสผ่านเรียบร้อยแล้ว', false);
        return;
    }

    try {
        const res = await fetch(`${BACKEND_URL}/admin/users/${encodeURIComponent(username)}/reset-password`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentSession.token}`
            }
        });
        const result = await res.json();
        if (result.status === 'success') {
            showToast(`เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ขเน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ข ${username} เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ${result.temp_password ? ` เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝ: ${result.temp_password}` : ''}`);
            return;
        }
        showToast(result.message || 'เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ขเน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ', false);
    } catch (err) {
        console.error('resetUserPassword failed', err);
        showToast('เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ขเน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ', false);
    }
}

function handleUserAction(action, username, currentStatus) {
    const normalizedAction = String(action || '').trim().toLowerCase();
    const safeUsername = String(username || '').trim();
    const safeStatus = String(currentStatus || 'approved').trim();

    if (!safeUsername) return;
    if (normalizedAction === 'manage') {
        manageUser(safeUsername, safeStatus);
        return;
    }
    if (normalizedAction === 'approve') {
        approveUser(safeUsername);
        return;
    }
    if (normalizedAction === 'reject') {
        rejectUser(safeUsername);
        return;
    }
    if (normalizedAction === 'disable' || normalizedAction === 'enable') {
        toggleUserAccess(safeUsername, safeStatus);
        return;
    }
    if (normalizedAction === 'reset-password') {
        resetUserPassword(safeUsername);
        return;
    }
}

function manageUser(username, currentStatus) {
    const allowedStatuses = ['approved', 'pending', 'rejected', 'disabled'];
    const normalizedCurrent = allowedStatuses.includes(String(currentStatus || '').toLowerCase())
        ? String(currentStatus || '').toLowerCase()
        : 'approved';
    openUserStatusSelector(username, normalizedCurrent);
}

function openUserStatusSelector(username, currentStatus) {
    const allowedStatuses = ['approved', 'pending', 'rejected', 'disabled'];
    const normalizedCurrent = allowedStatuses.includes(String(currentStatus || '').toLowerCase())
        ? String(currentStatus || '').toLowerCase()
        : 'approved';

    const existingModal = document.getElementById('user-status-modal-overlay');
    if (existingModal) {
        existingModal.remove();
    }

    const overlay = document.createElement('div');
    overlay.id = 'user-status-modal-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.style.cssText = [
        'position:fixed',
        'inset:0',
        'z-index:99999',
        'background:rgba(15,23,42,0.58)',
        'display:flex',
        'align-items:center',
        'justify-content:center',
        'padding:20px'
    ].join(';');

    overlay.innerHTML = `
        <div style="width:min(92vw, 460px); background:#ffffff; border-radius:18px; box-shadow:0 30px 60px rgba(15,23,42,0.28); overflow:hidden; border:1px solid rgba(148,163,184,0.25);">
            <div style="padding:22px 24px 14px; border-bottom:1px solid rgba(148,163,184,0.18);">
                <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:6px;">จัดการบัญชีผู้ใช้ ${escapeHtml(String(username || ''))}</div>
                <div style="font-size:13px; color:#64748b;">เลือกสถานะของบัญชีผู้ใช้งานนี้</div>
            </div>
            <div style="padding:20px 24px 24px;">
                <label for="user-status-modal-select" style="display:block; font-size:14px; font-weight:600; color:#334155; margin-bottom:10px;">สถานะ</label>
                <select id="user-status-modal-select" style="width:100%; min-height:48px; border:1px solid #cbd5e1; border-radius:12px; padding:0 14px; font-size:15px; color:#0f172a; background:#fff;">
                    <option value="approved">approved - อนุมัติแล้ว</option>
                    <option value="pending">pending - รอการอนุมัติ</option>
                    <option value="rejected">rejected - ปฏิเสธ</option>
                    <option value="disabled">disabled - ปิดการใช้งาน</option>
                </select>
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:22px;">
                    <button type="button" id="user-status-modal-cancel" style="background:#ffffff; color:#334155; border:1px solid #cbd5e1; padding:10px 16px; border-radius:12px; cursor:pointer; font-size:14px; font-weight:600;">ยกเลิก</button>
                    <button type="button" id="user-status-modal-save" style="background:#2563eb; color:white; border:none; padding:10px 18px; border-radius:12px; cursor:pointer; font-size:14px; font-weight:700;">บันทึก</button>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);

    const select = overlay.querySelector('#user-status-modal-select');
    const cancelBtn = overlay.querySelector('#user-status-modal-cancel');
    const saveBtn = overlay.querySelector('#user-status-modal-save');
    select.value = normalizedCurrent;

    const closeModal = () => {
        document.removeEventListener('keydown', handleKeydown);
        overlay.remove();
    };

    function handleKeydown(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    }

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeModal();
        }
    });

    cancelBtn.addEventListener('click', closeModal);
    saveBtn.addEventListener('click', () => {
        const selectedStatus = String(select.value || '').trim().toLowerCase();
        if (!allowedStatuses.includes(selectedStatus)) {
            showToast('เน€เธ๏ฟฝเน€เธเธเน€เธเธเน€เธโ€เน€เธเธ’เน€เธโฌเน€เธเธ…เน€เธเธ—เน€เธเธเน€เธ๏ฟฝเน€เธเธเน€เธโ€“เน€เธเธ’เน€เธ๏ฟฝเน€เธเธเน€เธโ€”เน€เธเธ•เน€เธ๏ฟฝเน€เธโ€“เน€เธเธเน€เธ๏ฟฝเน€เธโ€ขเน€เธ๏ฟฝเน€เธเธเน€เธ๏ฟฝ', false);
            return;
        }
        if (selectedStatus === normalizedCurrent) {
            showToast(`เน€เธ๏ฟฝเน€เธเธ‘เน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธเธ• ${username} เน€เธเธเน€เธเธเน€เธเธเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธเธเน€เธโ€“เน€เธเธ’เน€เธ๏ฟฝเน€เธเธเน€เธโฌเน€เธโ€เน€เธเธ”เน€เธเธเน€เธ๏ฟฝเน€เธเธ…เน€เธ๏ฟฝเน€เธเธ`, false);
            closeModal();
            return;
        }
        closeModal();
        updateUserStatus(username, selectedStatus);
    });

    document.addEventListener('keydown', handleKeydown);
    setTimeout(() => select.focus(), 0);
}

async function updateUserStatus(username, status) {
    if (currentSession.token === 'mock-sandbox-token') {
        if (mockSystemUsers[username]) {
            mockSystemUsers[username].status = status;
            showToast(`เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ข ${username} เน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ ${status} เน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ขเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝ (เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธโ€ฆเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ)`);
            loadUsersList();
            return;
        }
        if (mockRegisteredUsers[username]) {
            mockRegisteredUsers[username].status = status;
            showToast(`เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ข ${username} เน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ ${status} เน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ขเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝ (เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธโ€ฆเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ)`);
            loadUsersList();
        }
        return;
    }

    try {
        const res = await fetch(`${BACKEND_URL}/admin/users/${username}/status`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentSession.token}`
            },
            body: JSON.stringify({ status })
        });
        const result = await res.json();

        if (result.status === 'success') {
            showToast(`เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ข ${username} เน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ ${status} เน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ขเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝ`);
            loadUsersList();
        } else {
            showToast(result.message || 'เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ขเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ', false);
        }
    } catch (err) {
        showToast('เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน๏ฟฝเธเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌเธเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌโ€เน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€เน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเน€เธโ€ขเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝเน€เธโฌเน€เธ๏ฟฝเนโฌ๏ฟฝเน€เธโฌเน€เธ๏ฟฝเธข๏ฟฝ', false);
    }
}

// Log out handler
function logoutUser() {
    recordAppLog('info', 'logout', 'เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธญเธเนเธเธเธฒเธเนเธเธเธฐเธเนเธเธ', {
        user: currentSession?.username || ''
    });
    stopDashboardSyncPolling();
    localStorage.removeItem('nhso_token');
    localStorage.removeItem('nhso_user');
    currentSession = null;
    document.getElementById('login-overlay').className = 'login-overlay-visible';
    document.getElementById('login-form').reset();
    showToast("เนเธเธเธญเธเนเธเธเธฒเธเนเธเธเธฐเธเนเธเธเนเธเธเนเธเธเนเธเธเธขเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ");
}

// Render list of manually added fiscal years
function loadManagedYears() {
    const table = document.getElementById('managed-years-table');
    if (!table) return;
    const tbody = table.querySelector('tbody');
    tbody.innerHTML = '';
    
    // Sort years descending
    const sorted = [...managedYears].sort((a, b) => parseInt(b) - parseInt(a));
    
    if (sorted.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center">เนเธเธเนเธเธเนเธเธเนเธเธเธตเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเนเธเธเนเธเธเธขเธ•เนเธเธเนเธเธเธญเธ</td></tr>';
        return;
    }
    
    sorted.forEach(yr => {
        tbody.innerHTML += `
            <tr>
                <td style="font-weight:bold; font-size:1.05rem; padding: 12px 20px; color: var(--text-primary);">เนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“ เนเธเธ.เนเธเธ. ${yr}</td>
                <td class="text-center" style="padding: 12px;">
                    <button class="btn btn-secondary btn-small" onclick="deleteManagedYear('${yr}')" style="background-color:rgba(239, 68, 68, 0.1); color:var(--danger-primary); border:none; padding:4px 8px; font-size:0.8rem; cursor:pointer; border-radius:4px;">
                         เธฅเธ
                    </button>
                </td>
            </tr>
        `;
    });
}

// Add managed fiscal year
function addManagedYear(yr) {
    const cleanYear = String(yr).trim();
    if (!cleanYear.match(/^25\d{2}$/)) {
        showToast("เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“เนเธเธเนเธเธเนเธเธเธนเธเนเธเธเนเธเธเธญเธ เนเธเธเนเธเธเธญเธเนเธเธเนเธเธ เนเธเธ.เนเธเธ. 4 เนเธเธเนเธเธเธฑเธ เนเธเธเนเธเธ 2569", false);
        return;
    }
    if (!isPlausibleFiscalYear(cleanYear)) {
        showToast(`เนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“ ${cleanYear} เนเธเธเนเธเธเนเธเธเนเธเธเธญเธเนเธเธเนเธเธเธงเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธเนเธเธเธญเธเนเธเธเธฑเธ`, false);
        return;
    }
    
    if (managedYears.includes(cleanYear)) {
        showToast(`เนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“ เนเธเธ.เนเธเธ. ${cleanYear} เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฐเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ`, false);
        return;
    }
    
    managedYears.push(cleanYear);
    localStorage.setItem('nhso_managed_years', JSON.stringify(managedYears));
    loadManagedYears();
    refreshDashboard();
    showToast(`เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“ เนเธเธ.เนเธเธ. ${cleanYear} เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ`);
    recordAppLog('success', 'add-fiscal-year', `เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธตเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธ“ เนเธเธ.เนเธเธ. ${cleanYear}`, {
        fiscalYear: cleanYear,
        tab: activeTab
    });
}

// Delete managed fiscal year
function deleteManagedYear(yr) {
    if (confirm(`คุณต้องการลบปีงบประมาณ พ.ศ. ${yr} ออกจากระบบใช่หรือไม่?`)) {
        managedYears = managedYears.filter(y => y !== yr);
        localStorage.setItem('nhso_managed_years', JSON.stringify(managedYears));
        loadManagedYears();
        refreshDashboard();
        showToast(`ลบปีงบประมาณ พ.ศ. ${yr} เรียบร้อยแล้ว`);
        recordAppLog('warn', 'delete-fiscal-year', `ลบปีงบประมาณ พ.ศ. ${yr}`, {
            fiscalYear: yr,
            tab: activeTab
        });
    }
}

async function repairFiscalYearsFromUi() {
    const resultEl = document.getElementById('repair-fiscal-years-result');
    const btn = document.getElementById('repair-fiscal-years-btn');
    if (btn) btn.disabled = true;
    if (resultEl) resultEl.textContent = 'กำลังซ่อมปีงบประมาณ...';

    try {
        const res = await fetch(`${BACKEND_URL}/admin/repair-fiscal-years`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${currentSession?.token || localStorage.getItem('nhso_token') || ''}`
            },
            body: JSON.stringify({})
        });
        const result = await res.json();
        if (result.status === 'success') {
            const summary = result.result || {};
            const message = `ซ่อมปีงบประมาณสำเร็จ: summary ${summary.updatedSummaries || 0} รายการ, log ${summary.updatedLogs || 0} รายการ`;
            if (resultEl) resultEl.textContent = message;
            showToast(message);
            await loadDashboardData();
            loadManagedYears();
            refreshDashboard();
        } else {
            const message = result.message || 'ซ่อมปีงบประมาณไม่สำเร็จ';
            if (resultEl) resultEl.textContent = message;
            showToast(message, false);
        }
    } catch (err) {
        const message = `ซ่อมปีงบประมาณไม่สำเร็จ: ${err.message}`;
        if (resultEl) resultEl.textContent = message;
        showToast(message, false);
    } finally {
        if (btn) btn.disabled = false;
    }
}

// Setup Event Listeners and Initialize Page
document.addEventListener('DOMContentLoaded', async () => {
    checkBackendStatus();
    lucide.createIcons();
    checkActiveSession();
    localStorage.removeItem(LOG_HISTORY_KEY);
    await syncLogHistoryFromServer();
    syncUploadHistoryFromServer();

    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const user = document.getElementById('login-username')?.value.trim() || '';
            const pass = document.getElementById('login-password')?.value || '';
            await handleLogin(user, pass);
        });
    }

    const registerForm = document.getElementById('register-form');
    if (registerForm) registerForm.addEventListener('submit', publicRegisterUser);

    const showRegisterLink = document.getElementById('show-register');
    if (showRegisterLink && loginForm && registerForm) {
        showRegisterLink.addEventListener('click', (e) => {
            e.preventDefault();
            loginForm.style.display = 'none';
            registerForm.style.display = 'block';
        });
    }

    const showLoginLink = document.getElementById('show-login');
    if (showLoginLink && loginForm && registerForm) {
        showLoginLink.addEventListener('click', (e) => {
            e.preventDefault();
            registerForm.style.display = 'none';
            loginForm.style.display = 'block';
        });
    }

    const registerUserForm = document.getElementById('register-user-form');
    if (registerUserForm) registerUserForm.addEventListener('submit', registerUser);

    const addYearForm = document.getElementById('add-year-form');
    if (addYearForm) {
        addYearForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const yrInput = document.getElementById('new-fiscal-year');
            if (!yrInput) return;
            addManagedYear(yrInput.value);
            yrInput.value = "";
        });
    }
    const repairFiscalYearsBtn = document.getElementById('repair-fiscal-years-btn');
    if (repairFiscalYearsBtn) repairFiscalYearsBtn.addEventListener('click', repairFiscalYearsFromUi);
    const logoutBtn = document.getElementById('btn-logout');
    if (logoutBtn) logoutBtn.addEventListener('click', logoutUser);
    const usersAdminTable = document.getElementById('users-admin-table');
    if (usersAdminTable && !usersAdminTable.dataset.actionBound) {
        usersAdminTable.dataset.actionBound = '1';
        usersAdminTable.addEventListener('click', (event) => {
            const actionButton = event.target.closest('[data-user-action]');
            if (!actionButton || !usersAdminTable.contains(actionButton)) return;

            const username = String(actionButton.dataset.username || '').trim();
            const action = String(actionButton.dataset.userAction || '').trim();
            const currentStatus = String(actionButton.dataset.status || 'approved').trim();

            if (!username) return;
            if (action === 'manage') {
                manageUser(username, currentStatus);
            } else if (action === 'approve') {
                approveUser(username);
            } else if (action === 'reject') {
                rejectUser(username);
            }
        });
    }
    const logHistoryFilter = document.getElementById('log-history-filter');
    const logHistoryActionFilter = document.getElementById('log-history-action-filter');
    const logHistoryStartDate = document.getElementById('log-history-start-date');
    const logHistoryEndDate = document.getElementById('log-history-end-date');
    const logHistorySearch = document.getElementById('log-history-search');
    const logHistoryRefresh = document.getElementById('log-history-refresh');
    const logHistoryExportCsv = document.getElementById('log-history-export-csv');
    const logHistoryExportXlsx = document.getElementById('log-history-export-xlsx');
    const logHistoryClear = document.getElementById('log-history-clear');
    const uploadHistoryRefresh = document.getElementById('upload-history-refresh');
    const uploadHistoryExportCsv = document.getElementById('upload-history-export-csv');
    const uploadHistoryExportXlsx = document.getElementById('upload-history-export-xlsx');
    const uploadHistorySearch = document.getElementById('upload-history-search');
    const uploadHistoryDeleteList = document.getElementById('upload-history-delete-list');
    const uploadHistorySelectAll = document.getElementById('upload-history-select-all');
    const uploadHistoryClearSelection = document.getElementById('upload-history-clear-selection');
    const dashboardFileSelect = document.getElementById('dashboard-file-select');
    const cleanupOldDataBtn = document.getElementById('cleanup-old-data-btn');
    const importReplaceBtn = document.getElementById('btn-import-replace');
    const syncDbBtn = document.getElementById('btn-sync-db');
    const purgeDataBtn = document.getElementById('btn-purge-data');
    const yearAuditRefresh = document.getElementById('year-audit-refresh');
    const yearAuditExportCsv = document.getElementById('year-audit-export-csv');
    const yearAuditExportXlsx = document.getElementById('year-audit-export-xlsx');
    const backendApiUrlInput = document.getElementById('backend-api-url');
    const btnSaveBackendApi = document.getElementById('btn-save-backend-api');
    const btnTestBackendApi = document.getElementById('btn-test-backend-api');

    if (logHistoryFilter) logHistoryFilter.addEventListener('change', renderLogHistory);
    if (logHistoryActionFilter) logHistoryActionFilter.addEventListener('change', renderLogHistory);
    if (logHistoryStartDate) logHistoryStartDate.addEventListener('change', renderLogHistory);
    if (logHistoryEndDate) logHistoryEndDate.addEventListener('change', renderLogHistory);
    if (logHistorySearch) logHistorySearch.addEventListener('input', renderLogHistory);
    if (logHistoryRefresh) logHistoryRefresh.addEventListener('click', renderLogHistory);
    if (logHistoryExportCsv) logHistoryExportCsv.addEventListener('click', () => handleLogHistoryExport('csv'));
    if (logHistoryExportXlsx) logHistoryExportXlsx.addEventListener('click', () => handleLogHistoryExport('xlsx'));
    if (logHistoryClear) logHistoryClear.addEventListener('click', clearLogHistory);
    if (uploadHistoryRefresh) uploadHistoryRefresh.addEventListener('click', renderUploadHistory);
    if (uploadHistorySearch) uploadHistorySearch.addEventListener('input', renderUploadHistorySelect);
    if (uploadHistoryExportCsv) uploadHistoryExportCsv.addEventListener('click', () => {
        const rows = loadUploadHistory().map(item => ({
            'วันที่นำเข้า': item.importedAt ? new Date(item.importedAt).toLocaleString('th-TH') : '',
            'ชื่อไฟล์': item.fileName || '',
            'รหัสโรงพยาบาล': item.hospitalCode || '',
            'ชื่อโรงพยาบาล': item.hospitalName || '',
            'ปีงบประมาณ': item.fiscalYear || '',
            'เดือน': item.monthNum || '',
            'รหัสกลุ่มบริการย่อย': item.subFundId || '',
            'สถานะ': item.status || '',
            'ขนาดไฟล์': item.fileSizeBytes || 0,
            'อัปโหลดโดย': item.uploadedBy || ''
        }));
        if (rows.length === 0) {
            showToast('ไม่พบประวัติการนำเข้าในรายการนี้', false);
            return;
        }
        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Upload History');
        XLSX.writeFile(wb, `NHSO_Upload_History_${new Date().toISOString().slice(0, 10)}.csv`);
    });
    if (uploadHistoryExportXlsx) uploadHistoryExportXlsx.addEventListener('click', () => {
        const rows = loadUploadHistory().map(item => ({
            'เนเธเธเนเธเธเนเธเธเธฒเธ—เนเธเธเนเธเธเนเธเธเธฑเธเนเธเธเนเธเธเธฅเธ”': item.importedAt ? new Date(item.importedAt).toLocaleString('th-TH') : '',
            'เนเธเธเนเธเธเนเธเธ': item.fileName || '',
            'เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธ': item.hospitalCode || '',
            'เนเธเธเนเธเธเนเธเธเนเธเธเธซเธเนเธเธเนเธเธเธขเธเนเธเธเธดเธเนเธเธเนเธเธ': item.hospitalName || '',
            'เนเธเธเธตเธเนเธเธ': item.fiscalYear || '',
            'เนเธเธเนเธเธเธญเธ': item.monthNum || '',
            'เนเธเธเธญเธเนเธเธเธธเธ': item.subFundId || '',
            'เธชเธ–เธฒเธเนเธเธ': item.status || '',
            'เนเธเธเนเธเธเธฒเธ”เนเธเธเนเธเธเนเธเธ': item.fileSizeBytes || 0,
            'เนเธเธเนเธเธเนเธเธเนเธเธเธฑเธเนเธเธเนเธเธเธฅเธ”': item.uploadedBy || ''
        }));
        if (rows.length === 0) {
            showToast('เนเธเธเธฑเธเนเธเธเนเธเธเนเธเธเนเธเธเธตเธเนเธเธเนเธเธเนเธเธเธฑเธ•เธดเธเนเธเธเนเธเธเนเธเธเธฑเธเนเธเธเนเธเธเธฅเธ”', false);
            return;
        }
        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Upload History');
        XLSX.writeFile(wb, `NHSO_Upload_History_${new Date().toISOString().slice(0, 10)}.xlsx`);
    });
    if (uploadHistoryDeleteList) uploadHistoryDeleteList.addEventListener('change', handleUploadDeletionListChange);
    if (uploadHistorySelectAll) uploadHistorySelectAll.addEventListener('click', selectAllUploadDeletionItems);
    if (uploadHistoryClearSelection) uploadHistoryClearSelection.addEventListener('click', clearUploadDeletionItemsSelection);
    if (dashboardFileSelect) dashboardFileSelect.addEventListener('change', () => {
        setSelectedDashboardFileValue(dashboardFileSelect.value || 'all');
        refreshDashboard();
    });
    if (cleanupOldDataBtn) cleanupOldDataBtn.addEventListener('click', cleanupOldDataFromUi);
    if (importReplaceBtn) importReplaceBtn.addEventListener('click', deleteSelectedUploadHistoryEntry);
    if (syncDbBtn) {
        syncDbBtn.addEventListener('click', async () => {
            if (!confirm('คุณต้องการซิงก์ฐานข้อมูลปัจจุบันไปยัง MySQL ภายนอกหรือไม่?')) return;
            syncDbBtn.disabled = true;
            try {
                await syncCurrentDatabaseToBackend({
                    sourceRecords: database,
                    silent: false,
                    sourceLabel: 'current dashboard'
                });
                await loadDashboardData();
            } catch (err) {
                alert(`ซิงก์ฐานข้อมูลไม่สำเร็จ: ${err.message}`);
                showToast(`ซิงก์ฐานข้อมูลไม่สำเร็จ: ${err.message}`, false);
            } finally {
                syncDbBtn.disabled = false;
            }
        });
    }
    if (purgeDataBtn) purgeDataBtn.addEventListener('click', purgeSelectedDataFromUi);
    if (yearAuditRefresh) yearAuditRefresh.addEventListener('click', renderYearAudit);
    if (yearAuditExportCsv) yearAuditExportCsv.addEventListener('click', () => exportYearAudit('csv'));
    if (yearAuditExportXlsx) yearAuditExportXlsx.addEventListener('click', () => exportYearAudit('xlsx'));
    if (backendApiUrlInput) {
        backendApiUrlInput.value = BACKEND_URL || '/api';
        const backendCard = backendApiUrlInput.closest('.sync-info-card');
        if (backendCard) {
            backendCard.style.display = 'none';
        }
    }
    renderLogHistory();
    renderYearAudit();

    document.querySelectorAll('.sidebar-menu .menu-item').forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (btn.classList.contains('has-submenu')) {
                const submenuList = btn.nextElementSibling;
                if (btn.getAttribute('data-tab') === 'seamless') {
                    const wasOpen = submenuList && submenuList.classList.contains('open');
                    const willOpen = !wasOpen;
                    const seamlessAccordion = btn.closest('.menu-accordion') || null;
                    const seamlessList = seamlessAccordion ? seamlessAccordion.querySelector('.submenu-list') : submenuList;
                    setSeamlessSubmenuCollapsed(!willOpen);
                    if (seamlessList) {
                        seamlessList.classList.toggle('open', willOpen);
                        seamlessList.style.display = willOpen ? 'flex' : 'none';
                        seamlessList.style.visibility = 'visible';
                        seamlessList.style.maxHeight = willOpen ? 'none' : '0';
                        seamlessList.style.overflow = willOpen ? 'visible' : 'hidden';
                    }
                    btn.classList.toggle('open', willOpen);
                    btn.classList.add('active');
                    return;
                }
                if (submenuList && submenuList.classList.contains('submenu-list')) {
                    btn.classList.toggle('open');
                    submenuList.classList.toggle('open');
                }
                return; // Do not switch tab immediately
            }
            // Clear active submenus if a main tab is clicked
            document.querySelectorAll('.submenu-item').forEach(b => b.classList.remove('active'));
            try { localStorage.removeItem('nhso_current_seamless_subtab'); } catch (e) {}
            try { localStorage.removeItem('nhso_current_seamless_subtab_name'); } catch (e) {}
            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.seamlessSubtab, '');
            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.seamlessSubtabName, '');
            saveSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtab, '');
            saveSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtabName, '');
            window.currentSeamlessSubtab = null;
            window.currentSeamlessSubtabName = null;
            handleTabChange(btn.getAttribute('data-tab'));
        });
    });

    document.querySelectorAll('.submenu-item').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.submenu-item').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Mark parent as active
            document.querySelectorAll('.sidebar-menu .menu-item').forEach(b => b.classList.remove('active'));
            btn.closest('.menu-accordion').querySelector('.has-submenu').classList.add('active');
            
            const subtab = btn.getAttribute('data-subtab');
            try { localStorage.setItem('nhso_current_seamless_subtab', subtab); } catch (e) {}
            window.currentSeamlessSubtab = subtab;
            const spans = btn.querySelectorAll('span');
            const subtabName = spans.length > 1 ? spans[1].innerText : spans[0].innerText;
            try { localStorage.setItem('nhso_current_seamless_subtab_name', subtabName); } catch (e) {}
            window.currentSeamlessSubtabName = subtabName;
            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.seamlessSubtab, subtab);
            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.seamlessSubtabName, subtabName);
            saveSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtab, subtab);
            saveSharedUiPreference(SHARED_PREF_KEYS.seamlessSubtabName, subtabName);
            handleTabChange('seamless');
            refreshDashboard();
        });
    });

    const hospitalInputEl = document.getElementById('hospital-input');
    if (hospitalInputEl) {
        hospitalInputEl.addEventListener('input', async () => {
            const value = hospitalInputEl.value || '';
            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.hospitalInput, value);
            await saveSharedUiPreference(SHARED_PREF_KEYS.hospitalInput, value);
            refreshDashboard();
        });
    }
    const yearSelectEl = document.getElementById('year-select');
    if (yearSelectEl) {
        yearSelectEl.addEventListener('change', async () => {
            const selectedYear = yearSelectEl.value || 'all';
            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.yearSelect, selectedYear);
            await saveSharedUiPreference(SHARED_PREF_KEYS.yearSelect, selectedYear);
            refreshDashboard();
        });
    }
    const startMonthSelectEl = document.getElementById('start-month-select');
    if (startMonthSelectEl) {
        startMonthSelectEl.addEventListener('change', async () => {
            const value = startMonthSelectEl.value || '';
            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.startMonthSelect, value);
            await saveSharedUiPreference(SHARED_PREF_KEYS.startMonthSelect, value);
            refreshDashboard();
        });
    }
    const endMonthSelectEl = document.getElementById('end-month-select');
    if (endMonthSelectEl) {
        endMonthSelectEl.addEventListener('change', async () => {
            const value = endMonthSelectEl.value || '';
            setSharedUiPreferenceLocal(SHARED_PREF_KEYS.endMonthSelect, value);
            await saveSharedUiPreference(SHARED_PREF_KEYS.endMonthSelect, value);
            refreshDashboard();
        });
    }
    const tableSearchInput = document.getElementById('table-search-input');
    if (tableSearchInput) {
        tableSearchInput.addEventListener('input', () => {
            const filteredData = getFilteredData();
            updateTable(filteredData);
        });
    }

    const excelUploadInput = document.getElementById('excel-upload');
    if (excelUploadInput) excelUploadInput.addEventListener('change', handleFileUpload);
    const btnImportCmi = document.getElementById('btn-import-cmi-excel');
    if (btnImportCmi) {
        btnImportCmi.addEventListener('click', () => {
            const excelUpload = document.getElementById('excel-upload');
            if (excelUpload) excelUpload.click();
        });
    }

    const btnImportIpDelay = document.getElementById('btn-import-ip-delay-excel');
    const inputIpDelay = document.getElementById('ip-delay-file-input');

    if (btnImportIpDelay && inputIpDelay) {
        btnImportIpDelay.addEventListener('click', () => inputIpDelay.click());
        inputIpDelay.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (evt) => {
                try {
                    const data = new Uint8Array(evt.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const jsonData = XLSX.utils.sheet_to_json(firstSheet, { header: 1 });

                    if (jsonData.length > 1) {
                        const parsedData = [];
                        for (let i = 1; i < jsonData.length; i++) {
                            const row = jsonData[i];
                            if (!row || row.length === 0) continue;
                            const mVal = String(row[0] || '').trim();
                            if (!mVal || mVal.includes('รวม')) continue;

                            parsedData.push({
                                month: mVal,
                                d30Count: Number(row[1]) || 0,
                                d30Rw: Number(row[2]) || 0,
                                d60Count: Number(row[3]) || 0,
                                d60Rw: Number(row[4]) || 0,
                                d90Count: Number(row[5]) || 0,
                                d90Rw: Number(row[6]) || 0,
                                gt90Count: Number(row[7]) || 0,
                                gt90Rw: Number(row[8]) || 0
                            });
                        }
                        if (parsedData.length > 0) {
                            ipSubmissionDelayData = parsedData;
                            localStorage.setItem('nhso_ip_delay_data', JSON.stringify(parsedData));
                            renderIpSubmissionDelayTable();
                            if (typeof showToast === 'function') {
                                showToast('เนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฒเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ');
        }
    }
}

} catch (err) {
                    console.error('Error importing IP delay file:', err);
                    if (typeof showToast === 'function') {
                        showToast('เนเธเธเธดเธ”เนเธเธเนเธเธเธญเธเธดเธ”เนเธเธเนเธเธเธฒเธ”เนเธเธเนเธเธเธฃเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ Excel', false);
                    }
                }
            };
            reader.readAsArrayBuffer(file);
        });
    }
    document.querySelectorAll('.btn-export-excel').forEach(btn => btn.addEventListener('click', () => handleFileExport('xlsx')));
    document.querySelectorAll('.btn-export-csv').forEach(btn => btn.addEventListener('click', () => handleFileExport('csv')));
    document.querySelectorAll('.btn-print').forEach(btn => btn.addEventListener('click', handlePrint));
    window.addEventListener('focus', () => {
        syncDashboardStateFromServer().catch(() => {});
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            syncDashboardStateFromServer().catch(() => {});
        }
    });

});


function updateOpIpDetails(filteredData) {
    const section = document.getElementById('op-ip-details-section');
    if (!section) return;

    if (activeTab === 'op' || activeTab === 'ip') {
        section.style.display = 'flex';
        
        const titleType = activeTab === 'op' ? 'ผู้ป่วยนอก (OP)' : 'ผู้ป่วยใน (IP)';
        document.getElementById('error-table-title').textContent = `จำนวนข้อมูลแยกตามประเภทความผิดพลาด ${titleType}`;
        document.getElementById('submission-table-title').textContent = `การส่งข้อมูลสิทธิ UC ${titleType}`;

        // Calculate Data for Error Table (Top 5)
        const errorAgg = {};
        filteredData.forEach(item => {
            if (item.errors && Array.isArray(item.errors)) {
                item.errors.forEach(err => {
                    if (!errorAgg[err.code]) {
                        errorAgg[err.code] = { code: err.code, desc: err.desc, count: 0 };
                    }
                    errorAgg[err.code].count += err.count;
                });
            }
        });

        const sortedErrors = Object.values(errorAgg).sort((a, b) => b.count - a.count);
        
        
        const errTbody = document.querySelector('#error-details-table tbody');
        if (errTbody) errTbody.innerHTML = '';

        let errTotal = 0;
        let errFixedTotal = 0;
        let errRemainTotal = 0;

        if (sortedErrors.length === 0) {
            if (errTbody) errTbody.innerHTML = '<tr><td colspan="5" class="text-center">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธญเธเธดเธ”เนเธเธเนเธเธเธฒเธ”</td></tr>';
        } else {
            sortedErrors.forEach(err => {
                // For IP/OP: if there is an error, Fixed = 0, Remaining = err.count (swapped)
                const isIpOp = activeTab === 'ip' || activeTab === 'op';
                const fixed = isIpOp ? 0 : Math.floor(err.count * 0.9);
                const remain = isIpOp ? err.count : err.count - fixed;
                
                errTotal += err.count;
                errFixedTotal += fixed;
                errRemainTotal += remain;

                if (errTbody) if (errTbody) errTbody.innerHTML += `
                    <tr>
                        <td><strong>${err.code}</strong></td>
                        <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${err.desc}">${err.desc || '๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝ๏ฟฝอธิบ๏ฟฝ๏ฟฝ'}</td>
                        <td style="text-align: right;">${err.count.toLocaleString()}</td>
                        <td style="text-align: right;">${fixed.toLocaleString()}</td>
                        <td style="text-align: right;">${remain.toLocaleString()}</td>
                    </tr>
                `;
            });
            // Total Row
            errTbody.innerHTML += `
                <tr style="font-weight: bold; background-color: rgba(0,0,0,0.03);">
                    <td colspan="2">รวม</td>
                    <td style="text-align: right;">${errTotal.toLocaleString()}</td>
                    <td style="text-align: right;">${errFixedTotal.toLocaleString()}</td>
                    <td style="text-align: right;">${errRemainTotal.toLocaleString()}</td>
                </tr>
            `;
        }

        // Calculate Data for Monthly Submission
        const monthAgg = {};
        filteredData.forEach(item => {
            const mKey = `${item.fiscalYear}-${String(item.month).padStart(2, '0')}`;
            if (!monthAgg[mKey]) {
                monthAgg[mKey] = { 
                    claimAmt: 0, 
                    approveAmt: 0, 
                    errorAmt: 0,
                    totalRecords: 0,
                    passRecords: 0,
                    failRecords: 0,
                    hasRealRecords: false
                };
            }
            monthAgg[mKey].claimAmt += item.claimAmount;
            monthAgg[mKey].approveAmt += item.approvedAmount;
            
            if (item.totalRecords !== undefined) {
                monthAgg[mKey].totalRecords += item.totalRecords;
                monthAgg[mKey].passRecords += item.passRecords;
                monthAgg[mKey].failRecords += item.failRecords;
                monthAgg[mKey].hasRealRecords = true;
            }
            
            // Collect un-fixed C from errors
            let cRemain = 0;
            const isIpOpTab = activeTab === 'ip' || activeTab === 'op';
            if (isIpOpTab) {
                // For IP/OP: remaining errors is equal to the number of failed records
                cRemain = item.failRecords || 0;
            } else if (item.errors && Array.isArray(item.errors)) {
                item.errors.forEach(err => {
                    const r = err.count - Math.floor(err.count * 0.9);
                    cRemain += r;
                });
            }
            monthAgg[mKey].errorAmt += cRemain;
        });

        const sortedMonths = Object.keys(monthAgg).sort((a, b) => {
            const [aYear, aMonth] = String(a).split('-').map(v => parseInt(v, 10));
            const [bYear, bMonth] = String(b).split('-').map(v => parseInt(v, 10));
            if (aYear !== bYear) return aYear - bYear;
            return aMonth - bMonth;
        });
        
        const subTbody = document.querySelector('#submission-details-table tbody');
        if (subTbody) subTbody.innerHTML = '';

        let sumTotal = 0;
        let sumPass = 0;
        let sumFail = 0;
        let sumCRemain = 0;

        if (sortedMonths.length === 0) {
            if (subTbody) subTbody.innerHTML = '<tr><td colspan="6" class="text-center">เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฅเธเนเธเธเนเธเธเนเธเธเนเธเธ</td></tr>';
        } else {
            sortedMonths.forEach(m => {
                const data = monthAgg[m];
                
                let totalCount, passCount, failCount, failPercent;
                if (data.hasRealRecords) {
                    totalCount = data.totalRecords;
                    passCount = data.passRecords;
                    failCount = data.failRecords;
                    failPercent = totalCount > 0 ? ((failCount / totalCount) * 100).toFixed(2) : '0.00';
                } else {
                    // Derive mock counts from amounts to show realistic table (500 Baht per record avg)
                    totalCount = Math.max(1, Math.floor(data.claimAmt / 500)) + Math.floor(Math.random() * 50);
                    passCount = Math.floor((data.approveAmt / data.claimAmt) * totalCount) || Math.floor(totalCount * 0.9);
                    failCount = Math.max(0, totalCount - passCount);
                    failPercent = totalCount > 0 ? ((failCount / totalCount) * 100).toFixed(2) : '0.00';
                }
                
                sumTotal += totalCount;
                sumPass += passCount;
                sumFail += failCount;
                sumCRemain += data.errorAmt;

                const monthParts = String(m).split('-');
                const monthYear = monthParts[0];
                const monthIndex = parseInt(monthParts[1], 10);
                if (subTbody) subTbody.innerHTML += `
                    <tr>
                        <td>${getThaiMonthNameFromCalendarMonth(monthIndex, monthYear)}</td>
                        <td style="text-align: right;">${totalCount.toLocaleString()}</td>
                        <td style="text-align: right;">${passCount.toLocaleString()}</td>
                        <td style="text-align: right;">${failCount.toLocaleString()}</td>
                        <td style="text-align: right;">${failPercent}</td>
                        <td style="text-align: right;">${data.errorAmt.toLocaleString()}</td>
                    </tr>
                `;
            });
            // Total row
            const totalFailPercent = sumTotal > 0 ? ((sumFail / sumTotal) * 100).toFixed(2) : '0.00';
            subTbody.innerHTML += `
                <tr style="font-weight: bold; background-color: rgba(0,0,0,0.03);">
                    <td>รวม</td>
                    <td style="text-align: right;">${sumTotal.toLocaleString()}</td>
                    <td style="text-align: right;">${sumPass.toLocaleString()}</td>
                    <td style="text-align: right;">${sumFail.toLocaleString()}</td>
                    <td style="text-align: right;">${totalFailPercent}</td>
                    <td style="text-align: right;">${sumCRemain.toLocaleString()}</td>
                </tr>
            `;
        }

    } else {
        section.style.display = 'none';
    }
}

// Redesigned PPFS Custom Dashboard
function updatePpfsCustomDashboard(filteredData) {
    const normalizePpfsKey = (value) => String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^0-9A-Za-z\u0E00-\u0E7F]+/g, '');

    const ppfsReverseMap = Object.fromEntries(
        Object.entries(SUB_FUND_MAPPING).map(([code, desc]) => [normalizePpfsKey(desc), code])
    );

    const parsePpfsNumber = (value) => {
        if (typeof value === 'number') {
            return Number.isFinite(value) ? value : 0;
        }
        const raw = String(value ?? '')
            .replace(/,/g, '')
            .replace(/[%\s]/g, '')
            .trim();
        if (!raw) return 0;
        const normalized = raw.startsWith('(') && raw.endsWith(')')
            ? `-${raw.slice(1, -1)}`
            : raw;
        const parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const resolvePpfsSubFundCode = (sub) => {
        const candidates = [
            sub?.subCode,
            sub?.code,
            sub?.itemName,
            sub?.subName,
            sub?.main,
            sub?.name
        ].filter(Boolean);

        for (const candidate of candidates) {
            const normalized = normalizePpfsKey(candidate);
            if (!normalized) continue;

            for (const code of Object.keys(SUB_FUND_MAPPING)) {
                if (normalized === normalizePpfsKey(code)) return code;
            }

            if (ppfsReverseMap[normalized]) return ppfsReverseMap[normalized];

            for (const [code, desc] of Object.entries(SUB_FUND_MAPPING)) {
                const codeKey = normalizePpfsKey(code);
                const descKey = normalizePpfsKey(desc);
                if (normalized.includes(codeKey) || codeKey.includes(normalized)) return code;
                if (normalized.includes(descKey) || descKey.includes(normalized)) return code;
            }
        }

        const fallback = String(sub?.subName || sub?.subCode || sub?.code || '').trim();
        return /^\d+$/.test(fallback) ? null : fallback;
    };

    const getPpfsSubFundCount = (sub) => {
        const count = parsePpfsNumber(sub?.count || sub?.times || sub?.qty || sub?.quantity || 0);
        if (Number.isFinite(count) && count > 0) return count;
        const revenue = parsePpfsNumber(sub?.claimAmt || sub?.amt || sub?.revenue || sub?.approvedAmt || 0);
        return revenue > 0 ? 1 : 0;
    };

    const getPpfsSubFundRevenue = (sub) => {
        const revenue = parsePpfsNumber(sub?.claimAmt || sub?.amt || sub?.revenue || sub?.approvedAmt || sub?.amount || 0);
        return Number.isFinite(revenue) ? revenue : 0;
    };

    // Setup listeners if not already done
    if (!window.ppfsListenersAdded) {
        window.ppfsListenersAdded = true;
        const ppfsYrFilter = document.getElementById('ppfs-year-filter');
        const ppfsHospFilter = document.getElementById('ppfs-hosp-filter');
        const ppfsSearchInput = document.getElementById('ppfs-search-input');
        
        if (ppfsYrFilter) ppfsYrFilter.addEventListener('change', () => updatePpfsCustomDashboard(getFilteredData()));
        if (ppfsHospFilter) ppfsHospFilter.addEventListener('change', () => updatePpfsCustomDashboard(getFilteredData()));
        if (ppfsSearchInput) ppfsSearchInput.addEventListener('input', () => updatePpfsCustomDashboard(getFilteredData()));
    }

    // 1. Get filter values
    const selectedYr = document.getElementById('ppfs-year-filter') ? document.getElementById('ppfs-year-filter').value : 'all';
    const selectedHosp = document.getElementById('ppfs-hosp-filter') ? document.getElementById('ppfs-hosp-filter').value : 'all';
    const searchVal = document.getElementById('ppfs-search-input') ? document.getElementById('ppfs-search-input').value.toLowerCase() : '';

    // 2. Populate filters dynamically from database if it has PPFS records
    const ppfsItems = database.filter(item => 
        item.fund === 'ppfs' || item.fund === 'ppfs_sub' || item.baseFund === 'PP' || item.baseFund === 'OPFS' || item.baseFund === 'OPAE' ||
        String(item.fund).toLowerCase().includes('pp') || String(item.baseFund).toLowerCase().includes('pp')
    );

    // Populate hospital filter options
    const hospFilterSelect = document.getElementById('ppfs-hosp-filter');
    if (hospFilterSelect && hospFilterSelect.options.length <= 1 && ppfsItems.length > 0) {
        const uniqueHosps = [...new Set(ppfsItems.map(item => item.hospital))].sort();
        uniqueHosps.forEach(h => {
            const opt = document.createElement('option');
            opt.value = h;
            opt.textContent = h;
            hospFilterSelect.appendChild(opt);
        });
    }

    // Apply filters to current display
    let displayItems = ppfsItems;
    if (selectedYr !== 'all') {
        displayItems = displayItems.filter(item => String(item.fiscalYear) === selectedYr);
    }
    if (selectedHosp !== 'all') {
        displayItems = displayItems.filter(item => item.hospital === selectedHosp);
    }

    // 3. Compute KPI metrics
        let totalRevenue = 0;
    let targetPercent = 0;
    let mainSourceName = 'ไม่ระบุ';
    let mainSourceVal = 0;
    let errorRate = 0;
    let errorAmt = 0;
    let errorCount = 0;

    // If we have actual uploaded data, we compute the real values!
    if (displayItems.length > 0) {
        let sumClaim = 0;
        let sumApprove = 0;
        let sumC = 0;
        let totalCases = 0;
        let failCases = 0;
        
        displayItems.forEach(item => {
            sumClaim += item.claimAmount || 0;
            sumApprove += item.approvedAmount || 0;
            sumC += item.pendingAmount || 0;
            totalCases += item.totalRecords || 0;
            failCases += item.failRecords || 0;
        });

        totalRevenue = sumApprove;
        errorAmt = sumC || (sumClaim - sumApprove);
        if (errorAmt < 0) errorAmt = 0;
        errorCount = failCases;
        errorRate = totalCases > 0 ? parseFloat(((failCases / totalCases) * 100).toFixed(1)) : 0;
        targetPercent = totalCases > 0 ? parseFloat((((totalCases - failCases) / totalCases) * 100).toFixed(1)) : 85.5;

        // Determine main source dynamically
        let fundBreakdown = {};
        displayItems.forEach(item => {
            if (item.subFundsBreakdown) {
                item.subFundsBreakdown.forEach(sub => {
                    const subCode = resolvePpfsSubFundCode(sub);
                    if (!subCode) return;
                    fundBreakdown[subCode] = (fundBreakdown[subCode] || 0) + getPpfsSubFundRevenue(sub);
                });
            }
        });
        const sortedSources = Object.keys(fundBreakdown).sort((a,b) => fundBreakdown[b] - fundBreakdown[a]);
        if (sortedSources.length > 0) {
            mainSourceName = sortedSources[0];
            mainSourceVal = fundBreakdown[mainSourceName];
        }
    }

    // Update KPI Card values
    const kpiRevenue = document.getElementById('ppfs-kpi-revenue');
    if (kpiRevenue) kpiRevenue.textContent = `฿${totalRevenue.toLocaleString('en-US')}`;
    
    const kpiMainSourceLabel = document.getElementById('ppfs-kpi-main-source-label');
    if (kpiMainSourceLabel) kpiMainSourceLabel.textContent = mainSourceName;
    
    const kpiMainSourceValue = document.getElementById('ppfs-kpi-main-source-value');
    if (kpiMainSourceValue) kpiMainSourceValue.textContent = `฿${mainSourceVal.toLocaleString('en-US')}`;
    
    const kpiErrorValue = document.getElementById('ppfs-kpi-error-value');
    if (kpiErrorValue) kpiErrorValue.textContent = `฿${errorAmt.toLocaleString('en-US')}`;
    
    const kpiErrorCount = document.getElementById('ppfs-kpi-error-count');
    if (kpiErrorCount) kpiErrorCount.textContent = `จำนวนรายการผิดพลาด: ${errorCount.toLocaleString()}`;

    const cPercentText = document.getElementById('ppfs-c-percent');
    if (cPercentText) cPercentText.textContent = `${errorRate}%`;

    const gaugeValueText = document.getElementById('ppfs-gauge-value');
    if (gaugeValueText) gaugeValueText.textContent = `${targetPercent}%`;

    // 4. Render charts
    // Chart 1: Gauge (Half Doughnut)
    if (ppfsGaugeChart) ppfsGaugeChart.destroy();
    const ctxGauge = document.getElementById('ppfsGaugeChart').getContext('2d');
    ppfsGaugeChart = new Chart(ctxGauge, {
        type: 'doughnut',
        data: {
            labels: ['ผ่านเกณฑ์', 'ยังไม่ผ่าน'],
            datasets: [{
                data: [targetPercent, 100 - targetPercent],
                backgroundColor: ['#10b981', '#f1f5f9'],
                borderWidth: 0
            }]
        },
        options: {
            rotation: 270,
            circumference: 180,
            cutout: '80%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false },
                datalabels: { display: false }
            }
        }
    });

    // Chart 2: C / Error (Doughnut)
    if (ppfsCChart) ppfsCChart.destroy();
    const ctxC = document.getElementById('ppfsCChart').getContext('2d');
    ppfsCChart = new Chart(ctxC, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [errorRate, 100 - errorRate],
                backgroundColor: ['#8b5cf6', '#f1f5f9'],
                borderWidth: 0
            }]
        },
        options: {
            cutout: '80%',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false },
                datalabels: { display: false }
            }
        }
    });

    // Chart 3: Yearly Income Bar Chart
    const yearlyDataYears = getPlausibleFiscalYears(ppfsItems.map(item => item.fiscalYear));
    const yearlyDataMap = {};
    const yearlyChartYears = yearlyDataYears.length > 0 ? yearlyDataYears : getAllFiscalYears();
    yearlyChartYears.forEach(yr => {
        yearlyDataMap[yr] = { claim: 0, approve: 0, error: 0 };
    });

    yearlyChartYears.forEach(yr => {
        const yrItems = ppfsItems.filter(item => String(item.fiscalYear) === String(yr));
        if (yrItems.length > 0) {
            let c = 0, a = 0, e = 0;
            yrItems.forEach(item => {
                c += item.claimAmount || 0;
                a += item.approvedAmount || 0;
                e += item.pendingAmount || 0;
            });
            yearlyDataMap[yr] = { claim: c, approve: a, error: e };
        }
    });

    if (ppfsYearlyChart) ppfsYearlyChart.destroy();
    const ctxYearly = document.getElementById('ppfsYearlyChart').getContext('2d');
    ppfsYearlyChart = new Chart(ctxYearly, {
        type: 'bar',
        data: {
            labels: yearlyChartYears,
            datasets: [
                {
                    label: 'ยอดขอเบิก',
                    data: yearlyChartYears.map(yr => yearlyDataMap[yr].claim),
                    backgroundColor: '#3b82f6',
                    borderRadius: 4
                },
                {
                    label: 'ยอดอนุมัติจ่ายจริง',
                    data: yearlyChartYears.map(yr => yearlyDataMap[yr].approve),
                    backgroundColor: '#10b981',
                    borderRadius: 4
                },
                {
                    label: 'ติด C / Error',
                    data: yearlyChartYears.map(yr => yearlyDataMap[yr].error),
                    backgroundColor: '#f59e0b',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: function(value) { return (value / 1000000).toFixed(2); },
                    font: { size: 9, weight: 'bold' }
                }
            },
            scales: {
                y: { 
                    title: { display: true, text: 'ล้านบาท' },
                    ticks: {
                        callback: function(value) { return (value / 1000000).toFixed(1); }
                    }
                }
            }
        },
        plugins: window.ChartDataLabels ? [window.ChartDataLabels] : []
    });

    // Chart 4: Proportion Share (Doughnut)
        // Chart 4: Proportion Share (Doughnut)
    let propLabels = [];
    let propData = [];

    if (ppfsItems.length > 0) {
        let fundBreakdown = {};
        ppfsItems.forEach(item => {
            if (item.subFundsBreakdown) {
                item.subFundsBreakdown.forEach(sub => {
                    const subCode = resolvePpfsSubFundCode(sub);
                    if (!subCode) return;
                    fundBreakdown[subCode] = (fundBreakdown[subCode] || 0) + getPpfsSubFundRevenue(sub);
                });
            }
        });
        const sortedBreakdown = Object.keys(fundBreakdown).sort((a,b) => fundBreakdown[b] - fundBreakdown[a]);
        if (sortedBreakdown.length > 0) {
            propLabels = sortedBreakdown.slice(0, 4);
            propData = propLabels.map(l => fundBreakdown[l]);
            let otherSum = 0;
            sortedBreakdown.slice(4).forEach(l => otherSum += fundBreakdown[l]);
            if (otherSum > 0 || sortedBreakdown.length > 4) {
                propLabels.push('อื่นๆ');
                propData.push(otherSum);
            }
        }
    }

    if (ppfsProportionChart) ppfsProportionChart.destroy();
    const ctxProp = document.getElementById('ppfsProportionChart').getContext('2d');
    ppfsProportionChart = new Chart(ctxProp, {
        type: 'doughnut',
        data: {
            labels: propLabels,
            datasets: [{
                data: propData,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#94a3b8', '#14b8a6', '#f43f5e']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                datalabels: {
                    formatter: (value, ctx) => {
                        let sum = 0;
                        let dataArr = ctx.chart.data.datasets[0].data;
                        dataArr.map(data => { sum += data; });
                        let percentage = (value*100 / sum).toFixed(1)+"%";
                        return percentage;
                    },
                    color: '#fff',
                    font: { weight: 'bold', size: 10 }
                }
            }
        },
        plugins: window.ChartDataLabels ? [window.ChartDataLabels] : []
    });

    // 5. Populate Tree Table Dynamically
    const dynamicGroupsMap = {};
    let grandTarget = 0;
    let grandActual = 0;
    let grandRevenue = 0;

    // Initialize all mapped activities so they always appear
    Object.keys(SUB_FUND_MAPPING).forEach((code, idx) => {
        const desc = SUB_FUND_MAPPING[code];
        dynamicGroupsMap[code] = {
            id: 'grp_' + idx,
            name: desc,
            itemName: code,
            target: 0,
            actual: 0,
            revenue: 0,
            childrenMap: {}
        };
    });

    if (ppfsItems.length > 0) {
        ppfsItems.forEach(item => {
            if (item.subFundsBreakdown) {
                item.subFundsBreakdown.forEach(sub => {
                    const matchedCode = resolvePpfsSubFundCode(sub);
                    if (!matchedCode) return;
                    
                    if (!dynamicGroupsMap[matchedCode]) {
                        const mappedResult = mapSubFundName(matchedCode);
                        const mappedName = mappedResult.includes('=') ? mappedResult.split('=')[1] : mappedResult;
                        dynamicGroupsMap[matchedCode] = {
                            id: 'grp_dyn_' + Object.keys(dynamicGroupsMap).length,
                            name: mappedName,
                            itemName: matchedCode,
                            target: 0,
                            actual: 0,
                            revenue: 0,
                            childrenMap: {}
                        };
                    }
                    
                    const subCount = getPpfsSubFundCount(sub);
                    const subRevenue = getPpfsSubFundRevenue(sub);
                    dynamicGroupsMap[matchedCode].actual += subCount;
                    dynamicGroupsMap[matchedCode].revenue += subRevenue;
                    
                    grandActual += subCount;
                    grandRevenue += subRevenue;

                    const childKey = item.hospital || 'ไม่ระบุหน่วยบริการ';
                    if (!dynamicGroupsMap[matchedCode].childrenMap[childKey]) {
                        dynamicGroupsMap[matchedCode].childrenMap[childKey] = {
                            name: childKey,
                            details: matchedCode,
                            target: 0,
                            actual: 0,
                            revenue: 0,
                            status: 'ปกติ'
                        };
                    }
                    dynamicGroupsMap[matchedCode].childrenMap[childKey].actual += subCount;
                    dynamicGroupsMap[matchedCode].childrenMap[childKey].revenue += subRevenue;
                });
            }
        });
    }

    const groups = Object.values(dynamicGroupsMap).map(g => {
        g.children = Object.values(g.childrenMap);
        delete g.childrenMap;
        return g;
    });

    const tbody = document.getElementById('ppfs-tree-tbody');
    tbody.innerHTML = '';

    groups.forEach(g => {
        const filteredChildren = g.children.filter(c => 
            c.name.toLowerCase().includes(searchVal) || 
            c.details.toLowerCase().includes(searchVal)
        );

        if (searchVal && filteredChildren.length === 0 && !g.name.toLowerCase().includes(searchVal)) {
            return;
        }

        // Parent Row
        tbody.innerHTML += `
            <tr class="ppfs-parent-row" data-group="${g.id}" style="cursor: pointer; background-color: #f8fafc; font-weight: bold; border-bottom: 1px solid #e2e8f0; transition: background 0.15s;">
                <td style="padding: 12px 16px; display: flex; align-items: center; gap: 8px;"><span class="toggle-arrow" id="arrow-${g.id}" style="display: inline-block; transition: transform 0.2s; font-size: 11px;">▶</span> ${g.name}</td>
                <td style="padding: 12px 16px; color: #64748b;">${g.itemName}</td>
                <td style="padding: 12px 16px; text-align: right;">${g.actual.toLocaleString()}</td>
                <td style="padding: 12px 16px; text-align: right; color: #3b82f6;">฿${g.revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td style="padding: 12px 16px; text-align: center; color: #94a3b8;">-</td>
            </tr>
        `;

        // Child Rows
        filteredChildren.forEach(c => {
            tbody.innerHTML += `
                <tr class="ppfs-child-row ppfs-group-${g.id}" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;">
                    <td style="padding: 12px 16px 12px 32px; color: #475569;">${c.name}</td>
                    <td style="padding: 12px 16px; color: #64748b;">${c.details}</td>
                    <td style="padding: 12px 16px; text-align: right; color: #64748b;">${c.actual.toLocaleString()}</td>
                    <td style="padding: 12px 16px; text-align: right; color: #0f172a;">฿${c.revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td style="padding: 12px 16px; text-align: center;"><span class="badge badge-green" style="background-color: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 500;">${c.status}</span></td>
                </tr>
            `;
        });
    });

    // Grand Total Footer Row
    tbody.innerHTML += `
        <tr style="font-weight: bold; background-color: #f8fafc; border-top: 2px solid #cbd5e1;">
            <td style="padding: 12px 16px; color: #0f172a;">รวมทั้งหมด ${groups.length} กลุ่ม, ${grandActual.toLocaleString()} รายการ</td>
            <td style="padding: 12px 16px; color: #64748b;">-</td>
            <td style="padding: 12px 16px; text-align: right;">${grandActual.toLocaleString()}</td>
            <td style="padding: 12px 16px; text-align: right; color: #10b981; font-size: 15px;">฿${grandRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td style="padding: 12px 16px; text-align: center;"><span class="badge badge-green" style="background-color: #dcfce7; color: #15803d; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: bold;">รวม</span></td>
        </tr>
    `;

    // Collapsible Trigger Logic
    document.querySelectorAll('.ppfs-parent-row').forEach(row => {
        row.addEventListener('click', () => {
            const groupId = row.getAttribute('data-group');
            const children = document.querySelectorAll(`.ppfs-child-row.ppfs-group-${groupId}`);
            const arrow = document.getElementById(`arrow-${groupId}`);
            
            let isHidden = false;
            children.forEach(child => {
                if (child.style.display === 'none') {
                    child.style.display = 'table-row';
                } else {
                    child.style.display = 'none';
                    isHidden = true;
                }
            });
            
            if (arrow) {
                arrow.style.transform = isHidden ? 'rotate(-90deg)' : 'none';
            }
        });
    });
}

window.deleteFile = function(fileName) {
    if (!confirm(`เนเธเธเธธเธ“เนเธเธเนเธเธเธญเธเนเธเธเนเธเธเนเธเธเธฅเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฅเธเธญเธเนเธเธเนเธเธเนเธเธ ${fileName || 'เนเธเธเนเธเธเนเธเธเนเธเธเธตเธเนเธเธเนเธเธเนเธเธ'} เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ?`)) return;
    
    const targetName = fileName || '';
    const oldLength = database.length;
    
    const newData = database.filter(item => {
        const itemFileName = item.fileName || '';
        return itemFileName !== targetName;
    });
    
    if (newData.length === oldLength) {
        alert('เนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฅเธ—เนเธเธเนเธเธเนเธเธเธฅเธ เนเธเธเนเธเธเนเธเธเนเธเธเธฅเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ');
        return;
    }
    
    database.length = 0;
    database.push(...newData);
    
    try {
        localStorage.setItem('nhso_database', JSON.stringify(getDatabaseStoragePayload(database)));
    } catch (e) {
        console.error(e);
    }
    
    alert('เธฅเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธขเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ');
    recordAppLog('success', 'delete-file', `เธฅเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธเธฅเธเธญเธเนเธเธเนเธเธเนเธเธ ${fileName || 'เนเธเธเนเธเธเนเธเธเนเธเธเธตเธเนเธเธเนเธเธเนเธเธ'} เนเธเธเธญเธเนเธเธเธฒเธเนเธเธเธฐเธเนเธเธเนเธเธเนเธเธเนเธเธเนเธเธ`, {
        fileName: fileName || '',
        tab: activeTab,
        details: {
            before: oldLength,
            after: newData.length
        }
    });
    
    if (typeof loadDataFromLocalStorage === 'function') {
        loadDataFromLocalStorage();
        if (typeof renderDashboard === 'function') renderDashboard();
    } else {
        location.reload();
    }
};






window.handleUserAction = handleUserAction;
window.manageUser = manageUser;
window.approveUser = approveUser;
window.rejectUser = rejectUser;
window.toggleUserAccess = toggleUserAccess;
window.resetUserPassword = resetUserPassword;
window.updateUserStatus = updateUserStatus;

const app = document.getElementById("app");
const appBaseUrl = new URL("./", document.baseURI);
const initialUrl = new URL(window.location.href);

function appUrl(path) {
  return new URL(String(path).replace(/^\/+/, ""), appBaseUrl).toString();
}

const state = {
  session: null,
  activeView: sessionStorage.getItem("activeView") || "dashboard",
  loading: true,
  notificationsOpen: false,
  sidebarOpen: window.innerWidth > 920,
  notifications: [],
  dashboard: null,
  locations: [],
  drugs: [],
  boxes: [],
  alerts: [],
  reports: null,
  users: [],
  auditLogs: [],
  userEditor: null,
  locationEditor: null,
  drugEditor: null,
  boxEditor: null,
  boxDetail: sessionStorage.getItem("activeView") === "boxDetail" && sessionStorage.getItem("boxDetailId") ? { boxId: sessionStorage.getItem("boxDetailId"), box: null, items: [], rounds: [] } : null,
  modal: null,
  confirmModal: null,
  pagination: { boxes: { page: 1, pageSize: 10 }, locations: { page: 1, pageSize: 10 } },
  syncing: false,
  lastSyncedAt: null,
  notice: "",
  error: "",
  showRegister: false,
  appEventsBound: false,
  filters: {
    locations: "",
    drugs: "",
  },
};

let noticeTimer = null;

if (initialUrl.searchParams.get("error")) {
  state.error = initialUrl.searchParams.get("error");
}

const navItems = [
  { view: "dashboard", label: "Dashboard", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  { view: "boxes", label: "กล่องยาฉุกเฉิน", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  { view: "locations", label: "สถานที่", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  { view: "drugs", label: "ยา", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  { view: "alerts", label: "แจ้งเตือน", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  { view: "reports", label: "รายงาน", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  { view: "users", label: "ผู้ใช้งาน", roles: ["super_admin"] },
  { view: "settings", label: "ตั้งค่าระบบ", roles: ["super_admin"] },
  { view: "privacy", label: "ความเป็นส่วนตัว", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  { view: "guide", label: "วิธีการใช้งาน", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  { view: "about", label: "เกี่ยวกับระบบ", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
];

const roleLabel = {
  super_admin: "Super Admin",
  dept_admin: "Admin หน่วยงาน",
  pharmacist: "เภสัชกรรม",
  lab: "LAB",
  supply: "พัสดุ / คลัง",
  viewer: "Viewer",
};

function icon(name, extraClass = "") {
  const shapes = {
    dashboard:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4h4A1.5 1.5 0 0 1 11 5.5v4A1.5 1.5 0 0 1 9.5 11h-4A1.5 1.5 0 0 1 4 9.5v-4ZM13 5.5A1.5 1.5 0 0 1 14.5 4h4A1.5 1.5 0 0 1 20 5.5v6A1.5 1.5 0 0 1 18.5 13h-4A1.5 1.5 0 0 1 13 11.5v-6ZM4 14.5A1.5 1.5 0 0 1 5.5 13h4A1.5 1.5 0 0 1 11 14.5v4A1.5 1.5 0 0 1 9.5 20h-4A1.5 1.5 0 0 1 4 18.5v-4ZM13 15a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-3a2 2 0 0 1-2-2v-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
    box:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6.5 8.5 12 5l5.5 3.5v6L12 18l-5.5-3.5v-6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M6.8 8.8 12 12l5.2-3.2M12 12v6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    pin:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 21s6-5.4 6-10a6 6 0 1 0-12 0c0 4.6 6 10 6 10Z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="11" r="2.1" stroke="currentColor" stroke-width="1.6"/></svg>',
    pill:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14.8 5.2 5.2 14.8a4 4 0 1 0 5.7 5.7l9.6-9.6a4 4 0 0 0-5.7-5.7Z" stroke="currentColor" stroke-width="1.6"/><path d="M9 11l4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    bell:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M15 17H9m7-1.5a1 1 0 0 0 .8-1.6c-.8-1.1-1.1-2.5-1.1-4.3a4.7 4.7 0 1 0-9.4 0c0 1.8-.3 3.2-1.1 4.3a1 1 0 0 0 .8 1.6h10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
    report:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4.5h7l3 3V19.5H7z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M14 4.5v3h3M9 11h6M9 14h6M9 17h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    shield:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4.5 18 7v5.2c0 3.7-2.3 6.4-6 8.3-3.7-1.9-6-4.6-6-8.3V7l6-2.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
    book:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 5.5h10a2 2 0 0 1 2 2V19H8a2 2 0 0 0-2 2V5.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M8 9h8M8 12h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    info:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 10.5v5M12 7.8h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    money:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 7.5h14v9H5z" stroke="currentColor" stroke-width="1.6"/><path d="M8 12h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="12" r="2.2" stroke="currentColor" stroke-width="1.6"/></svg>',
    hourglass:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 4.5h10M7 19.5h10M8.5 5.5c0 2.5 1.7 4.2 3.5 5.5-1.8 1.3-3.5 3-3.5 5.5M15.5 5.5c0 2.5-1.7 4.2-3.5 5.5 1.8 1.3 3.5 3 3.5 5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    alert:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4.5 20 18H4z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M12 9v4.2M12 16.8h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    check:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 12l5 5L20 7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    sync:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4.5 12a7.5 7.5 0 0 1 12.7-5.4L19 8.5M19.5 12a7.5 7.5 0 0 1-12.7 5.4L5 15.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M19 5v3.5h-3.5M5 19v-3.5h3.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    edit:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 16.5V19h2.5L17.6 8.9l-2.5-2.5L5 16.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m14 6 2.5 2.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    logout:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 17.5H6.5A1.5 1.5 0 0 1 5 16V8A1.5 1.5 0 0 1 6.5 6.5H10M14.5 8.5 18 12l-3.5 3.5M17.5 12H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    lock:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 10V8a5 5 0 1 1 10 0v2M6 10.5h12V19H6z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
    user:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 13.5a4.2 4.2 0 1 0 0-8.4 4.2 4.2 0 0 0 0 8.4ZM5.5 19.5c1.2-3 3.7-4.5 6.5-4.5s5.3 1.5 6.5 4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    users:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9.5 11a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4ZM16.8 11.5a2.4 2.4 0 1 0 0-4.8 2.4 2.4 0 0 0 0 4.8M4.7 18.5c.8-2.4 2.7-3.6 4.8-3.6s4 1.2 4.8 3.6M14.8 18.2c.5-1.5 1.7-2.2 3-2.2s2.5.7 3 2.2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    minus:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 12h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    settings:
      '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>',
  };
  return `<span class="icon ${extraClass}">${shapes[name] || shapes.info}</span>`;
}

function api(path, options = {}) {
  const headers = new Headers(options.headers || {});
  headers.set("Accept", "application/json");
  if (options.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }
  return fetch(appUrl(path), {
    credentials: "include",
    ...options,
    headers,
  }).then(async (response) => {
    const text = await response.text();
    let payload = null;
    try {
      payload = text ? JSON.parse(text) : null;
    } catch {
      payload = { raw: text };
    }
    if (!response.ok) {
      const error = new Error(payload?.error || payload?.message || `Request failed (${response.status})`);
      error.status = response.status;
      error.payload = payload;
      throw error;
    }
    return payload;
  });
}

function isAllowed(view) {
  if (!state.session) return false;
  const role = state.session.role;
  const item = navItems.find((entry) => entry.view === view);
  return !item || item.roles?.includes(role);
}

function closeTransientUI() {
  state.modal = null;
  state.confirmModal = null;
  state.notificationsOpen = false;
  if (window.innerWidth <= 920) {
    state.sidebarOpen = false;
  }
  state.userEditor = null;
  state.locationEditor = null;
  state.drugEditor = null;
  state.boxEditor = null;
}

function clearNotice() {
  state.notice = "";
  if (noticeTimer) {
    clearTimeout(noticeTimer);
    noticeTimer = null;
  }
}

function showNotice(message, duration = 1800) {
  state.notice = message;
  if (noticeTimer) {
    clearTimeout(noticeTimer);
    noticeTimer = null;
  }
  if (duration > 0) {
    noticeTimer = window.setTimeout(() => {
      state.notice = "";
      noticeTimer = null;
      render();
    }, duration);
  }
  render();
}

function upsertById(list, item) {
  const index = list.findIndex((entry) => String(entry.id) === String(item.id));
  if (index >= 0) {
    list[index] = item;
  } else {
    list.unshift(item);
  }
}

function removeById(list, id) {
  const index = list.findIndex((entry) => String(entry.id) === String(id));
  if (index >= 0) {
    list.splice(index, 1);
  }
}

async function loadSession() {
  state.loading = true;
  try {
    state.session = await api("/api/auth/me");
    if (state.session) {
      state.error = "";
      state.sidebarOpen = window.innerWidth > 920;
      state.activeView = isAllowed(state.activeView) ? state.activeView : "dashboard";
      if ((state.session.approvalStatus || state.session.status) === "active") {
        await loadCurrentView();
        // connectEvents(); // Disabled to prevent 404 polling on Laravel
      } else if (eventSource) {
        eventSource.close();
        eventSource = null;
      }
    }
  } catch (error) {
    if (error.status === 401 || error.status === 403) {
      state.error = "";
    } else {
      state.error = error.message;
    }
  } finally {
    state.loading = false;
    render();
  }
}

async function loadCurrentView() {
  if (!state.session) return;
  
  // Always load active alerts list to update sidebar counts
  try {
    const alertsData = await api("/api/alerts");
    state.alerts = alertsData.alerts || [];
    state.notifications = alertsData.notifications || [];
  } catch (err) {
    console.error("Failed to load alerts for sidebar:", err);
  }

  const view = state.activeView;
  if (view === "dashboard") {
    const data = await api("/api/dashboard");
    state.dashboard = data.dashboard;
  } else if (view === "boxes") {
    const [data, boxes, locations] = await Promise.all([
      api("/api/dashboard"),
      api("/api/boxes"),
      api("/api/locations"),
    ]);
    state.dashboard = data.dashboard;
    state.boxes = boxes;
    state.locations = locations;
  } else if (view === "locations") {
    state.locations = await api("/api/locations");
  } else if (view === "drugs") {
    state.drugs = await api("/api/drugs");
  } else if (view.startsWith("alerts_")) {
    // Already loaded globally above
  } else if (view === "reports") {
    state.reports = await api("/api/reports/usage");
    state.reports ||= { usage: [], summary: "" };
  } else if (view === "users") {
    const [users, auditLogs, locations] = await Promise.all([
      api("/api/users"),
      api("/api/audit-logs?limit=50"),
      api("/api/locations"),
    ]);
    state.users = users || [];
    state.auditLogs = auditLogs.logs || [];
    state.locations = locations || [];
  } else if (view === "guide" || view === "about") {
    const data = await api("/api/dashboard");
    state.dashboard = data.dashboard;
  } else if (view === "boxDetail") {
    const boxId = state.boxDetail?.boxId;
    if (boxId) {
      const [box, items, rounds, locations, drugs] = await Promise.all([
        api(`/api/boxes/${boxId}`),
        api(`/api/boxes/${boxId}/items`),
        api(`/api/boxes/${boxId}/rounds`),
        api(`/api/locations`),
        api(`/api/drugs`),
      ]);
      state.boxDetail = {
        boxId,
        box,
        items,
        rounds,
      };
      state.locations = locations;
      state.drugs = drugs;
    }
  }
}

async function refreshNotifications() {
  if (!state.session) return;
  const data = await api("/api/notifications");
  state.notifications = data.notifications || [];
  render();
}

let eventSource = null;
/*
function connectEvents() {
  if (eventSource) {
    eventSource.close();
  }
  eventSource = new EventSource(appUrl("/api/events"), { withCredentials: true });
  eventSource.addEventListener("notification", () => refreshNotifications().catch(() => null));
  eventSource.addEventListener("refresh", () => {
    loadCurrentView().then(render).catch(() => null);
  });
}
*/

function pillTone(status) {
  if (status?.includes("พร้อมใช้งาน")) return "pill--success";
  if (status?.includes("รอเติมยา") || status?.includes("กำลังตรวจสอบ")) return "pill--warning";
  if (status?.includes("ใช้งาน") || status?.includes("พร้อมใช้") || status?.includes("รับรอง")) return "pill--success";
  if (status?.includes("ใกล้หมด") || status?.includes("ใกล้")) return "pill--warning";
  if (status?.includes("หมด")) return "pill--danger";
  return "pill--info";
}

function loginScreen() {
  return `
    <main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #164e3f; padding: 24px; font-family: 'Sarabun', 'Inter', sans-serif;">
      <div style="background: #f8fafc; border-radius: 20px; padding: 48px 40px; width: 100%; max-width: 500px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); text-align: center;">
        <div style="margin-bottom: 24px; display: flex; justify-content: center;">
          <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
             <span style="color: #0d905a; font-weight: 800; font-size: 32px; letter-spacing: 1px;">MEDICAL</span>
             <span style="background: #0d905a; color: white; font-weight: 800; font-size: 32px; padding: 2px 10px; border-radius: 8px;">BOX</span>
          </div>
        </div>

        <div id="localAdminPanel" style="text-align: left; margin-bottom: 24px;">
          <form id="loginForm">
            <label style="display: block; color: #334155; font-size: 14px; font-weight: 700; margin-bottom: 8px;">ชื่อผู้ใช้</label>
            <input type="text" name="username" autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="กรอกชื่อผู้ใช้" required style="width: 100%; padding: 14px 16px; margin-bottom: 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; background: white; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#0d905a'" onblur="this.style.borderColor='#cbd5e1'" />
            <label style="display: block; color: #334155; font-size: 14px; font-weight: 700; margin-bottom: 8px;">รหัสผ่าน</label>
            <input type="password" name="password" autocomplete="current-password" placeholder="กรอกรหัสผ่าน" required style="width: 100%; padding: 14px 16px; margin-bottom: 20px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; background: white; outline: none; transition: border-color 0.2s;" onfocus="this.style.borderColor='#0d905a'" onblur="this.style.borderColor='#cbd5e1'" />
            <button type="submit" style="width: 100%; padding: 14px; background: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 700; transition: background 0.2s;" onmouseover="this.style.background='#1e293b'" onmouseout="this.style.background='#0f172a'">เข้าสู่ระบบ</button>
          </form>
        </div>

        <div style="display: grid; gap: 14px; margin-bottom: 16px;">
          <button type="button" id="btnGoRegister" style="width: 100%; padding: 14px; background: #0d905a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 700; box-shadow: 0 4px 12px rgba(13,144,90,0.2); transition: background 0.2s;" onmouseover="this.style.background='#0b7a4c'" onmouseout="this.style.background='#0d905a'">
            ลงทะเบียนผู้ใช้งานใหม่
          </button>
          <button type="button" id="btnLoginThaid" onclick="alert('กำลังพัฒนา')" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 14px; background: white; border: 1px solid #cbd5e1; border-radius: 8px; color: #0f172a; font-size: 16px; font-weight: 700; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
            <span style="width: 26px; height: 26px; background: #0f172a; color: #fbbf24; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800;">ThaID</span>
            Login ด้วย ThaID
          </button>
        </div>

        ${state.error ? `<div style="color: #ef4444; margin-top: 16px; font-size: 14px; text-align: center; font-weight: 600;">${state.error}</div>` : ""}

        <p style="color: #94a3b8; font-size: 13px; margin-top: 32px; font-weight: 500;">
          โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา
        </p>
      </div>
    </main>
  `;
}

function registerScreen() {
  return `
    <main class="login-screen new-login">
      <div class="new-auth-container">
        <div class="new-auth-left">
          <h2 class="auth-steps-title">ลงทะเบียนผู้ใช้งานใหม่</h2>
          <p class="auth-steps-subtitle">สำหรับเจ้าหน้าที่เพื่อขอเข้าสู่ระบบ</p>
          <ul class="auth-steps-list">
            <li><span>1</span> กรอกอีเมล หรือ ชื่อผู้ใช้งาน</li>
            <li><span>2</span> ระบุชื่อ-นามสกุล</li>
            <li><span>3</span> ตั้งรหัสผ่านและยืนยันรหัสผ่าน</li>
            <li><span>4</span> รอการอนุมัติสิทธิ์การใช้งานจากผู้ดูแลระบบ</li>
          </ul>
        </div>
        <div class="new-auth-right">
          <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 24px;">
             <span style="color: #0d905a; font-weight: 800; font-size: 28px; letter-spacing: 1px;">MEDICAL</span>
             <span style="background: #0d905a; color: white; font-weight: 800; font-size: 28px; padding: 2px 10px; border-radius: 8px;">BOX</span>
          </div>
          <form class="auth-form new-auth-form" id="registerForm">
            <input type="text" name="providerId" class="new-auth-input" placeholder="อีเมล หรือ ชื่อผู้ใช้งาน" required />
            <input type="text" name="displayName" class="new-auth-input" placeholder="ชื่อ-นามสกุล" required />
            <input type="password" name="password" class="new-auth-input" placeholder="รหัสผ่าน" required />
            <input type="password" name="confirmPassword" class="new-auth-input" placeholder="ยืนยันรหัสผ่าน" required />
            <div style="text-align: center;">
              <button type="submit" class="register-button" style="margin-top: 10px;">
                ยืนยันการลงทะเบียน
              </button>
            </div>
          </form>
          
          <div class="auth-divider">
            <span>หรือ</span>
          </div>
          
          <div class="auth-register-section">
            <p>หากคุณมีบัญชีอยู่แล้ว</p>
            <button type="button" class="thaid-button" id="btnGoLogin">กลับไปหน้าเข้าสู่ระบบ</button>
          </div>
          
          ${state.error ? `<div class="auth-error">${state.error}</div>` : ""}
        </div>
      </div>
    </main>
  `;
}

function pendingApprovalScreen() {
  return `
    <main class="login-screen new-login">
      <div class="new-auth-container" style="max-width: 600px;">
        <div class="new-auth-right" style="width: 100%; border-radius: 12px; padding: 48px;">
          <div style="text-align: center;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 24px;">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <h2 style="font-size: 24px; font-weight: 600; color: #1e293b; margin-bottom: 16px;">รอการอนุมัติการใช้งาน</h2>
            <p style="color: #64748b; margin-bottom: 24px; line-height: 1.6;">
              บัญชีผู้ใช้งานของคุณ (<b>${state.session.displayName}</b>) ได้รับการลงทะเบียนเรียบร้อยแล้ว<br>
              ขณะนี้อยู่ในระหว่างรอการตรวจสอบและอนุมัติจากผู้ดูแลระบบ
            </p>
            <button type="button" class="thaid-button" id="btnLogoutPending" style="max-width: 200px; margin: 0 auto;">ออกจากระบบ</button>
          </div>
        </div>
      </div>
    </main>
  `;
}

function bindPendingEvents() {
  const btnLogout = document.getElementById("btnLogoutPending");
  if (btnLogout) {
    btnLogout.addEventListener("click", () => {
      api("/api/auth/logout", { method: "POST" })
        .then(() => {
          state.session = null;
          render();
        })
        .catch(() => {
          state.session = null;
          render();
        });
    });
  }
}

function shell() {
  const session = state.session;
  const displayName = session.displayName || session.username || "User";
  const unread = (state.notifications || []).filter((n) => !(n.readBy || []).includes(session.id)).length;
  
  const mainNav = [
    { view: "dashboard", label: "Dashboard", icon: "dashboard", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
    { view: "boxes", label: "กล่องยาฉุกเฉิน", icon: "box", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
    { view: "locations", label: "สถานที่", icon: "pin", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
    { view: "drugs", label: "ยา", icon: "pill", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
  ].filter(item => item.roles?.includes(session.role));

  const totalAlerts = (state.alerts || []).length;

  const mainNavAfter = [
    { view: "reports", label: "รายงาน", icon: "report", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
    { view: "users", label: "ผู้ใช้งาน", icon: "users", roles: ["super_admin"] },
    { view: "settings", label: "ตั้งค่าระบบ", icon: "settings", roles: ["super_admin"] },
  ].filter(item => item.roles?.includes(session.role));

  return `
    <div class="app-shell${!state.sidebarOpen ? " sidebar-collapsed" : ""}">
      <aside class="sidebar${state.sidebarOpen ? " is-open" : ""}" id="sidebar" aria-label="Main navigation">
        <div class="sidebar__brand" style="border-bottom: none; padding-bottom: 0;">
          <div>
            <div class="sidebar__title" style="font-size: 1.15rem; font-weight: 700; color: var(--muted); text-transform: none; letter-spacing: normal;">Menu</div>
          </div>
        </div>
        <nav class="nav">
          ${mainNav
            .map(
              (item) => `
                <button class="nav__item${state.activeView === item.view ? " is-active" : ""}" data-view="${item.view}">
                  <span class="nav__icon">${icon(item.icon, `icon-colored-${item.icon}`)}</span>
                  ${item.label}
                </button>
              `,
            )
            .join("")}
            
          <button class="nav__item${state.activeView === 'alerts' ? " is-active" : ""}" data-view="alerts">
            <span class="nav__icon">${icon("bell", "icon-colored-hourglass")}</span>
            แจ้งเตือน
            ${totalAlerts ? `<span class="nav__badge" style="margin-left: auto; box-shadow: none;">${totalAlerts}</span>` : ""}
          </button>
          
          ${mainNavAfter
            .map(
              (item) => `
                <button class="nav__item${state.activeView === item.view ? " is-active" : ""}" data-view="${item.view}">
                  <span class="nav__icon">${icon(item.icon, `icon-colored-${item.icon}`)}</span>
                  ${item.label}
                </button>
              `,
            )
            .join("")}
        </nav>
        <div class="sidebar__footer">
          <button class="sidebar-link" data-view="privacy">${icon("lock", "icon-colored-lock")} ความเป็นส่วนตัว</button>
          <button class="sidebar-link" data-view="guide">${icon("book", "icon-colored-guide")} วิธีการใช้งาน</button>
          <button class="sidebar-link" data-view="about">${icon("info", "icon-colored-info")} เกี่ยวกับระบบ</button>
          <button class="sidebar-link sidebar-link--danger" id="logoutButton">${icon("logout", "icon-colored-logout")} ออกจากระบบ</button>
        </div>
      </aside>
      <div class="backdrop${state.sidebarOpen ? " is-open" : ""}" id="backdrop"></div>
      <main class="main">
        <header class="topbar">
          <button class="icon-button topbar__menu" id="menuButton" aria-label="Open menu">
            <span></span><span></span><span></span>
          </button>
          <div class="topbar__heading">
            <div class="topbar__eyebrow">${session.settings?.hospitalName || "Medical Box Monitor"}</div>
            <h1>${titleForView(state.activeView)}</h1>
          </div>
          <div class="topbar__actions">
            <button class="ghost-button" id="notificationButton">
              <span class="ghost-button__icon">${icon("bell")}</span>
              แจ้งเตือน
              ${unread ? `<span class="nav__badge" style="margin-left: 6px; box-shadow: none;">${unread}</span>` : ""}
            </button>
            <button class="ghost-button" id="syncButton" title="โหลดข้อมูลล่าสุดจากฐานข้อมูล" ${state.syncing ? "disabled" : ""}>
              ${icon("sync")} ${state.syncing ? "กำลังรีเฟรช..." : "รีเฟรชข้อมูล"}
            </button>
            <div class="user-chip">
              <div class="user-chip__avatar">${displayName.slice(0, 1)}</div>
              <div>
                <div class="user-chip__name">${displayName}</div>
                <div class="user-chip__role">${roleLabel[session.role]}</div>
              </div>
            </div>
          </div>
        </header>
        ${notificationsDrawer()}
        ${state.notice ? `<div class="app-toast app-toast--success">${escapeHtml(state.notice)}</div>` : ""}
        ${state.error ? `<div class="app-toast app-toast--error">${escapeHtml(state.error)}</div>` : ""}
        <section class="page">
          ${viewContent()}
        </section>
        
        <footer class="app-footer" style="margin-top: auto; padding-top: 24px; border-top: 1px solid var(--line); display: flex; flex-direction: column; gap: 4px; font-size: 0.8rem; color: var(--muted);">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <span>© 2026 โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา</span>
            <span>ผู้ใช้งาน: ${escapeHtml(displayName)}</span>
          </div>
          <div style="font-size: 0.72rem; color: var(--muted); opacity: 0.8;">
            developer by Kr@pong
          </div>
        </footer>
      </main>
      ${state.confirmModal ? `
        <div class="modal-overlay" style="z-index: 3000;">
          <div class="modal-card" style="max-width: 400px; text-align: center; padding-top: 40px;">
            <h3 style="margin-bottom: 16px; font-size: 1.25rem;">${escapeHtml(state.confirmModal.message)}</h3>
            <div style="display: flex; gap: 12px; justify-content: center; margin-top: 24px;">
              <button class="secondary-button" data-confirm-cancel style="flex: 1;">ยกเลิก</button>
              <button class="primary-button" data-confirm-ok style="flex: 1; background: #ef4444; border-color: #ef4444;">ยืนยัน</button>
            </div>
          </div>
        </div>
      ` : ""}
    </div>
  `;
}

function titleForView(view) {
  const titles = {
    dashboard: "Dashboard",
    boxes: "กล่องยาฉุกเฉิน",
    boxDetail: "รายละเอียดกล่องยาฉุกเฉิน",
    locations: "สถานที่",
    drugs: "ยา",
    alerts_drug: "ยา",
    alerts_medical_supply: "เวชภัณฑ์",
    alerts_reagent: "น้ำยา",
    alerts_lab_test_kit: "LAB / Test Kit",
    alerts: "แจ้งเตือน",
    reports: "รายงาน",
    users: "ผู้ใช้งาน",
    privacy: "ความเป็นส่วนตัว",
    guide: "วิธีการใช้งาน",
    about: "เกี่ยวกับระบบ",
  };
  return titles[view] || "Dashboard";
}

function notificationsDrawer() {
  const visible = (state.notifications || []).slice(0, 5);
  return `
    <aside class="notifications${state.notificationsOpen ? " is-open" : ""}" aria-label="Notifications">
      <div class="notifications__head">
        <strong>การแจ้งเตือน</strong>
        <button class="icon-button" id="closeNotifications" aria-label="Close">${icon("minus")}</button>
      </div>
      <div class="notifications__list">
        ${visible.length ? visible.map((item) => `
          <article class="notification notification--${item.severity}">
            <div class="notification__title">${item.title}</div>
            <div class="notification__body">${item.body}</div>
            <div class="notification__meta">${formatThaiDateTime(item.createdAt, item.created_at)}</div>
          </article>
        `).join("") : `<div class="empty-state">ยังไม่มีการแจ้งเตือนใหม่</div>`}
      </div>
    </aside>
  `;
}

function metricCard(metric) {
  const hasItems = metric.items && metric.items.length > 0;
  const tooltipContent = hasItems ? `
    <div class="metric-card__tooltip">
      <div style="font-weight: 700; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 4px;">
        ${metric.label === "รายการหมดอายุ" ? "รายการที่หมดอายุแล้ว" : "รายการที่ใกล้หมดอายุ"}
      </div>
      <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; max-height: 250px; overflow-y: auto;">
        ${metric.items.map(item => `
          <li style="line-height: 1.3; font-size: 0.78rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 4px;">
            • <strong>${escapeHtml(item.drug_name)}</strong> 
            (${item.quantity} ${escapeHtml(item.unit || "ชิ้น")})<br/>
            <span style="color: rgba(255,255,255,0.7); font-size: 0.72rem;">
              กล่อง: ${escapeHtml(item.box_name)} | หมดอายุ: ${formatThaiDate(item.expiry_date)}
            </span>
          </li>
        `).join("")}
      </ul>
    </div>
  ` : "";

  return `
    <article class="metric-card metric-card--${metric.tone} ${hasItems ? 'has-tooltip' : ''}">
      <div class="metric-card__head">
        <span class="metric-card__label">${icon(metric.icon, "icon--lg")} ${metric.label}</span>
        <span class="metric-card__icon">${icon(metric.icon)}</span>
      </div>
      <div class="metric-card__value">${metric.value}</div>
      <div class="metric-card__meta">${metric.meta}</div>
      ${tooltipContent}
    </article>
  `;
}

function notificationBanner() {
  const permission = typeof Notification !== 'undefined' ? Notification.permission : 'default';
  if (permission === 'granted') return '';
  
  return `
    <div class="panel" style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; margin-bottom: 18px; background: #fff; border: 1px solid var(--line); border-radius: 12px; box-shadow: var(--shadow-soft); flex-wrap: wrap; gap: 12px; border-left: 4px solid var(--info);">
      <div style="display: flex; flex-direction: column; gap: 4px;">
        <div style="font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
          การแจ้งเตือนบนเว็บ
          <span class="pill pill--warning" id="notifPermissionLabel" style="font-size: 0.75rem; padding: 2px 8px;">ยังไม่อนุญาต</span>
        </div>
        <div style="font-size: 0.85rem; color: var(--muted);">
          แจ้งเตือนยาใกล้หมดอายุ/หมดอายุในหน่วยงานของคุณ (เฉพาะผู้ที่เปิดการแจ้งเตือน)
        </div>
      </div>
      <button class="primary-button" id="btnEnableNotifications" style="background: #2563eb; border-color: #2563eb; border-radius: 8px; padding: 8px 16px; font-size: 0.88rem;">เปิดการแจ้งเตือน</button>
    </div>
  `;
}

function dashboardView() {
  if (!state.dashboard) return loadingView();
  const { metrics, boxSummary, latestAlerts } = state.dashboard;
  return `
    ${notificationBanner()}
    <section class="metric-grid">
      ${metrics.map(metricCard).join("")}
    </section>
    <section class="content-grid">
      <article class="panel">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">สรุปสถานะกล่องยาฉุกเฉินรายหน่วยงาน</h3>
            <p class="panel__subtitle">ตรวจสอบความพร้อมใช้งานและจำนวนรายการยาในกล่องแต่ละหน่วย</p>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>กล่องยาฉุกเฉิน</th>
                <th>จำนวนรายการ</th>
                <th>สถานะความพร้อม</th>
              </tr>
            </thead>
            <tbody>
              ${boxSummary.map((item) => `
                <tr>
                  <td>
                    <div class="table-title">
                      <strong>${item.id}</strong>
                      <span>${item.name}</span>
                    </div>
                  </td>
                  <td>${item.item_count}</td>
                  <td><span class="pill ${pillTone(item.status)}">${item.status}</span></td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>
      </article>
      <article class="panel">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">ตารางการแจ้งเตือนยาหมดอายุล่าสุด</h3>
            <p class="panel__subtitle">ติดตามกล่องยาฉุกเฉิน ยา จำนวน และสถานะความเสี่ยง</p>
          </div>
          <span class="tag">${icon("bell")} ${latestAlerts.length} รายการ</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>กล่องยาฉุกเฉิน</th>
                <th>ยา</th>
                <th>จำนวน</th>
                <th>สถานะ</th>
                <th>วันหมดอายุ</th>
              </tr>
            </thead>
            <tbody>
              ${latestAlerts.map((item) => `
                <tr>
                  <td>
                    <div class="table-title">
                      <strong>${item.box}</strong>
                      <span>${item.location}</span>
                    </div>
                  </td>
                  <td>${item.drug}</td>
                  <td>${item.qty}</td>
                  <td><span class="pill ${pillTone(item.status)}">${item.status}</span></td>
                  <td>${item.date}</td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>
      </article>
    </section>
    
    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 18px;">
      <button class="primary-button" data-view="boxes" style="background: #2563eb; border-color: #2563eb; display: flex; align-items: center; gap: 6px;">
        ${icon("box")} จัดการกล่องยาฉุกเฉิน
      </button>
      <button class="primary-button" data-view="locations" style="background: #334155; border-color: #334155; display: flex; align-items: center; gap: 6px;">
        ${icon("pin")} จัดการสถานที่
      </button>
      <button class="primary-button" data-view="alerts" style="background: #ea580c; border-color: #ea580c; display: flex; align-items: center; gap: 6px;">
        ${icon("bell")} ดูแจ้งเตือน
      </button>
    </div>
  `;
}

function heroSection(title, eyebrow, desc) {
  return `
    <section class="hero">
      <div class="hero__copy">
        <div class="hero__eyebrow">${eyebrow}</div>
        <h2 class="hero__title">${title}</h2>
        <p class="hero__desc">${desc}</p>
      </div>
      <div class="hero__actions">
        <button class="secondary-button" data-view="guide">${icon("book")} วิธีใช้งาน</button>
        <button class="primary-button" data-view="alerts">${icon("bell")} เปิดการแจ้งเตือน</button>
      </div>
    </section>
  `;
}

function dashboardHero() {
  const totalBoxes = state.dashboard.boxSummary.length;
  const latestAlert = state.dashboard.latestAlerts[0];
  const alertCount = state.dashboard.latestAlerts.length;
  const unread = state.notifications.filter((n) => !(n.readBy || []).includes(state.session.id)).length;
  return `
    <section class="hero hero--dashboard">
      <div class="hero__copy">
        <div class="hero__eyebrow">Hospital command center</div>
        <h2 class="hero__title">ติดตามกล่องยาฉุกเฉินแบบ real-time ด้วยมุมมองที่พร้อมใช้งานจริง</h2>
        <p class="hero__desc">
          ภาพรวมของกล่องยา, แจ้งเตือน, และงานตรวจสอบล่าสุดถูกรวมไว้ในหน้าเดียว เพื่อให้ทีมเห็นสิ่งสำคัญก่อนเสมอ
        </p>
        <div class="hero__actions">
          <button class="secondary-button" data-view="guide">${icon("book")} ดูคู่มือ</button>
          <button class="primary-button" data-view="alerts">${icon("bell")} เปิดการแจ้งเตือน</button>
        </div>
      </div>
      <div class="hero__spotlight">
        <div class="hero__spotlight-head">
          <span class="tag">${icon("sync")} Live</span>
          <span class="hero__spotlight-time">${new Date().toLocaleString("th-TH")}</span>
        </div>
        <div class="hero__spotlight-grid">
          <article class="hero__spotlight-card">
            <span>กล่องทั้งหมด</span>
            <strong>${totalBoxes}</strong>
            <em>หน่วยพร้อมติดตาม</em>
          </article>
          <article class="hero__spotlight-card">
            <span>แจ้งเตือนล่าสุด</span>
            <strong>${alertCount}</strong>
            <em>รายการต้องตรวจสอบ</em>
          </article>
          <article class="hero__spotlight-card">
            <span>Unread</span>
            <strong>${unread}</strong>
            <em>การแจ้งเตือนที่ยังไม่อ่าน</em>
          </article>
        </div>
        <div class="hero__spotlight-foot">
          <div>
            <div class="hero__spotlight-label">สถานะล่าสุด</div>
            <div class="hero__spotlight-value">${latestAlert ? latestAlert.status : "พร้อมใช้งาน"}</div>
          </div>
          <div class="hero__spotlight-meta">${latestAlert ? `${latestAlert.box} - ${latestAlert.drug}` : "ระบบพร้อมใช้งาน"}</div>
        </div>
      </div>
    </section>
  `;
}

function paginationBar(total, page, pageSize, key) {
  const totalPages = Math.max(1, Math.ceil(total / pageSize));
  const start = total ? (page - 1) * pageSize + 1 : 0;
  const end = Math.min(page * pageSize, total);
  return `
    <div class="pagination">
      <span>Items per page:</span>
      <select class="pagination__select" data-page-size="${key}">
        ${[5, 10, 20, 50].map(n => `<option value="${n}" ${pageSize === n ? "selected" : ""}>${n}</option>`).join("")}
      </select>
      <span>${start}-${end} of ${total}</span>
      <div class="pagination__pages">
        <button class="pagination__btn" data-page="${key}:first" ${page <= 1 ? "disabled" : ""}>|&lt;</button>
        <button class="pagination__btn" data-page="${key}:prev"  ${page <= 1 ? "disabled" : ""}>&lt;</button>
        <button class="pagination__btn" data-page="${key}:next"  ${page >= totalPages ? "disabled" : ""}>&gt;</button>
        <button class="pagination__btn" data-page="${key}:last"  ${page >= totalPages ? "disabled" : ""}>&gt;|</button>
      </div>
    </div>
  `;
}

function boxesViewLegacy() {
  const canEdit = ["super_admin", "pharmacist"].includes(state.session.role);
  const pg = state.pagination.boxes;
  const fullList = state.boxes || [];
  const totalPages = Math.max(1, Math.ceil(fullList.length / pg.pageSize));
  const safePageState = Math.min(pg.page, totalPages);
  const start = (safePageState - 1) * pg.pageSize;
  const pagedList = fullList.slice(start, start + pg.pageSize);
  const m = state.modal;
  const isBoxModal = m && m.type === "box";
  const editor = isBoxModal ? m.data : null;

  return `
    <section class="panel">
      <div class="page-banner">
        <div class="page-banner__left">
          <div class="page-banner__icon">${icon("box")}</div>
          <div>
            <div class="page-banner__title">รายการกล่องยาฉุกเฉินทั้งหมด</div>
            <div class="page-banner__desc">ค้นหาและเพิ่มยาอุปกรณ์รายละเอียดกล่องยาฉุกเฉิน</div>
          </div>
        </div>
        ${canEdit ? `<button class="primary-button" data-modal-open="box">+ เพิ่มกล่องยาฉุกเฉิน</button>` : ""}
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ชื่อกล่องยาฉุกเฉิน</th>
              <th>สถานที่</th>
              <th>จำนวนยา (รวม)</th>
              <th>สถานะ</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            ${pagedList.length === 0 ? `<tr><td colspan="5" class="empty-state">ไม่มีข้อมูลกล่องยาฉุกเฉิน</td></tr>` : pagedList.map((item) => `
              <tr>
                <td><strong>${escapeHtml(item.name || item.code || "-")}</strong></td>
                <td>${state.locations.find((l) => l.id === item.locationId)?.name || "-"}</td>
                <td><span style="font-weight:600;color:${item.alertCount > 0 ? "#ef4444" : "var(--muted)"};">${item.alertCount ?? 0}</span></td>
                <td><span class="pill ${pillTone(item.status)}">${item.status}</span></td>
                <td class="row-actions">
                  ${canEdit ? `
                    <button class="action-icon" type="button" data-edit-box="${item.id}" title="แก้ไขกล่อง">${icon("edit")}</button>
                    <button class="action-icon action-icon--danger" type="button" data-delete-box="${item.id}" title="ลบกล่อง">${icon("minus")}</button>
                  ` : "-"}
                </td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
      ${paginationBar(fullList.length, safePageState, pg.pageSize, "boxes")}
    </section>

    ${isBoxModal ? `
      <div class="modal-overlay" data-modal-backdrop>
        <div class="modal-card">
          <div class="modal-card__head">
            <div class="modal-card__title">${editor ? "แก้ไขกล่องยาฉุกเฉิน" : "เพิ่มกล่องยาฉุกเฉินใหม่"}</div>
            <button class="modal-card__close" data-modal-close>ร—</button>
          </div>
          <form class="modal-form" data-form="box">
            <input type="hidden" name="id" value="${editor ? escapeHtml(editor.id) : ""}" />
            <div class="form-field">
              <label>รหัส / ชื่อกล่อง</label>
              <input name="code" placeholder="เช่น BOX-01 หรือ กล่องยา ER" value="${editor ? escapeHtml(editor.name || editor.code || "") : ""}" required />
            </div>
            <div class="form-field">
              <label>สถานที่</label>
              <select name="locationId">
                <option value="">- ไม่มีสถานที่ -</option>
                ${state.locations.map((loc) => `<option value="${loc.id}" ${editor && editor.locationId === loc.id ? "selected" : ""}>${escapeHtml(loc.name)}</option>`).join("")}
              </select>
            </div>
            <div class="form-field">
              <label>สถานะ</label>
              <select name="status">
                <option value="พร้อมใช้"  ${!editor || editor.status === "พร้อมใช้"  ? "selected" : ""}>พร้อมใช้</option>
                <option value="ส่งเติมยา" ${editor?.status === "ส่งเติมยา" ? "selected" : ""}>ส่งเติมยา</option>
                <option value="ตรวจสอบ"  ${editor?.status === "ตรวจสอบ"  ? "selected" : ""}>ตรวจสอบ</option>
              </select>
            </div>
            <div class="modal-form__actions">
              <button class="secondary-button" type="button" data-modal-close>ยกเลิก</button>
              <button class="primary-button" type="submit">${icon("box")} ${editor ? "บันทึก" : "เพิ่มกล่อง"}</button>
            </div>
          </form>
        </div>
      </div>
    ` : ""}
  `;
}

function boxesView() {
  const canEdit = ["super_admin", "pharmacist"].includes(state.session.role);
  const pg = state.pagination.boxes;
  const fullList = state.boxes || [];
  const totalPages = Math.max(1, Math.ceil(fullList.length / pg.pageSize));
  const safePage = Math.min(pg.page, totalPages);
  const start = (safePage - 1) * pg.pageSize;
  const pagedList = fullList.slice(start, start + pg.pageSize);
  const editor = state.modal?.type === "box" ? state.modal.data : null;
  const isBoxModal = state.modal?.type === "box";

  return `
    <section class="panel">
      <div class="page-banner">
        <div class="page-banner__left">
          <div class="page-banner__icon">${icon("box")}</div>
          <div>
            <div class="page-banner__title">รายการกล่องยาฉุกเฉินทั้งหมด</div>
            <div class="page-banner__desc">จัดการชื่อกล่อง สถานที่ จำนวนยา และสถานะการใช้งาน</div>
          </div>
        </div>
        ${canEdit ? `<button class="primary-button" data-modal-open="box">+ เพิ่มกล่องยาฉุกเฉิน</button>` : ""}
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ชื่อกล่องยาฉุกเฉิน</th>
              <th>สถานที่</th>
              <th>จำนวนยา(รวม)</th>
              <th>สถานะ</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            ${pagedList.length === 0 ? `
              <tr><td colspan="5" class="empty-state">ยังไม่มีข้อมูลกล่องยาฉุกเฉิน</td></tr>
            ` : pagedList.map((item) => `
              <tr class="clickable-row" data-box-detail="${item.id}">
                <td><strong>${escapeHtml(item.name || item.code || "-")}</strong></td>
                <td>${escapeHtml(state.locations.find((location) => String(location.id) === String(item.locationId))?.name || "-")}</td>
                <td><strong>${Number(item.drugCount || 0).toLocaleString("th-TH")}</strong></td>
                <td><span class="pill ${pillTone(item.status)}">${escapeHtml(item.status)}</span></td>
                <td class="row-actions">
                  ${canEdit ? `
                    <button class="action-icon" type="button" data-edit-box="${item.id}" title="แก้ไขกล่อง">${icon("edit")}</button>
                    <button class="action-icon action-icon--danger" type="button" data-delete-box="${item.id}" title="ลบกล่อง">${icon("minus")}</button>
                  ` : "-"}
                </td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
      ${paginationBar(fullList.length, safePage, pg.pageSize, "boxes")}
    </section>

    ${isBoxModal ? `
      <div class="modal-overlay" data-modal-backdrop>
        <div class="modal-card modal-card--box">
          <div class="modal-card__head">
            <div class="modal-card__title">${editor ? "แก้ไขข้อมูลกล่องยาฉุกเฉิน" : "เพิ่มกล่องยาฉุกเฉินใหม่"}</div>
            <button class="modal-card__close" type="button" data-modal-close aria-label="ปิด">×</button>
          </div>
          <form class="modal-form" data-form="box">
            <input type="hidden" name="id" value="${editor ? escapeHtml(editor.id) : ""}" />
            <div class="form-field">
              <label>รหัส / ชื่อกล่อง</label>
              <input name="code" placeholder="เช่น BOX-01 หรือ กล่องยา ER" value="${editor ? escapeHtml(editor.name || editor.code || "") : ""}" required />
            </div>
            <div class="form-field">
              <label>สถานที่จัดเก็บ</label>
              <select name="locationId">
                <option value="">- ยังไม่ระบุสถานที่ -</option>
                ${state.locations.map((location) => `
                  <option value="${location.id}" ${editor && String(editor.locationId) === String(location.id) ? "selected" : ""}>${escapeHtml(location.name)}</option>
                `).join("")}
              </select>
            </div>
            <div class="form-field">
              <label>จำนวนยา(รวม)</label>
              <input type="number" name="drugCount" min="0" step="1" inputmode="numeric" placeholder="0" value="${editor ? Number(editor.drugCount || 0) : 0}" required />
            </div>
            <div class="form-field">
              <label>สถานะกล่อง</label>
              <select name="status">
                <option value="พร้อมใช้งาน" ${!editor || ["พร้อมใช้", "พร้อมใช้งาน"].includes(editor.status) ? "selected" : ""}>พร้อมใช้งาน</option>
                <option value="รอเติมยา" ${["รอเติมยา", "ส่งเติมยา"].includes(editor?.status) ? "selected" : ""}>รอเติมยา</option>
                <option value="กำลังตรวจสอบ" ${["กำลังตรวจสอบ", "ตรวจสอบ"].includes(editor?.status) ? "selected" : ""}>กำลังตรวจสอบ</option>
              </select>
            </div>
            <div class="modal-form__actions">
              <button class="secondary-button" type="button" data-modal-close>ยกเลิก</button>
              <button class="primary-button" type="submit">${icon("box")} ${editor ? "บันทึกการแก้ไข" : "เพิ่มกล่อง"}</button>
            </div>
          </form>
        </div>
      </div>
    ` : ""}
  `;
}

function boxDetailView() {
  if (!state.boxDetail || !state.boxDetail.box) {
    return loadingView();
  }

  const { box, items, rounds } = state.boxDetail;
  const location = state.locations.find((l) => String(l.id) === String(box.locationId) || String(l.id) === String(box.location_id));
  const locationName = location ? location.name : "-";
  
  const canEdit = ["super_admin", "pharmacist", "dept_admin"].includes(state.session.role);

  // Helper function to check item status
  const getItemStatus = (expiryDate, alertDays) => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const expiry = new Date(expiryDate);
    expiry.setHours(0, 0, 0, 0);
    const diffTime = expiry - today;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    if (diffDays <= 0) {
      return { label: "หมดอายุแล้ว", tone: "expired" };
    } else if (diffDays <= alertDays) {
      return { label: "ใกล้หมดอายุ", tone: "expiring" };
    } else {
      return { label: "ปกติ", tone: "normal" };
    }
  };

  const isDispatchMode = state.boxDispatchMode;
  let activeItems = isDispatchMode ? state.boxDispatchItems : items;

  // Sorting logic (only apply in view mode)
  if (!isDispatchMode && state.boxDetailSort && state.boxDetailSort.key) {
    const { key, direction } = state.boxDetailSort;
    activeItems = [...activeItems].sort((a, b) => {
      let valA = a[key] ?? "";
      let valB = b[key] ?? "";
      if (key === 'drug_name') {
          valA = String(a.drug_name || a.name || '').toLowerCase();
          valB = String(b.drug_name || b.name || '').toLowerCase();
      } else if (key === 'quantity' || key === 'alert_days') {
        valA = Number(valA) || 0;
        valB = Number(valB) || 0;
      } else if (key === 'expiry_date') {
        valA = new Date(valA).getTime() || 0;
        valB = new Date(valB).getTime() || 0;
      } else {
        valA = String(valA || '').toLowerCase();
        valB = String(valB || '').toLowerCase();
      }
      if (valA < valB) return direction === 'asc' ? -1 : 1;
      if (valA > valB) return direction === 'asc' ? 1 : -1;
      return 0;
    });
  }

  const sortIcon = (key) => {
    if (isDispatchMode) return "";
    if (!state.boxDetailSort || state.boxDetailSort.key !== key) return `<span style="opacity: 0.3; font-size: 0.8em; margin-left: 4px;">↕</span>`;
    return state.boxDetailSort.direction === 'asc' 
      ? `<span style="font-size: 0.8em; margin-left: 4px; color: var(--primary);">↑</span>` 
      : `<span style="font-size: 0.8em; margin-left: 4px; color: var(--primary);">↓</span>`;
  };

  return `
    <section class="panel">
      <div class="box-detail-header">
        <button class="secondary-button" data-box-back style="display: inline-flex; align-items: center; gap: 6px;">
          ← ย้อนกลับ
        </button>
        ${!isDispatchMode && canEdit ? `
          <button class="primary-button" data-box-dispatch="${box.id}" style="background: #2563eb; border-color: #2563eb;">
            ${icon("edit")} จัดยาเข้ากล่อง
          </button>
        ` : ""}
      </div>

      <div class="box-info-grid">
        <div class="box-info-item">
          <span class="box-info-label">ชื่อกล่องยาฉุกเฉิน</span>
          <span class="box-info-value">${escapeHtml(box.name || box.code || "-")}</span>
        </div>
        <div class="box-info-item">
          <span class="box-info-label">สถานที่จัดเก็บ</span>
          <span class="box-info-value">${escapeHtml(locationName)}</span>
        </div>
        <div class="box-info-item">
          <span class="box-info-label">จำนวนยา(รวม)</span>
          <span class="box-info-value">${Number(box.drugCount || box.drug_count || 0).toLocaleString("th-TH")}</span>
        </div>
        <div class="box-info-item">
          <span class="box-info-label">มูลค่ายารวม</span>
          <span class="box-info-value">฿${Number(box.value || 0).toLocaleString("th-TH", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
        </div>
        <div class="box-info-item">
          <span class="box-info-label">สถานะกล่อง</span>
          <span class="box-info-value">
            <span class="pill ${pillTone(box.status)}">${escapeHtml(box.status)}</span>
          </span>
        </div>
      </div>

      <div class="panel__head" style="margin-top: 20px; border-bottom: 1px solid var(--line); padding-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
          <h3 class="panel__title">${isDispatchMode ? "โหมดจัดยา / บันทึกรอบจัดยาใหม่" : "รายการยารอบจัดล่าสุด"}</h3>
          <p class="panel__subtitle">${isDispatchMode ? "เพิ่ม แก้ไข หรือนำเข้ายาในรอบจัดยานี้" : "รายการยาล่าสุดที่บันทึกตามประวัติรอบการจัดยา"}</p>
        </div>
        ${!isDispatchMode && activeItems.length > 0 ? `
          <button class="secondary-button" type="button" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 6px;">
            ${icon("report")} พิมพ์รายการล่าสุด
          </button>
        ` : ""}
      </div>

      <div class="table-wrap">
        <table class="dispatch-table">
          <thead>
            <tr>
              ${isDispatchMode ? `
                <th style="width: 60px;">ลำดับ</th>
                <th>ยา</th>
                <th style="width: 150px;">lot</th>
                <th style="width: 150px;">แจ้งเตือนก่อน (วัน)</th>
                <th style="width: 120px;">จำนวน</th>
                <th style="width: 100px;">หน่วย</th>
                <th style="width: 150px;">หมดอายุ</th>
                <th style="width: 80px;">จัดการ</th>
              ` : `
                <th style="cursor: pointer; user-select: none;" data-sort-box="drug_name">ยา ${sortIcon('drug_name')}</th>
                <th style="width: 150px; cursor: pointer; user-select: none;" data-sort-box="lot_number">lot ${sortIcon('lot_number')}</th>
                <th style="width: 150px; cursor: pointer; user-select: none;" data-sort-box="alert_days">แจ้งเตือนก่อน (วัน) ${sortIcon('alert_days')}</th>
                <th style="width: 120px; cursor: pointer; user-select: none;" data-sort-box="quantity">จำนวน ${sortIcon('quantity')}</th>
                <th style="width: 150px; cursor: pointer; user-select: none;" data-sort-box="expiry_date">หมดอายุ ${sortIcon('expiry_date')}</th>
              `}
            </tr>
          </thead>
          <tbody>
            ${activeItems.length === 0 ? `
              <tr>
                <td colspan="${isDispatchMode ? 8 : 5}" class="empty-state" style="text-align: center; padding: 40px; color: var(--muted);">
                  ${isDispatchMode 
                    ? "ยังไม่มีรายการยาในรอบจัดยานี้ กรุณากดปุ่ม <b>+ เพิ่มรายการยา</b> เพื่อเริ่มต้น" 
                    : "กล่องนี้ยังไม่เคยจัดยาเข้าระบบ กดปุ่ม <b>จัดยาเข้ากล่อง</b> เพื่อดำเนินการ"}
                </td>
              </tr>
            ` : activeItems.map((item, index) => {
              if (isDispatchMode) {
                return `
                  <tr>
                    <td>${index + 1}</td>
                    <td><strong>${escapeHtml(item.drug_name || item.name || "-")}</strong></td>
                    <td>
                      <input type="text" class="inline-edit-input" data-dispatch-field="lot_number" data-index="${index}" value="${escapeHtml(item.lot_number || "")}" placeholder="Lot" required />
                    </td>
                    <td>
                      <select class="inline-edit-input" data-dispatch-field="alert_days" data-index="${index}">
                        <option value="30" ${item.alert_days == 30 ? "selected" : ""}>30 วัน</option>
                        <option value="60" ${item.alert_days == 60 ? "selected" : ""}>60 วัน</option>
                        <option value="90" ${item.alert_days == 90 ? "selected" : ""}>90 วัน</option>
                      </select>
                    </td>
                    <td>
                      <input type="number" class="inline-edit-input" data-dispatch-field="quantity" data-index="${index}" min="0" value="${item.quantity}" placeholder="0" required />
                    </td>
                    <td>${escapeHtml(item.unit || "เม็ด")}</td>
                    <td>
                      <input type="date" class="inline-edit-input" data-dispatch-field="expiry_date" data-index="${index}" value="${escapeHtml(item.expiry_date || "")}" required />
                    </td>
                    <td>
                      <button type="button" class="action-icon action-icon--danger" data-remove-dispatch-item="${index}" title="ลบรายการนี้">
                        ${icon("minus")}
                      </button>
                    </td>
                  </tr>
                `;
              } else {
                return `
                  <tr>
                    <td><strong>${escapeHtml(item.drug_name || "-")}</strong></td>
                    <td><code>${escapeHtml(item.lot_number || "-")}</code></td>
                    <td>${item.alert_days}</td>
                    <td><strong>${Number(item.quantity || 0).toLocaleString("th-TH")}</strong> ${escapeHtml(item.unit || "-")}</td>
                    <td>${formatThaiDate(item.expiry_date)}</td>
                  </tr>
                `;
              }
            }).join("")}
          </tbody>
        </table>
      </div>

      ${isDispatchMode ? `
        <div style="margin-top: 16px;">
          <button type="button" class="primary-button" data-modal-open="addDispatchItem" style="background: #0f7b4f; border-color: #0f7b4f; display: inline-flex; align-items: center; gap: 6px;">
            + เพิ่มรายการยา
          </button>
        </div>
        <div class="dispatch-actions">
          <button class="secondary-button" type="button" data-cancel-dispatch>ยกเลิก</button>
          <button class="primary-button" type="button" data-save-dispatch="${box.id}" style="background: #2563eb; border-color: #2563eb; display: inline-flex; align-items: center; gap: 6px;">
            ${icon("check")} บันทึกรอบจัดยา
          </button>
        </div>
      ` : ""}

      ${!isDispatchMode && rounds && rounds.length > 0 ? `
        <div class="panel__head" style="margin-top: 40px; border-top: 1px solid var(--line); padding-top: 24px;">
          <h3 class="panel__title">ประวัติรอบการจัดยา (Repack History)</h3>
          <p class="panel__subtitle">ประวัติการจัดยาเข้ารอบกล่องที่ผ่านมาทั้งหมด</p>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>รอบที่</th>
                <th>วันที่บันทึก</th>
                <th>ผู้ดำเนินการ</th>
                <th>จำนวนรายการยา</th>
                <th>สถานะรอบ</th>
              </tr>
            </thead>
            <tbody>
              ${rounds.map(r => `
                <tr>
                  <td><strong>รอบที่ ${r.round_number}</strong></td>
                  <td>${formatThaiDateTime(r.created_at)}</td>
                  <td>${escapeHtml(r.created_by || "-")}</td>
                  <td>${r.items_count} รายการ</td>
                  <td><span class="pill pill--success">${escapeHtml(r.status === 'active' ? 'ล่าสุด/ใช้งาน' : r.status)}</span></td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>
      ` : ""}
    </section>

    ${state.modal?.type === "addDispatchItem" ? `
      <div class="modal-overlay" data-modal-backdrop>
        <div class="modal-card" style="max-width: 500px;">
          <div class="modal-card__head">
            <div class="modal-card__title">เพิ่มรายการยาในกล่อง</div>
            <button class="modal-card__close" type="button" data-modal-close aria-label="ปิด">×</button>
          </div>
          <form class="modal-form" data-form="addDispatchItem" id="addDispatchItemForm">
            <div class="form-field">
              <label>เลือกยา/เวชภัณฑ์</label>
              <select name="drug_id" required style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: var(--radius-sm); font-size: 0.95rem;">
                <option value="">- เลือกยา -</option>
                ${state.drugs.map(drug => `<option value="${drug.id}">${escapeHtml(drug.name)} (${escapeHtml(drug.unit)})</option>`).join("")}
              </select>
            </div>
            <div class="form-field">
              <label>หมายเลขล็อต (Lot Number)</label>
              <input name="lot_number" placeholder="เช่น A123 หรือ LOT456" required />
            </div>
            <div class="form-field">
              <label>จำนวน</label>
              <input type="number" name="quantity" min="1" placeholder="ระบุจำนวน" required />
            </div>
            <div class="form-field">
              <label>วันหมดอายุ (Expiry Date)</label>
              <input type="date" name="expiry_date" required />
            </div>
            <div class="form-field">
              <label>ตั้งการแจ้งเตือน (วันหมดอายุล่วงหน้า)</label>
              <select name="alert_days" style="width: 100%; padding: 10px; border: 1px solid var(--line); border-radius: var(--radius-sm); font-size: 0.95rem;">
                <option value="30" selected>30 วัน</option>
                <option value="60">60 วัน</option>
                <option value="90">90 วัน</option>
              </select>
            </div>
            <div class="modal-form__actions">
              <button class="secondary-button" type="button" data-modal-close>ยกเลิก</button>
              <button class="primary-button" type="submit">${icon("check")} เพิ่มรายการ</button>
            </div>
          </form>
        </div>
      </div>
    ` : ""}
  `;
}

function locationsView() {
  const canEdit = ["super_admin"].includes(state.session.role);
  const pg = state.pagination.locations;
  const fullList = (state.locations || []).filter((item) =>
    (item.name || "").toLowerCase().includes((state.filters.locations || "").toLowerCase()),
  );
  const totalPages = Math.max(1, Math.ceil(fullList.length / pg.pageSize));
  const safePageState = Math.min(pg.page, totalPages);
  const start = (safePageState - 1) * pg.pageSize;
  const pagedList = fullList.slice(start, start + pg.pageSize);
  const m = state.modal;
  const isLocationModal = m && m.type === "location";
  const editor = isLocationModal ? m.data : null;

  return `
    <section class="panel">
      <div class="page-banner">
        <div class="page-banner__left">
          <div class="page-banner__icon">${icon("pin")}</div>
          <div>
            <div class="page-banner__title">จัดการสถานที่เก็บกระเป๋ายา</div>
            <div class="page-banner__desc">เช่น LR, OR, ICU เพื่อให้ทีมหายาถูกและทำงานได้ง่าย</div>
          </div>
        </div>
        ${canEdit ? `<button class="primary-button" data-modal-open="location">+ เพิ่มสถานที่</button>` : ""}
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ชื่อ</th>
              <th>รายละเอียด</th>
              <th>สถานะ</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            ${pagedList.length === 0 ? `<tr><td colspan="4" class="empty-state">ไม่มีข้อมูลสถานที่</td></tr>` : pagedList.map((item) => `
              <tr>
                <td><strong>${escapeHtml(item.name)}</strong></td>
                <td>${escapeHtml(item.detail || "-")}</td>
                <td><span class="pill ${pillTone(item.status)}">${item.status}</span></td>
                <td class="row-actions">
                  ${canEdit ? `
                    <button class="action-icon" type="button" data-edit-location="${item.id}" title="แก้ไขสถานที่">${icon("edit")}</button>
                    <button class="action-icon action-icon--danger" type="button" data-delete-location="${item.id}" title="ลบสถานที่">${icon("minus")}</button>
                  ` : "-"}
                </td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
      ${paginationBar(fullList.length, safePageState, pg.pageSize, "locations")}
    </section>

    ${isLocationModal ? `
      <div class="modal-overlay" data-modal-backdrop>
        <div class="modal-card">
          <div class="modal-card__head">
            <div class="modal-card__title">${editor ? "แก้ไขสถานที่" : "เพิ่มสถานที่ใหม่"}</div>
            <button class="modal-card__close" data-modal-close>ร—</button>
          </div>
          <form class="modal-form" data-form="location">
            <input type="hidden" name="id" value="${editor ? escapeHtml(editor.id) : ""}" />
            <div class="form-field">
              <label>ชื่อสถานที่</label>
              <input name="name" placeholder="เช่น ห้องฉุกเฉิน, ICU, ห้องคลอด" value="${editor ? escapeHtml(editor.name) : ""}" required />
            </div>
            <div class="form-field">
              <label>รายละเอียด</label>
              <input name="detail" placeholder="เช่น ชั้น 2, ตึก A" value="${editor ? escapeHtml(editor.detail || "") : ""}" />
            </div>
            <div class="form-field">
              <label>สถานะ</label>
              <select name="status">
                <option value="ใช้งาน" ${editor?.status === "ใช้งาน" || !editor ? "selected" : ""}>ใช้งาน</option>
                <option value="ยกเลิก" ${editor?.status === "ยกเลิก" ? "selected" : ""}>ยกเลิก</option>
              </select>
            </div>
            <div class="modal-form__actions">
              <button class="secondary-button" type="button" data-modal-close>ยกเลิก</button>
              <button class="primary-button" type="submit">${icon("pin")} ${editor ? "บันทึก" : "เพิ่มสถานที่"}</button>
            </div>
          </form>
        </div>
      </div>
    ` : ""}
  `;
}

function drugsView() {
  const list = state.drugs.filter((item) =>
    (item.name || "").toLowerCase().includes((state.filters.drugs || "").toLowerCase()),
  );
  const canEdit = ["super_admin", "pharmacist", "lab", "supply"].includes(state.session.role);
  const editor = state.drugEditor;
  return `
    <section class="panel">
      <div class="panel__head">
        <div>
          <h3 class="panel__title">คลังข้อมูลยาและเวชภัณฑ์</h3>
          <p class="panel__subtitle">จัดการ master data เพื่อให้ทีมบริการได้ข้อมูลที่ถูกต้องและพร้อมใช้งาน</p>
        </div>
        <label class="search-box">
          <span class="icon">${icon("info")}</span>
          <input type="search" placeholder="ค้นหายา..." data-filter="drugs" value="${escapeHtml(state.filters.drugs)}" />
        </label>
      </div>
      ${canEdit ? `
        <form class="inline-form" data-form="drug">
          <input type="hidden" name="id" value="${editor ? escapeHtml(editor.id) : ""}" />
          <input name="name" placeholder="ชื่อยา" value="${editor ? escapeHtml(editor.name) : ""}" required />
          <input name="unit" placeholder="หน่วย" value="${editor ? escapeHtml(editor.unit) : ""}" required />
          <input name="price" placeholder="฿0.00" value="${editor ? escapeHtml(editor.price) : ""}" required />
          <select name="category" required>
            ${[
              { value: "drug", label: "ยา", roles: ["super_admin", "pharmacist"] },
              { value: "medical_supply", label: "เวชภัณฑ์", roles: ["super_admin", "pharmacist", "supply"] },
              { value: "reagent", label: "น้ำยา", roles: ["super_admin", "lab"] },
              { value: "lab_test_kit", label: "LAB / Test Kit", roles: ["super_admin", "lab"] }
            ]
              .filter(opt => opt.roles?.includes(state.session.role))
              .map(opt => `<option value="${opt.value}" ${editor && editor.category === opt.value ? "selected" : ""}>${opt.label}</option>`)
              .join("")}
          </select>
          <button class="primary-button" type="submit">${icon("pill")} ${editor ? "บันทึกการแก้ไข" : "เพิ่มยา"}</button>
          ${editor ? `<button class="secondary-button" type="button" data-drug-cancel>ยกเลิก</button>` : ""}
        </form>
      ` : `<div class="muted-note">คุณมีสิทธิ์อ่านข้อมูลเท่านั้น</div>`}
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ชื่อยา</th>
              <th>หมวดหมู่</th>
              <th>หน่วย</th>
              <th>มูลค่าต่อหน่วย</th>
              <th>แก้ไข</th>
            </tr>
          </thead>
          <tbody>
            ${list.map((item) => `
              <tr>
                <td>${item.name}</td>
                <td>${
                  item.category === "drug" ? "ยา" :
                  item.category === "medical_supply" ? "เวชภัณฑ์" :
                  item.category === "reagent" ? "น้ำยา" :
                  item.category === "lab_test_kit" ? "LAB / Test Kit" :
                  item.category || "ยา"
                }</td>
                <td>${item.unit}</td>
                <td class="money">${item.price}</td>
                <td>${canEdit ? `
                  <button class="action-icon" type="button" data-edit-drug="${item.id}" title="แก้ไขยา" aria-label="แก้ไขยา ${escapeHtml(item.name)}">${icon("edit")}</button>
                  <button class="action-icon action-icon--danger" type="button" data-delete-drug="${item.id}" title="ลบยา" aria-label="ลบยา ${escapeHtml(item.name)}">${icon("minus")}</button>
                ` : "-"}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
    </section>
  `;
}

function formatThaiDate(isoDate) {
  const date = new Date(isoDate);
  return Number.isNaN(date.getTime()) ? isoDate : date.toLocaleDateString("th-TH");
}

function formatThaiDateTime(...values) {
  for (const value of values) {
    if (!value) continue;
    const date = new Date(value);
    if (!Number.isNaN(date.getTime())) {
      return date.toLocaleString("th-TH");
    }
  }
  return "-";
}

function alertsView(category) {
  const categoryLabels = {
    drug: "ยา",
    medical_supply: "เวชภัณฑ์",
    reagent: "น้ำยา",
    lab_test_kit: "LAB / Test Kit",
  };

  const isUnified = !category;
  const filtered = isUnified
    ? (state.alerts || [])
    : (state.alerts || []).filter((item) => item.category === category);

  const canResolve = (alert) => {
    const role = state.session.role;
    const locId = state.session.locationId;
    if (role === "super_admin") return true;
    if (role === "viewer") return false;

    if (role === "dept_admin") {
      return String(alert.locationId) === String(locId);
    }
    if (role === "pharmacist") {
      if (alert.category === "drug") return true;
      if (alert.category === "medical_supply") return String(alert.locationId) === String(locId);
    }
    if (role === "lab") {
      return alert.category === "reagent" || alert.category === "lab_test_kit";
    }
    if (role === "supply") {
      return alert.category === "medical_supply";
    }
    return false;
  };

  return `
    <section class="panel">
      <div class="panel__head">
        <div>
          <h3 class="panel__title">รายการคงคลังหมดอายุ${isUnified ? "" : `: ${categoryLabels[category]}`}</h3>
          <p class="panel__subtitle">รายการหมดอายุหรือใกล้หมดอายุในระบบ</p>
        </div>
        ${state.session.role === "super_admin" ? `<button class="primary-button" id="markAllRead">${icon("bell")} อ่านทั้งหมด</button>` : ""}
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>กล่องยาฉุกเฉิน</th>
              <th>รายการ</th>
              ${isUnified ? `<th>หมวดหมู่</th>` : ""}
              <th>จำนวน</th>
              <th>วันหมดอายุ</th>
              <th>ระดับ</th>
              <th>สถานะ</th>
              <th>การจัดการ</th>
            </tr>
          </thead>
          <tbody>
            ${filtered.length ? filtered.map((item) => `
              <tr>
                <td>${escapeHtml(item.boxName)}</td>
                <td><strong>${escapeHtml(item.drugName)}</strong></td>
                ${isUnified ? `<td>${categoryLabels[item.category] || item.category || "-"}</td>` : ""}
                <td>${item.quantity} ${escapeHtml(item.unit)}</td>
                <td>${formatThaiDate(item.expiryDate)}</td>
                <td>
                  <span class="pill ${item.severity === "danger" ? "pill--danger" : "pill--warning"}">
                    ${item.severity === "danger" ? "หมดอายุแล้ว" : "ใกล้หมดอายุ"}
                  </span>
                </td>
                <td>
                  <span class="pill ${pillTone(item.status)}">${escapeHtml(item.status)}</span>
                </td>
                <td>
                  ${canResolve(item) 
                    ? `<button class="secondary-button" style="padding: 6px 12px; font-size: 13px;" data-resolve-alert="${item.id}">${icon("check")} อนุมัติ/ปิดรายการ</button>`
                    : `-`
                  }
                </td>
              </tr>
            `).join("") : `<tr><td colspan="${isUnified ? 8 : 7}" class="empty-state">ไม่มีรายการคงคลังหมดอายุ</td></tr>`}
          </tbody>
        </table>
      </div>
    </section>
  `;
}

function reportsView() {
  if (!state.reports || !state.reports.metrics) return loadingView();
  
  const m = state.reports.metrics;
  
  return `
    <section class="section-stack">
      <div class="panel__head" style="margin-bottom: 20px;">
        <div>
          <h2 style="font-size: 24px; font-weight: bold; color: #1e293b; margin: 0;">รายงานขั้นสูง (Advanced Reports)</h2>
          <p style="color: #64748b; margin: 4px 0 0 0;">สรุปข้อมูลสถานะกล่องยา มูลค่า และประวัติการจัดยาแบบครบวงจร</p>
        </div>
        <div style="display: flex; gap: 10px;">
          <a href="${appUrl("/tools/report.pdf")}" target="_blank" class="secondary-button" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            ${icon("report")} ดาวน์โหลด PDF สรุปภาพรวม
          </a>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-left: 4px solid #3b82f6;">
          <div style="color: #64748b; font-size: 14px; font-weight: 500;">จำนวนกล่องยาฉุกเฉินทั้งหมด</div>
          <div style="font-size: 28px; font-weight: 700; color: #0f172a; margin-top: 8px;">${m.inventory.total_boxes} <span style="font-size: 16px; font-weight: normal; color: #64748b;">กล่อง</span></div>
          <div style="font-size: 13px; color: #10b981; margin-top: 4px;">พร้อมใช้ ${m.inventory.ready_boxes} กล่อง</div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-left: 4px solid #10b981;">
          <div style="color: #64748b; font-size: 14px; font-weight: 500;">มูลค่าคงคลังรวม (บาท)</div>
          <div style="font-size: 28px; font-weight: 700; color: #0f172a; margin-top: 8px;">${Number(m.inventory.total_value).toLocaleString(undefined, {minimumFractionDigits: 2})}</div>
          <div style="font-size: 13px; color: #64748b; margin-top: 4px;">อัปเดตล่าสุด: ${new Date().toLocaleTimeString("th-TH")}</div>
        </div>
        <div style="background: white; border-radius: 12px; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-left: 4px solid #ef4444;">
          <div style="color: #64748b; font-size: 14px; font-weight: 500;">การแจ้งเตือน (รอดำเนินการ)</div>
          <div style="font-size: 28px; font-weight: 700; color: #0f172a; margin-top: 8px;">${m.alerts.active} <span style="font-size: 16px; font-weight: normal; color: #64748b;">รายการ</span></div>
          <div style="font-size: 13px; color: #64748b; margin-top: 4px;">จากทั้งหมด ${m.alerts.total} รายการ</div>
        </div>
      </div>

      <div class="subgrid">
        <article class="panel">
          <div class="panel__head">
            <div>
              <h3 class="panel__title">รายงานสถานะกล่องยาและมูลค่าคงคลัง (Excel)</h3>
              <p class="panel__subtitle">ดาวน์โหลดรายละเอียดกล่องยาทั้งหมดในรูปแบบตาราง Excel เพื่อนำไปวิเคราะห์ต่อ</p>
            </div>
            <a href="${appUrl("/tools/export/boxes.xlsx")}" target="_blank" class="primary-button" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
              ${icon("money")} ดาวน์โหลด Excel
            </a>
          </div>
        </article>

        <article class="panel">
          <div class="panel__head">
            <div>
              <h3 class="panel__title">รายงานประวัติการจัดยาแบบละเอียด (Excel)</h3>
              <p class="panel__subtitle">ตรวจสอบย้อนหลังรอบการจัดยา ใครเป็นคนจัด และจัดยาอะไรลงไปบ้าง</p>
            </div>
            <a href="${appUrl("/tools/export/repack_history.xlsx")}" target="_blank" class="primary-button" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
              ${icon("pill")} ดาวน์โหลด Excel
            </a>
          </div>
        </article>
      </div>
    </section>
  `;
}

function usersView() {
  if (!["super_admin"].includes(state.session.role)) {
    return `<section class="panel"><div class="empty-state">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></section>`;
  }
  const editor = state.userEditor;
  const users = Array.isArray(state.users) ? state.users : [];
  const locations = Array.isArray(state.locations) ? state.locations : [];
  const auditLogs = Array.isArray(state.auditLogs) ? state.auditLogs : [];
  const editorTitle = editor
    ? ((editor.approvalStatus || editor.status) === "pending" ? `อนุมัติผู้ใช้ ${userDisplayName(editor)}` : `แก้ไขผู้ใช้ ${userDisplayName(editor)}`)
    : "สร้างผู้ใช้ใหม่";
  const submitLabel = editor
    ? ((editor.approvalStatus || editor.status) === "pending" ? "อนุมัติผู้ใช้งาน" : "บันทึกการแก้ไข")
    : "เพิ่มผู้ใช้";
  const totalUsers = users.length;
  const activeUsers = users.filter((user) => user.active).length;
  const adminUsers = users.filter((user) => user.role === "super_admin").length;
  return `
    <section class="section-stack">
      <article class="panel">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">จัดการผู้ใช้งาน</h3>
            <p class="panel__subtitle">สร้างผู้ใช้งานใหม่และกำหนดสิทธิ์การเข้าถึงแบบ role-based</p>
          </div>
          <button class="primary-button" type="button" data-user-new>${icon("users")} ผู้ใช้ใหม่</button>
        </div>
        <div class="users-stats">
          <article class="mini-stat">
            <span>ผู้ใช้ทั้งหมด</span>
            <strong>${totalUsers}</strong>
            <small>บัญชีในระบบทั้งหมด</small>
          </article>
          <article class="mini-stat">
            <span>ใช้งานอยู่</span>
            <strong>${activeUsers}</strong>
            <small>บัญชีที่ยัง active</small>
          </article>
          <article class="mini-stat">
            <span>Super Admin</span>
            <strong>${adminUsers}</strong>
            <small>ผู้มีสิทธิ์สูงสุด</small>
          </article>
        </div>
        <div class="banner banner--compact">
          <div class="banner__title">${editorTitle}</div>
          <p class="banner__text">ใช้ฟอร์มนี้เพื่อสร้าง แก้ไข ปิดการใช้งาน หรือรีเซ็ตรหัสผ่านของผู้ใช้ในระบบ</p>
        </div>
        <form class="inline-form" data-form="user">
          <input type="hidden" name="id" value="${editor ? escapeHtml(editor.id) : ""}" />
          <input name="username" placeholder="username" value="${editor ? escapeHtml(editor.username) : ""}" required />
          <input name="display_name" placeholder="ชื่อแสดงผล" value="${editor ? escapeHtml(userDisplayName(editor)) : ""}" required />
          <select name="role">
            ${Object.keys(roleLabel).map((role) => `<option value="${role}" ${editor && editor.role === role ? "selected" : ""}>${roleLabel[role]}</option>`).join("")}
          </select>
          <select name="locationId">
            <option value="">ทุกหน่วยงาน / Super Admin</option>
            ${locations.map((loc) => `<option value="${loc.id}" ${editor && String(editor.locationId) === String(loc.id) ? "selected" : ""}>${escapeHtml(loc.name)}</option>`).join("")}
          </select>
          <input name="password" ${editor ? "" : "required"} placeholder="${editor ? "เว้นว่างหากไม่เปลี่ยนรหัสผ่าน" : "รหัสผ่านเริ่มต้น"}" />
          <button class="primary-button" type="submit">${icon("users")} ${submitLabel}</button>
          ${editor ? `<button class="secondary-button" type="button" data-user-cancel>ยกเลิก</button>` : ""}
        </form>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ชื่อผู้ใช้</th>
              <th>ชื่อแสดงผล</th>
              <th>สิทธิ์</th>
              <th>หน่วยงาน</th>
              <th>สถานะ</th>
              <th>จัดการ</th>
            </tr>
          </thead>
          <tbody>
            ${users.map((user) => `
              <tr>
                <td>${user.username}</td>
                <td>${escapeHtml(userDisplayName(user) || "-")}</td>
                <td>${roleLabel[user.role] || user.role}</td>
                <td>${locations.find((l) => String(l.id) === String(user.locationId))?.name || "ทุกหน่วยงาน / Super Admin"}</td>
                <td>
                  ${(user.approvalStatus || user.status) === "pending" 
                    ? `<span class="pill" style="background:#f59e0b;color:#fff;">รออนุมัติ</span>`
                    : `<span class="pill ${user.active ? "pill--success" : "pill--danger"}">${user.active ? "ใช้งาน" : "ปิดใช้งาน"}</span>`
                  }
                </td>
                <td class="row-actions">
                  ${(user.approvalStatus || user.status) === "pending" 
                    ? `<button class="action-icon" type="button" data-user-approve="${user.id}" title="อนุมัติ">${icon("check")}</button>`
                    : `
                      <button class="action-icon" type="button" data-user-edit="${user.id}">${icon("edit")}</button>
                      <button class="action-icon" type="button" data-user-reset="${user.id}">${icon("lock")}</button>
                      <button class="action-icon" type="button" data-user-toggle="${user.id}">${user.active ? icon("logout") : icon("users")}</button>
                    `
                  }
                  <button class="action-icon" type="button" data-user-delete="${user.id}">${icon("minus")}</button>
                </td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      </div>
      </article>
      <article class="panel">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">Audit log ล่าสุด</h3>
            <p class="panel__subtitle">บันทึกการทำงานของผู้ใช้ย้อนหลังจากฐานข้อมูลจริง</p>
          </div>
          <span class="tag">${icon("report")} ${auditLogs.length} รายการ</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>เวลา</th>
                <th>ผู้ทำ</th>
                <th>การกระทำ</th>
                <th>รายละเอียด</th>
              </tr>
            </thead>
            <tbody>
              ${auditLogs.map((log) => `
                <tr>
                  <td>${formatThaiDateTime(log.createdAt, log.created_at)}</td>
                  <td>${log.actor ? `${log.actor.displayName} (${log.actor.role})` : "ระบบ"}</td>
                  <td><span class="pill pill--info">${log.action}</span></td>
                  <td>${escapeHtml(JSON.stringify(log.details || {})).slice(0, 140)}</td>
                </tr>
              `).join("")}
            </tbody>
          </table>
        </div>
      </article>
    </section>
  `;
}

function guideView() {
  return `
    <section class="section-stack">
      <article class="panel">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">คู่มือระบบตามกล่องยาฉุกเฉิน</h3>
            <p class="panel__subtitle">อธิบายขั้นตอนการใช้งานสำหรับผู้ดูแลระบบและผู้ปฏิบัติงาน</p>
          </div>
          <span class="tag">${icon("book")} วิธีการใช้งาน</span>
        </div>
        <div class="timeline" style="margin-top: 16px;">
          
          <details style="margin-bottom: 12px; border: 1px solid var(--border); border-radius: 8px; background: white; overflow: hidden;">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; background: #f8fafc;">
              <span style="width: 12px; height: 12px; border-radius: 50%; background: var(--primary);"></span> Dashboard (แผงควบคุม)
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--border); font-size: 14px; line-height: 1.6; color: #475569;">
              <b>หน้าที่:</b> ใช้สำหรับดูภาพรวมของระบบทั้งหมดแบบรวดเร็ว<br>
              <b>วิธีใช้งาน:</b><br>
              1. เมื่อเปิดเข้ามาจะเห็นตัวเลขสรุป "จำนวนยาที่หมดอายุ" (สีแดง) และ "ยาที่ใกล้หมดอายุ" (สีเหลือง)<br>
              2. ถัดมาจะเห็นการแยกประเภทของรายการ เช่น ยา, เวชภัณฑ์, สารเคมี (LAB)<br>
              3. ด้านล่างจะเป็นกราฟหรือตารางสรุปจำนวนกล่องยาของแต่ละหน่วยงาน/สถานที่<br>
              *สามารถคลิกที่ตัวเลขแจ้งเตือนเพื่อดูรายละเอียดของยาที่ใกล้หมดอายุได้ทันที
            </div>
          </details>

          <details style="margin-bottom: 12px; border: 1px solid var(--border); border-radius: 8px; background: white; overflow: hidden;">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; background: #f8fafc;">
              <span style="width: 12px; height: 12px; border-radius: 50%; background: #eab308;"></span> กล่องยาฉุกเฉิน (Boxes)
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--border); font-size: 14px; line-height: 1.6; color: #475569;">
              <b>หน้าที่:</b> จัดการกล่องยาของแต่ละแผนก และอัปเดตจำนวนยาที่มีในกล่อง<br>
              <b>วิธีใช้งาน:</b><br>
              1. <b>การเพิ่มกล่องใหม่ (เฉพาะเภสัชกร/แอดมิน):</b> กดปุ่ม "+ เพิ่มกล่องยาฉุกเฉิน" มุมขวาบน ตั้งชื่อกล่องและเลือกสถานที่เก็บ<br>
              2. <b>การจัดยาเข้ากล่อง (ทุกคนที่มีสิทธิ์):</b> คลิกเข้าไปที่รายชื่อกล่องยา จะพบปุ่ม "จัดยาเข้ากล่อง" (ปุ่มสีน้ำเงิน)<br>
              3. เมื่อกดเข้ามาแล้ว สามารถกดเลือกรายการยาจากฝั่งซ้าย เพื่อนำมาใส่กล่องทางฝั่งขวา<br>
              4. กรอก <b>จำนวน</b>, <b>หมายเลขล็อต (Lot No.)</b>, และ <b>วันหมดอายุ (Expiry Date)</b> ให้ตรงกับความเป็นจริง<br>
              5. กดบันทึกข้อมูล ยาจะเข้าไปอยู่ในกล่องและระบบจะเริ่มคำนวณวันหมดอายุให้อัตโนมัติ
            </div>
          </details>

          <details style="margin-bottom: 12px; border: 1px solid var(--border); border-radius: 8px; background: white; overflow: hidden;">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; background: #f8fafc;">
              <span style="width: 12px; height: 12px; border-radius: 50%; background: #3b82f6;"></span> ยา (รายการหลัก - Drugs)
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--border); font-size: 14px; line-height: 1.6; color: #475569;">
              <b>หน้าที่:</b> จัดการฐานข้อมูลรายการยาทั้งหมดของโรงพยาบาล<br>
              <b>วิธีใช้งาน (เฉพาะเภสัชกร หรือ Super Admin):</b><br>
              1. กดปุ่ม "+ เพิ่มยา/เวชภัณฑ์" เพื่อสร้างรายการใหม่<br>
              2. กรอกข้อมูลชื่อยา/ชื่อการค้า, รูปแบบยา (เช่น เม็ด, น้ำ), หมวดหมู่, และตั้งค่า <b>แจ้งเตือนล่วงหน้า (กี่วัน)</b><br>
              3. หากยาชนิดนี้ต้องการให้แจ้งเตือนล่วงหน้านานกว่าปกติ (เช่น 90 วัน) สามารถตั้งค่าเฉพาะตัวยาได้ที่นี่<br>
              4. รายการยาที่สร้างที่นี่ จะไปโผล่ให้พยาบาลเลือกใช้งานในเมนู "กล่องยาฉุกเฉิน"
            </div>
          </details>

          <details style="margin-bottom: 12px; border: 1px solid var(--border); border-radius: 8px; background: white; overflow: hidden;">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; background: #f8fafc;">
              <span style="width: 12px; height: 12px; border-radius: 50%; background: #ec4899;"></span> สถานที่ (Locations)
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--border); font-size: 14px; line-height: 1.6; color: #475569;">
              <b>หน้าที่:</b> จัดการจุดที่ตั้งของกล่องยาและสถานที่ทำงานของผู้ใช้<br>
              <b>วิธีใช้งาน (เฉพาะ Super Admin):</b><br>
              1. กดปุ่ม "+ เพิ่มสถานที่" <br>
              2. ใส่รหัสสถานที่ (เช่น ER, WARD1) และชื่อแผนก/หน่วยงาน<br>
              3. ข้อมูลนี้จะถูกนำไปผูกกับผู้ใช้งาน ว่าผู้ใช้นี้อยู่แผนกไหน และผูกกับกล่องยา ว่ากล่องนี้ตั้งอยู่ที่ไหน
            </div>
          </details>

          <details style="margin-bottom: 12px; border: 1px solid var(--border); border-radius: 8px; background: white; overflow: hidden;">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; background: #f8fafc;">
              <span style="width: 12px; height: 12px; border-radius: 50%; background: #8b5cf6;"></span> รายงาน (Reports)
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--border); font-size: 14px; line-height: 1.6; color: #475569;">
              <b>หน้าที่:</b> ดึงข้อมูลสรุปเพื่อนำไปทำรายงานประจำเดือน<br>
              <b>วิธีใช้งาน:</b><br>
              1. สามารถเลือกดูข้อมูลยาย้อนหลัง ข้อมูลการแจ้งเตือนต่างๆ ได้<br>
              2. จะมีตารางแสดงรายการยาที่หมดอายุไปแล้ว หรือยาที่ถูกนำออกจากกล่องไปแล้ว
            </div>
          </details>

          <details style="margin-bottom: 12px; border: 1px solid var(--border); border-radius: 8px; background: white; overflow: hidden;">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; background: #f8fafc;">
              <span style="width: 12px; height: 12px; border-radius: 50%; background: #ef4444;"></span> ผู้ใช้งาน (Users)
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--border); font-size: 14px; line-height: 1.6; color: #475569;">
              <b>หน้าที่:</b> จัดการบัญชีของเจ้าหน้าที่ในระบบ<br>
              <b>วิธีใช้งาน (เฉพาะ Super Admin):</b><br>
              1. หากมีผู้ใช้สมัครใหม่ จะมีสถานะเป็น <b>"ปิดใช้งาน"</b><br>
              2. แอดมินต้องกดไอคอนรูป <b>เครื่องหมายถูก (✔️)</b> เพื่อทำการอนุมัติสิทธิ์<br>
              3. ในหน้าต่างอนุมัติ แอดมินต้องเลือก <b>แผนก/สถานที่</b> และเลือก <b>สิทธิ์การใช้งาน (Role)</b> เช่น เป็นพยาบาล (Viewer) หรือ แอดมินหน่วยงาน<br>
              4. หากต้องการแก้ไขสิทธิ์ หรือรีเซ็ตรหัสผ่าน ให้คลิกที่ชื่อผู้ใช้หรือไอคอนแก้ไข
            </div>
          </details>

          <details style="margin-bottom: 12px; border: 1px solid var(--border); border-radius: 8px; background: white; overflow: hidden;">
            <summary style="padding: 12px 16px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; background: #f8fafc;">
              <span style="width: 12px; height: 12px; border-radius: 50%; background: #22c55e;"></span> ตั้งค่าระบบ (Settings)
            </summary>
            <div style="padding: 16px; border-top: 1px solid var(--border); font-size: 14px; line-height: 1.6; color: #475569;">
              <b>หน้าที่:</b> เชื่อมต่อกับระบบแจ้งเตือนภายนอก เช่น Telegram<br>
              <b>วิธีใช้งาน (เฉพาะ Super Admin):</b><br>
              1. นำ Token จาก Telegram Bot (BotFather) มากรอกใส่ช่อง Telegram Token<br>
              2. ใส่ Chat ID ของกลุ่มที่ต้องการให้ Bot ส่งข้อความเตือนยาใกล้หมดอายุไปหา<br>
              3. กดบันทึกข้อมูล และกดปุ่มทดสอบส่งข้อความ เพื่อดูว่าการเชื่อมต่อสำเร็จหรือไม่
            </div>
          </details>

        </div>
      </article>
    </section>
  `;
}

function aboutView() {
  return `
    <section class="section-stack">
      <article class="panel">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">ข้อมูลเวอร์ชันระบบ</h3>
            <p class="panel__subtitle">Drug Alert System</p>
          </div>
          <span class="tag" style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;">${icon("check")} พร้อมใช้งาน</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
          <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
            <div style="font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase;">วันที่อัปเดตล่าสุด</div>
            <div style="font-size: 15px; color: var(--text); font-weight: 500; margin-top: 4px;">10 กรกฎาคม 2026</div>
          </div>
          <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--border);">
            <div style="font-size: 12px; color: var(--muted); font-weight: 600; text-transform: uppercase;">รูปแบบการใช้งาน</div>
            <div style="font-size: 15px; color: var(--text); font-weight: 500; margin-top: 4px;">Web Application</div>
          </div>
          <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--border); grid-column: 1 / -1;">
            <div style="font-size: 13px; color: var(--text); font-weight: 600; margin-bottom: 8px;">จุดประสงค์ของระบบ</div>
            <ul style="font-size: 14px; color: #475569; padding-left: 20px; line-height: 1.6; margin: 0;">
              <li>จัดการข้อมูลยา คลังย่อย สถานที่ และรายการคงคลังให้อยู่ในฐานข้อมูลกลางเดียวกัน</li>
              <li>สนับสนุนการติดตามยาใกล้หมดอายุและยาคงเหลือต่ำด้วยการแจ้งเตือน 4 รอบ</li>
              <li>รองรับการใช้งานผ่านหน้าเว็บบนคอมพิวเตอร์และอุปกรณ์เคลื่อนที่</li>
              <li>มีระบบผู้ใช้ การอนุมัติ และสิทธิ์การเข้าถึงที่เหมาะสมสำหรับการใช้งานในองค์กร</li>
            </ul>
          </div>
          <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid var(--border); grid-column: 1 / -1;">
            <div style="font-size: 13px; color: var(--text); font-weight: 600; margin-bottom: 8px;">เทคโนโลยีหลัก</div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
              ${["Laravel 11", "Vue 3", "Tailwind CSS", "Vuetify", "MariaDB / MySQL", "Sanctum", "Apache 2.4", "Telegram Bot API"].map(tech => `<span style="background: white; border: 1px solid #cbd5e1; padding: 4px 10px; border-radius: 16px; font-size: 12px; font-weight: 500; color: #334155;">${tech}</span>`).join('')}
            </div>
          </div>
        </div>
      </article>

      <article class="panel" style="margin-top: 20px;">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">ผู้พัฒนาและที่ปรึกษา</h3>
            <p class="panel__subtitle">รายนามผู้รับผิดชอบการออกแบบและพัฒนาระบบ</p>
          </div>
          <span class="tag" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">${icon("user")} เครดิต</span>
        </div>
        
        <div style="display: flex; flex-direction: column; gap: 12px; margin-top: 16px;">
          <!-- Developer -->
          <div style="padding: 16px; display: flex; gap: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid var(--border);">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: grid; place-items: center; font-size: 20px; flex-shrink: 0;">
              ${icon("user")}
            </div>
            <div>
              <div style="font-weight: 700; font-size: 1.05rem; color: var(--text);">ศุภชัย นะที</div>
              <div style="font-size: 0.88rem; color: var(--primary); font-weight: 600; margin-top: 2px;">ผู้พัฒนา (นักวิชาการคอมพิวเตอร์)</div>
              <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px; line-height: 1.5;">รับผิดชอบการออกแบบ พัฒนา และปรับปรุงระบบให้มีความเหมาะสมต่อการใช้งานจริงในภาพรวมของโครงการ</div>
            </div>
          </div>
          
          <!-- Advisor -->
          <div style="padding: 16px; display: flex; gap: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid var(--border);">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #fef08a; color: #a16207; display: grid; place-items: center; font-size: 20px; flex-shrink: 0;">
              ${icon("info")}
            </div>
            <div>
              <div style="font-weight: 700; font-size: 1.05rem; color: var(--text);">เภสัช สันติ ซุยใจทัศน์</div>
              <div style="font-size: 0.88rem; color: #a16207; font-weight: 600; margin-top: 2px;">ที่ปรึกษาโครงการ</div>
              <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px; line-height: 1.5;">ให้คำแนะนำด้านเวิร์กโฟลว์ยา การจัดเก็บข้อมูล การแจ้งเตือน และการนำระบบไปใช้ในงานจริงอย่างเหมาะสม</div>
            </div>
          </div>
          
          <!-- Supporter -->
          <div style="padding: 16px; display: flex; gap: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid var(--border);">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: #fce7f3; color: #be185d; display: grid; place-items: center; font-size: 20px; flex-shrink: 0;">
              ${icon("check")}
            </div>
            <div>
              <div style="font-weight: 700; font-size: 1.05rem; color: var(--text);">นายแพทย์จักร์ชัย ติตะบุตร</div>
              <div style="font-size: 0.88rem; color: #be185d; font-weight: 600; margin-top: 2px;">ขอขอบคุณ (ผู้อำนวยการโรงพยาบาล)</div>
              <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px; line-height: 1.5;">ผู้ให้การสนับสนุนและผลักดันให้ระบบนี้เกิดขึ้นจนสามารถนำมาใช้งานได้จริง</div>
            </div>
          </div>
        </div>

        <div style="margin-top: 20px; padding: 16px; border-radius: 12px; background: #f1f5f9; border: 1px dashed #cbd5e1;">
          <div style="font-weight: 700; font-size: 14px; color: #334155; margin-bottom: 8px;">คำขอบคุณ</div>
          <p style="font-size: 13px; line-height: 1.6; color: #475569; margin: 0; text-indent: 20px;">
            ขอขอบคุณทุกท่านที่มีส่วนร่วมในการทำให้ระบบนี้เกิดขึ้น ทั้งการให้คำปรึกษา การสนับสนุนข้อมูลจริง และการผลักดันให้การดำเนินงานของหน่วยงานมีความสะดวกและเป็นระบบมากยิ่งขึ้น โดยเฉพาะ เภสัช สันติ ซุยใจทัศน์ ในฐานะที่ปรึกษา และ นายแพทย์จักร์ชัย ติตะบุตร ผู้อำนวยการโรงพยาบาลผู้ให้การสนับสนุนอย่างดียิ่ง รวมถึงขอขอบคุณผู้มีส่วนช่วยในการพัฒนาระบบทุกท่าน ตลอดจนผู้ช่วยด้านการวิเคราะห์ ออกแบบ และเรียบเรียงเนื้อหาที่ทำให้หน้าเว็บและคู่มือฉบับนี้สมบูรณ์ยิ่งขึ้น
          </p>
        </div>
      </article>

      <article class="panel" style="margin-top: 20px;">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">ช่องทางติดต่อผู้ดูแล</h3>
            <p class="panel__subtitle">การสนับสนุนและแจ้งปัญหาการใช้งาน</p>
          </div>
          <span class="tag" style="background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa;">${icon("bell")} Support</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
          <div style="font-size: 14px; color: #475569; line-height: 1.8;">
            <b>ผู้ดูแลระบบ:</b> ศุภชัย นะที<br>
            <b>อีเมล:</b> <a href="mailto:natee.krapong@gmail.com" style="color: var(--primary); text-decoration: none;">natee.krapong@gmail.com</a><br>
            <b>การแจ้งปัญหา:</b> ผ่านหน้าตั้งค่าระบบ หรือ Telegram<br>
            <b>เวลาสนับสนุน:</b> วันจันทร์ - ศุกร์ 08:30 - 16:30 น.
          </div>
        </div>
      </article>
    </section>
  `;
}

function settingsView() {
  if (!["super_admin"].includes(state.session.role)) {
    return `<section class="panel"><div class="empty-state">คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div></section>`;
  }
  const settings = state.session.settings || {};
  return `
    <section class="panel">
      <div class="panel__head">
        <div>
          <h3 class="panel__title">ตั้งค่าระบบ</h3>
          <p class="panel__subtitle">จัดการข้อมูลทั่วไปและโครงสร้างระบบความปลอดภัย/แจ้งเตือน</p>
        </div>
      </div>
      <form class="settings-form" data-form="settings">
        <div class="form-group">
          <label>ชื่อหน่วยงาน / โรงพยาบาล</label>
          <input name="hospitalName" value="${escapeHtml(settings.hospitalName || "")}" placeholder="ชื่อโรงพยาบาล" required />
        </div>
        <div class="form-group">
          <label>Telegram Bot Token</label>
          <input name="telegramBotToken" value="${escapeHtml(settings.telegramBotToken || "")}" placeholder="เช่น 123456789:ABCdefGhI..." />
        </div>
        <div class="form-group">
          <label>Telegram Chat ID</label>
          <input name="telegramChatId" value="${escapeHtml(settings.telegramChatId || "")}" placeholder="เช่น -100123456789" />
        </div>
        <div style="display: flex; gap: 12px; align-items: center; margin-top: 14px;">
          <button class="primary-button" type="submit">${icon("check")} บันทึกการตั้งค่า</button>
          <button class="secondary-button" type="button" id="btnTestTelegram">${icon("bell")} ทดสอบส่งแจ้งเตือน Telegram</button>
        </div>
      </form>
    </section>
  `;
}

function loadingView() {
  return `<section class="panel"><div class="empty-state">กำลังโหลดข้อมูล...</div></section>`;
}

function userDisplayName(user) {
  return user?.displayName ?? user?.display_name ?? user?.name ?? "";
}

function escapeHtml(value) {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function privacyView() {
  return `
    <section class="section-stack">
      <article class="panel">
        <div class="panel__head">
          <div>
            <h3 class="panel__title">นโยบายความเป็นส่วนตัว (Privacy Policy)</h3>
            <p class="panel__subtitle">การดูแลรักษาความปลอดภัยของข้อมูลบุคลากรและข้อมูลระบบ</p>
          </div>
          <span class="tag">${icon("lock")} ความปลอดภัยสูงสุด</span>
        </div>
        <div class="banner" style="background: #f0fdf4; border-color: #bbf7d0; color: #166534; margin-bottom: 20px;">
          <div class="banner__title" style="color: #166534;">การเข้ารหัสข้อมูลและการรักษาความปลอดภัย</div>
          <p class="banner__text" style="color: #166534; opacity: 0.9;">
            ระบบนี้ให้ความสำคัญสูงสุดกับการคุ้มครองข้อมูลส่วนบุคคล (PDPA) ตามมาตรฐานการรักษาความปลอดภัยข้อมูลทางการแพทย์
          </p>
        </div>
        
        <div style="display: grid; gap: 20px; line-height: 1.6;">
          <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--line);">
            <h4 style="margin: 0 0 10px; color: var(--text); font-weight: 700;">1. การจัดเก็บข้อมูลผู้ใช้งาน</h4>
            <p style="margin: 0; color: var(--muted); font-size: 0.9rem;">
              ระบบจะจัดเก็บเฉพาะข้อมูลที่จำเป็นสำหรับการระบุตัวตนในการปฏิบัติงาน ได้แก่ รหัสผู้ใช้งาน (Provider ID), ชื่อ-นามสกุล, และประวัติการเข้าใช้งานระบบ (Audit Log) เพื่อใช้ในการตรวจสอบย้อนกลับตามมาตรฐานความปลอดภัย
            </p>
          </div>

          <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--line);">
            <h4 style="margin: 0 0 10px; color: var(--text); font-weight: 700;">2. การเข้ารหัสข้อมูลที่สำคัญ</h4>
            <p style="margin: 0; color: var(--muted); font-size: 0.9rem;">
              ข้อมูลส่วนบุคคลและข้อมูลยืนยันตัวตนที่สำคัญจะถูกเข้ารหัสแบบทางเดียว (Hash) และเก็บรักษาโดยใช้คีย์ความปลอดภัยที่เป็นความลับเฉพาะของแอปพลิเคชัน (APP_KEY) จะไม่มีการบันทึกข้อมูลดิบแบบ plain text ลงในฐานข้อมูล
            </p>
          </div>

          <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--line);">
            <h4 style="margin: 0 0 10px; color: var(--text); font-weight: 700;">3. สิทธิ์การเข้าถึงข้อมูล (Role-Based Access Control)</h4>
            <p style="margin: 0; color: var(--muted); font-size: 0.9rem;">
              การเข้าถึงข้อมูลในระบบถูกจำกัดตามบทบาทหน้าที่ของผู้ใช้งาน (Roles) บุคลากรในแต่ละแผนกจะสามารถเข้าถึงและแก้ไขข้อมูลเฉพาะกล่องยาและสถานที่ที่เกี่ยวข้องในขอบเขตหน้าที่ที่ได้รับมอบหมายเท่านั้น
            </p>
          </div>

          <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--line);">
            <h4 style="margin: 0 0 10px; color: var(--text); font-weight: 700;">4. การบันทึกประวัติการใช้งาน (Audit Logs)</h4>
            <p style="margin: 0; color: var(--muted); font-size: 0.9rem;">
              ทุกการเปลี่ยนแปลงข้อมูลที่สำคัญ เช่น การเพิ่ม/ลบ/แก้ไขข้อมูลยา, กล่องยาฉุกเฉิน, และการอนุมัติแจ้งเตือน จะถูกบันทึกไว้ในระบบประวัติการทำงานเพื่อความโปร่งใสและตรวจสอบความถูกต้องย้อนหลังได้ตลอดเวลา
            </p>
          </div>
        </div>
      </article>
    </section>
  `;
}

function viewContent() {
  switch (state.activeView) {
    case "boxes":
      return boxesView();
    case "boxDetail":
      return boxDetailView();
    case "locations":
      return locationsView();
    case "drugs":
      return drugsView();
    case "alerts_drug":
      return alertsView("drug");
    case "alerts_medical_supply":
      return alertsView("medical_supply");
    case "alerts_reagent":
      return alertsView("reagent");
    case "alerts_lab_test_kit":
      return alertsView("lab_test_kit");
    case "alerts":
      return alertsView();
    case "settings":
      return settingsView();
    case "reports":
      return reportsView();
    case "users":
      return usersView();
    case "privacy":
      return privacyView();
    case "guide":
      return guideView();
    case "about":
      return aboutView();
    default:
      return dashboardView();
  }
}

async function submitLogin(event) {
  event.preventDefault();
  const formData = new FormData(event.currentTarget);
  const payload = Object.fromEntries(formData.entries());
  state.error = "";
  const result = await api("/api/auth/login", {
    method: "POST",
    body: JSON.stringify(payload),
  });
  state.session = result.user || null;
  state.showRegister = false;
  if (state.session && (state.session.approvalStatus || state.session.status) === "active") {
    try {
      await loadCurrentView();
      connectEvents();
    } catch {
      // Keep the login success even if a post-login refresh fails.
    }
  } else if (eventSource) {
    eventSource.close();
    eventSource = null;
  }
  render();
}

async function submitRegister(event) {
  event.preventDefault();
  const formData = new FormData(event.currentTarget);
  const payload = Object.fromEntries(formData.entries());
  if (payload.password !== payload.confirmPassword) {
    state.error = "รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน";
    render();
    return;
  }
  await api("/api/auth/register", {
    method: "POST",
    body: JSON.stringify({
      email: payload.providerId, // Backend expects email
      name: payload.displayName, // Backend expects name
      password: payload.password
    }),
  });
  state.error = "";
  state.showRegister = false;
  await loadSession(); // This will pull the session and show pending Approval Screen if active is 0
}

async function handleActionClick(event) {
  const target = event.target.closest("[data-view], [data-action], [data-edit-location], [data-delete-location], [data-edit-drug], [data-delete-drug], [data-edit-box], [data-delete-box], [data-box-cancel], [data-location-cancel], [data-drug-cancel], [data-user-new], [data-user-edit], [data-user-reset], [data-user-toggle], [data-user-delete], [data-user-cancel], [data-user-approve], [data-resolve-alert], [data-modal-open], [data-modal-close], [data-modal-backdrop], [data-page], [data-page-size], [data-confirm-ok], [data-confirm-cancel], #syncButton, #notificationButton, #closeNotifications, #markAllRead, #logoutButton, #menuButton, #backdrop, #btnEnableNotifications, #btnTestTelegram, [data-box-detail], [data-box-back], [data-box-dispatch], [data-cancel-dispatch], [data-remove-dispatch-item], [data-save-dispatch], [data-sort-box]");
  if (!target) return;

  if (target.matches("[data-sort-box]")) {
    const key = target.dataset.sortBox;
    if (state.boxDetailSort && state.boxDetailSort.key === key) {
      state.boxDetailSort.direction = state.boxDetailSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      state.boxDetailSort = { key, direction: 'asc' };
    }
    render();
    return;
  }

  if (target.id === "btnEnableNotifications") {
    if (typeof Notification !== 'undefined') {
      Notification.requestPermission().then(() => {
        render();
      });
    }
    return;
  }

  if (target.matches("[data-view]")) {
    const view = target.dataset.view;
    if (isAllowed(view)) {
      state.activeView = view;
      sessionStorage.setItem("activeView", view);
      if (view !== "boxDetail") sessionStorage.removeItem("boxDetailId");
      closeTransientUI();
      clearNotice();
      await loadCurrentView();
      render();
    }
    return;
  }

  if (target.id === "menuButton") {
    state.sidebarOpen = !state.sidebarOpen;
    render();
    return;
  }

  if (target.id === "backdrop") {
    state.sidebarOpen = false;
    render();
    return;
  }

  if (target.id === "notificationButton") {
    state.notificationsOpen = true;
    render();
    return;
  }

  if (target.id === "btnTestTelegram") {
    const form = document.querySelector(".settings-form");
    if (!form) return;
    const formData = new FormData(form);
    const values = Object.fromEntries(formData.entries());

    target.disabled = true;
    const originalText = target.innerHTML;
    target.innerHTML = "กำลังส่ง...";

    try {
      const res = await api("/api/settings/test-telegram", {
        method: "POST",
        body: JSON.stringify({
          telegramBotToken: values.telegramBotToken,
          telegramChatId: values.telegramChatId,
        }),
      });
      showNotice(res.message || "ส่งข้อความทดสอบสำเร็จ");
    } catch (err) {
      alert("เกิดข้อผิดพลาด: " + err.message);
    } finally {
      target.disabled = false;
      target.innerHTML = originalText;
    }
    return;
  }

  if (target.id === "closeNotifications") {
    state.notificationsOpen = false;
    render();
    return;
  }

  if (target.id === "syncButton") {
    state.syncing = true;
    clearNotice();
    state.error = "";
    render();
    await loadCurrentView();
    const data = await api("/api/notifications");
    state.notifications = data.notifications || [];
    state.syncing = false;
    state.lastSyncedAt = new Date();
    showNotice(`รีเฟรชข้อมูลล่าสุดแล้ว เวลา ${state.lastSyncedAt.toLocaleTimeString("th-TH")}`);
    render();
    return;
  }

  if (target.id === "markAllRead") {
    await api("/api/notifications/read-all", { method: "POST" });
    await refreshNotifications();
    return;
  }

  if (target.id === "logoutButton") {
    await api("/api/auth/logout", { method: "POST" });
    if (eventSource) eventSource.close();
    sessionStorage.removeItem("activeView");
    sessionStorage.removeItem("boxDetailId");
    state.session = null;
    state.dashboard = null;
    state.locations = [];
    state.drugs = [];
    state.boxes = [];
    state.alerts = [];
    state.reports = null;
    state.users = [];
    state.auditLogs = [];
    state.userEditor = null;
    state.locationEditor = null;
    state.drugEditor = null;
    state.boxEditor = null;
    state.notifications = [];
    state.notificationsOpen = false;
    state.sidebarOpen = false;
    render();
    return;
  }

  if (target.matches("[data-resolve-alert]")) {
    const alertId = target.dataset.resolveAlert;
    if (!window.confirm("ยืนยันที่จะอนุมัติ/ปิดรายการแจ้งเตือนนี้?")) return;
    await api(`/api/alerts/${alertId}/resolve`, { method: "POST" });
    await loadCurrentView();
    showNotice("อนุมัติ/ปิดรายการแจ้งเตือนเรียบร้อยแล้ว");
    render();
    return;
  }

  if (target.matches("[data-user-new]")) {
    state.userEditor = null;
    render();
    return;
  }

  if (target.matches("[data-confirm-cancel]")) {
    state.confirmModal = null;
    render();
    return;
  }

  if (target.matches("[data-confirm-ok]")) {
    if (state.confirmModal && state.confirmModal.action) {
      await state.confirmModal.action();
    }
    state.confirmModal = null;
    render();
    return;
  }

  if (target.matches("[data-modal-open]")) {
    state.notificationsOpen = false;
    state.modal = { type: target.dataset.modalOpen, data: null };
    render();
    return;
  }

  if (target.matches("[data-modal-close]")) {
    state.modal = null;
    render();
    return;
  }

  if (target.matches("[data-modal-backdrop]")) {
    if (event.target === target) { state.modal = null; render(); }
    return;
  }

  if (target.matches("[data-page]")) {
    const [key, action] = target.dataset.page.split(":");
    const pg = state.pagination[key];
    if (!pg) return;
    const list = key === "boxes" ? (state.boxes || []) : (state.locations || []);
    const total = list.length;
    const totalPages = Math.max(1, Math.ceil(total / pg.pageSize));
    if (action === "first") pg.page = 1;
    else if (action === "prev")  pg.page = Math.max(1, pg.page - 1);
    else if (action === "next")  pg.page = Math.min(totalPages, pg.page + 1);
    else if (action === "last")  pg.page = totalPages;
    render();
    return;
  }

  if (target.matches("[data-page-size]")) {
    const key = target.dataset.pageSize;
    if (state.pagination[key]) {
      state.pagination[key].pageSize = parseInt(target.value, 10) || 10;
      state.pagination[key].page = 1;
    }
    render();
    return;
  }

  if (target.matches("[data-edit-location]")) {
    const location = state.locations.find((item) => String(item.id) === String(target.dataset.editLocation));
    if (location) {
      state.notificationsOpen = false;
      state.modal = { type: "location", data: { ...location } };
    }
    render();
    return;
  }

  if (target.matches("[data-delete-location]")) {
    const location = state.locations.find((item) => String(item.id) === String(target.dataset.deleteLocation));
    if (!location) return;
    state.confirmModal = {
      message: `ลบสถานที่ ${location.name} ?`,
      action: async () => {
        await api(`/api/locations/${location.id}`, { method: "DELETE" });
        removeById(state.locations, location.id);
        showNotice(`ลบสถานที่ ${location.name} แล้ว`);
        render();
      }
    };
    render();
    return;
  }

  if (target.matches("[data-location-cancel]")) {
    state.locationEditor = null;
    clearNotice();
    render();
    return;
  }

  if (target.matches("[data-edit-box]")) {
    const box = state.boxes.find((item) => String(item.id) === String(target.dataset.editBox));
    if (box) {
      state.notificationsOpen = false;
      state.modal = { type: "box", data: { ...box } };
    }
    render();
    return;
  }

  if (target.matches("[data-box-cancel]")) {
    state.boxEditor = null;
    clearNotice();
    render();
    return;
  }

  if (target.matches("[data-delete-box]")) {
    const box = state.boxes.find((item) => String(item.id) === String(target.dataset.deleteBox));
    if (!box) return;
    state.confirmModal = {
      message: `ลบกล่องยาฉุกเฉิน ${box.name || box.code} ?`,
      action: async () => {
        await api(`/api/boxes/${box.id}`, { method: "DELETE" });
        removeById(state.boxes, box.id);
        showNotice(`ลบกล่อง ${box.name || box.code} แล้ว`);
        render();
      }
    };
    render();
    return;
  }

  if (target.matches("[data-edit-drug]")) {
    const drug = state.drugs.find((item) => String(item.id) === String(target.dataset.editDrug));
    state.notificationsOpen = false;
    state.drugEditor = drug ? { ...drug } : null;
    if (drug) showNotice(`กำลังแก้ไขยา: ${drug.name}`, 1200);
    else clearNotice();
    render();
    return;
  }

  if (target.matches("[data-delete-drug]")) {
    const drug = state.drugs.find((item) => String(item.id) === String(target.dataset.deleteDrug));
    if (!drug) return;
    state.confirmModal = {
      message: `ลบยา/เวชภัณฑ์ ${drug.name} ?`,
      action: async () => {
        await api(`/api/drugs/${drug.id}`, { method: "DELETE" });
        removeById(state.drugs, drug.id);
        showNotice(`ลบ ${drug.name} แล้ว`);
        render();
      }
    };
    render();
    return;
  }

  if (target.matches("[data-drug-cancel]")) {
    state.drugEditor = null;
    clearNotice();
    render();
    return;
  }

  if (target.matches("[data-user-cancel]")) {
    state.userEditor = null;
    render();
    return;
  }

  if (target.matches("[data-user-edit]")) {
    const user = state.users.find((item) => String(item.id) === String(target.dataset.userEdit));
    state.notificationsOpen = false;
    state.userEditor = user ? { ...user } : null;
    render();
    return;
  }

  if (target.matches("[data-user-reset]")) {
    const newPassword = window.prompt("ตั้งรหัสผ่านใหม่", "Temp@1234");
    if (!newPassword) return;
    await api(`/api/users/${target.dataset.userReset}/reset-password`, {
      method: "POST",
      body: JSON.stringify({ password: newPassword }),
    });
    await loadCurrentView();
    render();
    return;
  }

  if (target.matches("[data-user-toggle]")) {
    const user = state.users.find((item) => String(item.id) === String(target.dataset.userToggle));
    if (!user) return;
    await api(`/api/users/${user.id}`, {
      method: "PATCH",
      body: JSON.stringify({ active: !user.active }),
    });
    await loadCurrentView();
    render();
    return;
  }

  if (target.matches("[data-user-delete]")) {
    const user = state.users.find((item) => String(item.id) === String(target.dataset.userDelete));
    if (!user) return;
    state.confirmModal = {
      message: `ลบผู้ใช้ ${user.displayName} ?`,
      action: async () => {
        await api(`/api/users/${user.id}`, { method: "DELETE" });
        removeById(state.users, user.id);
        showNotice(`ลบผู้ใช้ ${user.displayName} แล้ว`);
        render();
      }
    };
    render();
    return;
  }

  if (target.matches("[data-user-approve]")) {
    const user = state.users.find((item) => String(item.id) === String(target.dataset.userApprove));
    if (!user) return;
    state.notificationsOpen = false;
    state.userEditor = { ...user, role: "viewer", locationId: "" };
    render();
    return;
  }

  if (target.closest("[data-action='openNotifications']")) {
    state.notificationsOpen = true;
    render();
    return;
  }

  if (target.matches("[data-box-detail]")) {
    if (event.target.closest(".row-actions")) {
      return;
    }
    const id = target.dataset.boxDetail;
    state.activeView = "boxDetail";
    sessionStorage.setItem("activeView", "boxDetail");
    sessionStorage.setItem("boxDetailId", id);
    state.boxDetail = { boxId: id, box: null, items: [], rounds: [] };
    state.boxDispatchMode = false;
    state.boxDispatchItems = [];
    closeTransientUI();
    clearNotice();
    await loadCurrentView();
    render();
    return;
  }

  if (target.matches("[data-box-back]")) {
    state.activeView = "boxes";
    sessionStorage.setItem("activeView", "boxes");
    sessionStorage.removeItem("boxDetailId");
    state.boxDetail = null;
    state.boxDispatchMode = false;
    state.boxDispatchItems = [];
    closeTransientUI();
    clearNotice();
    await loadCurrentView();
    render();
    return;
  }

  if (target.matches("[data-box-dispatch]")) {
    state.boxDispatchMode = true;
    state.boxDispatchItems = JSON.parse(JSON.stringify(state.boxDetail.items || []));
    render();
    return;
  }

  if (target.matches("[data-cancel-dispatch]")) {
    state.boxDispatchMode = false;
    state.boxDispatchItems = [];
    render();
    return;
  }

  if (target.closest("[data-remove-dispatch-item]")) {
    const removeTarget = target.closest("[data-remove-dispatch-item]");
    const idx = parseInt(removeTarget.dataset.removeDispatchItem, 10);
    state.boxDispatchItems.splice(idx, 1);
    render();
    return;
  }

  if (target.closest("[data-save-dispatch]")) {
    const saveTarget = target.closest("[data-save-dispatch]");
    const boxId = saveTarget.dataset.saveDispatch;
    const inputs = document.querySelectorAll(".dispatch-table input, .dispatch-table select");
    let valid = true;
    inputs.forEach(input => {
      if (!input.checkValidity()) {
        input.reportValidity();
        valid = false;
      }
    });
    if (!valid) return;

    state.syncing = true;
    render();
    try {
      await api(`/api/boxes/${boxId}/rounds`, {
        method: "POST",
        body: JSON.stringify({
          items: state.boxDispatchItems
        })
      });
      state.boxDispatchMode = false;
      state.boxDispatchItems = [];
      showNotice("บันทึกรอบจัดยาเรียบร้อยแล้ว");
      await loadCurrentView();
    } catch (err) {
      state.error = err.message;
    } finally {
      state.syncing = false;
      render();
    }
    return;
  }

  if (target.matches("[data-action]")) {
    const view = target.dataset.action;
    if (isAllowed(view)) {
      state.activeView = view;
      closeTransientUI();
      await loadCurrentView();
      render();
    }
  }
}

async function handleForms(event) {
  const form = event.target.closest("[data-form]");
  if (!form) return;
  event.preventDefault();
  const formData = new FormData(form);
  const values = Object.fromEntries(formData.entries());
  if (form.dataset.form === "location") {
    const isEditing = Boolean(values.id);
    const saved = await api(isEditing ? `/api/locations/${values.id}` : "/api/locations", {
      method: isEditing ? "PATCH" : "POST",
      body: JSON.stringify({
        name: values.name,
        detail: values.detail,
        details: values.detail,
        status: values.status,
      }),
    });
    upsertById(state.locations, saved);
    closeTransientUI();
    showNotice(isEditing ? "บันทึกการแก้ไขสถานที่แล้ว" : "เพิ่มสถานที่แล้ว");
  }
  if (form.dataset.form === "box") {
    const isEditing = Boolean(values.id);
    const boxId = values.id;
    const saved = await api(isEditing ? `/api/boxes/${boxId}` : "/api/boxes", {
      method: isEditing ? "PATCH" : "POST",
      body: JSON.stringify({
        code: values.code,
        name: values.code,
        locationId: values.locationId || null,
        location_id: values.locationId || null,
        drugCount: Number.parseInt(values.drugCount, 10) || 0,
        drug_count: Number.parseInt(values.drugCount, 10) || 0,
        status: values.status,
      }),
    });
    upsertById(state.boxes, saved);
    closeTransientUI();
    showNotice(isEditing ? "บันทึกการแก้ไขกล่องแล้ว" : "เพิ่มกล่องแล้ว");
  }
  if (form.dataset.form === "drug") {
    const isEditing = Boolean(values.id);
    const saved = await api(isEditing ? `/api/drugs/${values.id}` : "/api/drugs", {
      method: isEditing ? "PATCH" : "POST",
      body: JSON.stringify({
        name: values.name,
        unit: values.unit,
        price: values.price,
        category: values.category,
      }),
    });
    upsertById(state.drugs, saved);
    closeTransientUI();
    showNotice(isEditing ? "บันทึกการแก้ไขยาแล้ว" : "เพิ่มยาแล้ว");
  }
  if (form.dataset.form === "user") {
    const isEditing = Boolean(values.id);
    if (isEditing) {
      const originalUser = state.users.find((u) => String(u.id) === String(values.id));
      if (originalUser && (originalUser.approvalStatus || originalUser.status) === "pending") {
        const payload = {
          role: values.role,
          location_id: values.locationId || null,
          status: "active",
          active: true,
        };
        const saved = await api(`/api/users/${values.id}/approve`, {
          method: "PATCH",
          body: JSON.stringify(payload),
        });
        upsertById(state.users, saved);
        closeTransientUI();
      } else {
        const payload = {
          username: values.username,
          display_name: values.display_name,
          role: values.role,
          location_id: values.locationId || null,
          active: state.userEditor ? state.userEditor.active : true,
        };
        if (values.password) payload.password = values.password;
        const saved = await api(`/api/users/${values.id}`, { method: "PATCH", body: JSON.stringify(payload) });
        upsertById(state.users, saved);
        closeTransientUI();
      }
    } else {
      const payload = {
        username: values.username,
        display_name: values.display_name,
        role: values.role,
        location_id: values.locationId || null,
        password: values.password,
      };
      const saved = await api("/api/users", { method: "POST", body: JSON.stringify(payload) });
      upsertById(state.users, saved);
      closeTransientUI();
    }
  }
  if (form.dataset.form === "settings") {
    await api("/api/settings", {
      method: "PATCH",
      body: JSON.stringify({
        hospitalName: values.hospitalName,
        telegramBotToken: values.telegramBotToken,
        telegramChatId: values.telegramChatId,
      }),
    });
    closeTransientUI();
    await loadSession();
    showNotice("บันทึกการตั้งค่าระบบเรียบร้อยแล้ว");
  }
  if (form.dataset.form === "addDispatchItem") {
    const drug = state.drugs.find(d => d.id === values.drug_id);
    if (drug) {
      state.boxDispatchItems.push({
        drug_id: drug.id,
        drug_name: drug.name,
        lot_number: values.lot_number,
        quantity: parseInt(values.quantity, 10) || 0,
        unit: drug.unit,
        expiry_date: values.expiry_date,
        alert_days: parseInt(values.alert_days, 10) || 30
      });
      state.modal = null;
      showNotice(`เพิ่มยา ${drug.name} สำเร็จ`);
    }
  }
  render();
}

async function handleInput(event) {
  const dispatchField = event.target.closest("[data-dispatch-field]");
  if (dispatchField) {
    const field = dispatchField.dataset.dispatchField;
    const index = parseInt(dispatchField.dataset.index, 10);
    let val = dispatchField.value;
    if (field === "quantity" || field === "alert_days") {
      val = parseInt(val, 10) || 0;
    }
    state.boxDispatchItems[index][field] = val;
    return;
  }

  const input = event.target.closest("[data-filter]");
  if (!input) return;
  state.filters[input.dataset.filter] = input.value;
  render();
}

function render() {
  if (state.loading) {
    app.innerHTML = loadingView();
    return;
  }
  if (!state.session) {
    if (state.showRegister) {
      app.innerHTML = registerScreen();
      bindRegisterEvents();
    } else {
      app.innerHTML = loginScreen();
      bindLoginEvents();
    }
    return;
  }
  if (state.session.status === "inactive") {
    app.innerHTML = pendingApprovalScreen();
    bindPendingEvents();
    return;
  }
  app.innerHTML = shell();
  bindAppEvents();
}

function bindLoginEvents() {
  const registerButton = document.getElementById("btnGoRegister");
  const thaidButton = document.getElementById("btnLoginThaid");
  const btnToggle = document.getElementById("btnToggleLocalAdmin");
  const form = document.getElementById("loginForm");
  const panel = document.getElementById("localAdminPanel");

  registerButton?.addEventListener("click", () => {
    state.error = "";
    state.showRegister = true;
    render();
  });

  thaidButton?.addEventListener("click", () => {
    window.location.href = appUrl("/api/auth/thaid/start");
  });
  
  if (btnToggle && panel) {
    btnToggle.addEventListener("click", () => {
      if (panel.style.display === "none") {
        panel.style.display = "block";
        btnToggle.textContent = "ซ่อน Local Admin สำรอง";
      } else {
        panel.style.display = "none";
        btnToggle.textContent = "แสดง Local Admin สำรอง";
      }
    });
  }

  form?.addEventListener("submit", (event) => {
    submitLogin(event).catch((error) => {
      if (error.payload && error.payload.status === "pending_approval") {
        const formData = new FormData(event.currentTarget);
        state.session = { status: "inactive", displayName: formData.get("username") || "ผู้ใช้งานใหม่" };
        state.error = "";
      } else {
        state.error = error.message;
      }
      render();
    });
  });
}

function bindRegisterEvents() {
  const form = document.getElementById("registerForm");
  form?.addEventListener("submit", (event) => {
    submitRegister(event).catch((error) => {
      state.error = error.message;
      render();
    });
  });

  const btnGoLogin = document.getElementById("btnGoLogin");
  btnGoLogin?.addEventListener("click", () => {
    state.error = "";
    state.showRegister = false;
    render();
  });
}

function bindAppEvents() {
  if (state.appEventsBound) return;
  state.appEventsBound = true;
  // Use event delegation: one listener catches all dynamic elements.
  app.addEventListener("click", (event) => handleActionClick(event).catch((error) => {
    state.syncing = false;
    state.error = error.message;
    render();
  }));
  app.addEventListener("change", (event) => {
    const dispatchField = event.target.closest("[data-dispatch-field]");
    if (dispatchField) {
      const field = dispatchField.dataset.dispatchField;
      const index = parseInt(dispatchField.dataset.index, 10);
      let val = dispatchField.value;
      if (field === "quantity" || field === "alert_days") {
        val = parseInt(val, 10) || 0;
      }
      state.boxDispatchItems[index][field] = val;
      return;
    }

    const sel = event.target.closest("[data-page-size]");
    if (sel) {
      const key = sel.dataset.pageSize;
      if (state.pagination[key]) {
        state.pagination[key].pageSize = parseInt(sel.value, 10) || 10;
        state.pagination[key].page = 1;
      }
      render();
    }
  });
  app.addEventListener("submit", (event) => handleForms(event).catch((error) => {
    state.error = error.message;
    render();
  }));
  app.addEventListener("input", handleInput);
}

loadSession().catch((error) => {
  state.error = error.message;
  state.loading = false;
  render();
});





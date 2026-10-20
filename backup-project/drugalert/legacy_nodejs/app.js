const app = document.getElementById("app");
const appBaseUrl = new URL("./", document.baseURI);
const initialUrl = new URL(window.location.href);

function appUrl(path) {
  return new URL(String(path).replace(/^\/+/, ""), appBaseUrl).toString();
}

const state = {
  session: null,
  activeView: "dashboard",
  loading: true,
  notificationsOpen: false,
  sidebarOpen: false,
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
  };
  return `<span class="icon ${extraClass}">${shapes[name] || shapes.info}</span>`;
}

function api(path, options = {}) {
  const headers = new Headers(options.headers || {});
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
      const error = new Error(payload?.error || `Request failed (${response.status})`);
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
  return !item || item.roles.includes(role);
}

function closeTransientUI() {
  state.modal = null;
  state.confirmModal = null;
  state.notificationsOpen = false;
  state.sidebarOpen = false;
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
  const index = list.findIndex((entry) => entry.id === item.id);
  if (index >= 0) {
    list[index] = item;
  } else {
    list.unshift(item);
  }
}

function removeById(list, id) {
  const index = list.findIndex((entry) => entry.id === id);
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
      state.activeView = isAllowed(state.activeView) ? state.activeView : "dashboard";
      if ((state.session.approvalStatus || state.session.status) === "active") {
        await loadCurrentView();
        connectEvents();
      } else if (eventSource) {
        eventSource.close();
        eventSource = null;
      }
    }
  } catch (error) {
    state.error = error.message;
  } finally {
    state.loading = false;
    render();
  }
}

async function loadCurrentView() {
  if (!state.session) return;
  const view = state.activeView;
  if (view === "dashboard") {
    const data = await api("/api/dashboard");
    state.dashboard = data.dashboard;
    state.notifications = data.notifications || [];
  } else if (view === "boxes") {
    const [data, boxes, locations] = await Promise.all([
      api("/api/dashboard"),
      api("/api/boxes"),
      api("/api/locations"),
    ]);
    state.dashboard = data.dashboard;
    state.notifications = data.notifications || [];
    state.boxes = boxes;
    state.locations = locations;
  } else if (view === "locations") {
    state.locations = await api("/api/locations");
  } else if (view === "drugs") {
    state.drugs = await api("/api/drugs");
  } else if (view.startsWith("alerts_")) {
    const data = await api("/api/alerts");
    state.alerts = data.alerts || [];
    state.notifications = data.notifications || [];
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
    state.notifications = data.notifications || [];
  }
}

async function refreshNotifications() {
  if (!state.session) return;
  const data = await api("/api/notifications");
  state.notifications = data.notifications || [];
  render();
}

let eventSource = null;
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

function pillTone(status) {
  if (status.includes("พร้อมใช้งาน")) return "pill--success";
  if (status.includes("รอเติมยา") || status.includes("กำลังตรวจสอบ")) return "pill--warning";
  if (status.includes("ใช้งาน") || status.includes("พร้อมใช้") || status.includes("รับรอง")) return "pill--success";
  if (status.includes("ใกล้หมด") || status.includes("ใกล้")) return "pill--warning";
  if (status.includes("หมด")) return "pill--danger";
  return "pill--info";
}

function loginScreen() {
  return `
    <main style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at top, #0f7b4f 0%, #0a3d2c 60%, #08261c 100%); padding: 24px;">
      <div style="background: rgba(255,255,255,0.96); border-radius: 24px; padding: 40px; width: 100%; max-width: 560px; box-shadow: 0 24px 60px rgba(0,0,0,0.24); text-align: center;">
        <div style="margin-bottom: 20px; display: flex; justify-content: center;">
          <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
             <span style="color: #0d905a; font-weight: 700; font-size: 30px; letter-spacing: 1px;">MEDICAL</span>
             <span style="background: #0d905a; color: white; font-weight: 700; font-size: 30px; padding: 0 8px; border-radius: 8px;">BOX</span>
          </div>
        </div>

        <p style="margin: 0 0 28px; color: #475569; font-size: 15px; line-height: 1.7;">
          เลือกเข้าสู่ระบบด้วย ProviderID หรือ ThaID สำหรับผู้ใช้งานภายนอก
          <br />Local Admin สำรองยังใช้รหัสผ่านได้จากด้านล่าง
        </p>

        <div style="display: grid; gap: 12px; margin-bottom: 18px;">
          <button type="button" id="btnLoginProviderId" style="width: 100%; padding: 14px; background: #0d905a; color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: 700; box-shadow: 0 10px 18px rgba(13,144,90,0.22);">
            Login ด้วย ProviderID
          </button>
          <button type="button" id="btnLoginThaid" style="display: flex; align-items: center; justify-content: center; gap: 10px; width: 100%; padding: 14px; background: white; border: 1px solid #cbd5e1; border-radius: 10px; color: #0f172a; font-size: 16px; font-weight: 700;">
            <span style="width: 26px; height: 26px; background: #0f172a; color: #fbbf24; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px;">ThaID</span>
            Login ด้วย ThaID
          </button>
        </div>

        <button type="button" id="btnToggleLocalAdmin" style="width: 100%; padding: 12px; background: #f8fafc; border: 1px dashed #94a3b8; border-radius: 10px; color: #334155; font-size: 14px; font-weight: 600; margin-bottom: 16px;">
          แสดง Local Admin สำรอง
        </button>

        <div id="localAdminPanel" style="display: none; text-align: left; border-top: 1px solid #e2e8f0; padding-top: 18px;">
          <form id="loginForm">
            <label style="display: block; color: #475569; font-size: 14px; font-weight: bold; margin-bottom: 8px;">ชื่อผู้ใช้</label>
            <input type="text" name="username" autocomplete="username" autocapitalize="off" spellcheck="false" placeholder="กรอกชื่อผู้ใช้" required style="width: 100%; padding: 14px; margin-bottom: 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 16px;" />
            <label style="display: block; color: #475569; font-size: 14px; font-weight: bold; margin-bottom: 8px;">รหัสผ่าน</label>
            <input type="password" name="password" autocomplete="current-password" placeholder="กรอกรหัสผ่าน" required style="width: 100%; padding: 14px; margin-bottom: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 16px;" />
            <button type="submit" style="width: 100%; padding: 14px; background: #0f172a; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 700;">เข้าสู่ระบบ Local Admin</button>
          </form>
        </div>

        ${state.error ? `<div style="color: #ef4444; margin-top: 16px; font-size: 14px; text-align: left;">${state.error}</div>` : ""}

        <p style="color: #94a3b8; font-size: 12px; margin-top: 28px; line-height: 1.6;">
          ข้อมูลบัตรประชาชนจะไม่ถูกเก็บแบบ plain text และจะถูกแปลงเป็น cid_hash ตาม APP_KEY
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
          <h2 class="auth-steps-title">ลงทะเบียน Provider ID</h2>
          <p class="auth-steps-subtitle">สำหรับบุคลากรทางการแพทย์เพื่อเข้าสู่ระบบ</p>
          <ul class="auth-steps-list">
            <li><span>1</span> กรอกรหัส Provider ID ที่ได้รับ</li>
            <li><span>2</span> ระบุชื่อ-นามสกุล</li>
            <li><span>3</span> ตั้งรหัสผ่านและยืนยันรหัสผ่าน</li>
            <li><span>4</span> เมื่อลงทะเบียนเสร็จสิ้นจะสามารถเข้าสู่ระบบได้ทันที</li>
          </ul>
        </div>
        <div class="new-auth-right">
          <div class="provider-logo">
            <span class="provider-text">PROVIDER</span>
            <span class="id-badge">ID</span>
          </div>
          <form class="auth-form new-auth-form" id="registerForm">
            <input type="text" name="providerId" class="new-auth-input" placeholder="รหัสผู้ใช้ (Provider ID)" required />
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
      api("/api/auth/logout", { method: "POST" }).then(() => {
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
  ].filter(item => item.roles.includes(session.role));

  const totalAlerts = (state.alerts || []).length;

  const mainNavAfter = [
    { view: "reports", label: "รายงาน", icon: "report", roles: ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"] },
    { view: "users", label: "ผู้ใช้งาน", icon: "users", roles: ["super_admin"] },
  ].filter(item => item.roles.includes(session.role));

  return `
    <div class="app-shell">
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
  return `
    <article class="metric-card metric-card--${metric.tone}">
      <div class="metric-card__head">
        <span class="metric-card__label">${icon(metric.icon, "icon--lg")} ${metric.label}</span>
        <span class="metric-card__icon">${icon(metric.icon)}</span>
      </div>
      <div class="metric-card__value">${metric.value}</div>
      <div class="metric-card__meta">${metric.meta}</div>
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
            <h3 class="panel__title">Summary มูลค่ายาแยกตามกล่องยาฉุกเฉิน</h3>
            <p class="panel__subtitle">กล่องฉุกเฉินแต่ละหน่วย พร้อมมูลค่าสินค้าคงเหลือและสถานะพร้อมใช้</p>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>กล่องยาฉุกเฉิน</th>
                <th>มูลค่า</th>
                <th>สถานะ</th>
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
                  <td class="money">${item.value}</td>
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
            <h3 class="panel__title">ตารางการแจ้งเตือนล่าสุด</h3>
            <p class="panel__subtitle">ติดตามกล่องยาฉุกเฉิน ยา จำนวน สถานะ และวันหมดอายุ</p>
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
              <tr>
                <td><strong>${escapeHtml(item.name || item.code || "-")}</strong></td>
                <td>${escapeHtml(state.locations.find((location) => location.id === item.locationId)?.name || "-")}</td>
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
                  <option value="${location.id}" ${editor?.locationId === location.id ? "selected" : ""}>${escapeHtml(location.name)}</option>
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

function locationsView() {
  const canEdit = ["super_admin"].includes(state.session.role);
  const pg = state.pagination.locations;
  const fullList = (state.locations || []).filter((item) =>
    item.name.toLowerCase().includes((state.filters.locations || "").toLowerCase()),
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
    item.name.toLowerCase().includes(state.filters.drugs.toLowerCase()),
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
              .filter(opt => opt.roles.includes(state.session.role))
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
                <td>${canEdit ? `<button class="action-icon" type="button" data-edit-drug="${item.id}" title="แก้ไขยา" aria-label="แก้ไขยา ${escapeHtml(item.name)}">${icon("edit")}</button>` : "-"}</td>
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
      return alert.locationId === locId;
    }
    if (role === "pharmacist") {
      if (alert.category === "drug") return true;
      if (alert.category === "medical_supply") return alert.locationId === locId;
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
  if (!state.reports) return loadingView();
  return `
    <section class="section-stack">
      <div class="subgrid">
        <article class="panel">
          <div class="panel__head">
            <div>
              <h3 class="panel__title">รายงานการใช้ไป</h3>
              <p class="panel__subtitle">จำนวนและมูลค่ายาที่ใช้ไปแยกตามกล่อง รายเดือน และรายการยา</p>
            </div>
            <span class="tag">${icon("report")} ปัจจุบัน</span>
          </div>
          <div class="timeline">
            ${(state.reports.usage || []).map((item) => `
              <div class="timeline__item">
                <div class="timeline__dot${item.kind === "good" ? " timeline__dot--green" : item.kind === "warn" ? " timeline__dot--amber" : ""}"></div>
                <div class="timeline__body">
                  <p class="timeline__title">${item.title}</p>
                  <p class="timeline__text">${item.text}</p>
                </div>
              </div>
            `).join("")}
          </div>
        </article>
        <article class="panel">
          <div class="panel__head">
            <div>
              <h3 class="panel__title">รายงานการจัดยา</h3>
              <p class="panel__subtitle">สรุปยาคงเหลือหลังจัดในแต่ละรอบ</p>
            </div>
            <span class="tag">${icon("box")} รอบล่าสุด</span>
          </div>
          <div class="empty-state">
            <strong>Summary มูลค่ายาเหลือหลังจัด</strong>
            <p>${state.reports.summary}</p>
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
            ${locations.map((loc) => `<option value="${loc.id}" ${editor && editor.locationId === loc.id ? "selected" : ""}>${escapeHtml(loc.name)}</option>`).join("")}
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
                <td>${locations.find((l) => l.id === user.locationId)?.name || "-"}</td>
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
        <div class="timeline">
          <div class="timeline__item"><div class="timeline__dot"></div><div class="timeline__body"><p class="timeline__title">1. การเข้าระบบ</p><p class="timeline__text">เข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่าน ระบบจะโหลดข้อมูลตามสิทธิ์ของผู้ใช้ที่ login อยู่</p></div></div>
          <div class="timeline__item"><div class="timeline__dot"></div><div class="timeline__body"><p class="timeline__title">2. การใช้ข้อมูล</p><p class="timeline__text">เพิ่ม แก้ไข หรือค้นหายา/สถานที่/ผู้ใช้ เพื่อให้ข้อมูลตรงกับการใช้งานจริง</p></div></div>
          <div class="timeline__item"><div class="timeline__dot timeline__dot--green"></div><div class="timeline__body"><p class="timeline__title">3. การดูแจ้งเตือน</p><p class="timeline__text">สรุปยาใกล้หมดอายุและรายการผิดปกติ เพื่อให้ทีมตอบสนองได้เร็ว</p></div></div>
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
            <h3 class="panel__title">เกี่ยวกับระบบ</h3>
            <p class="panel__subtitle">Emergency Medical Box Monitoring - ระบบติดตามกล่องยาและแจ้งเตือนสำหรับหน่วยงานโรงพยาบาล</p>
          </div>
          <span class="tag">${icon("shield")} Full Stack</span>
        </div>
        <div class="banner">
          <div class="muted-note">รองรับมือถือ</div>
          <div class="banner__title">ใช้งานได้ทั้งบนคอมพิวเตอร์และสมาร์ทโฟน</div>
          <p class="banner__text">
            ระบบถูกออกแบบให้เหมาะกับหน่วยงานที่ต้องตรวจสอบสถานะกล่องยาอย่างรวดเร็ว
            มีโครงสร้างที่เรียบง่าย อ่านง่าย และพร้อมเชื่อม API/ฐานข้อมูลจริง
          </p>
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
        <div>
          <button class="primary-button" type="submit">${icon("check")} บันทึกการตั้งค่า</button>
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
      // Keep the user logged in even if a post-login refresh fails.
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
      providerId: payload.providerId,
      displayName: payload.displayName,
      password: payload.password
    }),
  });
  state.error = "";
  state.showRegister = false;
  await loadSession();
}

async function handleActionClick(event) {
  const target = event.target.closest("[data-view], [data-action], [data-edit-location], [data-delete-location], [data-edit-drug], [data-edit-box], [data-delete-box], [data-box-cancel], [data-location-cancel], [data-drug-cancel], [data-user-new], [data-user-edit], [data-user-reset], [data-user-toggle], [data-user-delete], [data-user-cancel], [data-user-approve], [data-resolve-alert], [data-modal-open], [data-modal-close], [data-modal-backdrop], [data-page], [data-page-size], [data-confirm-ok], [data-confirm-cancel], #syncButton, #notificationButton, #closeNotifications, #markAllRead, #logoutButton, #menuButton, #backdrop, #btnEnableNotifications");
  if (!target) return;

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
    const location = state.locations.find((item) => item.id === target.dataset.editLocation);
    if (location) {
      state.notificationsOpen = false;
      state.modal = { type: "location", data: { ...location } };
    }
    render();
    return;
  }

  if (target.matches("[data-delete-location]")) {
    const location = state.locations.find((item) => item.id === target.dataset.deleteLocation);
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
    const box = state.boxes.find((item) => item.id === target.dataset.editBox);
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
    const box = state.boxes.find((item) => item.id === target.dataset.deleteBox);
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
    const drug = state.drugs.find((item) => item.id === target.dataset.editDrug);
    state.notificationsOpen = false;
    state.drugEditor = drug ? { ...drug } : null;
    if (drug) showNotice(`กำลังแก้ไขยา: ${drug.name}`, 1200);
    else clearNotice();
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
    const user = state.users.find((item) => item.id === target.dataset.userEdit);
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
    const user = state.users.find((item) => item.id === target.dataset.userToggle);
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
    const user = state.users.find((item) => item.id === target.dataset.userDelete);
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
    const user = state.users.find((item) => item.id === target.dataset.userApprove);
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
      const originalUser = state.users.find((u) => u.id === values.id);
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
  render();
}

async function handleInput(event) {
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
  if ((state.session.approvalStatus || state.session.status) === "pending") {
    app.innerHTML = pendingApprovalScreen();
    bindPendingEvents();
    return;
  }
  app.innerHTML = shell();
  bindAppEvents();
}

function bindLoginEvents() {
  const providerButton = document.getElementById("btnLoginProviderId");
  const thaidButton = document.getElementById("btnLoginThaid");
  const btnToggle = document.getElementById("btnToggleLocalAdmin");
  const form = document.getElementById("loginForm");
  const panel = document.getElementById("localAdminPanel");

  providerButton?.addEventListener("click", () => {
    window.location.href = appUrl("/api/auth/provider_id/start");
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
      state.error = error.message;
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





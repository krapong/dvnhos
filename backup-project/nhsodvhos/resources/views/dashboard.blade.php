<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้บริหาร NHSO - ระบบติดตามสถานการณ์เงิน สปสช.</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="/public/style.css?v={{ filemtime(public_path('style.css')) }}&cb=20260817b">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <!-- SheetJS (XLSX) CDN -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <!-- Login Screen Overlay -->
    <div id="login-overlay" class="login-overlay-visible">
        <div class="login-card">
            <div class="login-brand">
                <div class="login-logo">
                    <i data-lucide="shield-check"></i>
                </div>
                <h2>แดชบอร์ดผู้บริหาร NHSO</h2>
                <p>ระบบติดตามเงินชดเชย สปสช. กองทุนย่อย</p>
            </div>
            
            <form id="login-form" class="login-form">
                <div class="form-group">
                    <label for="login-username">ชื่อผู้ใช้งาน (Username)</label>
                    <div class="input-wrapper">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" id="login-username" required placeholder="ระบุชื่อผู้ใช้งาน...">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="login-password">รหัสผ่าน (Password)</label>
                    <div class="input-wrapper">
                        <i data-lucide="lock" class="input-icon"></i>
                        <input type="password" id="login-password" required placeholder="ระบุรหัสผ่าน...">
                    </div>
                </div>


                <button type="submit" class="btn btn-primary btn-block">
                    <i data-lucide="log-in"></i>
                    <span>เข้าสู่ระบบ</span>
                </button>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="#" id="show-register" style="color: #3b82f6; text-decoration: none; font-size: 14px;">ลงทะเบียนผู้ใช้ใหม่</a>
                </div>
            </form>

            <form id="register-form" class="login-form" style="display: none;">
                <div class="form-group">
                    <label for="reg-username">ชื่อผู้ใช้งาน (Username)</label>
                    <div class="input-wrapper">
                        <i data-lucide="user" class="input-icon"></i>
                        <input type="text" id="reg-username" required placeholder="ระบุชื่อผู้ใช้งาน...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-password">รหัสผ่าน (Password)</label>
                    <div class="input-wrapper">
                        <i data-lucide="lock" class="input-icon"></i>
                        <input type="password" id="reg-password" required placeholder="ระบุรหัสผ่าน...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-fullname">ชื่อ-นามสกุล</label>
                    <div class="input-wrapper">
                        <i data-lucide="user-plus" class="input-icon"></i>
                        <input type="text" id="reg-fullname" required placeholder="ชื่อ - สกุล...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-phone">เบอร์โทรศัพท์</label>
                    <div class="input-wrapper">
                        <i data-lucide="phone" class="input-icon"></i>
                        <input type="text" id="reg-phone" required placeholder="เบอร์โทรศัพท์...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-position">ตำแหน่ง</label>
                    <div class="input-wrapper">
                        <i data-lucide="briefcase" class="input-icon"></i>
                        <input type="text" id="reg-position" required placeholder="ตำแหน่งงาน...">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-role">ระดับสิทธิ์ (Role)</label>
                    <div class="input-wrapper">
                        <i data-lucide="shield" class="input-icon"></i>
                        <select id="reg-role" class="form-control" style="width: 100%; border: none; padding: 10px 10px 10px 38px; border-radius: 8px; font-family: inherit; font-size: 14px; background: transparent; outline: none;">
                            <option value="USER">ทั่วไป (User)</option>
                            <option value="OFFICER">เจ้าหน้าที่ (Officer)</option>
                            <option value="EXECUTIVE">ผู้บริหาร (Executive)</option>
                            <option value="ADMIN">ผู้ดูแลระบบ (Admin)</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i data-lucide="user-plus"></i>
                    <span>ลงทะเบียน</span>
                </button>
                
                <div style="text-align: center; margin-top: 15px;">
                    <a href="#" id="show-login" style="color: #64748b; text-decoration: none; font-size: 14px;">กลับไปหน้าเข้าสู่ระบบ</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Application Container (Hidden or locked if not logged in) -->
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-icon">
                    <i data-lucide="activity" class="text-blue-primary"></i>
                </div>
                <div class="logo-text">
                    <h1>แดชบอร์ดผู้บริหาร NHSO</h1>
                    <span class="sub-logo">ระบบติดตามเงินชดเชย สปสช.</span>
                </div>
            </div>

            <!-- Logged User Status Widget -->
            <div class="user-status-card">
                <div class="user-avatar">
                    <i data-lucide="user-check"></i>
                </div>
                <div class="user-meta">
                    <h4 id="user-display-name">คุณกำลังใช้งาน</h4>
                    <span id="user-display-role" class="user-role-badge">ระดับสิทธิ์</span>
                </div>
                <button id="btn-logout" class="btn-logout-icon" title="ออกจากระบบ">
                    <i data-lucide="log-out"></i>
                </button>
            </div>

            <!-- Tab Menu -->
            <nav class="sidebar-menu">
                <button class="menu-item active" data-tab="smartmoney">
                    <i data-lucide="line-chart"></i>
                    <span>Smart Money</span>
                </button>
                <button class="menu-item" data-tab="op">
                    <i data-lucide="users"></i>
                    <span>ผู้ป่วยนอก (OP)</span>
                </button>
                <button class="menu-item" data-tab="ip">
                    <i data-lucide="bed-double"></i>
                    <span>ผู้ป่วยใน (IP)</span>
                </button>
                <button class="menu-item" data-tab="ppfs">
                    <i data-lucide="shield-check"></i>
                    <span>ส่งเสริมป้องกัน (PPFS)</span>
                </button>
                <button class="menu-item" data-tab="ppfs_sub">
                    <i data-lucide="home"></i>
                    <span>PPFS รพสต.</span>
                </button>
                <div class="menu-accordion">
                    <button class="menu-item has-submenu" data-tab="seamless" onclick="return window.toggleSeamlessSubmenu ? window.toggleSeamlessSubmenu(event) : false;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i data-lucide="git-pull-request"></i>
                            <span>จ่ายชดเชย Seamless</span>
                        </div>
                        <i data-lucide="chevron-down" class="submenu-icon"></i>
                    </button>
                    <div class="submenu-list">
                                <button class="submenu-item" data-subtab="ckd"><i data-lucide="activity" style="width:16px;height:16px;margin-right:6px;"></i><span>CKD</span></button>
                                <button class="submenu-item" data-subtab="ktp"><i data-lucide="building-2" style="width:16px;height:16px;margin-right:6px;"></i><span>KTP</span></button>
                                <button class="submenu-item" data-subtab="napp"><i data-lucide="clipboard-list" style="width:16px;height:16px;margin-right:6px;"></i><span>napp</span></button>
                                <button class="submenu-item" data-subtab="tb"><i data-lucide="wind" style="width:16px;height:16px;margin-right:6px;"></i><span>TB</span></button>
                                <button class="submenu-item" data-subtab="downs"><i data-lucide="baby" style="width:16px;height:16px;margin-right:6px;"></i><span>คัดกรองดาวน์</span></button>
                                <button class="submenu-item" data-subtab="thal_screen"><i data-lucide="droplet" style="width:16px;height:16px;margin-right:6px;"></i><span>คัดกรองธาลัสซีเมีย</span></button>
                                <button class="submenu-item" data-subtab="cervical"><i data-lucide="activity" style="width:16px;height:16px;margin-right:6px;"></i><span>คัดกรองมะเร็งปากมดลูก</span></button>
                                <button class="submenu-item" data-subtab="psych"><i data-lucide="brain" style="width:16px;height:16px;margin-right:6px;"></i><span>จิตเวช</span></button>
                                <button class="submenu-item" data-subtab="thal_disease"><i data-lucide="droplet" style="width:16px;height:16px;margin-right:6px;"></i><span>ธาลัสซีเมีย</span></button>
                                <button class="submenu-item" data-subtab="anc"><i data-lucide="baby" style="width:16px;height:16px;margin-right:6px;"></i><span>ฝากครรภ์</span></button>
                                <button class="submenu-item" data-subtab="rehab"><i data-lucide="leaf" style="width:16px;height:16px;margin-right:6px;"></i><span>ฟื้นฟู</span></button>
                                <button class="submenu-item" data-subtab="thai_med"><i data-lucide="flower" style="width:16px;height:16px;margin-right:6px;"></i><span>แพทย์แผนไทย</span></button>
                                <button class="submenu-item" data-subtab="morprom"><i data-lucide="smartphone" style="width:16px;height:16px;margin-right:6px;"></i><span>หมอพร้อม</span></button>
                    </div>
                </div>
                <div class="menu-separator">ความช่วยเหลือ</div>
                <button class="menu-item" data-tab="how-to-use">
                    <i data-lucide="help-circle"></i>
                    <span>วิธีใช้งาน</span>
                </button>
                <button class="menu-item" data-tab="about">
                    <i data-lucide="info"></i>
                    <span>เกี่ยวกับระบบ</span>
                </button>
                <div class="menu-separator sys-admin-only">ระบบจัดการข้อมูล</div>
                <button class="menu-item highlight sys-admin-only" data-tab="google-sync">
                    <i data-lucide="cloud-lightning"></i>
                    <span>ตั้งค่าระบบ & ผู้ใช้งาน</span>
                </button>
                <button class="menu-item sys-admin-only" data-tab="log-history">
                    <i data-lucide="file-text"></i>
                    <span>ประวัติ log</span>
                </button>
            </nav>

            <!-- Sidebar Error Widget -->
            <div class="sidebar-widget">
                <div class="widget-header">
                    <i data-lucide="alert-triangle" class="text-warning"></i>
                    <h3>ข้อผิดพลาดข้อมูล 5 อันดับแรก</h3>
                </div>
                <div class="widget-subheader" id="error-tab-title">ภาพรวมทั้งหมด</div>
                <ul class="error-list" id="top-errors-list">
                    <!-- Dynamic List Items -->
                </ul>
            </div>

            <!-- PDPA Shield Footer -->
            <div class="sidebar-footer" style="display: none;">
                <div class="pdpa-badge">
                    <i data-lucide="shield-alert" class="pdpa-icon animate-pulse"></i>
                    <div class="pdpa-text">
                        <strong>สอดคล้องตาม PDPA</strong>
                        <span>แสดงผลข้อมูลสรุปยอด (Aggregated) เท่านั้น ข้อมูลส่วนบุคคลจะถูกแปลงรหัสก่อนประมวลผล</span>
                    </div>
                </div>
                <!-- Developer Credit Badge under PDPA -->
                <div class="developer-credit-badge" style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed rgba(255, 255, 255, 0.12); display: flex; gap: 8px; align-items: flex-start;">
                    <i data-lucide="user-check" style="color: #38bdf8; width: 16px; height: 16px; flex-shrink: 0; margin-top: 2px;"></i>
                    <div style="font-size: 11px; line-height: 1.4; color: #94a3b8;">
                        <strong style="display: block; color: #f8fafc; font-weight: 600;">นางสาวปุณยาพร ติตตะบุตร</strong>
                        <span style="display: block; color: #94a3b8; font-size: 10.5px;">ตำแหน่งนักสาธารณสุข</span>
                        <span style="display: block; color: #38bdf8; font-size: 10.5px; font-weight: 600;">ผู้พัฒนาระบบ</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <!-- Header Filter Bar -->
            <header class="app-header">
                <div class="filter-bar">
                    <!-- Searchable Hospital Field -->
                    <div class="filter-group hospital-filter">
                        <label for="hospital-input">รหัส/ชื่อหน่วยงานบริการ</label>
                        <div class="input-wrapper">
                            <i data-lucide="search" class="input-icon"></i>
                            <input type="text" id="hospital-input" list="hospitals-list" placeholder="ค้นหา รหัส หรือ ชื่อสถานบริการ...">
                            <datalist id="hospitals-list">
                                <option value="11120 - โรงพยาบาลแม่แจ่ม">
                            </datalist>
                        </div>
                    </div>

                    <!-- Fiscal Year -->
                    <div class="filter-group">
                        <label for="year-select">ปีงบประมาณ</label>
                        <select id="year-select">
                            <option value="all">ทุกปีงบประมาณ</option>
                            <option value="2568" selected>2568</option>
                            <option value="2567">2567</option>
                            <option value="2566">2566</option>
                        </select>
                    </div>

                    <!-- Month Range -->
                    <div class="filter-group range-group">
                        <label>ช่วงเดือน</label>
                        <div class="range-inputs">
                            <select id="start-month-select">
                                <option value="1">ต.ค. (เริ่มปีงบ)</option>
                                <option value="2">พ.ย.</option>
                                <option value="3">ธ.ค.</option>
                                <option value="4">ม.ค.</option>
                                <option value="5">ก.พ.</option>
                                <option value="6">มี.ค.</option>
                                <option value="7">เม.ย.</option>
                                <option value="8">พ.ค.</option>
                                <option value="9">มิ.ย.</option>
                                <option value="10">ก.ค.</option>
                                <option value="11">ส.ค.</option>
                                <option value="12">ก.ย.</option>
                            </select>
                            <span class="range-separator">ถึง</span>
                            <select id="end-month-select">
                                <option value="1">ต.ค.</option>
                                <option value="2">พ.ย.</option>
                                <option value="3">ธ.ค.</option>
                                <option value="4">ม.ค.</option>
                                <option value="5">ก.พ.</option>
                                <option value="6">มี.ค.</option>
                                <option value="7">เม.ย.</option>
                                <option value="8">พ.ค.</option>
                                <option value="9">มิ.ย.</option>
                                <option value="10">ก.ค.</option>
                                <option value="11">ส.ค.</option>
                                <option value="12" selected>ก.ย. (สิ้นปีงบ)</option>
                            </select>
                        </div>
                    </div>

                    <div class="filter-group" style="min-width: 280px;">
                        <label for="dashboard-file-select">ไฟล์ที่แสดงผล</label>
                        <select id="dashboard-file-select">
                            <option value="all">ทุกไฟล์ในเมนูนี้</option>
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="header-actions">
                        <div id="btn-import-wrapper">
                            <button type="button" class="btn btn-danger" id="btn-purge-data" style="margin-right: 8px;">
                                <i data-lucide="trash-2"></i>
                                <span>ลบข้อมูลเก่า</span>
                            </button>
                            <div style="min-width: 620px; max-width: 920px; width: 100%; margin-right: 8px; display:flex; flex-direction:column; gap:8px;">
                                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    <input type="text" id="upload-history-search" class="form-control" placeholder="ค้นหาชื่อไฟล์, โรงพยาบาล, ปี หรือเดือน..." style="flex:1; min-width: 260px;">
                                    <div id="upload-history-selection-summary" style="font-size:13px; color:#64748b; white-space:nowrap;">แสดง 0 รายการ</div>
                                </div>
                                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                    <button type="button" class="btn btn-danger-soft" id="upload-history-select-all" style="flex:1; min-width: 150px;">เลือกทั้งหมด</button>
                                    <button type="button" class="btn btn-danger-soft" id="upload-history-clear-selection" style="flex:1; min-width: 150px;">ล้างที่เลือก</button>
                                </div>
                                <div id="upload-history-delete-list" class="form-control" style="min-height: 260px; max-height: 360px; overflow-y:auto; padding: 12px; background:#fff; display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:10px; align-items:start;">
                                    <div style="color:#64748b; font-size:13px;">กำลังโหลดรายการไฟล์ที่เคยนำเข้า...</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-danger" id="btn-import-replace" style="margin-right: 8px;">
                                <i data-lucide="trash-2"></i>
                                <span>ลบข้อมูลไฟล์ที่เลือก</span>
                            </button>
                            <label for="excel-upload" class="btn btn-secondary" id="btn-import-label">
                                <i data-lucide="upload-cloud"></i>
                                <span>นำเข้า Excel</span>
                            </label>
                            <input type="file" id="excel-upload" accept=".xlsx, .xls, .csv" style="display: none;" multiple>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content Panels -->
            <div class="content-body">
                <section class="menu-page-header" id="menu-page-header">
                    <div class="menu-page-header-copy">
                        <span class="menu-page-eyebrow" id="menu-page-eyebrow">Smart Money</span>
                        <h2 id="menu-page-title">ภาพรวมระบบ</h2>
                        <p id="menu-page-subtitle">แสดงภาพรวมการเงิน, การนำเข้าไฟล์ และรายการที่เกี่ยวข้องของเมนูที่เลือก</p>
                    </div>
                    <div class="menu-page-header-badge" id="menu-page-badge">พร้อมใช้งาน</div>
                </section>
                
                <!-- Main Dashboard Panel -->
                <div id="dashboard-tab-content" class="tab-pane active">
                    <!-- Summary Cards -->
                    <section class="summary-cards" id="standard-kpis">
                        <!-- Card 1 -->
                        <div class="kpi-card blue">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชยขอเบิกทั้งหมด</span>
                                <h2 class="card-value" id="kpi-total">฿0.00</h2>
                                <span class="card-trend text-blue" id="kpi-total-sub">ปีงบประมาณที่เลือก</span>
                            </div>
                            <div class="card-icon-wrapper">
                                <i data-lucide="wallet"></i>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="kpi-card green">
                            <div class="card-info">
                                <span class="card-label">ยอดอนุมัติจ่ายชดเชยจริง</span>
                                <h2 class="card-value" id="kpi-approved">฿0.00</h2>
                                <span class="card-trend text-success" id="kpi-approved-sub">ผ่านเกณฑ์ตรวจสอบ</span>
                            </div>
                            <div class="card-icon-wrapper">
                                <i data-lucide="badge-check"></i>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="kpi-card yellow">
                            <div class="card-info">
                                <span class="card-label">ยอดติด C / รอการชดเชย</span>
                                <h2 class="card-value" id="kpi-pending">฿0.00</h2>
                                <span class="card-trend text-warning" id="kpi-pending-sub">มีข้อผิดพลาดข้อมูล</span>
                            </div>
                            <div class="card-icon-wrapper">
                                <i data-lucide="alert-circle"></i>
                            </div>
                        </div>

                        <!-- Card 4 -->
                        <div class="kpi-card purple">
                            <div class="card-info">
                                <span class="card-label">อัตราการอนุมัติชดเชย</span>
                                <h2 class="card-value" id="kpi-rate">0.0%</h2>
                                <span class="card-trend text-purple" id="kpi-rate-sub">ของคำขอรับชดเชยทั้งหมด</span>
                            </div>
                            <div class="card-icon-wrapper">
                                <i data-lucide="percent"></i>
                            </div>
                        </div>
                    </section>

                    <!-- Summary Cards for PPFS Sub-districts (รพ.สต.) -->
                    <section class="summary-cards" id="ppfs-sub-kpis" style="display: none; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                        <!-- KPI Card Total Approved -->
                        <div class="kpi-card blue">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชยรวม (รายรับรวม)</span>
                                <h2 class="card-value" id="sub-kpi-total" style="font-size: 20px;">฿0.00</h2>
                                <span class="card-trend text-blue">ยอดการอนุมัติจ่ายจริง</span>
                            </div>
                            <div class="card-icon-wrapper">
                                <i data-lucide="wallet"></i>
                            </div>
                        </div>
                        
                        <!-- Dynamic sub-category cards (7 cards) -->
                        <div class="kpi-card green" style="padding:16px 20px;">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชย OP</span>
                                <h4 class="card-value" id="sub-kpi-op" style="font-size:18px;">฿0.00</h4>
                                <span class="card-trend text-success">ผู้ป่วยนอก</span>
                            </div>
                        </div>
                        <div class="kpi-card green" style="padding:16px 20px;">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชย HC</span>
                                <h4 class="card-value" id="sub-kpi-hc" style="font-size:18px;">฿0.00</h4>
                                <span class="card-trend text-success">Home Care</span>
                            </div>
                        </div>
                        <div class="kpi-card green" style="padding:16px 20px;">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชย HC DRUG</span>
                                <h4 class="card-value" id="sub-kpi-hc-drug" style="font-size:18px;">฿0.00</h4>
                                <span class="card-trend text-success">ยา Home Care</span>
                            </div>
                        </div>
                        <div class="kpi-card yellow" style="padding:16px 20px;">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชย AE</span>
                                <h4 class="card-value" id="sub-kpi-ae" style="font-size:18px;">฿0.00</h4>
                                <span class="card-trend text-warning">ฉุกเฉิน / อุบัติเหตุ</span>
                            </div>
                        </div>
                        <div class="kpi-card yellow" style="padding:16px 20px;">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชย AE DRUG</span>
                                <h4 class="card-value" id="sub-kpi-ae-drug" style="font-size:18px;">฿0.00</h4>
                                <span class="card-trend text-warning">ยาฉุกเฉิน / อุบัติเหตุ</span>
                            </div>
                        </div>
                        <div class="kpi-card purple" style="padding:16px 20px;">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชย PP</span>
                                <h4 class="card-value" id="sub-kpi-pp" style="font-size:18px;">฿0.00</h4>
                                <span class="card-trend text-purple">ส่งเสริมป้องกัน</span>
                            </div>
                        </div>
                        <div class="kpi-card purple" style="padding:16px 20px;">
                            <div class="card-info">
                                <span class="card-label">ยอดชดเชย FS</span>
                                <h4 class="card-value" id="sub-kpi-fs" style="font-size:18px;">฿0.00</h4>
                                <span class="card-trend text-purple">Fee Schedule</span>
                            </div>
                        </div>
                    </section>

                    <!-- Charts Grid -->
                    <section class="charts-grid">
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3 id="trend-chart-title">แนวโน้มสถานะทางการเงินชดเชยแยกรายปีงบประมาณ</h3>
                                <span class="chart-sub" id="trend-chart-subtitle">ข้อมูลเปรียบเทียบปีงบประมาณ 2566 - 2568</span>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>

                        <!-- Compare Chart -->
                        <div class="chart-container" id="compare-chart-card">
                            <div class="chart-header">
                                <h3 id="compare-chart-title">เปรียบเทียบยอดการจ่ายชดเชยแยกรายเดือน</h3>
                                <span class="chart-sub" id="compare-chart-subtitle">สัดส่วนงบชดเชยที่อนุมัติจริงรายกองทุน</span>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="compareChart"></canvas>
                            </div>
                        </div>

                        <!-- Category Grid Overview (New) -->
                        <div class="chart-container" id="category-grid-card" style="display: none; padding: 1.5rem; background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);">
                            <div class="chart-header" style="margin-bottom: 1.5rem;">
                                <h3>สรุปข้อมูลรายหมวดหมู่ (Overview)</h3>
                                <span class="chart-sub">ยอดเงินชดเชยรวมแยกตามหมวดหมู่</span>
                            </div>
                            <div id="category-grid-container" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; overflow-y: auto; max-height: 280px; padding-right: 8px;">
                                <!-- Populated by JS -->
                            </div>
                        </div>



                    </section>

                    <!-- Seamless Focus Mode Container -->
                    <section id="seamless-focus-mode-container" style="display: none; margin-bottom: 24px;">
                        <div id="seamless-current-subtab-banner" style="display: none; margin-bottom: 20px; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; border-radius: 14px; background: linear-gradient(135deg, #eff6ff 0%, #ecfeff 100%); border: 1px solid rgba(59, 130, 246, 0.18); box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <span style="font-size: 12px; color: #64748b; font-weight: 600;">Seamless sub menu ที่กำลังแสดง</span>
                                <strong id="seamless-current-subtab-name" style="font-size: 18px; color: #0f172a;">ทั้งหมด</strong>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px; color: #2563eb; font-weight: 600;">
                                <i data-lucide="sparkles" style="width: 18px; height: 18px;"></i>
                                <span id="seamless-current-subtab-count">0 รายการ</span>
                            </div>
                        </div>
                        
                        <!-- Row 1: Yearly Overview -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                            <!-- Yearly Chart -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h3 id="focus-yearly-chart-title">ภาพรวมรายปีงบประมาณ</h3>
                                    <span class="chart-sub">ยอดเงินชดเชยรวมแยกตามปี</span>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="focusYearlyChart"></canvas>
                                </div>
                            </div>
                            <!-- Yearly Table -->
                            <div class="table-container" style="margin-bottom: 0;">
                                <div class="table-header">
                                    <div class="table-title-group">
                                        <h3>ตารางสรุปรายปี</h3>
                                    </div>
                                    <div class="export-buttons" style="display: flex; gap: 4px;">
                                        <button class="btn btn-export-excel" style="background-color: #5cb85c; color: white; border: 1px solid #4cae4c; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                            <i data-lucide="download" style="width: 16px; height: 16px;"></i> Excel
                                        </button>
                                        <button class="btn btn-export-csv" style="background-color: #5bc0de; color: white; border: 1px solid #46b8da; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                            <i data-lucide="file-text" style="width: 16px; height: 16px;"></i> CSV
                                        </button>
                                        <button class="btn btn-export-print" style="background-color: #fff; color: #333; border: 1px solid #ccc; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                            <i data-lucide="printer" style="width: 16px; height: 16px;"></i> Print
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="nhso-table">
                                        <thead>
                                            <tr>
                                                <th>ปีงบประมาณ</th>
                                                <th style="text-align: right;">ครั้ง</th>
                                                <th style="text-align: right;">ชดเชย (บาท)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="focus-yearly-tbody">
                                        </tbody>
                                        <tfoot>
                                            <tr style="background-color: #f8fafc; font-weight: 600;">
                                                <td>รวม</td>
                                                <td id="focus-yearly-total-count" style="text-align: right;">0</td>
                                                <td id="focus-yearly-total-amt" style="text-align: right; color: #0ea5e9;">0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Monthly Overview -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                            <!-- Monthly Chart -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h3 id="focus-monthly-chart-title">ภาพรวมรายเดือน</h3>
                                    <span class="chart-sub">ยอดเงินชดเชยแยกตามเดือนที่สั่ง</span>
                                </div>
                                <div class="chart-wrapper">
                                    <canvas id="focusMonthlyChart"></canvas>
                                </div>
                            </div>
                            <!-- Monthly Table -->
                            <div class="table-container" style="margin-bottom: 0;">
                                <div class="table-header">
                                    <div class="table-title-group">
                                        <h3>ตารางสรุปรายเดือน</h3>
                                    </div>
                                    <div class="export-buttons" style="display: flex; gap: 4px;">
                                        <button class="btn btn-export-excel" style="background-color: #5cb85c; color: white; border: 1px solid #4cae4c; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                            <i data-lucide="download" style="width: 16px; height: 16px;"></i> Excel
                                        </button>
                                        <button class="btn btn-export-csv" style="background-color: #5bc0de; color: white; border: 1px solid #46b8da; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                            <i data-lucide="file-text" style="width: 16px; height: 16px;"></i> CSV
                                        </button>
                                        <button class="btn btn-export-print" style="background-color: #fff; color: #333; border: 1px solid #ccc; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                            <i data-lucide="printer" style="width: 16px; height: 16px;"></i> Print
                                        </button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="nhso-table">
                                        <thead>
                                            <tr>
                                                <th>เดือนที่ส่ง</th>
                                                <th style="text-align: right;">ครั้ง</th>
                                                <th style="text-align: right;">ชดเชย (บาท)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="focus-monthly-tbody">
                                        </tbody>
                                        <tfoot>
                                            <tr style="background-color: #f8fafc; font-weight: 600;">
                                                <td>รวม</td>
                                                <td id="focus-monthly-total-count" style="text-align: right;">0</td>
                                                <td id="focus-monthly-total-amt" style="text-align: right; color: #0ea5e9;">0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Row 3: Hospital Breakdown Table -->
                        <div class="table-container">
                            <div class="table-header">
                                <div class="table-title-group">
                                    <h3 id="focus-hospital-table-title">แยกรายหน่วยบริการในเครือข่าย</h3>
                                </div>
                                <div class="export-buttons" style="display: flex; gap: 4px;">
                                    <button class="btn btn-export-excel" style="background-color: #5cb85c; color: white; border: 1px solid #4cae4c; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="download" style="width: 16px; height: 16px;"></i> Excel
                                    </button>
                                    <button class="btn btn-export-csv" style="background-color: #5bc0de; color: white; border: 1px solid #46b8da; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="file-text" style="width: 16px; height: 16px;"></i> CSV
                                    </button>
                                    <button class="btn btn-export-print" style="background-color: #fff; color: #333; border: 1px solid #ccc; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="printer" style="width: 16px; height: 16px;"></i> Print
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="nhso-table">
                                    <thead>
                                        <tr>
                                            <th>รหัสหน่วย</th>
                                            <th>ชื่อหน่วยบริการ</th>
<th style="text-align: right;">เรียกเก็บ</th>
                                            <th style="text-align: right;">ครั้ง</th>
                                            <th style="text-align: right;">ชดเชย (บาท)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="focus-hospital-tbody">
                                    </tbody>
                                    <tfoot>
                                        <tr style="background-color: #f8fafc; font-weight: 600;">
                                            <td colspan="2">รวม</td>
                                            <td id="focus-hosp-total-req" style="text-align: right;">0.00</td>
                                            <td id="focus-hosp-total-count" style="text-align: right;">0</td>
                                            <td id="focus-hosp-total-amt" style="text-align: right; color: #0ea5e9;">0.00</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </section>

                    <!-- Table Data Section -->
                    <section class="table-container">
                        <div class="table-header">
                            <div class="table-title-group" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                <div>
                                    <h3 id="table-fund-title">รายการเงินชดเชยแยกรายกองทุน</h3>
                                    <span class="table-subtitle">ข้อมูลอัปเดตแบบเรียลไทม์จากการประมวลผล</span>
                                </div>
                                <div class="export-buttons" style="display: flex; gap: 4px;">
                                    <button class="btn btn-export-excel" style="background-color: #5cb85c; color: white; border: 1px solid #4cae4c; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                                        <span>Excel</span>
                                    </button>
                                    <button class="btn btn-export-csv" style="background-color: #5bc0de; color: white; border: 1px solid #46b8da; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="file-text" style="width: 16px; height: 16px;"></i>
                                        <span>CSV</span>
                                    </button>
                                    <button class="btn btn-print" style="background-color: #ffffff; color: #333; border: 1px solid #ccc; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="printer" style="width: 16px; height: 16px;"></i>
                                        <span>Print</span>
                                    </button>
                                </div>
                            </div>
                            <div class="table-actions">
                                <div class="table-search">
                                    <i data-lucide="search"></i>
                                    <input type="text" id="table-search-input" placeholder="ค้นหาข้อมูลในตาราง...">
                                </div>
                            </div>
                        </div>
                        <div class="table-wrapper">
                            <table class="data-table" id="compensation-table">
                                <thead>
                                    <!-- Dynamic headers will be injected -->
                                </thead>
                                <tbody>
                                    <!-- Dynamic rows will be injected -->
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- Summary Table Section -->
                    <section class="table-container" style="margin-top: 1.5rem;">
                        <div class="table-header">
                            <div class="table-title-group" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                <div>
                                    <h3>รายรับ แยกรายกองทุนย่อย</h3>
                                    <span class="table-subtitle">สรุปยอดรวมแต่ละกองทุนย่อย (แสดงควบคู่ตารางด้านบน)</span>
                                </div>
                                <div class="export-buttons" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                    <div id="batch-select-wrapper" style="display: none; align-items: center; gap: 6px; background: #f8fafc; padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1;">
                                        <label for="batch-period-select" style="font-size: 13px; font-weight: 600; color: #334155; white-space: nowrap;">เลขที่งวดงาน:</label>
                                        <select id="batch-period-select" style="background: white; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 8px; font-size: 13px; font-weight: 500; color: #0f172a; cursor: pointer;">
                                            <option value="all">ทั้งหมด (ทุกงวดงาน)</option>
                                        </select>
                                    </div>
                                    <button class="btn btn-export-excel" style="background-color: #5cb85c; color: white; border: 1px solid #4cae4c; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                                        <span>Excel</span>
                                    </button>
                                    <button class="btn btn-export-csv" style="background-color: #5bc0de; color: white; border: 1px solid #46b8da; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="file-text" style="width: 16px; height: 16px;"></i>
                                        <span>CSV</span>
                                    </button>
                                    <button class="btn btn-print" style="background-color: #ffffff; color: #333; border: 1px solid #ccc; padding: 6px 12px; border-radius: 4px; display: flex; align-items: center; gap: 4px; font-weight: 500; font-size: 13px;">
                                        <i data-lucide="printer" style="width: 16px; height: 16px;"></i>
                                        <span>Print</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-wrapper">
                            <table class="data-table" id="summary-table">
                                <thead>
                                    <tr>
                                        <th>รหัส</th>
                                        <th>ชื่อกองทุนหลัก</th>
                                        <th>รหัสกองทุนย่อย</th>
                                        <th>ชื่อกองทุนย่อย</th>
                                        <th style="text-align: right;">จำนวนเงิน (฿)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Dynamic summary rows -->
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <!-- OP/IP Detailed Tables -->
                    <section id="op-ip-details-section" class="charts-container" style="display: none; margin-top: 1.5rem; gap: 1.5rem;">
                        <!-- Left: Top 5 Errors -->
                        <div class="chart-card" style="flex: 1; min-height: unset; overflow-x: auto;">
                            <div class="table-header">
                                <div class="table-title-group">
                                    <h3 id="error-table-title">จำนวนข้อมูลแยกตามประเภทความผิดพลาด 5 ลำดับแรก</h3>
                                </div>
                            </div>
                            <div class="table-wrapper">
                                <table class="data-table" id="error-details-table">
                                    <thead>
                                        <tr>
                                            <th>CODE</th>
                                            <th>รายการ</th>
                                            <th style="text-align: right;">จำนวน</th>
                                            <th style="text-align: right;">แก้ไขแล้ว</th>
                                            <th style="text-align: right;">คงเหลือ</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Right: Monthly Submission -->
                        <div class="chart-card" style="flex: 1; min-height: unset; overflow-x: auto;">
                            <div class="table-header">
                                <div class="table-title-group">
                                    <h3 id="submission-table-title">การส่งข้อมูลสิทธิ UC</h3>
                                </div>
                            </div>
                            <div class="table-wrapper">
                                <table class="data-table" id="submission-details-table">
                                    <thead>
                                        <tr>
                                            <th>เดือน</th>
                                            <th style="text-align: right;">ทั้งหมด</th>
                                            <th style="text-align: right;">ผ่าน</th>
                                            <th style="text-align: right;">ไม่ผ่าน</th>
                                            <th style="text-align: right;">ร้อยละไม่ผ่าน</th>
                                            <th style="text-align: right;">C ยังไม่แก้ไข</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- Inpatient CMI-AdjRW Performance Chart (Bottom-most) -->
                    <div class="chart-container" id="ip-cmi-chart-card" style="display: none; margin-top: 1.5rem; width: 100%; box-sizing: border-box;">
                        <div class="chart-header" style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <h3>ศักยภาพการบริการผู้ป่วยใน แยกรายปี (CMI-AdjRW) (ตามวันจำหน่าย)</h3>
                                <span class="chart-sub">ค่าเฉลี่ย AdjRW ของผู้ป่วยในจำแนกรายปีงบประมาณ</span>
                            </div>
                            <div>
                                <button class="btn btn-import" id="btn-import-cmi-excel" style="background-color: #0284c7; color: white; border: none; padding: 8px 16px; border-radius: 6px; display: flex; align-items: center; gap: 6px; font-weight: 500; font-size: 13px; cursor: pointer; transition: all 0.2s ease;">
                                    <i data-lucide="upload" style="width: 16px; height: 16px;"></i>
                                    <span>นำเข้าไฟล์ Excel</span>
                                </button>
                            </div>
                        </div>
                        <div class="chart-wrapper" style="height: 320px; margin-top: 1.5rem;">
                            <canvas id="ipCmiChart"></canvas>
                        </div>
                        
                        <!-- CMI Summary & Comparison Table -->
                        <div id="ip-cmi-table-wrapper" style="margin-top: 1.5rem; width: 100%; overflow-x: auto;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <h4 style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="table" style="width: 16px; height: 16px; color: #425847;"></i>
                                    <span>ตารางเปรียบเทียบค่า CMI แยกตามปีงบประมาณ</span>
                                </h4>
                            </div>
                            <table class="table-summary" id="ip-cmi-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <thead>
                                    <tr style="background-color: #f1f5f9; color: #334155; text-align: center;">
                                        <th style="padding: 10px 12px; border: 1px solid #cbd5e1; font-weight: 600;">ปีงบประมาณ</th>
                                        <th style="padding: 10px 12px; border: 1px solid #cbd5e1; font-weight: 600;">จำนวนผู้ป่วยใน (ราย)</th>
                                        <th style="padding: 10px 12px; border: 1px solid #cbd5e1; font-weight: 600;">ผลรวม AdjRW</th>
                                        <th style="padding: 10px 12px; border: 1px solid #cbd5e1; font-weight: 600; background-color: #e2e8f0;">ค่า CMI (AdjRW)</th>
                                        <th style="padding: 10px 12px; border: 1px solid #cbd5e1; font-weight: 600;">อัตราการเปลี่ยนแปลง (%)</th>
                                    </tr>
                                </thead>
                                <tbody id="ip-cmi-tbody">
                                </tbody>
                            </table>
                        </div>

                        <!-- IP Submission Delay Summary Table Card (IP01/IP02) -->
                        <div id="ip-submission-delay-card" style="margin-top: 1.5rem; width: 100%; box-sizing: border-box; background: white; border-radius: 12px; padding: 1.25rem; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; flex-wrap: wrap; gap: 12px; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                        <span>ระยะเวลาการส่งข้อมูลผู้ป่วยในแยกรายเดือนที่ส่งข้อมูล (IP01/IP02)</span>
                                    </h3>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <button class="btn btn-import" id="btn-import-ip-delay-excel" style="background-color: #ffffff; color: #0284c7; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; display: flex; align-items: center; gap: 6px; font-weight: 500; font-size: 12px; cursor: pointer; transition: all 0.2s ease;">
                                        <i data-lucide="file-text" style="width: 14px; height: 14px;"></i>
                                        <span>นำเข้าไฟล์ Excel</span>
                                    </button>
                                    <input type="file" id="ip-delay-file-input" accept=".xlsx, .xls, .csv" style="display: none;">
                                </div>
                            </div>
                            <div style="width: 100%; overflow-x: auto;">
                                <table class="table-summary" id="ip-delay-table" style="width: 100%; border-collapse: collapse; font-size: 12px;">
                                    <thead>
                                        <tr style="background-color: #f8fafc; color: #334155; text-align: center;">
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">เดือนที่ส่ง</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">ภายใน30วัน(ครั้ง)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">ภายใน30วัน(AdjRW)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">31-60วัน(ครั้ง)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">31-60วัน(AdjRW)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">61-90วัน(ครั้ง)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">61-90วัน(AdjRW)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">มากกว่า90วัน(ครั้ง)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600;">มากกว่า90วัน(AdjRW)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600; background-color: #f1f5f9;">รวม (ครั้ง)</th>
                                            <th style="padding: 8px 6px; border: 1px solid #cbd5e1; font-weight: 600; background-color: #f1f5f9;">รวม (AdjRW)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ip-delay-tbody">
                                    </tbody>
                                    <tfoot id="ip-delay-tfoot" style="background-color: #f8fafc; font-weight: 700; text-align: center; border-top: 2px solid #cbd5e1;">
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- PPFS Redesigned Custom Dashboard Container -->
                    <div id="ppfs-custom-container" style="display: none; flex-direction: column; gap: 1.5rem; width: 100%; box-sizing: border-box;">
                        
                        <!-- Header Title -->
                        <div class="table-header" style="padding: 0; background: transparent; border: none; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <h2 style="font-size: 1.8rem; font-weight: 700; color: #1e293b; margin: 0;">รายงานผลประกอบการส่งเสริมสุขภาพและป้องกันโรค (PPFS)</h2>
                            </div>
                            <div class="custom-dropdown-container">
                                <select class="form-control" style="background-color: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; font-size: 13px; font-weight: 500; color: #334155; cursor: pointer;">
                                    <option>อัพเดทข้อมูลในตาราง...</option>
                                </select>
                            </div>
                        </div>

                        <!-- PPFS KPI Cards -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; width: 100%;">
                            
                            <!-- Card 1: Cumulative Revenue -->
                            <div class="kpi-card blue" style="position: relative; overflow: hidden; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-radius: 12px; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                                <div class="card-info" style="display: flex; flex-direction: column; gap: 4px;">
                                    <span class="card-label" style="font-size: 13px; color: #64748b; font-weight: 500;">ยอดชดเชยสะสม PPFS (ปี 2569)</span>
                                    <h2 class="card-value" id="ppfs-kpi-revenue" style="font-size: 26px; font-weight: 700; color: #0f172a; margin: 0;">฿3,150,000</h2>
                                    <div style="margin-top: 4px;">
                                        <span class="card-trend text-blue" style="background-color: #e0f2fe; color: #0284c7; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">เพิ่มขึ้น 18%</span>
                                    </div>
                                </div>
                                <div class="card-icon-wrapper" style="font-size: 1.8rem; color: #0284c7; opacity: 0.85;">
                                    <i data-lucide="wallet" style="width: 32px; height: 32px;"></i>
                                </div>
                            </div>

                            <!-- Card 2: Service Target Achieved -->
                            <div class="kpi-card green" style="position: relative; overflow: hidden; padding: 1.25rem 1.5rem; display: flex; border-radius: 12px; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); gap: 1rem; align-items: center; border: 1px solid #e2e8f0;">
                                <div class="card-info" style="display: flex; flex-direction: column; gap: 4px; flex: 1;">
                                    <span class="card-label" style="font-size: 13px; color: #64748b; font-weight: 500;">อัตราการให้บริการตามเป้าหมาย</span>
                                    <div style="height: 60px; width: 100%; position: relative; display: flex; align-items: center; justify-content: center; margin-top: 5px;">
                                        <canvas id="ppfsGaugeChart" style="width: 100% !important; height: 60px !important;"></canvas>
                                        <div style="position: absolute; bottom: 2px; font-size: 15px; font-weight: 700; color: #0f172a;" id="ppfs-gauge-value">85.5%</div>
                                    </div>
                                </div>
                                <div class="card-icon-wrapper" style="font-size: 1.8rem; color: #10b981; opacity: 0.85;">
                                    <i data-lucide="gauge" style="width: 28px; height: 28px;"></i>
                                </div>
                            </div>

                            <!-- Card 3: Main Revenue Source -->
                            <div class="kpi-card yellow" style="position: relative; overflow: hidden; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; border-radius: 12px; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                                <div class="card-info" style="display: flex; flex-direction: column; gap: 2px;">
                                    <span class="card-label" style="font-size: 13px; color: #64748b; font-weight: 500;">กองทุนสร้างรายได้หลัก</span>
                                    <span style="font-size: 12px; color: #f59e0b; font-weight: 600; margin-bottom: 2px;" id="ppfs-kpi-main-source-label">บริการฝากครรภ์</span>
                                    <h2 class="card-value" id="ppfs-kpi-main-source-value" style="font-size: 22px; font-weight: 700; color: #0f172a; margin: 0;">฿1,250,352</h2>
                                </div>
                                <div class="card-icon-wrapper" style="font-size: 1.8rem; color: #f59e0b; opacity: 0.85;">
                                    <i data-lucide="info" style="width: 28px; height: 28px;"></i>
                                </div>
                            </div>

                            <!-- Card 4: C / Error Rate -->
                            <div class="kpi-card purple" style="position: relative; overflow: hidden; padding: 1.25rem 1.5rem; display: flex; border-radius: 12px; background: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); align-items: center; gap: 10px; border: 1px solid #e2e8f0;">
                                <div style="width: 55px; height: 55px; position: relative; display: flex; align-items: center; justify-content: center;">
                                    <canvas id="ppfsCChart" style="width: 55px !important; height: 55px !important;"></canvas>
                                    <div style="position: absolute; font-size: 10px; font-weight: 700; color: #0f172a;" id="ppfs-c-percent">1.3%</div>
                                </div>
                                <div class="card-info" style="display: flex; flex-direction: column; gap: 2px; flex: 1;">
                                    <span class="card-label" style="font-size: 13px; color: #64748b; font-weight: 500;">อัตราติด C / Error</span>
                                    <h2 class="card-value" id="ppfs-kpi-error-value" style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">฿25,000</h2>
                                    <span style="font-size: 11px; color: #94a3b8;" id="ppfs-kpi-error-count">จำนวนครั้ง: 18</span>
                                </div>
                                <div class="card-icon-wrapper" style="font-size: 1.8rem; color: #8b5cf6; opacity: 0.85; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px;">
                                    <span style="font-weight: 700; font-size: 20px;">%</span>
                                </div>
                            </div>

                        </div>

                        <!-- PPFS Charts Grid -->
                        <div class="charts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px;">
                            
                            <!-- Left Chart: Yearly Income Trend -->
                            <div class="chart-container" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                                <div class="chart-header">
                                    <h3>แนวโน้มรายได้ PPFS แยกตามปีงบประมาณ</h3>
                                    <span class="chart-sub">รายได้: ล้านบาท</span>
                                </div>
                                <div class="chart-wrapper" style="height: 300px;">
                                    <canvas id="ppfsYearlyChart"></canvas>
                                </div>
                            </div>

                            <!-- Right Chart: Category Share -->
                            <div class="chart-container" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e2e8f0;">
                                <div class="chart-header">
                                    <h3>สัดส่วนรายได้ตามกลุ่มบริการหลัก (Top 5)</h3>
                                    <span class="chart-sub">สัดส่วนตามการจ่ายชดเชยที่อนุมัติจริง</span>
                                </div>
                                <div class="chart-wrapper" style="height: 300px;">
                                    <canvas id="ppfsProportionChart"></canvas>
                                </div>
                            </div>

                        </div>



                        <!-- PPFS Collapsible Tree Table Card -->
                        <div class="table-container" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); margin-bottom: 0; border: 1px solid #e2e8f0;">
                            <div class="table-responsive">
                                <table class="nhso-table" id="ppfs-tree-table" style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                                            <th style="text-align: left; padding: 12px 16px; font-weight: 600; color: #475569;">กลุ่ม/รายการบริการ <span style="font-size: 10px;">▲</span></th>
                                            <th style="text-align: left; padding: 12px 16px; font-weight: 600; color: #475569;">ITEM_NAME</th>
                                            <th style="text-align: right; padding: 12px 16px; font-weight: 600; color: #475569;">จำนวน(ครั้ง)</th>
                                            <th style="text-align: right; padding: 12px 16px; font-weight: 600; color: #475569;">รายได้รวม (บาท)</th>
                                            <th style="text-align: center; padding: 12px 16px; font-weight: 600; color: #475569; width: 100px;">สถานะ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ppfs-tree-tbody">
                                        <!-- Populated dynamically by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- How To Use Tab Content -->
                <div id="how-to-use-tab-content" class="tab-pane">
                    <div class="sync-info-card">
                        <div class="sync-card-header">
                            <div class="sync-badge" style="background-color:rgba(14, 165, 233, 0.08); color:var(--blue-primary);">
                                <i data-lucide="book-open"></i>
                                <span>How To Use (วิธีใช้งาน)</span>
                            </div>
                            <h2>คู่มือการใช้งานระบบ NHSO Executive Dashboard</h2>
                        </div>
                        <div style="padding: 24px; color: #334155; line-height: 1.6;">
                            
                            <p style="margin-bottom: 24px;">ระบบนี้แบ่งการทำงานออกเป็นหมวดหมู่ตามแถบเมนูด้านซ้าย คุณสามารถดูวิธีการใช้งานของแต่ละส่วนได้ดังนี้:</p>

                            <!-- 1. ตัวกรองข้อมูล -->
                            <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #fff;">
                                <h3 style="display: flex; align-items: center; gap: 8px; color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 16px;">
                                    <i data-lucide="filter" style="width: 18px; height: 18px; color: #64748b;"></i> 
                                    การใช้งานตัวกรอง (แถบด้านบน)
                                </h3>
                                <ul style="margin-top: 0; padding-left: 24px; margin-bottom: 0;">
                                    <li style="margin-bottom: 8px;"><b>รหัส/ชื่อหน่วยงาน:</b> พิมพ์ค้นหาเพื่อดูข้อมูลเฉพาะหน่วยบริการนั้นๆ</li>
                                    <li style="margin-bottom: 8px;"><b>ปีงบประมาณ & ช่วงเดือน:</b> เลือกช่วงเวลาที่ต้องการให้ระบบสรุปข้อมูล</li>
                                    <li>ตัวกรองนี้จะมีผลกับทุกๆ เมนูที่คุณเปิดดู</li>
                                </ul>
                            </div>

                            <!-- 2. Smart Money -->
                            <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #fff;">
                                <h3 style="display: flex; align-items: center; gap: 8px; color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 16px;">
                                    <i data-lucide="line-chart" style="width: 18px; height: 18px; color: var(--blue-primary);"></i> 
                                    เมนู Smart Money (ภาพรวมทั้งหมด)
                                </h3>
                                <ul style="margin-top: 0; padding-left: 24px; margin-bottom: 0;">
                                    <li style="margin-bottom: 8px;">แสดง <b>ภาพรวมการเงินทุกกองทุน</b> รวมกัน</li>
                                    <li style="margin-bottom: 8px;">ดูยอดชดเชยที่ขอเบิก, ยอดอนุมัติจ่ายจริง, ยอดติด C และอัตราส่วนความสำเร็จ (ร้อยละการอนุมัติ)</li>
                                    <li>กราฟเปรียบเทียบแนวโน้มรายปีงบประมาณ และแนวโน้มรายเดือน เพื่อวิเคราะห์ทิศทางรายรับของหน่วยบริการ</li>
                                </ul>
                            </div>

                            <!-- 3. OP -->
                            <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #fff;">
                                <h3 style="display: flex; align-items: center; gap: 8px; color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 16px;">
                                    <i data-lucide="users" style="width: 18px; height: 18px; color: var(--success-primary);"></i> 
                                    เมนู ผู้ป่วยนอก (OP)
                                </h3>
                                <ul style="margin-top: 0; padding-left: 24px; margin-bottom: 0;">
                                    <li style="margin-bottom: 8px;">เจาะลึกเฉพาะข้อมูลการเบิกจ่าย <b>กองทุนผู้ป่วยนอก (OP)</b> และสาขาย่อยที่เกี่ยวข้อง</li>
                                    <li>กราฟและตารางจะกรองเฉพาะยอดเงินชดเชยของผู้ป่วยนอก เพื่อตรวจสอบรายการที่ติด C หรือถูกปฏิเสธการจ่ายในหมวดหมู่นี้</li>
                                </ul>
                            </div>

                            <!-- 4. IP -->
                            <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #fff;">
                                <h3 style="display: flex; align-items: center; gap: 8px; color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 16px;">
                                    <i data-lucide="bed-double" style="width: 18px; height: 18px; color: var(--purple-primary);"></i> 
                                    เมนู ผู้ป่วยใน (IP)
                                </h3>
                                <ul style="margin-top: 0; padding-left: 24px; margin-bottom: 0;">
                                    <li style="margin-bottom: 8px;">แสดงข้อมูลการเบิกจ่าย <b>กองทุนผู้ป่วยใน (IP)</b></li>
                                    <li><b>ไฮไลต์:</b> มีกราฟแสดงดัชนี <b>Case Mix Index (CMI)</b> เพื่อดูแนวโน้มความซับซ้อนของโรคผู้ป่วยในที่ส่งผลต่อน้ำหนักสัมพัทธ์ (AdjRW) ของหน่วยบริการในแต่ละปีงบประมาณ</li>
                                </ul>
                            </div>

                            <!-- 5. PPFS -->
                            <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #fff;">
                                <h3 style="display: flex; align-items: center; gap: 8px; color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 16px;">
                                    <i data-lucide="shield-check" style="width: 18px; height: 18px; color: var(--warning);"></i> 
                                    เมนู ส่งเสริมป้องกัน (PPFS) และ PPFS รพสต.
                                </h3>
                                <ul style="margin-top: 0; padding-left: 24px; margin-bottom: 0;">
                                    <li style="margin-bottom: 8px;"><b>PPFS:</b> ข้อมูลงานส่งเสริมสุขภาพและป้องกันโรคในภาพรวมของเครือข่าย</li>
                                    <li><b>PPFS รพสต.:</b> เจาะจงเฉพาะระดับหน่วยบริการปฐมภูมิ (รพ.สต.) โดยจะมีการแยกหมวดหมู่ย่อย 7 หมวด (เช่น OP, HC, AE, PP, FS) ให้เห็นชัดเจนผ่านหน้าปัดเข็มทิศ (Gauge) และกราฟสัดส่วน</li>
                                </ul>
                            </div>

                            <!-- 6. Seamless -->
                            <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #fff;">
                                <h3 style="display: flex; align-items: center; gap: 8px; color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 16px;">
                                    <i data-lucide="git-pull-request" style="width: 18px; height: 18px; color: #ec4899;"></i> 
                                    เมนู จ่ายชดเชย Seamless
                                </h3>
                                <ul style="margin-top: 0; padding-left: 24px; margin-bottom: 0;">
                                    <li style="margin-bottom: 8px;">กดที่ <b>ลูกศรชี้ลง</b> ข้างเมนู Seamless เพื่อกางหมวดย่อย (เช่น CKD, KTP, ฝากครรภ์, หมอพร้อม ฯลฯ)</li>
                                    <li>เมื่อคลิกที่หมวดย่อย ระบบจะเข้าสู่โหมด <b>Focus Mode</b> เพื่อแสดงตารางสรุปรายปี รายเดือน และแยกตามหน่วยบริการเฉพาะของกองทุนย่อยนั้นๆ อย่างละเอียด</li>
                                </ul>
                            </div>

                            <!-- 7. Data Errors -->
                            <div style="margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #fff;">
                                <h3 style="display: flex; align-items: center; gap: 8px; color: #0f172a; margin-top: 0; margin-bottom: 12px; font-size: 16px;">
                                    <i data-lucide="alert-triangle" style="width: 18px; height: 18px; color: #ef4444;"></i> 
                                    แถบ Top 5 Data Errors (ด้านซ้ายล่าง)
                                </h3>
                                <p style="margin-top: 0; margin-bottom: 8px; padding-left: 24px;">ระบบจะดึงข้อผิดพลาดรหัส C (หรือความคลาดเคลื่อนข้อมูล) ที่เกิดขึ้นบ่อยที่สุด 5 อันดับแรก มาแสดงผลตามกองทุนที่คุณกำลังเลือกดู เพื่อให้เจ้าหน้าที่สามารถนำไปปรับปรุงการคีย์ข้อมูลในรอบถัดไป</p>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- About System Tab Content -->
                <div id="about-tab-content" class="tab-pane">
                    <div class="sync-info-card">
                        <div class="sync-card-header">
                            <div class="sync-badge" style="background-color:rgba(16, 185, 129, 0.08); color:var(--success-primary);">
                                <i data-lucide="info"></i>
                                <span>About (เกี่ยวกับระบบ)</span>
                            </div>
                            <h2>เกี่ยวกับระบบ NHSO Executive Dashboard</h2>
                        </div>
                        <div style="padding: 24px; color: #334155; line-height: 1.6;">
                            <p style="margin-bottom: 16px;"><b>NHSO Executive Dashboard</b> เป็นระบบติดตามสถานการณ์เงินชดเชยจากสำนักงานหลักประกันสุขภาพแห่งชาติ (สปสช.) สำหรับผู้บริหารและหน่วยงานบริการสุขภาพ เพื่อใช้ในการวิเคราะห์ข้อมูลการเงินชดเชยที่ได้รับ และที่ยังคงค้างหรือมีข้อผิดพลาด</p>
                            
                            <h3 style="margin-top: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">ขอขอบพระคุณคณะผู้บริหาร</h3>
                            
                            <div style="display: flex; justify-content: center; margin-top: 24px;">
                                <!-- Profile 1: Director -->
                                <div style="width: 100%; max-width: 600px; background: linear-gradient(to bottom right, #f8fafc, #ffffff); border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px 24px; text-align: center; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); border-top: 4px solid var(--blue-primary);">
                                    <div style="width: 160px; height: 160px; margin: 0 auto 20px auto; border-radius: 50%; overflow: hidden; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                        <img src="assets/director.jpg" alt="นายแพทย์จักร์ชัย ติตตะบุตร" style="width: 100%; height: 100%; object-fit: cover; object-position: top;" onerror="this.src='https://ui-avatars.com/api/?name=Director&background=random&size=160'">
                                    </div>
                                    <h3 style="margin: 0 0 8px 0; color: #0f172a; font-size: 22px;">นายแพทย์จักร์ชัย ติตตะบุตร</h3>
                                    <p style="margin: 0 0 4px 0; color: var(--blue-primary); font-size: 16px; font-weight: 500;">นายแพทย์ชำนาญการพิเศษ</p>
                                    <p style="margin: 0 0 20px 0; color: #64748b; font-size: 14px; line-height: 1.5;">ผู้อำนวยการโรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา</p>
                                    
                                    <div style="padding: 16px 24px; background: rgba(14, 165, 233, 0.05); border-radius: 8px; position: relative;">
                                        <i data-lucide="quote" style="position: absolute; top: 12px; left: 16px; width: 24px; height: 24px; color: var(--blue-primary); opacity: 0.2;"></i>
                                        <p style="margin: 0; color: #334155; font-size: 15px; font-style: italic; line-height: 1.6; position: relative; z-index: 1;">"ขอขอบพระคุณที่ให้โอกาส มอบวิสัยทัศน์ และสนับสนุนให้เกิดระบบนี้ขึ้นมาเพื่อพัฒนางานบริการของโรงพยาบาลอย่างเต็มที่"</p>
                                    </div>
                                </div>
                            </div>

                            <h3 style="margin-top: 40px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">คณะผู้จัดทำและที่ปรึกษา</h3>
                            
                            <div style="display: flex; flex-wrap: wrap; gap: 24px; margin-top: 24px; justify-content: center;">
                                <!-- Profile 2: Developer -->
                                <div style="flex: 1; min-width: 250px; max-width: 300px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 20px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s;">
                                    <div style="width: 140px; height: 140px; margin: 0 auto 16px auto; border-radius: 50%; overflow: hidden; border: 4px solid #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <img src="assets/developer.jpg" alt="นางสาวปุณยาพร ติตตะบุตร" style="width: 100%; height: 100%; object-fit: cover; object-position: top;" onerror="this.src='https://ui-avatars.com/api/?name=Developer&background=random&size=140'">
                                    </div>
                                    <h4 style="margin: 0 0 8px 0; color: #0f172a; font-size: 16px;">นางสาวปุณยาพร ติตตะบุตร</h4>
                                    <p style="margin: 0 0 4px 0; color: var(--success-primary); font-size: 14px; font-weight: 500;">นักสาธารณสุข</p>
                                    <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.5;">ผู้พัฒนาระบบ<br>โรงพยาบาลเทพรัตนเวชชานุกูลฯ</p>
                                </div>

                                <!-- Profile 3: Advisor -->
                                <div style="flex: 1; min-width: 250px; max-width: 300px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px 20px; text-align: center; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s;">
                                    <div style="width: 140px; height: 140px; margin: 0 auto 16px auto; border-radius: 50%; overflow: hidden; border: 4px solid #f8fafc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                        <img src="assets/advisor.jpg" alt="นายศุภชัย นะที" style="width: 100%; height: 100%; object-fit: cover; object-position: top;" onerror="this.src='https://ui-avatars.com/api/?name=Advisor&background=random&size=140'">
                                    </div>
                                    <h4 style="margin: 0 0 8px 0; color: #0f172a; font-size: 16px;">นายศุภชัย นะที</h4>
                                    <p style="margin: 0 0 4px 0; color: var(--purple-primary); font-size: 14px; font-weight: 500;">นักวิชาการคอมพิวเตอร์</p>
                                    <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.5;">ที่ปรึกษา<br>โครงการพัฒนาระบบ</p>
                                </div>
                            </div>
                            
                            <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 8px; border-left: 4px solid var(--warning);">
                                <strong style="display: flex; align-items: center; gap: 8px; color: #1e293b;"><i data-lucide="shield-check" style="width: 18px; height: 18px; color: var(--warning);"></i> มาตรฐานความปลอดภัย (PDPA Compliant)</strong>
                                <p style="margin: 8px 0 0 0;">ระบบนี้ได้รับการออกแบบให้สอดคล้องกับพระราชบัญญัติคุ้มครองข้อมูลส่วนบุคคล (PDPA) โดยทำการประมวลผลและแสดงผลเฉพาะข้อมูลภาพรวม (Aggregated Data) และข้อมูลเชิงสถิติเท่านั้น ข้อมูลส่วนบุคคลจะถูกแปลงรหัสและปกปิดตัวตนก่อนเข้าสู่กระบวนการวิเคราะห์</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Log History Tab Content -->
                <div id="log-history-tab-content" class="tab-pane">
                    <div class="sync-info-card">
                        <div class="sync-card-header">
                            <div class="sync-badge" style="background-color:rgba(37, 99, 235, 0.08); color:#1d4ed8;">
                                <i data-lucide="file-text"></i>
                                <span>ประวัติ log</span>
                            </div>
                            <h2>จัดเก็บและตรวจสอบประวัติการทำงานของระบบ</h2>
                            <p class="lead" style="margin-bottom:15px;">ดูเหตุการณ์สำคัญ เช่น การอัปโหลดไฟล์ การเพิ่มปีงบประมาณ และการเปลี่ยนสถานะต่าง ๆ ของผู้ใช้งาน</p>
                        </div>

                        <div class="users-list-container">
                            <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom: 12px; flex-wrap: wrap;">
                                <h3 style="margin:0;">รายการประวัติ log</h3>
                                <div style="display:flex; gap:8px; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-secondary" id="log-history-refresh">รีเฟรช</button>
                                    <button type="button" class="btn btn-secondary" id="log-history-export-csv">CSV</button>
                                    <button type="button" class="btn btn-secondary" id="log-history-export-xlsx">Excel</button>
                                    <button type="button" class="btn btn-danger" id="log-history-clear">ล้างประวัติ log</button>
                                </div>
                            </div>

                            <div class="admin-users-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="log-history-filter">ระดับ log</label>
                                    <select id="log-history-filter" class="form-control">
                                        <option value="all">ทั้งหมด</option>
                                        <option value="info">INFO</option>
                                        <option value="success">SUCCESS</option>
                                        <option value="warn">WARN</option>
                                        <option value="error">ERROR</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="log-history-action-filter">ประเภทเหตุการณ์</label>
                                    <input type="text" id="log-history-action-filter" class="form-control" placeholder="เช่น upload, login, purge">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="log-history-search">ค้นหา</label>
                                    <input type="text" id="log-history-search" class="form-control" placeholder="ค้นหาในข้อความ log...">
                                </div>
                            </div>

                            <div class="admin-users-grid" style="grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="log-history-start-date">วันที่เริ่มต้น</label>
                                    <input type="date" id="log-history-start-date" class="form-control">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="log-history-end-date">วันที่สิ้นสุด</label>
                                    <input type="date" id="log-history-end-date" class="form-control">
                                </div>
                            </div>

                            <div id="log-history-count" style="color:#64748b; font-size:13px; margin-bottom: 12px;">ทั้งหมด 0 รายการ | แสดง 0 รายการ</div>
                            <div class="table-wrapper" style="max-height: 420px; overflow-y:auto;">
                                <table class="data-table" id="log-history-table">
                                    <thead>
                                        <tr>
                                            <th>เวลา</th>
                                            <th>ระดับ</th>
                                            <th>เหตุการณ์</th>
                                            <th>รายละเอียด</th>
                                            <th>ข้อมูลเพิ่มเติม</th>
                                        </tr>
                                    </thead>
                                    <tbody id="log-history-tbody">
                                        <tr>
                                            <td colspan="5" style="padding:16px; text-align:center; color:#64748b;">กำลังโหลดข้อมูล...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Settings Tab Content -->
                <div id="google-sync-tab-content" class="tab-pane user-management-section" style="margin-top:30px;">
                        <div class="sync-info-card">
                            <div class="sync-card-header">
                                <div class="sync-badge" style="background-color:rgba(124, 58, 237, 0.08); color:var(--purple-primary);">
                                    <i data-lucide="users"></i>
                                    <span>User Management (ระบบจัดการผู้ใช้)</span>
                                </div>
                                <h2>เพิ่มและตรวจสอบบัญชีผู้ใช้งานในระบบ</h2>
                                <p class="lead" style="margin-bottom:15px;">ผู้ดูแลระบบสามารถสร้างบัญชีผู้ใช้ใหม่และกำหนดบทบาทที่แตกต่างกันในการเข้าถึงข้อมูล</p>
                            </div>

                            <div class="admin-users-grid" style="display:flex; flex-direction:column; gap:24px;">
                                <!-- Create User Form -->
                                <form id="register-user-form" class="register-user-form">
                                    <h3>สร้างบัญชีผู้ใช้ใหม่</h3>
                                    <div class="form-group">
                                        <label for="reg-username">ชื่อล็อกอิน (Username)</label>
                                        <input type="text" id="reg-username" required class="form-control" placeholder="เช่น executive_director">
                                    </div>
                                    <div class="form-group">
                                        <label for="reg-password">รหัสผ่าน (Password)</label>
                                        <input type="password" id="reg-password" required class="form-control" placeholder="รหัสผ่านอย่างน้อย 6 ตัวอักษร">
                                    </div>
                                    <div class="form-group">
                                        <label for="reg-fullname">ชื่อ-นามสกุลจริง (Full Name)</label>
                                        <input type="text" id="reg-fullname" required class="form-control" placeholder="เช่น นายแพทย์มานพ แสนสุข">
                                    </div>
                                    <div class="form-group">
                                        <label for="reg-phone">เบอร์โทรศัพท์</label>
                                        <input type="text" id="reg-phone" class="form-control" placeholder="เช่น 0812345678">
                                    </div>
                                    <div class="form-group">
                                        <label for="reg-position">ตำแหน่ง</label>
                                        <input type="text" id="reg-position" class="form-control" placeholder="เช่น เจ้าหน้าที่">
                                    </div>
                                    <div class="form-group">
                                        <label for="reg-role">ระดับสิทธิ์การเข้าใช้งาน (Role)</label>
                                        <select id="reg-role" class="form-control">
                                            <option value="USER">USER - ผู้ใช้งานทั่วไป (ดูข้อมูลได้อย่างเดียว)</option>
                                            <option value="EXECUTIVE">EXECUTIVE - ผู้บริหาร (อ่านเท่านั้น)</option>
                                            <option value="OFFICER">OFFICER - เจ้าหน้าที่บันทึกข้อมูล (อัปโหลดข้อมูลได้)</option>
                                            <option value="ADMIN">ADMIN - ผู้ดูแลระบบสูงสุด (จัดการระบบได้)</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary" style="margin-top:10px; width:100%;">
                                        <i data-lucide="user-plus"></i>
                                        <span>บันทึกบัญชีผู้ใช้</span>
                                    </button>
                                </form>

                                <!-- Active Users Table List -->
                                <div class="users-list-container">
                                    <h3>บัญชีผู้ใช้ทั้งหมดในระบบ</h3>
                                    <div class="table-wrapper" style="max-height: 320px; overflow-y: auto;">
                                        <table class="data-table" id="users-admin-table">
                                            <thead>
                                                <tr>
                                                    <th>ชื่อล็อกอิน</th>
                                                    <th>ชื่อ-สกุล</th>
                                                    <th>บทบาท (Role)</th>
                                                    <th style="text-align: center;">สถานะ (Status)</th>
                                                    <th style="text-align: center; width: 120px;">จัดการ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Dynmically loaded users list -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upload History & Data Cleanup Section -->
                    <div class="user-management-section sys-admin-only" style="margin-top:30px;">
                        <div class="sync-info-card">
                            <div class="sync-card-header">
                                <div class="sync-badge" style="background-color:rgba(14, 165, 233, 0.08); color:#0369a1;">
                                    <i data-lucide="archive"></i>
                                    <span>Upload History & Cleanup</span>
                                </div>
                                <h2>ประวัติการอัปโหลดไฟล์และการลบข้อมูลเก่า</h2>
                                <p class="lead" style="margin-bottom:15px;">ดูรายการไฟล์ที่อัปโหลดล่าสุด และลบข้อมูลเก่าที่ไม่ใช้งานตามช่วงเวลาที่กำหนด</p>
                            </div>

                            <div class="admin-users-grid" style="grid-template-columns: 1.5fr 1fr; gap: 24px;">
                                <div class="users-list-container">
                                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom: 12px; flex-wrap: wrap;">
                                        <h3 style="margin:0;">ประวัติการอัปโหลดไฟล์</h3>
                                        <div style="display:flex; gap:8px; flex-wrap: wrap;">
                                            <button type="button" class="btn btn-secondary" id="upload-history-refresh">รีเฟรช</button>
                                            <button type="button" class="btn btn-secondary" id="upload-history-export-csv">CSV</button>
                                            <button type="button" class="btn btn-secondary" id="upload-history-export-xlsx">Excel</button>
                                        </div>
                                    </div>
                                    <div id="upload-history-count" style="color:#64748b; font-size:13px; margin-bottom: 12px;">ทั้งหมด 0 รายการ</div>
                                    <div class="table-wrapper" style="max-height: 320px; overflow-y:auto;">
                                        <table class="data-table" id="upload-history-table">
                                            <thead>
                                                <tr>
                                                    <th>เวลาที่อัปโหลด</th>
                                                    <th>ไฟล์</th>
                                                    <th>หน่วยบริการ</th>
                                                    <th>ปีงบ</th>
                                                    <th>เดือน</th>
                                                    <th>กองทุน</th>
                                                    <th>เมนู</th>
                                                    <th>สถานะ</th>
                                                    <th style="text-align:right;">ขนาดไฟล์</th>
                                                    <th style="text-align:center;">จัดการ</th>
                                                </tr>
                                            </thead>
                                            <tbody id="upload-history-tbody">
                                                <tr>
                                                    <td colspan="9" style="padding:16px; text-align:center; color:#64748b;">กำลังโหลดข้อมูล...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="sync-info-card" style="padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <h3 style="margin-top:0;">ลบข้อมูลเก่า</h3>
                                    <div class="form-group">
                                        <label for="cleanup-retention-days">เก็บข้อมูลย้อนหลัง (วัน)</label>
                                        <input type="number" id="cleanup-retention-days" class="form-control" min="1" value="180">
                                    </div>
                                    <div class="form-group">
                                        <label for="cleanup-scope">ขอบเขตการล้าง</label>
                                        <select id="cleanup-scope" class="form-control">
                                            <option value="all">ทั้งหมด</option>
                                            <option value="uploads">ไฟล์แนบอัปโหลด</option>
                                            <option value="logs">ระบบ log</option>
                                        </select>
                                    </div>
                                    <button type="button" class="btn btn-danger" id="cleanup-old-data-btn" style="width:100%; margin-top: 8px;">
                                        <i data-lucide="trash-2"></i>
                                        <span>ล้างข้อมูลเก่า</span>
                                    </button>
                                    <div id="cleanup-result" style="margin-top:12px; color:#334155; font-size:13px; line-height:1.5;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manage Fiscal Years Section (Admin Only) -->
                    <div class="user-management-section sys-admin-only" style="margin-top:30px;">
                        <div class="sync-info-card">
                            <div class="sync-card-header">
                                <div class="sync-badge" style="background-color:rgba(16, 185, 129, 0.08); color:var(--success-primary);">
                                    <i data-lucide="calendar"></i>
                                    <span>Fiscal Year Management (จัดการปีงบประมาณ)</span>
                                </div>
                                <h2>เพิ่มปีงบประมาณสำหรับระบบตัวกรองและรายงาน</h2>
                                <p class="lead" style="margin-bottom:15px;">ผู้ดูแลระบบสามารถเพิ่มปีงบประมาณใหม่ล่วงหน้าเพื่อเตรียมพร้อมในการกรองและแสดงผลรายงาน</p>
                            </div>

                            <div class="admin-users-grid" style="grid-template-columns: 1fr 1fr; gap: 40px;">
                                <!-- Add Year Form -->
                                <form id="add-year-form" class="register-user-form" style="border-right: 1px solid rgba(255,255,255,0.05); padding-right: 20px;">
                                    <h3>เพิ่มปีงบประมาณใหม่</h3>
                                    <div class="form-group">
                                        <label for="new-fiscal-year">ปีงบประมาณ (พ.ศ.)</label>
                                        <input type="text" id="new-fiscal-year" required class="form-control" placeholder="เช่น 2569" pattern="^25\d{2}$" maxlength="4">
                                        <span class="help-block">กรอกเป็นตัวเลขปี พ.ศ. 4 หลัก</span>
                                    </div>
                                    <button class="btn btn-primary" type="submit" style="margin-top:15px; width:100%;">
                                        <i data-lucide="plus"></i>
                                        <span>บันทึกปีงบประมาณ</span>
                                    </button>
                                </form>

                                <!-- Years List -->
                                <div class="users-list-container" style="padding-left: 20px;">
                                    <h3>รายการปีงบประมาณในระบบ</h3>
                                    <div class="table-wrapper" style="max-height: 250px; overflow-y: auto;">
                                        <table class="data-table" id="managed-years-table">
                                            <thead>
                                                <tr>
                                                    <th>ปีงบประมาณ (พ.ศ.)</th>
                                                    <th class="text-center" style="width: 100px;">จัดการ</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Dynamic years loaded here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                </div>

            </div>
        </main>
    </div>

    <!-- Notification Toast -->

    <!-- Notification Toast -->
    <div id="toast" class="toast-hidden">
        <i data-lucide="check-circle" id="toast-icon"></i>
        <span id="toast-message">นำเข้าไฟล์สำเร็จแล้ว</span>
    </div>

    <!-- Initialization and Application logic -->
    <script>
        window.__NHSO_API_ROOT__ = '/index.php/api';
        
        var ERROR_CATALOG = {
            eclaim: [
                { code: 'C01', desc: 'รหัสโรคหลักไม่สอดคล้องกับหัตถการทางการแพทย์' },
                { code: 'C02', desc: 'ไม่มีข้อมูลการบันทึกเวลาที่เริ่มหรือสิ้นสุดบริการ' },
                { code: 'C03', desc: 'เอกสารและผลการชันสูตรประกอบการส่งเบิกไม่ครบถ้วน' },
                { code: 'C12', desc: 'รหัสยาทางการค้าระบุไม่ตรงตามบัญชียาหลักแห่งชาติ (TMT Code)' }
            ],
            op: [
                { code: 'C04', desc: 'รหัสวินิจฉัยโรค ICD-10 ไม่อยู่ในชุดสิทธิ์ของสิทธิ์บัตรทอง' },
                { code: 'C05', desc: 'มีการส่งข้อมูลซ้ำซ้อนในวันบริการเดียวกัน' },
                { code: 'C11', desc: 'สิทธิ์หลักประกันสุขภาพไม่มีผลบังคับใช้ในวันที่รับการรักษา' }
            ],
            ip: [
                { code: 'C06', desc: 'ระยะเวลาการนอนพักรักษาตัวในโรงพยาบาล (LOS) เกินเกณฑ์เฉลี่ยกลุ่มโรค' },
                { code: 'C09', desc: 'ข้อมูลหัตถการอยู่นอกเหนือน้ำหนักสัมพัทธ์ (AdjRW) ที่เหมาะสม' }
            ],
            smartmoney: [
                { code: 'M01', desc: 'ผลการวิเคราะห์สัดส่วนความคุ้มค่าไม่ผ่านเกณฑ์การเปรียบเทียบ' },
                { code: 'M02', desc: 'มียอดการยื่นซ้ำซ้อนกับโครงการชดเชยงบประมาณรายย่อย' },
                { code: 'M03', desc: 'ไม่พบชื่อบัญชีเงินโอนปลายทางที่ได้รับอนุญาตในระบบโอนเงิน' }
            ],
            ppfs: [
                { code: 'P01', desc: 'กลุ่มอายุของผู้มารับบริการไม่ตรงตามเกณฑ์ช่วงวัยในการตรวจคัดกรอง' },
                { code: 'P02', desc: 'กิจกรรมบริการส่งเสริมสุขภาพถูกเบิกซ้ำเกินความถี่ขั้นสูงต่อคนต่อปี' },
                { code: 'P03', desc: 'รหัสบริการส่งเสริมป้องกันโรคระบุรหัสกิจกรรมไม่ถูกต้อง' }
            ],
            seamless: [
                { code: 'C08', desc: 'ไม่พบการส่งใบ Refer ส่งต่อในระบบฐานข้อมูลกลาง สปสช.' },
                { code: 'S01', desc: 'ไม่พบประวัติใบรับส่งตัวข้ามจังหวัดและเอกสารส่งต่อข้ามเขต' },
                { code: 'S02', desc: 'เงื่อนไขเครือข่ายส่งต่อไร้รอยต่อขัดแย้งกับข้อตกลงพื้นที่' },
                { code: 'S04', desc: 'รหัสชดเชยพิเศษ (Special Claim Code) ไม่พบในฐานทะเบียนสิทธิ์' }
            ]
        };
    </script>
    <script charset="utf-8" src="/public/app.v71.js?v={{ filemtime(public_path('app.v71.js')) }}&cb=20260817c"></script>
</body>
</html>





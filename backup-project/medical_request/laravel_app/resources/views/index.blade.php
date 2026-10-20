<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบขอข้อมูลเวชระเบียน | Medical Record Request</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --secondary: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Sarabun', sans-serif;
        }

        body {
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* --- Transitions --- */
        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- Layout --- */
        .container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .hidden { display: none !important; }

        /* --- Header --- */
        header {
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 0.75rem 0;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: #1d4ed8; /* Blue from image */
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.9rem;
            color: #475569;
        }

        /* --- Buttons --- */
        .btn {
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-primary { 
            background: #15803d; /* Green from image submit button */
            color: white; 
            font-weight: 600;
        }
        .btn-primary:hover { background: #166534; }
        
        .btn-logout {
            background: transparent;
            border: 1px solid #f87171;
            color: #ef4444;
            padding: 0.3rem 1rem;
            border-radius: 99px;
            font-size: 0.8rem;
            text-decoration: none;
        }
        .btn-logout:hover { background: #fee2e2; }

        /* --- Alert --- */
        .alert-box {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.75rem 1rem;
            background: white;
            border-left: 4px solid #ef4444;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #ef4444;
            font-weight: 500;
        }

        /* --- Form Sections --- */
        .section-title {
            color: #1d4ed8;
            font-weight: 700;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1.25rem;
        }

        .field-label {
            font-weight: 700;
            margin-bottom: 0.75rem;
            display: block;
            color: #334155;
            font-size: 0.9rem;
        }

        .checkbox-group-wrapper {
            padding: 1.5rem;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }

        .column-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 1rem;
        }

        @media (max-width: 900px) {
            .column-grid { grid-template-columns: repeat(2, 1fr); }
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #475569;
            cursor: pointer;
        }

        .checkbox-item input {
            width: 16px;
            height: 16px;
        }

        textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            min-height: 60px;
            font-size: 0.9rem;
            resize: vertical;
        }

        .date-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .date-grid { grid-template-columns: 1fr; }
        }

        .important-input {
            border-color: #ef4444 !important;
        }
        .important-label {
            color: #ef4444 !important;
        }

        /* --- History Layout --- */
        .history-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 1.15rem;
            color: #475569;
            margin-bottom: 1rem;
        }

        /* --- Login Page --- */
        #login-view {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        }

        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255,255,255,0.8);
        }

        .login-card h2 {
            margin-bottom: 2rem;
            text-align: center;
            color: var(--text-main);
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        input, select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            outline: none;
            transition: border-color 0.2s;
            font-size: 1rem;
        }

        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }

        /* --- Dashboard --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        .card {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* --- Table --- */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background: #f8fafc;
            padding: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .status-pill {
            padding: 0.25rem 0.6rem;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #dbeafe; color: #1e40af; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        /* --- Admin Module --- */
        .admin-controls {
            display: flex;
            gap: 0.5rem;
        }

        /* --- Toast --- */
        #toast {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            background: #334155;
            color: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transform: translateY(100px);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        #toast.show { transform: translateY(0); }

        /* --- Modal --- */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        /* --- Dashboard Stats --- */
        .stats-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 1000px) {
            .stats-grid { grid-template-columns: 1fr; }
            .charts-container { grid-template-columns: 1fr; }
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* --- Star Rating --- */
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 10px;
            margin: 1rem 0;
        }
        .star-rating input { display: none; }
        .star-rating label {
            font-size: 2.5rem;
            color: #e2e8f0;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #f59e0b;
        }
    </style>
</head>
<body>

    <!-- Header (Hidden initially) -->
    <header id="app-header" class="hidden">
        <div class="container header-content">
            <div id="brand-header" class="brand">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                สร้างคำขอข้อมูลใหม่
            </div>
            <div class="user-info">
                สวัสดี, <span id="display-name" style="font-weight: 700;">Username</span>
                <span id="display-dept" class="role-badge" style="background: #f1f5f9; color: #475569;">Dept</span>
                <button class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 4px 8px; border-color: #cbd5e1; color: #64748b;" onclick="openChangePasswordModal()">เปลี่ยนรหัสผ่าน</button>
                <button class="btn btn-logout" onclick="logout()">ออกจากระบบ</button>
            </div>
        </div>
    </header>

    <!-- Login View -->
    <main id="login-view">
        <div class="login-card fade-in">
            <h2>ยินดีต้อนรับ</h2>
            <form id="login-form">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="username" placeholder="กรอกชื่อผู้ใช้" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="password" placeholder="กรอกรหัสผ่าน" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;" id="login-btn">
                    เข้าสู่ระบบ
                </button>
                <div id="login-error" style="color: var(--danger); font-size: 0.8rem; margin-top: 1rem; text-align: center;" class="hidden"></div>
                
                <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--border); padding-top: 1rem;">
                    <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">ยังไม่มีบัญชีผู้ใช้?</p>
                    <button type="button" class="btn" style="background: white; border: 1px solid var(--primary); color: var(--primary); width: 100%;" onclick="openRegisterModal()">
                        ลงทะเบียนใหม่
                    </button>
                </div>
            </form>
            <p style="text-align: center; font-size: 0.75rem; color: var(--text-muted); margin-top: 2rem;">
                Internal System for Hospital Staff Only
            </p>
        </div>
    </main>

    <!-- User Dashboard View -->
    <main id="user-view" class="container hidden fade-in">
        <div class="alert-box">
            <span> | * การขอข้อมูลควรขอก่อนวันที่ต้องการข้อมูลอย่างน้อย 3 วัน</span>
        </div>

        <form id="request-form" class="card" style="margin-bottom: 2rem; padding: 2rem;">
            <!-- ตอนที่ 1: ข้อมูลทั่วไป -->
            <div class="section-title">
                <span style="background: #1d4ed8; color: white; padding: 2px 10px; border-radius: 4px;">1</span> ตอนที่ 1: ข้อมูลทั่วไป
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 1.5rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div class="form-group">
                    <label class="field-label">ชื่อผู้ขอข้อมูล:</label>
                    <input type="text" id="req_name" placeholder="ชื่อ-นามสกุล" required>
                </div>
                <div class="form-group">
                    <label class="field-label">หน่วยงานที่ปฏิบัติงาน:</label>
                    <input type="text" id="req_dept" placeholder="แผนก/หน่วยงาน" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                    <label class="field-label">เบอร์ที่ติดต่อได้:</label>
                    <input type="tel" id="req_contact" placeholder="เช่น 081-234-5678" required>
                </div>
            </div>

            <!-- ตอนที่ 2: ประเภทการขอข้อมูล -->
            <div class="section-title">
                <span style="background: #1d4ed8; color: white; padding: 2px 10px; border-radius: 4px;">2</span> ตอนที่ 2: ประเภทการขอข้อมูล
            </div>
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="field-label">ปีงบประมาณ:</label>
                    <input type="text" id="fiscal_year" placeholder="เช่น 2567" required>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="field-label">ประเภทรายการที่ต้องการ:</label>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div>
                            <label class="checkbox-item">
                                <input type="checkbox" name="type" value="Statistics" id="check-stats" onchange="toggleStatsDetail()"> ๑. ขอจำนวนสรุป (Statistics)
                            </label>
                            <div id="stats-detail-box" class="hidden" style="margin-top: 10px; margin-left: 24px;">
                                <label style="font-size: 0.8rem; color: #1e40af; font-weight: 600;">ระบุข้อมูลสรุปที่ต้องการ (เช่น จำนวนผู้ป่วยแยกตามโรค, อายุ, ฯลฯ):</label>
                                <textarea id="stats_details" placeholder="เช่น ขอจำนวนผู้รับบริการแยกตามกลุ่มอายุและสิทธิ์การรักษา..." style="border-color: #3b82f6; background: #eff6ff;"></textarea>
                            </div>
                        </div>
                        <label class="checkbox-item">
                            <input type="checkbox" name="type" value="Patient List" checked> ๒. ขอรายชื่อผู้ป่วย (Patient List)
                        </label>
                    </div>
                </div>
                
                <div class="date-grid" style="margin-top: 1.5rem;">
                    <div class="form-group">
                        <label class="field-label">ข้อมูลเริ่มวันที่:</label>
                        <input type="date" id="start_date" required>
                    </div>
                    <div class="form-group">
                        <label class="field-label">ถึงวันที่:</label>
                        <input type="date" id="end_date" required>
                    </div>
                    <div class="form-group">
                        <label class="field-label important-label">วันที่ต้องการข้อมูล:</label>
                        <input type="date" id="target_date" class="important-input" required>
                    </div>
                </div>
            </div>

            <!-- ตอนที่ 3: ลักษณะข้อมูล -->
            <div class="section-title">
                <span style="background: #1d4ed8; color: white; padding: 2px 10px; border-radius: 4px;">3</span> ตอนที่ 3: ลักษณะข้อมูล
            </div>
            <div class="checkbox-group-wrapper" style="background: #f8fafc;">
                <label class="field-label" style="color: #3b82f6;">เลือกคอลัมน์ข้อมูลที่ต้องการ:</label>
                <div class="column-grid">
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="HN"> HN</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="AN"> AN</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="ชื่อ-นามสกุล"> ชื่อ-นามสกุล</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="CID"> CID</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="อายุ/เพศ"> อายุ/เพศ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="สิทธิ"> สิทธิ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="ICD-10"> ICD-10</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="ICD-9"> ICD-9</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="วันที่รับบริการ"> วันที่รับบริการ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="เบอร์โทร"> เบอร์โทร</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="คลินิก/หน่วยงานที่รับบริการ"> คลินิก/หน่วยงานที่รับบริการ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="รายการยาที่ได้รับ"> รายการยาที่ได้รับ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="ค่ารักษาพยาบาล"> ค่ารักษาพยาบาล</label>
                </div>
                <label class="field-label" style="font-size: 0.8rem; margin-top: 1rem;">ระบุรายการอื่น / ระบุรายละเอียดเพิ่มเติม:</label>
                <textarea id="additional_details" placeholder="ระบุคอลัมน์หรือเงื่อนไขพิเศษเพิ่มเติมที่นี่..." style="margin-bottom: 1.5rem;"></textarea>

                <div class="form-group">
                    <label class="field-label" style="color: #15803d;">ช่องทางการส่งข้อมูล:</label>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <select id="delivery_channel" onchange="checkDeliveryOther()" required>
                            <option value="" disabled selected>เลือกช่องทางหลัก...</option>
                            <option value="Line">Line</option>
                            <option value="Mail">Mail</option>
                            <option value="Flash Drive / External HDD">Flash Drive / External HDD</option>
                            <option value="เอกสาร (Paper)">เอกสาร (Paper)</option>
                            <option value="อื่นๆ">อื่นๆ (ระบุรายละเอียดด้านล่าง)</option>
                        </select>
                        <input type="text" id="delivery_channel_details" placeholder="ระบุรายละเอียดเพิ่มเติม (เช่น ID Line, Email หรือช่องทางอื่นที่ต้องการ)..." style="border-color: #15803d; background: #f0fdf4;">
                    </div>
                </div>
            </div>

            <div class="form-group" style="background: #fff1f2; padding: 1rem; border-radius: 8px; border: 1px solid #fecaca; margin-top: 1.5rem;">
                <label class="checkbox-item" style="color: #be123c; font-weight: 600;">
                    <input type="checkbox" id="pdpa_consent" required> 
                    ข้าพเจ้ายืนยันว่าการขอข้อมูลนี้เป็นไปเพื่อวัตถุประสงค์ในการปฏิบัติงาน/วิจัยเท่านั้น และจะรักษาความลับของข้อมูลส่วนบุคคลตามกฎหมาย PDPA อย่างเคร่งครัด
                </label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-top: 2rem;" id="submit-request-btn">
                ยืนยันส่งคำขอข้อมูล
            </button>
        </form>

        <!-- History Table Section -->
        <div class="card" style="padding: 1.5rem;">
            <div class="history-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                ประวัติการขอข้อมูลของคุณ
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 150px;">วันที่ขอ</th>
                            <th>ประเภท</th>
                            <th style="width: 120px;">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody id="user-requests-table"></tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Admin Dashboard View -->
    <main id="admin-view" class="container hidden fade-in">
        <!-- Admin Navigation Tabs -->
        <div style="margin-bottom: 1.5rem; display: flex; gap: 10px;">
            <button id="tab-requests" class="btn btn-primary" onclick="switchAdminTab('requests')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                รายการคำขอข้อมูล
            </button>
            <button id="tab-users" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary);" onclick="switchAdminTab('users')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                จัดการผู้ใช้ (CSV Import)
            </button>
        </div>

        <div id="admin-section-requests">
        <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div id="stat-total" class="stat-value">0</div>
                    <div class="stat-label">Total Requests</div>
                </div>
                <div class="stat-card">
                    <div id="stat-avg-rating" class="stat-value">0.0</div>
                    <div class="stat-label">Avg Satisfaction</div>
                </div>
                
                <div class="charts-container" style="grid-column: span 2;">

                <div class="card" style="min-height: 300px;">
                    <canvas id="deptChart"></canvas>
                </div>
                <div class="card" style="min-height: 300px;">
                    <canvas id="deptPieChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <h3 class="card-title">Feedback & Comments</h3>
            </div>
            <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 150px;">วันที่</th>
                            <th style="width: 100px;">คะแนน</th>
                            <th>ความคิดเห็น</th>
                            <th>ผู้ประเมิน</th>
                        </tr>
                    </thead>
                    <tbody id="admin-feedback-table"></tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Manage Medical Record Requests</h3>
                <button class="btn btn-outline" onclick="loadRequests()">Refresh Data</button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>แผนก</th>
                            <th>ผู้ขอ</th>
                            <th>วันที่ขอ</th>
                            <th>ประเภท</th>
                            <th>ช่วงข้อมูล</th>
                            <th>สถานะ</th>
                            <th>จัดการ / ไฟล์</th>
                        </tr>
                    </thead>
                    <tbody id="admin-requests-table"></tbody>
                </table>
            </div>
            </div>
        </div>
        </div>

        <!-- Admin Users Section -->
        <div id="admin-section-users" class="hidden">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">User Management</h3>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="user-search" placeholder="ค้นหาตามชื่อ/Username..." style="width: 250px; padding: 0.5rem;" oninput="filterUserTable()">
                        <button class="btn btn-primary" onclick="openImportModal()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                            นำเข้าพนักงาน (CSV)
                        </button>
                    </div>
                </div>
                <div class="table-container">
                    <table id="users-table-main">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>บทบาท</th>
                                <th>แผนก</th>
                            </tr>
                        </thead>
                        <tbody id="admin-users-table"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>


    <!-- Admin Action Modal (Enhanced) -->
    <div id="admin-modal" class="modal-overlay hidden">
        <div class="card fade-in" style="max-width: 600px; width: 95%; padding: 2rem; max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-bottom: 0.5rem; color: #1e293b;" id="modal-req-id">จัดการคำขอ</h3>
            
            <!-- ส่วนแสดงรายละเอียดที่ User ขอมา -->
            <div id="request-detail-summary" style="background: #f1f5f9; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; line-height: 1.6;">
                <div style="display: grid; grid-template-columns: 100px 1fr; gap: 5px;">
                    <b style="color: #64748b;">ผู้ขอ:</b> <span id="view-user">-</span>
                    <b style="color: #64748b;">ประเภทข่าว:</b> <span id="view-type">-</span>
                    <b style="color: #64748b;">คอลัมน์:</b> <span id="view-cols">-</span>
                    <b style="color: #64748b;">ช่วงข้อมูล:</b> <span id="view-dates">-</span>
                    <b style="color: #64748b;">วันที่ต้องการ:</b> <span id="view-target" style="color: #ef4444; font-weight: 700;">-</span>
                    <b style="color: #64748b;">รายละเอียด:</b> <span id="view-details" style="white-space: pre-wrap;">-</span>
                    <b style="color: #64748b;">วัตถุประสงค์:</b> <span id="view-purpose" style="white-space: pre-wrap;">-</span>
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">แนบลิงก์ไฟล์ผลลัพธ์ (สำหรับขั้นตอนส่งไฟล์)</label>
                <input type="url" id="file_url" placeholder="https://drive.google.com/...">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 2rem;">
                <button class="btn btn-primary" style="background: #3b82f6; grid-column: span 2;" onclick="submitAdminAction('Processing')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>
                    รับงาน (Processing)
                </button>
                <button class="btn btn-primary" onclick="submitAdminAction('Approved')">ส่งไฟล์และอนุมัติ</button>
                <button class="btn btn-danger" onclick="submitAdminAction('Rejected')">ยกเลิกงาน/ปฏิเสธ</button>
                <button class="btn btn-outline" style="grid-column: span 2; margin-top: 5px;" onclick="closeModal()">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div id="register-modal" class="modal-overlay hidden">
        <div class="card fade-in" style="max-width: 450px; width: 95%; padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: #1e293b; text-align: center;">ลงทะเบียนสมาชิกใหม่</h3>
            <form id="register-form">
                <div class="form-group">
                    <label>ชื่อ-นามสกุล</label>
                    <input type="text" id="reg-fullname" placeholder="กรอกชื่อ-นามสกุลจริง" required>
                </div>
                <div class="form-group">
                    <label>แผนก/หน่วยงาน</label>
                    <input type="text" id="reg-dept" placeholder="เช่น งานเวชระเบียน" required>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" id="reg-username" placeholder="ตั้งชื่อผู้ใช้" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="reg-password" placeholder="ตั้งรหัสผ่าน" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" id="reg-confirm-password" placeholder="ยืนยันรหัสผ่านอีกครั้ง" required>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeRegisterModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;" id="reg-submit-btn">ลงทะเบียน</button>
                </div>
                <div id="reg-error" style="color: var(--danger); font-size: 0.8rem; margin-top: 1rem; text-align: center;" class="hidden"></div>
            </form>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div id="password-modal" class="modal-overlay hidden">
        <div class="card fade-in" style="max-width: 400px; width: 95%; padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: #1e293b; text-align: center;">เปลี่ยนรหัสผ่าน</h3>
            <form id="password-form">
                <div class="form-group">
                    <label>รหัสผ่านปัจจุบัน</label>
                    <input type="password" id="current-password" required>
                </div>
                <div class="form-group">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" id="new-password" required minlength="4">
                </div>
                <div class="form-group">
                    <label>ยืนยันรหัสผ่านใหม่</label>
                    <input type="password" id="confirm-new-password" required minlength="4">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeChangePasswordModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;" id="pwd-submit-btn">บันทึก</button>
                </div>
                <div id="pwd-error" style="color: var(--danger); font-size: 0.8rem; margin-top: 1rem; text-align: center;" class="hidden"></div>
            </form>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedback-modal" class="modal-overlay hidden">
        <div class="card fade-in" style="max-width: 400px; width: 95%; padding: 2rem; text-align: center;">
            <h3 style="margin-bottom: 0.5rem; color: #1e293b;">ประเมินความพึงพอใจ</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">รหัสคำขอ: <span id="feedback-req-id" style="font-weight: 700;">-</span></p>
            
            <form id="feedback-form">
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" required><label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4"><label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3"><label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2"><label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1"><label for="star1">★</label>
                </div>
                
                <div class="form-group" style="text-align: left;">
                    <label>ข้อเสนอแนะเพิ่มเติม</label>
                    <textarea id="feedback-comment" placeholder="ความรู้สึกหลังใช้งาน หรือสิ่งที่ต้องการให้ปรับปรุง..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;" onclick="closeFeedbackModal()">ไว้ภายหลัง</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;" id="feedback-submit-btn">ส่งประเมิน</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import CSV Modal -->
    <div id="import-modal" class="modal-overlay hidden">
        <div class="card fade-in" style="max-width: 500px; width: 95%; padding: 2rem;">
            <h3 style="margin-bottom: 1rem;">นำเข้าข้อมูลผู้ใช้จาก CSV</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.5rem;">
                โปรดเลือกไฟล์ .csv ที่มีหัวคอลัมน์ดังนี้:<br>
                <code>id, username, password, role, dept, full_name</code>
            </p>
            
            <div style="border: 2px dashed var(--border); padding: 2rem; text-align: center; border-radius: 12px; margin-bottom: 1.5rem;">
                <input type="file" id="csv-file-input" accept=".csv" style="display: none;" onchange="handleFileSelect(event)">
                <label for="csv-file-input" style="cursor: pointer;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 1rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
                    <div id="file-label-text">คลิกเพื่อเลือกไฟล์ CSV</div>
                </label>
            </div>

            <div id="import-preview" class="hidden" style="margin-bottom: 1.5rem; max-height: 150px; overflow-y: auto; background: #f8fafc; padding: 0.5rem; font-size: 0.75rem; border-radius: 8px;">
                <table style="width: 100%;">
                    <thead><tr><th>User</th><th>Name</th><th>Dept</th></tr></thead>
                    <tbody id="preview-body"></tbody>
                </table>
            </div>

            <div style="display: flex; gap: 10px;">
                <button class="btn btn-outline" style="flex: 1;" onclick="closeImportModal()">ยกเลิก</button>
                <button id="confirm-import-btn" class="btn btn-primary" style="flex: 2;" disabled onclick="processImport()">เริ่มนำเข้าข้อมูล</button>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toast">
        <span id="toast-msg">Success!</span>
    </div>

    <script>
        /**
         * แปะ URL ของ Google Apps Script Web App ของคุณที่นี่
         * (ไปที่ Deploy > Manage Deploys > คัดลอก Web App URL)
         */
        const API_URL = ""; 

        // Global State
        let currentUser = @json(auth()->user()); 
        if (currentUser) {
             // Map Laravel fields to JS expected names
             currentUser.fullName = currentUser.full_name;
             currentUser.dept = currentUser.department;
        }

        let selectedRequestId = null;
        let adminRequestsData = [];
        let adminUsersData = []; 
        let deptChart = null;
        let deptPieChart = null;
        let csvUploadData = null; 
        
        window.addEventListener('DOMContentLoaded', () => {
            if (currentUser) {
                showView(currentUser.role === 'admin' ? 'admin' : 'user');
                loadRequests();
            }
        });
        
        const CHART_COLORS = [
            'rgba(14, 165, 233, 0.7)', // Blue
            'rgba(16, 185, 129, 0.7)', // Emerald
            'rgba(245, 158, 11, 0.7)', // Amber
            'rgba(239, 68, 68, 0.7)',  // Rose
            'rgba(99, 102, 241, 0.7)', // Indigo
            'rgba(139, 92, 246, 0.7)', // Violet
            'rgba(236, 72, 153, 0.7)', // Pink
            'rgba(100, 116, 139, 0.7)' // Slate
        ];
        const BORDER_COLORS = [
            'rgb(14, 165, 233)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)', 'rgb(239, 68, 68)',
            'rgb(99, 102, 241)', 'rgb(139, 92, 246)', 'rgb(236, 72, 153)', 'rgb(100, 116, 139)'
        ];

        // Initialize
        document.getElementById('login-form').addEventListener('submit', handleLogin);
        document.getElementById('request-form').addEventListener('submit', handleRequestSubmit);
        document.getElementById('register-form').addEventListener('submit', handleRegister);
        document.getElementById('feedback-form').addEventListener('submit', handleFeedbackSubmit);
        document.getElementById('password-form').addEventListener('submit', handleChangePassword);

        function openFeedbackModal(requestId) {
            document.getElementById('feedback-req-id').innerText = requestId;
            document.getElementById('feedback-modal').classList.remove('hidden');
        }

        function closeFeedbackModal() {
            document.getElementById('feedback-modal').classList.add('hidden');
            document.getElementById('feedback-form').reset();
        }

        async function handleFeedbackSubmit(e) {
            e.preventDefault();
            const requestId = document.getElementById('feedback-req-id').innerText;
            const rating = document.querySelector('input[name="rating"]:checked').value;
            const comment = document.getElementById('feedback-comment').value;
            const btn = document.getElementById('feedback-submit-btn');

            btn.disabled = true;
            btn.innerHTML = 'กำลังส่ง...';

            try {
                const res = await callServer('submitFeedback', {
                    formData: {
                        requestId,
                        rating,
                        comment,
                        user: currentUser.username,
                        dept: currentUser.dept
                    }
                });
                if (res.success) {
                    showToast('ขอบคุณสำหรับการประเมินครับ');
                    closeFeedbackModal();
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการส่งประเมิน');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'ส่งประเมิน';
            }
        }

        function openRegisterModal() {
            document.getElementById('register-modal').classList.remove('hidden');
        }

        function closeRegisterModal() {
            document.getElementById('register-modal').classList.add('hidden');
            document.getElementById('register-form').reset();
            document.getElementById('reg-error').classList.add('hidden');
        }

        // Change Password Modal
        function openChangePasswordModal() {
            document.getElementById('password-modal').classList.remove('hidden');
        }

        function closeChangePasswordModal() {
            document.getElementById('password-modal').classList.add('hidden');
            document.getElementById('password-form').reset();
            document.getElementById('pwd-error').classList.add('hidden');
        }

        async function handleChangePassword(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmNewPassword = document.getElementById('confirm-new-password').value;
            const btn = document.getElementById('pwd-submit-btn');
            const err = document.getElementById('pwd-error');

            if (newPassword !== confirmNewPassword) {
                err.innerText = 'รหัสผ่านใหม่ไม่ตรงกัน';
                err.classList.remove('hidden');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'กำลังบันทึก...';
            err.classList.add('hidden');

            try {
                const res = await callServer('changePassword', { 
                    username: currentUser.username, 
                    currentPassword, 
                    newPassword 
                });
                if (res.success) {
                    showToast('เปลี่ยนรหัสผ่านสำเร็จแล้ว');
                    closeChangePasswordModal();
                } else {
                    err.innerText = res.message;
                    err.classList.remove('hidden');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'บันทึก';
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const fullName = document.getElementById('reg-fullname').value;
            const dept = document.getElementById('reg-dept').value;
            const username = document.getElementById('reg-username').value;
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('reg-confirm-password').value;
            const btn = document.getElementById('reg-submit-btn');
            const err = document.getElementById('reg-error');

            if (password !== confirmPassword) {
                err.innerText = 'รหัสผ่านไม่ตรงกัน';
                err.classList.remove('hidden');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'กำลังประมวลผล...';
            err.classList.add('hidden');

            try {
                const res = await callServer('register', { 
                    formData: { fullName, dept, username, password } 
                });
                btn.disabled = false;
                btn.innerHTML = 'ลงทะเบียน';
                
                if (res.success) {
                    showToast('ลงทะเบียนสำเร็จแล้ว โปรดเข้าสู่ระบบ');
                    closeRegisterModal();
                    document.getElementById('username').value = username;
                    document.getElementById('password').value = password;
                } else {
                    err.innerText = res.message;
                    err.classList.remove('hidden');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = 'ลงทะเบียน';
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        }

        /**
         * ฟังก์ชันหลักสำหรับเรียกคุยกับ Backend (Laravel API)
         */
        async function callServer(action, data = {}) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const routeMap = {
                'login': { url: '/api/login', method: 'POST' },
                'register': { url: '/api/register', method: 'POST' },
                'logout': { url: '/api/logout', method: 'POST' },
                'getRequests': { url: '/api/requests', method: 'GET' },
                'createRequest': { url: '/api/requests', method: 'POST' },
                'getDashboardData': { url: '/api/dashboard-data', method: 'GET' },
                'submitFeedback': { url: '/api/feedback', method: 'POST' },
                'updateRequestStatus': { url: '/api/admin/update-status', method: 'POST' },
                'getAllUsers': { url: '/api/admin/users', method: 'GET' },
                'importUsers': { url: '/api/admin/import-users', method: 'POST' },
                'resetPassword': { url: '/api/admin/reset-password', method: 'POST' },
                'changePassword': { url: '/api/change-password', method: 'POST' }
            };

            const config = routeMap[action];
            if (!config) throw new Error("Invalid action: " + action);

            let url = config.url;
            const options = {
                method: config.method,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            };

            if (config.method === 'POST') {
                // Flatten data if needed (some calls use { formData: {} })
                let bodyData = data.formData || data; 
                options.body = JSON.stringify(bodyData);
            } else if (config.method === 'GET' && Object.keys(data).length > 0) {
                const params = new URLSearchParams(data).toString();
                url += `?${params}`;
            }

            try {
                const response = await fetch(url, options);
                if (response.status === 401) { logout(); throw new Error('Session Expired'); }
                const result = await response.json();
                return result;
            } catch (e) {
                console.error("Fetch Error:", e);
                throw e;
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const btn = document.getElementById('login-btn');
            
            btn.disabled = true;
            btn.innerHTML = 'Connecting...';
            
            try {
                const res = await callServer('login', { username, password });
                btn.disabled = false;
                btn.innerHTML = 'เข้าสู่ระบบ';
                if (res.success) {
                    currentUser = res.user;
                    showView(currentUser.role === 'admin' ? 'admin' : 'user');
                    loadRequests();
                } else {
                    const err = document.getElementById('login-error');
                    err.innerText = res.message;
                    err.classList.remove('hidden');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = 'เข้าสู่ระบบ';
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            }
        }

        async function handleRequestSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('submit-request-btn');
            
            const selectedTypes = Array.from(document.querySelectorAll('input[name="type"]:checked')).map(el => el.value);
            const selectedCols = Array.from(document.querySelectorAll('input[name="cols"]:checked')).map(el => el.value);
            const isStats = document.getElementById('check-stats').checked;
            const statsInfo = isStats ? ` [รายละเอียดสรุป: ${document.getElementById('stats_details').value}]` : '';

            const formData = {
                user: currentUser.username,
                dept: document.getElementById('req_dept').value,
                requesterName: document.getElementById('req_name').value,
                contactNumber: document.getElementById('req_contact').value,
                fiscalYear: document.getElementById('fiscal_year').value,
                deliveryChannel: document.getElementById('delivery_channel').value + (document.getElementById('delivery_channel_details').value ? ' - ' + document.getElementById('delivery_channel_details').value : ''),
                start_date: document.getElementById('start_date').value,
                end_date: document.getElementById('end_date').value,
                target_date: document.getElementById('target_date').value,
                type: selectedTypes.join(' & ') + statsInfo,
                columns: selectedCols.join(', '),
                details: document.getElementById('additional_details').value,
                purpose: '' // Purpose can be integrated into additional details or separate field
            };

            if (selectedTypes.length === 0) {
                showToast('โปรดเลือกประเภทรายการที่ต้องการ');
                return;
            }

            if (!document.getElementById('pdpa_consent').checked) {
                showToast('โปรดกดยินยอมเงื่อนไข PDPA ก่อนส่งคำขอ');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'กำลังบันทึกข้อมูล...';

            try {
                const res = await callServer('createRequest', { formData });
                btn.disabled = false;
                btn.innerHTML = 'ยืนยันส่งคำขอข้อมูล';
                if (res.success) {
                    showToast('ส่งคำขอเรียบร้อยแล้ว');
                    document.getElementById('request-form').reset();
                    loadRequests();
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = 'ยืนยันส่งคำขอข้อมูล';
                showToast('เกิดข้อผิดพลาดในการส่งข้อมูล');
            }
        }

        async function loadRequests() {
            if (!currentUser) return;
            
            try {
                const data = await callServer('getRequests', { role: currentUser.role, username: currentUser.username });
                if (currentUser.role === 'admin') {
                    adminRequestsData = data;
                    renderAdminTable(data);
                    updateDashboard();
                } else {
                    renderUserTable(data);
                }
            } catch (err) {
                console.error("Load error:", err);
            }
        }

        function renderUserTable(data) {
            const tbody = document.getElementById('user-requests-table');
            tbody.innerHTML = data.map(row => `
                <tr>
                   <td>${row.request_date}</td>
                   <td>
                        <div style="font-weight: 700;">${row.type}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">เริ่ม ${row.start_date} ถึง ${row.end_date}</div>
                   </td>
                   <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="status-pill status-${row.status.toLowerCase()}">${row.status}</span>
                            ${row.status === 'Approved' ? `
                                <button class="btn" style="padding: 2px 8px; font-size: 0.65rem; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;" onclick="openFeedbackModal('${row.id}')">
                                    ประเมิน
                                </button>
                            ` : ''}
                        </div>
                   </td>
                </tr>
            `).join('');
        }

        function renderAdminTable(data) {
            const tbody = document.getElementById('admin-requests-table');
            tbody.innerHTML = data.map(row => `
                <tr>
                    <td><span style="color: var(--text-muted); font-size: 0.75rem;">${row.id}</span></td>
                    <td><span class="role-badge" style="font-size: 0.7rem;">${row.dept || '-'}</span></td>
                    <td><b>${row.user}</b></td>
                    <td>${row.request_date}</td>
                    <td>
                        <div><b>${row.type}</b></div>
                        <div style="font-size: 0.75rem;">คอลัมน์: ${row.columns || '-'}</div>
                    </td>
                    <td>${row.start_date} to ${row.end_date}</td>
                    <td><span class="status-pill status-${row.status.toLowerCase()}">${row.status}</span></td>
                    <td>
                        ${row.status === 'Pending' || row.status === 'Processing'
                            ? `<button class="btn btn-primary btn-sm" onclick="openAdminModal('${row.id}')">จัดการ</button>`
                            : row.file_url ? `<a href="${row.file_url}" target="_blank" style="font-size: 0.8rem;">ดูไฟล์</a>` : 'ไม่มีไฟล์'
                        }
                    </td>
                </tr>
            `).join('');
        }

        function showView(view) {
            document.getElementById('login-view').classList.add('hidden');
            document.getElementById('user-view').classList.add('hidden');
            document.getElementById('admin-view').classList.add('hidden');
            document.getElementById('app-header').classList.remove('hidden');
            
            document.getElementById('display-name').innerText = currentUser.fullName || currentUser.username;
            document.getElementById('display-dept').innerText = currentUser.dept || 'N/A';
            
            // Pre-fill request form
            if (view === 'user') {
                document.getElementById('req_name').value = currentUser.fullName || '';
                document.getElementById('req_dept').value = currentUser.dept || '';
            }
            
            const brandHeader = document.getElementById('brand-header');
            if (view === 'user') {
                document.getElementById('user-view').classList.remove('hidden');
                brandHeader.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> สร้างคำขอข้อมูลใหม่`;
            }
            if (view === 'admin') {
                document.getElementById('admin-view').classList.remove('hidden');
                brandHeader.innerHTML = `Medical Admin Panel`;
            }
        }

        function logout() {
            currentUser = null;
            document.getElementById('app-header').classList.add('hidden');
            document.getElementById('user-view').classList.add('hidden');
            document.getElementById('admin-view').classList.add('hidden');
            document.getElementById('login-view').classList.remove('hidden');
            document.getElementById('login-form').reset();
            document.getElementById('login-error').classList.add('hidden');
        }

        // Modal Logic
        function openAdminModal(id) {
            selectedRequestId = id;
            const row = adminRequestsData.find(r => r.id === id);
            if (!row) return;

            document.getElementById('modal-req-id').innerText = 'จัดการคำขอ: ' + id;
            
            // แสดงรายละเอียดแบบเต็ม
            document.getElementById('view-user').innerText = row.user;
            document.getElementById('view-type').innerText = row.type;
            document.getElementById('view-cols').innerText = row.columns || 'ไม่ได้ระบุ';
            document.getElementById('view-dates').innerText = `${row.start_date} ถึง ${row.end_date}`;
            document.getElementById('view-target').innerText = row.target_date || '-';
            document.getElementById('view-details').innerText = row.details || '-';
            document.getElementById('view-purpose').innerText = row.purpose || '-';

            document.getElementById('admin-modal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('admin-modal').classList.add('hidden');
            document.getElementById('file_url').value = '';
        }

        async function submitAdminAction(status) {
            const fileUrl = document.getElementById('file_url').value;
            
            try {
                const res = await callServer('updateRequestStatus', { 
                    id: selectedRequestId, 
                    status: status, 
                    fileUrl: fileUrl 
                });
                if (res.success) {
                    showToast(`Request ${status}`);
                    closeModal();
                    loadRequests();
                }
            } catch (err) {
                console.error(err);
                showToast('ไม่สามารถอัปเดตสถานะได้');
            }
        }

        // --- User Management Logic ---

        function switchAdminTab(tab) {
            const reqTab = document.getElementById('tab-requests');
            const userTab = document.getElementById('tab-users');
            const reqSection = document.getElementById('admin-section-requests');
            const userSection = document.getElementById('admin-section-users');

            if (tab === 'requests') {
                reqTab.classList.replace('btn-outline', 'btn-primary');
                userTab.classList.replace('btn-primary', 'btn-outline');
                reqSection.classList.remove('hidden');
                userSection.classList.add('hidden');
                loadRequests();
            } else {
                userTab.classList.replace('btn-outline', 'btn-primary');
                reqTab.classList.replace('btn-primary', 'btn-outline');
                userSection.classList.remove('hidden');
                reqSection.classList.add('hidden');
                loadUsers();
            }
        }

        async function loadUsers() {
            try {
                const res = await callServer('getAllUsers');
                adminUsersData = res.data; // เข้าถึงฟิลด์ data จาก Laravel Response
                renderUserManagementTable(adminUsersData);
            } catch (err) {
                showToast('โหลดข้อมูลผู้ใช้ไม่สำเร็จ');
            }
        }

        function renderUserManagementTable(users) {
            const tbody = document.getElementById('admin-users-table');
            tbody.innerHTML = users.map(u => `
                <tr>
                    <td>${u.id}</td>
                    <td><b>${u.full_name || '-'}</b></td>
                    <td>${u.username}</td>
                    <td><code style="color: #64748b;">********</code></td>
                    <td><span class="status-pill ${u.role === 'admin' ? 'status-approved' : 'status-processing'}">${u.role}</span></td>
                    <td style="font-size: 0.8rem;">${u.department || '-'}</td>
                    <td>
                        <button class="btn btn-sm" style="padding: 4px 8px; font-size: 0.7rem; background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3;" onclick="confirmResetPassword('${u.id}', '${u.full_name}')">
                            Reset 🔑
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        async function confirmResetPassword(id, name) {
            if (confirm(`คุณต้องการรีเซ็ตรหัสผ่านของ ${name} เป็น "mch1234" ใช่หรือไม่?`)) {
                try {
                    const res = await callServer('resetPassword', { id: id });
                    if (res.success) {
                        showToast(res.message);
                    } else {
                        showToast('เกิดข้อผิดพลาด: ' + res.message);
                    }
                } catch (err) {
                    showToast('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้');
                }
            }
        }

        function filterUserTable() {
            const query = document.getElementById('user-search').value.toLowerCase();
            const filtered = adminUsersData.filter(u => 
                (u.full_name || '').toLowerCase().includes(query) || 
                (u.username || '').toLowerCase().includes(query) ||
                (u.department || '').toLowerCase().includes(query)
            );
            renderUserManagementTable(filtered);
        }

        // --- CSV Import ---

        function openImportModal() {
            document.getElementById('import-modal').classList.remove('hidden');
        }

        function closeImportModal() {
            document.getElementById('import-modal').classList.add('hidden');
            document.getElementById('csv-file-input').value = '';
            document.getElementById('file-label-text').innerText = 'คลิกเพื่อเลือกไฟล์ CSV';
            document.getElementById('import-preview').classList.add('hidden');
            document.getElementById('confirm-import-btn').disabled = true;
            csvUploadData = null;
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            document.getElementById('file-label-text').innerText = file.name;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                parseCSV(text);
            };
            reader.readAsText(file);
        }

        function parseCSV(text) {
            const lines = text.split(/\r?\n/);
            const headers = lines[0].split(',').map(h => h.trim().toLowerCase());
            
            // Map headers to indices
            const mapping = {
                id: headers.indexOf('id'),
                username: headers.indexOf('username'),
                password: headers.indexOf('password'),
                role: headers.indexOf('role'),
                dept: headers.indexOf('dept'),
                full_name: headers.indexOf('full_name')
            };

            const result = [];
            const previewRows = [];

            for (let i = 1; i < lines.length; i++) {
                if (!lines[i].trim()) continue;
                const cols = lines[i].split(',').map(c => c.trim());
                if (cols.length < 2) continue;

                const user = [
                    cols[mapping.id] || '',
                    cols[mapping.username] || '',
                    cols[mapping.password] || '1234',
                    cols[mapping.role] || 'user',
                    cols[mapping.dept] || '',
                    cols[mapping.full_name] || ''
                ];
                
                result.push(user);
                if (previewRows.length < 5) {
                    previewRows.push(`<tr><td>${user[1]}</td><td>${user[5]}</td><td>${user[4]}</td></tr>`);
                }
            }

            if (result.length > 0) {
                csvUploadData = result;
                document.getElementById('preview-body').innerHTML = previewRows.join('');
                document.getElementById('import-preview').classList.remove('hidden');
                document.getElementById('confirm-import-btn').disabled = false;
                document.getElementById('confirm-import-btn').innerText = `ยืนยันนำเข้า (${result.length} รายการ)`;
            }
        }

        async function processImport() {
            if (!csvUploadData) return;
            
            const btn = document.getElementById('confirm-import-btn');
            btn.disabled = true;
            btn.innerText = 'กำลังส่งข้อมูล...';

            try {
                const res = await callServer('importUsers', { userData: csvUploadData });
                if (res.success) {
                    showToast(`นำเข้าสำเร็จ ${csvUploadData.length} รายการ`);
                    closeImportModal();
                    loadUsers();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + res.message);
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            } finally {
                btn.disabled = false;
            }
        }

        // UI Helpers
        async function updateDashboard() {
            try {
                const data = await callServer('getDashboardData');
                document.getElementById('stat-total').innerText = data.total;
                document.getElementById('stat-avg-rating').innerText = data.avgRating || '0.0';
                
                renderChart(data.labels, data.counts);
                renderFeedbackTable(data.recentFeedback || []);
            } catch (err) {
                console.error("Dashboard error:", err);
            }
        }

        function renderFeedbackTable(feedbackList) {
            const tbody = document.getElementById('admin-feedback-table');
            if (feedbackList.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">ยังไม่มีการประเมิน</td></tr>';
                return;
            }
            tbody.innerHTML = feedbackList.map(fb => `
                <tr>
                    <td style="font-size: 0.75rem;">${fb.timestamp}</td>
                    <td><span style="color: #f59e0b; font-weight: 700;">${'★'.repeat(fb.rating)}</span></td>
                    <td><div style="font-size: 0.85rem;">${fb.comment || '-'}</div></td>
                    <td><div style="font-size: 0.75rem;"><b>${fb.user}</b> (${fb.dept})</div></td>
                </tr>
            `).join('');
        }

        function renderChart(labels, counts) {
            const ctxBar = document.getElementById('deptChart').getContext('2d');
            const ctxPie = document.getElementById('deptPieChart').getContext('2d');
            
            if (deptChart) deptChart.destroy();
            if (deptPieChart) deptPieChart.destroy();
            
            const backgroundColors = labels.map((_, i) => CHART_COLORS[i % CHART_COLORS.length]);
            const borderColors = labels.map((_, i) => BORDER_COLORS[i % BORDER_COLORS.length]);

            // บาร์ชาร์ตแบบแยกสี
            deptChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'คำขอแยกรายแผนก',
                        data: counts,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'แนวโน้มยอดคำขอแยกตามแผนก', font: { size: 14 } }
                    },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });

            // พายชาร์ต
            deptPieChart = new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: backgroundColors,
                        borderColor: 'white',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } },
                        title: { display: true, text: 'สัดส่วนคำขอ (Percentage Allocation)', font: { size: 14 } }
                    }
                }
            });
        }

        function checkDeliveryOther() {
            const channel = document.getElementById('delivery_channel').value;
            const details = document.getElementById('delivery_channel_details');
            if (channel === 'อื่นๆ') {
                details.placeholder = "โปรดระบุช่องทางการส่งข้อมูลที่คุณต้องการ...";
                details.focus();
            } else {
                details.placeholder = "ระบุรายละเอียดเพิ่มเติม (เช่น ID Line, Email หรือหมายเหตุ)...";
            }
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        function toggleStatsDetail() {
            const isChecked = document.getElementById('check-stats').checked;
            const box = document.getElementById('stats-detail-box');
            if (isChecked) {
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
                document.getElementById('stats_details').value = '';
            }
        }
    </script>
</body>
</html>

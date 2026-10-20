<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบขอข้อมูลเวชระเบียน | Medical Record Request</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary: #6366f1; /* Vibrant Indigo */
            --primary-dark: #4f46e5;
            --secondary: #10b981; /* Emerald */
            --danger: #f43f5e; /* Rose */
            --warning: #f59e0b;
            --bg: #f5f3ff; /* Bright purple-tinted background */
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius: 12px;
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        /* --- Admin Dark Mode --- */
        body.admin-dark-mode {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --primary: #3b82f6;
            --primary-dark: #2563eb;
        }

        body.admin-dark-mode header {
            background: var(--card-bg);
            border-bottom-color: var(--border);
        }

        body.admin-dark-mode .card,
        body.admin-dark-mode .stat-card {
            background: var(--card-bg);
            border-color: var(--border);
        }

        body.admin-dark-mode table th {
            background-color: #334155;
            color: #f8fafc;
        }

        body.admin-dark-mode table td {
            border-bottom-color: var(--border);
            color: #e2e8f0;
        }
        
        body.admin-dark-mode input, 
        body.admin-dark-mode select,
        body.admin-dark-mode textarea {
            background-color: #0f172a;
            color: #f8fafc;
            border-color: var(--border);
        }
        
        /* Outline Buttons for Dark Mode */
        body.admin-dark-mode .btn {
            background-color: transparent !important;
        }
        
        body.admin-dark-mode .btn-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        body.admin-dark-mode .btn-primary:hover {
            background-color: var(--primary) !important;
            color: white;
        }

        body.admin-dark-mode .btn-outline {
            border: 1px solid var(--border);
            color: #cbd5e1;
        }
        body.admin-dark-mode .btn-outline:hover {
            background-color: var(--border) !important;
            color: white;
        }

        body.admin-dark-mode .btn-danger {
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        body.admin-dark-mode .btn-danger:hover {
            background-color: var(--danger) !important;
            color: white;
        }

        body.admin-dark-mode .btn-success {
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }
        body.admin-dark-mode .btn-success:hover {
            background-color: var(--secondary) !important;
            color: white;
        }

        body.admin-dark-mode .status-pending { background: #78350f; color: #fef3c7; }
        body.admin-dark-mode .status-processing { background: #1e3a8a; color: #dbeafe; }
        body.admin-dark-mode .status-approved { background: #14532d; color: #dcfce7; }
        body.admin-dark-mode .status-rejected { background: #7f1d1d; color: #fee2e2; }

        body.admin-dark-mode #brand-header { color: #60a5fa; }
        body.admin-dark-mode .stat-value { color: #60a5fa; }
        body.admin-dark-mode .role-badge { background: #334155 !important; color: #cbd5e1 !important; }
        body.admin-dark-mode .section-title span { background: #60a5fa; }
        body.admin-dark-mode .history-title svg { stroke: #60a5fa; }

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
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* --- Layout --- */
        .container {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .hidden {
            display: none !important;
        }

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
            color: #1d4ed8;
            /* Blue from image */
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
            background: #15803d;
            /* Green from image submit button */
            color: white;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #166534;
        }

        .btn-logout {
            background: transparent;
            border: 1px solid #f87171;
            color: #ef4444;
            padding: 0.3rem 1rem;
            border-radius: 99px;
            font-size: 0.8rem;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #fee2e2;
        }

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
            .column-grid {
                grid-template-columns: repeat(2, 1fr);
            }
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
            .date-grid {
                grid-template-columns: 1fr;
            }
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

        .login-container {
            display: flex;
            gap: 2rem;
            align-items: stretch;
            width: 100%;
            max-width: 900px;
            flex-direction: row;
            justify-content: center;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
                align-items: center;
            }
        }

        .login-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.8);
            flex: 1;
        }

        .telegram-guide-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            width: 100%;
            max-width: 450px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex: 1;
            text-align: left;
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

        input,
        select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            outline: none;
            transition: border-color 0.2s;
            font-size: 1rem;
        }

        input:focus,
        select:focus {
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
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
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

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
        }

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

        #toast.show {
            transform: translateY(0);
        }

        /* --- Modal --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .request-summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 110px 1fr;
            gap: 8px 12px;
        }

        .summary-label {
            font-weight: 600;
            color: #64748b;
        }

        .summary-val {
            color: #1e293b;
            font-weight: 500;
        }

        .summary-val.pre-wrap {
            white-space: pre-wrap;
        }

        .summary-val.highlight-val {
            color: #ef4444 !important;
            font-weight: 700 !important;
        }

        /* Dark Mode overrides */
        body.admin-dark-mode .request-summary-box {
            background: #0f172a;
            border-color: #334155;
        }

        body.admin-dark-mode .summary-label {
            color: #94a3b8;
        }

        body.admin-dark-mode .summary-val {
            color: #f8fafc;
        }

        body.admin-dark-mode .summary-val.highlight-val {
            color: #fca5a5 !important;
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
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .charts-container {
                grid-template-columns: 1fr;
            }
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

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2.5rem;
            color: #e2e8f0;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating label:hover,
        .star-rating label:hover~label,
        .star-rating input:checked~label {
            color: #f59e0b;
        }

        /* Premium File Attachment List CSS */
        .file-list {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #334155;
            transition: all 0.2s;
        }

        body.admin-dark-mode .file-item {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }

        .file-item:hover {
            border-color: var(--primary);
            background: #f8fafc;
        }

        body.admin-dark-mode .file-item:hover {
            background: #0f172a;
        }

        .file-name-link {
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            color: inherit;
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .file-name-link:hover {
            color: var(--primary);
        }

        .file-delete-btn {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
        }

        .file-delete-btn:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        body.admin-dark-mode .file-delete-btn:hover {
            background: #7f1d1d;
            color: #fca5a5;
        }

        /* --- PDF Template Custom & Isolation Styles --- */
        #pdf-template {
            color: #000000 !important;
            background-color: #ffffff !important;
            box-sizing: border-box !important;
            width: 210mm !important;
            min-height: 295mm !important;
            height: auto !important;
        }

        #pdf-template * {
            color: #000000 !important;
            border-color: #cbd5e1 !important;
            box-sizing: border-box !important;
        }
        #pdf-template h1,
        #pdf-template h2,
        #pdf-template h3,
        #pdf-template p,
        #pdf-template span,
        #pdf-template div {
            color: #1e293b !important;
        }
        #pdf-template table {
            border-collapse: collapse !important;
            width: 100% !important;
            background-color: #ffffff !important;
            margin-bottom: 10px !important;
        }
        #pdf-template table th,
        #pdf-template table td {
            color: #000000 !important;
            border: 1px solid #cbd5e1 !important;
            background-color: #ffffff !important;
            padding: 5px 8px !important;
            font-size: 13.5px !important;
            line-height: 1.35 !important;
            text-align: left !important;
        }
        #pdf-template table tr td:first-child {
            background-color: #f1f5f9 !important;
            font-weight: 600 !important;
            width: 30% !important;
            color: #334155 !important;
        }
    </style>
</head>

<body>

    <!-- Header (Hidden initially) -->
    <header id="app-header" class="hidden">
        <div class="container header-content">
            <div id="brand-header" class="brand">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                </svg>
                สร้างคำขอข้อมูลใหม่
            </div>
            <div class="user-info">
                สวัสดี, <span id="display-name" style="font-weight: 700;">Username</span>
                <span id="display-dept" class="role-badge" style="background: #f1f5f9; color: #475569;">Dept</span>
                <button id="btn-switch-role" class="btn btn-outline btn-sm hidden" style="font-size: 0.75rem; padding: 4px 8px; border-color: var(--primary); color: var(--primary); font-weight: bold;" onclick="toggleAdminMode()">สลับโหมด</button>
                <button class="btn btn-outline btn-sm"
                    style="font-size: 0.75rem; padding: 4px 8px; border-color: #cbd5e1; color: #64748b;"
                    onclick="openChangePasswordModal()">เปลี่ยนรหัสผ่าน</button>
                <button class="btn btn-logout" onclick="logout()">ออกจากระบบ</button>
            </div>
        </div>
    </header>

    <!-- Login View -->
    <main id="login-view">
        <div class="login-container fade-in">
            <!-- Login Card -->
            <div class="login-card">
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
                    <div id="login-error"
                        style="color: var(--danger); font-size: 0.8rem; margin-top: 1rem; text-align: center;"
                        class="hidden"></div>

                    <div
                        style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--border); padding-top: 1rem;">
                        <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.5rem;">
                            ยังไม่มีบัญชีผู้ใช้?</p>
                        <button type="button" class="btn"
                            style="background: white; border: 1px solid var(--primary); color: var(--primary); width: 100%;"
                            onclick="openRegisterModal()">
                            ลงทะเบียนใหม่
                        </button>
                    </div>
                </form>
                <p style="text-align: center; font-size: 0.75rem; color: var(--text-muted); margin-top: 2rem;">
                    Internal System for Hospital Staff Only
                </p>
            </div>

            <!-- Telegram Guide Card -->
            <div class="telegram-guide-card">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem;">
                        <div style="background: #0088cc; padding: 10px; border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; color: white; flex-shrink: 0;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                            </svg>
                        </div>
                        <h3 style="margin: 0; color: #1e293b; font-size: 1.1rem; font-weight: 700;">แจ้งเตือนผ่าน Telegram</h3>
                    </div>
                    
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.5;">
                        รับไฟล์ผลลัพธ์เวชระเบียน หรือแจ้งเตือนทันทีเมื่อมีการอนุมัติ/ปฏิเสธคำขอของท่านผ่านบอท Telegram:
                    </p>
                    
                    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                        <div style="display: flex; gap: 10px; align-items: flex-start;">
                            <div style="background: #e0f2fe; color: #0284c7; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; flex-shrink: 0; margin-top: 1px;">1</div>
                            <div style="font-size: 0.8rem; color: #334155; line-height: 1.4;">
                                กดปุ่ม <b>"แอดบอท Telegram"</b> ด้านล่าง หรือค้นหาชื่อบอท <b class="tg-bot-name">@YOUR_BOT_USERNAME</b>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: flex-start;">
                            <div style="background: #e0f2fe; color: #0284c7; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; flex-shrink: 0; margin-top: 1px;">2</div>
                            <div style="font-size: 0.8rem; color: #334155; line-height: 1.4;">
                                กดปุ่ม <b>"Start"</b> (เริ่มใช้งาน) ในแชทของบอท
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: flex-start;">
                            <div style="background: #e0f2fe; color: #0284c7; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem; flex-shrink: 0; margin-top: 1px;">3</div>
                            <div style="font-size: 0.8rem; color: #334155; line-height: 1.4;">
                                เมื่อระบบทำการ <b>ส่งไฟล์</b> หรือ <b>ปฏิเสธคำขอ</b> บอทจะแจ้งเตือนและส่งไฟล์หาท่านทันที!
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem; display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                    <a href="https://t.me/YOUR_BOT_USERNAME" target="_blank" class="btn tg-bot-link" style="background: #0088cc; color: white; width: 100%; font-weight: 600; text-decoration: none; font-size: 0.85rem; padding: 0.5rem 1rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                        แอดบอท Telegram ที่นี่
                    </a>
                    <div style="font-size: 0.7rem; color: var(--text-muted); text-align: center;">
                        * สแกน QR Code เพื่อเชื่อมต่อผ่านโทรศัพท์มือถือ
                    </div>
                    <div style="border: 1px solid var(--border); padding: 6px; border-radius: 10px; background: white; display: flex; align-items: center; justify-content: center; width: 110px; height: 110px; box-shadow: var(--shadow);">
                        <img id="tg-bot-qr" src="https://quickchart.io/qr?text=https%3A%2F%2Ft.me%2FYOUR_BOT_USERNAME&size=100" alt="Telegram QR" style="width: 100%; height: 100%; object-fit: contain;">
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- User Dashboard View -->
    <main id="user-view" class="container hidden fade-in">
        <!-- User Navigation Tabs -->
        <div style="margin-bottom: 1.5rem; display: flex; gap: 10px; flex-wrap: wrap;">
            <button id="user-tab-dashboard" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary);" onclick="switchUserTab('dashboard')">
                📊 แดชบอร์ด
            </button>
            <button id="user-tab-requests" class="btn btn-primary" onclick="switchUserTab('requests')">
                📝 สร้างคำขอ / ประวัติ
            </button>
            <button id="user-tab-feedback" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary);" onclick="switchUserTab('feedback')">
                ⭐ ข้อเสนอแนะ
            </button>
        </div>

        <div id="user-section-requests">
            <div class="alert-box">
                <span> | * การขอข้อมูลควรขอก่อนวันที่ต้องการข้อมูลอย่างน้อย 3 วัน</span>
            </div>

        <form id="request-form" class="card" style="margin-bottom: 2rem; padding: 2rem;">
            <!-- ตอนที่ 1: ข้อมูลทั่วไป -->
            <div class="section-title">
                <span style="background: #1d4ed8; color: white; padding: 2px 10px; border-radius: 4px;">1</span> ตอนที่
                1: ข้อมูลทั่วไป
            </div>
            <div
                style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 1.5rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
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
                <span style="background: #1d4ed8; color: white; padding: 2px 10px; border-radius: 4px;">2</span> ตอนที่
                2: ประเภทการขอข้อมูล
            </div>
            <div
                style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="field-label">ปีงบประมาณ:</label>
                    <input type="text" id="fiscal_year" placeholder="เช่น 2567" required>
                </div>
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="field-label">ประเภทรายการที่ต้องการ:</label>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div>
                            <label class="checkbox-item">
                                <input type="checkbox" name="type" value="Statistics" id="check-stats"
                                    onchange="toggleStatsDetail()"> ๑. ขอจำนวนสรุป (Statistics)
                            </label>
                            <div id="stats-detail-box" class="hidden" style="margin-top: 10px; margin-left: 24px;">
                                <label
                                    style="font-size: 0.8rem; color: #1e40af; font-weight: 600;">ระบุข้อมูลสรุปที่ต้องการ
                                    (เช่น จำนวนผู้ป่วยแยกตามโรค, อายุ, ฯลฯ):</label>
                                <textarea id="stats_details"
                                    placeholder="เช่น ขอจำนวนผู้รับบริการแยกตามกลุ่มอายุและสิทธิ์การรักษา..."
                                    style="border-color: #3b82f6; background: #eff6ff;"></textarea>
                            </div>
                        </div>
                        <label class="checkbox-item">
                            <input type="checkbox" name="type" value="Patient List" checked> ๒. ขอรายชื่อผู้ป่วย
                            (Patient List)
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
                <span style="background: #1d4ed8; color: white; padding: 2px 10px; border-radius: 4px;">3</span> ตอนที่
                3: ลักษณะข้อมูล
            </div>
            <div class="checkbox-group-wrapper" style="background: #f8fafc;">
                <label class="field-label" style="color: #3b82f6;">เลือกคอลัมน์ข้อมูลที่ต้องการ:</label>
                <div class="column-grid">
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="HN"> HN</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="AN"> AN</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="ชื่อ-นามสกุล">
                        ชื่อ-นามสกุล</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="CID"> CID</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="อายุ/เพศ"> อายุ/เพศ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="สิทธิ"> สิทธิ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="ICD-10"> ICD-10</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="ICD-9"> ICD-9</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="วันที่รับบริการ">
                        วันที่รับบริการ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="เบอร์โทร"> เบอร์โทร</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="คลินิก/หน่วยงานที่รับบริการ">
                        คลินิก/หน่วยงานที่รับบริการ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="รายการยาที่ได้รับ">
                        รายการยาที่ได้รับ</label>
                    <label class="checkbox-item"><input type="checkbox" name="cols" value="ค่ารักษาพยาบาล">
                        ค่ารักษาพยาบาล</label>
                </div>
                <label class="field-label" style="font-size: 0.8rem; margin-top: 1rem;">ระบุรายการอื่น /
                    ระบุรายละเอียดเพิ่มเติม:</label>
                <textarea id="additional_details" placeholder="ระบุคอลัมน์หรือเงื่อนไขพิเศษเพิ่มเติมที่นี่..."
                    style="margin-bottom: 1.5rem;"></textarea>

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
                        <input type="text" id="delivery_channel_details"
                            placeholder="ระบุรายละเอียดเพิ่มเติม (เช่น ID Line, Email หรือช่องทางอื่นที่ต้องการ)..."
                            style="border-color: #15803d; background: #f0fdf4;">
                    </div>
                </div>
            </div>

            <div class="form-group" style="background: #f0fdf4; padding: 1rem; border-radius: 8px; border: 1px solid #bbf7d0; margin-top: 1.5rem;">
                <label class="checkbox-item" style="color: #166534; font-weight: 600;">
                    <input type="checkbox" id="anonymize_data">
                    🛡️ [ทางเลือก] ต้องการให้ทำการปิดบังข้อมูลระบุตัวตน (De-identification) เช่น ซ่อนชื่อ-สกุล, CID ก่อนส่งมอบข้อมูล
                </label>
            </div>

            <div class="form-group"
                style="background: #fff1f2; padding: 1rem; border-radius: 8px; border: 1px solid #fecaca; margin-top: 1.5rem;">
                <label class="checkbox-item" style="color: #be123c; font-weight: 600;">
                    <input type="checkbox" id="pdpa_consent" required>
                    ข้าพเจ้ายืนยันว่าการขอข้อมูลนี้เป็นไปเพื่อวัตถุประสงค์ในการปฏิบัติงาน/วิจัยเท่านั้น
                    และจะรักษาความลับของข้อมูลส่วนบุคคลตามกฎหมาย PDPA อย่างเคร่งครัด
                </label>
            </div>

            <input type="hidden" id="edit_request_id" value="">
            <div style="display: flex; gap: 10px; margin-top: 2rem;">
                <button type="button" class="btn btn-outline hidden" style="flex: 1; padding: 1rem;" id="cancel-edit-btn" onclick="cancelEdit()">
                    ยกเลิกการแก้ไข
                </button>
                <button type="submit" class="btn btn-primary" style="flex: 2; padding: 1rem;" id="submit-request-btn">
                    ยืนยันส่งคำขอข้อมูล
                </button>
            </div>
        </form>

        <!-- History Table Section -->
        <div class="card" style="padding: 1.5rem;">
            <div class="history-title">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
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
        </div>
        <div id="user-section-dashboard" class="hidden"></div>
        <div id="user-section-feedback" class="hidden"></div>
    </main>

    <!-- Admin Dashboard View -->
    <main id="admin-view" class="container hidden fade-in">
        <!-- Admin Navigation Tabs -->
        <div style="margin-bottom: 1.5rem; display: flex; gap: 10px; flex-wrap: wrap;">
            <button id="tab-dashboard" class="btn btn-primary" onclick="switchAdminTab('dashboard')">
                📊 แดชบอร์ด
            </button>
            <button id="tab-requests" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary);" onclick="switchAdminTab('requests')">
                📑 คำขอข้อมูล
            </button>
            <button id="tab-feedback" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary);" onclick="switchAdminTab('feedback')">
                ⭐ ข้อเสนอแนะ
            </button>
            <button id="tab-login-history" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary);" onclick="switchAdminTab('login-history')">
                🕒 ประวัติเข้าใช้งาน
            </button>
            <button id="tab-audit-log" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary);" onclick="switchAdminTab('audit-log')">
                🛡️ Audit Log
            </button>
            <button id="tab-users" class="btn btn-outline" style="border: 1px solid var(--primary); color: var(--primary);" onclick="switchAdminTab('users')">
                👥 จัดการผู้ใช้
            </button>
        </div>

        <div id="admin-section-dashboard">
            <div id="shared-dashboard-content">
                <!-- Dashboard Filters -->
            <div class="card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
                <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 1; min-width: 150px;">
                        <label class="field-label" style="margin-bottom: 5px;">ปีงบประมาณ</label>
                        <input type="text" id="dash-filter-fiscal" placeholder="เช่น 2567">
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <label class="field-label" style="margin-bottom: 5px;">ตั้งแต่วันที่</label>
                        <input type="date" id="dash-filter-start">
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <label class="field-label" style="margin-bottom: 5px;">ถึงวันที่</label>
                        <input type="date" id="dash-filter-end">
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="updateDashboard()">กรองข้อมูล</button>
                        <button class="btn btn-outline" onclick="clearDashboardFilters()">ล้าง</button>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div id="stat-total" class="stat-value">0</div>
                    <div class="stat-label">จำนวนคำขอทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div id="stat-avg-rating" class="stat-value">0.0</div>
                    <div class="stat-label">คะแนนความพึงพอใจเฉลี่ย</div>
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
            </div>
        </div>

        <div id="admin-section-feedback" class="hidden">
            <div id="shared-feedback-content">
                <div class="card" style="margin-bottom: 2rem;">
                <div class="card-header">
                    <h3 class="card-title">ข้อเสนอแนะและความคิดเห็น</h3>
                </div>
                <div class="table-container" style="max-height: 500px; overflow-y: auto;">
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
            </div>
        </div>

        <div id="admin-section-requests" class="hidden">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">จัดการคำขอข้อมูลเวชระเบียน</h3>
                    <div>
                        <button class="btn btn-outline" style="border-color: #10b981; color: #10b981;" onclick="exportRequestsToCSV()">📥 ดาวน์โหลด CSV</button>
                        <button class="btn btn-outline" onclick="loadRequests()">รีเฟรชข้อมูล (Refresh)</button>
                    </div>
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
        
        <div id="admin-section-login-history" class="hidden">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">ประวัติการเข้าใช้งานระบบ</h3>
                    <button class="btn btn-outline" onclick="loadLoginHistory()">รีเฟรชข้อมูล</button>
                </div>
                <div class="table-container" style="max-height: 600px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ชื่อผู้ใช้</th>
                                <th>IP Address</th>
                                <th>Browser / OS</th>
                                <th>เวลาเข้าสู่ระบบ</th>
                            </tr>
                        </thead>
                        <tbody id="admin-login-history-table"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="admin-section-audit-log" class="hidden">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">บันทึกประวัติการกระทำ (Audit Log)</h3>
                    <button class="btn btn-outline" onclick="loadAuditLog()">รีเฟรชข้อมูล</button>
                </div>
                <div class="table-container" style="max-height: 600px; overflow-y: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>เวลา</th>
                                <th>ผู้ใช้งาน</th>
                                <th>การกระทำ</th>
                                <th>รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody id="admin-audit-log-table"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Admin Users Section -->
        <div id="admin-section-users" class="hidden">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">User Management</h3>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="user-search" placeholder="ค้นหาตามชื่อ/Username..."
                            style="width: 250px; padding: 0.5rem;" oninput="filterUserTable()">
                        <button class="btn btn-primary" onclick="openImportModal()">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
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
                                <th>จัดการ</th>
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
        <div class="card fade-in"
            style="max-width: 600px; width: 95%; padding: 2rem; max-height: 90vh; overflow-y: auto;">
            <h3 style="margin-bottom: 0.5rem; color: #1e293b;" id="modal-req-id">จัดการคำขอ</h3>

            <!-- ส่วนแสดงรายละเอียดที่ User ขอมา -->
            <div id="request-detail-summary" class="request-summary-box">
                <div class="summary-grid">
                    <span class="summary-label">ผู้ขอ:</span> <span id="view-user" class="summary-val">-</span>
                    <span class="summary-label">ประเภทข่าว:</span> <span id="view-type" class="summary-val">-</span>
                    <span class="summary-label">คอลัมน์:</span> <span id="view-cols" class="summary-val">-</span>
                    <span class="summary-label">ช่วงข้อมูล:</span> <span id="view-dates" class="summary-val">-</span>
                    <span class="summary-label">วันที่ต้องการ:</span> <span id="view-target" class="summary-val highlight-val">-</span>
                    <span class="summary-label">รายละเอียด:</span> <span id="view-details" class="summary-val pre-wrap">-</span>
                    <span class="summary-label">วัตถุประสงค์:</span> <span id="view-purpose" class="summary-val pre-wrap">-</span>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="field-label">อัปโหลดไฟล์ผลลัพธ์ (รองรับทุกรูปแบบไฟล์)</label>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="file" id="upload_file" style="display: none;" onchange="handleAdminFileSelect(event)" multiple>
                    <button type="button" class="btn btn-outline" style="flex: 1; border-color: var(--primary); color: var(--primary); padding: 0.5rem 1rem;" onclick="document.getElementById('upload_file').click()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        เลือกไฟล์...
                    </button>
                    <span id="admin-file-name" style="flex: 2; font-size: 0.85rem; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">ยังไม่ได้เลือกไฟล์</span>
                </div>
                <div id="admin-existing-files"></div>
                <div id="admin-new-files"></div>
                <div id="upload-progress" class="hidden" style="margin-top: 8px; font-size: 0.85rem; color: var(--primary); font-weight: 500;">
                    กำลังอัปโหลด... 0%
                </div>
            </div>

            <div class="form-group">
                <label class="field-label">แนบลิงก์ไฟล์ผลลัพธ์ (หรือระบบจะใส่ให้อัตโนมัติเมื่ออัปโหลดไฟล์)</label>
                <input type="url" id="file_url" placeholder="https://drive.google.com/...">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 2rem;">
                <button class="btn btn-primary" style="background: #3b82f6; grid-column: span 2;"
                    onclick="submitAdminAction('Processing')">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                    รับงาน (Processing)
                </button>
                <button class="btn btn-primary" onclick="submitAdminAction('Approved')">ส่งไฟล์และอนุมัติ</button>
                <button class="btn btn-danger" onclick="submitAdminAction('Rejected')">ยกเลิกงาน/ปฏิเสธ</button>
                <button class="btn btn-outline" style="grid-column: span 2; margin-top: 5px;"
                    onclick="closeModal()">ปิดหน้าต่าง</button>
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
                    <button type="button" class="btn btn-outline" style="flex: 1;"
                        onclick="closeRegisterModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;"
                        id="reg-submit-btn">ลงทะเบียน</button>
                </div>
                <div id="reg-error"
                    style="color: var(--danger); font-size: 0.8rem; margin-top: 1rem; text-align: center;"
                    class="hidden"></div>
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
                    <button type="button" class="btn btn-outline" style="flex: 1;"
                        onclick="closeChangePasswordModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;" id="pwd-submit-btn">บันทึก</button>
                </div>
                <div id="pwd-error"
                    style="color: var(--danger); font-size: 0.8rem; margin-top: 1rem; text-align: center;"
                    class="hidden"></div>
            </form>
        </div>
    </div>

    <!-- Admin Reset Password Modal -->
    <div id="admin-reset-password-modal" class="modal-overlay hidden">
        <div class="card fade-in" style="max-width: 400px; width: 95%; padding: 2rem;">
            <h3 style="margin-bottom: 0.5rem; color: #1e293b;" id="reset-pwd-title">รีเซ็ตรหัสผ่านผู้ใช้</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem;">กำหนดรหัสผ่านใหม่สำหรับผู้ใช้นี้</p>
            <form id="admin-reset-pwd-form" onsubmit="submitAdminResetPassword(event)">
                <input type="hidden" id="reset-target-username">
                <div class="form-group">
                    <label>รหัสผ่านใหม่</label>
                    <input type="password" id="admin-new-password" placeholder="ตั้งรหัสผ่านใหม่ (อย่างน้อย 4 ตัวอักษร)" required minlength="4">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;"
                        onclick="closeAdminResetPasswordModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;" id="admin-reset-pwd-btn">บันทึกรหัสผ่านใหม่</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Admin Edit User Modal -->
    <div id="admin-edit-user-modal" class="modal-overlay hidden">
        <div class="card fade-in" style="max-width: 400px; width: 95%; padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem; color: #1e293b;" id="edit-user-title">แก้ไขผู้ใช้</h3>
            <form id="admin-edit-user-form" onsubmit="submitAdminEditUser(event)">
                <input type="hidden" id="edit-target-username">
                <div class="form-group">
                    <label>ชื่อ-นามสกุล</label>
                    <input type="text" id="edit-user-fullname" required>
                </div>
                <div class="form-group">
                    <label>แผนก</label>
                    <input type="text" id="edit-user-dept" required>
                </div>
                <div class="form-group">
                    <label>บทบาท</label>
                    <select id="edit-user-role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;"
                        onclick="closeAdminEditUserModal()">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;" id="admin-edit-user-btn">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div id="feedback-modal" class="modal-overlay hidden">
        <div class="card fade-in" style="max-width: 500px; width: 95%; padding: 2rem;">
            <h3 style="margin-bottom: 0.5rem; color: #1e293b; text-align: center;">ประเมินความพึงพอใจการให้บริการ</h3>
            <p style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 1.5rem; text-align: center;">
                รหัสคำขอ: <span id="feedback-req-id" style="font-weight: 700; color: var(--primary);"></span></p>

            <form id="feedback-form">
                <div
                    style="margin-bottom: 1.5rem; background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">

                    <!-- Criteria 1 -->
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label style="margin: 0; font-weight: 600; color: #334155; font-size: 0.9rem;">1.
                            ความรวดเร็วในการได้รับข้อมูล</label>
                        <div class="star-rating" style="margin: 0; gap: 5px; font-size: 1.5rem;">
                            <input type="radio" id="s_star5" name="speedRating" value="5" required><label
                                for="s_star5">★</label>
                            <input type="radio" id="s_star4" name="speedRating" value="4"><label for="s_star4">★</label>
                            <input type="radio" id="s_star3" name="speedRating" value="3"><label for="s_star3">★</label>
                            <input type="radio" id="s_star2" name="speedRating" value="2"><label for="s_star2">★</label>
                            <input type="radio" id="s_star1" name="speedRating" value="1"><label for="s_star1">★</label>
                        </div>
                    </div>

                    <!-- Criteria 2 -->
                    <div
                        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <label style="margin: 0; font-weight: 600; color: #334155; font-size: 0.9rem;">2.
                            ความถูกต้องครบถ้วนของข้อมูล</label>
                        <div class="star-rating" style="margin: 0; gap: 5px; font-size: 1.5rem;">
                            <input type="radio" id="a_star5" name="accuracyRating" value="5" required><label
                                for="a_star5">★</label>
                            <input type="radio" id="a_star4" name="accuracyRating" value="4"><label
                                for="a_star4">★</label>
                            <input type="radio" id="a_star3" name="accuracyRating" value="3"><label
                                for="a_star3">★</label>
                            <input type="radio" id="a_star2" name="accuracyRating" value="2"><label
                                for="a_star2">★</label>
                            <input type="radio" id="a_star1" name="accuracyRating" value="1"><label
                                for="a_star1">★</label>
                        </div>
                    </div>

                    <!-- Criteria 3 -->
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <label style="margin: 0; font-weight: 600; color: #334155; font-size: 0.9rem;">3.
                            การให้บริการของเจ้าหน้าที่</label>
                        <div class="star-rating" style="margin: 0; gap: 5px; font-size: 1.5rem;">
                            <input type="radio" id="sv_star5" name="serviceRating" value="5" required><label
                                for="sv_star5">★</label>
                            <input type="radio" id="sv_star4" name="serviceRating" value="4"><label
                                for="sv_star4">★</label>
                            <input type="radio" id="sv_star3" name="serviceRating" value="3"><label
                                for="sv_star3">★</label>
                            <input type="radio" id="sv_star2" name="serviceRating" value="2"><label
                                for="sv_star2">★</label>
                            <input type="radio" id="sv_star1" name="serviceRating" value="1"><label
                                for="sv_star1">★</label>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="text-align: left;">
                    <label style="font-weight: 600;">ข้อเสนอแนะเพิ่มเติม</label>
                    <textarea id="feedback-comment" placeholder="ความรู้สึกหลังใช้งาน หรือสิ่งที่ต้องการให้ปรับปรุง..."
                        style="min-height: 80px;"></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-outline" style="flex: 1;"
                        onclick="closeFeedbackModal()">ไว้ภายหลัง</button>
                    <button type="submit" class="btn btn-primary" style="flex: 2;"
                        id="feedback-submit-btn">ส่งผลการประเมิน</button>
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

            <div
                style="border: 2px dashed var(--border); padding: 2rem; text-align: center; border-radius: 12px; margin-bottom: 1.5rem;">
                <input type="file" id="csv-file-input" accept=".csv" style="display: none;"
                    onchange="handleFileSelect(event)">
                <label for="csv-file-input" style="cursor: pointer;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"
                        style="margin-bottom: 1rem;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="9" y1="15" x2="15" y2="15"></line>
                    </svg>
                    <div id="file-label-text">คลิกเพื่อเลือกไฟล์ CSV</div>
                </label>
            </div>

            <div id="import-preview" class="hidden"
                style="margin-bottom: 1.5rem; max-height: 150px; overflow-y: auto; background: #f8fafc; padding: 0.5rem; font-size: 0.75rem; border-radius: 8px;">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Name</th>
                            <th>Dept</th>
                        </tr>
                    </thead>
                    <tbody id="preview-body"></tbody>
                </table>
            </div>

            <div style="display: flex; gap: 10px;">
                <button class="btn btn-outline" style="flex: 1;" onclick="closeImportModal()">ยกเลิก</button>
                <button id="confirm-import-btn" class="btn btn-primary" style="flex: 2;" disabled
                    onclick="processImport()">เริ่มนำเข้าข้อมูล</button>
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
        const API_URL = "api.php";

        // Global State
        let currentUser = null;
        let selectedRequestId = null;
        let adminRequestsData = [];
        let adminUsersData = []; // เพิ่มตัวแปรเก็บข้อมูลรายชื่อผู้ใช้
        let deptChart = null;
        let existingFiles = [];  // คอลเลกชันไฟล์ที่แนบอยู่เดิม (จากฐานข้อมูล)
        let selectedFiles = [];  // คอลเลกชันไฟล์ใหม่ที่เลือกจาก admin
        let deptPieChart = null;
        let csvUploadData = null; // เก็บข้อมูล CSV ที่จะอัปโหลด

        const STATUS_TH = {
            'Pending': 'รอดำเนินการ',
            'Processing': 'กำลังดำเนินการ',
            'Approved': 'อนุมัติ/เสร็จสิ้น',
            'Rejected': 'ยกเลิกคำขอ'
        };

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
        document.addEventListener('DOMContentLoaded', async () => {
            document.getElementById('login-form').addEventListener('submit', handleLogin);
            document.getElementById('request-form').addEventListener('submit', handleRequestSubmit);
            document.getElementById('register-form').addEventListener('submit', handleRegister);
            document.getElementById('feedback-form').addEventListener('submit', handleFeedbackSubmit);
            document.getElementById('password-form').addEventListener('submit', handleChangePassword);

            // Set min date for target_date to 3 days in the future
            const targetDateInput = document.getElementById('target_date');
            if (targetDateInput) {
                const minDate = new Date();
                minDate.setDate(minDate.getDate() + 3);
                const yyyy = minDate.getFullYear();
                const mm = String(minDate.getMonth() + 1).padStart(2, '0');
                const dd = String(minDate.getDate()).padStart(2, '0');
                targetDateInput.min = `${yyyy}-${mm}-${dd}`;
            }

            // Check Session
            try {
                const res = await callServer('checkSession');
                
                // อัปเดตลิงก์ ชื่อบอท และ QR Code ของ Telegram แบบไดนามิกอัตโนมัติ
                if (res.botUsername && res.botUsername !== 'YOUR_BOT_USERNAME') {
                    const tgLinks = document.querySelectorAll('.tg-bot-link');
                    tgLinks.forEach(link => {
                        link.href = `https://t.me/${res.botUsername}`;
                    });
                    const tgNames = document.querySelectorAll('.tg-bot-name');
                    tgNames.forEach(span => {
                        span.innerText = `@${res.botUsername}`;
                    });
                    const tgQr = document.getElementById('tg-bot-qr');
                    if (tgQr) {
                        tgQr.src = `https://quickchart.io/qr?text=https%3A%2F%2Ft.me%2F${res.botUsername}&size=100`;
                    }
                }

                if (res.success) {
                    currentUser = res.user;
                    showView(currentUser.role === 'admin' ? 'admin' : 'user');
                    loadRequests();
                }
            } catch (err) {
                console.error("Session check failed", err);
            }
        });

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
            const speedRating = document.querySelector('input[name="speedRating"]:checked').value;
            const accuracyRating = document.querySelector('input[name="accuracyRating"]:checked').value;
            const serviceRating = document.querySelector('input[name="serviceRating"]:checked').value;
            const comment = document.getElementById('feedback-comment').value;
            const btn = document.getElementById('feedback-submit-btn');

            btn.disabled = true;
            btn.innerHTML = 'กำลังส่ง...';

            try {
                const res = await callServer('submitFeedback', {
                    formData: {
                        requestId,
                        speedRating,
                        accuracyRating,
                        serviceRating,
                        comment,
                        user: currentUser.username,
                        dept: currentUser.dept
                    }
                });
                if (res.success) {
                    showToast('ขอบคุณสำหรับการประเมินครับ');
                    closeFeedbackModal();
                    loadRequests(); // อัปเดตตารางประวัติทันทีเพื่อเปลี่ยนปุ่มประเมินเป็นสถานะประเมินแล้ว
                } else {
                    showToast(res.message || 'เกิดข้อผิดพลาดในการส่งประเมิน');
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
         * ฟังก์ชันหลักสำหรับเรียกคุยกับ Backend
         */
        async function callServer(action, data = {}) {
            if (API_URL) {
                // สำหรับรันบน Server ภายนอก (ใช้ Fetch API)
                try {
                    const response = await fetch(API_URL, {
                        method: 'POST',
                        body: JSON.stringify({ action: action, data: data })
                    });
                    const result = await response.json();
                    return result;
                } catch (e) {
                    console.error("API Error: ", e);
                    throw e;
                }
            } else {
                // สำหรับรันบน Google Apps Script ปกติ (ใช้ google.script.run)
                return new Promise((resolve, reject) => {
                    google.script.run
                        .withSuccessHandler(resolve)
                        .withFailureHandler(reject)[action](...Object.values(data));
                });
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
            const editId = document.getElementById('edit_request_id').value;

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

            if (document.getElementById('anonymize_data') && document.getElementById('anonymize_data').checked) {
                formData.details += (formData.details ? '\n' : '') + '[ต้องการให้ปิดบังข้อมูลระบุตัวตน (De-identification)]';
            }

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
                let res;
                if (editId) {
                    formData.id = editId;
                    res = await callServer('updateRequest', { formData });
                } else {
                    res = await callServer('createRequest', { formData });
                }
                
                btn.disabled = false;
                btn.innerHTML = editId ? 'บันทึกการแก้ไข' : 'ยืนยันส่งคำขอข้อมูล';
                
                if (res.success) {
                    showToast(editId ? 'บันทึกการแก้ไขเรียบร้อยแล้ว' : 'ส่งคำขอเรียบร้อยแล้ว');
                    cancelEdit();
                    loadRequests();
                } else {
                    showToast(res.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = editId ? 'บันทึกการแก้ไข' : 'ยืนยันส่งคำขอข้อมูล';
                showToast('เกิดข้อผิดพลาดในการส่งข้อมูล');
            }
        }

        function editUserRequest(id) {
            const req = userRequestsData.find(r => r.id === id);
            if (!req) return;
            
            document.getElementById('edit_request_id').value = req.id;
            document.getElementById('req_name').value = req.requester_name || '';
            document.getElementById('req_contact').value = req.contact_number || '';
            document.getElementById('req_dept').value = req.dept || '';
            document.getElementById('fiscal_year').value = req.fiscal_year || '';
            
            // split deliveryChannel
            const deliveryParts = (req.delivery_channel || '').split(' - ');
            document.getElementById('delivery_channel').value = deliveryParts[0] || 'Paper';
            checkDeliveryOther();
            if (deliveryParts.length > 1) {
                document.getElementById('delivery_channel_details').value = deliveryParts[1];
            } else {
                document.getElementById('delivery_channel_details').value = '';
            }

            document.getElementById('start_date').value = req.start_date || '';
            document.getElementById('end_date').value = req.end_date || '';
            document.getElementById('target_date').value = req.target_date || '';
            document.getElementById('additional_details').value = req.details || '';
            
            // checkboxes for cols
            document.querySelectorAll('input[name="cols"]').forEach(cb => {
                cb.checked = (req.columns || '').includes(cb.value);
            });
            
            // types
            document.querySelectorAll('input[name="type"]').forEach(cb => {
                cb.checked = (req.type || '').includes(cb.value);
            });
            const statsMatch = (req.type || '').match(/\[รายละเอียดสรุป: (.*?)\]/);
            if (statsMatch) {
                document.getElementById('stats_details').value = statsMatch[1];
            } else {
                document.getElementById('stats_details').value = '';
            }
            toggleStatsDetail();

            document.getElementById('submit-request-btn').innerHTML = 'บันทึกการแก้ไข';
            document.getElementById('cancel-edit-btn').classList.remove('hidden');
            
            document.getElementById('request-form').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelEdit() {
            document.getElementById('edit_request_id').value = '';
            document.getElementById('request-form').reset();
            document.getElementById('submit-request-btn').innerHTML = 'ยืนยันส่งคำขอข้อมูล';
            document.getElementById('cancel-edit-btn').classList.add('hidden');
            
            // Reset to defaults
            const usr = currentUser;
            if (usr) {
                document.getElementById('req_name').value = usr.fullName || usr.username;
                document.getElementById('req_dept').value = usr.dept || '';
            }
            
            checkDeliveryOther();
            toggleStatsDetail();
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

        let userRequestsData = [];

        function getFriendlyFileName(url) {
            if (!url) return '';
            const parts = url.split('/');
            const filename = parts[parts.length - 1];
            const underscoreIndex = filename.indexOf('_');
            if (underscoreIndex !== -1) {
                return filename.substring(underscoreIndex + 1);
            }
            return filename;
        }

        function renderUserTable(data) {
            userRequestsData = data;
            const tbody = document.getElementById('user-requests-table');
            tbody.innerHTML = data.map(row => `
                <tr>
                   <td>${row.request_date}</td>
                   <td>
                        <div style="font-weight: 700;">${row.type}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">เริ่ม ${row.start_date} ถึง ${row.end_date}</div>
                   </td>
                   <td>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span class="status-pill status-${row.status.toLowerCase()}">${STATUS_TH[row.status] || row.status}</span>
                            ${row.status === 'Approved' && row.file_url ? `
                                <div style="display: flex; flex-direction: column; gap: 4px; margin-top: 4px;">
                                    ${row.file_url.split(',').map(url => url.trim()).filter(Boolean).map(url => `
                                        <a href="${url}" target="_blank" class="btn btn-outline" style="font-size: 0.7rem; padding: 2px 6px; display: inline-flex; align-items: center; gap: 4px; border-color: var(--primary); color: var(--primary); text-decoration: none;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                            ${getFriendlyFileName(url)}
                                        </a>
                                    `).join('')}
                                </div>
                            ` : ''}
                            <button class="btn btn-outline btn-sm" style="font-size: 0.7rem; padding: 2px 5px;" onclick="previewPDF('${row.id}')">
                                พิมพ์ PDF
                            </button>
                            ${row.status === 'Pending' ? `
                                <button class="btn btn-primary btn-sm" style="font-size: 0.7rem; padding: 2px 5px;" onclick="editUserRequest('${row.id}')">
                                    แก้ไขข้อมูล
                                </button>
                            ` : ''}
                            ${row.status === 'Approved' ? (
                                parseInt(row.has_feedback) > 0 ? `
                                    <span style="font-size: 0.75rem; color: #10b981; font-weight: 600; margin-top: 5px; display: inline-block; text-align: center;">✅ ประเมินแล้ว</span>
                                ` : `
                                    <button class="btn" style="padding: 2px 8px; font-size: 0.65rem; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; margin-top: 5px;" onclick="openFeedbackModal('${row.id}')">
                                        ประเมิน
                                    </button>
                                `
                            ) : ''}
                        </div>
                   </td>
                </tr>
            `).join('');
        }

        function renderAdminTable(data) {
            const tbody = document.getElementById('admin-requests-table');
            const today = new Date();
            tbody.innerHTML = data.map(row => {
                let rowStyle = '';
                if ((row.status === 'Pending' || row.status === 'Processing') && row.target_date) {
                    const target = new Date(row.target_date);
                    const diffTime = target - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays < 0) {
                        rowStyle = 'background-color: #fee2e2;'; // Overdue (Red)
                    } else if (diffDays <= 2) {
                        rowStyle = 'background-color: #ffedd5;'; // Warning (Orange)
                    }
                }
                return `
                <tr style="${rowStyle}">
                    <td><span style="color: var(--text-muted); font-size: 0.75rem;">${row.id}</span></td>
                    <td><span class="role-badge" style="font-size: 0.7rem;">${row.dept || '-'}</span></td>
                    <td><b>${row.user}</b></td>
                    <td>${row.request_date}</td>
                    <td>
                        <div><b>${row.type}</b></div>
                        <div style="font-size: 0.75rem;">คอลัมน์: ${row.columns || '-'}</div>
                    </td>
                    <td>${row.start_date} to ${row.end_date}</td>
                    <td><span class="status-pill status-${row.status.toLowerCase()}">${STATUS_TH[row.status] || row.status}</span></td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap; align-items: center; margin-bottom: 5px;">
                            ${row.status === 'Pending' || row.status === 'Processing'
                                ? `<button class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="openAdminModal('${row.id}')">จัดการ</button>`
                                : `<button class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 4px 8px; border-color: var(--warning); color: var(--warning);" onclick="openAdminModal('${row.id}')">แก้ไข</button>`
                            }
                            <button class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="previewPDF('${row.id}')">PDF</button>
                        </div>
                        ${row.file_url ? `
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                ${row.file_url.split(',').map(url => url.trim()).filter(Boolean).map(url => `
                                    <a href="${url}" target="_blank" class="btn btn-outline" style="font-size: 0.7rem; padding: 2px 6px; display: inline-flex; align-items: center; gap: 4px; border-color: var(--primary); color: var(--primary); text-decoration: none;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        ${getFriendlyFileName(url)}
                                    </a>
                                `).join('')}
                            </div>
                        ` : '<div style="font-size: 0.75rem; color: var(--text-muted);">ไม่มีไฟล์</div>'}
                    </td>
                </tr>
            `}).join('');
        }

        function exportRequestsToCSV() {
            if (!adminRequestsData || adminRequestsData.length === 0) return;
            const headers = ['ID', 'User', 'Department', 'Type', 'Target Date', 'Status'];
            const rows = adminRequestsData.map(r => [
                r.id, r.user, r.dept, '"' + String(r.type || '').replace(/"/g, '""') + '"', r.target_date, r.status
            ]);
            let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(e => e.join(",")).join("\n");
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "medical_requests_export.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        let currentMode = 'user';

        function toggleAdminMode() {
            if (currentMode === 'admin') {
                showView('user');
            } else {
                showView('admin');
            }
        }

        function showView(view) {
            currentMode = view;
            document.getElementById('login-view').classList.add('hidden');
            document.getElementById('user-view').classList.add('hidden');
            document.getElementById('admin-view').classList.add('hidden');
            document.getElementById('app-header').classList.remove('hidden');

            document.getElementById('display-name').innerText = currentUser.fullName || currentUser.username;
            document.getElementById('display-dept').innerText = currentUser.dept || 'N/A';

            if (currentUser && currentUser.role === 'admin') {
                const btnSwitch = document.getElementById('btn-switch-role');
                if (btnSwitch) {
                    btnSwitch.classList.remove('hidden');
                    if (view === 'admin') {
                        btnSwitch.innerText = '👤 สลับเป็นโหมดผู้ใช้งาน (เพื่อสร้างคำขอ)';
                    } else {
                        btnSwitch.innerText = '⚙️ กลับหน้าผู้ดูแลระบบ';
                    }
                }
            } else {
                const btnSwitch = document.getElementById('btn-switch-role');
                if (btnSwitch) btnSwitch.classList.add('hidden');
            }

            if (view === 'admin') {
                document.body.classList.remove('admin-dark-mode'); // Removed dark mode to keep it bright
            } else {
                document.body.classList.remove('admin-dark-mode');
            }

            // Pre-fill request form
            if (view === 'user') {
                document.getElementById('req_name').value = currentUser.fullName || '';
                document.getElementById('req_dept').value = currentUser.dept || '';
            }

            const brandHeader = document.getElementById('brand-header');
            if (view === 'user') {
                document.getElementById('user-view').classList.remove('hidden');
                brandHeader.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> หน้าหลักผู้ใช้งาน`;
                switchUserTab('dashboard');
            }
            if (view === 'admin') {
                document.getElementById('admin-view').classList.remove('hidden');
                brandHeader.innerHTML = `Medical Admin Panel`;
                switchAdminTab('dashboard');
            }
        }

        async function logout() {
            try {
                await callServer('logout');
            } catch (err) {
                console.error("Logout error", err);
            }
            currentUser = null;
            document.getElementById('app-header').classList.add('hidden');
            document.getElementById('user-view').classList.add('hidden');
            document.getElementById('admin-view').classList.add('hidden');
            document.getElementById('login-view').classList.remove('hidden');
            document.getElementById('login-form').reset();
            document.getElementById('login-error').classList.add('hidden');
        }

        // Modal Logic
        function renderAdminModalFiles() {
            const existingContainer = document.getElementById('admin-existing-files');
            if (existingFiles.length > 0) {
                existingContainer.innerHTML = `
                    <div style="margin-top: 10px; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: var(--text-main);">ไฟล์ที่แนบอยู่เดิม:</div>
                    <div class="file-list">
                        ${existingFiles.map((url, idx) => `
                            <div class="file-item">
                                <a href="${url}" target="_blank" class="file-name-link">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                    ${getFriendlyFileName(url)}
                                </a>
                                <button type="button" class="file-delete-btn" onclick="removeExistingFile(${idx})" title="ลบไฟล์">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                existingContainer.innerHTML = '';
            }

            const newContainer = document.getElementById('admin-new-files');
            if (selectedFiles.length > 0) {
                newContainer.innerHTML = `
                    <div style="margin-top: 10px; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: var(--primary);">ไฟล์ที่จะอัปโหลดใหม่:</div>
                    <div class="file-list">
                        ${selectedFiles.map((file, idx) => `
                            <div class="file-item" style="border-color: var(--primary); background: rgba(14, 165, 233, 0.05);">
                                <span class="file-name-link" style="color: var(--primary);">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                    ${file.name} (${(file.size / 1024).toFixed(1)} KB)
                                </span>
                                <button type="button" class="file-delete-btn" onclick="removeSelectedFile(${idx})" title="ลบออกจากรายการ">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                `;
                document.getElementById('admin-file-name').innerText = `เลือกแล้ว ${selectedFiles.length} ไฟล์`;
                document.getElementById('admin-file-name').style.color = 'var(--primary)';
            } else {
                newContainer.innerHTML = '';
                document.getElementById('admin-file-name').innerText = 'ยังไม่ได้เลือกไฟล์';
                document.getElementById('admin-file-name').style.color = 'var(--text-muted)';
            }

            document.getElementById('file_url').value = existingFiles.join(', ');
        }

        function removeExistingFile(index) {
            existingFiles.splice(index, 1);
            renderAdminModalFiles();
        }

        function removeSelectedFile(index) {
            selectedFiles.splice(index, 1);
            renderAdminModalFiles();
        }

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

            // ดึงไฟล์เดิมที่มีอยู่
            existingFiles = row.file_url ? row.file_url.split(',').map(s => s.trim()).filter(Boolean) : [];
            selectedFiles = []; // รีเซ็ตไฟล์ใหม่ที่จะเลือกอัปโหลด

            renderAdminModalFiles();

            document.getElementById('admin-modal').classList.remove('hidden');
        }

        function handleAdminFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    const exists = selectedFiles.some(f => f.name === files[i].name && f.size === files[i].size);
                    if (!exists) {
                        selectedFiles.push(files[i]);
                    }
                }
                renderAdminModalFiles();
            }
            event.target.value = ''; // รีเซ็ตค่าเพื่อให้สามารถเลือกไฟล์เดิมได้
        }

        function closeModal() {
            document.getElementById('admin-modal').classList.add('hidden');
            document.getElementById('file_url').value = '';
            document.getElementById('upload_file').value = '';
            const nameSpan = document.getElementById('admin-file-name');
            nameSpan.innerText = 'ยังไม่ได้เลือกไฟล์';
            nameSpan.style.color = 'var(--text-muted)';
            const progressDiv = document.getElementById('upload-progress');
            progressDiv.classList.add('hidden');
            progressDiv.innerText = 'กำลังอัปโหลด... 0%';
            progressDiv.style.color = 'var(--primary)';
            
            existingFiles = [];
            selectedFiles = [];
        }

        async function submitAdminAction(status) {
            const inputVal = document.getElementById('file_url').value.trim();
            existingFiles = inputVal ? inputVal.split(',').map(s => s.trim()).filter(Boolean) : [];

            let uploadedUrls = [];

            if (selectedFiles.length > 0) {
                const progressDiv = document.getElementById('upload-progress');
                progressDiv.classList.remove('hidden');
                
                for (let i = 0; i < selectedFiles.length; i++) {
                    const file = selectedFiles[i];
                    progressDiv.innerText = `กำลังอัปโหลดไฟล์ (${i + 1}/${selectedFiles.length}): ${file.name}...`;
                    progressDiv.style.color = 'var(--primary)';

                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const response = await fetch('upload.php', {
                            method: 'POST',
                            body: formData
                        });
                        const res = await response.json();
                        if (res.success) {
                            uploadedUrls.push(res.fileUrl);
                        } else {
                            showToast('อัปโหลดไฟล์ล้มเหลว: ' + res.message);
                            progressDiv.innerText = 'อัปโหลดล้มเหลว: ' + res.message;
                            progressDiv.style.color = 'var(--danger)';
                            return;
                        }
                    } catch (err) {
                        console.error("Upload error", err);
                        showToast('อัปโหลดไฟล์ล้มเหลว: ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                        progressDiv.innerText = 'อัปโหลดล้มเหลว: ปัญหาทางเครือข่าย';
                        progressDiv.style.color = 'var(--danger)';
                        return;
                    }
                }

                progressDiv.innerText = 'อัปโหลดสำเร็จทั้งหมด!';
                progressDiv.style.color = 'var(--secondary)';
            }

            const finalUrls = [...existingFiles, ...uploadedUrls].join(', ');

            try {
                const res = await callServer('updateRequestStatus', {
                    id: selectedRequestId,
                    status: status,
                    fileUrl: finalUrls
                });
                if (res.success) {
                    showToast(`คำขอถูกเปลี่ยนสถานะเป็น ${STATUS_TH[status] || status}`);
                    closeModal();
                    loadRequests();
                }
            } catch (err) {
                console.error(err);
                showToast('ไม่สามารถอัปเดตสถานะได้');
            }
        }

        // --- User Management Logic ---

        async function loadLoginHistory() {
            try {
                const history = await callServer('getLoginHistory');
                renderLoginHistoryTable(history);
            } catch (err) {
                showToast('โหลดข้อมูลประวัติเข้าใช้งานไม่สำเร็จ');
            }
        }
        
        function renderLoginHistoryTable(history) {
            const tbody = document.getElementById('admin-login-history-table');
            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">ไม่พบประวัติเข้าใช้งาน</td></tr>';
                return;
            }
            tbody.innerHTML = history.map(h => `
                <tr>
                    <td><b>${h.username}</b></td>
                    <td>${h.ip_address}</td>
                    <td style="font-size: 0.75rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${h.user_agent}">${h.user_agent}</td>
                    <td>${h.login_time}</td>
                </tr>
            `).join('');
        }

        function switchUserTab(tab) {
            const tabs = ['requests', 'dashboard', 'feedback'];
            tabs.forEach(t => {
                const btn = document.getElementById('user-tab-' + t);
                const sec = document.getElementById('user-section-' + t);
                
                if (t === 'dashboard') {
                    const el = document.getElementById('shared-dashboard-content');
                    if (el && el.parentElement.id !== 'user-section-dashboard') {
                        document.getElementById('user-section-dashboard').appendChild(el);
                    }
                } else if (t === 'feedback') {
                    const el = document.getElementById('shared-feedback-content');
                    if (el && el.parentElement.id !== 'user-section-feedback') {
                        document.getElementById('user-section-feedback').appendChild(el);
                    }
                }

                if (btn && sec) {
                    if (t === tab) {
                        btn.classList.add('btn-primary');
                        btn.classList.remove('btn-outline');
                        sec.classList.remove('hidden');
                    } else {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline');
                        sec.classList.add('hidden');
                    }
                }
            });

            if (tab === 'dashboard' || tab === 'feedback') {
                updateDashboard();
            }
        }

        async function loadAuditLog() {
            try {
                const logs = await callServer('getAuditLog');
                const tbody = document.getElementById('admin-audit-log-table');
                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">ไม่พบประวัติ</td></tr>';
                    return;
                }
                tbody.innerHTML = logs.map(l => `
                    <tr>
                        <td style="font-size: 0.8rem;">${l.timestamp}</td>
                        <td><b>${l.username}</b></td>
                        <td><span style="color: #3b82f6; font-weight: 600;">${l.action}</span></td>
                        <td style="font-size: 0.85rem;">${l.details}</td>
                    </tr>
                `).join('');
            } catch (err) {
                showToast('โหลดข้อมูล Audit Log ไม่สำเร็จ');
            }
        }

        function switchAdminTab(tab) {
            const tabs = ['dashboard', 'requests', 'feedback', 'login-history', 'audit-log', 'users'];
            tabs.forEach(t => {
                const btn = document.getElementById('tab-' + t);
                const sec = document.getElementById('admin-section-' + t);
                
                if (t === 'dashboard') {
                    const el = document.getElementById('shared-dashboard-content');
                    if (el && el.parentElement.id !== 'admin-section-dashboard') {
                        document.getElementById('admin-section-dashboard').appendChild(el);
                    }
                } else if (t === 'feedback') {
                    const el = document.getElementById('shared-feedback-content');
                    if (el && el.parentElement.id !== 'admin-section-feedback') {
                        document.getElementById('admin-section-feedback').appendChild(el);
                    }
                }

                if (btn && sec) {
                    if (t === tab) {
                        btn.classList.add('btn-primary');
                        btn.classList.remove('btn-outline');
                        sec.classList.remove('hidden');
                    } else {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline');
                        sec.classList.add('hidden');
                    }
                }
            });

            if (tab === 'dashboard') {
                updateDashboard();
            } else if (tab === 'requests') {
                loadRequests();
            } else if (tab === 'users') {
                loadUsers();
            } else if (tab === 'login-history') {
                loadLoginHistory();
            } else if (tab === 'audit-log') {
                loadAuditLog();
            }
        }

        async function loadUsers() {
            try {
                const users = await callServer('getAllUsers');
                adminUsersData = users;
                renderUserManagementTable(users);
            } catch (err) {
                showToast('โหลดข้อมูลผู้ใช้ไม่สำเร็จ');
            }
        }

        function renderUserManagementTable(users) {
            const tbody = document.getElementById('admin-users-table');
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">ไม่พบข้อมูลพนักงาน</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(u => {
                const isActive = u.is_active === undefined ? true : u.is_active == 1;
                return `
                <tr style="${isActive ? '' : 'opacity: 0.5;'}">
                    <td>${u.id}</td>
                    <td><b>${u.fullName}</b> ${isActive ? '' : '<span style="color:var(--danger); font-size: 0.75rem; margin-left: 5px;">(ระงับ)</span>'}</td>
                    <td>${u.username}</td>
                    <td><code>${u.password}</code></td>
                    <td><span class="status-pill ${u.role === 'admin' ? 'status-approved' : 'status-processing'}">${u.role}</span></td>
                    <td style="font-size: 0.8rem;">${u.dept}</td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="openAdminResetPasswordModal('${u.username}')">รีเซ็ตรหัส</button>
                            <button class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="openAdminEditUserModal('${u.username}', '${u.fullName}', '${u.dept}', '${u.role}')">แก้ไข</button>
                            ${isActive ? 
                                `<button class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="adminToggleUserStatus('${u.username}', false)">ปิดการใช้งาน</button>` :
                                `<button class="btn btn-success btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="adminToggleUserStatus('${u.username}', true)">เปิดการใช้งาน</button>`
                            }
                            <button class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="adminDeleteUser('${u.username}')">ลบ</button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
        }

        function filterUserTable() {
            const query = document.getElementById('user-search').value.toLowerCase();
            const filtered = adminUsersData.filter(u =>
                u.fullName.toLowerCase().includes(query) ||
                u.username.toLowerCase().includes(query) ||
                u.dept.toLowerCase().includes(query)
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
            reader.onload = function (e) {
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

        // --- Admin Reset Password ---
        function openAdminResetPasswordModal(username) {
            document.getElementById('reset-pwd-title').innerText = `รีเซ็ตรหัสผ่าน: ${username}`;
            document.getElementById('reset-target-username').value = username;
            document.getElementById('admin-new-password').value = '';
            document.getElementById('admin-reset-password-modal').classList.remove('hidden');
        }

        function closeAdminResetPasswordModal() {
            document.getElementById('admin-reset-password-modal').classList.add('hidden');
        }

        async function submitAdminResetPassword(e) {
            e.preventDefault();
            const username = document.getElementById('reset-target-username').value;
            const newPassword = document.getElementById('admin-new-password').value;
            const btn = document.getElementById('admin-reset-pwd-btn');
            
            btn.disabled = true;
            btn.innerText = 'กำลังบันทึก...';
            
            try {
                const res = await callServer('adminResetPassword', { targetUsername: username, newPassword: newPassword });
                if (res.success) {
                    showToast(`รีเซ็ตรหัสผ่านของ ${username} สำเร็จ`);
                    closeAdminResetPasswordModal();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + (res.message || 'Unknown error'));
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            } finally {
                btn.disabled = false;
                btn.innerText = 'บันทึกรหัสผ่านใหม่';
            }
        }

        // --- Admin Edit User ---
        function openAdminEditUserModal(username, fullName, dept, role) {
            document.getElementById('edit-target-username').value = username;
            document.getElementById('edit-user-fullname').value = fullName;
            document.getElementById('edit-user-dept').value = dept;
            document.getElementById('edit-user-role').value = role;
            document.getElementById('admin-edit-user-modal').classList.remove('hidden');
        }

        function closeAdminEditUserModal() {
            document.getElementById('admin-edit-user-modal').classList.add('hidden');
        }

        async function submitAdminEditUser(e) {
            e.preventDefault();
            const username = document.getElementById('edit-target-username').value;
            const fullName = document.getElementById('edit-user-fullname').value;
            const dept = document.getElementById('edit-user-dept').value;
            const role = document.getElementById('edit-user-role').value;
            const btn = document.getElementById('admin-edit-user-btn');
            
            btn.disabled = true;
            btn.innerText = 'กำลังบันทึก...';
            
            try {
                const res = await callServer('adminUpdateUser', { username, fullName, dept, role });
                if (res.success) {
                    showToast(`อัปเดตข้อมูล ${username} สำเร็จ`);
                    closeAdminEditUserModal();
                    loadUsers();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + (res.message || 'Unknown error'));
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            } finally {
                btn.disabled = false;
                btn.innerText = 'บันทึกข้อมูล';
            }
        }

        async function adminToggleUserStatus(username, status) {
            if (!confirm(`คุณต้องการ ${status ? 'เปิดการใช้งาน' : 'ปิดการใช้งาน'} ผู้ใช้ ${username} ใช่หรือไม่?`)) return;
            try {
                const res = await callServer('adminToggleUserStatus', { username, status });
                if (res.success) {
                    showToast(`${status ? 'เปิดการใช้งาน' : 'ปิดการใช้งาน'} สำเร็จ`);
                    loadUsers();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + (res.message || 'Unknown error'));
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            }
        }

        async function adminDeleteUser(username) {
            if (!confirm(`คุณต้องการลบผู้ใช้ ${username} ใช่หรือไม่?\n(ประวัติคำขอเก่าจะไม่ถูกลบ แต่ผู้ใช้นี้จะเข้าสู่ระบบไม่ได้อีก)`)) return;
            try {
                const res = await callServer('adminDeleteUser', { username });
                if (res.success) {
                    showToast(`ลบผู้ใช้ ${username} สำเร็จ`);
                    loadUsers();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + (res.message || 'Unknown error'));
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            }
        }

        // UI Helpers
        function clearDashboardFilters() {
            if(document.getElementById('dash-filter-fiscal')) document.getElementById('dash-filter-fiscal').value = '';
            if(document.getElementById('dash-filter-start')) document.getElementById('dash-filter-start').value = '';
            if(document.getElementById('dash-filter-end')) document.getElementById('dash-filter-end').value = '';
            updateDashboard();
        }

        async function updateDashboard() {
            try {
                const fiscalYear = document.getElementById('dash-filter-fiscal') ? document.getElementById('dash-filter-fiscal').value : '';
                const startDate = document.getElementById('dash-filter-start') ? document.getElementById('dash-filter-start').value : '';
                const endDate = document.getElementById('dash-filter-end') ? document.getElementById('dash-filter-end').value : '';

                const data = await callServer('getDashboardData', { fiscalYear, startDate, endDate });
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

        let currentPdfId = null;

        function previewPDF(id) {
            // Find the request from either userRequestsData or adminRequestsData
            const dataRow = (currentUser.role === 'admin' ? adminRequestsData : userRequestsData).find(r => r.id === id);
            if (!dataRow) return;

            currentPdfId = id;

            document.getElementById('pdf-req-id').innerText = dataRow.id;
            document.getElementById('pdf-req-date').innerText = dataRow.request_date;
            document.getElementById('pdf-user').innerText = `${dataRow.requester_name || '-'} (${dataRow.user || '-'})`;
            document.getElementById('pdf-dept').innerText = dataRow.dept || '-';
            
            // New fields
            document.getElementById('pdf-contact').innerText = dataRow.contact_number || '-';
            document.getElementById('pdf-fiscal').innerText = dataRow.fiscal_year || '-';
            document.getElementById('pdf-delivery').innerText = dataRow.delivery_channel || '-';
            document.getElementById('pdf-purpose').innerText = dataRow.purpose || '-';
            
            document.getElementById('pdf-type').innerText = dataRow.type;
            document.getElementById('pdf-cols').innerText = dataRow.columns || '-';
            document.getElementById('pdf-range').innerText = `${dataRow.start_date} ถึง ${dataRow.end_date}`;
            document.getElementById('pdf-target').innerText = dataRow.target_date || '-';
            document.getElementById('pdf-details').innerText = dataRow.details || '-';
            document.getElementById('pdf-status').innerText = STATUS_TH[dataRow.status] || dataRow.status;

            // Signature block pre-filling
            document.getElementById('pdf-sig-user').innerText = dataRow.requester_name || '................................................';
            
            // Format Thai Buddhist Era date for request date
            if (dataRow.request_date) {
                const datePart = dataRow.request_date.split(' ')[0];
                const parts = datePart.split('-');
                if (parts.length === 3) {
                    const thaiYear = parseInt(parts[0]) + 543;
                    document.getElementById('pdf-sig-date').innerText = `${parts[2]} / ${parts[1]} / ${thaiYear}`;
                } else {
                    document.getElementById('pdf-sig-date').innerText = '....... / ....... / .......';
                }
            } else {
                document.getElementById('pdf-sig-date').innerText = '....... / ....... / .......';
            }

            if (dataRow.approved_by) {
                document.getElementById('pdf-sig-admin').innerText = dataRow.approved_by;
                document.getElementById('pdf-sig-admin-date').innerText = '....... / ....... / .......';
            } else {
                document.getElementById('pdf-sig-admin').innerText = '................................................';
                document.getElementById('pdf-sig-admin-date').innerText = '....... / ....... / .......';
            }

            document.getElementById('pdf-preview-modal').classList.remove('hidden');
        }

        function closePdfPreviewModal() {
            document.getElementById('pdf-preview-modal').classList.add('hidden');
        }

        function downloadPDF() {
            const element = document.getElementById('pdf-template');
            const btn = document.getElementById('btn-download-pdf');
            const originalText = btn.innerText;

            btn.innerText = 'กำลังสร้าง PDF...';
            btn.disabled = true;

            const opt = {
                margin: 0,
                filename: `Medical_Request_${currentPdfId}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true, 
                    letterRendering: true,
                    scrollY: 0,
                    scrollX: 0
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerText = originalText;
                btn.disabled = false;
            });
        }
    </script>

    <!-- PDF Preview Modal -->
    <div id="pdf-preview-modal" class="modal-overlay hidden">
        <div class="card fade-in"
            style="max-width: 900px; width: 95%; max-height: 95vh; display: flex; flex-direction: column; padding: 0; overflow: hidden;">
            <div
                style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc;">
                <h3 style="margin: 0; color: #1e293b;">ตัวอย่างเอกสารก่อนพิมพ์</h3>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-outline" onclick="closePdfPreviewModal()">ปิด</button>
                    <button class="btn btn-primary" id="btn-download-pdf" onclick="downloadPDF()">บันทึกเป็น
                        PDF</button>
                </div>
            </div>

            <!-- Scrollable Area for Preview -->
            <div
                style="padding: 2rem; overflow-y: auto; background: #cbd5e1; display: flex; justify-content: center; flex: 1;">

                <!-- The A4 Template -->
                <div id="pdf-template"
                    style="width: 210mm; min-height: 295mm; padding: 12mm; background: white; color: black; font-family: 'Sarabun', sans-serif; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                    <div
                        style="text-align: center; margin-bottom: 12px; border-bottom: 2px solid #1e293b; padding-bottom: 6px;">
                        <h1 style="margin: 0 0 5px 0; color: #1e293b; font-size: 20px; font-weight: 700;">โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา</h1>
                        <h2 style="margin: 0; color: #1e293b; font-size: 16px;">แบบฟอร์มคำขอข้อมูลเวชระเบียน</h2>
                        <h3 style="margin: 3px 0 0 0; color: #475569; font-size: 13px;">Medical Record Data Request Summary</h3>
                    </div>

                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 16px;">
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; width: 32%; background: #f1f5f9;">รหัสคำขอ (Request ID)</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; width: 68%;" id="pdf-req-id"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">วันที่ส่งคำขอ</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-req-date"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">ชื่อผู้ขอข้อมูล</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-user"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">หน่วยงาน</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-dept"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">เบอร์โทรศัพท์ติดต่อ</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-contact"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">ปีงบประมาณ</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-fiscal"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">ประเภทรายการข้อมูล</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-type"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">คอลัมน์ข้อมูลที่ต้องการ</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-cols"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">ช่วงวันที่ข้อมูล</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-range"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">วันที่ต้องการรับข้อมูล</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-target"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">ช่องทางการส่งข้อมูล</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1;" id="pdf-delivery"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">วัตถุประสงค์การใช้ข้อมูล</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; white-space: pre-wrap;" id="pdf-purpose"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">รายละเอียดเพิ่มเติม</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; white-space: pre-wrap;" id="pdf-details"></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 600; background: #f1f5f9;">สถานะปัจจุบัน</td>
                            <td style="padding: 8px; border: 1px solid #cbd5e1; font-weight: 700;" id="pdf-status"></td>
                        </tr>
                    </table>

                    <div style="margin-top: 20px; display: flex; justify-content: space-between; font-size: 13.5px;">
                        <div style="text-align: center; width: 45%;">
                            <p>ลงชื่อ................................................ผู้ขอข้อมูล</p>
                            <p style="margin-top: 3px; font-weight: 500;">( <span id="pdf-sig-user">................................................</span> )</p>
                            <p style="margin-top: 3px;">วันที่ <span id="pdf-sig-date">....... / ....... / .......</span></p>
                        </div>
                        <div style="text-align: center; width: 45%;">
                            <p>ลงชื่อ................................................เจ้าหน้าที่ผู้รับงาน</p>
                            <p style="margin-top: 3px; font-weight: 500;">( <span id="pdf-sig-admin">................................................</span> )</p>
                            <p style="margin-top: 3px;">วันที่ <span id="pdf-sig-admin-date">....... / ....... / .......</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</body>

</html>
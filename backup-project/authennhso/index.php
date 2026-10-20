<?php
require_once __DIR__ . '/config.php';
requireLogin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="ระบบตรวจจับผู้รับบริการที่ไม่ได้ขอเลข Authen Code หรือยังไม่ได้ปิดสิทธิ์ (HOSxP) เพื่อป้องกันรายได้สูญเสีย">
    <title>NHSO Claim Detection — HOSxP รพ.แม่แจ่ม</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">

    <!-- HEADER -->
    <header>
        <div class="app-brand">
            <div class="app-logo-icon">N</div>
            <div class="app-title">
                <h1>NHSO Claim Detection</h1>
                <p>ระบบตรวจจับสิทธิ์เคลมที่ตกหล่น &middot; HOSxP รพ.แม่แจ่ม &middot; <span style="color:var(--success);">Read-Only</span></p>
            </div>
        </div>
        <div class="header-actions">
            <div class="user-badge" style="display: flex; align-items: center; gap: 8px; background: var(--bg-surface); border: 1px solid var(--border-color); padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 500;">
                <span class="user-icon" style="font-size: 14px;">👤</span>
                <span class="user-name" style="color: var(--text-title);"><?php echo htmlspecialchars($currentUser['fullname']); ?></span>
            </div>
            <?php if (isset($currentUser['role']) && $currentUser['role'] === 'admin'): ?>
                <a href="admin_users.php" class="btn btn-sm" style="background-color:#8b5cf6; color:white; text-decoration:none;" title="จัดการผู้ใช้งาน">⚙️ Admin Panel</a>
            <?php endif; ?>
            <button class="btn btn-success btn-sm" id="btn-export-csv" title="ส่งออกรายงาน CSV">📥 Export CSV</button>
            <a href="logout.php" class="btn btn-danger btn-sm" title="ออกจากระบบ" style="text-decoration: none;">🚪 ออกจากระบบ</a>
        </div>
    </header>

    <!-- KPI CARDS -->
    <div class="metrics-grid">
        <div class="kpi-card kpi-total" id="card-total-visits" style="cursor: pointer;">
            <div class="kpi-header">
                <span class="kpi-title">มาทั้งหมดในวัน</span>
                <span class="kpi-icon">📋</span>
            </div>
            <div class="kpi-value" id="stat-total-visits">0</div>
            <div class="kpi-desc"><span id="stat-total-patients" style="font-weight:600;">0</span> คน (แยกรายบุคคล)</div>
        </div>

        <div class="kpi-card kpi-authen" id="card-no-authen" style="cursor: pointer;">
            <div class="kpi-header">
                <span class="kpi-title">ไม่มีเลข Authen</span>
                <span class="kpi-icon">🚫</span>
            </div>
            <div class="kpi-value" id="stat-no-authen">0</div>
            <div class="kpi-desc">ไม่รวมสิทธิ์ออกหน่วย & ชำระเงิน</div>
        </div>

        <div class="kpi-card kpi-approve" id="card-not-closed-visit" style="cursor: pointer;">
            <div class="kpi-header">
                <span class="kpi-title">ไม่ปิด visit (ไม่มี Invoice)</span>
                <span class="kpi-icon">⏳</span>
            </div>
            <div class="kpi-value" id="stat-not-closed-hosxp">0</div>
            <div class="kpi-desc">ไม่มีเลข Invoice ใน HOSxP</div>
        </div>

        <div class="kpi-card kpi-close" id="card-not-closed-nhso" style="cursor: pointer;">
            <div class="kpi-header">
                <span class="kpi-title">ไม่ปิดสิทธิ์ (EP)</span>
                <span class="kpi-icon">🔓</span>
            </div>
            <div class="kpi-value" id="stat-not-closed-nhso">0</div>
            <div class="kpi-desc"> auth_code เริ่มต้นด้วย "EP"</div>
        </div>

        <div class="kpi-card kpi-loss" id="card-closed-nhso" style="cursor: pointer;">
            <div class="kpi-header">
                <span class="kpi-title">ปิดสิทธิ์ (PP)</span>
                <span class="kpi-icon">🔒</span>
            </div>
            <div class="kpi-value" id="stat-closed-nhso">0</div>
            <div class="kpi-desc"> auth_code เริ่มต้นด้วย "PP"</div>
        </div>
    </div>

    <!-- DASHBOARD BODY -->
    <div class="dashboard-body">

        <!-- FILTER SIDEBAR -->
        <aside>
            <div class="panel">
                <div class="panel-title">🔍 ตัวกรองข้อมูล</div>

                <div class="loss-prevention-sidebar-card">
                    <div class="loss-prevention-sidebar-label">💰 ประมาณการสูญเสีย :</div>
                    <div id="stat-loss-value" class="loss-prevention-sidebar-value">฿0</div>
                </div>

                <div class="form-group">
                    <label for="filter-search">ค้นหาด่วน (HN / ชื่อ / CID)</label>
                    <input type="text" id="filter-search" class="form-control" placeholder="พิมพ์เพื่อค้นหา...">
                </div>

                <div class="form-group">
                    <label for="filter-start">ตั้งแต่วันที่</label>
                    <input type="date" id="filter-start" class="form-control">
                </div>

                <div class="form-group">
                    <label for="filter-end">ถึงวันที่</label>
                    <input type="date" id="filter-end" class="form-control">
                </div>

                <div class="form-group">
                    <label for="filter-pttype">สิทธิ NHSO</label>
                    <select id="filter-pttype" class="form-control">
                        <option value="all">ทุกสิทธิ์ NHSO</option>
                        <option value="UCS">UCS / บัตรทอง (nhso_code=01)</option>
                        <option value="OFC">ข้าราชการ / รัฐวิสาหกิจ (nhso_code=02)</option>
                        <option value="SSS">ประกันสังคม (nhso_code=03)</option>
                        <option value="LGO">อปท (nhso_code=04)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="filter-dept">แผนก / คลินิก</label>
                    <select id="filter-dept" class="form-control">
                        <option value="all">ทุกแผนก</option>
                        <!-- Departments loaded dynamically from HOSxP -->
                    </select>
                </div>

                <div class="form-group">
                    <label for="filter-issue">สถานะที่ต้องการตรวจสอบ</label>
                    <select id="filter-issue" class="form-control">
                        <option value="all_issues">🔴 แสดงเฉพาะที่ยังมีปัญหา</option>
                        <option value="all">🌐 แสดงทั้งหมด (ไม่กรองปัญหา)</option>
                        <option value="missing_authen">🚫 ไม่มีเลข Authen (สิทธิ์หลัก)</option>
                        <option value="not_closed_visit">⏳ ไม่ปิด Visit (ไม่มี Invoice)</option>
                        <option value="not_closed_nhso">🔓 ไม่ปิดสิทธิ์ (สถานะ EP)</option>
                        <option value="closed_nhso">🔒 ปิดสิทธิ์เรียบร้อย (สถานะ PP)</option>
                    </select>
                </div>

                <button class="btn btn-secondary btn-block" id="btn-reset-filters">🔄 ล้างตัวกรอง</button>

                <!-- Charts -->
                <div class="chart-container">
                    <div class="panel-title" style="border-bottom:none; padding-bottom:0; margin-bottom:12px;">📊 ปัญหาเคลมแยกแผนก</div>
                    <div style="height: 220px;">
                        <canvas id="dept-chart"></canvas>
                    </div>
                </div>

                <div class="chart-container" style="margin-top:16px; border-top:none;">
                    <div class="panel-title" style="border-bottom:none; padding-bottom:0; margin-bottom:12px;">🎯 ปัญหาเคลมแยกสิทธิ์</div>
                    <div style="height: 180px;">
                        <canvas id="pttype-chart"></canvas>
                    </div>
                </div>
            </div>
        </aside>

        <!-- DATA TABLE -->
        <main>
            <div class="table-panel">
                <div class="table-header-row">
                    <div class="table-title">
                        📝 รายการผู้รับบริการ
                        <span class="table-count-badge" id="record-count">0</span>
                        <span style="font-size:12px; color:var(--text-muted); font-weight:400;">รายการ</span>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th class="sortable" data-sort="hn">HN / VN</th>
                                <th class="sortable" data-sort="fname">ชื่อ-สกุล / CID</th>
                                <th class="sortable" data-sort="vstdate">วันที่รับบริการ 🔽</th>
                                <th>แผนก</th>
                                <th>สิทธิ์</th>
                                <th style="text-align:right;">ค่ารักษา (บาท)</th>
                                <th>สถานะเคลม</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>
                        <tbody id="patient-table-body">
                            <tr>
                                <td colspan="8" style="text-align:center; padding:50px; color:var(--text-muted);">
                                    <div style="font-size:32px; margin-bottom:12px;">⏳</div>
                                    <div>กำลังเชื่อมต่อฐานข้อมูล HOSxP...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Container -->
                <div class="pagination-container" id="pagination-wrapper" style="display: none;">
                    <div class="pagination-info">
                        แสดงรายการที่ <span id="pag-start">0</span> - <span id="pag-end">0</span> จากทั้งหมด <span id="pag-total">0</span> รายการ
                    </div>
                    <div class="pagination-buttons" id="pagination-buttons">
                        <!-- Dynamic pagination buttons loaded via JS -->
                    </div>
                </div>
            </div>
        </main>

    </div>

</div>

<!-- DETAIL MODAL (Read-Only) -->
<div class="modal-overlay" id="detail-modal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-title">📋 รายละเอียดผู้รับบริการ &mdash; VN: <span id="m-vn-text"></span></div>
            <button class="modal-close" title="ปิด">&times;</button>
        </div>
        <div class="modal-body">

            <div class="detail-section">
                <div class="section-title">ข้อมูลผู้รับบริการ</div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">HN</span>
                        <span class="info-value" id="m-hn">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">เลขบัตรประชาชน</span>
                        <span class="info-value" id="m-cid">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ชื่อ-นามสกุล</span>
                        <span class="info-value" id="m-name">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">อายุ / วันเกิด</span>
                        <span class="info-value" id="m-age">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">เบอร์โทรศัพท์</span>
                        <span class="info-value" id="m-phone">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">สิทธิการรักษา</span>
                        <span class="info-value" id="m-pttype">-</span>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="section-title">ข้อมูลการรับบริการ</div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">วันที่-เวลา</span>
                        <span class="info-value" id="m-vstdate">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">แผนก / คลินิก</span>
                        <span class="info-value" id="m-dept">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">แพทย์ผู้ตรวจ</span>
                        <span class="info-value" id="m-doctor">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">สาขาเฉพาะทาง</span>
                        <span class="info-value" id="m-spclty">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">การวินิจฉัย (ICD-10)</span>
                        <span class="info-value" id="m-dx">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">ค่ารักษาพยาบาลรวม</span>
                        <span class="info-value" id="m-income" style="color:#f87171; font-size:18px;">-</span>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <div class="section-title">ข้อมูลสิทธิ์เคลม NHSO (nhso_confirm_privilege)</div>
                <div class="claim-edit-box">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Authen Code</span>
                            <span class="info-value" id="m-authen-code">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">สถานะ Confirm</span>
                            <span class="info-value" id="m-confirm-status">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">NHSO Seq</span>
                            <span class="info-value" id="m-nhso-seq">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">จำนวนเงินที่ Confirm</span>
                            <span class="info-value" id="m-nhso-amount">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">วันที่-เวลา Confirm</span>
                            <span class="info-value" id="m-confirm-date">-</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">เจ้าหน้าที่ผู้ Confirm</span>
                            <span class="info-value" id="m-confirm-staff">-</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary" id="btn-close-modal">ปิด</button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast-container" id="toast-container"></div>

<script src="assets/app.js"></script>

</body>
</html>

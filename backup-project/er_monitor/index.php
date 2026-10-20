<?php
require_once __DIR__.'/config/database.php';

// Parse .env if exists
$appUrl = '';
if (file_exists(__DIR__.'/.env')) {
    $lines = file(__DIR__.'/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2 && trim($parts[0]) === 'APP_URL') {
            $appUrl = rtrim(trim($parts[1]), '/') . '/';
        }
    }
}

// Ignore APP_URL from .env if running on localhost (for testing)
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    $appUrl = '';
}

if (!$appUrl) {
    // Generate robust base URL fallback
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = dirname($_SERVER['PHP_SELF']);
    $dir = str_replace('\\', '/', $dir);
    if ($dir === '/' || $dir === '\\') { $dir = ''; }
    $appUrl = $protocol . "://" . $host . $dir . "/";
}
$baseUrl = $appUrl;

$apiUrl   = 'api/patients.php';
$refreshSec = defined('REFRESH_SEC') ? REFRESH_SEC : 30;
$hospital = defined('HOSPITAL_NAME') ? HOSPITAL_NAME : 'โรงพยาบาล';
$dept     = defined('DEPT_NAME') ? DEPT_NAME : 'ER';
$appTitle = defined('APP_TITLE') ? APP_TITLE : 'ER PATIENT MONITOR';
?>
<!DOCTYPE html>
<html lang="th">
<head>
<base href="<?= htmlspecialchars($baseUrl) ?>">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($appTitle) ?> | <?= htmlspecialchars($hospital) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/monitor.css?v=<?= time() ?>">
</head>
<body data-api="<?= htmlspecialchars($apiUrl) ?>" data-refresh="<?= $refreshSec ?>">



<div id="app">

  <!-- TOP BAR -->
  <header id="topbar">
    <div id="tb-left">
      <div id="logo-box">+</div>
      <div style="display: flex; flex-direction: column; gap: 0.5vh;">
        <div id="tb-title"><?= htmlspecialchars($appTitle) ?><br><small>ระบบติดตามการประเมินซ้ำแบบเรียลไทม์</small></div>
        <div id="top-alert-btn" style="display:none;" onclick="APP.toggleAlertDropdown()">
          🚨 แจ้งเตือน: <span id="top-alert-count">0</span> เคสเกินเวลา
        </div>
      </div>
    </div>

    <!-- ALERT DROPDOWN -->
    <div id="alert-dropdown" style="display:none;">
      <div id="alert-dropdown-header">
        🚨 รายชื่อผู้ป่วยถึงเวลาประเมินซ้ำ
        <button onclick="APP.toggleAlertDropdown()" class="btn-close-dropdown">✖</button>
      </div>
      <div id="alert-dropdown-body"></div>
    </div>

    <!-- CENTER: LEGENDS AND SUMMARIES -->
    <div id="tb-center">
      <!-- SUMMARY -->
      <div class="tbc-block">
        <div class="tbc-title">สรุปจำนวนผู้ป่วย</div>
        <div class="tbc-items">
          <div class="tb-sum" style="border-left:3px solid #b71c1c;">
            <div class="tb-sum-lbl"><strong style="font-size:1.4vh; color:#fff;">แดง</strong><br>ESI I</div>
            <div class="tb-sum-val" style="color:#ff5252;" id="sum-1">0</div>
          </div>
          <div class="tb-sum" style="border-left:3px solid #ffab40;">
            <div class="tb-sum-lbl"><strong style="font-size:1.4vh; color:#fff;">ส้ม</strong><br>ESI II</div>
            <div class="tb-sum-val" style="color:#ffab40;" id="sum-2">0</div>
          </div>
          <div class="tb-sum" style="border-left:3px solid #ffeb3b;">
            <div class="tb-sum-lbl"><strong style="font-size:1.4vh; color:#fff;">เหลือง</strong><br>ESI III</div>
            <div class="tb-sum-val" style="color:#ffeb3b;" id="sum-3">0</div>
          </div>
          <div class="tb-sum" style="border-left:3px solid #69f0ae;">
            <div class="tb-sum-lbl"><strong style="font-size:1.4vh; color:#fff;">เขียว-ขาว</strong><br>ESI IV-V</div>
            <div class="tb-sum-val" style="color:#69f0ae;" id="sum-4">0</div>
          </div>
        </div>
      </div>

    </div>

    <div id="tb-right">
      <!-- Total Patients Box -->
      <div id="tb-total-wrap">
        <div style="color: #7a9abb; font-size: 1.2vh; text-align: right; line-height: 1.2;">ผู้รับบริการ<br>ทั้งหมด</div>
        <div style="font-size: 3vh; font-weight: bold; color: #fff; padding-left: 0.5vw;" id="tb-total-num">0</div>
      </div>
      
      <div id="tb-clock-wrap">
        <div id="tb-clock">
          <span style="opacity:.6; margin-right:6px; font-size: 2vh;">⏱</span><span id="txt-time">--:--:--</span>
        </div>
        <div id="tb-date">-- -- ----<br>--</div>
      </div>
      <div id="tb-tools">
        <div style="font-size:1vh; color:#ffab40; font-weight:bold; text-align:right;">
          🔔 อัปเดตอัตโนมัติ<br>ทุก <?= $refreshSec ?> วินาที
        </div>
        <div style="display: flex; justify-content: flex-end; gap: 0.5vw; margin-top:0.5vh;">
          <button class="btn-icon" id="btn-sound" onclick="APP.toggleSound()">🔔</button>
          <button class="btn-icon" onclick="APP.toggleFs()">⛶</button>
          <button class="btn-text" onclick="APP.loadDemo()">ข้อมูลจำลอง</button>
        </div>
      </div>
    </div>
  </header>

  <!-- REASSESSMENT INTERVAL HORIZONTAL BAR -->
  <div id="reassess-bar">
    <!-- STATUS LEGEND -->
    <div style="display:flex; align-items:center; gap:0.5vw; background:rgba(0,0,0,0.2); padding:0.5vh 1vw; border-radius:8px;">
      <div style="font-size: 1vh; color: #aaa; font-weight: bold; margin-right:0.5vw;">สัญลักษณ์<br>สถานะ</div>
      <div class="tb-badge" style="background:rgba(56, 142, 60, 0.2);">
        <div style="font-weight:bold; color:#69f0ae;">✔ ปกติ</div>
      </div>
      <div class="tb-badge" style="background:rgba(245, 124, 0, 0.2);">
        <div style="font-weight:bold; color:#ffab40;">⚠️ ใกล้ครบ</div>
      </div>
      <div class="tb-badge" style="background:rgba(211, 47, 47, 0.2); border:1px solid #ff5252;">
        <div style="font-weight:bold; color:#ff5252; animation: pulse 1s infinite;">🚨 เกินเวลา</div>
      </div>
    </div>

    <!-- REASSESSMENT INTERVAL -->
    <div style="font-size: 1.4vh; color: #7a9abb; font-weight: bold; margin-left: 1vw;">รอบการประเมินซ้ำ:</div>
    <div class="reassess-items">
      <div class="reassess-item" style="border-left:3px solid #ff5252;">
        <div style="font-weight:bold; color:#fff;">🔴 แดง <small style="color:#aaa; font-weight:normal;">(ESI I)</small></div>
        <div style="color:#ffab40; font-weight:bold;">10 นาที</div>
      </div>
      <div class="reassess-item" style="border-left:3px solid #ffab40;">
        <div style="font-weight:bold; color:#fff;">🟠 ส้ม <small style="color:#aaa; font-weight:normal;">(ESI II)</small></div>
        <div style="color:#ffab40; font-weight:bold;">30 นาที</div>
      </div>
      <div class="reassess-item" style="border-left:3px solid #ffeb3b;">
        <div style="font-weight:bold; color:#fff;">🟡 เหลือง <small style="color:#aaa; font-weight:normal;">(ESI III)</small></div>
        <div style="color:#ffab40; font-weight:bold;">60 นาที</div>
      </div>
      <div class="reassess-item" style="border-left:3px solid #69f0ae;">
        <div style="font-weight:bold; color:#fff;">🟢 เขียว/ขาว <small style="color:#aaa; font-weight:normal;">(ESI IV-V)</small></div>
        <div style="color:#ffab40; font-weight:bold;">120 นาที</div>
      </div>
    </div>
  </div>

  <!-- MASTER TABLE HEADER (Removed for Column Layout) -->

  <!-- MAIN AREA -->
  <div id="main">
    <div id="zones-col">
      <!-- ZONE 1: Resuscitation -->
      <section class="zone" id="zone-1">
        <div class="zone-head zh-1">
          <div class="zh-title"><span class="esi-dot bg-red"></span> ฉุกเฉินวิกฤต <small>(ESI I)</small></div>
          <div class="zh-timer">⏰ ประเมินซ้ำทุก 10 นาที</div>
          <div class="zh-count"><span id="cnt-1">0</span> ราย</div>
        </div>
        <div class="zone-rows" id="rows-1"></div>
      </section>

      <!-- ZONE 2: Emergency -->
      <section class="zone" id="zone-2">
        <div class="zone-head zh-2">
          <div class="zh-title"><span class="esi-dot bg-orange"></span> ฉุกเฉินเร่งด่วน <small>(ESI II)</small></div>
          <div class="zh-timer">⏰ ประเมินซ้ำทุก 30 นาที</div>
          <div class="zh-count"><span id="cnt-2">0</span> ราย</div>
        </div>
        <div class="zone-rows" id="rows-2"></div>
      </section>

      <!-- ZONE 3: Urgency -->
      <section class="zone" id="zone-3">
        <div class="zone-head zh-3">
          <div class="zh-title"><span class="esi-dot bg-yellow"></span> ฉุกเฉินไม่เร่งด่วน <small>(ESI III)</small></div>
          <div class="zh-timer">⏰ ประเมินซ้ำทุก 60 นาที</div>
          <div class="zh-count"><span id="cnt-3">0</span> ราย</div>
        </div>
        <div class="zone-rows" id="rows-3"></div>
      </section>

      <!-- ZONE 4: Semi/Non-Urgency -->
      <section class="zone" id="zone-4">
        <div class="zone-head zh-4">
          <div class="zh-title"><span class="esi-dot bg-green"></span> ทั่วไป / ไม่ฉุกเฉิน <small>(ESI IV-V)</small></div>
          <div class="zh-timer">⏰ ประเมินซ้ำทุก 120 นาที</div>
          <div class="zh-count"><span id="cnt-4">0</span> ราย</div>
        </div>
        <div class="zone-rows" id="rows-4"></div>
      </section>
    </div><!-- /zones-col -->

  </div><!-- /main -->

  <!-- FOOTER -->
  <footer id="footer" style="display: flex; justify-content: space-between; align-items: center; padding: 1vh 2vw; background: rgba(0,0,0,0.3); border-top: 1px solid rgba(255,255,255,0.1);">
    <div style="display:flex;align-items:center;gap:10px;">
      <span style="font-size:2vh;">🏥</span>
      <div>
        <div style="font-weight:bold; color:#fff; font-size:1.6vh;"><?= htmlspecialchars($hospital) ?></div>
        <div style="color:#7a9abb; font-size:1.2vh;">แผนก <?= htmlspecialchars($dept) ?></div>
      </div>
    </div>
    <div style="text-align:right;">
      <div style="font-weight:bold; color:#fff; font-size:1.4vh;" id="last-upd">อัปเดตล่าสุด: --:--:--</div>
      <div style="color:#7a9abb; font-size:1.2vh;">เชื่อมต่อฐานข้อมูล HOSxP สำเร็จ</div>
    </div>
  </footer>

</div><!-- /app -->

<script src="assets/js/monitor.js?v=<?= time() ?>"></script>
</body>
</html>
<?php
require_once __DIR__ . '/config.php';
requireLogin();

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'admin') {
    die("<h3>Access Denied</h3><p>คุณไม่มีสิทธิ์เข้าถึงหน้านี้ (เฉพาะ Admin เท่านั้น)</p><a href='index.php'>กลับหน้าหลัก</a>");
}

$logFile = __DIR__ . '/login_history.csv';
$logs = [];

if (file_exists($logFile)) {
    if (($handle = fopen($logFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // [0] = time, [1] = pid, [2] = name, [3] = status, [4] = ip, [5] = ua
            if (count($data) >= 6) {
                $logs[] = [
                    'time' => $data[0],
                    'pid' => $data[1],
                    'name' => $data[2],
                    'status' => $data[3],
                    'ip' => $data[4],
                    'ua' => $data[5]
                ];
            }
        }
        fclose($handle);
    }
}

// Sort newest first
usort($logs, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการเข้าใช้งาน — NHSO Claim Detection</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .admin-container { padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        .table-panel { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px; padding: 20px; margin-top: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 14px; }
        th { color: var(--text-muted); font-size: 13px; font-weight: 600; text-transform: uppercase; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .badge-denied { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .ua-text { font-size: 12px; color: var(--text-muted); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; }
    </style>
</head>
<body>

<div class="admin-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin:0; font-size: 24px;">ประวัติการเข้าใช้งานระบบ (Audit Logs)</h1>
            <p style="margin: 5px 0 0 0; color: var(--text-muted);">รายการพยายามเข้าสู่ระบบทั้งหมด (ทั้งสำเร็จและไม่สำเร็จ)</p>
        </div>
        <div>
            <a href="admin_users.php" class="btn btn-primary" style="margin-right: 10px;">👤 จัดการผู้ใช้งาน</a>
            <a href="index.php" class="btn btn-secondary">กลับหน้า Dashboard</a>
        </div>
    </div>

    <div class="table-panel">
        <table>
            <thead>
                <tr>
                    <th>วัน-เวลา (Time)</th>
                    <th>PID / Provider ID</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>สถานะ (Status)</th>
                    <th>IP Address</th>
                    <th>เบราว์เซอร์ (User Agent)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);">ยังไม่มีประวัติการเข้าใช้งาน</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo htmlspecialchars($log['time']); ?></td>
                            <td style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($log['pid']); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['name']); ?></strong></td>
                            <td>
                                <?php 
                                    $statusClass = 'badge-pending';
                                    if ($log['status'] === 'LOGIN_SUCCESS') $statusClass = 'badge-success';
                                    if (strpos($log['status'], 'DENIED') !== false) $statusClass = 'badge-denied';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($log['status']); ?>
                                </span>
                            </td>
                            <td style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($log['ip']); ?></td>
                            <td><span class="ua-text" title="<?php echo htmlspecialchars($log['ua']); ?>"><?php echo htmlspecialchars($log['ua']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php
// api.php
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

// รับข้อมูลจาก Fetch
$rawInput = file_get_contents('php://input') ?: (file_exists('test.json') ? file_get_contents('test.json') : '');
$request = json_decode($rawInput, true);

if (!$request || !isset($request['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$action = $request['action'];
$data = isset($request['data']) ? $request['data'] : [];

try {
    switch ($action) {
        case 'login':
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (isset($user['is_active']) && $user['is_active'] == 0) {
                    echo json_encode(['success' => false, 'message' => 'บัญชีผู้ใช้ถูกระงับการใช้งาน']);
                    break;
                }
                $_SESSION['user'] = [
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'dept' => $user['department'],
                    'fullName' => $user['full_name']
                ];
                
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $stmtLog = $pdo->prepare("INSERT INTO login_history (username, ip_address, user_agent) VALUES (?, ?, ?)");
                $stmtLog->execute([$user['username'], $ip, $ua]);

                echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
            }
            break;

        case 'register':
            $formData = $data['formData'] ?? [];
            $username = $formData['username'] ?? '';
            $password = password_hash($formData['password'] ?? '', PASSWORD_DEFAULT);
            $fullName = $formData['fullName'] ?? '';
            $dept = $formData['dept'] ?? '';

            // ตรวจสอบชื่อผู้ใช้ซ้ำ
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว']);
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, department) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $password, $fullName, $dept])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลงทะเบียน']);
            }
            break;

        case 'changePassword':
            $username = $data['username'] ?? '';
            $currentPassword = $data['currentPassword'] ?? '';
            $newPassword = $data['newPassword'] ?? '';

            if (!isset($_SESSION['user']) || $_SESSION['user']['username'] !== $username) {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }

            $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $hash = $stmt->fetchColumn();

            if (password_verify($currentPassword, $hash)) {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
                $stmt->execute([$newHash, $username]);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
            }
            break;

        case 'createRequest':
            $formData = $data['formData'] ?? [];
            $reqId = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);

            $stmt = $pdo->prepare("INSERT INTO medical_requests (id, username, dept, requester_name, contact_number, fiscal_year, delivery_channel, start_date, end_date, target_date, type, columns, details, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $reqId,
                $formData['user'],
                $formData['dept'],
                $formData['requesterName'],
                $formData['contactNumber'],
                $formData['fiscalYear'],
                $formData['deliveryChannel'],
                $formData['start_date'],
                $formData['end_date'],
                $formData['target_date'],
                $formData['type'],
                $formData['columns'],
                $formData['details'],
                $formData['purpose'] ?? ''
            ]);

            // ส่งแจ้งเตือน Telegram สำหรับคำขอใหม่
            try {
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $link = "{$protocol}://{$host}";
                
                $message = "🔔 <b>มีคำขอข้อมูลเวชระเบียนใหม่!</b>\n"
                         . "━━━━━━━━━━━━━━━━━━━\n"
                         . "📝 <b>รหัสคำขอ:</b> <code>" . htmlspecialchars($reqId) . "</code>\n"
                         . "👤 <b>ผู้ขอข้อมูล:</b> " . htmlspecialchars($formData['requesterName'] ?? '') . "\n"
                         . "🏢 <b>แผนก/ฝ่าย:</b> " . htmlspecialchars($formData['dept'] ?? '') . "\n"
                         . "📞 <b>เบอร์ติดต่อ:</b> " . htmlspecialchars($formData['contactNumber'] ?? '') . "\n"
                         . "📁 <b>ประเภทข้อมูล:</b> " . htmlspecialchars($formData['type'] ?? '') . "\n"
                         . "🎯 <b>วัตถุประสงค์:</b> " . htmlspecialchars($formData['purpose'] ?? '') . "\n"
                         . "📅 <b>วันที่ต้องการใช้:</b> " . htmlspecialchars($formData['target_date'] ?? '') . "\n"
                         . "━━━━━━━━━━━━━━━━━━━\n"
                         . "⚙️ <i>กรุณาเข้าสู่ระบบเพื่อดำเนินการตรวจสอบและอนุมัติ</i>\n"
                         . "🔗 <a href=\"" . $link . "\">เข้าสู่ระบบที่นี่</a>";
                         
                sendTelegramMessage($message);
            } catch (Exception $tgEx) {
                error_log("Telegram notification failed in createRequest: " . $tgEx->getMessage());
            }

            echo json_encode(['success' => true]);
            break;

        case 'updateRequest':
            $formData = $data['formData'] ?? [];
            $reqId = $data['id'] ?? '';
            $username = $formData['user'] ?? '';

            if (!$reqId || !$username) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                break;
            }

            // ตรวจสอบสถานะว่ายังเป็น Pending อยู่หรือไม่
            $stmt = $pdo->prepare("SELECT status FROM medical_requests WHERE id = ? AND username = ?");
            $stmt->execute([$reqId, $username]);
            $currentStatus = $stmt->fetchColumn();

            if ($currentStatus !== 'Pending') {
                echo json_encode(['success' => false, 'message' => 'คำขอนี้ไม่สามารถแก้ไขได้แล้ว (สถานะถูกเปลี่ยนไปแล้ว)']);
                break;
            }

            $stmt = $pdo->prepare("UPDATE medical_requests SET requester_name = ?, contact_number = ?, fiscal_year = ?, delivery_channel = ?, start_date = ?, end_date = ?, target_date = ?, type = ?, columns = ?, details = ?, purpose = ? WHERE id = ? AND username = ?");
            if ($stmt->execute([
                $formData['requesterName'],
                $formData['contactNumber'],
                $formData['fiscalYear'],
                $formData['deliveryChannel'],
                $formData['start_date'],
                $formData['end_date'],
                $formData['target_date'],
                $formData['type'],
                $formData['columns'],
                $formData['details'],
                $formData['purpose'] ?? '',
                $reqId,
                $username
            ])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล']);
            }
            break;

        case 'getRequests':
            $role = $data['role'] ?? 'user';
            $username = $data['username'] ?? '';

            if ($role === 'admin') {
                $stmt = $pdo->prepare("SELECT id, username as user, dept, DATE_FORMAT(request_date, '%Y-%m-%d %H:%i') as request_date, type, columns, start_date, end_date, target_date, details, purpose, status, file_url, requester_name, contact_number, fiscal_year, delivery_channel, approved_by, (SELECT COUNT(*) FROM feedback WHERE feedback.request_id = medical_requests.id) AS has_feedback FROM medical_requests ORDER BY created_at DESC");
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT id, username as user, dept, DATE_FORMAT(request_date, '%Y-%m-%d %H:%i') as request_date, type, columns, start_date, end_date, target_date, details, purpose, status, file_url, requester_name, contact_number, fiscal_year, delivery_channel, approved_by, (SELECT COUNT(*) FROM feedback WHERE feedback.request_id = medical_requests.id) AS has_feedback FROM medical_requests WHERE username = ? ORDER BY created_at DESC");
                $stmt->execute([$username]);
            }
            $result = $stmt->fetchAll();
            echo json_encode($result);
            break;

        case 'updateRequestStatus':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $id = $data['id'] ?? '';
            $status = $data['status'] ?? '';
            $fileUrl = $data['fileUrl'] ?? '';
            
            // Get full name of the logged-in admin/staff
            $adminName = $_SESSION['user']['fullName'] ?? $_SESSION['user']['username'] ?? 'เจ้าหน้าที่';

            $stmt = $pdo->prepare("UPDATE medical_requests SET status = ?, file_url = ?, approved_by = ? WHERE id = ?");
            if ($stmt->execute([$status, $fileUrl, $adminName, $id])) {
                // ส่งแจ้งเตือน Telegram เมื่ออัปเดตสถานะสำเร็จ
                try {
                    // ดึงรายละเอียดคำขอเพื่อส่งแจ้งเตือน Telegram
                    $stmtFetch = $pdo->prepare("SELECT requester_name, dept, type FROM medical_requests WHERE id = ?");
                    $stmtFetch->execute([$id]);
                    $reqDetail = $stmtFetch->fetch();
                    
                    if ($reqDetail) {
                        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                        $link = "{$protocol}://{$host}";
                        
                        $statusEmoji = '🔄';
                        $statusText = $status;
                        if ($status === 'Pending') {
                            $statusEmoji = '⏳';
                            $statusText = 'รอการตรวจสอบ (Pending)';
                        } elseif ($status === 'Processing') {
                            $statusEmoji = '⚙️';
                            $statusText = 'กำลังดำเนินการ (Processing)';
                        } elseif ($status === 'Approved') {
                            $statusEmoji = '✅';
                            $statusText = 'อนุมัติแล้ว (Approved)';
                        } elseif ($status === 'Rejected') {
                            $statusEmoji = '❌';
                            $statusText = 'ปฏิเสธคำขอ (Rejected)';
                        }
                        
                        $hasFileText = "";
                        if (!empty($fileUrl)) {
                            $hasFileText = "\n📎 <b>ไฟล์แนบ:</b> มีการแนบไฟล์เอกสาร";
                        }
                        
                        $message = "📢 <b>อัปเดตสถานะคำขอข้อมูลเวชระเบียน!</b>\n"
                                 . "━━━━━━━━━━━━━━━━━━━\n"
                                 . "📝 <b>รหัสคำขอ:</b> <code>" . htmlspecialchars($id) . "</code>\n"
                                 . "👤 <b>ผู้ขอข้อมูล:</b> " . htmlspecialchars($reqDetail['requester_name'] ?? '') . " (แผนก " . htmlspecialchars($reqDetail['dept'] ?? '') . ")\n"
                                 . "📁 <b>ประเภทข้อมูล:</b> " . htmlspecialchars($reqDetail['type'] ?? '') . "\n"
                                 . "🔄 <b>สถานะล่าสุด:</b> " . $statusEmoji . " <b>" . htmlspecialchars($statusText) . "</b>" . $hasFileText . "\n"
                                 . "👤 <b>ดำเนินการโดย:</b> " . htmlspecialchars($adminName) . "\n"
                                 . "━━━━━━━━━━━━━━━━━━━\n"
                                 . "🔗 <a href=\"" . $link . "\">ตรวจสอบรายละเอียดเพิ่มเติมในระบบ</a>";
                                 
                        sendTelegramMessage($message);
                    }
                } catch (Exception $tgEx) {
                    error_log("Telegram notification failed in updateRequestStatus: " . $tgEx->getMessage());
                }

                // Insert into audit log
                $auditStmt = $pdo->prepare("INSERT INTO audit_log (username, action, details) VALUES (?, ?, ?)");
                $auditStmt->execute([$adminName, 'Update Status', "Updated request $id to $status"]);

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Update failed']);
            }
            break;

        case 'getDashboardData':
            $filterStartDate = $data['startDate'] ?? '';
            $filterEndDate = $data['endDate'] ?? '';
            $filterFiscalYear = $data['fiscalYear'] ?? '';

            $whereRequests = "WHERE 1=1";
            $params = [];
            
            if ($filterFiscalYear) {
                $whereRequests .= " AND fiscal_year = ?";
                $params[] = $filterFiscalYear;
            }
            if ($filterStartDate && $filterEndDate) {
                $whereRequests .= " AND DATE(request_date) >= ? AND DATE(request_date) <= ?";
                $params[] = $filterStartDate;
                $params[] = $filterEndDate;
            }

            // Total requests
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM medical_requests $whereRequests");
            $stmt->execute($params);
            $total = $stmt->fetchColumn();

            // Avg Rating (joining with medical_requests to filter by request_date/fiscal_year)
            $whereFeedback = str_replace("fiscal_year", "m.fiscal_year", $whereRequests);
            $whereFeedback = str_replace("request_date", "m.request_date", $whereFeedback);
            $stmt = $pdo->prepare("SELECT AVG(f.rating) FROM feedback f LEFT JOIN medical_requests m ON f.request_id = m.id $whereFeedback");
            $stmt->execute($params);
            $avgRating = round($stmt->fetchColumn() ?? 0, 1);

            // Chart data
            $stmt = $pdo->prepare("SELECT dept, COUNT(*) as cnt FROM medical_requests $whereRequests GROUP BY dept ORDER BY cnt DESC");
            $stmt->execute($params);
            $chartRows = $stmt->fetchAll();
            $labels = [];
            $counts = [];
            foreach ($chartRows as $row) {
                $labels[] = $row['dept'];
                $counts[] = $row['cnt'];
            }

            // Recent feedback
            $stmt = $pdo->prepare("SELECT f.rating, f.comment, f.username as user, f.dept, DATE_FORMAT(f.timestamp, '%Y-%m-%d %H:%i') as timestamp FROM feedback f LEFT JOIN medical_requests m ON f.request_id = m.id $whereFeedback ORDER BY f.timestamp DESC LIMIT 10");
            $stmt->execute($params);
            $recentFeedback = $stmt->fetchAll();

            echo json_encode([
                'total' => $total,
                'avgRating' => $avgRating,
                'labels' => $labels,
                'counts' => $counts,
                'recentFeedback' => $recentFeedback
            ]);
            break;

        case 'getAuditLog':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $stmt = $pdo->prepare("SELECT username, action, details, DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i:%s') as timestamp FROM audit_log ORDER BY timestamp DESC LIMIT 500");
            $stmt->execute();
            $result = $stmt->fetchAll();
            echo json_encode($result);
            break;

        case 'getLoginHistory':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $stmt = $pdo->prepare("SELECT username, ip_address, user_agent, DATE_FORMAT(login_time, '%Y-%m-%d %H:%i:%s') as login_time FROM login_history ORDER BY login_time DESC LIMIT 500");
            $stmt->execute();
            $result = $stmt->fetchAll();
            echo json_encode($result);
            break;

        case 'submitFeedback':
            $formData = $data['formData'] ?? [];
            $requestId = $formData['requestId'] ?? '';
            
            // ป้องกันการประเมินซ้ำ (One-time evaluation check)
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM feedback WHERE request_id = ?");
            $stmtCheck->execute([$requestId]);
            if ($stmtCheck->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'คุณได้ทำการประเมินสำหรับคำขอนี้ไปแล้ว']);
                break;
            }

            $speed = $formData['speedRating'] ?? 5;
            $accuracy = $formData['accuracyRating'] ?? 5;
            $service = $formData['serviceRating'] ?? 5;
            $overallRating = round(($speed + $accuracy + $service) / 3);

            $stmt = $pdo->prepare("INSERT INTO feedback (request_id, username, dept, rating, speed_rating, accuracy_rating, service_rating, comment) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([
                $requestId,
                $formData['user'],
                $formData['dept'],
                $overallRating,
                $speed,
                $accuracy,
                $service,
                $formData['comment']
            ])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึกการประเมิน']);
            }
            break;

        case 'getAllUsers':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $stmt = $pdo->prepare("SELECT id, username, full_name as fullName, department as dept, role, password, is_active FROM users");
            $stmt->execute();
            $users = $stmt->fetchAll();
            // Hide actual passwords for security, just send placeholder
            foreach ($users as &$u) {
                $u['password'] = '********';
            }
            echo json_encode($users);
            break;

        case 'importUsers':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $userData = $data['userData'] ?? [];
            $imported = 0;
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, department, full_name) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE role=VALUES(role), department=VALUES(department), full_name=VALUES(full_name)");
                foreach ($userData as $u) {
                    $username = $u[1];
                    $password = password_hash($u[2] ?: '1234', PASSWORD_DEFAULT);
                    $role = $u[3];
                    $dept = $u[4];
                    $fullName = $u[5];
                    if ($username) {
                        $stmt->execute([$username, $password, $role, $dept, $fullName]);
                        $imported++;
                    }
                }
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => "Imported $imported users"]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'adminResetPassword':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $targetUsername = $data['targetUsername'] ?? '';
            $newPassword = $data['newPassword'] ?? '';
            
            if (!$targetUsername || strlen($newPassword) < 4) {
                echo json_encode(['success' => false, 'message' => 'Invalid input']);
                break;
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            if ($stmt->execute([$hashedPassword, $targetUsername])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            break;

        case 'adminUpdateUser':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $targetUsername = $data['username'] ?? '';
            $fullName = $data['fullName'] ?? '';
            $dept = $data['dept'] ?? '';
            $role = $data['role'] ?? 'user';
            
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, department = ?, role = ? WHERE username = ?");
            if ($stmt->execute([$fullName, $dept, $role, $targetUsername])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            break;

        case 'adminDeleteUser':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $targetUsername = $data['username'] ?? '';
            if ($targetUsername === $_SESSION['user']['username']) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM users WHERE username = ?");
            if ($stmt->execute([$targetUsername])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            break;

        case 'adminToggleUserStatus':
            if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                break;
            }
            $targetUsername = $data['username'] ?? '';
            $status = isset($data['status']) && $data['status'] ? 1 : 0;
            if ($targetUsername === $_SESSION['user']['username']) {
                echo json_encode(['success' => false, 'message' => 'Cannot disable yourself']);
                break;
            }
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE username = ?");
            if ($stmt->execute([$status, $targetUsername])) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
            break;

        case 'checkSession':
            $botUsername = 'YOUR_BOT_USERNAME';
            if (defined('TELEGRAM_BOT_TOKEN')) {
                if (!isset($_SESSION['bot_username'])) {
                    $token = TELEGRAM_BOT_TOKEN;
                    if (!empty($token) && $token !== 'YOUR_TELEGRAM_BOT_TOKEN') {
                        $url = "https://api.telegram.org/bot" . $token . "/getMe";
                        $options = [
                            'http' => [
                                'method'  => 'GET',
                                'timeout' => 3
                            ]
                        ];
                        $context = stream_context_create($options);
                        $res = @file_get_contents($url, false, $context);
                        if ($res !== FALSE) {
                            $json = json_decode($res, true);
                            if (isset($json['result']['username'])) {
                                $_SESSION['bot_username'] = $json['result']['username'];
                            }
                        }
                    }
                }
                $botUsername = $_SESSION['bot_username'] ?? 'YOUR_BOT_USERNAME';
            }

            if (isset($_SESSION['user'])) {
                echo json_encode(['success' => true, 'user' => $_SESSION['user'], 'botUsername' => $botUsername]);
            } else {
                echo json_encode(['success' => false, 'botUsername' => $botUsername]);
            }
            break;

        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>

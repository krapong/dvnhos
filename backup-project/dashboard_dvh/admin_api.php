<?php
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

// Enforce admin-only access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลส่วนนี้สำหรับผู้ใช้ทั่วไป']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// GET USERS list
if ($action === 'get_users') {
    try {
        $stmt = $pdo->query("SELECT id, username, fullname, role, is_active, created_at FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $users]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// SAVE USER (Create / Update)
if ($action === 'save_user') {
    $id = $_POST['id'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

    if (empty($username) || empty($role)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และเลือกสิทธิ์การเข้าถึง']);
        exit;
    }

    try {
        if (!empty($id)) {
            // Edit Mode
            // 1. Check if username already exists for other users
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว']);
                exit;
            }

            // 2. Prevent admin from editing their own role or deactivating themselves to avoid self-lockout
            if ((int)$id === (int)$_SESSION['user_id']) {
                $role = 'admin'; // Force role to remain admin
                $is_active = 1;  // Force active
            }

            if (!empty($password)) {
                // Update with password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, fullname = ?, role = ?, password = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $fullname, $role, $hashed_password, $is_active, $id]);
            } else {
                // Update without password
                $stmt = $pdo->prepare("UPDATE users SET username = ?, fullname = ?, role = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$username, $fullname, $role, $is_active, $id]);
            }

            echo json_encode(['success' => true, 'message' => 'ปรับปรุงข้อมูลผู้ใช้งานเรียบร้อยแล้ว']);
        } else {
            // Add Mode
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกรหัสผ่าน']);
                exit;
            }

            // Check duplicate
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว']);
                exit;
            }

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, fullname, password, role, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $fullname, $hashed_password, $role, $is_active]);

            echo json_encode(['success' => true, 'message' => 'เพิ่มผู้ใช้งานใหม่เรียบร้อยแล้ว']);
        }
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// TOGGLE USER STATUS
if ($action === 'toggle_user_status') {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสผู้ใช้']);
        exit;
    }

    if ((int)$id === (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'คุณไม่สามารถปิดการใช้งานบัญชีของคุณเองได้']);
        exit;
    }

    try {
        // Fetch current status
        $stmt = $pdo->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบผู้ใช้ในระบบ']);
            exit;
        }

        $new_status = $user['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $id]);

        $status_msg = $new_status ? 'เปิดการใช้งานเรียบร้อยแล้ว' : 'ปิดการใช้งานเรียบร้อยแล้ว';
        echo json_encode(['success' => true, 'message' => $status_msg, 'is_active' => $new_status]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// DELETE USER
if ($action === 'delete_user') {
    $id = $_POST['id'] ?? '';
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบรหัสผู้ใช้ที่ต้องการลบ']);
        exit;
    }

    if ((int)$id === (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'คุณไม่สามารถลบบัญชีของคุณเองได้']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'ลบผู้ใช้งานเรียบร้อยแล้ว']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// SAVE SYSTEM SETTINGS
if ($action === 'save_settings') {
    $default_fy = (int)($_POST['default_fiscal_year'] ?? date('Y'));
    $range_start = (int)($_POST['fiscal_year_range_start'] ?? ($default_fy - 5));
    $range_end = (int)($_POST['fiscal_year_range_end'] ?? ($default_fy + 2));

    if ($range_start > $default_fy || $range_end < $default_fy) {
        echo json_encode(['success' => false, 'message' => 'ปีงบประมาณหลักต้องอยู่ระหว่างปีเริ่มต้นและปีสิ้นสุด']);
        exit;
    }
    
    if ($range_start > $range_end) {
        echo json_encode(['success' => false, 'message' => 'ปีเริ่มต้นไม่สามารถมากกว่าปีสิ้นสุดได้']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        $stmt->execute(['default_fiscal_year', $default_fy, $default_fy]);
        $stmt->execute(['fiscal_year_range_start', $range_start, $range_start]);
        $stmt->execute(['fiscal_year_range_end', $range_end, $range_end]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'บันทึกการตั้งค่าระบบเรียบร้อยแล้ว']);
    } catch (\PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// SAVE TELEGRAM SETTINGS
if ($action === 'save_telegram_settings') {
    $telegram_bot_token = trim($_POST['telegram_bot_token'] ?? '');
    $telegram_chat_id = trim($_POST['telegram_chat_id'] ?? '');

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        $stmt->execute(['telegram_bot_token', $telegram_bot_token, $telegram_bot_token]);
        $stmt->execute(['telegram_chat_id', $telegram_chat_id, $telegram_chat_id]);
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'บันทึกตั้งค่า Telegram เรียบร้อยแล้ว']);
    } catch (\PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// TEST TELEGRAM NOTIFICATION
if ($action === 'test_telegram_notification') {
    require_once 'telegram_helper.php';
    $msg = "🧪 <b>ข้อความทดสอบจากระบบ</b>\n\nการตั้งค่า Telegram ของคุณ (Bot Token & Chat ID) สามารถใช้งานได้ตามปกติครับ! 🎉\n\n⏰ <i>เวลาที่ทดสอบ: " . date('d/m/Y H:i:s') . "</i>";
    $result = sendTelegramNotify($msg);
    
    if ($result) {
        $resObj = json_decode($result, true);
        if (isset($resObj['ok']) && $resObj['ok']) {
            echo json_encode(['success' => true, 'message' => 'ส่งข้อความทดสอบสำเร็จ! โปรดตรวจสอบใน Telegram ของคุณ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ส่งล้มเหลว: ' . ($resObj['description'] ?? 'เกิดข้อผิดพลาดจาก Telegram')]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถส่งได้ กรุณาตั้งค่า Token และ Chat ID ก่อนทำการทดสอบ']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'การดำเนินการไม่ถูกต้อง']);

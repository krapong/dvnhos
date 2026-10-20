<?php
// upload.php
session_start();

header('Content-Type: application/json; charset=utf-8');

// 1. Authentication Check (Must be logged in as admin)
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Admin privileges required']);
    exit;
}

// 2. Check File Upload Parameter
if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file received']);
    exit;
}

$file = $_FILES['file'];

// Check upload error codes
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload'
    ];
    $errMsg = $errorMessages[$file['error']] ?? 'Unknown upload error';
    echo json_encode(['success' => false, 'message' => $errMsg]);
    exit;
}

// 3. Create Upload Directory securely
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// 4. Secure uploads/ directory against script execution
$htaccessPath = $uploadDir . '.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = "# Disable PHP engine and script execution for security\n"
                     . "<Files *>\n"
                     . "    SetHandler default-handler\n"
                     . "</Files>\n"
                     . "RemoveHandler .php .phtml .php3 .php4 .php5 .php6 .php7 .php8 .phps\n"
                     . "RemoveType .php .phtml .php3 .php4 .php5 .php6 .php7 .php8 .phps\n";
    file_put_contents($htaccessPath, $htaccessContent);
}

// 5. Sanitize Filename to prevent directory traversal and clean special characters
$originalName = basename($file['name']);
// Remove any directory structures and force ASCII-only to prevent encoding issues on Windows
$originalName = preg_replace('/[^a-zA-Z0-9\-\.]/', '_', $originalName);

// Generate a highly unique filename to avoid collision
$fileExt = pathinfo($originalName, PATHINFO_EXTENSION);
$fileNameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
$fileNameWithoutExt = trim($fileNameWithoutExt, '_');
if (empty($fileNameWithoutExt)) {
    $fileNameWithoutExt = 'uploaded_file';
}
$newFileName = uniqid() . '_' . $fileNameWithoutExt . ($fileExt ? '.' . $fileExt : '');

$absoluteUploadDir = rtrim(__DIR__, '/\\') . '/uploads/';
$targetFilePathAbsolute = $absoluteUploadDir . $newFileName;
$targetFilePathRelative = 'uploads/' . $newFileName;

// 6. Move the uploaded file
if (move_uploaded_file($file['tmp_name'], $targetFilePathAbsolute)) {
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'fileUrl' => $targetFilePathRelative
    ]);
} else {
    $err = error_get_last();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to move uploaded file. Error: ' . ($err['message'] ?? 'Unknown'),
        'debug' => [
            'tmp_name' => $file['tmp_name'],
            'target' => $targetFilePathAbsolute,
            'is_uploaded' => is_uploaded_file($file['tmp_name'])
        ]
    ]);
}
?>

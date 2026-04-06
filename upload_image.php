<?php
session_start();

// ต้อง login ถึงจะอัปโหลดรูปได้
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];

    // Validate
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Upload error: ' . $file['error']]);
        exit();
    }

    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'ประเภทไฟล์ไม่รองรับ (รองรับ: JPG, PNG, GIF, WebP)']);
        exit();
    }

    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['error' => 'ไฟล์ใหญ่เกินไป (สูงสุด 5MB)']);
        exit();
    }

    // Generate unique filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('img_', true) . '.' . $ext;
    $destPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $url = $protocol . '://' . $host . '/mesashop/uploads/' . $filename;
        echo json_encode(['url' => $url]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'ไม่สามารถบันทึกไฟล์ได้']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
}
?>

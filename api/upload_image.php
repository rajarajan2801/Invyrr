<?php
/**
 * Product Image Upload
 * POST /api/upload_image.php   multipart/form-data  field: image
 * Returns: { success, path }
 */
require __DIR__.'/../includes/db.php';
header('Content-Type: application/json');
startSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

if (empty($_FILES['image'])) {
    jsonError('No file uploaded');
}

$file  = $_FILES['image'];
$error = $file['error'];

if ($error !== UPLOAD_ERR_OK) {
    $msgs = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (php.ini limit)',
        UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit)',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension',
    ];
    jsonError($msgs[$error] ?? 'Upload error ' . $error);
}

// Validate MIME type by reading magic bytes — never trust $_FILES['type']
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mime     = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    jsonError('Only JPEG, PNG, GIF and WebP images are allowed');
}

// Max 4 MB
if ($file['size'] > 4 * 1024 * 1024) {
    jsonError('Image must be under 4 MB');
}

// Create upload directory if needed
$uploadDir = __DIR__ . '/../assets/uploads/products/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate a safe, unique filename
$ext      = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$mime];
$filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    jsonError('Failed to save image');
}

// Return a web-accessible relative path
$webPath = 'assets/uploads/products/' . $filename;
jsonOk(['path' => $webPath], 'Image uploaded');

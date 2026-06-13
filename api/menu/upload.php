<?php
require_once dirname(__DIR__, 2) . '/auth.php';
require_once dirname(__DIR__, 2) . '/db.php';

$user = require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error(405, 'Method not allowed');
}

$slug       = $_GET['restaurant'] ?? DEFAULT_RESTAURANT_SLUG;
$restaurant = get_restaurant($slug);
if (!$restaurant) json_error(404, 'Restaurant not found');

// Admins and managers may only upload for their own restaurant
if ($user['role'] !== 'SUPERADMIN' && $user['restaurantId'] !== $restaurant['id']) {
    json_error(403, 'Access denied to this restaurant');
}

if (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    json_error(400, 'No image file provided');
}

$file = $_FILES['image'];
$upload_errors = [
    UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit',
    UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit',
    UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server',
    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
    UPLOAD_ERR_EXTENSION  => 'Upload blocked by server extension',
];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_error(400, $upload_errors[$file['error']] ?? 'Upload error code ' . $file['error']);
}

// Max 2 MB
if ($file['size'] > 2 * 1024 * 1024) {
    json_error(400, 'Image must be under 2 MB');
}

// Validate real MIME type (not just extension or Content-Type header)
$finfo   = new finfo(FILEINFO_MIME_TYPE);
$mime    = $finfo->file($file['tmp_name']);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];
if (!isset($allowed[$mime])) {
    json_error(400, 'Only JPEG, PNG, GIF, or WebP images are accepted');
}
$ext = $allowed[$mime];

// Prevent path traversal: slug must be alphanumeric with dashes only
if (!preg_match('/^[a-z0-9_-]+$/i', $restaurant['slug'])) {
    json_error(500, 'Invalid restaurant slug');
}

$upload_dir = dirname(__DIR__, 2) . '/uploads/' . $restaurant['slug'] . '/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0775, true)) {
        json_error(500, 'Could not create upload directory');
    }
}

$filename = bin2hex(random_bytes(12)) . '.' . $ext;
$filepath = $upload_dir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    json_error(500, 'Failed to save uploaded image');
}

$url = '/uploads/' . $restaurant['slug'] . '/' . $filename;
json_ok(['url' => $url, 'filename' => $filename]);

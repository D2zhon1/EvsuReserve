<?php
/**
 * Payment proof upload helpers.
 */

function evsu_save_payment_proof(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime    = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!in_array($mime, $allowed, true)) {
        return null;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        return null;
    }

    $dir = dirname(__DIR__) . '/uploads/payments';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return null;
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
    $ext  = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $ext : 'jpg';
    $name = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    return 'uploads/payments/' . $name;
}

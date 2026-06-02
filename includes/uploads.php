<?php
/**
 * Payment proof upload helpers.
 */

/** Total size cap for the uploads directory (5 GB). */
function evsu_uploads_quota_bytes(): int
{
    return 5 * 1024 * 1024 * 1024;
}

function evsu_uploads_root(): string
{
    return dirname(__DIR__) . '/uploads';
}

function evsu_uploads_dir_size(?string $root = null): int
{
    $root = $root ?? evsu_uploads_root();
    if (!is_dir($root)) {
        return 0;
    }

    $size = 0;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iter as $entry) {
        if ($entry->isFile()) {
            $size += $entry->getSize();
        }
    }

    return $size;
}

function evsu_uploads_has_space(int $additionalBytes): bool
{
    return evsu_uploads_dir_size() + $additionalBytes <= evsu_uploads_quota_bytes();
}

/** Last failure from evsu_save_payment_proof: invalid | quota | io */
function evsu_upload_failure_reason(): ?string
{
    return $GLOBALS['evsu_upload_failure'] ?? null;
}

function evsu_save_payment_proof(array $file): ?string
{
    $GLOBALS['evsu_upload_failure'] = null;

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $GLOBALS['evsu_upload_failure'] = 'invalid';

        return null;
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $mime    = mime_content_type($file['tmp_name']) ?: ($file['type'] ?? '');
    if (!in_array($mime, $allowed, true)) {
        $GLOBALS['evsu_upload_failure'] = 'invalid';

        return null;
    }

    $fileSize = (int) ($file['size'] ?? 0);
    if ($fileSize > 5 * 1024 * 1024) {
        $GLOBALS['evsu_upload_failure'] = 'invalid';

        return null;
    }

    if (!evsu_uploads_has_space($fileSize)) {
        $GLOBALS['evsu_upload_failure'] = 'quota';

        return null;
    }

    $dir = evsu_uploads_root() . '/payments';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        $GLOBALS['evsu_upload_failure'] = 'io';

        return null;
    }

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg');
    $ext  = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $ext : 'jpg';
    $name = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        $GLOBALS['evsu_upload_failure'] = 'io';

        return null;
    }

    return 'uploads/payments/' . $name;
}

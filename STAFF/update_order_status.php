<?php
header('Content-Type: application/json');

// Read JSON body or fallback to form data
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}

$id = $input['id'] ?? null;
$status = $input['status'] ?? null;

$allowed = ['pending','paid','processing','ready','completed','cancelled'];

if (!$id || !$status || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// In a real app: update DB here. For now, log the update for debugging.
$logLine = sprintf("[%s] status update: id=%s status=%s\n", date('c'), $id, $status);
$file = __DIR__ . '/status_updates.log';
@file_put_contents($file, $logLine, FILE_APPEND | LOCK_EX);

echo json_encode(['success' => true]);

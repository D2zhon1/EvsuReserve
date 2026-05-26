<?php

function evsu_base_url(): string
{
    static $base = null;
    if ($base === null) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $appRoot = str_replace('\\', '/', realpath(dirname(__DIR__)));
        $base    = rtrim(str_replace($docRoot, '', $appRoot), '/');
    }
    return $base;
}

function evsu_require_login(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . evsu_base_url() . '/login_page.php');
        exit;
    }
}

function evsu_require_roles(array $roles): void
{
    evsu_require_login();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied.';
        exit;
    }
}

function evsu_student_init(mysqli $conn): array
{
    evsu_require_roles(['student']);
    $user_id = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare('SELECT full_name, email, student_id, course, year_level FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        session_destroy();
        header('Location: ../login_page.php');
        exit;
    }

    $user_name  = $user['full_name'];
    $first_name = explode(' ', $user_name)[0];

    $stmt = $conn->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->bind_result($cart_count);
    $stmt->fetch();
    $stmt->close();

    $_SESSION['cart_count'] = (int) $cart_count;

    return [
        'user_id'    => $user_id,
        'user'       => $user,
        'user_name'  => $user_name,
        'first_name' => $first_name,
        'cart_count' => (int) $cart_count,
    ];
}

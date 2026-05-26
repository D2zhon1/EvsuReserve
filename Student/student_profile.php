<?php
session_start();
require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../includes/auth.php';

$ctx        = evsu_student_init($conn);
$user       = $ctx['user'];
$first_name = $ctx['first_name'];
$cart_count = $ctx['cart_count'];

$active_nav = 'profile';
require __DIR__ . '/_layout_top.php';
?>

    <div class="page-header">
      <div>
        <h1 class="page-title">My Profile</h1>
        <p class="page-sub">Account information</p>
      </div>
    </div>

    <div class="profile-card" style="max-width:520px;background:#fff;border-radius:12px;padding:1.5rem;box-shadow:0 2px 12px rgba(0,0,0,.06);">
      <p><strong>Name:</strong> <?= htmlspecialchars($user['full_name']) ?></p>
      <p><strong>Student ID:</strong> <?= htmlspecialchars($user['student_id']) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
      <p><strong>Course:</strong> <?= htmlspecialchars($user['course'] ?? '—') ?></p>
      <p><strong>Year Level:</strong> <?= htmlspecialchars($user['year_level'] ?? '—') ?></p>
    </div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>

<?php
require_once __DIR__ . '/database.php';

$error   = '';
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';

    if ($email === '' || $password === '' || $confirm_pass === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/@evsu\.edu\.ph$/i', $email)) {
        $error = 'Use your @evsu.edu.ph student email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ? AND role = 'student' LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'No student account found for that email.';
        } else {
            $userId = (int) $user['id'];
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $upd    = $conn->prepare('UPDATE users SET password = ? WHERE id = ? AND role = \'student\'');
            $upd->bind_param('si', $hash, $userId);
            $upd->execute();
            $upd->close();

            evsu_log_activity($conn, 'Password Reset', $email, 'student', 'Password updated via forgot password');
            header('Location: login_page.php?reset=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="CSS/login_page.css" />
</head>
<body>
  <div class="page-bg" aria-hidden="true"></div>
  <a href="login_page.php" class="back-home">← Back to Sign In</a>

  <div class="login-card">
    <div class="card-left">
      <div class="school-brand">
        <div class="seal-circle">
          <img src="image/logo.jpg" alt="EVSU" />
        </div>
        <div class="school-name">
          <span class="name-top">STUDENT</span>
          <span class="name-bottom">PASSWORD RESET</span>
        </div>
      </div>
      <div class="photo-shade" aria-hidden="true"></div>
    </div>

    <div class="card-right">
      <h1 class="portal-title">EVSU RESERVE</h1>
      <h2 class="signin-heading">Reset Password</h2>
      <p style="font-size:14px;color:#666;margin-bottom:16px;">
        Enter your student email and choose a new password. For student accounts only.
      </p>

      <?php if ($error): ?>
        <p class="login-error" style="color:#8b0000;margin-bottom:12px;font-size:14px;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="POST" action="forgot-password.php" novalidate>
        <div class="field">
          <input
            type="email"
            name="email"
            placeholder="Student email (@evsu.edu.ph)"
            autocomplete="email"
            value="<?= htmlspecialchars($email) ?>"
            required
          />
        </div>
        <div class="field">
          <input
            type="password"
            name="password"
            placeholder="New password (min. 8 characters)"
            autocomplete="new-password"
            minlength="8"
            required
          />
        </div>
        <div class="field">
          <input
            type="password"
            name="confirm_pass"
            placeholder="Confirm new password"
            autocomplete="new-password"
            minlength="8"
            required
          />
        </div>
        <button type="submit" class="btn-login">Update Password</button>
      </form>

      <div class="form-links" style="justify-content:center;margin-top:1rem;">
        <a href="login_page.php" class="link-maroon">Back to Sign In</a>
      </div>
    </div>
  </div>
</body>
</html>

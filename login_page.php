<?php
session_start();

require_once __DIR__ . '/database.php';

$login_error   = '';
$login_success = isset($_GET['registered'])
    ? 'Account created! Sign in with your email or student ID and password.'
    : '';

if (isset($_GET['reset'])) {
    $login_success = 'Password updated successfully. Sign in with your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['student_id'] ?? $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $login_error = 'Please enter your email or student ID and password.';
    } else {
        $stmt = $conn->prepare(
            'SELECT id, student_id, full_name, email, password, role
             FROM users
             WHERE email = ? OR student_id = ?
             LIMIT 1'
        );
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['student_id'] = $user['student_id'];
                $_SESSION['user_name']  = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role']       = $user['role'];

                evsu_log_activity(
                    $conn,
                    'User Login',
                    $user['email'],
                    $user['role'],
                    'Successful login'
                );

                $redirects = [
                    'student' => 'Student/student_dashboard.php',
                    'cashier' => 'Cashier/cashier_dashboard.php',
                    'staff'   => 'STAFF/StaffDashboard.php',
                    'admin'   => 'Admin/admin_dashboard.php',
                ];

                $role = $user['role'];
                header('Location: ' . ($redirects[$role] ?? 'Student/student_dashboard.php'));
                exit;
            }

            $login_error = 'Incorrect password.';
            evsu_log_activity($conn, 'User Login Failed', $login, '', 'Invalid password');
        } else {
            $login_error = 'Account not found.';
            evsu_log_activity($conn, 'User Login Failed', $login, '', 'Unknown account');
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Portal — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="CSS/login_page.css" />
</head>
<body>

  <!-- Faint campus background -->
  <div class="page-bg" aria-hidden="true"></div>

  <!-- Back to homepage -->
  <a href="landing_page.php" class="back-home">← Back to Homepage</a>

  <!-- Main card -->
  <div class="login-card">

    <!-- LEFT — photo panel -->
    <div class="card-left">
      <div class="school-brand">
        <div class="seal-circle">
           <img src="image/logo.jpg" alt="EVSU" />
          <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24"
               fill="none" stroke="#c8a951" stroke-width="1.8"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>
        <div class="school-name">
          <span class="name-top">WELCOME TO</span>
          <span class="name-bottom">EVSU RESERVE</span>
        </div>
      </div>
      <div class="photo-shade" aria-hidden="true"></div>
    </div>

    <!-- RIGHT — form panel -->
    <div class="card-right">

      <h1 class="portal-title">EVSU RESERVE</h1>
      <h2 class="signin-heading">Sign In</h2>

      <?php if ($login_success): ?>
        <p class="login-error" style="color:#166534;margin-bottom:12px;font-size:14px;"><?= htmlspecialchars($login_success) ?></p>
      <?php endif; ?>
      <?php if ($login_error): ?>
        <p class="login-error" style="color:#8b0000;margin-bottom:12px;font-size:14px;"><?= htmlspecialchars($login_error) ?></p>
      <?php endif; ?>

      <form method="POST" action="login_page.php" novalidate id="login-form">

        <div class="field">
          <input
            type="text"
            id="student_id"
            name="student_id"
            placeholder="Email or Student ID"
            autocomplete="username"
            required
          />
        </div>

        <div class="field">
          <div class="pw-wrap">
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Password"
              autocomplete="current-password"
              required
            />
            <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="Show password">
              <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                   viewBox="0 0 24 24" fill="none" stroke="currentColor"
                   stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login" id="submit-btn">Login</button>

      </form>

      <div class="form-links">
        <a href="forgot-password.php" class="link-maroon">Forgot Password?</a>
        <span>New? <a href="register_page.php" class="link-maroon">Register</a></span>
      </div>

      <p class="terms-text">
        By using this service, you understood and agree to the
        <a href="#" class="link-maroon">EVSU Online Services Terms of Use and Privacy Statement</a>
      </p>

    </div>
  </div>

  <script src="JS/login_page.js"></script>
</body>
</html>

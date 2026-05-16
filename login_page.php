<?php
session_start();

// ── DB config ─────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'evsu_reserve');
define('DB_USER', 'root');
define('DB_PASS', '');
// ─────────────────────────────────────────────────────────────────────────

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password   = trim($_POST['password']   ?? '');

    if (empty($student_id) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare("SELECT * FROM users WHERE student_id = ? LIMIT 1");
            $stmt->execute([$student_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: ' . ($user['role'] === 'admin' ? '/admin/dashboard.php' : '/student/dashboard.php'));
                exit;
            } else {
                $error = 'Invalid Student ID or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again later.';
        }
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
          <!-- Replace with: <img src="evsu-seal.png" alt="EVSU Seal" /> -->
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

      <?php if ($error): ?>
        <div class="alert-error" role="alert">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login_page.php" novalidate id="login-form">

        <div class="field">
          <input
            type="text"
            id="student_id"
            name="student_id"
            placeholder="Email"
            value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>"
            autocomplete="username"
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
        <a href="forgot-password.php" class="link-maroon">Forgot Password ?</a>
        <span>New ? <a href="register.php" class="link-maroon">Register</a></span>
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
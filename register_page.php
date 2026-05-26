<?php
require_once __DIR__ . '/database.php';

$register_error = '';
$form_values = [
    'student_id' => '',
    'name'       => '',
    'email'      => '',
    'course'     => '',
    'year_level' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id   = trim($_POST['student_id'] ?? '');
    $name         = trim($_POST['name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $course       = trim($_POST['course'] ?? '');
    $year_level   = trim($_POST['year_level'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';

    $form_values = compact('student_id', 'name', 'email', 'course', 'year_level');

    if ($student_id === '' || $name === '' || $email === '' || $course === '' || $year_level === '' || $password === '' || $confirm_pass === '') {
        $register_error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $register_error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_pass) {
        $register_error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE student_id = ? OR email = ? LIMIT 1');
        $check->bind_param('ss', $student_id, $email);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $register_error = 'Student ID or email is already registered.';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (student_id, full_name, email, course, year_level, password, role)
                 VALUES (?, ?, ?, ?, ?, ?, 'student')"
            );

            if (!$stmt) {
                $register_error = 'Database error. Please try again.';
            } else {
                $stmt->bind_param('ssssss', $student_id, $name, $email, $course, $year_level, $hashed_password);

                if ($stmt->execute()) {
                    evsu_log_activity($conn, 'User Registered', $email, 'student', "New account: {$student_id}");
                    $stmt->close();
                    header('Location: login_page.php?registered=1');
                    exit;
                }

                $register_error = 'Registration failed: ' . $stmt->error;
                $stmt->close();
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@600;700&family=Source+Sans+3:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="CSS/login_page.css" />
  <link rel="stylesheet" href="CSS/register_page.css" />
</head>
<body>

  <!-- Faint campus background -->
  <div class="page-bg" aria-hidden="true"></div>


  <!-- Main card (wider for register) -->
  <div class="login-card register-card">

    <!-- LEFT — photo panel -->
    <div class="card-left">
      <div class="school-brand">
        <div class="seal-circle">
          <img src="../image/logo.jpg" alt="EVSU" />
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

      <!-- Left panel info blurb -->
      <div class="left-info">
        <div class="left-info-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
               fill="none" stroke="#c8a951" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
          <span>Book school facilities with ease</span>
        </div>
        <div class="left-info-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
               fill="none" stroke="#c8a951" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
            <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
            <line x1="3" y1="10" x2="21" y2="10"/>
          </svg>
          <span>Manage reservations in real-time</span>
        </div>
        <div class="left-info-item">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
               fill="none" stroke="#c8a951" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
          <span>For EVSU students &amp; faculty</span>
        </div>
      </div>

      <div class="photo-shade" aria-hidden="true"></div>
    </div>

    <!-- RIGHT — form panel -->
    <div class="card-right">

      <h1 class="portal-title">EVSU RESERVE</h1>
      <h2 class="signin-heading">Create Account</h2>

      <?php if ($register_error): ?>
        <p style="color:#8b0000;margin-bottom:12px;font-size:14px;"><?= htmlspecialchars($register_error) ?></p>
      <?php endif; ?>

      <form novalidate id="register-form" action="register_page.php" method="POST">

        <!-- Row 1: Student ID + Full Name -->
        <div class="field-row">
          <div class="field">
            <label for="student_id">Student ID</label>
            <input
              type="text"
              id="student_id"
              name="student_id"
              placeholder="e.g. 2021-00001"
              autocomplete="off"
              value="<?= htmlspecialchars($form_values['student_id']) ?>"
            />
          </div>
          <div class="field">
            <label for="name">Full Name</label>
            <input
              type="text"
              id="name"
              name="name"
              placeholder="Juan Dela Cruz"
              autocomplete="name"
              value="<?= htmlspecialchars($form_values['name']) ?>"
            />
          </div>
        </div>

        <!-- Row 2: Email -->
        <div class="field">
          <label for="email">Email Address</label>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="yourname@evsu.edu.ph"
            autocomplete="email"
            value="<?= htmlspecialchars($form_values['email']) ?>"
          />
        </div>

        <!-- Row 3: Course + Year Level -->
        <div class="field-row">
          <div class="field">
            <label for="course">Course / Program</label>
            <select id="course" name="course" required>
              <option value="" disabled <?= $form_values['course'] === '' ? 'selected' : '' ?>>Select course</option>
              <?php
              $courses = ['BSIT' => 'BS Information Technology', 'BSCS' => 'BS Computer Science', 'BSCE' => 'BS Civil Engineering', 'BSEE' => 'BS Electrical Engineering', 'BSME' => 'BS Mechanical Engineering', 'BSED' => 'BS Education', 'BSBA' => 'BS Business Administration', 'OTHER' => 'Other'];
              foreach ($courses as $val => $label):
                  $sel = $form_values['course'] === $val ? 'selected' : '';
              ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="year_level">Year Level</label>
            <select id="year_level" name="year_level" required>
              <option value="" disabled <?= $form_values['year_level'] === '' ? 'selected' : '' ?>>Select year</option>
              <?php
              $year_labels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', 5 => '5th Year'];
              foreach ($year_labels as $y => $label):
                  $sel = $form_values['year_level'] === (string) $y ? 'selected' : '';
              ?>
              <option value="<?= $y ?>" <?= $sel ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Row 4: Password + Confirm Password -->
        <div class="field-row">
          <div class="field">
            <label for="password">Password</label>
            <div class="pw-wrap">
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Min. 8 characters"
                autocomplete="new-password"
              />
              <button type="button" class="pw-toggle" onclick="togglePw('password','eye-icon-1')" aria-label="Show password">
                <svg id="eye-icon-1" xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <!-- Password strength bar -->
            <div class="strength-bar-wrap" id="strength-wrap" style="display:none;">
              <div class="strength-bar" id="strength-bar"></div>
            </div>
            <span class="strength-label" id="strength-label"></span>
          </div>
          <div class="field">
            <label for="confirm_pass">Confirm Password</label>
            <div class="pw-wrap">
              <input
                type="password"
                id="confirm_pass"
                name="confirm_pass"
                placeholder="Re-enter password"
                autocomplete="new-password"
              />
              <button type="button" class="pw-toggle" onclick="togglePw('confirm_pass','eye-icon-2')" aria-label="Show password">
                <svg id="eye-icon-2" xmlns="http://www.w3.org/2000/svg" width="17" height="17"
                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                  <circle cx="12" cy="12" r="3"/>
                </svg>
              </button>
            </div>
            <span class="match-label" id="match-label"></span>
          </div>
        </div>

        <button type="submit" class="btn-login" id="submit-btn">Create Account</button>

      </form>

      <div class="form-links" style="justify-content:center; margin-top: 1rem;">
        <span>Already have an account? <a href="login_page.php" class="link-maroon">Sign In</a></span>
      </div>

      <p class="terms-text">
        By registering, you understood and agree to the
        <a href="#" class="link-maroon">EVSU Online Services Terms of Use and Privacy Statement</a>
      </p>

    </div>
  </div>

  <script src="JS/register_page.js"></script>
</body>
</html>
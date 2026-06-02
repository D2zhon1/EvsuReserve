<?php
session_start();

require_once __DIR__ . '/../database.php';

$user_name  = $_SESSION['user_name']  ?? 'Admin User';
$first_name = explode(' ', $user_name)[0];

$defaults = [
    'system_name'         => 'EVSU RESERVE',
    'contact_email'       => '',
    'announcement'        => '',
    'paymongo_public_key' => '',
    'cash_instructions'   => 'Please pay at the IGP Office cashier window.',
];

$saved_settings = evsu_load_settings($conn);
$errors         = [];
$success        = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $keys = ['system_name', 'contact_email', 'announcement', 'paymongo_public_key', 'cash_instructions'];

    $email = trim($_POST['contact_email'] ?? '');
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Contact Email must be a valid email address.';
    }

    if (empty($errors)) {
        foreach ($keys as $key) {
            $value = trim($_POST[$key] ?? '');
            evsu_save_setting($conn, $key, $value);
            $saved_settings[$key] = $value;
        }
        evsu_log_activity($conn, 'Settings Updated', $_SESSION['user_email'] ?? '', 'admin', 'System settings saved');
        $success = true;
    }
}

$settings = array_merge($defaults, $saved_settings);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>System Settings — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/admin_settings.css"/>
</head>
<body>

<!-- ══ SIDEBAR ══════════════════════════════════════════════════════════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
          <path d="M6 12v5c3 3 9 3 12 0v-5"/>
        </svg>
      </div>
      <div>
        <span class="logo-name">EVSU</span>
        <span class="logo-sub">RESERVE</span>
      </div>
    </div>
    <div class="role-pill">Admin</div>
  </div>

  <nav class="sidebar-nav">
    <a href="admin_dashboard.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="admin_users.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      Users
    </a>

    <a href="admin_settings.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="3"/>
        <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
      </svg>
      Settings
    </a>
  </nav>

  <div class="sidebar-bottom">
    <a href="../logout.php" class="nav-item nav-logout">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
        <polyline points="16 17 21 12 16 7"/>
        <line x1="21" y1="12" x2="9" y2="12"/>
      </svg>
      Sign Out
    </a>
  </div>
</aside>

<!-- ══ MAIN ══════════════════════════════════════════════════════════════ -->
<div class="main-wrap">

  <header class="topbar">
    <button class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="6"  x2="21" y2="6"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <div class="topbar-right">
      <div class="topbar-user">
        <div class="user-avatar"><?= strtoupper(substr($first_name, 0, 1)) ?></div>
        <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
      </div>
    </div>
  </header>

  <main class="page-content">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">System Settings</h1>
        <p class="page-sub">Configure system-wide settings</p>
      </div>
    </div>

    <!-- Toast messages -->
    <?php if ($success): ?>
      <div class="alert alert-success" id="alertBanner">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
        Settings saved successfully.
      </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-error" id="alertBanner">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <div>
          <?php foreach ($errors as $err): ?>
            <p><?= htmlspecialchars($err) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Settings form -->
    <form method="POST" action="" id="settingsForm" novalidate>
      <div class="settings-stack">

        <!-- General Settings -->
        <div class="settings-card">
          <div class="settings-card-header">
            <div class="settings-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
              </svg>
            </div>
            <h3 class="settings-card-title">General Settings</h3>
          </div>

          <div class="fields-stack">

            <div class="field-group">
              <label class="field-label" for="system_name">System Name</label>
              <input
                type="text"
                id="system_name"
                name="system_name"
                class="field-input"
                value="<?= htmlspecialchars($settings['system_name']) ?>"
                required
              />
            </div>

            <div class="field-group">
              <label class="field-label" for="contact_email">Contact Email</label>
              <input
                type="email"
                id="contact_email"
                name="contact_email"
                class="field-input"
                value="<?= htmlspecialchars($settings['contact_email']) ?>"
                placeholder="igp@evsu.edu.ph"
              />
            </div>

            <div class="field-group">
              <label class="field-label" for="announcement">Announcement</label>
              <textarea
                id="announcement"
                name="announcement"
                class="field-textarea"
                rows="3"
                placeholder="System-wide announcement shown to all users"
              ><?= htmlspecialchars($settings['announcement']) ?></textarea>
              <p class="field-hint">Leave blank to hide the announcement banner.</p>
            </div>

          </div>
        </div>

        <!-- Payment Settings -->
        <div class="settings-card">
          <div class="settings-card-header">
            <div class="settings-card-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                   fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/>
              </svg>
            </div>
            <h3 class="settings-card-title">Payment Settings</h3>
          </div>

          <div class="fields-stack">

            <div class="field-group">
              <label class="field-label" for="paymongo_public_key">PayMongo Public Key</label>
              <div class="input-with-toggle">
                <input
                  type="password"
                  id="paymongo_public_key"
                  name="paymongo_public_key"
                  class="field-input"
                  value="<?= htmlspecialchars($settings['paymongo_public_key']) ?>"
                  placeholder="pk_live_..."
                  autocomplete="off"
                />
                <button type="button" class="toggle-visibility" onclick="toggleVisibility('paymongo_public_key', this)" title="Show/hide key">
                  <svg id="eye-show-paymongo_public_key" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                       fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  <svg id="eye-hide-paymongo_public_key" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                       fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                       style="display:none">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                    <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                    <line x1="1" y1="1" x2="23" y2="23"/>
                  </svg>
                </button>
              </div>
              <p class="field-hint">Enter your PayMongo public key for online payment integration.</p>
            </div>

            <div class="field-group">
              <label class="field-label" for="cash_instructions">Cash Payment Instructions</label>
              <textarea
                id="cash_instructions"
                name="cash_instructions"
                class="field-textarea"
                rows="2"
              ><?= htmlspecialchars($settings['cash_instructions']) ?></textarea>
            </div>

          </div>
        </div>

        <!-- Save button -->
        <div class="save-row">
          <button type="submit" name="save_settings" class="btn-save" id="saveBtn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
              <polyline points="17 21 17 13 7 13 7 21"/>
              <polyline points="7 3 7 8 15 8"/>
            </svg>
            <span id="saveBtnLabel">Save Settings</span>
          </button>
          <span class="save-hint" id="saveHint" style="display:none">You have unsaved changes</span>
        </div>

      </div>
    </form>

  </main>
</div><!-- /main-wrap -->

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="../JS/admin_settings.js"></script>
</body>
</html>

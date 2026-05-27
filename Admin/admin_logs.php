<?php
session_start();

require_once __DIR__ . '/../database.php';

$user_name  = $_SESSION['user_name']  ?? 'Admin User';
$first_name = explode(' ', $user_name)[0];

$search = trim($_GET['search'] ?? '');

$all_logs = [];
$sql = 'SELECT id, action, user_email, user_role, details, created_at AS created_date
        FROM activity_logs WHERE 1=1';
$types  = '';
$params = [];

if ($search !== '') {
    $sql     .= ' AND (action LIKE ? OR user_email LIKE ? OR details LIKE ? OR user_role LIKE ?)';
    $types   .= 'ssss';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['id'] = 'LOG-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
    $all_logs[] = $row;
}
$stmt->close();

$filtered_logs = $all_logs;

// ── Action → colour tag mapping ────────────────────────────────────────────
function action_tag(string $action): string {
    $action_lower = strtolower($action);
    if (str_contains($action_lower, 'login failed') || str_contains($action_lower, 'rejected') || str_contains($action_lower, 'cancelled')) {
        return 'tag-red';
    }
    if (str_contains($action_lower, 'verified') || str_contains($action_lower, 'registered') || str_contains($action_lower, 'backup')) {
        return 'tag-green';
    }
    if (str_contains($action_lower, 'login') || str_contains($action_lower, 'placed') || str_contains($action_lower, 'added')) {
        return 'tag-blue';
    }
    if (str_contains($action_lower, 'updated') || str_contains($action_lower, 'changed') || str_contains($action_lower, 'generated')) {
        return 'tag-orange';
    }
    return 'tag-gray';
}

// ── Human-friendly time ────────────────────────────────────────────────────
function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    return date('M j, g:i A', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Activity Logs — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/admin_logs.css"/>
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

    <a href="admin_reports.php" class="nav-item">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="20" x2="18" y2="10"/>
        <line x1="12" y1="20" x2="12" y2="4"/>
        <line x1="6"  y1="20" x2="6"  y2="14"/>
      </svg>
      Reports
    </a>
    <a href="admin_logs.php" class="nav-item active">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
        <polyline points="10 9 9 9 8 9"/>
      </svg>
      Activity Logs
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
        <h1 class="page-title">Activity Logs</h1>
        <p class="page-sub">Monitor system activity and user actions</p>
      </div>
      <span class="log-count-pill"><?= count($filtered_logs) ?> entries</span>
    </div>

    <!-- Search bar -->
    <form method="GET" action="" class="search-form" id="searchForm">
      <div class="search-wrap">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          type="text"
          name="search"
          class="search-input"
          placeholder="Search by action, email, or details…"
          value="<?= htmlspecialchars($search) ?>"
          autocomplete="off"
          id="searchInput"
        />
        <?php if ($search): ?>
          <a href="?" class="search-clear" title="Clear search">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2.5"
                 stroke-linecap="round" stroke-linejoin="round">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </a>
        <?php endif; ?>
      </div>
    </form>

    <!-- Logs card -->
    <div class="logs-card">

      <?php if (empty($filtered_logs)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="1.5"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
          </svg>
          <p class="empty-title">No activity logs found</p>
          <p class="empty-sub">
            <?= $search ? 'Try a different search term.' : 'System activities will be recorded here.' ?>
          </p>
        </div>

      <?php else: ?>
        <div class="logs-list">
          <?php foreach ($filtered_logs as $log):
            $tag  = action_tag($log['action']);
            $time = time_ago($log['created_date']);
            $full_time = date('M j, Y g:i A', strtotime($log['created_date']));
          ?>
          <div class="log-row">
            <!-- Left: dot indicator -->
            <div class="log-dot-wrap">
              <div class="log-dot <?= $tag ?>"></div>
              <div class="log-line"></div>
            </div>

            <!-- Content -->
            <div class="log-body">
              <div class="log-top">
                <div class="log-main">
                  <span class="log-action-tag <?= $tag ?>"><?= htmlspecialchars($log['action']) ?></span>
                  <p class="log-meta">
                    <?= htmlspecialchars($log['user_email']) ?>
                    <?php if ($log['user_role']): ?>
                      <span class="log-role">(<?= htmlspecialchars($log['user_role']) ?>)</span>
                    <?php endif; ?>
                  </p>
                  <?php if ($log['details']): ?>
                    <p class="log-details"><?= htmlspecialchars($log['details']) ?></p>
                  <?php endif; ?>
                </div>
                <time class="log-time" title="<?= $full_time ?>"><?= $time ?></time>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div><!-- /logs-card -->

  </main>
</div><!-- /main-wrap -->

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<script src="../JS/admin_logs.js"></script>
</body>
</html>

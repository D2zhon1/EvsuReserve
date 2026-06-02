<?php
session_start();

require_once __DIR__ . '/../database.php';

$user_name  = $_SESSION['user_name']  ?? 'Admin User';
$first_name = explode(' ', $user_name)[0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    header('Content-Type: application/json');

    $user_id  = (int) preg_replace('/\D/', '', $_POST['user_id'] ?? '');
    $new_role = trim($_POST['role'] ?? '');
    $valid_roles = ['student', 'cashier', 'staff', 'admin'];

    if ($user_id > 0 && in_array($new_role, $valid_roles, true)) {
        $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $new_role, $user_id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            evsu_log_activity($conn, 'Role Updated', $_SESSION['user_email'] ?? '', 'admin', "User #{$user_id} role set to {$new_role}");
            echo json_encode(['success' => true, 'user_id' => (string) $user_id, 'role' => $new_role]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Update failed']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
    exit;
}

// ── Filters ────────────────────────────────────────────────────────────────
$search      = trim($_GET['search'] ?? '');
$role_filter = $_GET['role'] ?? 'all';
$valid_roles = ['all', 'student', 'cashier', 'staff', 'admin'];
if (!in_array($role_filter, $valid_roles)) $role_filter = 'all';

$all_users = [];
$sql = "SELECT id, full_name, email, role, student_id,
               COALESCE(department, course, '') AS department,
               DATE(created_at) AS created_date
        FROM users WHERE 1=1";
$types  = '';
$params = [];

if ($role_filter !== 'all') {
    $sql     .= ' AND role = ?';
    $types   .= 's';
    $params[] = $role_filter;
}
if ($search !== '') {
    $sql     .= ' AND (full_name LIKE ? OR email LIKE ? OR student_id LIKE ?)';
    $types   .= 'sss';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    if ($row['role'] !== 'student') {
        $row['student_id'] = $row['student_id'] ?: '';
    }
    $all_users[] = $row;
}
$stmt->close();

$filtered_users = $all_users;

// ── Role badge config ──────────────────────────────────────────────────────
$role_badge = [
    'admin'   => 'badge-role-admin',
    'staff'   => 'badge-role-staff',
    'cashier' => 'badge-role-cashier',
    'student' => 'badge-role-student',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Management — EVSU Reserve</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/admin_users.css"/>
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
    <a href="admin_users.php" class="nav-item active">
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
        <h1 class="page-title">User Management</h1>
        <p class="page-sub">Manage user accounts and roles</p>
      </div>
      <span class="user-count-pill"><?= count($filtered_users) ?> user<?= count($filtered_users) !== 1 ? 's' : '' ?></span>
    </div>

    <!-- Filters -->
    <form method="GET" action="" class="filters-bar" id="filterForm">
      <div class="search-wrap">
        <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input
          type="text"
          name="search"
          class="search-input"
          placeholder="Search by name or email…"
          value="<?= htmlspecialchars($search) ?>"
          autocomplete="off"
          id="searchInput"
        />
      </div>
      <select name="role" class="filter-select" onchange="this.form.submit()">
        <option value="all"     <?= $role_filter === 'all'     ? 'selected' : '' ?>>All Roles</option>
        <option value="student" <?= $role_filter === 'student' ? 'selected' : '' ?>>Student</option>
        <option value="cashier" <?= $role_filter === 'cashier' ? 'selected' : '' ?>>Cashier</option>
        <option value="staff"   <?= $role_filter === 'staff'   ? 'selected' : '' ?>>Staff</option>
        <option value="admin"   <?= $role_filter === 'admin'   ? 'selected' : '' ?>>Admin</option>
      </select>
      <button type="submit" class="btn-search">Search</button>
    </form>

    <!-- Users table card -->
    <div class="orders-card">

      <?php if (empty($filtered_users)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="1.5"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
          <p class="empty-title">No users found</p>
          <p class="empty-sub">Try adjusting your search or role filter.</p>
        </div>

      <?php else: ?>
        <div class="orders-table-wrap">
          <table class="orders-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Department</th>
                <th>Joined</th>
                <th>Change Role</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered_users as $u):
                $initials  = strtoupper(substr($u['full_name'] ?: 'U', 0, 1));
                $badge_cls = $role_badge[$u['role']] ?? 'badge-role-student';
                $joined    = $u['created_date'] ? date('M j, Y', strtotime($u['created_date'])) : '—';
              ?>
              <tr data-user-id="<?= htmlspecialchars($u['id']) ?>">
                <!-- User -->
                <td>
                  <div class="user-cell">
                    <div class="user-avatar-sm <?= $badge_cls ?>">
                      <?= $initials ?>
                    </div>
                    <div>
                      <p class="user-full-name"><?= htmlspecialchars($u['full_name'] ?: 'Unknown') ?></p>
                      <p class="user-student-id"><?= htmlspecialchars($u['student_id'] ?: '—') ?></p>
                    </div>
                  </div>
                </td>
                <!-- Email -->
                <td class="text-muted td-email"><?= htmlspecialchars($u['email']) ?></td>
                <!-- Role badge -->
                <td>
                  <span class="role-badge <?= $badge_cls ?>"><?= ucfirst($u['role']) ?></span>
                </td>
                <!-- Department -->
                <td class="text-muted"><?= htmlspecialchars($u['department'] ?: '—') ?></td>
                <!-- Joined -->
                <td class="text-muted text-sm"><?= $joined ?></td>
                <!-- Change role dropdown -->
                <td>
                  <select
                    class="role-select"
                    data-user-id="<?= htmlspecialchars($u['id']) ?>"
                    data-current-role="<?= htmlspecialchars($u['role']) ?>"
                    onchange="updateRole(this)"
                  >
                    <option value="student" <?= $u['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="cashier" <?= $u['role'] === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                    <option value="staff"   <?= $u['role'] === 'staff'   ? 'selected' : '' ?>>Staff</option>
                    <option value="admin"   <?= $u['role'] === 'admin'   ? 'selected' : '' ?>>Admin</option>
                  </select>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div><!-- /orders-card -->

  </main>
</div><!-- /main-wrap -->

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script src="../JS/admin_users.js"></script>
</body>
</html>

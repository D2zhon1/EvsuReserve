<?php
/** @var string $active_nav dashboard|products|orders|cart|profile */
/** @var string $first_name */
/** @var int $cart_count */
$page_title = $page_title ?? 'EVSU Reserve';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($page_title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../CSS/student_dashboard.css"/>
</head>
<body>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <div class="logo-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
          <path d="M6 12v5c3 3 9 3 12 0v-5"/>
        </svg>
      </div>
      <div>
        <span class="logo-name">EVSU</span>
        <span class="logo-sub">RESERVE</span>
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="student_dashboard.php" class="nav-item <?= $active_nav === 'dashboard' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Dashboard
    </a>
    <a href="student_product.php" class="nav-item <?= $active_nav === 'products' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 0 1-8 0"/>
      </svg>
      Products
    </a>
    <a href="student_orders.php" class="nav-item <?= $active_nav === 'orders' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
        <path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>
      </svg>
      My Orders
    </a>
    <a href="student_cart.php" class="nav-item <?= $active_nav === 'cart' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      Cart
      <?php if ($cart_count > 0): ?>
        <span class="nav-badge"><?= $cart_count ?></span>
      <?php endif; ?>
    </a>
    <a href="student_profile.php" class="nav-item <?= $active_nav === 'profile' ? 'active' : '' ?>">
      <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
        <circle cx="12" cy="7" r="4"/>
      </svg>
      Profile
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
<div class="main-wrap">
  <header class="topbar">
    <button class="menu-btn" type="button" onclick="toggleSidebar()" aria-label="Toggle menu">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="12" x2="21" y2="12"/>
        <line x1="3" y1="6"  x2="21" y2="6"/>
        <line x1="3" y1="18" x2="21" y2="18"/>
      </svg>
    </button>
    <div class="topbar-right">
      <a href="student_cart.php" class="topbar-cart">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <?php if ($cart_count > 0): ?>
          <span class="cart-dot"><?= $cart_count ?></span>
        <?php endif; ?>
      </a>
      <div class="topbar-user">
        <div class="user-avatar"><?= strtoupper(substr($first_name, 0, 1)) ?></div>
        <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
      </div>
    </div>
  </header>
  <main class="page-content">

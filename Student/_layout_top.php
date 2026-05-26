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
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
      </div>
      <div><span class="logo-name">EVSU</span><span class="logo-sub">RESERVE</span></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="student_dashboard.php" class="nav-item <?= $active_nav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
    <a href="student_product.php" class="nav-item <?= $active_nav === 'products' ? 'active' : '' ?>">Products</a>
    <a href="student_orders.php" class="nav-item <?= $active_nav === 'orders' ? 'active' : '' ?>">My Orders</a>
    <a href="student_cart.php" class="nav-item <?= $active_nav === 'cart' ? 'active' : '' ?>">
      Cart<?php if ($cart_count > 0): ?> <span class="nav-badge"><?= $cart_count ?></span><?php endif; ?>
    </a>
    <a href="student_profile.php" class="nav-item <?= $active_nav === 'profile' ? 'active' : '' ?>">Profile</a>
  </nav>
  <div class="sidebar-bottom">
    <a href="../logout.php" class="nav-item nav-logout">Sign Out</a>
  </div>
</aside>
<div class="main-wrap">
  <header class="topbar">
    <button class="menu-btn" type="button" onclick="toggleSidebar()" aria-label="Menu">☰</button>
    <div class="topbar-right">
      <a href="student_cart.php" class="topbar-cart">Cart<?php if ($cart_count > 0): ?><span class="cart-dot"><?= $cart_count ?></span><?php endif; ?></a>
      <div class="topbar-user"><div class="user-avatar"><?= strtoupper(substr($first_name, 0, 1)) ?></div><span class="user-name"><?= htmlspecialchars($first_name) ?></span></div>
    </div>
  </header>
  <main class="page-content">

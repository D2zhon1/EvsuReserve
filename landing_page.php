<!DOCTYPE html
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EVSU Reserve – University IGP Sales & Inventory</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="CSS/landing_page.css" />
</head>
<body>

  <!-- HEADER -->
  <header class="site-header" id="site-header">
    <div class="container header-inner">
      <div class="logo">
        <div class="logo-icon">
          <!-- Graduation cap SVG -->
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
            <path d="M6 12v5c3 3 9 3 12 0v-5"/>
          </svg>
        </div>
        <span class="logo-text">EVSU <span class="accent">RESERVE</span></span>
      </div>
      <button class="btn btn-primary header-cta" onclick="handleLogin()">
        Sign In
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 12h14M12 5l7 7-7 7"/>
        </svg>
      </button>
    </div>
  </header>

  <!-- HERO -->
  <section class="hero" id="hero">
    <div class="container hero-inner">
      <div class="badge" data-animate="fade-up" data-delay="0">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2.5"
             stroke-linecap="round" stroke-linejoin="round">
          <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
        </svg>
        Eastern Visayas State University
      </div>

      <h1 class="hero-title" data-animate="fade-up" data-delay="100">
        <span class="accent">EVSU RESERVE</span>
        <br />
        SYSTEM
      </h1>

      <p class="hero-sub" data-animate="fade-up" data-delay="200">
        Order uniforms, school supplies, and merchandise online. Track payments,
        manage inventory, and generate reports — all in one streamlined platform.
      </p>

      <div class="hero-actions" data-animate="fade-up" data-delay="300">
        <button class="btn btn-primary btn-lg" onclick="handleLogin()">
          Get Started
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14M12 5l7 7-7 7"/>
          </svg>
        </button>
        <button class="btn btn-outline btn-lg" onclick="handleLogin()">
          Browse Products
        </button>
      </div>
    </div>

    <!-- decorative blobs -->
    <div class="blob blob-1" aria-hidden="true"></div>
    <div class="blob blob-2" aria-hidden="true"></div>
  </section>

  <!-- FEATURES -->
  <section class="features" id="features">
    <div class="container">
      <div class="section-header" data-animate="fade-up">
        <h2>Everything You Need</h2>
        <p>Streamlined university commerce in one platform</p>
      </div>

      <div class="cards-grid">

        <!-- Card 1 -->
        <div class="card" data-animate="fade-up" data-delay="0">
          <div class="card-icon">
            <!-- ShoppingBag -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
              <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
              <line x1="3" y1="6" x2="21" y2="6"/>
              <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
          </div>
          <h3>Online Ordering</h3>
          <p>Browse and order uniforms, ID slings, booklets, and more from your device.</p>
          <ul class="card-list">
            <li><span class="check">✓</span> Product catalog</li>
            <li><span class="check">✓</span> Size selection</li>
            <li><span class="check">✓</span> Shopping cart</li>
            <li><span class="check">✓</span> Order tracking</li>
          </ul>
        </div>

        <!-- Card 2 -->
        <div class="card" data-animate="fade-up" data-delay="120">
          <div class="card-icon">
            <!-- Shield -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </div>
          <h3>Secure Payments</h3>
          <p>Pay online or upload proof of cash payment with full verification tracking.</p>
          <ul class="card-list">
            <li><span class="check">✓</span> Online payments</li>
            <li><span class="check">✓</span> Cash verification</li>
            <li><span class="check">✓</span> Digital receipts</li>
            <li><span class="check">✓</span> Payment history</li>
          </ul>
        </div>

        <!-- Card 3 -->
        <div class="card" data-animate="fade-up" data-delay="240">
          <div class="card-icon">
            <!-- Zap -->
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
              <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
            </svg>
          </div>
          <h3>Smart Management</h3>
          <p>Real-time inventory tracking, automated reports, and activity monitoring.</p>
          <ul class="card-list">
            <li><span class="check">✓</span> Inventory control</li>
            <li><span class="check">✓</span> Sales reports</li>
            <li><span class="check">✓</span> Activity logs</li>
            <li><span class="check">✓</span> User management</li>
          </ul>
        </div>

      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="logo">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" class="accent">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
          <path d="M6 12v5c3 3 9 3 12 0v-5"/>
        </svg>
        <span class="logo-text">EVSU RESERVE</span>
      </div>
      <p class="footer-copy">© 2026 Eastern Visayas State University. All rights reserved.</p>
    </div>
  </footer>

  <script src="JS/landing_page.js"></script>
</body>
</html>
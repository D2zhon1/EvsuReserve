/* =============================================
   EVSU RESERVE — main.js
   ============================================= */

/**
 * handleLogin()
 * Replace the URL below with your actual login/auth endpoint.
 */
function handleLogin() {
  // TODO: Change this to your actual login page URL
  window.location.href = '/login_page.php';
  // window.location.href = '#features';
}

/* ── Sticky header shadow on scroll ── */
(function initHeader() {
  const header = document.getElementById('site-header');
  if (!header) return;

  const onScroll = () => {
    if (window.scrollY > 10) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll(); // run once on load
})();

/* ── Scroll-triggered fade-up animations ── */
(function initAnimations() {
  const elements = document.querySelectorAll('[data-animate]');
  if (!elements.length) return;

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;

        const el    = entry.target;
        const delay = parseInt(el.dataset.delay || '0', 10);

        setTimeout(() => {
          el.classList.add('visible');
        }, delay);

        // Animate only once
        observer.unobserve(el);
      });
    },
    { threshold: 0.15 }
  );

  elements.forEach((el) => observer.observe(el));
})();
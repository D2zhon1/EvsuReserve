/**
 * EVSU RESERVE — staff_dashboard.js
 * Handles sidebar toggle, mobile overlay, and UI interactions.
 */

(function () {
  'use strict';

  /* ── Sidebar toggle ─────────────────────────────────────────── */
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');

  function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  window.toggleSidebar = function () {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  };

  /* Close sidebar when clicking overlay */
  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  /* Close sidebar on Escape key */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeSidebar();
  });

  /* Close sidebar when resizing to desktop */
  window.addEventListener('resize', function () {
    if (window.innerWidth > 768) closeSidebar();
  });

  /* ── Animate stat bars on page load ────────────────────────── */
  function animateBars() {
    const bars = document.querySelectorAll('.ostat-bar');
    bars.forEach(function (bar) {
      const target = bar.style.width;
      bar.style.width = '0';
      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          bar.style.width = target;
        });
      });
    });
  }

  /* ── Stat card counter animation ───────────────────────────── */
  function animateCounters() {
    const values = document.querySelectorAll('.stat-value');
    values.forEach(function (el) {
      const text = el.textContent.trim();

      // Handle peso shorthand like ₱48.8k
      const pesoMatch = text.match(/^₱([\d.]+)k$/);
      if (pesoMatch) {
        const target = parseFloat(pesoMatch[1]);
        let current = 0;
        const step = target / 30;
        const timer = setInterval(function () {
          current = Math.min(current + step, target);
          el.textContent = '₱' + current.toFixed(1) + 'k';
          if (current >= target) clearInterval(timer);
        }, 25);
        return;
      }

      // Handle plain integers
      const intMatch = text.match(/^(\d+)$/);
      if (intMatch) {
        const target = parseInt(intMatch[1], 10);
        if (target === 0) return;
        let current = 0;
        const duration = 600;
        const startTime = performance.now();
        function tick(now) {
          const elapsed = now - startTime;
          const progress = Math.min(elapsed / duration, 1);
          // Ease out cubic
          const eased = 1 - Math.pow(1 - progress, 3);
          current = Math.round(eased * target);
          el.textContent = current;
          if (progress < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
      }
    });
  }

  /* ── Row highlight on quick-action click ───────────────────── */
  function initRowActions() {
    const actionLinks = document.querySelectorAll('.action-link');
    actionLinks.forEach(function (link) {
      link.addEventListener('click', function () {
        const row = link.closest('tr');
        if (row) {
          row.style.background = '#fdf2f2';
          setTimeout(function () { row.style.background = ''; }, 400);
        }
      });
    });
  }

  /* ── Toast notification helper (for future use) ──────────────
     Usage: showToast('Order updated!', 'success')
     Types: 'success' | 'error' | 'info'
  ─────────────────────────────────────────────────────────────── */
  window.showToast = function (message, type) {
    type = type || 'info';
    var toast = document.createElement('div');
    toast.className = 'sd-toast sd-toast-' + type;
    toast.textContent = message;

    var styleMap = {
      success: 'background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;',
      error:   'background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;',
      info:    'background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;',
    };

    toast.style.cssText = [
      'position:fixed;bottom:1.5rem;right:1.5rem;',
      'padding:.65rem 1.1rem;border-radius:8px;',
      'font-family:Source Sans 3,sans-serif;font-size:.875rem;font-weight:600;',
      'z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,.12);',
      'animation:fadeUp .3s ease both;',
      styleMap[type] || styleMap.info,
    ].join('');

    document.body.appendChild(toast);
    setTimeout(function () {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity .3s ease';
      setTimeout(function () { toast.remove(); }, 320);
    }, 3000);
  };

  /* ── Init ───────────────────────────────────────────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    animateBars();
    animateCounters();
    initRowActions();
  });

})();
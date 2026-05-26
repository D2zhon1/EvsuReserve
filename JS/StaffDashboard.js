/* =============================================
   EVSU RESERVE — staff_dashboard.js
   ============================================= */

/* ── Auto-refresh low-stock count badge in sidebar (optional) ────────────
   Uncomment and point to your API endpoint if you want live updates.

   setInterval(async () => {
     try {
       const res  = await fetch('../api/low_stock_count.php');
       const data = await res.json();
       const badge = document.getElementById('low-stock-badge');
       if (badge) badge.textContent = data.count;
     } catch (e) { console.warn('Low-stock refresh failed', e); }
   }, 60_000);
──────────────────────────────────────────────────────────────────────── */

/* ── Animate stat cards on page load ── */
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('.stat-card');
  cards.forEach((card, i) => {
    card.style.animationDelay = `${0.05 + i * 0.07}s`;
  });

  /* Animate numeric stat values counting up */
  document.querySelectorAll('.stat-value').forEach(el => {
    const raw = el.textContent.trim();

    /* Only animate plain integers (skip ₱ revenue string) */
    if (/^\d+$/.test(raw)) {
      const target = parseInt(raw, 10);
      if (target === 0) return;

      let current  = 0;
      const step   = Math.ceil(target / 30);
      const timer  = setInterval(() => {
        current += step;
        if (current >= target) {
          el.textContent = target;
          clearInterval(timer);
        } else {
          el.textContent = current;
        }
      }, 30);
    }
  });
});
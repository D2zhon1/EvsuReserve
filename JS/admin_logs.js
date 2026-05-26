/* =============================================
   EVSU RESERVE — admin_logs.js
   Live search, sidebar toggle, auto-refresh
   ============================================= */

'use strict';

/* ══════════════════════════════════════════════
   SIDEBAR TOGGLE
   ══════════════════════════════════════════════ */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  const isOpen  = sidebar.classList.contains('open');
  sidebar.classList.toggle('open', !isOpen);
  overlay.classList.toggle('open', !isOpen);
  document.body.style.overflow = isOpen ? '' : 'hidden';
}
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('open')) toggleSidebar();
  }
});

/* ══════════════════════════════════════════════
   LIVE CLIENT-SIDE SEARCH
   Filters rows instantly as you type;
   debounced at 200ms for performance.
   Also updates the entry count pill.
   ══════════════════════════════════════════════ */
(function initLiveSearch() {
  const input    = document.getElementById('searchInput');
  const form     = document.getElementById('searchForm');
  const countPill = document.querySelector('.log-count-pill');
  if (!input) return;

  let debounceTimer;

  input.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => filterRows(this.value.trim()), 200);
  });

  function filterRows(query) {
    const rows    = document.querySelectorAll('.log-row');
    const q       = query.toLowerCase();
    let   visible = 0;

    rows.forEach(row => {
      const text    = row.textContent.toLowerCase();
      const matches = !q || text.includes(q);
      row.style.display = matches ? '' : 'none';
      if (matches) visible++;
    });

    /* Update entry count pill */
    if (countPill) {
      countPill.textContent = visible + ' entr' + (visible === 1 ? 'y' : 'ies');
    }

    /* Show/hide empty state */
    let emptyEl = document.getElementById('liveEmpty');
    const list  = document.querySelector('.logs-list');
    if (list) {
      if (visible === 0 && q) {
        if (!emptyEl) {
          emptyEl = document.createElement('div');
          emptyEl.id = 'liveEmpty';
          emptyEl.className = 'empty-state';
          emptyEl.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="1.5"
                 stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <p class="empty-title">No results found</p>
            <p class="empty-sub">Try a different search term.</p>
          `;
          list.after(emptyEl);
        }
        emptyEl.style.display = '';
      } else if (emptyEl) {
        emptyEl.style.display = 'none';
      }
    }
  }

  /* Submit form on Enter (server-side filter as fallback) */
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') { form.submit(); }
  });
})();

/* ══════════════════════════════════════════════
   RELATIVE TIME — re-renders every 30 seconds
   so "5m ago", "1h ago" etc. stay current
   ══════════════════════════════════════════════ */
(function initRelativeTime() {
  function timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)    return 'Just now';
    if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
           + ', '
           + d.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' });
  }

  function refresh() {
    document.querySelectorAll('time.log-time[title]').forEach(el => {
      const raw = el.getAttribute('title');
      if (raw) {
        /* title is the full formatted string; parse date from data attr if present */
        const iso = el.dataset.iso;
        if (iso) el.textContent = timeAgo(iso);
      }
    });
  }

  /* Store ISO strings on each <time> element for refresh */
  document.querySelectorAll('time.log-time').forEach(el => {
    const title = el.getAttribute('title'); // "May 16, 2026 2:30 PM"
    if (title) {
      const parsed = new Date(title);
      if (!isNaN(parsed)) el.dataset.iso = parsed.toISOString();
    }
  });

  setInterval(refresh, 30_000);
})();

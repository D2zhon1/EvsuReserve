/* =============================================
   EVSU RESERVE — admin_dashboard.js
   Chart.js 4 — Bar (Orders by Status),
   Doughnut (Products by Category)
   ============================================= */

'use strict';

/* ── Design tokens ── */
const COLOR = {
  maroon:      '#8b0000',
  maroonAlpha: 'rgba(139,0,0,0.10)',
  gold:        '#c8a951',
  border:      '#e8e0d8',
  muted:       '#7a6a5a',
  text:        '#1a1208',

  pie: [
    '#8b0000',  // maroon
    '#c8a951',  // gold
    '#1d7fc4',  // blue
    '#15803d',  // green
    '#7e3af2',  // purple
    '#d97706',  // orange
  ],
};

/* Global Chart.js defaults */
Chart.defaults.font.family = "'Source Sans 3', sans-serif";
Chart.defaults.font.size   = 12;
Chart.defaults.color       = COLOR.muted;
Chart.defaults.plugins.legend.display = false;

const tooltipDefaults = {
  backgroundColor: '#fff',
  titleColor:      COLOR.text,
  bodyColor:       COLOR.muted,
  borderColor:     COLOR.border,
  borderWidth:     1,
  padding:         10,
  cornerRadius:    8,
  boxPadding:      4,
};

/* ══════════════════════════════════════════════
   1. ORDERS BY STATUS — Vertical Bar Chart
   ══════════════════════════════════════════════ */
(function buildOrdersChart() {
  const canvas = document.getElementById('ordersChart');
  if (!canvas) return;

  const { labels, counts } = CHART_DATA.orderStatus;

  new Chart(canvas, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Orders',
        data:  counts,
        backgroundColor: COLOR.maroon,
        borderRadius:    { topLeft: 6, topRight: 6 },
        borderSkipped:   'bottom',
        barThickness:    36,
      }],
    },
    options: {
      responsive:          true,
      maintainAspectRatio: false,
      scales: {
        x: {
          grid:   { display: false },
          border: { display: false },
          ticks:  { font: { weight: '600' } },
        },
        y: {
          grid:   { color: COLOR.border },
          border: { dash: [4, 4], display: false },
          ticks:  { precision: 0 },
          beginAtZero: true,
        },
      },
      plugins: {
        tooltip: {
          ...tooltipDefaults,
          callbacks: {
            label: item => ` ${item.raw} order${item.raw !== 1 ? 's' : ''}`,
          },
        },
      },
    },
  });
})();

/* ══════════════════════════════════════════════
   2. PRODUCTS BY CATEGORY — Doughnut Chart
   ══════════════════════════════════════════════ */
(function buildCategoryChart() {
  const canvas = document.getElementById('categoryChart');
  if (!canvas) return;

  const { labels, values } = CHART_DATA.category;
  const colors = labels.map((_, i) => COLOR.pie[i % COLOR.pie.length]);

  new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data:            values,
        backgroundColor: colors,
        borderColor:     '#fff',
        borderWidth:     3,
        hoverOffset:     8,
      }],
    },
    options: {
      responsive:          true,
      maintainAspectRatio: false,
      cutout:              '60%',
      plugins: {
        tooltip: {
          ...tooltipDefaults,
          callbacks: {
            label: item => ` ${item.label}: ${item.raw} product${item.raw !== 1 ? 's' : ''}`,
          },
        },
      },
    },
  });

  /* Build custom legend */
  const legend = document.getElementById('categoryLegend');
  if (legend) {
    labels.forEach((label, i) => {
      const item = document.createElement('div');
      item.className = 'legend-item';
      item.innerHTML = `
        <span class="legend-dot" style="background:${colors[i]}"></span>
        <span>${label}: ${values[i]}</span>
      `;
      legend.appendChild(item);
    });
  }
})();

/* ══════════════════════════════════════════════
   Sidebar toggle (mobile)
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

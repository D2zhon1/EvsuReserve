/* =============================================
   EVSU RESERVE — cashier_reports.js
   Chart.js 4 — Line, Horizontal Bar, Doughnut
   ============================================= */

'use strict';

/* ── Design tokens (mirrors CSS vars) ── */
const COLOR = {
  maroon:      '#8b0000',
  maroonAlpha: 'rgba(139,0,0,0.10)',
  gold:        '#c8a951',
  goldSolid:   '#c8a951',
  border:      '#e8e0d8',
  muted:       '#7a6a5a',
  text:        '#1a1208',
  bg:          '#f7f3f0',

  // Pie palette
  pie: [
    '#8b0000',   // maroon
    '#c8a951',   // gold
    '#1d4ed8',   // blue
    '#15803d',   // green
    '#c2410c',   // orange-red
  ],
};

/* Global Chart.js defaults */
Chart.defaults.font.family   = "'Source Sans 3', sans-serif";
Chart.defaults.font.size     = 12;
Chart.defaults.color         = COLOR.muted;
Chart.defaults.plugins.legend.display = false;

/* ── Shared tooltip style ── */
const tooltipPlugin = {
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
   1. REVENUE TREND — Line Chart
   ══════════════════════════════════════════════ */
(function buildRevenueChart() {
  const canvas = document.getElementById('revenueChart');
  if (!canvas) return;

  const { labels, revenue, allLabels } = CHART_DATA.revenue;

  new Chart(canvas, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Revenue',
        data:  revenue,
        borderColor:     COLOR.maroon,
        backgroundColor: COLOR.maroonAlpha,
        borderWidth:     2.5,
        pointRadius:     0,
        pointHoverRadius: 5,
        pointHoverBackgroundColor: COLOR.maroon,
        pointHoverBorderColor:     '#fff',
        pointHoverBorderWidth:     2,
        tension: 0.35,
        fill: true,
      }],
    },
    options: {
      responsive:          true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      scales: {
        x: {
          grid: { color: COLOR.border, drawTicks: false },
          border: { dash: [4, 4] },
          ticks: { maxRotation: 0, autoSkip: false },
        },
        y: {
          grid: { color: COLOR.border, drawTicks: false },
          border: { dash: [4, 4], display: false },
          ticks: {
            callback: v => v === 0 ? '0' : '₱' + v.toLocaleString(),
          },
          beginAtZero: true,
        },
      },
      plugins: {
        tooltip: {
          ...tooltipPlugin,
          callbacks: {
            title:  items => allLabels[items[0].dataIndex] || items[0].label,
            label:  item  => ' ₱' + (item.raw || 0).toLocaleString(),
          },
        },
      },
    },
  });
})();

/* ══════════════════════════════════════════════
   2. TOP PRODUCTS — Horizontal Bar Chart
   ══════════════════════════════════════════════ */
(function buildProductsChart() {
  const canvas = document.getElementById('productsChart');
  if (!canvas) return;

  const { names, revenue } = CHART_DATA.products;

  // Shorten long product names
  const shortNames = names.map(n => n.length > 18 ? n.slice(0, 16) + '…' : n);

  new Chart(canvas, {
    type: 'bar',
    data: {
      labels: shortNames,
      datasets: [{
        label:           'Revenue',
        data:            revenue,
        backgroundColor: COLOR.gold,
        borderRadius:    { topRight: 6, bottomRight: 6 },
        borderSkipped:   'left',
        barThickness:    22,
      }],
    },
    options: {
      indexAxis:           'y',      // horizontal bars
      responsive:          true,
      maintainAspectRatio: false,
      scales: {
        x: {
          grid:   { color: COLOR.border },
          border: { dash: [4, 4], display: false },
          ticks: { callback: v => '₱' + v.toLocaleString() },
          beginAtZero: true,
        },
        y: {
          grid:   { display: false },
          border: { display: false },
          ticks:  { font: { weight: '600' } },
        },
      },
      plugins: {
        tooltip: {
          ...tooltipPlugin,
          callbacks: {
            title: items => names[items[0].dataIndex],  // full name in tooltip
            label: item  => ' ₱' + (item.raw || 0).toLocaleString(),
          },
        },
      },
    },
  });
})();

/* ══════════════════════════════════════════════
   3. PAYMENT METHODS — Doughnut Chart
   ══════════════════════════════════════════════ */
(function buildMethodsChart() {
  const canvas = document.getElementById('methodsChart');
  if (!canvas) return;

  const { labels, values } = CHART_DATA.methods;
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
      cutout:              '62%',
      plugins: {
        tooltip: {
          ...tooltipPlugin,
          callbacks: {
            label: item => ` ${item.label}: ${item.raw} payment${item.raw !== 1 ? 's' : ''}`,
          },
        },
      },
    },
  });

  // Build custom legend
  const legend = document.getElementById('pieLegend');
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

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('open')) toggleSidebar();
  }
});

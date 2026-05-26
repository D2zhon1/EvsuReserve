  </main>
</div>
<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>
<div class="toast" id="toast"></div>
<?php if (!empty($toast_msg)): ?>
<script>window.addEventListener('DOMContentLoaded',()=>{if(typeof showToast==='function')showToast(<?= json_encode($toast_msg) ?>, <?= json_encode($toast_type ?? 'success') ?>);});</script>
<?php endif; ?>
<script src="../JS/student_dashboard.js"></script>
</body>
</html>

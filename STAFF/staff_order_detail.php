<?php
// Compatibility redirect for legacy order detail URLs.
$id = isset($_GET['id']) ? '&id=' . urlencode($_GET['id']) : '';
header('Location: OrderManagement.php?' . ltrim($id, '&'));
exit;

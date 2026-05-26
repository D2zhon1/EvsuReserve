<?php
// Compatibility redirect for legacy staff orders link.
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: OrderManagement.php' . $query);
exit;

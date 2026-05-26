<?php
// Compatibility redirect for legacy staff products link.
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ProductManagement.php' . $query);
exit;

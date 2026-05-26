<?php
require_once dirname(__DIR__) . '/database.php';
$r = $conn->query('SELECT COUNT(*) AS c FROM users');
echo 'users=' . $r->fetch_assoc()['c'] . PHP_EOL;
$r = $conn->query('SELECT COUNT(*) AS c FROM payments');
echo 'payments=' . $r->fetch_assoc()['c'] . PHP_EOL;
echo "bootstrap OK\n";

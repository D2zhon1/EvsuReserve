<?php
/**
 * CLI test: simulates a fresh PC (drops DB, then runs auto-install).
 * Run: php database/test_bootstrap.php
 */
require_once __DIR__ . '/bootstrap.php';

$cfg  = evsu_db_config();
$name = $cfg['database'];

echo "Connecting to MySQL server...\n";
$server = evsu_mysqli_connect_server();

if (evsu_database_exists($server, $name)) {
    echo "Dropping existing database `{$name}` for clean test...\n";
    $safe = $server->real_escape_string($name);
    $server->query("DROP DATABASE `{$safe}`");
}

evsu_bootstrap_reset();
echo "Running auto-install...\n";
evsu_bootstrap_database();

$server->select_db($name);
$tables = ['users', 'products', 'cart_items', 'orders', 'order_items', 'payments', 'system_settings', 'activity_logs'];
foreach ($tables as $table) {
    $ok = evsu_table_exists($server, $table);
    echo ($ok ? '[OK]' : '[FAIL]') . " table {$table}\n";
    if (!$ok) {
        exit(1);
    }
}

$users = (int) $server->query('SELECT COUNT(*) AS c FROM users')->fetch_assoc()['c'];
echo "Demo users seeded: {$users}\n";

$conn = evsu_db_connect();
echo "App connection OK. Database `{$name}` is ready.\n";

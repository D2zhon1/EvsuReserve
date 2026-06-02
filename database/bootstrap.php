<?php
/**
 * Database connection and shared helpers.
 * Create the database and tables manually (see database/README.md).
 */

function evsu_db_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function evsu_db_fail(string $message): void
{
    http_response_code(500);
    die(
        '<h1>EVSU Reserve — Database connection failed</h1>'
        . '<p>' . htmlspecialchars($message) . '</p>'
        . '<p>Start MySQL in XAMPP, create/import the database, and check '
        . '<code>database/config.php</code> (or <code>config.local.php</code>).</p>'
        . '<p>See <code>database/README.md</code> for import instructions.</p>'
    );
}

function evsu_table_exists(mysqli $db, string $table): bool
{
    $table  = $db->real_escape_string($table);
    $result = $db->query("SHOW TABLES LIKE '{$table}'");
    return $result && $result->num_rows > 0;
}

function evsu_column_exists(mysqli $db, string $table, string $column): bool
{
    $table  = $db->real_escape_string($table);
    $column = $db->real_escape_string($column);
    $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && $result->num_rows > 0;
}

function evsu_db_connect(): mysqli
{
    $cfg = evsu_db_config();
    mysqli_report(MYSQLI_REPORT_OFF);

    try {
        $conn = new mysqli(
            $cfg['host'],
            $cfg['username'],
            $cfg['password'],
            $cfg['database']
        );
    } catch (mysqli_sql_exception $e) {
        evsu_db_fail('MySQL connection failed: ' . $e->getMessage());
    }

    if ($conn->connect_error) {
        evsu_db_fail('Connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function evsu_pdo_connect(): PDO
{
    $cfg = evsu_db_config();

    return new PDO(
        "mysql:host={$cfg['host']};dbname={$cfg['database']};charset=utf8mb4",
        $cfg['username'],
        $cfg['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

function evsu_log_activity(mysqli $conn, string $action, ?string $email, ?string $role, string $details): void
{
    $ip   = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare(
        'INSERT INTO activity_logs (action, user_email, user_role, details, ip_address) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('sssss', $action, $email, $role, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

function evsu_load_settings(mysqli $conn): array
{
    $defaults = [
        'system_name'         => 'EVSU RESERVE',
        'contact_email'       => '',
        'announcement'        => '',
        'paymongo_public_key' => '',
        'cash_instructions'   => 'Please pay at the IGP Office cashier window.',
    ];

    $result = $conn->query('SELECT setting_key, setting_value FROM system_settings');
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $defaults[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $defaults;
}

function evsu_save_setting(mysqli $conn, string $key, string $value): void
{
    $stmt = $conn->prepare(
        'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->bind_param('ss', $key, $value);
    $stmt->execute();
    $stmt->close();
}

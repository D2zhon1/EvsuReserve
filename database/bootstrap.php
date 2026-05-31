<?php
/**
 * Creates database and tables on first run (e.g. new PC / fresh XAMPP).
 * No demo data is inserted. Real data added later is never wiped.
 */

function evsu_db_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function evsu_bootstrap_is_done(): bool
{
    return !empty($GLOBALS['evsu_bootstrap_done']);
}

function evsu_bootstrap_reset(): void
{
    $GLOBALS['evsu_bootstrap_done'] = false;
}

function evsu_bootstrap_fail(string $message): void
{
    evsu_bootstrap_reset();
    http_response_code(500);
    die(
        '<h1>EVSU Reserve — Database setup failed</h1>'
        . '<p>' . htmlspecialchars($message) . '</p>'
        . '<p>Check that MySQL is running in XAMPP and credentials in '
        . '<code>database/config.php</code> (or <code>config.local.php</code>) are correct.</p>'
    );
}

function evsu_mysqli_connect_server(): mysqli
{
    $cfg = evsu_db_config();
    mysqli_report(MYSQLI_REPORT_OFF);

    try {
        $conn = new mysqli($cfg['host'], $cfg['username'], $cfg['password']);
    } catch (mysqli_sql_exception $e) {
        evsu_bootstrap_fail('MySQL connection failed: ' . $e->getMessage());
    }

    if ($conn->connect_error) {
        evsu_bootstrap_fail('MySQL connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function evsu_database_exists(mysqli $server, string $database): bool
{
    $db = $server->real_escape_string($database);
    $res = $server->query(
        "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '{$db}' LIMIT 1"
    );
    return $res && $res->num_rows > 0;
}

function evsu_bootstrap_database(): void
{
    if (evsu_bootstrap_is_done()) {
        return;
    }

    $cfg  = evsu_db_config();
    $name = $cfg['database'];

    $root = evsu_mysqli_connect_server();

    $safeName = $root->real_escape_string($name);
    if (!$root->query(
        "CREATE DATABASE IF NOT EXISTS `{$safeName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    )) {
        evsu_bootstrap_fail('Could not create database: ' . $root->error);
    }

    if (!$root->select_db($name)) {
        evsu_bootstrap_fail('Could not open database "' . htmlspecialchars($name) . '": ' . $root->error);
    }

    $root->query('SET FOREIGN_KEY_CHECKS = 0');
    evsu_run_schema($root);
    $root->query('SET FOREIGN_KEY_CHECKS = 1');

    evsu_run_migrations($root);

    // Only seed default system settings (non-destructive — uses INSERT IGNORE).
    // Never seeds users, products, orders, payments, or logs.
    evsu_seed_default_settings($root);

    $root->close();
    $GLOBALS['evsu_bootstrap_done'] = true;
}

function evsu_run_schema(mysqli $db): void
{
    $statements = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id VARCHAR(50) NOT NULL UNIQUE,
            full_name VARCHAR(120) NOT NULL,
            email VARCHAR(120) NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('student','cashier','staff','admin') NOT NULL DEFAULT 'student',
            course VARCHAR(80) NULL,
            year_level VARCHAR(20) NULL,
            department VARCHAR(120) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_users_role (role),
            INDEX idx_users_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sku VARCHAR(40) NULL,
            name VARCHAR(120) NOT NULL,
            description TEXT NULL,
            category VARCHAR(50) NOT NULL DEFAULT 'general',
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            markup_price DECIMAL(10,2) NULL,
            stock_quantity INT NOT NULL DEFAULT 0,
            size_stock TEXT NULL,
            sizes_available VARCHAR(255) NULL,
            image_url VARCHAR(500) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_products_category (category),
            INDEX idx_products_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS cart_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NOT NULL,
            product_name VARCHAR(120) NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            size VARCHAR(20) NOT NULL DEFAULT '',
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_cart_user_product_size (user_id, product_id, size),
            CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS orders (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(30) NOT NULL UNIQUE,
            user_id INT UNSIGNED NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            status ENUM('pending','processing','ready','paid','completed','cancelled') NOT NULL DEFAULT 'pending',
            payment_status ENUM('pending','verified','paid','rejected','refunded') NOT NULL DEFAULT 'pending',
            stock_deducted TINYINT(1) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
            INDEX idx_orders_created (created_at),
            INDEX idx_orders_payment_status (payment_status),
            INDEX idx_orders_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS order_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT UNSIGNED NOT NULL,
            product_id INT UNSIGNED NULL,
            product_name VARCHAR(120) NOT NULL,
            size VARCHAR(20) NULL,
            quantity INT UNSIGNED NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
            INDEX idx_order_items_order (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS payments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            payment_code VARCHAR(20) NOT NULL UNIQUE,
            order_id INT UNSIGNED NOT NULL,
            method ENUM('GCash','PayMaya','Cash','Bank Transfer') NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
            reference_number VARCHAR(80) NULL,
            proof_url VARCHAR(500) NULL,
            verified_by INT UNSIGNED NULL,
            verification_date DATETIME NULL,
            rejection_reason VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            CONSTRAINT fk_payments_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_payments_status (status),
            INDEX idx_payments_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(100) NOT NULL PRIMARY KEY,
            setting_value TEXT NULL,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(120) NOT NULL,
            user_email VARCHAR(120) NULL,
            user_role VARCHAR(30) NULL,
            details TEXT NULL,
            ip_address VARCHAR(45) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_logs_created (created_at),
            INDEX idx_logs_action (action)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($statements as $sql) {
        if (!$db->query($sql)) {
            evsu_bootstrap_fail('Schema error: ' . $db->error);
        }
    }
}

function evsu_run_migrations(mysqli $db): void
{
    if (!evsu_table_exists($db, 'users')) {
        return;
    }

    if (evsu_column_exists($db, 'users', 'name') && !evsu_column_exists($db, 'users', 'full_name')) {
        $db->query("ALTER TABLE users CHANGE name full_name VARCHAR(120) NOT NULL");
    }

    if (!evsu_column_exists($db, 'users', 'course')) {
        $db->query("ALTER TABLE users ADD COLUMN course VARCHAR(80) NULL AFTER role");
    }
    if (!evsu_column_exists($db, 'users', 'year_level')) {
        $db->query("ALTER TABLE users ADD COLUMN year_level VARCHAR(20) NULL AFTER course");
    }
    if (!evsu_column_exists($db, 'users', 'department')) {
        $db->query("ALTER TABLE users ADD COLUMN department VARCHAR(120) NULL AFTER year_level");
    }

    if (evsu_column_exists($db, 'users', 'role')) {
        $db->query(
            "ALTER TABLE users MODIFY role ENUM('student','cashier','staff','admin') NOT NULL DEFAULT 'student'"
        );
    }

    if (evsu_table_exists($db, 'products') && !evsu_column_exists($db, 'products', 'markup_price')) {
        $db->query("ALTER TABLE products ADD COLUMN markup_price DECIMAL(10,2) NULL AFTER unit_price");
    }
    if (evsu_table_exists($db, 'products') && !evsu_column_exists($db, 'products', 'sku')) {
        $db->query("ALTER TABLE products ADD COLUMN sku VARCHAR(40) NULL AFTER id");
    }

    if (evsu_table_exists($db, 'orders')) {
        $db->query(
            "ALTER TABLE orders MODIFY status ENUM('pending','processing','ready','paid','completed','cancelled') NOT NULL DEFAULT 'pending'"
        );
        if (!evsu_column_exists($db, 'orders', 'stock_deducted')) {
            $db->query(
                'ALTER TABLE orders ADD COLUMN stock_deducted TINYINT(1) NOT NULL DEFAULT 0 AFTER payment_status'
            );
        }
    }

    if (evsu_table_exists($db, 'products') && !evsu_column_exists($db, 'products', 'size_stock')) {
        $db->query('ALTER TABLE products ADD COLUMN size_stock TEXT NULL AFTER stock_quantity');
    }
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

/**
 * Inserts default system_settings keys only if they don't exist yet.
 * Uses INSERT IGNORE so existing values (including ones you've edited) are never overwritten.
 */
function evsu_seed_default_settings(mysqli $db): void
{
    $defaults = [
        'system_name'         => 'EVSU RESERVE',
        'contact_email'       => 'igp@evsu.edu.ph',
        'announcement'        => '',
        'paymongo_public_key' => '',
        'cash_instructions'   => 'Please pay at the IGP Office cashier window.',
    ];

    $stmt = $db->prepare(
        'INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES (?, ?)'
    );
    foreach ($defaults as $key => $value) {
        $stmt->bind_param('ss', $key, $value);
        $stmt->execute();
    }
    $stmt->close();
}

// ---------------------------------------------------------------------------
// Public connection helpers
// ---------------------------------------------------------------------------

function evsu_db_connect(): mysqli
{
    $cfg = evsu_db_config();
    mysqli_report(MYSQLI_REPORT_OFF);

    if (!evsu_bootstrap_is_done()) {
        evsu_bootstrap_database();
    }

    $connect = static function () use ($cfg): mysqli {
        try {
            $conn = new mysqli(
                $cfg['host'],
                $cfg['username'],
                $cfg['password'],
                $cfg['database']
            );
        } catch (mysqli_sql_exception $e) {
            $conn = null;
            $GLOBALS['evsu_last_db_error'] = $e->getMessage();
        }

        if ($conn instanceof mysqli && $conn->connect_error) {
            $GLOBALS['evsu_last_db_error'] = $conn->connect_error;
            return $conn;
        }

        return $conn;
    };

    $conn = $connect();

    $needsInstall = !$conn
        || $conn->connect_error
        || (isset($GLOBALS['evsu_last_db_error'])
            && stripos((string) $GLOBALS['evsu_last_db_error'], 'Unknown database') !== false);

    if ($needsInstall) {
        evsu_bootstrap_reset();
        evsu_bootstrap_database();
        $conn = $connect();
    }

    if (!$conn || $conn->connect_error) {
        $msg = $conn->connect_error ?? ($GLOBALS['evsu_last_db_error'] ?? 'Unknown error');
        evsu_bootstrap_fail('Connection failed: ' . $msg);
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

function evsu_pdo_connect(): PDO
{
    evsu_db_connect();
    $cfg = evsu_db_config();

    return new PDO(
        "mysql:host={$cfg['host']};dbname={$cfg['database']};charset=utf8mb4",
        $cfg['username'],
        $cfg['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

// ---------------------------------------------------------------------------
// Utility helpers
// ---------------------------------------------------------------------------

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
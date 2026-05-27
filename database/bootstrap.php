<?php
/**
 * Creates database, tables, and demo data on first run (e.g. new PC / fresh XAMPP).
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
    evsu_seed_if_empty($root);
    evsu_seed_payments_if_missing($root);
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
    }
}

function evsu_table_exists(mysqli $db, string $table): bool
{
    $table = $db->real_escape_string($table);
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

function evsu_seed_if_empty(mysqli $db): void
{
    if (!evsu_table_exists($db, 'users')) {
        return;
    }

    $count = 0;
    $res   = $db->query('SELECT COUNT(*) AS c FROM users');
    if ($res) {
        $row   = $res->fetch_assoc();
        $count = (int) ($row['c'] ?? 0);
    }
    if ($count > 0) {
        return;
    }

    $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    $users = [
        "('2020-00001','Juan dela Cruz','juan.delacruz@evsu.edu.ph','{$hash}','student','BSIT','2nd Year',NULL)",
        "('2020-00002','Ana Reyes','ana.reyes@evsu.edu.ph','{$hash}','student','BSN','3rd Year',NULL)",
        "('2020-00003','Carlo Mendoza','carlo.mendoza@evsu.edu.ph','{$hash}','student','BSCE','2nd Year',NULL)",
        "('2020-00004','Liza Fernandez','liza.fernandez@evsu.edu.ph','{$hash}','student','BSBA','4th Year',NULL)",
        "('2020-00005','Mark Bautista','mark.bautista@evsu.edu.ph','{$hash}','student','BSIT','1st Year',NULL)",
        "('2020-00006','Grace Villanueva','grace.villanueva@evsu.edu.ph','{$hash}','student','BSED','2nd Year',NULL)",
        "('2020-00007','Paolo Cruz','paolo.cruz@evsu.edu.ph','{$hash}','student','BSME','3rd Year',NULL)",
        "('2020-00008','Rica Morales','rica.morales@evsu.edu.ph','{$hash}','student','BSN','1st Year',NULL)",
        "('CASH-00001','Maria Santos','maria.santos@evsu.edu.ph','{$hash}','cashier',NULL,NULL,'IGP Office')",
        "('CASH-00002','Jose Reyes','jose.reyes@evsu.edu.ph','{$hash}','cashier',NULL,NULL,'IGP Office')",
        "('STAFF-0001','Carmen Lopez','carmen.lopez@evsu.edu.ph','{$hash}','staff',NULL,NULL,'Registrar')",
        "('STAFF-0002','Ramon Torres','ramon.torres@evsu.edu.ph','{$hash}','staff',NULL,NULL,'Supply Office')",
        "('STAFF-0003','Elena Garcia','elena.garcia@evsu.edu.ph','{$hash}','staff',NULL,NULL,'Supply Office')",
        "('ADMIN-0001','System Admin','admin@evsu.edu.ph','{$hash}','admin',NULL,NULL,'ICT Office')",
    ];

    if (!$db->query(
        'INSERT INTO users (student_id, full_name, email, password, role, course, year_level, department) VALUES '
        . implode(',', $users)
    )) {
        evsu_bootstrap_fail('Seed users failed: ' . $db->error);
    }

    $products = [
        "(1,'UNI-PE-001','PE Uniform','Physical Education uniform','uniform',350.00,420.00,45,'S,M,L,XL',1)",
        "(2,'UNI-POLO-002','Polo Shirt','Official school polo','uniform',380.00,456.00,40,'S,M,L,XL',1)",
        "(3,'ACC-ID-003','School ID','Student identification card','accessories',200.00,240.00,100,NULL,1)",
        "(4,'UNI-LAB-004','Laboratory Gown','Science lab gown','uniform',350.00,420.00,30,'S,M,L',1)",
        "(5,'ACC-SL-005','EVSU ID Sling','Official ID sling','id_sling',80.00,110.00,120,NULL,1)",
        "(6,'SUP-BK-006','Blue Exam Booklet','Examination booklet','booklet',15.00,20.00,75,NULL,1)",
        "(7,'MER-TB-007','EVSU Tote Bag','Canvas tote bag','merchandise',180.00,220.00,60,NULL,1)",
        "(8,'SUP-BP-008','Ballpen (12 pcs)','Ballpen set','school_supply',60.00,75.00,200,NULL,1)",
    ];

    $db->query(
        'INSERT INTO products (id, sku, name, description, category, unit_price, markup_price, stock_quantity, sizes_available, is_active) VALUES '
        . implode(',', $products)
        . ' ON DUPLICATE KEY UPDATE name=VALUES(name), unit_price=VALUES(unit_price), stock_quantity=VALUES(stock_quantity)'
    );

    $orders = [
        "(1,'ORD-2026-0145',1,1250.00,'pending','pending','2026-05-16 08:00:00')",
        "(2,'ORD-2026-0146',2,350.00,'pending','pending','2026-05-16 09:00:00')",
        "(3,'ORD-2026-0143',3,780.00,'processing','verified','2026-05-16 07:30:00')",
        "(4,'ORD-2026-0140',4,2100.00,'paid','verified','2026-05-16 06:00:00')",
        "(5,'ORD-2026-0139',5,420.00,'cancelled','rejected','2026-05-15 14:00:00')",
        "(6,'ORD-2026-0137',6,960.00,'pending','pending','2026-05-16 10:00:00')",
        "(7,'ORD-2026-0135',7,550.00,'paid','verified','2026-05-16 05:30:00')",
        "(8,'ORD-2026-0134',8,1800.00,'cancelled','rejected','2026-05-15 11:00:00')",
        "(9,'ORD-2026-0120',1,670.00,'completed','verified','2026-05-04 10:00:00')",
        "(10,'ORD-2026-0110',3,1100.00,'paid','paid','2026-05-01 09:00:00')",
    ];

    $db->query(
        'INSERT INTO orders (id, order_number, user_id, total_amount, status, payment_status, created_at) VALUES '
        . implode(',', $orders)
    );

    $items = [
        '(1,1,1,\'PE Uniform\',NULL,2,400.00,800.00)',
        '(1,3,3,\'School ID\',NULL,1,200.00,250.00)',
        '(2,4,4,\'Laboratory Gown\',NULL,1,350.00,350.00)',
        '(3,1,1,\'PE Uniform\',NULL,1,400.00,400.00)',
        '(3,2,2,\'Polo Shirt\',NULL,1,380.00,380.00)',
        '(4,2,2,\'Polo Shirt\',NULL,3,380.00,1140.00)',
        '(4,1,1,\'PE Uniform\',NULL,2,400.00,800.00)',
        '(5,4,4,\'Laboratory Gown\',NULL,1,350.00,350.00)',
        '(6,2,2,\'Polo Shirt\',NULL,2,380.00,760.00)',
        '(6,3,3,\'School ID\',NULL,1,200.00,200.00)',
        '(7,1,1,\'PE Uniform\',NULL,1,400.00,400.00)',
        '(8,2,2,\'Polo Shirt\',NULL,4,380.00,1520.00)',
        '(9,4,4,\'Laboratory Gown\',NULL,1,350.00,350.00)',
        '(10,1,1,\'PE Uniform\',NULL,2,400.00,800.00)',
    ];

    $db->query(
        'INSERT INTO order_items (order_id, product_id, product_name, size, quantity, unit_price, subtotal) VALUES '
        . implode(',', $items)
    );

    $payments = [
        "('PAY-001',1,'GCash',1250.00,'pending','GC-9812734','2026-05-16 08:14:00',NULL,NULL)",
        "('PAY-002',2,'Cash',350.00,'pending',NULL,'2026-05-16 09:02:00',NULL,NULL)",
        "('PAY-003',3,'PayMaya',780.00,'verified','PM-4421098','2026-05-16 07:45:00','2026-05-16 08:00:00',9)",
        "('PAY-004',4,'GCash',2100.00,'verified','GC-7723001','2026-05-16 06:30:00','2026-05-16 07:00:00',9)",
        "('PAY-005',5,'Cash',420.00,'rejected',NULL,'2026-05-15 14:20:00','2026-05-15 15:00:00',9)",
        "('PAY-006',6,'Bank Transfer',960.00,'pending','BT-001-2026','2026-05-16 10:11:00',NULL,NULL)",
        "('PAY-007',7,'GCash',550.00,'verified','GC-5500213','2026-05-16 05:58:00','2026-05-16 06:15:00',9)",
        "('PAY-008',8,'PayMaya',1800.00,'rejected','GC-1122334','2026-05-15 11:30:00','2026-05-15 12:00:00',9)",
    ];

    $db->query(
        'INSERT INTO payments (payment_code, order_id, method, amount, status, reference_number, created_at, verification_date, verified_by) VALUES '
        . implode(',', $payments)
    );

    $logs = [
        "('User Login','juan.delacruz@evsu.edu.ph','student','Successful login',NULL)",
        "('Payment Verified','maria.santos@evsu.edu.ph','cashier','Payment PAY-006 verified',NULL)",
        "('Order Placed','ana.reyes@evsu.edu.ph','student','New order ORD-2026-0146 placed',NULL)",
        "('Product Updated','carmen.lopez@evsu.edu.ph','staff','Stock updated for PE Uniform',NULL)",
    ];

    $db->query(
        'INSERT INTO activity_logs (action, user_email, user_role, details, ip_address) VALUES '
        . implode(',', $logs)
    );

    evsu_seed_default_settings($db);
}

function evsu_seed_payments_if_missing(mysqli $db): void
{
    $payCount = (int) $db->query('SELECT COUNT(*) AS c FROM payments')->fetch_assoc()['c'];
    $ordCount = (int) $db->query('SELECT COUNT(*) AS c FROM orders')->fetch_assoc()['c'];
    if ($payCount > 0 || $ordCount === 0) {
        return;
    }

    $payments = [
        "('PAY-001',1,'GCash',1250.00,'pending','GC-9812734','2026-05-16 08:14:00',NULL,NULL)",
        "('PAY-002',2,'Cash',350.00,'pending',NULL,'2026-05-16 09:02:00',NULL,NULL)",
        "('PAY-003',3,'PayMaya',780.00,'verified','PM-4421098','2026-05-16 07:45:00','2026-05-16 08:00:00',9)",
        "('PAY-004',4,'GCash',2100.00,'verified','GC-7723001','2026-05-16 06:30:00','2026-05-16 07:00:00',9)",
        "('PAY-005',5,'Cash',420.00,'rejected',NULL,'2026-05-15 14:20:00','2026-05-15 15:00:00',9)",
        "('PAY-006',6,'Bank Transfer',960.00,'pending','BT-001-2026','2026-05-16 10:11:00',NULL,NULL)",
        "('PAY-007',7,'GCash',550.00,'verified','GC-5500213','2026-05-16 05:58:00','2026-05-16 06:15:00',9)",
        "('PAY-008',8,'PayMaya',1800.00,'rejected','GC-1122334','2026-05-15 11:30:00','2026-05-15 12:00:00',9)",
    ];

    $db->query(
        'INSERT IGNORE INTO payments (payment_code, order_id, method, amount, status, reference_number, created_at, verification_date, verified_by) VALUES '
        . implode(',', $payments)
    );
}

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

    try {
        return new PDO(
            "mysql:host={$cfg['host']};dbname={$cfg['database']};charset=utf8mb4",
            $cfg['username'],
            $cfg['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'Unknown database') !== false) {
            evsu_bootstrap_reset();
            evsu_bootstrap_database();
            return new PDO(
                "mysql:host={$cfg['host']};dbname={$cfg['database']};charset=utf8mb4",
                $cfg['username'],
                $cfg['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }
        evsu_bootstrap_fail('PDO connection failed: ' . $e->getMessage());
    }
}

function evsu_log_activity(mysqli $conn, string $action, ?string $email, ?string $role, string $details): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
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

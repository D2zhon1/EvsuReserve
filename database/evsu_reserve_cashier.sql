-- =============================================================================
-- EVSU Reserve — Cashier database schema & sample data
-- Database: evsu_reserve
-- Import via phpMyAdmin or: mysql -u root < database/evsu_reserve_cashier.sql
-- =============================================================================

CREATE DATABASE IF NOT EXISTS evsu_reserve
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE evsu_reserve;

-- -----------------------------------------------------------------------------
-- Core tables (used by login & student cart; safe to re-run with IF NOT EXISTS)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id    VARCHAR(50)  NOT NULL UNIQUE,
  name          VARCHAR(120) NOT NULL,
  email         VARCHAR(120) NULL,
  password      VARCHAR(255) NOT NULL,
  role          ENUM('student', 'cashier', 'admin') NOT NULL DEFAULT 'student',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(120) NOT NULL,
  description      TEXT         NULL,
  category         VARCHAR(50)  NOT NULL DEFAULT 'general',
  unit_price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  stock_quantity   INT          NOT NULL DEFAULT 0,
  sizes_available  VARCHAR(255) NULL,
  image_url        VARCHAR(500) NULL,
  is_active        TINYINT(1)   NOT NULL DEFAULT 1,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cart_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NOT NULL,
  product_name VARCHAR(120) NOT NULL,
  unit_price   DECIMAL(10,2) NOT NULL,
  size         VARCHAR(20)  NOT NULL DEFAULT '',
  quantity     INT UNSIGNED NOT NULL DEFAULT 1,
  created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cart_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  UNIQUE KEY uq_cart_user_product_size (user_id, product_id, size)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Cashier tables: orders, line items, payments
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS orders (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number    VARCHAR(30)  NOT NULL UNIQUE,
  user_id         INT UNSIGNED NOT NULL,
  total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status          ENUM('pending', 'processing', 'paid', 'completed', 'cancelled')
                  NOT NULL DEFAULT 'pending',
  payment_status  ENUM('pending', 'verified', 'paid', 'rejected', 'refunded')
                  NOT NULL DEFAULT 'pending',
  notes           TEXT         NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
  INDEX idx_orders_created (created_at),
  INDEX idx_orders_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id     INT UNSIGNED NOT NULL,
  product_id   INT UNSIGNED NULL,
  product_name VARCHAR(120) NOT NULL,
  size         VARCHAR(20)  NULL,
  quantity     INT UNSIGNED NOT NULL DEFAULT 1,
  unit_price   DECIMAL(10,2) NOT NULL,
  subtotal     DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_order_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
  INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_code       VARCHAR(20)  NOT NULL UNIQUE,
  order_id           INT UNSIGNED NOT NULL,
  method             ENUM('GCash', 'PayMaya', 'Cash', 'Bank Transfer') NOT NULL,
  amount             DECIMAL(10,2) NOT NULL,
  status             ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
  reference_number   VARCHAR(80)  NULL,
  proof_url          VARCHAR(500) NULL,
  verified_by        INT UNSIGNED NULL,
  verification_date  DATETIME     NULL,
  rejection_reason   VARCHAR(255) NULL,
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME     NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_order    FOREIGN KEY (order_id)     REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_verifier FOREIGN KEY (verified_by)  REFERENCES users(id)   ON DELETE SET NULL,
  INDEX idx_payments_status (status),
  INDEX idx_payments_created (created_at),
  INDEX idx_payments_method (method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Re-seed cashier demo data (safe to re-run)
-- -----------------------------------------------------------------------------
DELETE FROM payments WHERE payment_code IN (
  'PAY-001','PAY-002','PAY-003','PAY-004','PAY-005','PAY-006','PAY-007','PAY-008'
);
DELETE FROM order_items WHERE order_id BETWEEN 1 AND 10;
DELETE FROM orders WHERE id BETWEEN 1 AND 10;

-- -----------------------------------------------------------------------------
-- Sample users (demo password for all accounts below: password)
-- -----------------------------------------------------------------------------

INSERT INTO users (student_id, name, email, password, role) VALUES
  ('2020-00001', 'Juan dela Cruz',    'juan.delacruz@evsu.edu.ph',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
  ('2020-00002', 'Ana Reyes',         'ana.reyes@evsu.edu.ph',        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
  ('2020-00003', 'Carlo Mendoza',     'carlo.mendoza@evsu.edu.ph',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
  ('2020-00004', 'Liza Fernandez',    'liza.fernandez@evsu.edu.ph',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
  ('2020-00005', 'Mark Bautista',     'mark.bautista@evsu.edu.ph',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
  ('2020-00006', 'Grace Villanueva',  'grace.villanueva@evsu.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
  ('2020-00007', 'Paolo Cruz',        'paolo.cruz@evsu.edu.ph',       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
  ('2020-00008', 'Rica Morales',      'rica.morales@evsu.edu.ph',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
  ('CASH-00001', 'Maria Santos',      'maria.santos@evsu.edu.ph',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier'),
  ('ADMIN-0001', 'System Admin',      'admin@evsu.edu.ph',            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  email = VALUES(email),
  role = VALUES(role);

-- -----------------------------------------------------------------------------
-- Sample products
-- -----------------------------------------------------------------------------

INSERT INTO products (id, name, description, category, unit_price, stock_quantity, sizes_available) VALUES
  (1, 'PE Uniform',      'Physical Education uniform',     'uniforms', 400.00, 50, 'S,M,L,XL'),
  (2, 'Polo Shirt',      'Official school polo',           'uniforms', 380.00, 40, 'S,M,L,XL'),
  (3, 'School ID',       'Student identification card',    'accessories', 200.00, 100, NULL),
  (4, 'Laboratory Gown', 'Science lab gown',               'uniforms', 350.00, 30, 'S,M,L')
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  unit_price = VALUES(unit_price),
  stock_quantity = VALUES(stock_quantity);

-- -----------------------------------------------------------------------------
-- Sample orders (matches cashier mock order numbers)
-- -----------------------------------------------------------------------------

INSERT INTO orders (id, order_number, user_id, total_amount, status, payment_status, created_at) VALUES
  (1, 'ORD-2026-0145', 1, 1250.00, 'pending',    'pending',  '2026-05-16 08:00:00'),
  (2, 'ORD-2026-0146', 2,  350.00, 'pending',    'pending',  '2026-05-16 09:00:00'),
  (3, 'ORD-2026-0143', 3,  780.00, 'processing', 'verified', '2026-05-16 07:30:00'),
  (4, 'ORD-2026-0140', 4, 2100.00, 'paid',       'verified', '2026-05-16 06:00:00'),
  (5, 'ORD-2026-0139', 5,  420.00, 'cancelled',  'rejected', '2026-05-15 14:00:00'),
  (6, 'ORD-2026-0137', 6,  960.00, 'pending',    'pending',  '2026-05-16 10:00:00'),
  (7, 'ORD-2026-0135', 7,  550.00, 'paid',       'verified', '2026-05-16 05:30:00'),
  (8, 'ORD-2026-0134', 8, 1800.00, 'cancelled',  'rejected', '2026-05-15 11:00:00'),
  (9, 'ORD-2026-0120', 1,  670.00, 'completed',  'verified', '2026-05-04 10:00:00'),
  (10,'ORD-2026-0110', 3, 1100.00, 'paid',       'paid',     '2026-05-01 09:00:00')
ON DUPLICATE KEY UPDATE
  total_amount = VALUES(total_amount),
  status = VALUES(status),
  payment_status = VALUES(payment_status);

INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) VALUES
  (1, 1, 'PE Uniform',      2, 400.00, 800.00),
  (1, 3, 'School ID',       1, 200.00, 250.00),
  (2, 4, 'Laboratory Gown', 1, 350.00, 350.00),
  (3, 1, 'PE Uniform',      1, 400.00, 400.00),
  (3, 2, 'Polo Shirt',      1, 380.00, 380.00),
  (4, 2, 'Polo Shirt',      3, 380.00, 1140.00),
  (4, 1, 'PE Uniform',      2, 400.00, 800.00),
  (4, 3, 'School ID',       1, 200.00, 160.00),
  (5, 4, 'Laboratory Gown', 1, 350.00, 350.00),
  (5, 3, 'School ID',       1, 200.00,  70.00),
  (6, 2, 'Polo Shirt',      2, 380.00, 760.00),
  (6, 3, 'School ID',       1, 200.00, 200.00),
  (7, 1, 'PE Uniform',      1, 400.00, 400.00),
  (7, 3, 'School ID',       1, 200.00, 150.00),
  (8, 2, 'Polo Shirt',      4, 380.00, 1520.00),
  (8, 3, 'School ID',       1, 200.00, 280.00),
  (9, 4, 'Laboratory Gown', 1, 350.00, 350.00),
  (9, 2, 'Polo Shirt',      1, 320.00, 320.00),
  (10,1, 'PE Uniform',      2, 400.00, 800.00),
  (10,4, 'Laboratory Gown', 1, 300.00, 300.00);

-- -----------------------------------------------------------------------------
-- Sample payments (matches cashier_dashboard / cashier_payments mock data)
-- -----------------------------------------------------------------------------

INSERT INTO payments (payment_code, order_id, method, amount, status, reference_number, created_at, verification_date, verified_by) VALUES
  ('PAY-001', 1, 'GCash',         1250.00, 'pending',  'GC-9812734',  '2026-05-16 08:14:00', NULL,                NULL),
  ('PAY-002', 2, 'Cash',           350.00, 'pending',  NULL,          '2026-05-16 09:02:00', NULL,                NULL),
  ('PAY-003', 3, 'PayMaya',        780.00, 'verified', 'PM-4421098',  '2026-05-16 07:45:00', '2026-05-16 08:00:00', 9),
  ('PAY-004', 4, 'GCash',         2100.00, 'verified', 'GC-7723001',  '2026-05-16 06:30:00', '2026-05-16 07:00:00', 9),
  ('PAY-005', 5, 'Cash',           420.00, 'rejected', NULL,          '2026-05-15 14:20:00', '2026-05-15 15:00:00', 9),
  ('PAY-006', 6, 'Bank Transfer',  960.00, 'pending',  'BT-001-2026', '2026-05-16 10:11:00', NULL,                NULL),
  ('PAY-007', 7, 'GCash',          550.00, 'verified', 'GC-5500213',  '2026-05-16 05:58:00', '2026-05-16 06:15:00', 9),
  ('PAY-008', 8, 'PayMaya',       1800.00, 'rejected', 'GC-1122334',  '2026-05-15 11:30:00', '2026-05-15 12:00:00', 9)
ON DUPLICATE KEY UPDATE
  status = VALUES(status),
  reference_number = VALUES(reference_number),
  verification_date = VALUES(verification_date),
  verified_by = VALUES(verified_by);

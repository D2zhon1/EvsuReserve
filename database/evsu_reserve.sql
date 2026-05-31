-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2026 at 05:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `evsu_reserve`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `action` varchar(120) NOT NULL,
  `user_email` varchar(120) DEFAULT NULL,
  `user_role` varchar(30) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `action`, `user_email`, `user_role`, `details`, `ip_address`, `created_at`) VALUES
(1, 'User Registered', 'admin@evsu.edu.ph', 'student', 'New account: ADMIN-0001', '::1', '2026-05-31 21:47:39'),
(2, 'User Registered', 'staff@evsu.edu.ph', 'student', 'New account: STAFF-0001', '::1', '2026-05-31 21:48:32'),
(3, 'User Registered', 'cashier@evsu.edu.ph', 'student', 'New account: CASHIER-0001', '::1', '2026-05-31 21:49:02'),
(4, 'User Login', 'admin@evsu.edu.ph', 'admin', 'Successful login', '::1', '2026-05-31 21:52:13'),
(5, 'User Login', 'staff@evsu.edu.ph', 'staff', 'Successful login', '::1', '2026-05-31 21:52:56'),
(6, 'User Registered', 'jhonloyd.estrera@evsu.edu.ph', 'student', 'New account: 2021-32357', '::1', '2026-05-31 22:51:04'),
(7, 'User Login', 'jhonloyd.estrera@evsu.edu.ph', 'student', 'Successful login', '::1', '2026-05-31 22:51:11'),
(8, 'User Login', 'staff@evsu.edu.ph', 'staff', 'Successful login', '::1', '2026-05-31 22:51:31'),
(9, 'User Login', 'jhonloyd.estrera@evsu.edu.ph', 'student', 'Successful login', '::1', '2026-05-31 22:59:51'),
(10, 'User Login', 'staff@evsu.edu.ph', 'staff', 'Successful login', '::1', '2026-05-31 23:01:11'),
(11, 'Product Added', 'staff@evsu.edu.ph', 'staff', 'Product: EVSU Male Uniform', '::1', '2026-05-31 23:01:49'),
(12, 'User Login Failed', 'jhonloyd.estrera@evsu.edu.ph', '', 'Invalid password', '::1', '2026-05-31 23:02:08'),
(13, 'User Login', 'jhonloyd.estrera@evsu.edu.ph', 'student', 'Successful login', '::1', '2026-05-31 23:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_name` varchar(120) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `size` varchar(20) NOT NULL DEFAULT '',
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(30) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','processing','ready','paid','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','verified','paid','rejected','refunded') NOT NULL DEFAULT 'pending',
  `stock_deducted` tinyint(1) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED DEFAULT NULL,
  `product_name` varchar(120) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `payment_code` varchar(20) NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `method` enum('GCash','PayMaya','Cash','Bank Transfer') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `reference_number` varchar(80) DEFAULT NULL,
  `proof_url` varchar(500) DEFAULT NULL,
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verification_date` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `sku` varchar(40) DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `markup_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `size_stock` text DEFAULT NULL,
  `sizes_available` varchar(255) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `category`, `unit_price`, `markup_price`, `stock_quantity`, `size_stock`, `sizes_available`, `image_url`, `is_active`, `created_at`) VALUES
(2, 'SC-UNI-001', 'EVSU Male Uniform', '', 'uniform', 650.00, 650.00, 28, '{\"S\":10,\"M\":18}', 'S,M', 'http://localhost/EvsuReserve/image/Male.png', 1, '2026-05-31 23:01:49');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('announcement', '', NULL),
('cash_instructions', 'Please pay at the IGP Office cashier window.', NULL),
('contact_email', 'igp@evsu.edu.ph', NULL),
('paymongo_public_key', '', NULL),
('system_name', 'EVSU RESERVE', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','cashier','staff','admin') NOT NULL DEFAULT 'student',
  `course` varchar(80) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `department` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_id`, `full_name`, `email`, `password`, `role`, `course`, `year_level`, `department`, `created_at`, `updated_at`) VALUES
(1, 'ADMIN-0001', 'Admin', 'admin@evsu.edu.ph', '$2y$10$GGtiz2r6kiKu0ZQ6s.did.mJjwMY/in/FcBCt6eGkzsazQCUHEpu6', 'admin', NULL, NULL, 'IGP OFFICE', '2026-05-31 21:47:39', NULL),
(2, 'STAFF-0001', 'Staff', 'staff@evsu.edu.ph', '$2y$10$kpgwNpzxodIeMjX16W3z..j5VwQN.3Uy94phk2mLBLUoCZjgCC.0W', 'staff', NULL, NULL, 'IGP OFFICE', '2026-05-31 21:48:32', NULL),
(3, 'CASHIER-0001', 'Cashier', 'cashier@evsu.edu.ph', '$2y$10$hjfgC8GVtI1dqZk3KlnLBuShesWFYQbphAxadLFD0DMt/EPXDWZ7i', 'cashier', 'OTHER', NULL, 'IGP OFFICE', '2026-05-31 21:49:02', NULL),
(4, '2021-32357', 'Jhon Loyd Estrera', 'jhonloyd.estrera@evsu.edu.ph', '$2y$10$Oob/aknhh/K9ZFyuoKD6QeDpr3TCvo/3fKkJ8xvfEDnsuB8sEcL8i', 'student', 'BSIT', '3', NULL, '2026-05-31 22:51:04', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_created` (`created_at`),
  ADD KEY `idx_logs_action` (`action`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cart_user_product_size` (`user_id`,`product_id`,`size`),
  ADD KEY `fk_cart_product` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `idx_orders_created` (`created_at`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_order_items_product` (`product_id`),
  ADD KEY `idx_order_items_order` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_code` (`payment_code`),
  ADD KEY `fk_payments_order` (`order_id`),
  ADD KEY `fk_payments_verifier` (`verified_by`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_created` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_category` (`category`),
  ADD KEY `idx_products_active` (`is_active`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payments_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

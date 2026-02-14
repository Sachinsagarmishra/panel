-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: sdb-85.hosting.stackcp.net
-- Generation Time: Feb 14, 2026 at 05:41 AM
-- Server version: 10.11.11-MariaDB-log
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ssmpanel-35303938fdb7`
--

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `ifsc_code` varchar(20) DEFAULT NULL,
  `upi_id` varchar(100) DEFAULT NULL,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bank_accounts`
--

INSERT INTO `bank_accounts` (`id`, `account_name`, `bank_name`, `account_number`, `ifsc_code`, `upi_id`, `custom_fields`, `is_default`, `created_at`) VALUES
(6, 'Sachin Sagar', 'SBI', '43326752306', 'SBIN0011548', '7982857852-2@ibl', '[]', 1, '2025-07-24 07:55:12');

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `whatsapp` varchar(20) DEFAULT NULL,
  `brand_name` varchar(255) DEFAULT NULL,
  `reference_websites` text DEFAULT NULL,
  `status` enum('Active','Paused','Completed') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `name`, `email`, `phone`, `country`, `whatsapp`, `brand_name`, `reference_websites`, `status`, `notes`, `created_at`) VALUES
(6, 'Manu Ji', 'sarita41k@gmail.com', '+91 90566 10575', 'India', NULL, 'Anikmart.in', NULL, 'Active', NULL, '2025-03-03 07:52:09'),
(7, 'Shamita Sharma', 'support@wanderoo.au', '+91 72591 11263', 'India', NULL, 'Wanderoo.au.com', NULL, 'Active', NULL, '2025-05-14 11:09:50'),
(8, 'Elvis', 'info@lekobeadventures.com', '+255 676 68 1293', 'South Africa', NULL, 'lekobeadventures.com', NULL, 'Active', NULL, '2025-07-11 11:11:29'),
(9, 'Dishant Saini', 'dishant@scalingsharks.com', '+91 83059 00007', 'India', NULL, 'scalingshark.com', NULL, 'Active', NULL, '2019-10-23 11:13:04'),
(10, 'Nishant', 'support@bytbots.com', '+91 92050 93074', 'India', NULL, 'Bytbots.com', NULL, 'Active', NULL, '2025-07-16 10:50:38'),
(11, 'Suchit Mishra', 'suchitmishra1728@gmail.com', '+91 89487 94697', 'India', NULL, 'cnn-robotics.com', NULL, 'Active', NULL, '2025-07-01 19:12:36'),
(12, 'M.D Nadeem Sir', 'hello@ohmyweb.in', '', 'India', NULL, 'Oh My Web!', NULL, 'Active', NULL, '2024-09-01 19:31:57'),
(13, 'Satyam', 'contact@auslese-automation.com', '+91 99993 05522', 'India', NULL, 'auslese-automation.com', NULL, 'Active', NULL, '2025-02-01 04:10:28'),
(14, 'Riya Kriti', 'riyakriti@gmail.com', '+91 86780 11540', 'India', NULL, 'Family Tree', NULL, 'Active', NULL, '2025-08-13 06:25:42'),
(15, 'Nitin Sharma', 'Nitin@nsfinancialservices.co.uk', '82830 29302', 'India', NULL, 'NS Financial Services', NULL, 'Active', NULL, '2025-11-13 07:14:06'),
(17, 'Roberto Mussato', 'robikenya01@gmail.com', '+39 329 471 6073', 'Italy', NULL, 'Pinealetravel.com', NULL, 'Active', NULL, '2025-08-30 18:33:35'),
(18, 'Jatin Kumar', 'jatinjienterprises@gmail.com', '‪+91 98732 76930‬', 'India', NULL, 'Jatin Ji Enterprises', NULL, 'Active', NULL, '2025-12-03 12:04:11');

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` int(11) NOT NULL,
  `code` varchar(10) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `exchange_rate` decimal(10,4) DEFAULT 1.0000,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `code`, `name`, `symbol`, `exchange_rate`, `is_active`, `created_at`) VALUES
(1, 'USD', 'US Dollar', '$', 1.0000, 1, '2025-07-19 18:29:44'),
(2, 'INR', 'Indian Rupee', '₹', 86.4200, 1, '2025-07-19 18:29:44'),
(3, 'AED', 'UAE Dirham', 'د.إ', 3.6700, 0, '2025-07-19 18:29:44'),
(4, 'EUR', 'Euro', '€', 0.9200, 0, '2025-07-19 18:29:44'),
(5, 'GBP', 'British Pound', '£', 0.8100, 0, '2025-07-19 18:29:44'),
(6, 'CAD', 'Canadian Dollar', 'C$', 1.3500, 0, '2025-07-19 18:29:44'),
(7, 'AUD', 'Australian Dollar', 'A$', 1.5200, 0, '2025-07-19 18:29:44'),
(8, 'SGD', 'Singapore Dollar', 'S$', 1.3400, 0, '2025-07-19 18:29:44'),
(9, 'JPY', 'Japanese Yen', '¥', 150.0000, 0, '2025-07-19 18:29:44');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `issue_date` date NOT NULL,
  `due_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'USD',
  `status` enum('Paid','Unpaid','Overdue') DEFAULT 'Unpaid',
  `payment_mode` varchar(100) DEFAULT NULL,
  `bank_account` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_number`, `client_id`, `project_id`, `issue_date`, `due_date`, `amount`, `currency`, `status`, `payment_mode`, `bank_account`, `notes`, `created_at`) VALUES
(12, 'INV-2025-001', 6, 4, '2025-07-24', '2025-08-25', 11200.00, 'INR', 'Paid', 'Bank Transfer', '6', '', '2025-07-24 07:57:17'),
(22, 'INV-2025-003', 9, 13, '2025-09-21', '2025-10-25', 9500.00, 'INR', 'Paid', 'UPI', '6', '', '2025-09-21 11:44:13'),
(27, 'INV-2025-004', 14, 12, '2025-09-22', '2025-09-25', 12000.00, 'INR', 'Paid', 'Bank Transfer', '6', 'Advance payment {₹2500} was done 13 August 2025', '2025-09-22 10:35:51'),
(29, 'INV-2025-005', 14, 12, '2025-11-13', '2025-11-20', 500.00, 'INR', 'Paid', 'Bank Transfer', '6', '', '2025-11-13 06:33:06'),
(30, 'INV-2025-006', 15, 14, '2025-11-13', '2025-11-20', 15000.00, 'INR', 'Paid', 'Bank Transfer', '6', '', '2025-11-13 07:18:46'),
(33, 'INV-2025-007', 17, 17, '2025-11-16', '2025-11-18', 19700.00, 'INR', 'Paid', 'Bank Transfer', '6', 'last 100 euro meand 9800 left.', '2025-11-16 19:45:38'),
(34, 'INV-2025-008', 11, 11, '2025-11-07', '2025-11-08', 5000.00, 'INR', 'Paid', 'Bank Transfer', '6', '', '2025-11-16 19:49:03'),
(35, 'INV-2025-009', 8, 8, '2025-11-16', '2025-11-25', 12200.00, 'INR', 'Paid', 'Bank Transfer', '6', '', '2025-11-16 19:50:51'),
(42, 'INV-2026-010', 6, 16, '2026-01-06', '2026-02-15', 20000.00, 'INR', 'Paid', 'Bank Transfer', '6', '', '2026-01-06 16:33:55'),
(43, 'INV-2026-012', 8, 8, '2026-01-25', '2026-01-27', 280.00, 'USD', 'Paid', 'Bank Transfer', '6', 'As the December billing was not processed in the previous invoice cycle, the same has been carried forward and reflected in this invoice.', '2026-01-25 20:11:40'),
(44, 'INV-2026-013', 6, 4, '2026-02-10', '2026-02-17', 19360.00, 'INR', 'Unpaid', 'Bank Transfer', '6', 'This is for october to december month.', '2026-02-10 17:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `rate` decimal(10,2) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `description`, `quantity`, `rate`, `amount`, `created_at`) VALUES
(7, 12, 'Last Milestone Custom Website Anikamrt ', 1.00, 10000.00, 10000.00, '2025-07-24 07:57:17'),
(8, 12, 'nsfs.co.uk webiste changes', 1.00, 1200.00, 1200.00, '2025-07-24 07:57:17'),
(20, 22, 'Boonit website Changes', 1.00, 5000.00, 5000.00, '2025-09-21 11:44:13'),
(21, 22, 'Children visa Landing page', 1.00, 1500.00, 1500.00, '2025-09-21 11:44:13'),
(22, 22, 'Greenco 2 landing Pages', 2.00, 1500.00, 3000.00, '2025-09-21 11:44:13'),
(23, 27, 'Website UI/UX Design + Developement', 1.00, 12000.00, 12000.00, '2025-09-22 10:35:51'),
(24, 29, 'Google Workspace Setup', 1.00, 500.00, 500.00, '2025-11-13 06:33:06'),
(25, 30, 'UI/UX Design ', 1.00, 5000.00, 5000.00, '2025-11-13 07:18:46'),
(26, 30, 'Custom Develeoepemrnt', 1.00, 10000.00, 10000.00, '2025-11-13 07:18:46'),
(28, 33, 'ui ux d+ devl.', 1.00, 19700.00, 19700.00, '2025-11-16 19:45:38'),
(29, 34, 'monthly', 1.00, 5000.00, 5000.00, '2025-11-16 19:49:03'),
(30, 35, 'monthly seo', 1.00, 12200.00, 12200.00, '2025-11-16 19:50:51'),
(35, 42, 'UI/UX Design + Development', 1.00, 20000.00, 20000.00, '2026-01-06 16:33:55'),
(36, 43, 'Website maintenance and SEO {December month}', 1.00, 140.00, 140.00, '2026-01-25 20:11:40'),
(37, 43, 'Website maintenance and SEO {January month}', 1.00, 140.00, 140.00, '2026-01-25 20:11:40'),
(38, 44, 'October month {24 hours} * 350', 1.00, 8400.00, 8400.00, '2026-02-10 17:23:37'),
(39, 44, 'Server transfer and database migration {october month}', 1.00, 1500.00, 1500.00, '2026-02-10 17:23:37'),
(40, 44, 'Nov+ December month {26 hours} * 350', 1.00, 9100.00, 9100.00, '2026-02-10 17:23:37'),
(41, 44, 'VPS hostinger {1 month}', 1.00, 360.00, 360.00, '2026-02-10 17:23:37');

-- --------------------------------------------------------

--
-- Table structure for table `passwords`
--

CREATE TABLE `passwords` (
  `id` int(11) NOT NULL,
  `website_link` varchar(500) NOT NULL,
  `username_email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `passwords`
--

INSERT INTO `passwords` (`id`, `website_link`, `username_email`, `password`, `created_at`, `updated_at`) VALUES
(1, 'https://catoppersexamination.in/wp-admin/', 'CA Topper', 'Catoppers@0001', '2025-07-22 16:57:52', '2025-07-22 16:57:52'),
(2, 'https://mail.hostinger.com/', 'enquiry@wanderoo.com.au', 'Wanderoo@1234!@#', '2025-08-09 18:03:19', '2025-08-09 18:03:19');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) NOT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `paid_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `client_id`, `project_id`, `amount`, `currency`, `razorpay_order_id`, `paid_at`, `created_at`) VALUES
(2, 9, 6, 1.00, 'INR', 'order_Rv94vTssCCKGot', '2025-12-30 15:56:50', '2025-12-30 15:56:50');

-- --------------------------------------------------------

--
-- Table structure for table `payment_links`
--

CREATE TABLE `payment_links` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(5) NOT NULL,
  `slug` varchar(50) DEFAULT NULL,
  `razorpay_order_id` varchar(100) DEFAULT NULL,
  `razorpay_link_id` varchar(100) NOT NULL,
  `razorpay_link_url` text NOT NULL,
  `status` enum('created','paid','cancelled') DEFAULT 'created',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_links`
--

INSERT INTO `payment_links` (`id`, `client_id`, `project_id`, `amount`, `currency`, `slug`, `razorpay_order_id`, `razorpay_link_id`, `razorpay_link_url`, `status`, `created_at`, `paid_at`) VALUES
(9, 9, 6, 1.00, 'INR', 'afc014eaa7fc', 'order_Rv94vTssCCKGot', '', '', 'paid', '2025-12-23 18:04:57', '2025-12-30 15:56:50'),
(10, 8, 8, 140.00, 'USD', '2b5365c36614', 'order_RydO3YnmrdRnED', '', '', 'created', '2026-01-01 13:40:52', NULL),
(12, 8, 8, 280.00, 'USD', 'db91122e0d77', 'order_S8Enz3xFTvUpO7', '', '', 'created', '2026-01-25 20:08:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `client_id` int(11) NOT NULL,
  `services` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`services`)),
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Idea','In Progress','Review','Done') DEFAULT 'Idea',
  `deliverables` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `client_id`, `services`, `start_date`, `end_date`, `status`, `deliverables`, `file_path`, `notes`, `created_at`) VALUES
(4, 'Custom Website Anikmart', 6, '[\"Web Development\"]', '2025-03-03', '0000-00-00', 'Done', '', NULL, '', '2025-07-24 07:52:55'),
(5, 'Wanderoo.au.com', 7, '[\"Web Design\",\"Web Development\",\"WordPress\"]', '2025-05-14', '0000-00-00', 'Done', '', NULL, 'Full Ui/ux Deisgn with custom wordpress developement', '2025-07-24 11:14:09'),
(6, 'Boonit.co.uk', 9, '[\"WordPress\"]', '2025-06-01', '0000-00-00', 'Done', '', NULL, '', '2025-07-24 11:18:50'),
(7, 'Greenco.co.uk', 9, '[\"Web Design\",\"Web Development\"]', '2025-07-25', '2025-07-25', 'Done', '2 landing pages for ads with design and developement', NULL, '', '2025-07-25 06:54:09'),
(8, 'lekobeadventures.com', 8, '[\"SEO\"]', '2025-07-11', '2025-07-11', 'In Progress', 'SEO', NULL, '', '2025-07-25 10:48:50'),
(9, 'Bytbots.com', 10, '[\"SEO\"]', '2025-07-16', '2025-07-16', 'Done', 'SEO', NULL, '', '2025-07-25 10:51:14'),
(10, 'Navigateisrael.co.il', 9, '[\"Web Design\",\"WordPress\"]', '2025-06-21', '2025-07-25', 'Done', 'One landing page', NULL, '', '2025-07-25 10:56:55'),
(11, 'Monthly Retainer', 11, '[\"Web Development\",\"SEO\"]', '2025-07-01', '2025-07-01', 'Done', 'Monthly retainer', NULL, '', '2025-07-29 19:13:44'),
(12, 'Family Tree Webiste', 14, '[\"Web Design\",\"WordPress\"]', '2025-08-13', '2025-08-31', 'Done', 'Design of 04 pages with developerment', NULL, '', '2025-08-13 06:26:46'),
(13, 'Scaling Shark', 9, '[\"Web Development\"]', '2019-01-21', '2025-09-21', 'Done', '', NULL, '', '2025-09-21 11:38:07'),
(14, 'NS Financial Services Website', 15, '[\"Web Design\",\"Web Development\"]', '2025-10-01', '2025-10-31', 'Done', '', NULL, '', '2025-11-13 07:15:08'),
(16, 'Sachrate', 6, '[\"Web Design\",\"Web Development\"]', '2025-11-08', '2025-11-30', 'Done', '', NULL, '', '2025-11-13 07:51:12'),
(17, 'pinealetravel.com', 17, '[\"Web Design\",\"WordPress\"]', '2025-09-01', '2025-11-25', 'Review', 'UI/UX Design + Developement', NULL, '', '2025-11-16 19:35:02');

-- --------------------------------------------------------

--
-- Table structure for table `proposals`
--

CREATE TABLE `proposals` (
  `id` int(11) NOT NULL,
  `project_title` varchar(255) NOT NULL,
  `client_id` int(11) NOT NULL,
  `scope_of_work` text DEFAULT NULL,
  `deliverables` text DEFAULT NULL,
  `timeline` varchar(255) DEFAULT NULL,
  `pricing` decimal(10,2) DEFAULT NULL,
  `payment_terms` text DEFAULT NULL,
  `status` enum('Draft','Sent','Accepted','Rejected') DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `priority` enum('Low','Medium','High') DEFAULT 'Medium',
  `due_date` date DEFAULT NULL,
  `status` enum('Todo','In Progress','Completed') DEFAULT 'Todo',
  `project_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(5) NOT NULL,
  `gateway` varchar(20) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_link_id` varchar(100) DEFAULT NULL,
  `status` enum('paid','failed','pending') DEFAULT 'pending',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `share_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@freelancepro.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1, NULL, '2025-07-20 04:16:34', '2025-07-20 04:16:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `webhook_logs`
--

CREATE TABLE `webhook_logs` (
  `id` int(11) NOT NULL,
  `gateway` varchar(20) DEFAULT NULL,
  `event` varchar(100) DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `passwords`
--
ALTER TABLE `passwords`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_links`
--
ALTER TABLE `payment_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_client_id` (`client_id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `proposals`
--
ALTER TABLE `proposals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `project_id` (`project_id`),
  ADD KEY `status` (`status`),
  ADD KEY `payment_method` (`payment_method`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `webhook_logs`
--
ALTER TABLE `webhook_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `passwords`
--
ALTER TABLE `passwords`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_links`
--
ALTER TABLE `payment_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `proposals`
--
ALTER TABLE `proposals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `webhook_logs`
--
ALTER TABLE `webhook_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_links`
--
ALTER TABLE `payment_links`
  ADD CONSTRAINT `fk_payment_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projects`
--
ALTER TABLE `projects`
  ADD CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proposals`
--
ALTER TABLE `proposals`
  ADD CONSTRAINT `proposals_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

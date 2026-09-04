-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 11:57 AM
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
-- Database: `ims_copy_testing`
--

-- --------------------------------------------------------

--
-- Table structure for table `additional_fee_items`
--

CREATE TABLE `additional_fee_items` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL COMMENT 'Reference to additional_fee_payments.id',
  `sequence` int(11) NOT NULL COMMENT 'Display order of the fee item',
  `description` varchar(255) NOT NULL COMMENT 'Description of the fee item',
  `amount` decimal(10,2) NOT NULL COMMENT 'Amount for this fee item',
  `created_at` datetime NOT NULL COMMENT 'Record creation timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `additional_fee_payments`
--

CREATE TABLE `additional_fee_payments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL COMMENT 'Student code from students table',
  `student_registration_id` varchar(50) NOT NULL COMMENT 'Registration ID from allocate_programme',
  `programme_code` varchar(20) NOT NULL COMMENT 'Program code from program_table',
  `batch_id` int(11) NOT NULL COMMENT 'Batch ID from batch_table',
  `program_name` varchar(100) NOT NULL COMMENT 'Program name for reference',
  `batch_name` varchar(50) NOT NULL COMMENT 'Batch name for reference',
  `total_amount` decimal(10,2) NOT NULL COMMENT 'Total amount of all fee items',
  `paid_date` date NOT NULL COMMENT 'Date when fee was paid',
  `payment_method` varchar(30) DEFAULT 'Cash' COMMENT 'Method of payment',
  `payment_reference` varchar(100) DEFAULT NULL COMMENT 'Reference number for payment if applicable',
  `remarks` text DEFAULT NULL COMMENT 'Any additional notes',
  `status` enum('paid','pending','cancelled') NOT NULL DEFAULT 'paid' COMMENT 'Payment status',
  `entered_by` varchar(50) NOT NULL COMMENT 'Username who entered this record',
  `created_at` datetime NOT NULL COMMENT 'Record creation timestamp',
  `updated_at` datetime DEFAULT NULL COMMENT 'Record last update timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `add_payment_plan_table`
--

CREATE TABLE `add_payment_plan_table` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `programme_batch` varchar(255) NOT NULL,
  `university_fee_LKR` int(11) DEFAULT NULL,
  `courseFeeLKR_total` int(11) DEFAULT NULL,
  `course_fee_LKR` int(11) DEFAULT NULL,
  `course_fee_type_LKR` enum('full','installment') DEFAULT NULL,
  `installment_month_LKR` int(11) DEFAULT NULL,
  `registration_fee_LKR` int(11) DEFAULT NULL,
  `university_fee_GBP` int(11) DEFAULT NULL,
  `courseFeeGBP_total` int(11) DEFAULT NULL,
  `course_fee_GBP` int(11) DEFAULT NULL,
  `course_fee_type_GBP` enum('full','installment') DEFAULT NULL,
  `installment_month_GBP` int(11) DEFAULT NULL,
  `registration_fee_GBP` int(11) DEFAULT NULL,
  `university_fee_USD` int(11) DEFAULT NULL,
  `courseFeeUSD_total` int(11) DEFAULT NULL,
  `course_fee_USD` int(11) DEFAULT NULL,
  `course_fee_type_USD` enum('full','installment') DEFAULT NULL,
  `installment_month_USD` int(11) DEFAULT NULL,
  `registration_fee_USD` int(11) DEFAULT NULL,
  `entered_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `registered_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `role`, `registered_date`) VALUES
(4, 'mirshath', '$2y$10$G7KuxDmz6umK5R24juj1P.GfQcC.iH1e22UIdB1xk2szx2nPEZhgS', 'super_admin', '2025-02-13 08:20:53'),
(12, 'miru', '$2y$10$FUL3/gxai8w37NVc53lA..JSCbXtxdkAomJAr0JNR9.t3QEJimVBK', 'data_enter', '2025-02-13 08:20:53'),
(14, 'hasni', '$2y$10$vTCIw2mIvn2IaeIVNr4FmeiPDX5Gs5kh507ttCoTojFWBC7LXTkoK', 'manager', '2025-05-11 03:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `allocated_components`
--

CREATE TABLE `allocated_components` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `main_component_id` int(11) NOT NULL,
  `sub_component_id` int(11) DEFAULT NULL,
  `entered_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allocated_components`
--

INSERT INTO `allocated_components` (`id`, `module_id`, `main_component_id`, `sub_component_id`, `entered_by`, `created_at`) VALUES
(105, 171, 35, NULL, NULL, '2025-01-18 11:11:22'),
(106, 171, 36, NULL, NULL, '2025-01-18 11:11:45'),
(107, 171, 37, NULL, NULL, '2025-01-18 11:12:01'),
(108, 172, 38, NULL, NULL, '2025-01-18 11:12:18'),
(109, 172, 39, NULL, NULL, '2025-01-18 11:12:29'),
(110, 173, 35, NULL, NULL, '2025-01-18 11:12:55'),
(111, 173, 40, NULL, NULL, '2025-01-18 11:13:12'),
(112, 174, 35, NULL, NULL, '2025-01-18 11:13:22'),
(113, 174, 40, NULL, NULL, '2025-01-18 11:13:37'),
(114, 175, 41, NULL, NULL, '2025-01-18 11:14:02'),
(115, 176, 42, NULL, NULL, '2025-01-18 11:14:13'),
(116, 177, 43, NULL, NULL, '2025-01-18 11:18:55'),
(117, 177, 44, NULL, NULL, '2025-01-18 11:19:12'),
(118, 178, 45, NULL, NULL, '2025-01-18 11:19:23'),
(119, 178, 46, NULL, NULL, '2025-01-18 11:19:33'),
(120, 179, 47, NULL, NULL, '2025-01-18 11:19:43'),
(123, 180, 47, NULL, NULL, '2025-01-20 06:04:18'),
(126, 183, 58, NULL, NULL, '2025-02-05 05:00:18'),
(127, 183, 57, NULL, NULL, '2025-02-05 05:00:47'),
(128, 184, 56, NULL, NULL, '2025-02-05 05:01:26'),
(129, 184, 55, NULL, NULL, '2025-02-05 05:01:52'),
(130, 185, 54, NULL, NULL, '2025-02-05 05:02:20'),
(131, 185, 53, NULL, NULL, '2025-02-05 05:02:34'),
(132, 186, 52, NULL, NULL, '2025-02-05 05:03:06'),
(164, 198, 68, NULL, NULL, '2025-02-05 10:33:19'),
(177, 189, 60, 3, NULL, '2025-03-20 08:28:33'),
(178, 189, 60, 4, NULL, '2025-03-20 08:28:33'),
(179, 189, 61, NULL, NULL, '2025-03-20 08:28:44'),
(181, 192, 60, 12, NULL, '2025-04-23 10:39:16'),
(182, 192, 61, NULL, NULL, '2025-04-23 10:39:31'),
(183, 205, 35, NULL, NULL, '2025-04-30 05:12:01'),
(184, 205, 40, NULL, NULL, '2025-04-30 05:12:12');

-- --------------------------------------------------------

--
-- Table structure for table `allocate_programme`
--

CREATE TABLE `allocate_programme` (
  `id` int(11) NOT NULL,
  `student_code` int(20) NOT NULL,
  `university_id` int(11) NOT NULL,
  `programme_code` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `student_registration_id` varchar(50) NOT NULL,
  `elective_subs` text DEFAULT NULL,
  `compulsory_sub` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `dm_remark` varchar(100) NOT NULL,
  `entered_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allocate_programme`
--

INSERT INTO `allocate_programme` (`id`, `student_code`, `university_id`, `programme_code`, `batch_id`, `student_registration_id`, `elective_subs`, `compulsory_sub`, `status`, `dm_remark`, `entered_by`) VALUES
(1, 1, 1, 46, 34, '481072452', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(2, 2, 1, 46, 34, '482112401', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(3, 3, 1, 46, 34, '484032501', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(4, 4, 1, 46, 34, '484032502', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(5, 5, 1, 46, 34, '484032503', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(6, 6, 1, 46, 34, '484032504', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(7, 7, 1, 46, 34, '484032505', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(8, 8, 1, 46, 34, '484032506', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(9, 9, 1, 46, 34, '484032507', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(10, 10, 1, 46, 34, '484032508', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(11, 11, 1, 46, 34, '484032509', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(12, 12, 1, 46, 34, '484032510', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(13, 13, 1, 46, 34, '484032511', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(14, 14, 1, 46, 34, '484032512', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(15, 15, 1, 46, 34, '484032513', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(16, 16, 1, 46, 34, '484032514', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(17, 17, 1, 46, 34, '484032515', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(18, 18, 1, 46, 34, '484032516', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(19, 19, 1, 46, 34, '484032517', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(20, 20, 1, 46, 34, '484032518', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(21, 21, 1, 46, 34, '484032519', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(22, 22, 1, 46, 34, '484032520', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(23, 23, 1, 46, 34, '484032521', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(24, 24, 1, 46, 34, '484032522', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(25, 25, 1, 46, 34, '484032523', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(26, 26, 1, 46, 34, '484032524', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(27, 27, 1, 46, 34, '484032525 ', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(28, 28, 1, 46, 34, '484032526', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(29, 29, 1, 46, 34, '484032527', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(30, 30, 1, 46, 34, '484032528', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(31, 31, 1, 46, 34, '484032529', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(32, 32, 1, 46, 34, '484032530', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(33, 33, 1, 46, 34, '484032531', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(34, 34, 1, 46, 34, '484032532', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(35, 35, 1, 46, 34, '484032533', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(36, 36, 1, 46, 34, '484032534', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(37, 37, 1, 46, 34, '484032535', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(38, 38, 1, 46, 34, '484032536', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(39, 39, 1, 46, 34, '484032537', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(40, 40, 1, 46, 34, '484032538', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(41, 41, 1, 46, 34, '484032539', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(42, 42, 1, 46, 35, '483112407', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(43, 43, 1, 46, 35, '483112445', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(44, 44, 1, 46, 35, '485032501', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(45, 45, 1, 46, 35, '485032502', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(46, 46, 1, 46, 35, '485032503', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(47, 47, 1, 46, 35, '485032504', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(48, 48, 1, 46, 35, '485032505', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(49, 49, 1, 46, 35, '485032506', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(50, 50, 1, 46, 35, '485032507', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(51, 51, 1, 46, 35, '485032508 ', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(52, 52, 1, 46, 35, '485032509', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(53, 53, 1, 46, 35, '485032510', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(54, 54, 1, 46, 35, '485032511', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(55, 55, 1, 46, 35, '485032512', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(56, 56, 1, 46, 35, '485032513', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(57, 57, 1, 46, 35, '485032514', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(58, 58, 1, 46, 35, '485032515', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(59, 59, 1, 46, 35, '485032516', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(60, 60, 1, 46, 35, '485032517', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(61, 61, 1, 46, 35, '485032518', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(62, 62, 1, 46, 35, '485032519', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(63, 63, 1, 46, 35, '485032520', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(64, 64, 1, 46, 35, '485032521', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(65, 65, 1, 46, 35, '485032522', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(66, 66, 1, 46, 35, '485032523', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(67, 67, 1, 46, 35, '485032524', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(68, 68, 1, 46, 35, '485032525', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(69, 69, 1, 46, 35, '485032526', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(70, 70, 1, 46, 35, '485032527', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(71, 71, 1, 46, 35, '485032528', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(72, 72, 1, 46, 35, '485032529', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(73, 73, 1, 46, 35, '485032530', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(74, 74, 1, 46, 35, '485032531', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(75, 75, 1, 46, 35, '485032532', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(76, 76, 1, 46, 35, '485032533', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(77, 77, 1, 46, 35, '485032534', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(78, 78, 1, 46, 35, '485032535', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(79, 79, 1, 46, 35, '485032536', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(80, 80, 1, 46, 35, '485032537', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(81, 81, 1, 46, 35, '485032538', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(82, 82, 1, 46, 35, '485032539', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(83, 83, 1, 46, 35, '485032540', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(84, 84, 1, 46, 35, '485032541', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(85, 85, 1, 46, 35, '485032542', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(86, 86, 1, 46, 35, '485032543', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(87, 87, 1, 46, 35, '485032544', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(88, 88, 1, 46, 35, '485032545', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(89, 89, 1, 46, 35, '485032546', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(90, 90, 1, 46, 35, '485032547', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(91, 91, 1, 46, 35, '485032548', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(92, 92, 1, 46, 35, '485032549', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(93, 93, 1, 46, 35, '485032550', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(94, 94, 1, 46, 35, '485032551', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(95, 95, 1, 46, 35, '485032552', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(96, 96, 1, 46, 35, '485032553', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(97, 97, 1, 46, 35, '485032554', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(98, 98, 1, 46, 35, '485032555', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(99, 99, 1, 46, 35, '485032556', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(100, 100, 1, 46, 35, '485032557', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(101, 101, 1, 46, 35, '485032558', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(102, 102, 1, 46, 35, '485032559', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(103, 103, 1, 46, 35, '485032560', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(104, 104, 1, 46, 35, '485032561', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(105, 105, 1, 46, 35, '485032562', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath'),
(106, 106, 1, 46, 35, '485032563', '', 'Leadership and Management in a Digital Economy, Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques,Research Methods for Managers,Research Project', 'active', '', 'mirshath');

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `programme_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `year_id` varchar(255) NOT NULL,
  `semester_id` varchar(255) NOT NULL,
  `assessment_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `attachment_1` varchar(255) DEFAULT NULL,
  `attachment_2` varchar(255) DEFAULT NULL,
  `attachment_3` varchar(255) DEFAULT NULL,
  `attachment_4` varchar(255) DEFAULT NULL,
  `mail_sent` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `main_component_id` int(11) DEFAULT NULL,
  `sub_component_id` int(11) DEFAULT NULL,
  `entered_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_components`
--

CREATE TABLE `assignment_components` (
  `id` int(11) NOT NULL,
  `assessment_code` varchar(255) NOT NULL,
  `as_main_component_name` varchar(255) NOT NULL,
  `main_component_percent` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignment_components`
--

INSERT INTO `assignment_components` (`id`, `assessment_code`, `as_main_component_name`, `main_component_percent`) VALUES
(25, 'IFD1', 'Written  Exam I', '50%'),
(26, 'IFD1', 'Written Exam II', '50%'),
(27, 'IFD', 'Group Presentation', '50%'),
(28, 'ifd', 'Written Exam I', '30%'),
(29, 'ifd', 'Written Exam', '70%'),
(30, 'ifd', 'Close Book Exam', '50%'),
(31, 'ifd', 'Open Book Exam', '50%'),
(32, 'ifd', 'Practical Exam', '30%'),
(33, 'ifd', 'Assignment', '70%'),
(34, 'ifd', 'Assignment', '100%'),
(35, 'ifd', 'Written  Exam I - (LO 1&2)   ', NULL),
(36, 'ifd', 'Group Poster Presentation - (LO3', NULL),
(37, 'ifd', 'Written Exam II - (LO4) ', NULL),
(38, 'ifd', 'Assignment - (LO 1&2)', NULL),
(39, 'ifd', 'Individual Oral Presentation (LO3&4)', NULL),
(40, 'ifd', 'Written Exam II (LO 3&4)', NULL),
(41, 'ifd', 'Group Oral Presentation (LO1,2,3&4)', NULL),
(42, 'ifd', 'Assignment - (LO1,2,3&4)', NULL),
(43, 'ecm', 'Assignemt', '50%'),
(44, 'ecm', 'Exam', '50%'),
(45, 'ecm', 'Presentation', '80%'),
(46, 'ecm', 'Reflective Statement', '20%'),
(47, 'ecm', 'Exam', '100%'),
(52, 'gdm', 'Time Constraint Assessment (CBE) :LO1, LO2, LO3, LO4', ''),
(53, 'gdm', 'Group Presentation : LO3 & LO4 ', ''),
(54, 'gdm', 'Individual Assignment : LO1 & LO2', ''),
(55, 'gdm', 'Individual Digital Poster Presentation with 2 min video : LO3 & LO4', ''),
(56, 'gdm', 'Time Constraint Assessment (OBE) : LO1 & LO2', ''),
(57, 'gdm', 'Individual Assignment : LO3 & LO4', ''),
(58, 'gdm', 'Case study-based Time Constraint Assessment (OBE) : LO1 & LO2', ''),
(60, 'bbm', 'Continuous Assessment ', '40%'),
(61, 'bbm', 'End-Semester Examination', '60%'),
(62, 'bbm', 'Continuous Assessment', '60%'),
(63, 'bbm', 'End-Semester Examination', '40%'),
(64, 'bbm', 'Continuous Assessment', '50%'),
(65, 'bbm', 'End-Semester Examination', '50%'),
(68, 'hd_biomed', 'Assignment', '60%'),
(74, 'b1', 'Continuous Assessment', '40%');

-- --------------------------------------------------------

--
-- Table structure for table `batch_table`
--

CREATE TABLE `batch_table` (
  `id` int(11) NOT NULL,
  `batch_name` varchar(255) NOT NULL,
  `university` int(11) NOT NULL,
  `programme` int(11) NOT NULL,
  `year_batch_code` varchar(50) NOT NULL,
  `intake_date` date NOT NULL,
  `end_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_table`
--

INSERT INTO `batch_table` (`id`, `batch_name`, `university`, `programme`, `year_batch_code`, `intake_date`, `end_date`, `created_at`, `updated_at`) VALUES
(12, 'Batch 30', 1, 39, '2024', '2025-01-09', '2025-01-31', '2025-01-18 10:48:35', '2025-01-18 10:48:35'),
(13, 'Batch 31', 1, 39, '2024', '2025-01-08', '2025-01-31', '2025-01-18 10:49:02', '2025-01-18 10:49:02'),
(14, 'Batch 20', 1, 40, '2024', '2025-01-07', '2025-01-23', '2025-01-18 10:49:28', '2025-01-18 10:49:28'),
(15, 'Batch 21', 1, 40, '2024', '2025-01-05', '2025-01-30', '2025-01-18 10:49:42', '2025-01-18 10:49:42'),
(16, 'Batch 22', 1, 40, '2024', '2025-01-14', '2025-01-30', '2025-01-18 10:49:55', '2025-01-18 10:49:55'),
(17, 'Batch 14', 1, 41, '2023', '2025-01-07', '2025-01-31', '2025-01-18 10:51:12', '2025-01-18 10:51:12'),
(18, 'Batch 15', 1, 41, '2023', '2025-01-13', '2025-01-24', '2025-01-18 10:51:23', '2025-01-18 10:51:23'),
(19, 'Batch 16', 1, 41, '2023', '2025-01-05', '2025-01-31', '2025-01-18 10:51:50', '2025-01-18 10:51:50'),
(20, 'Batch 17', 1, 41, '2024', '2025-01-07', '2025-01-30', '2025-01-18 10:52:04', '2025-01-18 10:52:04'),
(21, 'Batch 18', 1, 41, '2024', '2025-01-09', '2025-01-30', '2025-01-18 10:52:18', '2025-01-18 10:52:18'),
(22, 'Batch 76', 1, 46, '2023', '2025-01-13', '2025-01-31', '2025-01-18 10:53:32', '2025-01-18 10:53:32'),
(23, 'Batch 77', 1, 46, '2023', '2025-01-08', '2025-01-31', '2025-01-18 10:53:45', '2025-01-18 10:53:45'),
(25, 'Batch 05', 1, 45, '2023', '2025-01-13', '2025-01-31', '2025-01-18 10:54:42', '2025-01-18 10:54:42'),
(26, 'Batch 06', 1, 45, '2023', '2025-01-14', '2025-01-31', '2025-01-18 10:54:52', '2025-01-18 10:54:52'),
(28, 'Batch 07', 1, 45, '2023', '2025-01-06', '2025-01-24', '2025-01-18 10:55:29', '2025-01-18 10:55:29'),
(29, 'Batch 01', 1, 47, '2023', '2025-01-14', '2025-01-30', '2025-01-18 10:56:11', '2025-01-18 10:56:11'),
(30, 'Batch 02', 1, 47, '2024', '2025-01-05', '2025-01-30', '2025-01-18 10:56:23', '2025-01-18 10:56:23'),
(31, 'Batch 29', 1, 42, '2025', '2025-02-07', '2025-02-28', '2025-02-05 10:38:00', '2025-02-05 10:38:00'),
(34, 'Batch 84', 1, 46, '2025', '2025-05-16', '2025-05-17', '2025-05-16 04:49:28', '2025-05-16 04:49:28'),
(35, 'Batch 85', 1, 46, '2025', '2025-05-16', '2025-05-17', '2025-05-16 04:49:55', '2025-05-16 04:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `bbm_direct_result`
--

CREATE TABLE `bbm_direct_result` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `std_reg_id` varchar(200) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `main_comp_id` int(11) DEFAULT NULL,
  `sub_component_id` int(11) DEFAULT NULL,
  `with_Ques` varchar(10) DEFAULT NULL,
  `question_no` varchar(50) NOT NULL,
  `100marksEx1` varchar(50) DEFAULT NULL,
  `examiner1_marks` decimal(5,2) DEFAULT NULL,
  `100marksEx2` varchar(50) DEFAULT NULL,
  `examiner2_marks` decimal(5,2) DEFAULT NULL,
  `final_marks` decimal(5,2) DEFAULT NULL,
  `status` enum('Pending','Finalized') DEFAULT 'Pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bbm_final_results_tbl`
--

CREATE TABLE `bbm_final_results_tbl` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_reg_code` varchar(200) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `main_comp_id` int(11) NOT NULL,
  `sub_comp_id` int(11) NOT NULL,
  `final_result` decimal(10,2) DEFAULT NULL,
  `final_result_ex2` decimal(10,2) DEFAULT NULL,
  `que_no` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bbm_result_details`
--

CREATE TABLE `bbm_result_details` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `main_comp_id` int(11) DEFAULT NULL,
  `sub_component_id` int(11) DEFAULT NULL,
  `with_Ques` varchar(10) DEFAULT NULL,
  `question_no` varchar(50) NOT NULL,
  `100marksEx1` varchar(50) DEFAULT NULL,
  `examiner1_marks` decimal(5,2) DEFAULT NULL,
  `100marksEx2` varchar(50) DEFAULT NULL,
  `examiner2_marks` decimal(5,2) DEFAULT NULL,
  `final_marks` decimal(5,2) DEFAULT NULL,
  `status` enum('Pending','Finalized') DEFAULT 'Pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bms_grading`
--

CREATE TABLE `bms_grading` (
  `id` int(11) NOT NULL,
  `bms_grad` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bms_grading`
--

INSERT INTO `bms_grading` (`id`, `bms_grad`) VALUES
(1, 'completed'),
(2, 'not-complete');

-- --------------------------------------------------------

--
-- Table structure for table `coordinator_table`
--

CREATE TABLE `coordinator_table` (
  `id` int(11) NOT NULL,
  `coordinator_code` varchar(50) NOT NULL,
  `title` enum('Mr','Mrs','Ms','Dr','Prof') NOT NULL,
  `coordinator_name` varchar(100) NOT NULL,
  `bms_email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `criterias`
--

CREATE TABLE `criterias` (
  `id` int(11) NOT NULL,
  `criteria_code` varchar(100) NOT NULL,
  `criteria_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `criterias`
--

INSERT INTO `criterias` (`id`, `criteria_code`, `criteria_name`, `created_at`) VALUES
(1, 'CR01', 'Bachelor', '2024-08-12 08:22:32'),
(2, 'CR02', 'Masters', '2024-08-12 08:22:32'),
(3, 'CR03', 'Diploma', '2024-08-12 08:22:32'),
(4, 'CR04', 'CBM', '2024-08-12 08:22:32'),
(7, 'CR006', 'A/L', '2024-08-14 05:37:09'),
(8, 'CR007', 'Work Experience', '2024-08-14 05:37:09'),
(9, 'CR008', 'PGDip', '2024-08-14 05:38:05'),
(10, 'CR009', 'IFD', '2024-08-14 05:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `currency_table`
--

CREATE TABLE `currency_table` (
  `id` int(11) NOT NULL,
  `currency_code` varchar(50) NOT NULL,
  `currency_name` varchar(100) NOT NULL,
  `short_name` varchar(50) NOT NULL,
  `symbol` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `currency_table`
--

INSERT INTO `currency_table` (`id`, `currency_code`, `currency_name`, `short_name`, `symbol`) VALUES
(1, 'Rs', 'Sri Lankan Rupees', 'LKR', 'Rs'),
(2, 'GBP', 'Pound Sterling', 'GBP', '£'),
(3, 'USD', 'United States Dollar', 'USD', '$');

-- --------------------------------------------------------

--
-- Table structure for table `decision_table`
--

CREATE TABLE `decision_table` (
  `id` int(11) NOT NULL,
  `decision_code` varchar(50) NOT NULL,
  `decision_name` varchar(100) NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `decision_table`
--

INSERT INTO `decision_table` (`id`, `decision_code`, `decision_name`, `description`) VALUES
(7, 'dasf', 'sf', 'sdfsd');

-- --------------------------------------------------------

--
-- Table structure for table `description_grading`
--

CREATE TABLE `description_grading` (
  `id` int(11) NOT NULL,
  `description_grad` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `description_grading`
--

INSERT INTO `description_grading` (`id`, `description_grad`) VALUES
(1, 'distinction'),
(2, 'merit'),
(3, 'pass'),
(4, 'resit'),
(5, 'withheld'),
(6, 'absent');

-- --------------------------------------------------------

--
-- Table structure for table `failed_emails`
--

CREATE TABLE `failed_emails` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) DEFAULT NULL,
  `student_email` varchar(255) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `final_student_results`
--

CREATE TABLE `final_student_results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_registration_id` varchar(50) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `final_result` varchar(50) NOT NULL,
  `entered_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gpa_calculate_tbl`
--

CREATE TABLE `gpa_calculate_tbl` (
  `id` int(11) NOT NULL,
  `std_id` int(11) NOT NULL,
  `std_reg_no` varchar(100) DEFAULT NULL,
  `prog_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `year_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `total_credit_value` decimal(10,2) DEFAULT 0.00,
  `total_CGP_value` decimal(10,2) DEFAULT 0.00,
  `final_GPA_value` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_table`
--

CREATE TABLE `grade_table` (
  `id` int(11) NOT NULL,
  `grade_code` varchar(50) NOT NULL,
  `grade_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_table`
--

INSERT INTO `grade_table` (`id`, `grade_code`, `grade_name`, `created_at`, `updated_at`) VALUES
(1, '0001', 'Distinction', '2024-08-16 09:47:10', '2024-08-16 10:11:16'),
(2, '0002', 'Merit', '2024-08-16 09:47:27', '2024-08-16 09:47:27'),
(3, '0003', 'Pass', '2024-08-16 09:47:38', '2024-08-16 09:51:18');

-- --------------------------------------------------------

--
-- Table structure for table `installment_details_table`
--

CREATE TABLE `installment_details_table` (
  `id` int(11) NOT NULL,
  `installment_payment_table_id` int(11) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `programme_batch` varchar(100) DEFAULT NULL,
  `installment_numbers` varchar(250) DEFAULT NULL,
  `devided_values` decimal(10,2) DEFAULT NULL,
  `installment_amount` decimal(10,2) DEFAULT NULL,
  `discount_type` varchar(50) DEFAULT NULL,
  `discount_value` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `entered_by` varchar(50) DEFAULT NULL,
  `updated_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `installment_payment_table`
--

CREATE TABLE `installment_payment_table` (
  `id` int(11) NOT NULL,
  `payment_plans_tb_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `programme_batch` varchar(255) DEFAULT NULL,
  `unifee_lkr_total` int(11) DEFAULT NULL,
  `unifee_lkr` int(11) DEFAULT NULL,
  `unifee_gbp_total` int(11) DEFAULT NULL,
  `unifee_gbp` int(11) DEFAULT NULL,
  `unifee_usd_total` int(11) DEFAULT NULL,
  `unifee_usd` int(11) DEFAULT NULL,
  `fee_type` varchar(255) DEFAULT NULL,
  `coursefee_total` int(11) DEFAULT NULL,
  `coursefee` int(11) DEFAULT NULL,
  `registrationfee` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `type` varchar(255) NOT NULL,
  `university` varchar(255) NOT NULL,
  `programme` varchar(255) NOT NULL,
  `intake` date NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `contact` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `status` varchar(50) NOT NULL,
  `entered_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads_table`
--

CREATE TABLE `leads_table` (
  `id` int(11) NOT NULL,
  `lead_type` varchar(100) DEFAULT NULL,
  `entered_by` varchar(250) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads_table`
--

INSERT INTO `leads_table` (`id`, `lead_type`, `entered_by`, `created_at`) VALUES
(1, 'Websites', NULL, '2024-08-12 06:41:04'),
(2, 'Social Media', NULL, '2024-08-12 06:41:04'),
(3, 'Referrals', NULL, '2024-08-12 06:41:04'),
(96, 'Walk in', NULL, '2024-10-01 11:13:56'),
(102, 'Call', 'mirshath', '2025-03-20 10:22:16');

-- --------------------------------------------------------

--
-- Table structure for table `lecturer_table`
--

CREATE TABLE `lecturer_table` (
  `id` int(11) NOT NULL,
  `title` enum('Mr','Mrs','Ms','Dr','Prof') NOT NULL,
  `lecturer_name` varchar(255) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `qualification` text NOT NULL,
  `programs` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `module_code` varchar(50) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `university_id` int(11) NOT NULL,
  `programme_id` int(11) NOT NULL,
  `year_id` int(10) DEFAULT NULL,
  `semester_id` int(10) DEFAULT NULL,
  `pass_mark` varchar(50) NOT NULL,
  `type` enum('Compulsory','Elective') NOT NULL,
  `lecturers` varchar(255) NOT NULL,
  `institution` varchar(255) NOT NULL,
  `module_GPA` int(11) DEFAULT NULL,
  `examinor_1` varchar(100) DEFAULT NULL,
  `examinor_2` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `module_code`, `module_name`, `university_id`, `programme_id`, `year_id`, `semester_id`, `pass_mark`, `type`, `lecturers`, `institution`, `module_GPA`, `examinor_1`, `examinor_2`) VALUES
(171, 'Unit 1', 'Economics for Business', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(172, 'Unit 2', 'Business Essentials', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(174, 'Unit 4', 'Mathematics and Statistics', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(175, 'Unit 5', 'Digital Technology and Study Skills', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(176, 'Unit 6', 'English for Academic Purposes', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(177, 'ECM101', 'Principles of Management', 1, 45, 42, 27, '50', 'Compulsory', '', '', NULL, NULL, NULL),
(178, 'ECM102', 'Marketing Essentials', 1, 45, 42, 27, '50', 'Compulsory', '', '', NULL, NULL, NULL),
(179, 'ECM103', 'Accounting Principles', 1, 45, 42, 27, '50', 'Compulsory', '', '', NULL, NULL, NULL),
(183, 'GDM Unit 1', 'Leadership and Management in a Digital Economy', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(184, 'GDM Unit 2', 'Human Resource Management', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(185, 'GDM Unit 3', 'Marketing and Digital Strategy', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(186, 'GDM Unit 4', 'Financial Principles and Techniques ', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(187, 'GDM Unit 5', 'Research Methods for Managers', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(188, 'GDM Unit 6', 'Research Project', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(189, 'MG BM1114', 'Introduction to Business and Management', 1, 47, 17, 1, '40%', 'Compulsory', '', '', 5, '4', '12'),
(190, 'MG BM1124', 'Business Environment', 1, 47, 17, 1, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(191, 'MG BM1134', 'Business Communication', 1, 47, 17, 1, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(192, 'MG BM1143', 'Business Law ', 1, 47, 17, 1, '40%', 'Compulsory', '', '', 3, '4', '12'),
(193, 'MG BM1213', 'Information Technology', 1, 47, 17, 2, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(194, 'MG BM1224', 'Marketing Management', 1, 47, 17, 2, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(195, 'MG BM1234', 'Financial Accounting', 1, 47, 17, 2, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(196, 'MG BM1244', 'Humen Resource Management ', 1, 47, 17, 2, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(205, 'Unit 3', 'Accounting Fundamentals', 1, 39, 42, 27, '100', 'Compulsory', '', '', 6, '4', '4');

-- --------------------------------------------------------

--
-- Table structure for table `nav_collections`
--

CREATE TABLE `nav_collections` (
  `id` int(11) NOT NULL,
  `main_list` varchar(250) NOT NULL,
  `sub_lists` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nav_collections`
--

INSERT INTO `nav_collections` (`id`, `main_list`, `sub_lists`) VALUES
(1, 'File', 'Lead Types,Year,Semester,Criteria,University,Coordinator,Program,Assignment Components,Lecture,Module,Batch,Grade,Currency,Status,Decision'),
(2, 'Transaction', 'Add Leads,Student Registration,Upload Students,Allocate Program,Components Allocations,Update Student Status,Student Batch Transfer,Update Students E Module,Upload Student Documents,student_upload_copy_edit_button,Add Payment Plan,Batch Wise Payment Plan,Payment,Penalty Payment,Additional Payment,Send Offer Letter,Time Table,Daily Time Table Message,Special Class Messages,Exam / Assignment,exams_asses_send_button,exams_asses_edit_button,Exam / Assignment Result,E/A Result Send,Module Results Mail,Special Reason,Add Decision,Alumni,List Board'),
(3, 'Edit', 'Edit Programme Allocation,Edit Payment'),
(4, 'Re Print', ''),
(5, 'Reports', 'All Student Details,Student Wise Details,Leads Report,Outstanding Payment,Special Reason Report,Exam / Assignment Report,Result Mailing Report,Payment Report,Penalty Payment Report,Additional Payment Report,Time Table Report,Alumni Report,BBM result report'),
(6, 'Options', 'Add Users,User Permission'),
(7, 'Dashboard', 'card_enable'),
(8, 'Cancellations', 'payment cancellation,penalty payment cancellation,Additional payment cancellation');

-- --------------------------------------------------------

--
-- Table structure for table `nested_assign_components`
--

CREATE TABLE `nested_assign_components` (
  `id` int(11) NOT NULL,
  `nested_components_name` varchar(50) DEFAULT NULL,
  `nested_components_percent` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `normal_grading`
--

CREATE TABLE `normal_grading` (
  `id` int(11) NOT NULL,
  `normal_grad` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `normal_grading`
--

INSERT INTO `normal_grading` (`id`, `normal_grad`) VALUES
(1, 'A+'),
(2, 'A'),
(3, 'A-'),
(4, 'B+'),
(5, 'B'),
(6, 'B-'),
(7, 'C+');

-- --------------------------------------------------------

--
-- Table structure for table `payment_cancellation_log`
--

CREATE TABLE `payment_cancellation_log` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `cancelled_by` varchar(50) NOT NULL,
  `cancelled_at` datetime NOT NULL,
  `payment_type` varchar(20) DEFAULT 'regular'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_uni_fee`
--

CREATE TABLE `payment_uni_fee` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_batch` varchar(255) NOT NULL,
  `currency_type` varchar(200) NOT NULL,
  `paid_amount` double NOT NULL,
  `exchange_rate` double NOT NULL,
  `LKR_money` double NOT NULL,
  `paid_date` date DEFAULT NULL,
  `payment_type` varchar(200) DEFAULT NULL,
  `bank_name` varchar(200) NOT NULL,
  `card_bank_deposit_dt` date DEFAULT NULL,
  `entered_by` varchar(100) DEFAULT 'admin',
  `entered_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_wise_info`
--

CREATE TABLE `payment_wise_info` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_batch` varchar(255) NOT NULL,
  `installmentNumber` varchar(50) NOT NULL,
  `paymentAmount` decimal(10,2) NOT NULL,
  `paid_date` date DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `card_bank_deposit_dt` date NOT NULL,
  `entered_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `entered_by` varchar(255) NOT NULL,
  `rcpt_number` varchar(50) DEFAULT NULL,
  `status` enum('paid','pending','cancelled') DEFAULT 'paid',
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` varchar(50) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_payments`
--

CREATE TABLE `penalty_payments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL COMMENT 'Student code from students table',
  `student_registration_id` varchar(50) NOT NULL COMMENT 'Registration ID from allocate_programme',
  `programme_code` varchar(20) NOT NULL COMMENT 'Program code from program_table',
  `batch_id` int(11) NOT NULL COMMENT 'Batch ID from batch_table',
  `program_batch` varchar(100) NOT NULL COMMENT 'Combined program and batch name for easy reference',
  `penalty_type` varchar(50) NOT NULL COMMENT 'Type of penalty (Late Fee, Missing Documents, etc.)',
  `penalty_amount` decimal(10,2) NOT NULL COMMENT 'Original penalty amount',
  `discount_type` varchar(50) DEFAULT 'N/A' COMMENT 'Type of discount if applicable',
  `discount_amount` decimal(10,2) DEFAULT 0.00 COMMENT 'Discount amount',
  `final_amount` decimal(10,2) NOT NULL COMMENT 'Final amount after discount',
  `paid_date` date NOT NULL COMMENT 'Date when penalty was paid',
  `payment_method` varchar(30) DEFAULT 'Cash' COMMENT 'Method of payment',
  `payment_reference` varchar(100) DEFAULT NULL COMMENT 'Reference number for payment if applicable',
  `remarks` text DEFAULT NULL COMMENT 'Any additional notes',
  `status` enum('paid','pending','cancelled') NOT NULL DEFAULT 'paid' COMMENT 'Payment status',
  `entered_by` varchar(50) NOT NULL COMMENT 'Username who entered this record',
  `created_at` datetime NOT NULL COMMENT 'Record creation timestamp',
  `updated_at` datetime DEFAULT NULL COMMENT 'Record last update timestamp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `program_table`
--

CREATE TABLE `program_table` (
  `program_code` int(11) NOT NULL,
  `university_id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `prog_code` varchar(50) DEFAULT NULL,
  `coordinator_name` varchar(255) DEFAULT NULL,
  `medium` enum('English','Tamil') NOT NULL,
  `duration` varchar(100) DEFAULT NULL,
  `result_method` varchar(50) NOT NULL,
  `course_fee_lkr` decimal(10,2) DEFAULT NULL,
  `course_fee_gbp` decimal(10,2) DEFAULT NULL,
  `course_fee_usd` decimal(10,2) DEFAULT NULL,
  `course_fee_euro` decimal(10,2) DEFAULT NULL,
  `entry_requirement` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_table`
--

INSERT INTO `program_table` (`program_code`, `university_id`, `program_name`, `prog_code`, `coordinator_name`, `medium`, `duration`, `result_method`, `course_fee_lkr`, `course_fee_gbp`, `course_fee_usd`, `course_fee_euro`, `entry_requirement`) VALUES
(39, 1, 'IFD (Business) - ATHE Level 3', 'IFD1', 'Hasni Nihar', 'English', '12 Months', '', 5000.00, 5000.00, 5000.00, 5000.00, 'Bachelor,Masters,Diploma,CBM,A/L'),
(40, 1, 'IFD (Applied Science) - ATHE Level 3', 'IFD0', 'Asma Raahman', 'English', '12 months', '', 5000.00, 5000.00, 5000.00, 5000.00, 'Bachelor,Diploma,CBM,PGDip'),
(41, 1, 'BTEC Higher National Diploma in Business', 'btech', 'Geethika Liyanage', 'English', '18 months', '', 5000.00, 0.00, 0.00, 0.00, 'Bachelor,Masters,Diploma,CBM'),
(42, 1, 'Higher Diploma in Biomedical Science', 'hd-bio-sci', 'Geethika Liyanage', 'English', '18 months', '', 4000.00, 0.00, 0.00, 0.00, 'Bachelor,Masters,Diploma,CBM'),
(43, 1, 'Higher Diploma in Biotechnology', 'hd-bio-tech', 'Hasni Nihar', 'English', '18 months', '', 7000.00, 0.00, 0.00, 0.00, 'Masters,Diploma,A/L'),
(44, 1, 'Higher Diploma in Food Science and Nutrition', 'hd-fd-sc', 'Asma Raahman', 'English', '18 months', '', 4000.00, 0.00, 0.00, 0.00, 'A/L'),
(45, 1, 'Executive Certificate in Management', 'ecm', 'Asma Raahman', 'English', '6 months', '', 4500.00, 0.00, 0.00, 0.00, 'A/L'),
(46, 1, 'Graduate Diploma in Management (Level 6)', 'gdm', 'Geethika Liyanage', 'English', '12 months', '', 3500.00, 0.00, 0.00, 0.00, 'A/L'),
(47, 1, 'Bachelor of Business Management (Hons)', 'bbm', 'Hasni Nihar', 'English', '4 years', '', 12000.00, 0.00, 0.00, 0.00, 'A/L'),
(48, 1, 'Higher Diploma in Medical Biotechnology', 'hd-medi-bio', 'Asma Raahman', 'English', '18 months', '', 3600.00, 0.00, 0.00, 0.00, 'A/L');

-- --------------------------------------------------------

--
-- Table structure for table `semester_table`
--

CREATE TABLE `semester_table` (
  `id` int(11) NOT NULL,
  `semester_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semester_table`
--

INSERT INTO `semester_table` (`id`, `semester_name`, `created_at`) VALUES
(1, 'Semester I', '2024-01-01 04:30:00'),
(2, 'Semester II', '2024-04-01 04:30:00'),
(3, 'Semester III', '2024-08-01 04:30:00'),
(5, 'Semester IV', '2024-08-12 07:57:21'),
(27, 'N/A', '2024-10-01 11:22:20');

-- --------------------------------------------------------

--
-- Table structure for table `special_class_messages`
--

CREATE TABLE `special_class_messages` (
  `id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `class_date` date NOT NULL,
  `class_time` time NOT NULL,
  `link` text DEFAULT NULL,
  `mail_subject` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status_table`
--

CREATE TABLE `status_table` (
  `id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_table`
--

INSERT INTO `status_table` (`id`, `status_name`) VALUES
(2, 'Prospectives'),
(3, 'Not prospective'),
(4, 'Interested'),
(7, 'N/A'),
(9, 'Future Intake');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_code` int(20) NOT NULL,
  `title` enum('Mr','Mrs','Ms','Dr','Prof') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `certificate_name` varchar(100) NOT NULL,
  `preferred_name` varchar(50) NOT NULL,
  `date_of_birth` date NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `permanent_address` text NOT NULL,
  `current_address` text NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `emergency_contact_name` varchar(100) NOT NULL,
  `emergency_contact_number` varchar(20) NOT NULL,
  `english_ability` tinyint(1) NOT NULL,
  `minimum_entry_qualification` tinyint(1) NOT NULL,
  `nic` varchar(20) NOT NULL,
  `passport` varchar(20) NOT NULL,
  `personal_email` varchar(100) NOT NULL,
  `bms_email` varchar(100) NOT NULL,
  `occupation` varchar(100) NOT NULL,
  `organization` varchar(100) NOT NULL,
  `previous_organization` varchar(100) NOT NULL,
  `qualifications` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 0,
  `student_status` varchar(255) DEFAULT 'Active',
  `transfer_status` tinyint(4) NOT NULL DEFAULT 0,
  `remark` varchar(255) NOT NULL,
  `entered_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_code`, `title`, `first_name`, `last_name`, `certificate_name`, `preferred_name`, `date_of_birth`, `nationality`, `permanent_address`, `current_address`, `mobile`, `telephone`, `emergency_contact_name`, `emergency_contact_number`, `english_ability`, `minimum_entry_qualification`, `nic`, `passport`, `personal_email`, `bms_email`, `occupation`, `organization`, `previous_organization`, `qualifications`, `active`, `student_status`, `transfer_status`, `remark`, `entered_by`) VALUES
(1, '', 'Inushan ', 'Parameshwaran', 'Parameshwaran Inushan ', 'Parameshwaran Inushan ', '2000-10-16', 'Sri Lanka', '     ', 'No 100B Aberatna Mawatha  Boralesgamuwa  ', '772749757', '766410210', '', '', 1, 1, '200024003564', '', 'Inushan5@gmail.com', 'inushan.parameshwaran@bms.ac.lk', '', 'Wheelhouse Enterprises', '', '', 1, 'Active', 0, '', 'mirshath'),
(2, '', 'Velnithi ', 'Thaveeshan ', 'Velnithi Thaveeshan ', 'Velnithi Thaveeshan ', '1998-02-11', 'Sri Lanka', 'No 28, IBC road  Wellawatta  colombo  ', 'No 28, IBC road  Wellawatta  colombo  ', '707021198', '', '', '', 1, 1, '983070710v', '', 'vvthaveeshan@gmail.com', 'velnithi.thaveeshan@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(3, '', 'Omar', 'Mohamed ilyas', 'Omar Mohamed ilyas', 'Omar Mohamed ilyas', '2003-03-26', 'Sri Lanka', '58/1B,  Majeediya Estate,  Gothatuwa  Colombo  western province', '     ', '0764466020', '', '', '', 1, 1, '200308610436', '', 'ahmedilyas555@gmail.com', 'mohamed.ilyas@bms.ac.lk', 'Customer care service ', 'Startek', '', '', 1, 'Active', 0, '', 'mirshath'),
(4, '', 'Jawagar Jerad ', 'Gnanapragasam', 'Gnanapragasam Jawagar Jerad', 'Gnanapragasam Jawagar Jerad', '1998-04-17', 'Sri Lanka', '145/19.  K.B. Christy Perera Mawatha, Colombo 13.  Colombo 13. colombo  western ', '145/19.  K.B. Christy Perera Mawatha, Colombo 13.  Colombo 13. colombo  western ', '773898149', '773898149', '', '', 1, 1, '199810803571', '', 'Jawagarjerad@gmail.com', 'jawagar.jerad@bms.ac.lk', '', 'Mercantile Investment ', '', '', 1, 'Active', 0, '', 'mirshath'),
(5, '', 'Ijas', 'Ahamed', 'M.S. Ijas Ahamed', 'M.S. Ijas Ahamed', '1998-04-10', 'Sri Lanka', '148,Thayar Road,   Maduranchanai Street,   Pottuvil - 03  Pottuvil Ampara Eastern ', '148,Thayar Road,   Maduranchanai Street,   Pottuvil - 03  Pottuvil Ampara Eastern ', '0762901015', '', '', '', 1, 1, '199810102925', '', 'ijasahamed.ho@gmail.com', 'ijas.ahamed@bms.ac.lk', 'Digital marketing executive ', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(6, '', 'Nelith ', 'Samarajeewa', 'Nelith Ransana Samarajeewa', 'Nelith Ransana Samarajeewa', '2004-05-07', 'Sri Lanka', '41/28 a,  Epitamulla Road,  Pitakotte,  Kotte Colombo Western ', '41/28 a,  Epitamulla Road,  Pitakotte,  Kotte Colombo Western ', '757962201', '112867577', '', '', 1, 1, '200412801320', '', 'nelithsamarajeewa@gmail.com', 'nelith.samarajeewa@bms.ac.lk', 'Internal audit ', 'CDB Finance PLC', '', '', 1, 'Active', 0, '', 'mirshath'),
(7, '', 'Christy', 'Puvinayagam', 'Christy Shehan Puvinayagam', 'Christy Shehan Puvinayagam', '2000-01-26', 'Sri Lanka', 'B 20,  Samaranayake Housing,  Pitakotte,  Wellisara Gampaha Western ', 'IL Tower,   46/56,  Nwam Mawatha,  Colombo 02 Colombo Western ', '766412603', '112956421', '', '', 1, 1, '200002602965', '', 'shehanchristy78@gmail.com', 'christy.puvinayagam@bms.ac.lk', 'Retail sales specialist ', 'Advantis Express (Pvt) Ltd ', '', '', 1, 'Active', 0, '', 'mirshath'),
(8, '', 'Noori ', 'Aniff', 'Noori Najma Aniff', 'Noori Najma Aniff', '2000-11-10', 'Sri Lanka', 'No 493, Galle Road,  Kalutara South Kalutara  Kalutara Western ', 'No 493, Galle Road,  Kalutara South Kalutara  Kalutara Western ', '779255373', '712755777', '', '', 1, 1, '200081504274', '', 'najmagashiya@gmail.com', 'najma.aniff@bms.ac.lk', 'Banking ', 'Seylan bank PLC', '', '', 1, 'Active', 0, '', 'mirshath'),
(9, '', 'Devshreen', 'Devshreen', 'Lindamnlage Rashmika Devshreen Silva', 'Lindamnlage Rashmika Devshreen Silva', '1994-10-03', 'Sri Lanka', 'No 773,   3rd Lane,  Kadalana Moratuwa colombo Western ', 'No 773,   3rd Lane,  Kadalana Moratuwa colombo Western ', '719101003', '112653321', '', '', 1, 1, '942771037V', '', 'rashmika.net@gmail.com', 'rashmika.devshreen@bms.ac.lk', 'Senior marketing executive ', 'Mercantile Investment and Finance PLC ', '', '', 1, 'Active', 0, '', 'mirshath'),
(10, '', 'Anees', 'Shazfa ', 'Mohammed Anees Fathima Shazfa ', 'Mohammed Anees Fathima Shazfa ', '2004-12-12', 'Sri Lanka', 'E/21/A,  Ethnawala Road  Dummaladeniya, Warakapola Warakapola  Gampaha  Western ', 'E/21/A,  Ethnawala Road  Dummaladeniya, Warakapola Warakapola  Gampaha  Western ', '714149676', '', '', '', 1, 1, '200484701825', '', 'shazfaanees1212@gmail.com', 'shazfa.anees@bms.ac.lk', 'Audit trainee ', 'Institute of Charted Accountants of Sri Lanka ', '', '', 1, 'Active', 0, '', 'mirshath'),
(11, '', 'Keerthana', 'Muraleetharan', 'Keerthana Muraleetharan', 'Keerthana Muraleetharan', '2002-08-22', 'Sri Lanka', 'No 155/10,  Union Place  Colombo 02 Colombo  Colombo Western', 'No 155/10,  Union Place  Colombo 02 Colombo  Colombo Western', '772336575', '112302619', '', '', 1, 1, '200273503210', '', 'keerthanamuraleetharan@gmail.com', 'keerthana.muraleetharan@bms.ac.lk', 'Executive Assistant ', 'Commercial bank Ltd ', '', '', 1, 'Active', 0, '', 'mirshath'),
(12, '', 'Inthumathy', 'Devadas', 'Inthumathy Devadas', 'Inthumathy Devadas', '1998-05-02', 'Sri Lanka', 'No 16,  Kudaoya Bazaar,  Hatton Hatton  Hatton Central ', 'No 16,  Kudaoya Bazaar,  Hatton Hatton  Hatton Central ', '703841434', '512223430', '', '', 1, 1, '986233814V', '', 'd.inthumathiie@gmail.com', 'inthumathy.devadas@bms.ac.lk', 'Process Analyst ', 'Health Recon ', '', '', 1, 'Active', 0, '', 'mirshath'),
(13, '', 'Oshna', ' Udawellage ', 'Oshna Santhush Wassana Udawellage ', 'Oshna Santhush Wassana Udawellage ', '2000-06-25', 'Sri Lanka', '415/24/1/1,  High Level Road,  Delkanda, Nugegoda Nugegoda  Colombo Western', '415/24/1/1,  High Level Road,  Delkanda, Nugegoda Nugegoda  Colombo Western', '778710908', '', '', '', 1, 1, '200017700638', '', 'santushwassana@gmail.com', 'oshana.udawellage@bms.ac.lk', 'Customer service executive ', 'HSBC Bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(14, '', 'Nidushan ', 'Ravichandran', 'Nidushan Ravichandran', 'Nidushan Ravichandran', '2003-07-10', 'Sri Lanka', '65/34 A,   Weliamuna Road, Hendala, wattala Wattala Gampaha Western ', '65/34 A,   Weliamuna Road, Hendala, wattala Wattala Gampaha Western ', '763730476', '0777204504', '', '', 1, 1, '200319213962', '', 'nidushanravichandranhas16@gmail.com', 'nidushan.ravichandran@bms.ac.lk', '', 'Legacy health CCC', '', '', 1, 'Active', 0, '', 'mirshath'),
(15, '', 'Wasana', 'Perera ', 'U. Wasana Nilukshi Perera ', 'U. Wasana Nilukshi Perera ', '1995-01-09', 'Sri Lanka', 'No 195/B,  Morganwatta,  Uswetakeiyawa Ussatakeiyawa Gampaha Western ', 'No 195/B,  Morganwatta,  Uswetakeiyawa Ussatakeiyawa Gampaha Western ', '776875097', '', '', '', 1, 1, '955091345V', '', 'wasanaperera10@gmail.com', 'wasana.perera@bms.ac.lk', 'Customer service executive and Assistant manager ', 'HSBC - HDPL ', '', '', 1, 'Active', 0, '', 'mirshath'),
(16, '', 'Dilukshi ', 'Sinnaraj', 'Dilukshi Sinnaraj', 'Dilukshi Sinnaraj', '1998-08-27', 'Sri Lanka', 'No 32,   Poornawatha,   Kandy Kandy  Kandy Central', 'No 201/A,  Shurbbery garden, Colombo 04 Colombo Colombo Colombo', '763260146', '', '', '', 1, 1, '987402415V', '', 'wasanaperera10@gmail.com', 'dilukshi.sinnaraj@bms.ac.lk', 'Accountant ', 'Smart Drason Lanka (pvt) Ltd', '', '', 1, 'Active', 0, '', 'mirshath'),
(17, '', 'Malith ', 'Yogendra ', 'Malith Malan Yogendra', 'Malith Malan Yogendra', '2004-10-11', 'Sri Lanka', 'No. 368,  Kiriberiya Road,  Samagi Mawatha,  Eluvila, Panadura  colombo  western', 'No. 368,  Kiriberiya Road,  Samagi Mawatha,  Eluvila, Panadura  colombo  western', '752867399', '752867399', '', '', 1, 1, '200428502827', '', 'malithmalan2004@gmail.com', 'malith.yogendra@bms.ac.lk', 'Trainee Executive', 'Lanka Terms Manufacturing Pvt Ltd ', '', '', 1, 'Active', 0, '', 'mirshath'),
(18, '', 'Selvam Jerinson ', 'Jerinson ', 'Jerinson Selvam ', 'Jerinson Selvam ', '1998-08-13', 'Sri Lanka', 'A/4/4,  Siripala Bindu Niwasa,  Weragoda,  Wellampititya.  colombo  western', 'A/4/4,  Siripala Bindu Niwasa,  Weragoda,  Wellampititya.  colombo  western', '764874341', '764874341', '', '', 1, 1, '982262763V', '', 'jerinsonselvam1998@gmail.com', 'jerinson.jerinson@bms.ac.lk', 'Policy Servicing ', 'AIA Insurance', '', '', 1, 'Active', 0, '', 'mirshath'),
(19, '', ' Yesith ', 'Dissanayake', 'Kankanamalage Yesith Dilmin Dissanayake', 'Kankanamalage Yesith Dilmin Dissanayake', '1998-09-28', 'Sri Lanka', '247/6,   Bangalawaththa,  Sooriyapaluwa, Kadawatha Kadawatha Gampaha  Western', '247/6,   Bangalawaththa,  Sooriyapaluwa, Kadawatha Kadawatha Gampaha  Western', '713061816', '112974684', '', '', 1, 1, '199827201334', '', 'yesithdilmin@outlook.com', 'yesith.dissanayake@bms.ac.lk', 'Senior banking associate ', 'National development bank PLC ', '', '', 1, 'Active', 0, '', 'mirshath'),
(20, '', 'Rajendram', 'Medad', 'Rajendram Miskaan Medad', 'Rajendram Miskaan Medad', '1999-03-18', 'Sri Lanka', '206 Canal Road, Elakanda, Hendala, Wattala Wattala Gampaha  Western ', '206 Canal Road, Elakanda, Hendala, Wattala Wattala Gampaha  Western ', '752263870', '765395026', '', '', 1, 1, '199907810105', '', 'medad1999@gmail.com', 'miskaan.rajendram@bms.ac.lk', 'Analyst - talent Acquisition ', 'Ascent business solutions ', '', '', 1, 'Active', 0, '', 'mirshath'),
(21, '', 'Ulinduni ', 'Wickramasinghe', 'Dona Ulinduni Sasmitha Wickramasinghe', 'Dona Ulinduni Sasmitha Wickramasinghe', '1996-03-28', 'Sri Lanka', '116/6,    Geekiyanawattha,  Honnanththara South, Piliyndala  Piliyndala  Colombo  Western ', '116/6,    Geekiyanawattha,  Honnanththara South, Piliyndala  Piliyndala  Colombo  Western ', '778164403', '', '', '', 1, 1, '966490861V', '', 'sasmiwickramasinghe@gmail.com', 'sasmitha.wickramasinghe@bms.ac.lk', 'Senior banking assistant ', 'DFCC bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(22, '', ' Shanilka', 'Alkaduwa', 'Arambegedara Mudiyanselage Shanilka Rajindu Alkaduwa', 'Arambegedara Mudiyanselage Shanilka Rajindu Alkadu', '2004-12-08', 'Sri Lanka', 'No, 3/3 B,  Field Avenue,   Kohuwala, Nugegoda Nugegoda  Colombo  Western ', 'No, 3/3 B,  Field Avenue,   Kohuwala, Nugegoda Nugegoda  Colombo  Western ', '773914101', '', '', '', 1, 1, '200434303135', '', 'rajindualkaduwa@gmail.com', 'shanilka.alkaduwa@bms.ac.lk', 'Audit trainee ', 'MSK Associate ', '', '', 1, 'Active', 0, '', 'mirshath'),
(23, '', 'Fiyaz ', ' Marikkar', 'Fiyaz Marikkar', 'Fiyaz Marikkar', '2000-12-21', 'Sri Lanka', '31/1,   Paratha Road,  Kehelwatta, Panadura Panadura Colombo Western ', '217,  217, Stanley Thilakaratne Mw,  217, Stanley Thilakaratne Mw, Nugegoda Nugegoda Colombo Western ', '777037848', '', '', '', 1, 1, '200035603273', '', 'fiyazfirdouse@gmail.com', 'fiyaz.marikkar@bms.ac.lk', 'Own business ', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(24, '', 'Randula', ' Illangarathne', 'G.R.D. Randula Illangarathne', 'G.R.D. Randula Illangarathne', '1999-04-01', 'Sri Lanka', '42/1 A, Kottawa,  Pannipitiya Pannipitiya  Colombo  Western ', '42/1 A, Kottawa,  Pannipitiya Pannipitiya  Colombo  Western ', '719009082', '112782532', '', '', 1, 1, '995920166V', '', 'dasuniillangarathne@gmail.com', 'dasuni.illangarathne@bms.ac.lk', 'Junior Marketing Executive ', 'Information Institute of Technology ', '', '', 1, 'Active', 0, '', 'mirshath'),
(25, '', 'Shanmugapraba', 'Dhanushkaran ', 'Shanmugapraba Dhanushkaran ', 'Shanmugapraba Dhanushkaran ', '2002-12-02', 'Sri Lanka', 'No. 72/33,  Canal Road,  Hendala,  Wattala gampaha western', 'No. 72/33,  Canal Road,  Hendala,  Wattala gampaha western', '777064496', '777064496', '', '', 1, 1, '200233704531', '', 'dhanushkkaran72@gmail.com', 'shanmugapraba.dhanushkaran@bms.ac.lk', 'Process Associate', 'HCL Tech', '', '', 1, 'Active', 0, '', 'mirshath'),
(26, '', 'Lalithchandra ', 'Lalithchandra ', 'Sakaladhipathi Lalithchandra Lalithchandra ', 'Sakaladhipathi Lalithchandra Lalithchandra ', '1976-05-26', 'Sri Lanka', 'No 202/B/38,   The Village, Kudamaduwa,   Mattegoda Mattegoda  Colombo Western ', 'No 202/B/38,   The Village, Kudamaduwa,   Mattegoda Mattegoda  Colombo Western ', '716289298', '112782574', '', '', 1, 1, '761470213V', '', 'lalith.dharmapria@gmail.com', 'lalith.sakaladhipathi@bms.ac.lk', 'Banking ', 'Hatton National Bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(27, '', 'Tiyara', 'Emmanuel ', 'Emmanuel Tiyara Yoland ', 'Emmanuel Tiyara Yoland ', '2004-09-28', 'Sri Lanka', 'No1231A/  Ananda Mawatha,  Hunupitiya,   Wattala gampaha western', 'No1231A/  Ananda Mawatha,  Hunupitiya,   Wattala gampaha western', '761801660', '761801660', '', '', 1, 1, '200477200899', '', 'tiyaraemmanuel8@gmail.com', 'tiyara.emmanuel@bms.ac.lk', '', 'Royal Institute', '', '', 1, 'Active', 0, '', 'mirshath'),
(28, '', 'Imesha ', 'Kularathne ', 'P.I.L. Kularathne ', 'P.I.L. Kularathne ', '1999-10-27', 'Sri Lanka', 'No 161/1  Kalana building  Soysapura, Moratuwa  Moratuwa  Colombo  Western ', 'No 161/1  Kalana building  Soysapura, Moratuwa  Moratuwa  Colombo  Western ', '0775323622', '', '', '', 1, 1, '998015081V', '', 'imeshalakshani99@gmail.com', 'imesha.kularathne@bms.ac.lk', 'Senior banking associate ', 'NDB bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(29, '', 'Yasara', 'Sewmini ', 'Vidanapathiranage Yasara Sewmini ', 'Vidanapathiranage Yasara Sewmini ', '2001-06-06', 'Sri Lanka', '361/C/08,   Himbutana Rd,   Angoda Angoda  Colombo Western ', '361/C/08,   Himbutana Rd,   Angoda Angoda  Colombo Western ', '764395365', '771738830', '', '', 1, 1, '200165803699', '', 'yasarasewmini800@gmail.com', 'yasara.vidanapathiranage@bms.ac.lk', 'Accounts Executive (Pvt) Ltd', 'Comotronics Lanka ', '', '', 1, 'Active', 0, '', 'mirshath'),
(30, '', 'Anolia', 'Elangakoon', 'Anolia Mistrica Elangakoon', 'Anolia Mistrica Elangakoon', '1999-01-04', 'Sri Lanka', '353,  Carmel Lane,  Palliyawatta, Wattala Wattala Gampaha  Western ', '353,  Carmel Lane,  Palliyawatta, Wattala Wattala Gampaha  Western ', '757982421', '', '', '', 1, 1, '199950410878', '', 'mistricaanolia@gmail.com', 'anolia.elangokoon@bms.ac.lk', 'Executive operations ', 'Legacy health IIC ', '', '', 1, 'Active', 0, '', 'mirshath'),
(31, '', 'Sasiprabha', 'Madhubhani', 'Parana Polketiyage Sasiprabha Madhubhani', 'Parana Polketiyage Sasiprabha Madhubhani', '2002-10-27', 'Sri Lanka', 'Senasuma\',  Kalahe, Senasuma\', Kalahe, Wanchawala, Galle Galle  Galle  Southern ', 'Senasuma\',  Kalahe, Senasuma\', Kalahe, Wanchawala, Galle Galle  Galle  Southern ', '768149720', '763929103', '', '', 1, 1, '200180101374', '', 'sasiprabhamadu@gmail.com', 'sasiprabha.polketiyage@bms.ac.lk', 'Customer service Executive ', 'HSBC Electronic Data Processing Lanka Pvt Ltd ', '', '', 1, 'Active', 0, '', 'mirshath'),
(32, '', 'Gayan ', ' Wickramasinghe ', 'N.D. Gayan Wickramasinghe ', 'N.D. Gayan Wickramasinghe ', '1991-09-24', 'Sri Lanka', 'No 17, Gangula Mawatha,  Suwarapola, Piliyandala Piliyandala Colombo  Western ', 'No 17, Gangula Mawatha,  Suwarapola, Piliyandala Piliyandala Colombo  Western ', '766840035', '114922055', '', '', 1, 1, '912684040V', '', 'gayanwickramasinghe24@gmail.com', 'gayan.wickramasinghe@bms.ac.lk', 'Area sales manager ', 'ABANS ', '', '', 1, 'Active', 0, '', 'mirshath'),
(33, '', 'Anushi', 'Hakmanage', 'Anushi Prabashwari Hakmanage', 'Anushi Prabashwari Hakmanage', '1994-02-18', 'Sri Lanka', '76/3 B,   Shramadana Mawatha,  Pagoda Road, Nugogoda Nugegoda Colombo Western ', '76/3 B,   Shramadana Mawatha,  Pagoda Road, Nugogoda Nugegoda Colombo Western ', '777975208', '', '', '', 1, 1, '945490160V', '', 'anushihakmanage@gmail.com', 'anushi.hakmanage@bms.ac.lk', 'Financial Crime and Surveillance Operations - Anal', 'Standard Charted Bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(34, '', 'Premkumar ', 'Rishidaran ', 'Premkumar Rishidaran ', 'Premkumar Rishidaran ', '2001-12-01', 'Sri Lanka', '23,  Poonagala,   Bandarawela.  Badulla Uva', '23,  Poonagala,   Bandarawela.  Badulla Uva', '76603638', '766036382', '', '', 1, 1, '200134601495', '', 'rishidaran20011211@gmail.com', 'premkumar.rishidaran@bms.ac.lk', 'Assistant Accountant', 'Zyrex Pvt Ltd', '', '', 1, 'Active', 0, '', 'mirshath'),
(35, '', 'Rushen ', 'Ferdinando', 'K.P.M.V.D.R.E.N. Ferdinando', 'K.P.M.V.D.R.E.N. Ferdinando', '2001-01-31', 'Sri Lanka', 'No 09,   6th Lane,   Moratumulla, Moratuwa Moratuwa  Colombo  Western ', 'No 09,   6th Lane,   Moratumulla, Moratuwa Moratuwa  Colombo  Western ', '778119865', '112652905', '', '', 1, 1, '200103100640', '', 'rushenferdinando123@gmail.com', 'rushen.ferdinando@bms.ac.lk', 'Banking Associate ', 'HSBC bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(36, '', 'Muhammed ', ' Azeez', 'Muhammed Ifaad Azeez', 'Muhammed Ifaad Azeez', '1999-07-14', 'Sri Lanka', '140 B,  Isipathana Mawatha,   Colombo 5 Colombo  Colombo  Western ', '140 B,  Isipathana Mawatha,   Colombo 5 Colombo  Colombo  Western ', '779110543', '778166912', '', '', 1, 1, '991961593V', '', 'ifaadazeez@gmail.com', 'ifaad.azeez@bms.ac.lk', 'Banking ', 'Amana bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(37, '', 'Joel ', ' Stanislaus', 'Joel Oren Stanislaus', 'Joel Oren Stanislaus', '2003-03-14', 'Sri Lanka', 'No 149/15,  Modava Street,  Colombo 15 Colombo  Colombo Western ', 'No 54,  Station Road,   Wattala Wattala Gampaha  Western ', '714708392', '', '', '', 1, 1, '200307412867', '', 'stanislausjoel2003@gmail.com', 'joel.stanislaus@bms.ac.lk', 'Executive sales ', 'Standard chartered in a client center ', '', '', 1, 'Active', 0, '', 'mirshath'),
(38, '', 'Ravindran ', 'Thushanthan', 'Ravindran Thushanthan ', 'Ravindran Thushanthan ', '1999-09-26', 'Sri Lanka', '196 1/B,  Mattagoda Rd, Hendala, Wattala Wattala Gampaha  Western ', '196 1/B,  Mattagoda Rd, Hendala, Wattala Wattala Gampaha  Western ', '770882242', '', '', '', 1, 1, '992702451V', '', 'ThushanThanRavindran9@gmail.com', 'thushanthan.ravindran@bms.ac.lk', '', 'M.D Capital ', '', '', 1, 'Active', 0, '', 'mirshath'),
(39, '', 'Nabomani', 'Rupasinghe', 'Nabomani Shashipraba Rupasinghe', 'Nabomani Shashipraba Rupasinghe', '2000-08-08', 'Sri Lanka', '168,/2 a,  Makandana,   Madapatha Madapatha Colombo Western ', '168,/2 a,  Makandana,   Madapatha Madapatha Colombo Western ', '767849979', '767849979', '', '', 1, 1, '200072101843', '', 'shashi.nabo@gmail.com', 'nabomani.rupasinghe@bms.ac.lk', 'Fund admin supervisor ', 'HSBC bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(40, '', 'Gerald', 'Bastianpillai ', 'Bastiyanpillai Gerald Shavindra', 'Bastiyanpillai Gerald Shavindra', '2002-05-19', 'Sri Lanka', 'Seagull Court,  Initium Road, Dehiwala   Dehiwala  colombo  western', 'Seagull Court,  Initium Road, Dehiwala   Dehiwala  colombo  western', '761587359', '761587359', '', '', 1, 1, '200214000098', '', 'bastiampillaigerald@gmail.com', 'gerald.bastianpillai@bms.ac.lk', '', 'HCL Tech', '', '', 1, 'Active', 0, '', 'mirshath'),
(41, '', 'Sandamuthu', 'De Silva ', 'G W L Sandamuthu Dewdahara De Silva', 'G W L Sandamuthu Dewdahara De Silva', '2005-12-28', 'Sri Lanka', '23/9,  John Samuel Road,  Upper Indibedda  Moratuwa colombo  western', '23/9,  John Samuel Road,  Upper Indibedda  Moratuwa colombo  western', '778365179', '112652872', '', '', 1, 1, '200586303939', '', 'sandamuthudewdahara@gmail.com', 'sandamuthu.desilva@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(42, '', 'Dinesh Murugesam ', 'Murugesam Dinesh ', 'Murugesam Dinesh ', 'Murugesam Dinesh ', '1997-06-15', 'Sri Lanka', '     ', '49/4 A Baseline mawatha Dematagoda, 	 Colombo 09     ', '778945395', '', '', '', 1, 1, '971671955v', '', 'murugesamdinesh20@gmail.com', 'dinesh.murugesam@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(43, '', 'Vihini', 'Dissanayake', 'Vihini Dissanayake', 'Vihini Dissanayake', '2002-09-07', 'Sri Lanka', '706/A,  Kulasewana Road,  Kottawa  Colombo  western province', '     ', '0740485633', '', '', '', 1, 1, '200275100197', '', 'vihinidissanayake02@gmail.com', 'vihini.dissanayake@bms.ac.lk', 'FPS', 'Business Development Exports', '', '', 1, 'Active', 0, '', 'mirshath'),
(44, '', 'Dushintha', 'Kaleichelvan', 'Dushintha Kaleichelvan', 'Dushintha Kaleichelvan', '2003-07-04', 'Sri Lanka', 'Old Division,   Rokathenna,  Hali ela Hli ela  Badulla  Uva', '14, Subadharama Road, Dehiwala Dehiwala Colombo  Western ', '740362508', '', '', '', 1, 1, '200368600516', '', 'vadhushdhushintha@gmail.com', 'dushintha.kaleichelvan@bms.ac.lk', 'Executive', 'Legacy health', '', '', 1, 'Active', 0, '', 'mirshath'),
(45, '', 'Arshad ', 'Rilwan', 'Mohamed Arshad Rilwan', 'Mohamed Arshad Rilwan', '1995-11-30', 'Sri Lanka', '35/30, Fathima Graden,  Makola Makola  Gampaha  Western ', '35/30, Fathima Graden,  Makola Makola  Gampaha  Western ', '772256998', '', '', '', 1, 1, '953350688V', '', 'arshadrilwan4617@gmail.com', 'arshad.rilwan@bms.ac.lk', 'NS International ', 'NS International ', '', '', 1, 'Active', 0, '', 'mirshath'),
(46, '', 'Nuwhair', ' Nashith', 'Ahamed Nuwhair Mohamed Nashith', 'Ahamed Nuwhair Mohamed Nashith', '2003-10-19', 'Sri Lanka', '514 A,  Robert Place,  Dehiwala Dehiwala Colombo  Western ', '514 A,  Robert Place,  Dehiwala Dehiwala Colombo  Western ', '775056497', '', '', '', 1, 1, '200329313198', '', 'nuwhairnash7@gmail.com', 'nuwhair.nashith@bms.ac.lk', 'HCL Tech ', 'HCL Tech ', '', '', 1, 'Active', 0, '', 'mirshath'),
(47, '', 'Hafees', 'Akthar', 'Sithy Failoon Mohamed Hafees  Akthar', 'Sithy Failoon Mohamed Hafees  Akthar', '2003-09-18', 'Sri Lanka', '374 Osman Road,   Sainthamaruthu - 08  Sainthamaruthu  Ampara Eastern', '374 Osman Road,   Sainthamaruthu - 08  Sainthamaruthu  Ampara Eastern', '752829800', '762829103', '', '', 1, 1, '200326213091', '', 'hafeesakthar2003@gmail.com', 'hafees.akthar@bms.ac.lk', 'Nidha holy tours PVT LTD', 'Nidha holy tours PVT LTD', '', '', 1, 'Active', 0, '', 'mirshath'),
(48, '', 'Fathima', 'Zahariya', 'Fathima Zahariya', 'Fathima Zahariya', '1991-09-18', 'Sri Lanka', '111/25,  Vivekananda Hill,   Colombo - 13 Colombo Colombo Western', '111/25,  Vivekananda Hill,   Colombo - 13 Colombo Colombo Western', '752829800', '', '', '', 1, 1, '917623562V', '', 'zhryarhm@gmail.com', 'zahariya.raheem@bms.ac.lk', 'Art teacher ', 'Iman Academy ', '', '', 1, 'Active', 0, '', 'mirshath'),
(49, '', 'Ashani', 'Gunasekara', 'Ashani Gunasekara', 'Ashani Gunasekara', '2001-02-18', 'Sri Lanka', 'No 8/1   Godella, Dankotuwa Dankotuwa Gampaha  Western ', 'No 8/1   Godella, Dankotuwa Dankotuwa Gampaha  Western ', '778008797', '', '', '', 1, 1, '200154900697', '', 'ashaninimeshika2001@gmail.com', 'ashani.gunasekara@bms.ac.lk', 'Banking assistant ', 'Seylan bank PLC ', '', '', 1, 'Active', 0, '', 'mirshath'),
(50, '', 'Aynul ', 'Nasla ', 'M. N. Aynul Nasla ', 'M. N. Aynul Nasla ', '2003-07-01', 'Sri Lanka', 'No 15/2/4,   7c Road,  Kudabuthgamu, Angoda Angoda Colombo  Western ', '25 A,  Maradana Road,   Handala, Wattala Wattala Gampaha  Western ', '762810228', '112419884', '', '', 1, 1, '200368300250', '', 'aynnaz180@gmail.com', 'nasla.nilam@bms.ac.lk', 'Executive Operations ', 'Legacy health ', '', '', 1, 'Active', 0, '', 'mirshath'),
(51, '', 'Risini', 'Chanma', 'Ramanayake Arachchige Dona Risini Chanma', 'Ramanayake Arachchige Dona Risini Chanma', '2003-07-11', 'Sri Lanka', 'No 23/1,  Egodawatta RD,   Piriveba Junction, Boralesgamuwa Boralesgamuwa  Colombo Western ', 'No 23/1,  Egodawatta RD,   Piriveba Junction, Boralesgamuwa Boralesgamuwa  Colombo Western ', '757814033', '', '', '', 1, 1, '200369300883', '', 'risinichanma@gmail.com', 'risini.ramanayake@bms.ac.lk', 'Banking ', 'Commercial bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(52, '', 'Tharusha', 'Keragala', 'K. R. Tharusha Nuwantha Keragala', 'K. R. Tharusha Nuwantha Keragala', '1998-01-19', 'Sri Lanka', '84/1 B,   Malapalla,  Pannipitiya Pannipitiya Colombo  Western ', '84/1 B,   Malapalla,  Pannipitiya Pannipitiya Colombo  Western ', '773081991', '', '', '', 1, 1, '980194159V', '', 'tharusha01@gmail.com', 'tharusha.keragala@bms.ac.lk', 'Transnational Lanka Pvt Ltd ', 'Transnational Lanka Pvt Ltd ', '', '', 1, 'Active', 0, '', 'mirshath'),
(53, '', 'Livinda', 'Niranjan', 'Livinda Annette Niranjan', 'Livinda Annette Niranjan', '2002-03-13', 'Sri Lanka', '25/A,  Maradana Road,  Hendala,  Wattala Colombo  western province', '     ', '0786675533', '', '', '', 1, 1, '200257302125', '', 'livi.niranjan@gmail.com', 'annette.niranjan@bms.ac.lk', 'Data Entry Operator', 'HNB', '', '', 1, 'Active', 0, '', 'mirshath'),
(54, '', 'Rakshika ', 'Rubeshkumar ', 'Rakshika Rubeshkumar ', 'Rakshika Rubeshkumar ', '2005-01-31', 'Sri Lanka', '246/4,  Aluthmawaththa Road,   Colombo 15 colombo  western ', '246/4,  Aluthmawaththa Road,   Colombo 15 colombo  western ', '776674911', '', '', '', 1, 1, '200553102740', '', 'rakshikarubesh46@gmail.com', 'rakshika.rubeshkumar@bms.ac.lk', '', 'Legacy Health', '', '', 1, 'Active', 0, '', 'mirshath'),
(55, '', 'Kesha', 'Dahanayake ', 'Kesha Mariyata Dahanayake ', 'Kesha Mariyata Dahanayake ', '1994-01-22', 'Sri Lanka', 'No 172/E,   Sri Rahula Mawatha,   Katubedda, Moratuwa Moratuwa  Colombo  Western ', 'No 172/E,   Sri Rahula Mawatha,   Katubedda, Moratuwa Moratuwa  Colombo  Western ', '779204693', '', '', '', 1, 1, '945220589V', '', 'keshamariyata4k@gmail.com', 'kesha.dahanayake@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(56, '', 'Priyadharshini', 'Vivekananthan ', 'Priyadharshini Vivekananthan ', 'Priyadharshini Vivekananthan ', '2000-10-24', 'Sri Lanka', '10/23A,  Sri Kalyani Gangarama Mawatha,  Mattakuliya, Colombo 15 Colombo western province', '     ', '0758103693', '0774844818', '', '', 1, 1, '200079804036', '', 'vivekananthanpriyadharshini24@gmail.com', 'priyadharshini.vivekananthan@bms.ac.lk', 'Data Entry Operator', 'HNB', '', '', 1, 'Active', 0, '', 'mirshath'),
(57, '', 'Velusamy', 'Krishanthan ', 'Velusamy Krishanthan ', 'Velusamy Krishanthan ', '1995-01-30', 'Sri Lanka', 'No 46 A,  1/2 New Mosque Road,  Maha Heenatiyangala, Kalutara Kalutara Kalutara Southern ', 'No 46 A,  1/2 New Mosque Road,  Maha Heenatiyangala, Kalutara Kalutara Kalutara Southern ', '760717625', '', '', '', 1, 1, '950300329V', '', 'vkisanthan.95@gmail.com', 'krishanthan.velusamy@bms.ac.lk', 'Medical representative ', 'Emerchemie NB Ceylon ', '', '', 1, 'Active', 0, '', 'mirshath'),
(58, '', 'Krushmica', 'Selvarajah ', 'Selvarajah Krushmica', 'Selvarajah Krushmica', '2003-09-07', 'Sri Lanka', '76/1,  Chillaw Road,  Kattuwa,   Negombo gampaha western', '76/1,  Chillaw Road,  Kattuwa,   Negombo gampaha western', '755617149', '755617149', '', '', 1, 1, '200311753030', '', 'krushmiselva@gmail.com', 'krushmica.selvarajah@bms.ac.lk', 'Senior Executive Relationship Manager', 'Inspirex', '', '', 1, 'Active', 0, '', 'mirshath'),
(59, '', 'Yogeswaran ', 'Vidushika ', 'Yogeswaran Vidushika ', 'Yogeswaran Vidushika ', '2003-06-01', 'Sri Lanka', '1/3B 23,  Ekamuthupura Mattakkuliya,   Colombo 15 colombo  western', '1/3B 23,  Ekamuthupura Mattakkuliya,   Colombo 15 colombo  western', '753651977', '753651977', '', '', 1, 1, '200365312872', '', 'vidushikanisha@gmail.com', 'vidushika.yogeswaran@bms.ac.lk', '', 'Legacy Health', '', '', 1, 'Active', 0, '', 'mirshath'),
(60, '', 'Gayanee', 'Rajapaksha', 'Rajapaksha Mudiyanselage Gayanee Nimesha', 'Rajapaksha Mudiyanselage Gayanee Nimesha', '2000-10-08', 'Sri Lanka', 'Elder House Road,  Yaddigama, Koonwewa, Maho  Koonwewa,   Maho Kurunegala North Western Province', 'Elder House Road,  Yaddigama, Koonwewa, Maho  Koonwewa,   Maho Kurunegala North Western Province', '772378263', '117450650', '', '', 1, 1, '200078202397', '', 'gayani.rajapaksha2000@gmail.com', 'gayanee.nimesha@bms.ac.lk', 'Officer - Operation', 'VFS Global', '', '', 1, 'Active', 0, '', 'mirshath'),
(61, '', 'Keshari ', 'Samararathne', 'Keshari Mithabhani Samararathne', 'Keshari Mithabhani Samararathne', '1999-01-05', 'Sri Lanka', '336/1, Sirimangala Watte rd,  Mampe, Piliyandala Piliyandala Colombo  Western ', '336/1, Sirimangala Watte rd,  Mampe, Piliyandala Piliyandala Colombo  Western ', '769731070', '112708160', '', '', 1, 1, '199950510253', '', 'kesharisamararathne1234@gmail.com', 'keshari.samararathne@bms.ac.lk', 'Embellishmmet Development Executive ', 'Hirdaramani center of operation ', '', '', 1, 'Active', 0, '', 'mirshath'),
(62, '', 'Yasas', 'Wickramasekara ', 'Yasas Deshan Wickramasekara ', 'Yasas Deshan Wickramasekara ', '2002-12-04', 'Sri Lanka', '325/12/66,  Kurulu Uyana, Pelanwatta, Pannipitiya colombo  western', '325/12/66,  Kurulu Uyana, Pelanwatta, Pannipitiya colombo  western', '763682284', '112837141', '', '', 1, 1, '200233902742', '', 'yasasdeshan357@gmail.com', 'yasas.wickramasekara@bms.ac.lk', 'Assistant - Compliance and Verification', 'NTB', '', '', 1, 'Active', 0, '', 'mirshath'),
(63, '', 'Dinethma', 'Perera', 'Koratota Liyanage Dinethma Hasandi Perera', 'Koratota Liyanage Dinethma Hasandi Perera', '2003-09-29', 'Sri Lanka', 'No.32, Wellahena III Lane, Welisara, Ragama  Wellahena III Lane,  Welisara,  Ragama  gampaha western', 'No.32, Wellahena III Lane, Welisara, Ragama  Wellahena III Lane,  Welisara,  Ragama  gampaha western', '760010268', '112951342', '', '', 1, 1, '200377300438', '', 'dinethmaperera29@gmail.com', 'dinethma.perera@bms.ac.lk', 'Accountant', 'Kumaraperu Investment & Pvt Ltd', '', '', 1, 'Active', 0, '', 'mirshath'),
(64, '', 'Maheshi', 'Wanni Arachchige', 'Wanni Arachchige Maheshi Kaushalya', 'Wanni Arachchige Maheshi Kaushalya', '1996-05-26', 'Sri Lanka', '314/D,  Old Kandy Road,  Dalugama,  Kelaniya Colombo  western province', '314/D,  Old Kandy Road,  Dalugama,  Kelaniya Colombo  western province', '0774506746', '', '', '', 1, 1, '966472758V', '', 'maheshikaushalya2@gmail.com', 'maheshi.kaushalya@bms.ac.lk', 'Finance Management Trainee', 'Crest Container Lines USA', '', '', 1, 'Active', 0, '', 'mirshath'),
(65, '', 'Munugoda Hewage Senuri Nimeshika', 'Senuri Nimeshika', 'Munugoda Hewage Senuri Nimeshika', 'Munugoda Hewage Senuri Nimeshika', '2003-01-06', 'Sri Lanka', 'No. 27/5,  Sudarmarathnarama lane,  Lower Indibedda,  Moratuwa Colombo Western Province', 'No. 27/5,  Sudarmarathnarama lane,  Lower Indibedda,  Moratuwa Colombo Western Province', '713119195', '', '', '', 1, 1, '200350603230', '', 'Nimeshikasenuri@gmail.com', 'senuri.nimeshika@bms.ac.lk', '', 'NationsTrust Bank', '', '', 1, 'Active', 0, '', 'mirshath'),
(66, '', 'Thisara', 'Abeysinghe', 'A.M.T.L.B. Abeysinghe', 'A.M.T.L.B. Abeysinghe', '2002-10-09', 'Sri Lanka', '194/C/3,  Thewaththa Road,  Weniwelkola, Gonapola  Junction  Colombo Colombo Western ', '194/C/3,  Thewaththa Road,  Weniwelkola, Gonapola  Junction  Colombo Colombo Western ', '778496048', '713684507', '', '', 1, 1, '200228302609', '', 'thisaralakshantc@gmail.com', 'thisara.abeysinghe@bms.ac.lk', 'Customer service executive ', 'HSBC Global services ', '', '', 1, 'Active', 0, '', 'mirshath'),
(67, '', 'Dineth', 'Nandul', 'Lokuge Dineth Nandul', 'Lokuge Dineth Nandul', '2002-07-12', 'Sri Lanka', 'Gangarama road,   Werahera , Boralasgamuwa Boralesgamuwa Colombo  Western ', '40/4,  pieris Mawatha,     ', '715849415', '112509814', '', '', 1, 1, '200219401390', '', 'dinethirthiq@gmail.com', 'dineth.lokuge@bms.ac.lk', 'Banking ', 'Nations trust bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(68, '', 'Irukshi', ' Fernando', 'Mahathom Melage Anne Irukshi Kaushalya Fernando', 'Mahathom Melage Anne Irukshi Kaushalya Fernando', '2002-10-04', 'Sri Lanka', '54/12,  Galpoththa Road,   Kadalana, Moratuwa Moratuwa  Colombo Western ', '54/12,  Galpoththa Road,   Kadalana, Moratuwa Moratuwa  Colombo Western ', '777679930', '', '', '', 1, 1, '200277803584', '', 'fernandoiru00@gmail.com', 'irukshi.fernando@bms.ac.lk', 'Relationship assistant ', 'Nations Trust Bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(69, '', 'Pavithra ', 'Ramasamy', 'Ramasamy Pavithra', 'Ramasamy Pavithra', '1999-10-27', 'Sri Lanka', 'Upcot Road,  Brunswick,   Maskeliya  Nuwara Eliya Central', 'A1F 48,  Sri Sumanatissa Mawatha,   Colombo 12 colombo  western', '778754004', '778754004', '', '', 1, 1, '998017106V', '', 'pavhiramasamynarayanan27@gmail.com', 'pavithra.ramasamy@bms.ac.lk', 'Customer Engagement Associate ', 'HNB', '', '', 1, 'Active', 0, '', 'mirshath'),
(70, '', 'Amara ', 'Basheer ', 'Amara Valdez Basheer ', 'Amara Valdez Basheer ', '1999-01-25', 'Sri Lanka', '26/1,   5th Lane,   Ratmalana colombo  western', '26/1,   5th Lane,   Ratmalana colombo  western', '765373653', '765373653', '', '', 1, 1, 'P9928312A', '', 'amaravbasheer@gmail.com', 'amara.basheer@bms.ac.lk', 'Marketing Executive', 'Bizy Corp', '', '', 1, 'Active', 0, '', 'mirshath'),
(71, '', 'Lahiru', 'Karunaratne', 'Vidanage Charith Lahiru Kaushal Karunaratne', 'Vidanage Charith Lahiru Kaushal Karunaratne', '1999-10-25', 'Sri Lanka', '67,  Rawathawatte Road,  Moratuwa Moratuwa Colombo  Western ', '67,  Rawathawatte Road,  Moratuwa Moratuwa Colombo  Western ', '714018087', '112655500', '', '', 1, 1, '199929902646', '', 'charithkaushal48@gmail.com', 'kaushal.karunaratne@bms.ac.lk', 'Junior executive ', 'Sampath bank PLC ', '', '', 1, 'Active', 0, '', 'mirshath'),
(72, '', 'Shehani', 'Mallawa Thanthrige Don ', 'Shehani Prasanthika Mallawa Thanthrige Don ', 'Shehani Prasanthika Mallawa Thanthrige Don ', '1991-10-04', 'Sri Lanka', 'No 84, Gongithota,  Wattala Wattala Gampaha  Western ', 'No 84, Gongithota,  Wattala Wattala Gampaha  Western ', '775689409', '112935014', '', '', 1, 1, '967783293V', '', 'shehaniprasanthika@gmail.com', 'shehani.mallawa@bms.ac.lk', 'Team lead', 'Loyalty Accounting Solutions ', '', '', 1, 'Active', 0, '', 'mirshath'),
(73, '', 'Kusal ', 'Wickramarathna', 'Kusal Udana Wickramarathna', 'Kusal Udana Wickramarathna', '1999-03-21', 'Sri Lanka', '91/47-C,  , Watarappala Road, Mount Lavinia Mount Lavinia  Colombo  Western ', '91/47-C,  Watarappala Road, Mount Lavinia Mount Lavinia  Colombo  Western ', '767416899', '', '', '', 1, 1, '199408102330', '', 'kusaluw@gmail.com', 'kusal.wickramarathna@bms.ac.lk', 'Executive operations ', 'Avant garde maritime  services Pvt Ltd ', '', '', 1, 'Active', 0, '', 'mirshath'),
(74, '', 'Githmi', 'Ranawaka', 'Githmi Kaweesha Ranawaka Arachchige Dona', 'Githmi Kaweesha Ranawaka Arachchige Dona', '1999-11-16', 'Sri Lanka', 'No 88,  Balagalla,  Divulapitiya Divulapitiya Gampaha  Western ', 'No 88,  Balagalla,  Divulapitiya Divulapitiya Gampaha  Western ', '776608752', '774010843', '', '', 1, 1, '199982111258', '', 'githmikaweesha@gmail.com', 'githmi.ranawaka@bms.ac.lk', 'Recovery documentation officer ', 'Vallibel Finance ', '', '', 1, 'Active', 0, '', 'mirshath'),
(75, '', 'Salmaan', 'Salmaan', 'Mohammed Salmaan Hamid', 'Mohammed Salmaan Hamid', '1999-10-30', 'Sri Lanka', '124/2A,   Galvihara Road,   Dehiwala Dehiwala Colombo  Western ', '124/2A,   Galvihara Road,   Dehiwala Dehiwala Colombo  Western ', '720701010', '', '', '', 1, 1, '199930410706', '', 'salmaanhamid07@gmail.com', 'salmaan.hamid@bms.ac.lk', 'Senior associate sales', 'Muve Colombo ', '', '', 1, 'Active', 0, '', 'mirshath'),
(76, '', 'Methma ', 'Sooriyapperuma', 'Methma Sooriyapperuma', 'Methma Sooriyapperuma', '2002-12-18', 'Sri Lanka', 'Siriwedo niwasa Road,  Kirimetiyana East,  Lunuwila Linuwila Puttalam  North western ', 'No 400/19,  Thalawewa Road,   Kirimetiyana East, Lunuwila Lunuwila  Puttalam  North western ', '743427778', '', '', '', 1, 1, '200285303646', '', 'methmaoshadi@gmail.com', 'methma.sooriyapperuma@bms.ac.lk', 'Senior banking associate ', 'NDB Bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(77, '', 'Joshua ', 'Milhuisen ', 'Joshua Daniel Sean Milhuisen ', 'Joshua Daniel Sean Milhuisen ', '2004-12-11', 'Sri Lanka', 'No. 15,  Prathibimbarama Road,   Kalubowila  colombo  western', 'No. 15,  Prathibimbarama Road,   Kalubowila  colombo  western', '760929421', '760929421', '', '', 1, 1, '200432302261', '', 'jdmilhuisen679@gmail.com', 'joshua.milhuisen@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(78, '', 'Ruvishka', 'Silva ', 'Kirinde Liyanage Ruvishka Shemindra Silva ', 'Kirinde Liyanage Ruvishka Shemindra Silva ', '2003-06-22', 'Sri Lanka', '8/1 A  Mahajana Road,  Kadalana,  Moratuwa  colombo  western', '8/1 A  Mahajana Road,  Kadalana,  Moratuwa  colombo  western', '713599194', '112653498', '', '', 1, 1, '200317400709', '', 'rshemindra22@gmail.com', 'ruvishka.silva@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(79, '', 'Tufail ', 'Batcha', 'Mohamed Tufail Batcha', 'Mohamed Tufail Batcha', '1998-06-29', 'Sri Lanka', '1A/13,  Dewata Road,   Kaldemulla,  Moratuwa. colombo  western', '1A/13,  Dewata Road,   Kaldemulla,  Moratuwa. colombo  western', '778304614', '778304614', '', '', 1, 1, '199818103030', '', 'tufailbatcha@gmail.com', 'tufail.mohamed@bms.ac.lk', 'Senior Executive', 'Sea Freight LCL Operations', '', '', 1, 'Active', 0, '', 'mirshath'),
(80, '', 'Imalka ', 'Wickramaratne', 'Imalka Wickramaratne', 'Imalka Wickramaratne', '1999-05-02', 'Sri Lanka', 'No 42/1C,  Jaya Mawatha Road,  Mampe, Piliyandala Piliyandala Colombo  Western ', 'No 42/1C,  Jaya Mawatha Road,  Mampe, Piliyandala Piliyandala Colombo  Western ', '773690391', '727741214', '', '', 1, 1, '199903610181', '', 'imalkasaga123@gmail.com', 'imalka.wickramaratne@bms.ac.lk', 'Director/Operations ', 'Flexo International Pvt Ltd ', '', '', 1, 'Active', 0, '', 'mirshath'),
(81, '', 'Zuabir ', 'Sherazadh ', 'Zuabir Fathima Sherazadh ', 'Zuabir Fathima Sherazadh ', '1992-06-22', 'Sri Lanka', 'No 6/41,   Elvitigala Mawatha,  Colombo 08 Colombo Colombo  Western ', 'No 6/41,   Elvitigala Mawatha,  Colombo 08 Colombo Colombo  Western ', '0773041974', '112696671', '', '', 1, 1, '199267402531', '', 'shera.hilmy@gmail.com', 'sherazadh.zuabir@bms.ac.lk', 'Admin/Operation coordinator ', 'Santhi Maargam ', '', '', 1, 'Active', 0, '', 'mirshath'),
(82, '', 'Janani ', 'Pillayi ', 'Yoharajan Pillayi Janani Vamini ', 'Yoharajan Pillayi Janani Vamini ', '2003-08-11', 'Sri Lanka', '895/1,  Aluthmawatha Road,   Colombo 15 colombo  western', '87/16 B,  Walls Lane,   Colombo 15 colombo  western ', '761624418', '761624418', '', '', 1, 1, '200372412576', '', 'jananivamini11@gmail.com', 'janani.vamini@bms.ac.lk', '', 'Legacy Health', '', '', 1, 'Active', 0, '', 'mirshath'),
(83, '', 'Sandeepa ', 'Vihangi ', 'Balasooriya Appuhamilage Sandeepa Vihangi', 'Balasooriya Appuhamilage Sandeepa Vihangi', '2001-02-25', 'Sri Lanka', '22/D,  New Hospital Road,  Pamunuwa,  Maharagama colombo  western ', '22/D,  New Hospital Road,  Pamunuwa,  Maharagama colombo  western ', '776208592', '112837033', '', '', 1, 1, '200155601233', '', 'sandeepavihangi@gmail.com', 'sandeepa.vihangi@bms.ac.lk', '', 'HSBC', '', '', 1, 'Active', 0, '', 'mirshath'),
(84, '', 'Shamritha', 'Balasubramaniam ', 'Balasubramaniam Shamritha', 'Balasubramaniam Shamritha', '2004-08-05', 'Sri Lanka', 'No- 41 1/3,  Golden Tower,  Maha Vidyalaya Mawatha,  Colombo 13 colombo  western', 'No- 41 1/3,  Golden Tower,  Maha Vidyalaya Mawatha,  Colombo 13 colombo  western', '778802213', '778802213', '', '', 1, 1, '200471802382', '', 'bshamritha@gmail.com', 'shamritha.balasubramaniam@bms.ac.lk', 'Accountant', 'ABN Electricals ', '', '', 1, 'Active', 0, '', 'mirshath'),
(85, '', 'Thilini', 'De Silva', 'Thilini De Silva', 'Thilini De Silva', '1999-07-11', 'Sri Lanka', 'No 24/4,  Diddeniya,  Hanwella Hanwella  Colombo  Western ', 'No 24/4,  Diddeniya,  Hanwella Hanwella  Colombo  Western ', '761531807', '779618026', '', '', 1, 1, '199981211330', '', 'ttudayanganisilva@gmail.com', 'thilini.desilva@bms.ac.lk', 'Banking assistant ', 'Nations trust bank ', '', '', 1, 'Active', 0, '', 'mirshath'),
(86, '', ' Sachini', 'Dissanayake', 'Udawela Lekamlage Sachini Dewmini Dissanayake', 'Udawela Lekamlage Sachini Dewmini Dissanayake', '2003-06-23', 'Sri Lanka', '173/5/14,  Chamara Sewana Mawatha,  Mirisawatte,  Mudungoda gampaha western ', '246/4,  Aluthmawaththa Road,   Colombo 15 colombo  western ', '704490725', '770455585', '', '', 1, 1, '200367510675', '', 'sachiidewmi@gmail.com', 'sachini.dewmini@bms.ac.lk', '', 'HSBC', '', '', 1, 'Active', 0, '', 'mirshath'),
(87, '', 'Aanjalee ', 'Perera', 'Aanjalee Menasha Dilrukshi Perera', 'Aanjalee Menasha Dilrukshi Perera', '2002-12-28', 'Sri Lanka', 'No 57,   Mahavidhana lane,  koralawella, Moratuwa Moratuwa  Colombo  Western ', 'No 57,   Mahavidhana lane,  koralawella, Moratuwa Moratuwa  Colombo  Western ', '719330852', '777725077', '', '', 1, 1, '200286302610', '', 'anjvee20@gmail.com', 'aanjalee.perera@bms.ac.lk', 'Banking ', 'HSBC', '', '', 1, 'Active', 0, '', 'mirshath'),
(88, '', 'Dilki', 'Fernandopulle ', 'Dilki Shevoni Fernando Pulle', 'Dilki Shevoni Fernando Pulle', '2001-03-19', 'Sri Lanka', 'No. 142/8,  Negombo Road,   Dankotuwa Puttalam North Western Province', 'No. 142/8,  Negombo Road,   Dankotuwa Puttalam North Western Province', '740962625', '740962625', '', '', 1, 1, '200157900852', '', 'dilkishevoni@gmail.com', 'dilki.fernandopulle@bms.ac.lk', 'Trainee Banking Assistant', 'NTB', '', '', 1, 'Active', 0, '', 'mirshath'),
(89, '', 'Nagendran', 'Rakavina ', 'Nagendran Rakavina ', 'Nagendran Rakavina ', '2002-09-10', 'Sri Lanka', '360/9E,  Freedom Lane,  Aluthmawatha Road,  Colombo 15 colombo  western', '360/9E,  Freedom Lane,  Aluthmawatha Road,  Colombo 15 colombo  western', '762313464', '762313464', '', '', 1, 1, '200275404750', '', 'rakaveenanagendran@gmail.com', 'rakavina.nagendran@bms.ac.lk', 'Hirdarmani Group', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(90, '', 'Krishani ', 'Charlet', 'Francis Krishani Charlet', 'Francis Krishani Charlet', '1997-03-11', 'Sri Lanka', '243/3, Samanalathenna Mw,  Ratnapura Rathnapura  Rathnapura Sabaragamuwa ', 'Hettiyawatta,  Kotahena,  Colombo 13 Colombo  Colombo  Western ', '704123797', '', '', '', 1, 1, '978080480V', '', 'Krishanifrancis643@gmail.com', 'krishani.francis@bms.ac.lk', 'Senior banking assistant ', 'NTB ', '', '', 1, 'Active', 0, '', 'mirshath'),
(91, '', 'Dineshi ', 'Dharmadasa', 'Mahadewa Pathirannehelage Dineshi Dharmadasa', 'Mahadewa Pathirannehelage Dineshi Dharmadasa', '1991-07-02', 'Sri Lanka', 'No 363/6,  Lunugama,  Gampaha Gampaha  Gampaha  Western ', 'No 363/6,  Lunugama,  Gampaha Gampaha  Gampaha  Western ', '771759349', '112405495', '', '', 1, 1, '915380891V', '', 'dineshipdnew@gmail.com', 'dineshi.mahadewa@bms.ac.lk', 'Quality coordinator ', 'MAS Linea Aqua ', '', '', 1, 'Active', 0, '', 'mirshath'),
(92, '', 'Thagshini', 'Dhayabaran', 'Thagshini Dhayabaran', 'Thagshini Dhayabaran', '1997-05-11', 'Sri Lanka', '54 CG 02,  Chithra Lane, Narahenpita,  Colombo 05 Narahenpita  Colombo  western ', '54 CG 02,  Chithra Lane, Narahenpita,  Colombo 05 Narahenpita  Colombo  western ', '704683366', '', '', '', 1, 1, '199781000910', '', 'thagshinidhayabaran@gmail.com', 'thagshini.dhayabaran@bms.ac.lk', 'Senior operation executive ', 'Legacy health care Pvt Ltd ', '', '', 1, 'Active', 0, '', 'mirshath'),
(93, '', 'Ayesha ', 'Maddumage ', 'Edissuriya Maddumage Ayesha ', 'Edissuriya Maddumage Ayesha ', '1994-03-16', 'Sri Lanka', 'No 548/2,  Walawewaththa,  Ambalantota Ambalantota  Ambalantota  Southern ', 'No 56/36,  Mahawaththa Road,  Borella Road, Pannipitiya Pannipitiya Colombo  Western ', '764100314', '', '', '', 1, 1, '945750472V', '', 'maddumageayesha@gmail.com', 'ayesha.maddumage@bms.ac.lk', 'Senior executive ', 'Student enrolment ', '', '', 1, 'Active', 0, '', 'mirshath'),
(94, '', 'Mohamed Nadir ', 'Ali ', 'Mohamed Nadir Ali ', 'Mohamed Nadir Ali ', '2002-11-11', 'Sri Lanka', '310/C/1/A,  Meethotamulla Road,   Wellampitiya  colombo  western', '310/C/1/A,  Meethotamulla Road,   Wellampitiya  colombo  western', '760404727', '760404727', '', '', 1, 1, '200231604219', '', 'mohnadirali06@gmail.com', 'nadir.ali@bms.ac.lk', '', 'heritage Teas Pvt Ltd', '', '', 1, 'Active', 0, '', 'mirshath'),
(95, '', 'Vidurshan', 'Rajeshkanna', 'Rajeshkanna Vidurshan', 'Rajeshkanna Vidurshan', '2004-06-17', 'Sri Lanka', '46, 5B Modara Street, Colombo 15     ', '     ', '0777555310', '0757021693', '', '', 1, 1, '200416902141', '', 'exam1701@gmail.com', 'vidurshan.rajeshkanna@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(96, '', 'Mohammad ', 'Imran ', 'Mohamed Ikram Mohammad Imran', 'Mohamed Ikram Mohammad Imran', '2004-01-07', 'Sri Lanka', '513/7/1/1  Avissawella Road,   Wellampitiya colombo  western', 'S13/7/1/1  Avissawella Road,   Wellampitiya colombo  western', '771534484', '771534484', '', '', 1, 1, '200400700341', '', 'mohommedimran3@gmail.com', 'mohammad.imran@bms.ac.lk', 'Owner', 'I M Lanka Trading Pvt Ltd', '', '', 1, 'Active', 0, '', 'mirshath'),
(97, '', 'Mohammed Zaheem ', 'Fawzur Rahman ', 'Fawzur Rahman Mohammed Zaheem ', 'Fawzur Rahman Mohammed Zaheem ', '2005-01-14', 'Sri Lanka', '333/D,  Mahabuthgamuwa,   Kotikawathe  colombo  western', '333/D,  Mahabuthgamuwa,   Kotikawathe  colombo  western', '769876380', '769876380', '', '', 1, 1, '200501403746', '', 'mhmmdzhm1@gmail.com', 'mohammed.zaheem@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(98, '', 'Mohommad Shakir ', ' Kamaldeen  ', 'Mohommad Shakir Kamaldeen  ', 'Mohommad Shakir Kamaldeen  ', '2003-05-13', 'Sri Lanka', '220/20/2/C, Bandaranayake Mawatha, Hunupitiya,  Wattala  gampaha western', '254/B,  Vihara Mawatha,  Hunupitiya,  Wattala  gampaha western', '752272682', '752272682', '', '', 1, 1, '200313410711', '', 'shakirkamaldeen1215@gmail.com', 'mohammad.shakir@bms.ac.lk', 'Executive operations', 'Legacy Health', '', '', 1, 'Active', 0, '', 'mirshath'),
(99, '', 'Tilshani', 'Sheriffdeen', 'Tilshani Sheriffdeen', 'Tilshani Sheriffdeen', '2003-09-25', 'Sri Lanka', '78/6,  Mahalwarawa Rd,  Pannipitiya Pannipitiya  Colombo  Western ', '78/6,  Mahalwarawa Rd,  Pannipitiya Pannipitiya  Colombo  Western ', '778329599', '112841705', '', '', 1, 1, '200376900266', '', 'tarasheriffdeen@gmail.com', 'tilshani.sheriffdeen@bms.ac.lk', 'Junior executive ', 'Softlogic life ', '', '', 1, 'Active', 0, '', 'mirshath'),
(100, '', 'Shathath', 'Shanfer ', 'Mohamed Shathath Ahamed Shanfer', 'Mohamed Shathath Ahamed Shanfer', '2004-08-13', 'Sri Lanka', 'No. 338,  Kadawatha road,   Dehiwala colombo  western', 'No. 338,  Kadawatha road,   Dehiwala colombo  western', '761500990', '756775220', '', '', 1, 1, '200422600334', '', 'Ahamedshanfer75@gmail.com', 'shanfer.ahamed@bms.ac.lk', 'Trainee', 'Legacy Health', '', '', 1, 'Active', 0, '', 'mirshath'),
(101, '', 'Mohamed', 'Umar', 'Fouzul Ameer Mohamed Umar', 'Fouzul Ameer Mohamed Umar', '2001-04-18', 'Sri Lanka', '20/28, Kattiawatta Road,   Mabola, Wattala Wattala  Gampaha Western ', '20/28, Kattiawatta Road,   Mabola, Wattala Wattala  Gampaha Western ', '720664055', '721114109', '', '', 1, 1, '200112904290', '', 'umarameer664@gmail.com', 'umar.ameer@bms.ac.lk', 'Team leader operations ', 'BPO', '', '', 1, 'Active', 0, '', 'mirshath'),
(102, '', 'Savanthi', 'Lenora ', 'Savanthi Shashikala Lenora', 'Savanthi Shashikala Lenora', '2001-01-26', 'Sri Lanka', '     ', '     ', '711732170', '114682682', '', '', 1, 1, '200152602350', '', 'SAVANTHILENORA320@GMAIL.COM', 'savanthi.lenora@bms.ac.lk', '', 'NTB', '', '', 1, 'Active', 0, '', 'mirshath'),
(103, '', 'Nethmi ', 'De Silva', 'S.P.N.Parindya De Silva', 'S.P.N.Parindya De Silva', '2003-06-24', 'Sri Lanka', 'No. 17/E,  6th lane,  Pagoda Road,  Nugegoda  colombo  western', 'No. 17/E,  6th lane,  Pagoda Road,  Nugegoda  colombo  western', '763269869', '763269869', '', '', 1, 1, '200367612223', '', 'desilvanethmi24@gmail.com', 'nethmi.parindya@bms.ac.lk', '', '', '', '', 1, 'Active', 0, '', 'mirshath'),
(104, '', 'Fazeem', 'Rizmie', 'Fazeem Rizmie', 'Fazeem Rizmie', '1999-06-28', 'Sri Lanka', '215,  Mohideen Masjid Road,  Maradana,  Colombo 10 Colombo Western', '62,  Welewatta Road,   Wellampitiya  ', '752221231', '761871096', '', '', 1, 1, '991801677V', '', 'imfazee7@gmail.com', 'fazeem.rizmie@bms.ac.lk', 'Senior Marketing Executive ', 'Union International Accessories ', '', '', 1, 'Active', 0, '', 'mirshath'),
(105, '', 'Shihana', 'Samsudeen', 'F.Shihana Samsudeen', 'F.Shihana Samsudeen', '1991-03-24', 'Sri Lanka', '28,  Lankamatha Rd,   Mahabage Gampaha Western', '28,  Lankamatha Rd,   Mahabage Gampaha Western', '778858339', '756994461', '', '', 1, 1, '915840957V', '', 'Shihana.Sam@gmail.com', 'shihana.samsudeen@bms.ac.lk', '', 'Advontis Express', '', '', 1, 'Active', 0, '', 'mirshath'),
(106, '', 'Thivyalojan', 'Selvaraj', 'Selvaraj Thivyalojan', 'Selvaraj Thivyalojan', '1998-11-13', 'Sri Lanka', '37/B,  Janpathya   Norwood  ', '110,  Malwatta,   Dehiwala Colombo Western', '765721997', '', '', '', 1, 1, '983181937V', '', 'thivyalojan9@gmail.com', 'thivyalojan.selvaraj@bms.ac.lk', 'Audit Assistant ', 'L.M. Association ', '', '', 1, 'Active', 0, '', 'mirshath');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL,
  `student_code` int(11) NOT NULL,
  `registration_receipt` varchar(255) DEFAULT NULL,
  `cv` varchar(255) DEFAULT NULL,
  `nic_passport` varchar(255) DEFAULT NULL,
  `education_qualification_1` varchar(255) DEFAULT NULL,
  `education_qualification_2` varchar(255) DEFAULT NULL,
  `education_qualification_3` varchar(255) DEFAULT NULL,
  `education_qualification_4` varchar(255) DEFAULT NULL,
  `experience_1` varchar(255) DEFAULT NULL,
  `experience_2` varchar(255) DEFAULT NULL,
  `experience_3` varchar(255) DEFAULT NULL,
  `experience_4` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `other` varchar(255) DEFAULT NULL,
  `entered_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_results`
--

CREATE TABLE `student_results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_registration_id` varchar(50) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `main_component_id` int(11) NOT NULL,
  `sub_component_id` int(11) DEFAULT NULL,
  `result` varchar(50) DEFAULT NULL,
  `resit_result_1` varchar(50) DEFAULT NULL,
  `resit_result_2` varchar(50) DEFAULT NULL,
  `resit_result_3` varchar(50) DEFAULT NULL,
  `resit_result_4` varchar(50) DEFAULT NULL,
  `full_marks` varchar(50) DEFAULT NULL,
  `converted_marks` varchar(40) DEFAULT NULL,
  `hd_full_marks` int(11) DEFAULT NULL,
  `hd_converted_marks` int(11) DEFAULT NULL,
  `hd_grade` varchar(50) DEFAULT NULL,
  `hd_resit1_full_marks` int(10) DEFAULT NULL,
  `hd_resit1_converted_marks` int(50) DEFAULT NULL,
  `hd_resit1_grade` varchar(50) DEFAULT NULL,
  `hd_resit2_full_marks` int(11) DEFAULT NULL,
  `hd_resit2_converted_marks` int(11) DEFAULT NULL,
  `hd_resit2_grade` varchar(40) DEFAULT NULL,
  `hd_resit3_full_marks` int(20) DEFAULT NULL,
  `hd_resit3_converted_marks` int(20) DEFAULT NULL,
  `hd_resit3_grade` varchar(50) DEFAULT NULL,
  `entered_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `student_results`
--
DELIMITER $$
CREATE TRIGGER `update_final_result` AFTER INSERT ON `student_results` FOR EACH ROW BEGIN
    REPLACE INTO final_student_results (student_id, student_registration_id, program_id, batch_id, module_id, final_result)
    SELECT 
        student_id, 
        student_registration_id, 
        program_id, 
        batch_id, 
        module_id, 
        SUM(CAST(converted_marks AS SIGNED))
    FROM student_results
    WHERE student_id = NEW.student_id
      AND program_id = NEW.program_id
      AND batch_id = NEW.batch_id
      AND module_id = NEW.module_id
    GROUP BY student_id, program_id, batch_id, module_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_transfer`
--

CREATE TABLE `student_transfer` (
  `id` int(11) NOT NULL,
  `student_id` int(50) NOT NULL,
  `from_student` varchar(100) NOT NULL,
  `from_university` varchar(100) NOT NULL,
  `from_programme` varchar(100) NOT NULL,
  `from_batch` varchar(50) NOT NULL,
  `from_registration_code` varchar(50) NOT NULL,
  `university_id` int(11) NOT NULL,
  `programme_code` int(50) NOT NULL,
  `batch_id` int(50) NOT NULL,
  `transfer_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `active_status` varchar(50) NOT NULL DEFAULT 'active',
  `allocated_id` int(11) NOT NULL,
  `entered_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sub_assign_components`
--

CREATE TABLE `sub_assign_components` (
  `id` int(11) NOT NULL,
  `sub_component_name` varchar(55) NOT NULL,
  `sub_component_percent` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sub_assign_components`
--

INSERT INTO `sub_assign_components` (`id`, `sub_component_name`, `sub_component_percent`) VALUES
(1, 'Sub Component One', '99%'),
(3, 'Individual Assignment ', '20%'),
(4, 'Group Assignment', '20%'),
(5, 'Mid-Semester Test', ''),
(6, 'Assignment', ''),
(7, 'Assignment', ''),
(8, 'Individual and Group Assignment', ''),
(9, 'Individual Assignment', '20%'),
(10, 'Group Assignment', '20%'),
(11, 'Mid-Semester Test    ', '20%'),
(12, 'Group Assignment / QUI', '40%');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `programme_id` varchar(20) NOT NULL COMMENT 'Program code from program_table',
  `programme_name` varchar(100) NOT NULL COMMENT 'Program name for reference',
  `batch_id` int(11) NOT NULL COMMENT 'Batch ID from batch_table',
  `batch_name` varchar(50) NOT NULL COMMENT 'Batch name for reference',
  `module_id` int(11) NOT NULL COMMENT 'Module ID from modules table',
  `module_name` varchar(100) NOT NULL COMMENT 'Module name for reference',
  `lecturer_id` int(11) NOT NULL COMMENT 'Lecturer ID from lecturer_table',
  `lecturer_name` varchar(100) NOT NULL COMMENT 'Lecturer name for reference',
  `day` varchar(10) NOT NULL COMMENT 'Day of the week',
  `start_time` time NOT NULL COMMENT 'Class start time',
  `end_time` time NOT NULL COMMENT 'Class end time',
  `start_date` date NOT NULL COMMENT 'Module start date',
  `end_date` date NOT NULL COMMENT 'Module end date',
  `comp1_deadline` date NOT NULL COMMENT 'Component 1 deadline',
  `comp2_deadline` date NOT NULL COMMENT 'Component 2 deadline',
  `created_by` varchar(50) NOT NULL COMMENT 'Username who created this entry',
  `created_at` datetime NOT NULL COMMENT 'Record creation timestamp',
  `updated_by` varchar(50) DEFAULT NULL COMMENT 'Username who last updated this entry',
  `updated_at` datetime DEFAULT NULL COMMENT 'Record last update timestamp',
  `status` enum('active','inactive','cancelled') NOT NULL DEFAULT 'active' COMMENT 'Timetable entry status'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `total_mod_rslt`
--

CREATE TABLE `total_mod_rslt` (
  `id` int(11) NOT NULL,
  `std_id` int(11) NOT NULL,
  `std_reg_no` varchar(50) NOT NULL,
  `prog_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `year_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `module_gpa_value` decimal(10,2) DEFAULT NULL,
  `ex1_total_rslt` decimal(10,2) NOT NULL,
  `ex2_total_rslt` decimal(10,2) NOT NULL,
  `total` double(10,2) DEFAULT NULL,
  `grades` varchar(50) DEFAULT NULL,
  `GV` decimal(10,2) NOT NULL,
  `CGP` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `universities`
--

CREATE TABLE `universities` (
  `id` int(11) NOT NULL,
  `university_code` varchar(100) NOT NULL,
  `university_name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `uni_code` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `universities`
--

INSERT INTO `universities` (`id`, `university_code`, `university_name`, `address`, `uni_code`, `created_at`) VALUES
(1, 'UN01', 'BMS', 'Wellawatte', 'UEX01', '2024-08-12 08:27:18'),
(14, 'UN02', 'ESOFT', 'bamba', 'UEX02', '2024-08-15 10:30:06');

-- --------------------------------------------------------

--
-- Table structure for table `user_permission`
--

CREATE TABLE `user_permission` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nav_items` int(11) NOT NULL,
  `sub_list_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_permission`
--

INSERT INTO `user_permission` (`id`, `user_id`, `nav_items`, `sub_list_value`) VALUES
(2556, 12, 1, 'Lead Types'),
(2557, 12, 1, 'Year'),
(2558, 12, 1, 'Semester'),
(2559, 12, 1, 'Criteria'),
(2560, 12, 1, 'University'),
(2561, 12, 1, 'Coordinator'),
(2562, 12, 1, 'Program'),
(2563, 12, 1, 'Assignment Components'),
(2564, 12, 1, 'Lecture'),
(2565, 12, 1, 'Module'),
(2566, 12, 1, 'Batch'),
(2567, 12, 1, 'Grade'),
(2568, 12, 1, 'Currency'),
(2569, 12, 1, 'Status'),
(2570, 12, 1, 'Decision'),
(2571, 12, 2, 'Add Leads'),
(2572, 12, 2, 'Student Registration'),
(2573, 12, 2, 'Upload Students'),
(2574, 12, 2, 'Allocate Program'),
(2575, 12, 2, 'Components Allocations'),
(2576, 12, 2, 'Update Student Status'),
(2577, 12, 2, 'Student Batch Transfer'),
(2578, 12, 2, 'Update Students E Module'),
(2579, 12, 2, 'Upload Student Documents'),
(2580, 12, 2, 'student_upload_copy_edit_button'),
(2581, 12, 2, 'Add Payment Plan'),
(2582, 12, 2, 'Batch Wise Payment Plan'),
(2583, 12, 2, 'Payment'),
(2584, 12, 2, 'Penalty Payment'),
(2585, 12, 2, 'Additional Payment'),
(2586, 12, 2, 'Send Offer Letter'),
(2587, 12, 2, 'Time Table'),
(2588, 12, 2, 'Daily Time Table Message'),
(2589, 12, 2, 'Special Class Messages'),
(2590, 12, 2, 'Exam / Assignment'),
(2591, 12, 2, 'exams_asses_send_button'),
(2592, 12, 2, 'exams_asses_edit_button'),
(2593, 12, 2, 'Exam / Assignment Result'),
(2594, 12, 2, 'E/A Result Send'),
(2595, 12, 2, 'Module Results Mail'),
(2596, 12, 2, 'Special Reason'),
(2597, 12, 2, 'Add Decision'),
(2598, 12, 2, 'Alumni'),
(2599, 12, 2, 'List Board'),
(2600, 12, 3, 'Edit Programme Allocation'),
(2601, 12, 3, 'Edit Payment'),
(2602, 12, 4, ''),
(2603, 12, 5, 'All Student Details'),
(2604, 12, 5, 'Student Wise Details'),
(2605, 12, 5, 'Leads Report'),
(2606, 12, 5, 'Outstanding Payment'),
(2607, 12, 5, 'Special Reason Report'),
(2608, 12, 5, 'Exam / Assignment Report'),
(2609, 12, 5, 'Result Mailing Report'),
(2610, 12, 5, 'Payment Report'),
(2611, 12, 5, 'Penalty Payment Report'),
(2612, 12, 5, 'Additional Payment Report'),
(2613, 12, 5, 'Time Table Report'),
(2614, 12, 5, 'Alumni Report'),
(2615, 12, 5, 'BBM result report'),
(2616, 12, 6, 'Add Users'),
(2617, 12, 6, 'User Permission'),
(2618, 12, 7, 'card_enable'),
(2619, 12, 8, 'payment cancellation'),
(2620, 12, 8, 'penalty payment cancellation'),
(2621, 12, 8, 'Additional payment cancellation'),
(2635, 14, 2, 'Student Registration'),
(2636, 14, 2, 'Exam / Assignment'),
(2637, 14, 2, 'Exam / Assignment Result'),
(2638, 14, 2, 'E/A Result Send'),
(2639, 14, 2, 'Module Results Mail'),
(2640, 4, 1, 'Lead Types'),
(2641, 4, 1, 'Year'),
(2642, 4, 1, 'Semester'),
(2643, 4, 1, 'Criteria'),
(2644, 4, 1, 'University'),
(2645, 4, 1, 'Coordinator'),
(2646, 4, 1, 'Program'),
(2647, 4, 1, 'Assignment Components'),
(2648, 4, 1, 'Lecture'),
(2649, 4, 1, 'Module'),
(2650, 4, 1, 'Batch'),
(2651, 4, 1, 'Grade'),
(2652, 4, 1, 'Currency'),
(2653, 4, 1, 'Status'),
(2654, 4, 1, 'Decision'),
(2655, 4, 2, 'Add Leads'),
(2656, 4, 2, 'Student Registration'),
(2657, 4, 2, 'Upload Students'),
(2658, 4, 2, 'Allocate Program'),
(2659, 4, 2, 'Components Allocations'),
(2660, 4, 2, 'Update Student Status'),
(2661, 4, 2, 'Student Batch Transfer'),
(2662, 4, 2, 'Update Students E Module'),
(2663, 4, 2, 'Upload Student Documents'),
(2664, 4, 2, 'student_upload_copy_edit_button'),
(2665, 4, 2, 'Add Payment Plan'),
(2666, 4, 2, 'Batch Wise Payment Plan'),
(2667, 4, 2, 'Payment'),
(2668, 4, 2, 'Penalty Payment'),
(2669, 4, 2, 'Additional Payment'),
(2670, 4, 2, 'Send Offer Letter'),
(2671, 4, 2, 'Time Table'),
(2672, 4, 2, 'Daily Time Table Message'),
(2673, 4, 2, 'Special Class Messages'),
(2674, 4, 2, 'Exam / Assignment'),
(2675, 4, 2, 'exams_asses_send_button'),
(2676, 4, 2, 'exams_asses_edit_button'),
(2677, 4, 2, 'Exam / Assignment Result'),
(2678, 4, 2, 'E/A Result Send'),
(2679, 4, 2, 'Module Results Mail'),
(2680, 4, 2, 'Special Reason'),
(2681, 4, 2, 'Add Decision'),
(2682, 4, 2, 'Alumni'),
(2683, 4, 2, 'List Board'),
(2684, 4, 3, 'Edit Programme Allocation'),
(2685, 4, 3, 'Edit Payment'),
(2686, 4, 4, ''),
(2687, 4, 5, 'All Student Details'),
(2688, 4, 5, 'Student Wise Details'),
(2689, 4, 5, 'Leads Report'),
(2690, 4, 5, 'Outstanding Payment'),
(2691, 4, 5, 'Special Reason Report'),
(2692, 4, 5, 'Exam / Assignment Report'),
(2693, 4, 5, 'Result Mailing Report'),
(2694, 4, 5, 'Payment Report'),
(2695, 4, 5, 'Penalty Payment Report'),
(2696, 4, 5, 'Additional Payment Report'),
(2697, 4, 5, 'Time Table Report'),
(2698, 4, 5, 'Alumni Report'),
(2699, 4, 5, 'BBM result report'),
(2700, 4, 6, 'Add Users'),
(2701, 4, 6, 'User Permission'),
(2702, 4, 7, 'card_enable'),
(2703, 4, 8, 'payment cancellation'),
(2704, 4, 8, 'penalty payment cancellation'),
(2705, 4, 8, 'Additional payment cancellation');

-- --------------------------------------------------------

--
-- Table structure for table `year_table`
--

CREATE TABLE `year_table` (
  `id` int(11) NOT NULL,
  `year_name` varchar(100) NOT NULL,
  `entered_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `year_table`
--

INSERT INTO `year_table` (`id`, `year_name`, `entered_by`, `created_at`) VALUES
(17, '1st year', NULL, '2024-08-12 07:47:35'),
(18, '2nd year', NULL, '2024-08-12 07:47:37'),
(19, '3rd year', NULL, '2024-08-12 07:47:39'),
(42, 'N/A', NULL, '2024-08-17 07:18:28');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `additional_fee_items`
--
ALTER TABLE `additional_fee_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payment_id` (`payment_id`);

--
-- Indexes for table `additional_fee_payments`
--
ALTER TABLE `additional_fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_student_registration_id` (`student_registration_id`),
  ADD KEY `idx_programme_code` (`programme_code`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_paid_date` (`paid_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `add_payment_plan_table`
--
ALTER TABLE `add_payment_plan_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `allocated_components`
--
ALTER TABLE `allocated_components`
  ADD PRIMARY KEY (`id`),
  ADD KEY `main_component_id` (`main_component_id`),
  ADD KEY `allocated_components_ibfk_2` (`sub_component_id`);

--
-- Indexes for table `allocate_programme`
--
ALTER TABLE `allocate_programme`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_code` (`student_code`),
  ADD KEY `university_id` (`university_id`),
  ADD KEY `programme_code` (`programme_code`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assignment_components`
--
ALTER TABLE `assignment_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `batch_table`
--
ALTER TABLE `batch_table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university` (`university`),
  ADD KEY `programme` (`programme`);

--
-- Indexes for table `bbm_direct_result`
--
ALTER TABLE `bbm_direct_result`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bbm_final_results_tbl`
--
ALTER TABLE `bbm_final_results_tbl`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bbm_result_details`
--
ALTER TABLE `bbm_result_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bms_grading`
--
ALTER TABLE `bms_grading`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coordinator_table`
--
ALTER TABLE `coordinator_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `criterias`
--
ALTER TABLE `criterias`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `currency_table`
--
ALTER TABLE `currency_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `decision_table`
--
ALTER TABLE `decision_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `description_grading`
--
ALTER TABLE `description_grading`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `failed_emails`
--
ALTER TABLE `failed_emails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `final_student_results`
--
ALTER TABLE `final_student_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result` (`student_id`,`program_id`,`batch_id`,`module_id`);

--
-- Indexes for table `gpa_calculate_tbl`
--
ALTER TABLE `gpa_calculate_tbl`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_semester` (`std_id`,`prog_id`,`batch_id`,`year_id`,`semester_id`);

--
-- Indexes for table `grade_table`
--
ALTER TABLE `grade_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `installment_details_table`
--
ALTER TABLE `installment_details_table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `installment_payment_table_id` (`installment_payment_table_id`);

--
-- Indexes for table `installment_payment_table`
--
ALTER TABLE `installment_payment_table`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_plans_tb_id` (`payment_plans_tb_id`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `leads_table`
--
ALTER TABLE `leads_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lecturer_table`
--
ALTER TABLE `lecturer_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `university_id` (`university_id`),
  ADD KEY `programme_id` (`programme_id`);

--
-- Indexes for table `nav_collections`
--
ALTER TABLE `nav_collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nested_assign_components`
--
ALTER TABLE `nested_assign_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `normal_grading`
--
ALTER TABLE `normal_grading`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_cancellation_log`
--
ALTER TABLE `payment_cancellation_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_uni_fee`
--
ALTER TABLE `payment_uni_fee`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_wise_info`
--
ALTER TABLE `payment_wise_info`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `penalty_payments`
--
ALTER TABLE `penalty_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_student_registration_id` (`student_registration_id`),
  ADD KEY `idx_programme_code` (`programme_code`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_paid_date` (`paid_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `program_table`
--
ALTER TABLE `program_table`
  ADD PRIMARY KEY (`program_code`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `semester_table`
--
ALTER TABLE `semester_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `special_class_messages`
--
ALTER TABLE `special_class_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `status_table`
--
ALTER TABLE `status_table`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_code`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_code` (`student_code`);

--
-- Indexes for table `student_results`
--
ALTER TABLE `student_results`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_transfer`
--
ALTER TABLE `student_transfer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `programme_code` (`programme_code`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `university_id` (`university_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `sub_assign_components`
--
ALTER TABLE `sub_assign_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_programme_id` (`programme_id`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_module_id` (`module_id`),
  ADD KEY `idx_lecturer_id` (`lecturer_id`),
  ADD KEY `idx_day` (`day`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `total_mod_rslt`
--
ALTER TABLE `total_mod_rslt`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `universities`
--
ALTER TABLE `universities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_permission`
--
ALTER TABLE `user_permission`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `nav_items` (`nav_items`);

--
-- Indexes for table `year_table`
--
ALTER TABLE `year_table`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `additional_fee_items`
--
ALTER TABLE `additional_fee_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `additional_fee_payments`
--
ALTER TABLE `additional_fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `add_payment_plan_table`
--
ALTER TABLE `add_payment_plan_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `allocated_components`
--
ALTER TABLE `allocated_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=185;

--
-- AUTO_INCREMENT for table `allocate_programme`
--
ALTER TABLE `allocate_programme`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment_components`
--
ALTER TABLE `assignment_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `batch_table`
--
ALTER TABLE `batch_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `bbm_direct_result`
--
ALTER TABLE `bbm_direct_result`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbm_final_results_tbl`
--
ALTER TABLE `bbm_final_results_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bbm_result_details`
--
ALTER TABLE `bbm_result_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bms_grading`
--
ALTER TABLE `bms_grading`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `coordinator_table`
--
ALTER TABLE `coordinator_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `criterias`
--
ALTER TABLE `criterias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `currency_table`
--
ALTER TABLE `currency_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `decision_table`
--
ALTER TABLE `decision_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `description_grading`
--
ALTER TABLE `description_grading`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `failed_emails`
--
ALTER TABLE `failed_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `final_student_results`
--
ALTER TABLE `final_student_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gpa_calculate_tbl`
--
ALTER TABLE `gpa_calculate_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_table`
--
ALTER TABLE `grade_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `installment_details_table`
--
ALTER TABLE `installment_details_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `installment_payment_table`
--
ALTER TABLE `installment_payment_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads_table`
--
ALTER TABLE `leads_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `lecturer_table`
--
ALTER TABLE `lecturer_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=206;

--
-- AUTO_INCREMENT for table `nav_collections`
--
ALTER TABLE `nav_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `nested_assign_components`
--
ALTER TABLE `nested_assign_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `normal_grading`
--
ALTER TABLE `normal_grading`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_cancellation_log`
--
ALTER TABLE `payment_cancellation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_uni_fee`
--
ALTER TABLE `payment_uni_fee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_wise_info`
--
ALTER TABLE `payment_wise_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penalty_payments`
--
ALTER TABLE `penalty_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `program_table`
--
ALTER TABLE `program_table`
  MODIFY `program_code` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `semester_table`
--
ALTER TABLE `semester_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `special_class_messages`
--
ALTER TABLE `special_class_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status_table`
--
ALTER TABLE `status_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_code` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `student_results`
--
ALTER TABLE `student_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_transfer`
--
ALTER TABLE `student_transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `sub_assign_components`
--
ALTER TABLE `sub_assign_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `total_mod_rslt`
--
ALTER TABLE `total_mod_rslt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `universities`
--
ALTER TABLE `universities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_permission`
--
ALTER TABLE `user_permission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2706;

--
-- AUTO_INCREMENT for table `year_table`
--
ALTER TABLE `year_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `additional_fee_items`
--
ALTER TABLE `additional_fee_items`
  ADD CONSTRAINT `fk_additional_fee_items_payment` FOREIGN KEY (`payment_id`) REFERENCES `additional_fee_payments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `allocated_components`
--
ALTER TABLE `allocated_components`
  ADD CONSTRAINT `allocated_components_ibfk_1` FOREIGN KEY (`main_component_id`) REFERENCES `assignment_components` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `allocated_components_ibfk_2` FOREIGN KEY (`sub_component_id`) REFERENCES `sub_assign_components` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `allocate_programme`
--
ALTER TABLE `allocate_programme`
  ADD CONSTRAINT `allocate_programme_ibfk_1` FOREIGN KEY (`student_code`) REFERENCES `students` (`student_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `allocate_programme_ibfk_2` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `allocate_programme_ibfk_3` FOREIGN KEY (`programme_code`) REFERENCES `program_table` (`program_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `allocate_programme_ibfk_4` FOREIGN KEY (`batch_id`) REFERENCES `batch_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `batch_table`
--
ALTER TABLE `batch_table`
  ADD CONSTRAINT `batch_table_ibfk_1` FOREIGN KEY (`university`) REFERENCES `universities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_programme_code` FOREIGN KEY (`programme`) REFERENCES `program_table` (`program_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `installment_details_table`
--
ALTER TABLE `installment_details_table`
  ADD CONSTRAINT `installment_details_table_ibfk_1` FOREIGN KEY (`installment_payment_table_id`) REFERENCES `installment_payment_table` (`id`);

--
-- Constraints for table `modules`
--
ALTER TABLE `modules`
  ADD CONSTRAINT `modules_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`),
  ADD CONSTRAINT `modules_ibfk_2` FOREIGN KEY (`programme_id`) REFERENCES `program_table` (`program_code`);

--
-- Constraints for table `program_table`
--
ALTER TABLE `program_table`
  ADD CONSTRAINT `program_table_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`student_code`) REFERENCES `students` (`student_code`);

--
-- Constraints for table `student_transfer`
--
ALTER TABLE `student_transfer`
  ADD CONSTRAINT `student_transfer_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_transfer_ibfk_2` FOREIGN KEY (`programme_code`) REFERENCES `program_table` (`program_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_transfer_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `batch_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `student_transfer_ibfk_4` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_permission`
--
ALTER TABLE `user_permission`
  ADD CONSTRAINT `user_permission_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`),
  ADD CONSTRAINT `user_permission_ibfk_2` FOREIGN KEY (`nav_items`) REFERENCES `nav_collections` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

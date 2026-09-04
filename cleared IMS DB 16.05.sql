-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 08:29 AM
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
(125, 182, 50, NULL, NULL, '2025-01-22 09:00:50'),
(126, 183, 58, NULL, NULL, '2025-02-05 05:00:18'),
(127, 183, 57, NULL, NULL, '2025-02-05 05:00:47'),
(128, 184, 56, NULL, NULL, '2025-02-05 05:01:26'),
(129, 184, 55, NULL, NULL, '2025-02-05 05:01:52'),
(130, 185, 54, NULL, NULL, '2025-02-05 05:02:20'),
(131, 185, 53, NULL, NULL, '2025-02-05 05:02:34'),
(132, 186, 52, NULL, NULL, '2025-02-05 05:03:06'),
(133, 187, 51, NULL, NULL, '2025-02-05 05:03:18'),
(134, 188, 51, NULL, NULL, '2025-02-05 05:03:26'),
(162, 197, 66, NULL, NULL, '2025-02-05 10:32:43'),
(163, 198, 67, NULL, NULL, '2025-02-05 10:33:05'),
(164, 198, 68, NULL, NULL, '2025-02-05 10:33:19'),
(165, 199, 69, NULL, NULL, '2025-02-05 10:33:50'),
(166, 199, 71, NULL, NULL, '2025-02-05 10:34:03'),
(167, 199, 70, NULL, NULL, '2025-02-05 10:34:12'),
(168, 200, 69, NULL, NULL, '2025-02-05 10:34:44'),
(169, 200, 69, NULL, NULL, '2025-02-05 10:34:55'),
(170, 201, 72, NULL, NULL, '2025-02-05 10:35:08'),
(171, 201, 73, NULL, NULL, '2025-02-05 10:35:18'),
(172, 201, 73, NULL, 'mirshath', '2025-02-18 08:25:36'),
(173, 201, 73, NULL, 'mirshath', '2025-02-18 08:31:06'),
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
(26, 'IFD1', 'Written Exam II - 50% ', '50%'),
(27, 'IFD', ' Group Presentation 50%', '50%'),
(28, 'ifd', 'Written Exam I - 30%', '30%'),
(29, 'ifd', 'Written Exam - 70%', '70%'),
(30, 'ifd', 'Close Book Exam - 50%', '50%'),
(31, 'ifd', 'Open Book Exam 50%', '50%'),
(32, 'ifd', 'Practical Exam - 30%', '30%'),
(33, 'ifd', 'Assignment - 70%', '70%'),
(34, 'ifd', 'Assignment - 100%', '100%'),
(35, 'ifd', 'Written  Exam I - (LO 1&2)   ', NULL),
(36, 'ifd', 'Group Poster Presentation - (LO3', NULL),
(37, 'ifd', 'Written Exam II - (LO4) ', NULL),
(38, 'ifd', 'Assignment - (LO 1&2)', NULL),
(39, 'ifd', 'Individual Oral Presentation (LO3&4)', NULL),
(40, 'ifd', 'Written Exam II (LO 3&4)', NULL),
(41, 'ifd', 'Group Oral Presentation (LO1,2,3&4)', NULL),
(42, 'ifd', 'Assignment - (LO1,2,3&4)', NULL),
(43, 'ecm', 'Assignemt 50%', '50%'),
(44, 'ecm', 'Exam 50%', '50%'),
(45, 'ecm', 'Presentation 80%', '80%'),
(46, 'ecm', 'Reflective Statement 20% ', '20%'),
(47, 'ecm', 'Exam 100%', '100%'),
(50, 'sd', 'ifd- sc', '50%'),
(51, 'gdm', 'Assignment', ''),
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
(66, 'hd_biomed', 'LR', ''),
(67, 'hd_biomed', 'pre', '40%'),
(68, 'hd_biomed', 'Assignment', '60%'),
(69, 'hd_biomed', 'E', '50%'),
(70, 'hd_biomed', 'LR', '25%'),
(71, 'hd_biomed', 'E', '25%'),
(72, 'hd_biomed', 'E', '60%'),
(73, 'hd_biomed', 'E', '40%'),
(74, 'b1', ' Continuous Assessment', '40%'),
(75, 'bb2', 'End-Semester Examination', '60');

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

--
-- Dumping data for table `coordinator_table`
--

INSERT INTO `coordinator_table` (`id`, `coordinator_code`, `title`, `coordinator_name`, `bms_email`, `password_hash`) VALUES
(1, 'COD1', 'Ms', 'Asma Raahman', 'asari@bms.ac.lk', '$2y$10$LnTuLDS0Cz8wWV8Er8IjiOILvj753szQ9RcqvhLKe3MegzxY.NVtK'),
(2, 'COD2', 'Prof', 'Geethika Liyanage', 'latuhywu@mailinator.com', '$2y$10$HOK49FsnIAr/ZfiXTaQZ3.UOz3lUL23mTVvSdaOKFUIN5PmGi1J3u'),
(3, 'COD3', 'Dr', 'Hasni Nihar', 'webmaster@bms.ac.lk', '$2y$10$0EQTFHGnAh7lcVf7wstFcO66JrBRPgI8IBuSq3q1rSaVInj4zADwO');

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
(197, 'HD_BIO_MED', 'Scien: & Lab: Skill', 1, 42, 42, 1, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(198, 'HD_BIO_MED2', 'Academic Writing', 1, 42, 42, 1, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(199, 'HD_BIO_MED3', 'Molecu: Bio: & Gen:', 1, 42, 42, 2, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(200, 'HD_BIO_MED4', 'Human Ana: & Phy:', 1, 42, 42, 2, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(201, 'HD_BIO_MED5', 'Immunology', 1, 42, 42, 2, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(204, 'mt', 'mt', 1, 47, 17, 1, '50', 'Compulsory', '', '', 2, '4', '12'),
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
(2706, 4, 1, 'Lead Types'),
(2707, 4, 1, 'Year'),
(2708, 4, 1, 'Semester'),
(2709, 4, 1, 'Criteria'),
(2710, 4, 1, 'University'),
(2711, 4, 1, 'Coordinator'),
(2712, 4, 1, 'Program'),
(2713, 4, 1, 'Assignment Components'),
(2714, 4, 1, 'Lecture'),
(2715, 4, 1, 'Module'),
(2716, 4, 1, 'Batch'),
(2717, 4, 1, 'Grade'),
(2718, 4, 1, 'Currency'),
(2719, 4, 1, 'Status'),
(2720, 4, 1, 'Decision'),
(2721, 4, 2, 'Add Leads'),
(2722, 4, 2, 'Student Registration'),
(2723, 4, 2, 'Upload Students'),
(2724, 4, 2, 'Allocate Program'),
(2725, 4, 2, 'Components Allocations'),
(2726, 4, 2, 'Update Student Status'),
(2727, 4, 2, 'Student Batch Transfer'),
(2728, 4, 2, 'Upload Student Documents'),
(2729, 4, 2, 'student_upload_copy_edit_button'),
(2730, 4, 2, 'Add Payment Plan'),
(2731, 4, 2, 'Batch Wise Payment Plan'),
(2732, 4, 2, 'Payment'),
(2733, 4, 2, 'Penalty Payment'),
(2734, 4, 2, 'Additional Payment'),
(2735, 4, 2, 'Send Offer Letter'),
(2736, 4, 2, 'Time Table'),
(2737, 4, 2, 'Daily Time Table Message'),
(2738, 4, 2, 'Special Class Messages'),
(2739, 4, 2, 'Exam / Assignment'),
(2740, 4, 2, 'exams_asses_send_button'),
(2741, 4, 2, 'exams_asses_edit_button'),
(2742, 4, 2, 'Exam / Assignment Result'),
(2743, 4, 2, 'E/A Result Send'),
(2744, 4, 2, 'Module Results Mail'),
(2745, 4, 2, 'Special Reason'),
(2746, 4, 2, 'Add Decision'),
(2747, 4, 2, 'Alumni'),
(2748, 4, 2, 'List Board'),
(2749, 4, 3, 'Edit Programme Allocation'),
(2750, 4, 3, 'Edit Payment'),
(2751, 4, 4, ''),
(2752, 4, 5, 'All Student Details'),
(2753, 4, 5, 'Student Wise Details'),
(2754, 4, 5, 'Leads Report'),
(2755, 4, 5, 'Outstanding Payment'),
(2756, 4, 5, 'Special Reason Report'),
(2757, 4, 5, 'Exam / Assignment Report'),
(2758, 4, 5, 'Result Mailing Report'),
(2759, 4, 5, 'Payment Report'),
(2760, 4, 5, 'Penalty Payment Report'),
(2761, 4, 5, 'Additional Payment Report'),
(2762, 4, 5, 'Time Table Report'),
(2763, 4, 5, 'Alumni Report'),
(2764, 4, 5, 'BBM result report'),
(2765, 4, 6, 'Add Users'),
(2766, 4, 6, 'User Permission'),
(2767, 4, 7, 'card_enable'),
(2768, 4, 8, 'payment cancellation'),
(2769, 4, 8, 'penalty payment cancellation'),
(2770, 4, 8, 'Additional payment cancellation');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `student_code` int(20) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2771;

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

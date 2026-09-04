-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 10:56 AM
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

--
-- Dumping data for table `additional_fee_items`
--

INSERT INTO `additional_fee_items` (`id`, `payment_id`, `sequence`, `description`, `amount`, `created_at`) VALUES
(1, 1, 1, 'test1', 250.00, '2025-05-14 12:21:16'),
(2, 1, 2, 'Test2', 300.00, '2025-05-14 12:21:16'),
(3, 2, 1, 'Summa', 2500.00, '2025-05-14 15:43:31');

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

--
-- Dumping data for table `additional_fee_payments`
--

INSERT INTO `additional_fee_payments` (`id`, `student_id`, `student_registration_id`, `programme_code`, `batch_id`, `program_name`, `batch_name`, `total_amount`, `paid_date`, `payment_method`, `payment_reference`, `remarks`, `status`, `entered_by`, `created_at`, `updated_at`) VALUES
(1, '23', 'STU-IFD-B-B1-01-transed-ECM', '45', 26, 'Executive Certificate in Management', 'Batch 06', 550.00, '2025-05-14', 'Cash', NULL, NULL, 'cancelled', 'mirshath', '2025-05-14 12:21:16', NULL),
(2, '23', 'STU-IFD-B-B1-01-transed-ECM', '45', 26, 'Executive Certificate in Management', 'Batch 06', 2500.00, '2025-05-14', 'Cash', NULL, NULL, 'cancelled', 'mirshath', '2025-05-14 15:43:31', NULL);

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

--
-- Dumping data for table `add_payment_plan_table`
--

INSERT INTO `add_payment_plan_table` (`id`, `student_id`, `programme_batch`, `university_fee_LKR`, `courseFeeLKR_total`, `course_fee_LKR`, `course_fee_type_LKR`, `installment_month_LKR`, `registration_fee_LKR`, `university_fee_GBP`, `courseFeeGBP_total`, `course_fee_GBP`, `course_fee_type_GBP`, `installment_month_GBP`, `registration_fee_GBP`, `university_fee_USD`, `courseFeeUSD_total`, `course_fee_USD`, `course_fee_type_USD`, `installment_month_USD`, `registration_fee_USD`, `entered_by`, `created_at`) VALUES
(278, 23, 'Executive Certificate in Management - Batch 06', 0, 39000, 30000, 'full', 0, 9000, 350, 0, 0, '', 0, 0, 0, 0, 0, '', 0, 0, 'mirshath', '2025-05-08 04:52:49'),
(279, 87, 'Executive Certificate in Management - Batch 05', 0, 69000, 60000, 'installment', 6, 9000, 0, 0, 0, '', 0, 0, 600, 0, 0, '', 0, 0, 'mirshath', '2025-05-08 05:04:19'),
(280, 25, 'IFD (Business) - ATHE Level 3 - Batch 30', 0, 3333, 3000, 'installment', 2, 333, 333, 0, 0, '', 0, 0, 0, 0, 0, '', 0, 0, 'mirshath', '2025-05-10 06:47:33');

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
(210, 23, 1, 39, 12, 'STU-IFD-B-B1-01', '', 'Economics for Business,Business Essentials,Accounting Fundamentals,Mathematics and Statistics,Digital Technology and Study Skills,English for Academic Purposes', 'transferred', 't-ecm', NULL),
(211, 25, 1, 39, 12, 'STU-222222', '', 'Economics for Business,Business Essentials,Accounting Fundamentals,Mathematics and Statistics,Digital Technology and Study Skills,English for Academic Purposes', 'active', '', NULL),
(212, 26, 1, 45, 25, 'sssss', '', 'Principles of Management,Marketing Essentials,Accounting Principles', 'active', '', NULL),
(213, 86, 1, 41, 17, 'STU-00054dd', '', 'DEMO', 'active', '', NULL),
(214, 88, 1, 40, 14, 'STU-00s', '', '', 'active', '', NULL),
(215, 87, 1, 45, 25, 'ecm', '', 'Principles of Management,Marketing Essentials,Accounting Principles', 'active', '', NULL),
(216, 83, 1, 46, 22, 'STU-GDM-01-MS', '', 'Leadership and Management in a Digital Economy,Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers,Research Project', 'active', '', NULL),
(217, 89, 1, 47, 29, 'STU-BBM-ST-RANIDHU', '', 'Introduction to Business and Management,Business Environment,Business Communication,Business Law ,Information Technology,Marketing Management,Financial Accounting,Humen Resource Management ', 'active', '', NULL),
(218, 24, 1, 42, 31, 'STU-BIO-MED-asela', '', 'Scien: & Lab: Skill,Academic Writing,Molecu: Bio: & Gen:,Human Ana: & Phy:,Immunology', 'active', '', NULL),
(219, 85, 1, 42, 31, 'STU-ww-BIO-MED-st2', '', 'Scien: & Lab: Skill,Academic Writing,Molecu: Bio: & Gen:,Human Ana: & Phy:,Immunology', 'active', '', NULL),
(220, 84, 1, 41, 17, 'STU-KADA-BTEC-B14-S1', '', 'DEMO', 'active', '', NULL),
(221, 90, 1, 45, 25, 'STU-DOC1_st', '', 'Principles of Management,Marketing Essentials,Accounting Principles', 'transferred', 'tr_to_ifd_b', 'mirshath'),
(222, 90, 1, 39, 12, 'STU-DOC1_st_IFD_new', NULL, 'Economics for Business,Business Essentials,Accounting Fundamentals,Mathematics and Statistics,Digital Technology and Study Skills,English for Academic Purposes,www', 'active', '', 'mirshath'),
(233, 149, 1, 39, 12, 'Gimmi-IFD-st-4', '', 'Economics for Business,Business Essentials,Accounting Fundamentals,Mathematics and Statistics,Digital Technology and Study Skills,English for Academic Purposes,www', 'active', '', 'mirshath'),
(234, 150, 1, 39, 12, 'rsn-mhthya-IFD-st-5', 'IFD MODULE 2 ELECT', 'IFD MODULE 1 COMP,IFD MODULE 3 COMP', 'active', '', 'mirshath'),
(235, 23, 1, 45, 25, 'STU-IFD-B-B1-01-transed-ECM', NULL, 'Principles of Management,Marketing Essentials,Accounting Principles', 'transferred', 'SAME ID TEST', 'mirshath'),
(236, 151, 1, 47, 29, 'kasun-raja-BBM-st-1', '', 'Introduction to Business and Management,Business Environment,Business Communication,Business Law ,Information Technology,Marketing Management,Financial Accounting,Humen Resource Management ', 'active', '', 'mirshath'),
(237, 23, 1, 45, 26, 'STU-IFD-B-B1-01-transed-ECM', NULL, 'Principles of Management,Marketing Essentials,Accounting Principles', 'active', '', 'mirshath'),
(238, 153, 1, 46, 23, 'AAA-GDM-84-S1', 'IFD MODULE 2 ELECT', 'IFD MODULE 1 COMP,IFD MODULE 3 COMP', 'active', '', 'miru'),
(239, 154, 1, 46, 23, 'BBB-GDM-84-S2', 'IFD MODULE 2 ELECT', 'IFD MODULE 1 COMP,IFD MODULE 3 COMP', 'active', '', 'miru');

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

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `programme_id`, `batch_id`, `module_id`, `year_id`, `semester_id`, `assessment_date`, `description`, `attachment_1`, `attachment_2`, `attachment_3`, `attachment_4`, `mail_sent`, `created_at`, `updated_at`, `main_component_id`, `sub_component_id`, `entered_by`) VALUES
(108, 39, 12, 205, 'N/A', 'N/A', '2025-05-10', '<p>IFD B30 WE1,2</p>\r\n', '6811b15a34408_text-to-word (3) (2).doc', '', '', '', NULL, '2025-04-30 05:12:58', '2025-04-30 05:12:58', 35, 0, 'mirshath'),
(109, 39, 12, 205, 'N/A', 'N/A', '2025-05-03', '<p>IFD B30 WE3,4</p>\r\n', '6811b18ab2cac_text-to-word (2).doc', '', '', '', NULL, '2025-04-30 05:13:16', '2025-04-30 05:13:46', 40, 0, 'mirshath'),
(110, 45, 25, 177, 'N/A', 'N/A', '2025-05-10', '<p>ECM B5 As50%</p>\r\n', '6811bc46437b3_text-to-word (3) (1).doc', '', '', '', NULL, '2025-04-30 05:59:34', '2025-04-30 05:59:34', 43, 0, 'mirshath'),
(111, 45, 25, 177, 'N/A', 'N/A', '2025-05-03', '<p>ECM B5 EX50%</p>\r\n', '6811bc585da77_text-to-word.doc', '', '', '', NULL, '2025-04-30 05:59:52', '2025-04-30 05:59:52', 44, 0, 'mirshath'),
(112, 46, 22, 184, 'N/A', 'N/A', '2025-05-03', '<p>GDM B76 HRM TCA</p>\r\n', '6811bef8517a7_text-to-word (3) (2).doc', '', '', '', NULL, '2025-04-30 06:11:04', '2025-04-30 06:11:04', 56, 0, 'mirshath'),
(113, 46, 22, 184, 'N/A', 'N/A', '2025-05-03', '<p>GDM B76 HRM IDPP</p>\r\n', '6811bf093301d_text-to-word (3) (2).doc', '', '', '', NULL, '2025-04-30 06:11:21', '2025-04-30 06:11:21', 55, 0, 'mirshath'),
(114, 42, 31, 198, 'N/A', 'Semester I', '2025-05-10', '<p>BIOMED B29 AW PRE</p>\r\n', '6811c4426ebff_text-to-word (3).doc', '', '', '', '2025-05-14 10:45:41', '2025-04-30 06:33:38', '2025-05-14 05:15:41', 67, 0, 'mirshath'),
(115, 42, 31, 198, 'N/A', 'Semester I', '2025-05-10', '<p>BIOMED B29 AW ASSIGN</p>\r\n', '6811c44eca9aa_text-to-word (3).doc', '', '', '', NULL, '2025-04-30 06:33:50', '2025-04-30 06:33:50', 68, 0, 'mirshath'),
(116, 47, 29, 189, '1st year', 'Semester I', '2025-05-10', '<p>BBM B1 IBM CA- IA</p>\r\n', '6811c8388ad90_module_results (11).pdf', '', '', '', NULL, '2025-04-30 06:50:32', '2025-04-30 06:50:32', 60, 3, 'mirshath'),
(117, 47, 29, 189, '1st year', 'Semester I', '2025-05-10', '<p>BBM B1 IBM CA- GA</p>\r\n', '6811c84c528b9_module_results (11).pdf', '', '', '', NULL, '2025-04-30 06:50:52', '2025-04-30 06:50:52', 60, 4, 'mirshath'),
(118, 47, 29, 189, '1st year', 'Semester I', '2025-05-10', '<p>BBM B1 IBM END SEMI</p>\r\n', '6811c8609fd41_module_results (11).pdf', '', '', '', NULL, '2025-04-30 06:51:12', '2025-04-30 06:51:12', 61, 0, 'mirshath');

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

--
-- Dumping data for table `bbm_direct_result`
--

INSERT INTO `bbm_direct_result` (`id`, `student_id`, `std_reg_id`, `program_id`, `batch_id`, `module_id`, `year_id`, `semester_id`, `main_comp_id`, `sub_component_id`, `with_Ques`, `question_no`, `100marksEx1`, `examiner1_marks`, `100marksEx2`, `examiner2_marks`, `final_marks`, `status`, `updated_at`) VALUES
(466, 89, 'STU-BBM-ST-RANIDHU', 47, 29, 189, 17, 1, 60, 3, '', 'direct_fullmarks', '100', 20.00, '100', 20.00, 20.00, 'Pending', '2025-04-30 07:56:39'),
(467, 151, 'kasun-raja-BBM-st-1', 47, 29, 189, 17, 1, 60, 3, '', 'direct_fullmarks', '100', 20.00, '100', 20.00, 20.00, 'Pending', '2025-04-30 07:56:39'),
(468, 89, 'STU-BBM-ST-RANIDHU', 47, 29, 189, 17, 1, 60, 4, '', 'direct_fullmarks', '50', 10.00, '50', 10.00, 10.00, 'Pending', '2025-04-30 07:56:58'),
(469, 151, 'kasun-raja-BBM-st-1', 47, 29, 189, 17, 1, 60, 4, '', 'direct_fullmarks', '50', 10.00, '50', 10.00, 10.00, 'Pending', '2025-04-30 07:56:58');

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

--
-- Dumping data for table `bbm_final_results_tbl`
--

INSERT INTO `bbm_final_results_tbl` (`id`, `student_id`, `student_reg_code`, `program_id`, `batch_id`, `module_id`, `year_id`, `semester_id`, `main_comp_id`, `sub_comp_id`, `final_result`, `final_result_ex2`, `que_no`) VALUES
(441, 89, 'STU-BBM-ST-RANIDHU', 47, 29, 189, 17, 1, 61, 0, 60.00, 60.00, 'WITH_QUE_NO'),
(442, 151, 'kasun-raja-BBM-st-1', 47, 29, 189, 17, 1, 61, 0, 60.00, 60.00, 'WITH_QUE_NO'),
(443, 89, 'STU-BBM-ST-RANIDHU', 47, 29, 189, 17, 1, 60, 3, 30.00, 30.00, 'direct_fullmarks'),
(444, 151, 'kasun-raja-BBM-st-1', 47, 29, 189, 17, 1, 60, 3, 30.00, 30.00, 'direct_fullmarks');

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

--
-- Dumping data for table `bbm_result_details`
--

INSERT INTO `bbm_result_details` (`id`, `student_id`, `program_id`, `batch_id`, `module_id`, `year_id`, `semester_id`, `main_comp_id`, `sub_component_id`, `with_Ques`, `question_no`, `100marksEx1`, `examiner1_marks`, `100marksEx2`, `examiner2_marks`, `final_marks`, `status`, `updated_at`) VALUES
(953, 89, 47, 29, 189, 17, 1, 61, NULL, 'no', 'fullmarks', '100', 60.00, '100', 60.00, NULL, 'Pending', '2025-04-30 09:59:31'),
(954, 151, 47, 29, 189, 17, 1, 61, NULL, 'no', 'fullmarks', '100', 60.00, '100', 60.00, NULL, 'Pending', '2025-04-30 07:43:16');

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

--
-- Dumping data for table `final_student_results`
--

INSERT INTO `final_student_results` (`id`, `student_id`, `student_registration_id`, `program_id`, `batch_id`, `module_id`, `final_result`, `entered_by`, `created_at`) VALUES
(1567, 23, 'STU-IFD-B-B1-01-transed-ECM', 45, 25, 177, '77', NULL, '2025-03-21 05:09:32'),
(1588, 25, 'STU-222222', 39, 12, 173, 'merit', NULL, '2025-04-30 05:00:22'),
(1590, 90, 'STU-DOC1_st_IFD_new', 39, 12, 173, 'merit', NULL, '2025-04-30 05:00:22'),
(1592, 149, 'Gimmi-IFD-st-4', 39, 12, 173, 'merit', NULL, '2025-04-30 05:00:22'),
(1594, 150, 'rsn-mhthya-IFD-st-5', 39, 12, 173, 'merit', NULL, '2025-04-30 05:00:22'),
(1608, 25, 'STU-222222', 39, 12, 205, 'merit', NULL, '2025-04-30 05:14:33'),
(1610, 90, 'STU-DOC1_st_IFD_new', 39, 12, 205, 'pass', NULL, '2025-04-30 05:14:33'),
(1612, 149, 'Gimmi-IFD-st-4', 39, 12, 205, 'merit', NULL, '2025-04-30 05:14:33'),
(1614, 150, 'rsn-mhthya-IFD-st-5', 39, 12, 205, 'pass', NULL, '2025-04-30 05:14:33'),
(1620, 26, 'sssss', 45, 25, 177, '90', NULL, '2025-04-30 06:00:29'),
(1622, 87, 'ecm', 45, 25, 177, '90', NULL, '2025-04-30 06:00:29'),
(1626, 83, 'STU-GDM-01-MS', 46, 22, 184, 'pass', NULL, '2025-04-30 06:12:49'),
(1632, 24, 'STU-BIO-MED-asela', 42, 31, 198, 'distinction', NULL, '2025-04-30 06:35:23'),
(1634, 85, 'STU-ww-BIO-MED-st2', 42, 31, 198, 'pending', NULL, '2025-04-30 06:35:23');

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

--
-- Dumping data for table `gpa_calculate_tbl`
--

INSERT INTO `gpa_calculate_tbl` (`id`, `std_id`, `std_reg_no`, `prog_id`, `batch_id`, `year_id`, `semester_id`, `total_credit_value`, `total_CGP_value`, `final_GPA_value`) VALUES
(19, 89, 'STU-BBM-ST-RANIDHU', 47, 29, 17, 1, 5.00, 20.00, 4.00),
(20, 151, 'kasun-raja-BBM-st-1', 47, 29, 17, 1, 5.00, 20.00, 4.00);

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

--
-- Dumping data for table `installment_details_table`
--

INSERT INTO `installment_details_table` (`id`, `installment_payment_table_id`, `student_id`, `programme_batch`, `installment_numbers`, `devided_values`, `installment_amount`, `discount_type`, `discount_value`, `due_date`, `remark`, `entered_by`, `updated_by`) VALUES
(604, 251, '23', 'Executive Certificate in Management - Batch 06', 'installment_1', 30000.00, 0.00, 'N/A', 0, '2025-06-16', '', 'mirshath', NULL),
(605, 252, '87', 'Executive Certificate in Management - Batch 05', 'installment_1', 10000.00, 0.00, 'N/A', 0, '2025-06-29', '', 'mirshath', NULL),
(606, 252, '87', 'Executive Certificate in Management - Batch 05', 'installment_2', 10000.00, 10000.00, 'N/A', 0, '2025-07-29', '', 'mirshath', NULL),
(607, 252, '87', 'Executive Certificate in Management - Batch 05', 'installment_3', 10000.00, 10000.00, 'N/A', 0, '2025-08-28', '', 'mirshath', NULL),
(608, 252, '87', 'Executive Certificate in Management - Batch 05', 'installment_4', 10000.00, 10000.00, 'N/A', 0, '2025-09-27', '', 'mirshath', NULL),
(609, 252, '87', 'Executive Certificate in Management - Batch 05', 'installment_5', 10000.00, 10000.00, 'N/A', 0, '2025-10-27', '', 'mirshath', NULL),
(610, 252, '87', 'Executive Certificate in Management - Batch 05', 'installment_6', 10000.00, 10000.00, 'N/A', 0, '2025-11-26', '', 'mirshath', NULL),
(611, 253, '25', 'IFD (Business) - ATHE Level 3 - Batch 30', 'installment_1', 1500.00, 0.00, 'N/A', 0, '2025-06-22', '', 'mirshath', NULL),
(612, 253, '25', 'IFD (Business) - ATHE Level 3 - Batch 30', 'installment_2', 1500.00, 1300.00, 'N/A', 0, '2025-07-22', '', 'mirshath', NULL);

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

--
-- Dumping data for table `installment_payment_table`
--

INSERT INTO `installment_payment_table` (`id`, `payment_plans_tb_id`, `student_id`, `programme_batch`, `unifee_lkr_total`, `unifee_lkr`, `unifee_gbp_total`, `unifee_gbp`, `unifee_usd_total`, `unifee_usd`, `fee_type`, `coursefee_total`, `coursefee`, `registrationfee`) VALUES
(251, 278, 23, 'Executive Certificate in Management - Batch 06', 0, 0, 350, 0, 0, 0, 'full', 39000, 0, 0),
(252, 279, 87, 'Executive Certificate in Management - Batch 05', 0, 0, 0, 0, 600, 0, 'installment', 69000, 50000, 0),
(253, 280, 25, 'IFD (Business) - ATHE Level 3 - Batch 30', 0, 0, 333, 250, 0, 0, 'installment', 3333, 1300, 0);

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

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `date`, `type`, `university`, `programme`, `intake`, `first_name`, `last_name`, `contact`, `email`, `details`, `status`, `entered_by`) VALUES
(11, '1993-01-01', 'Social Media', '1', 'Higher Diploma in Biomedical Science', '1980-09-27', 'Nisam', 'Mohamed', '0124984458', 'nizjjjjam@mailinator.com', 'ABCDEFG HIJKLMNO', 'Not prospective', NULL),
(12, '2024-08-31', 'Referral', '14', 'BTEC Higher National Diploma in Business', '2024-09-19', 'Daniels', 'Herrera', '0254904455', 'nenyli@mailinator.com', 'SSS', 'N/A', NULL),
(13, '1996-01-26', 'Websites', '14', 'BTEC Higher National Diploma in Business', '1973-05-25', 'Sydnee', 'Castillo', 'Aliqua Esse ullam ', 'baxyjifix@mailinator.com', 'Tempore blanditiis ', 'Not prospective', NULL),
(14, '2025-02-13', 'Websites', '1', 'IFD (Business) - ATHE Level 3', '2025-02-20', 'Mirshath', 'MMM', '0254904455', 'mirshath.mmm@gmail.com', 'Testing s', 'Interested', 'mirshath');

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

--
-- Dumping data for table `lecturer_table`
--

INSERT INTO `lecturer_table` (`id`, `title`, `lecturer_name`, `hourly_rate`, `qualification`, `programs`, `created_at`) VALUES
(45, 'Mr', 'noname 3', 0.00, 'MBA, UK', 'IFD (Business) - ATHE Level 3', '2024-08-15 06:39:58'),
(46, 'Ms', 'Noname 23324', 0.00, 'BSc (Hons) Business with Human Resource Management, UK', 'BTEC Higher National Diploma in Business', '2024-08-15 06:41:11'),
(48, 'Prof', 'Kadeem Lester', 500.00, 'Laboris similique cu', 'Higher Diploma in Biomedical Science', '2024-08-15 10:42:21'),
(50, 'Mr', 'Mirshath', 250.00, 'MSc in Software engineering', 'DIE', '2024-10-03 05:38:59');

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

--
-- Dumping data for table `payment_cancellation_log`
--

INSERT INTO `payment_cancellation_log` (`id`, `payment_id`, `student_id`, `reason`, `cancelled_by`, `cancelled_at`, `payment_type`) VALUES
(1, 365, 23, 'ddd', 'mirshath', '2025-05-14 15:19:12', 'regular'),
(2, 365, 23, 'sdasdsad', 'mirshath', '2025-05-14 15:24:12', 'regular'),
(3, 2, 23, 'SSSS', 'mirshath', '2025-05-14 15:43:49', 'additional'),
(4, 1, 23, 'Summa', 'mirshath', '2025-05-14 15:51:02', 'penalty');

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

--
-- Dumping data for table `payment_uni_fee`
--

INSERT INTO `payment_uni_fee` (`id`, `student_id`, `program_batch`, `currency_type`, `paid_amount`, `exchange_rate`, `LKR_money`, `paid_date`, `payment_type`, `bank_name`, `card_bank_deposit_dt`, `entered_by`, `entered_date`) VALUES
(95, 23, 'Executive Certificate in Management - Batch 06', 'GBP', 350, 350, 122500, '2025-04-30', 'cash', '', '0000-00-00', 'mirshath', '2025-05-08 04:54:18'),
(96, 87, 'Executive Certificate in Management - Batch 05', 'USD', 600, 350, 210000, '2025-05-01', 'cash', '', '0000-00-00', 'mirshath', '2025-05-08 05:05:09'),
(97, 25, 'IFD (Business) - ATHE Level 3 - Batch 30', 'GBP', 33, 200, 6600, '2025-04-30', 'cash', '', '0000-00-00', 'mirshath', '2025-05-10 06:47:58'),
(98, 25, 'IFD (Business) - ATHE Level 3 - Batch 30', 'GBP', 30, 250, 7500, '2025-04-30', 'cash', '', '0000-00-00', 'mirshath', '2025-05-10 06:48:38'),
(99, 25, 'IFD (Business) - ATHE Level 3 - Batch 30', 'GBP', 20, 350, 7000, '2025-05-02', 'cash', '', '0000-00-00', 'mirshath', '2025-05-10 09:28:42');

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

--
-- Dumping data for table `payment_wise_info`
--

INSERT INTO `payment_wise_info` (`id`, `student_id`, `program_batch`, `installmentNumber`, `paymentAmount`, `paid_date`, `payment_type`, `bank_name`, `card_bank_deposit_dt`, `entered_date`, `entered_by`, `rcpt_number`, `status`, `cancellation_reason`, `cancelled_by`, `cancelled_at`) VALUES
(365, 23, 'Executive Certificate in Management - Batch 06', 'Initial Payment', 3000.00, '2025-05-06', 'cash', '', '0000-00-00', '2025-05-08 04:53:29', 'mirshath', '561733x', 'cancelled', 'sdasdsad', NULL, NULL),
(366, 23, 'Executive Certificate in Management - Batch 06', 'Initial Payment', 6000.00, '2025-05-07', 'cash', '', '0000-00-00', '2025-05-08 04:53:51', 'mirshath', '729243m', 'paid', NULL, NULL, NULL),
(367, 23, 'Executive Certificate in Management - Batch 06', 'installment_1', 30000.00, '2025-04-28', 'cash', '', '0000-00-00', '2025-05-08 04:55:30', 'mirshath', '678287a', 'paid', NULL, NULL, NULL),
(368, 87, 'Executive Certificate in Management - Batch 05', 'installment_1', 5000.00, '2025-05-01', 'cash', '', '0000-00-00', '2025-05-08 05:05:09', 'mirshath', '927770n', 'paid', NULL, NULL, NULL),
(369, 87, 'Executive Certificate in Management - Batch 05', 'Initial Payment', 9000.00, '2025-05-01', 'cash', '', '0000-00-00', '2025-05-08 05:05:09', 'mirshath', '927770n', 'paid', NULL, NULL, NULL),
(370, 87, 'Executive Certificate in Management - Batch 05', 'installment_1', 5000.00, '2025-04-30', 'cash', '', '0000-00-00', '2025-05-08 05:06:55', 'mirshath', '536378U', 'paid', NULL, NULL, NULL),
(371, 25, 'IFD (Business) - ATHE Level 3 - Batch 30', 'Initial Payment', 333.00, '2025-04-28', 'cash', '', '0000-00-00', '2025-05-10 06:51:46', 'mirshath', '334268V', 'paid', NULL, NULL, NULL),
(372, 25, 'IFD (Business) - ATHE Level 3 - Batch 30', 'installment_1', 1500.00, '2025-05-02', 'cash', '', '0000-00-00', '2025-05-10 09:28:42', 'mirshath', '858732J', 'paid', NULL, NULL, NULL),
(373, 25, 'IFD (Business) - ATHE Level 3 - Batch 30', 'installment_2', 200.00, '2025-05-02', 'cash', '', '0000-00-00', '2025-05-10 09:28:42', 'mirshath', '858732J', 'paid', NULL, NULL, NULL);

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

--
-- Dumping data for table `penalty_payments`
--

INSERT INTO `penalty_payments` (`id`, `student_id`, `student_registration_id`, `programme_code`, `batch_id`, `program_batch`, `penalty_type`, `penalty_amount`, `discount_type`, `discount_amount`, `final_amount`, `paid_date`, `payment_method`, `payment_reference`, `remarks`, `status`, `entered_by`, `created_at`, `updated_at`) VALUES
(1, '23', 'STU-IFD-B-B1-01-transed-ECM', '45', 26, 'Executive Certificate in Management - Batch 06', 'Late Fee', 500.00, 'Scholarship', 20.00, 480.00, '2025-05-14', 'Cash', NULL, NULL, 'cancelled', 'mirshath', '2025-05-14 12:14:02', NULL),
(2, '23', 'STU-IFD-B-B1-01-transed-ECM', '45', 26, 'Executive Certificate in Management - Batch 06', 'Missing Documents', 3500.00, 'Special Case', 250.00, 3250.00, '2025-05-14', 'Cash', NULL, NULL, 'paid', 'mirshath', '2025-05-14 15:47:15', NULL);

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

--
-- Dumping data for table `special_class_messages`
--

INSERT INTO `special_class_messages` (`id`, `program_code`, `batch_id`, `module_id`, `class_date`, `class_time`, `link`, `mail_subject`, `description`, `created_at`) VALUES
(3, '47', 29, 191, '2025-05-13', '14:03:00', 'https://www.bms.ac.lk/', 'sss', '<p>sadasda</p>', '2025-05-14 08:30:16');

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
(23, 'Mr', 'Mohamed', 'Mirshath', 'Minzar Mirshath', 'mohamed', '1999-01-19', 'Srilankan', 'Anuradhapura', 'Colombo', '0766158014', '0254904455', 'mohamed', '0756158014', 1, 1, '990190984V', '', 'mirshath.mmm@gmail.com', 'mirshath.mmm@gmail.com', '', '', '', 'Bachelors,Diploma', 1, 'transferred', 1, 't-ecm', NULL),
(24, 'Mr', 'Asela', 'Ayya', 'Asela Ayya', '', '2000-02-01', 'Srilankan', 'Kandy', 'Colombo', '0766158016', '0254904456', 'Avv', '0254896445', 1, 1, '6589978V', '', 'asela@gmail.com', 'mirshath.mmm@gmail.com', '', '', '', 'Bachelors,Masters,Diploma,IFD', 1, 'active', 0, '', NULL),
(25, 'Mr', 'Nafris', 'Mohamed', 'Nafris Mohamed', '', '2024-10-16', 'Srilankan', 'Anuradhapura', 'colombo', '324789', '764329', 'hhh', '9074326', 1, 1, '987775847V', '', 'nafris@gmailcom', 'mirmirsha123@gmail.com', '', '', '', 'Masters,Diploma,CBM', 1, 'active', 0, '', NULL),
(26, 'Mr', 'Dilshath', 'Mohamed', 'Dilshath mohamed', '', '2003-04-02', 'Srilankan', 'Trinco', 'Colombo', '5898806', '759868967', 'ssss', '78967698', 1, 1, '2564888V', '', 'Dilshath@gmail.com', 'mirshath.mmm@gmail.com', '', '', '', 'Bachelors,Masters,Diploma,CBM', 1, 'active', 0, '', NULL),
(83, 'Ms', 'ms', 'ms', 'ms', 'ms', '2025-01-15', 'Srilanka', 'srilanka', 'srilanka', '254904455', '254904455', 'moather', '254904455', 1, 1, '3335666V', '333555', 'aa@gmail.com', 'mirshath.mmm@gmail.com', 'Occup', 'Organ', 'previo ors', 'Masters,Diploma', 1, 'active', 0, '', NULL),
(84, 'Mr', 'Kada', 'nsd', 'ms', 'ms', '2025-01-01', 'Srilanka', 'srilanka', 'srilanka', '254904455', '254904455', 'moather', '254904455', 1, 1, '3335666V', '333555', 'aa@gmail.com', 'mirshath.mmm@gmail.com', 'Occup', 'Organ', 'previo ors', 'Masters,Diploma', 1, 'active', 0, '', NULL),
(85, 'Mr', 'www', 'www', 'www www', 'ww ww ww ww', '2025-01-02', 'Srilankan', 'www www ', 'www www', '0254904455', '0254900442', 'wwew', '025490445', 1, 1, '6589978V', '', 'yournumplz@gmail.com', 'yournumplz@gmail.com', '', '', '', 'Bachelors,Masters,Diploma', 1, 'active', 0, '', NULL),
(86, 'Mr', 'nazik', 'Mohammed', 'Nazik Mohammed', 'NAzik Mohammed', '2000-12-25', 'Srilankan', 'Colombo - 10 ', 'Colombo - 10 ', '0254904455', '0254904455', 'NO name', '0254904455', 1, 1, '99658665V', '', 'nazik@gmail.com', 'yournumplz@gmail.com', '', '', '', 'Bachelors,Masters,Diploma,CBM', 1, 'active', 0, '', NULL),
(87, 'Mr', 'Hasni', 'Nihar', 'Hasni Nihar', 'Hasni Nihar', '1996-05-24', 'Srilankan', 'Panadura , Colombo', 'Panadura , Colombo', '0254904455', '0254904455', 'Father', '0254904455', 1, 1, '96788945V', '', 'yournumplz@gmail.com', 'mirshath.mmm@gmail.com', 'Developer', '', 'Trainee', 'Bachelors,Masters,Diploma,CBM,AL', 1, 'active', 0, '', NULL),
(88, 'Ms', 'Vidhura', 'Ayya', 'Vidhura Vidhura', 'Vidhura', '0000-00-00', 'Srilanka', 'colombo', 'colombo', '254904455', '254904455', 'father', '254904455', 1, 1, '98665554V', '', 'vidhura@gmail.com', 'vid@bms.com', 'photography', '', '', '', 1, 'Active', 0, '', NULL),
(89, 'Ms', 'Ranidhu', 'Ranidhu', 'Ranidhu Suntec', 'Suntech Rani', '2025-01-09', 'Srilanka', 'colombo', 'colombo', '254904455', '254904455', 'Relations', '254904455', 1, 1, '458875545V', '', 'mirshath.mmm@gmail.com', 'mirshath.mmm@gmail.com', 'suntech', '', '', '', 1, 'active', 0, '', NULL),
(90, 'Dr', 'doc1', 'doc1', 'DOC Upul', 'DOC Upu', '2025-02-12', 'Srilankan', 'Demo', 'Demo', '0254904455', '0766158014', 'eee', '0254904455', 1, 1, '9901909587V', '', 'testing@gmail.com', 'yournumplz@gmail.com', '', '', '', 'Bachelors,Masters', 1, 'transferred', 1, 'tr_to_ifd_b', 'miru'),
(149, 'Ms', 'Jimy', 'Jimy', 'Jimmy Jim', 'Jimmy Jim', '0000-00-00', 'Srilanka', 'srilanka', 'srilanka', '17896542', '112569874', 'mother', '113698741', 1, 1, '993549871V', '', 'jimmy@gmail.com', 'jimms@gmail.com', 'dd', 'dd', 'dd', 'Masters,Diploma', 1, 'Active', 0, '', 'mirshath'),
(150, 'Ms', 'roshan', 'roshan', 'rshn mahathya', 'roshan mahathya', '0000-00-00', 'Srilanka', 'srilanka', 'srilanka', '17896542', '112569874', 'mother', '113698741', 1, 1, 'd9871V', '', 'rshnmy@gmail.com', 'rshnnms@gmail.com', '', '', '', 'Masters,Diploma', 1, 'Active', 0, '', 'mirshath'),
(151, 'Ms', 'Kasun', 'Rajapaksha', 'kasun rajapaksha', 'kasun rajapaksha', '2025-03-03', 'Srilanka', 'srilanka', 'srilanka', '17896555', '112569865', 'father', '113698554', 1, 1, '9907855V', '', 'mirshath.mmm@gmail.com', 'mirshath.mmm@gmail.com', '', '', '', 'Masters,Diploma', 1, 'Active', 0, '', 'mirshath'),
(152, 'Mr', 'Dhusanth', 'Dhusanth', 'Dhusanth DU', 'Dhusanth DU', '2025-04-15', 'Srilankan', 'mmm', 'Mihintale', '0766158014', '0254904455', 'Mirshath', '', 1, 1, '990190984V', '', 'sdsd.m@cgs.lk', 'sdsd.m@cgs.lk', '', '', '', 'Bachelors,Masters,Diploma', 1, 'active', 0, '', 'mirshath'),
(153, 'Mr', 'AAAA', 'AAA', 'AAAA', 'AAA', '0000-00-00', 'Srilanka', 'srilanka', 'srilanka', '123456789', '123456789', 'mother', '123456789', 1, 1, '12346789V', '', 'aaaa@gmail.com', 'aaaa@gmail.com', 'aa', 'aa', 'aa', 'Masters,Diploma', 1, 'Active', 0, '', 'miru'),
(154, 'Ms', 'BBBB', 'BBBB', 'BBBBB', 'BBBBB', '0000-00-00', 'Srilanka', 'srilanka', 'srilanka', '77712346', '77712346', 'mother', '77712346', 1, 1, '654987321v', '', 'bbbb@gmail.com', 'bbbb@gmail.com', '', '', '', 'Masters,Diploma', 1, 'Active', 0, '', 'miru'),
(155, 'Mr', 'ss', 'ss', 'ss', 'ss', '2025-05-07', 'Srilankan', 'ss, ss, ss, ss', 'ss, ss, ss, ss', '0254904455', '0254904455', 'vv', '0766158632', 1, 1, '936520984V', '', 'ss@gmail.com', 'ss@gmail.com', '', '', '', 'Bachelors,Masters,Diploma,CBM', 1, 'active', 0, '', 'miru');

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
-- Dumping data for table `student_results`
--

INSERT INTO `student_results` (`id`, `student_id`, `student_registration_id`, `program_id`, `batch_id`, `module_id`, `main_component_id`, `sub_component_id`, `result`, `resit_result_1`, `resit_result_2`, `resit_result_3`, `resit_result_4`, `full_marks`, `converted_marks`, `hd_full_marks`, `hd_converted_marks`, `hd_grade`, `hd_resit1_full_marks`, `hd_resit1_converted_marks`, `hd_resit1_grade`, `hd_resit2_full_marks`, `hd_resit2_converted_marks`, `hd_resit2_grade`, `hd_resit3_full_marks`, `hd_resit3_converted_marks`, `hd_resit3_grade`, `entered_by`, `created_at`) VALUES
(652, 25, 'STU-222222', 39, 12, 205, 35, 0, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 05:14:23'),
(653, 90, 'STU-DOC1_st_IFD_new', 39, 12, 205, 35, 0, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 05:14:24'),
(654, 149, 'Gimmi-IFD-st-4', 39, 12, 205, 35, 0, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 05:14:24'),
(655, 150, 'rsn-mhthya-IFD-st-5', 39, 12, 205, 35, 0, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 05:14:24'),
(656, 25, 'STU-222222', 39, 12, 205, 40, 0, 'merit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 05:14:33'),
(657, 90, 'STU-DOC1_st_IFD_new', 39, 12, 205, 40, 0, 'pass', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 05:14:33'),
(658, 149, 'Gimmi-IFD-st-4', 39, 12, 205, 40, 0, 'merit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 05:14:33'),
(659, 150, 'rsn-mhthya-IFD-st-5', 39, 12, 205, 40, 0, 'pass', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 05:14:33'),
(660, 26, 'sssss', 45, 25, 177, 44, 0, NULL, NULL, NULL, NULL, NULL, NULL, '40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:00:23'),
(661, 87, 'ecm', 45, 25, 177, 44, 0, NULL, NULL, NULL, NULL, NULL, NULL, '40', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:00:23'),
(662, 26, 'sssss', 45, 25, 177, 43, 0, NULL, NULL, NULL, NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:00:29'),
(663, 87, 'ecm', 45, 25, 177, 43, 0, NULL, NULL, NULL, NULL, NULL, NULL, '50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:00:29'),
(664, 83, 'STU-GDM-01-MS', 46, 22, 184, 55, 0, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:12:43'),
(665, 83, 'STU-GDM-01-MS', 46, 22, 184, 56, 0, 'pass', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:12:49'),
(666, 24, 'STU-BIO-MED-asela', 42, 31, 198, 67, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100, 40, 'Distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:35:10'),
(667, 85, 'STU-ww-BIO-MED-st2', 42, 31, 198, 67, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 50, 20, 'Pass', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:35:10'),
(668, 24, 'STU-BIO-MED-asela', 42, 31, 198, 68, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 100, 60, 'Distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:35:23'),
(669, 85, 'STU-ww-BIO-MED-st2', 42, 31, 198, 68, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 30, 18, 'Pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-30 06:35:23');

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

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `programme_id`, `programme_name`, `batch_id`, `batch_name`, `module_id`, `module_name`, `lecturer_id`, `lecturer_name`, `day`, `start_time`, `end_time`, `start_date`, `end_date`, `comp1_deadline`, `comp2_deadline`, `created_by`, `created_at`, `updated_by`, `updated_at`, `status`) VALUES
(1, '39', '', 12, 'Batch 30', 205, 'Accounting Fundamentals', 45, 'Mr noname 3', 'Monday', '12:25:00', '16:29:00', '2025-05-05', '2025-05-15', '2025-05-12', '2025-05-29', 'mirshath', '2025-05-14 12:25:48', 'mirshath', '2025-05-14 12:26:28', 'cancelled'),
(2, '39', '', 12, 'Batch 30', 205, 'Accounting Fundamentals', 45, 'Mr noname 3', 'Thursday', '14:29:00', '16:31:00', '2025-05-12', '2025-05-22', '2025-05-19', '2025-05-23', 'mirshath', '2025-05-14 12:27:26', 'mirshath', '2025-05-14 12:28:10', 'cancelled'),
(3, '39', 'IFD (Business) - ATHE Level 3', 12, 'Batch 30', 205, 'Accounting Fundamentals', 45, 'Mr noname 3', 'Wednesday', '13:29:00', '18:34:00', '2025-04-29', '2025-05-23', '2025-05-19', '2025-05-21', 'mirshath', '2025-05-14 12:29:07', NULL, NULL, 'active');

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

--
-- Dumping data for table `total_mod_rslt`
--

INSERT INTO `total_mod_rslt` (`id`, `std_id`, `std_reg_no`, `prog_id`, `batch_id`, `module_id`, `year_id`, `semester_id`, `module_gpa_value`, `ex1_total_rslt`, `ex2_total_rslt`, `total`, `grades`, `GV`, `CGP`) VALUES
(126, 89, 'STU-BBM-ST-RANIDHU', 47, 29, 189, 17, 1, 5.00, 90.00, 90.00, 90.00, 'A+', 4.00, 20.00),
(127, 151, 'kasun-raja-BBM-st-1', 47, 29, 189, 17, 1, 5.00, 90.00, 90.00, 90.00, 'A+', 4.00, 20.00);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `additional_fee_payments`
--
ALTER TABLE `additional_fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `add_payment_plan_table`
--
ALTER TABLE `add_payment_plan_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=281;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=240;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=470;

--
-- AUTO_INCREMENT for table `bbm_final_results_tbl`
--
ALTER TABLE `bbm_final_results_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=445;

--
-- AUTO_INCREMENT for table `bbm_result_details`
--
ALTER TABLE `bbm_result_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=955;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1636;

--
-- AUTO_INCREMENT for table `gpa_calculate_tbl`
--
ALTER TABLE `gpa_calculate_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `grade_table`
--
ALTER TABLE `grade_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `installment_details_table`
--
ALTER TABLE `installment_details_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=613;

--
-- AUTO_INCREMENT for table `installment_payment_table`
--
ALTER TABLE `installment_payment_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=254;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `leads_table`
--
ALTER TABLE `leads_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `lecturer_table`
--
ALTER TABLE `lecturer_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_uni_fee`
--
ALTER TABLE `payment_uni_fee`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `payment_wise_info`
--
ALTER TABLE `payment_wise_info`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=374;

--
-- AUTO_INCREMENT for table `penalty_payments`
--
ALTER TABLE `penalty_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `status_table`
--
ALTER TABLE `status_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_code` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=157;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `student_results`
--
ALTER TABLE `student_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=670;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `total_mod_rslt`
--
ALTER TABLE `total_mod_rslt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=128;

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

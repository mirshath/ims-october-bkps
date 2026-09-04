-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2026 at 01:23 PM
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
-- Database: `ims_localtet`
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lkr_reg_date` date DEFAULT NULL,
  `lkr_reg_due_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `add_payment_plan_table`
--

INSERT INTO `add_payment_plan_table` (`id`, `student_id`, `programme_batch`, `university_fee_LKR`, `courseFeeLKR_total`, `course_fee_LKR`, `course_fee_type_LKR`, `installment_month_LKR`, `registration_fee_LKR`, `university_fee_GBP`, `courseFeeGBP_total`, `course_fee_GBP`, `course_fee_type_GBP`, `installment_month_GBP`, `registration_fee_GBP`, `university_fee_USD`, `courseFeeUSD_total`, `course_fee_USD`, `course_fee_type_USD`, `installment_month_USD`, `registration_fee_USD`, `entered_by`, `created_at`, `lkr_reg_date`, `lkr_reg_due_date`) VALUES
(1, 449, 'Graduate Diploma in Management (Level 6) - Batch 76', NULL, 260000, 200000, 'installment', 2, 60000, 230, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'admin', '2026-08-13 05:29:52', '2026-09-25', '2026-10-02');

--
-- Triggers `add_payment_plan_table`
--
DELIMITER $$
CREATE TRIGGER `trg_app_insert` AFTER INSERT ON `add_payment_plan_table` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('add_payment_plan_table', NEW.id, 'insert',
            CONCAT('New payment plan created: student_id=', NEW.student_id));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_app_update` AFTER UPDATE ON `add_payment_plan_table` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('add_payment_plan_table', NEW.id, 'update',
            CONCAT('Payment plan updated: student_id=', NEW.student_id));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `title` varchar(20) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `username` varchar(255) NOT NULL,
  `admin_email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin',
  `registered_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `title`, `full_name`, `username`, `admin_email`, `password`, `role`, `registered_date`) VALUES
(4, 'Mr.', 'mirshath Mohamed', 'mirshath', 'mirshath@gmail.com', '$2y$10$c8RVl068wFSo4YHcuu1qQeBWjeoP7rRMJefSxtv2uKJZ8egfRVI8K', 'manager', '2025-02-13 08:20:53'),
(14, 'Mr.', 'hasni', 'hasni', 'hasni@gmail.com', '$2y$10$bRqn1sdkS3xLqcdNgXRZuuSUxhn70qMFxNpjezBgP99SFEPyf1hIi', 'data_enter', '2025-05-11 03:35:44'),
(22, 'Mr.', 'admin', 'admin', 'admin@gmail.com', '$2y$10$2g4NzNAm5i23pHeWJFAyX.3bZZWFlP.Y.cRZvosKfsPdvA8e8R2JC', 'super_admin', '2025-09-23 07:19:19'),
(25, 'Dr.', 'Lecture 01', 'lecture', 'lecure@gmail.com', '$2y$10$6SbpbfhbfYyM.CA//C/oLO2gcJXwQC5UH5bCSq9VhzKZlR1Gr1IzK', 'lecture', '2026-06-11 06:51:25'),
(26, 'Ms.', 'lexture 02', 'lecture2', 'lecture2@gmail.com', '$2y$10$B.p/7APq6JnGeHohbu4jwuLQDuAga5aurpo5.EQcxGMNXpuJn8fzG', 'lecture', '2026-06-11 06:52:24');

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
(184, 205, 40, NULL, NULL, '2025-04-30 05:12:12'),
(185, 206, 68, NULL, NULL, '2025-05-19 08:59:27'),
(186, 207, 77, NULL, NULL, '2025-06-13 06:58:26'),
(187, 207, 78, NULL, NULL, '2025-06-13 06:58:35'),
(188, 209, 77, NULL, NULL, '2025-06-13 08:05:25'),
(189, 209, 78, NULL, NULL, '2025-06-13 08:05:33'),
(194, 211, 80, NULL, NULL, '2025-06-13 10:58:50'),
(195, 211, 79, NULL, NULL, '2025-06-13 10:59:14'),
(196, 212, 81, NULL, NULL, '2025-06-16 10:19:44'),
(197, 208, 64, NULL, NULL, '2025-06-23 03:56:11'),
(198, 208, 82, NULL, NULL, '2025-06-23 03:56:25'),
(199, 208, 83, NULL, NULL, '2025-06-23 03:57:15'),
(200, 206, 63, NULL, NULL, '2026-01-26 08:51:20');

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
  `student_registration_id` varchar(50) DEFAULT NULL,
  `new_student_registration_id` varchar(255) DEFAULT NULL,
  `elective_subs` text DEFAULT NULL,
  `compulsory_sub` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'active',
  `dm_remark` varchar(100) NOT NULL,
  `entered_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allocate_programme`
--

INSERT INTO `allocate_programme` (`id`, `student_code`, `university_id`, `programme_code`, `batch_id`, `student_registration_id`, `new_student_registration_id`, `elective_subs`, `compulsory_sub`, `status`, `dm_remark`, `entered_by`) VALUES
(532, 449, 1, 46, 22, '476052501', '25000001', '', 'Leadership and Management in a Digital Economy,Financial Principles and Techniques ,Research Methods for Managers', 'active', '', 'admin'),
(533, 450, 1, 46, 22, '476052502', '25000002', '', 'Leadership and Management in a Digital Economy,Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers', 'completed', 'FFF', 'mirshath'),
(534, 451, 1, 46, 22, '476052503', '25000003', '', 'Leadership and Management in a Digital Economy,Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers', 'active', '', 'mirshath'),
(535, 452, 1, 46, 22, '476052504', NULL, '', 'Leadership and Management in a Digital Economy,Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers', 'active', '', 'admin'),
(536, 453, 1, 46, 22, '476052505', NULL, '', 'Leadership and Management in a Digital Economy,Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers', 'active', '', 'admin'),
(537, 454, 1, 46, 22, '476052506', NULL, '', 'Leadership and Management in a Digital Economy,Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers', 'active', '', 'admin'),
(538, 455, 1, 46, 22, '476052507', NULL, '', 'Leadership and Management in a Digital Economy,Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers', 'active', '', 'admin'),
(539, 456, 1, 46, 23, NULL, NULL, '', 'Leadership and Management in a Digital Economy,Human Resource Management,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers,Lec add module', 'active', '', 'admin'),
(546, 463, 1, 63, 42, NULL, NULL, '', 'Mathematics and Statistics', 'active', '', 'admin'),
(547, 464, 1, 41, 21, NULL, NULL, '', 'Business and the Business Environment', 'active', '', 'admin'),
(551, 469, 1, 46, 35, 'AAA-GDM-84-S1', NULL, 'IFD MODULE 2 ELECT', 'Financial Principles and Techniques ,Human Resource Management,Leadership and Management in a Digital Economy,Lec add module,Marketing and Digital Strategy,Research Methods for Managers', 'active', '', 'admin'),
(552, 470, 1, 46, 35, 'BBB-GDM-84-S2', NULL, '', 'Leadership and Management in a Digital Economy,Marketing and Digital Strategy,Financial Principles and Techniques ,Research Methods for Managers,Lec add module', 'active', '', 'admin'),
(553, 471, 1, 41, 21, 'AAA-GDM-84-S1', NULL, 'IFD MODULE 2 ELECT', 'Business and the Business Environment', 'active', '', 'admin'),
(554, 472, 1, 41, 21, 'BBB-GDM-84-S2', NULL, 'IFD MODULE 2 ELECT', 'Business and the Business Environment', 'active', '', 'admin'),
(555, 473, 1, 41, 21, NULL, NULL, '', 'Business and the Business Environment', 'active', '', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `assesment_document_send_email_log`
--

CREATE TABLE `assesment_document_send_email_log` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_registration_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('sent','failed','not_sent') NOT NULL DEFAULT 'not_sent',
  `sent_date` datetime DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
  `assessment_date` datetime DEFAULT NULL,
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
(1, 39, 13, 205, 'N/A', 'N/A', '2026-01-02 20:43:00', '<p>IFD B 1</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2025-12-19 10:13:45', '2025-12-19 10:13:45', 35, NULL, 'mirshath'),
(2, 39, 13, 205, 'N/A', 'N/A', '2026-01-02 20:43:00', '<p>IFD B 2</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2025-12-19 10:13:52', '2025-12-19 10:13:52', 40, NULL, 'mirshath'),
(3, 46, 22, 184, 'N/A', 'N/A', '2026-01-10 11:01:00', '<p>GDM B76 1-2&nbsp;</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2026-01-10 06:27:35', '2026-01-10 06:27:35', 56, NULL, 'mirshath'),
(4, 46, 22, 184, 'N/A', 'N/A', '2026-01-10 11:01:00', '<p>GDM B76 3-4</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2026-01-10 06:27:43', '2026-01-10 06:27:43', 55, NULL, 'mirshath'),
(5, 39, 12, 205, 'N/A', 'N/A', '2026-01-19 22:50:00', '<p>IFD ACC L01 &amp; L02&nbsp; -&nbsp;</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2026-01-19 05:16:31', '2026-01-19 05:16:31', 35, NULL, 'mirshath'),
(6, 39, 12, 205, 'N/A', 'N/A', '2026-01-19 22:50:00', '<p>IFD ACC L03 &amp; L04&nbsp; -&nbsp;</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2026-01-19 05:16:41', '2026-01-19 05:16:41', 40, NULL, 'mirshath'),
(7, 43, 41, 209, 'N/A', 'Semester I', '2026-01-06 16:40:00', '<p>DDDDDD</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2026-01-23 11:05:29', '2026-01-23 11:05:29', 77, NULL, 'hasni'),
(8, 43, 41, 209, 'N/A', 'Semester I', '2026-01-06 16:40:00', '<p>DDDDDD@@@@@@@@@@</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2026-01-23 11:05:34', '2026-01-23 11:05:34', 78, NULL, 'hasni'),
(9, 57, 36, 206, 'N/A', 'N/A', '2026-01-29 14:26:00', '<p>SE B1 SM1 Assignment</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2026-01-26 08:52:16', '2026-01-26 08:52:16', 68, NULL, 'mirshath'),
(10, 57, 36, 206, 'N/A', 'N/A', '2026-01-29 14:26:00', '<p>SE B1 SM1 END SEMESTER</p>\r\n', NULL, NULL, NULL, NULL, NULL, '2026-01-26 08:52:26', '2026-01-26 08:52:26', 63, NULL, 'mirshath');

--
-- Triggers `assessments`
--
DELIMITER $$
CREATE TRIGGER `prevent_assessments_delete` BEFORE DELETE ON `assessments` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = '❌ Assessment records cannot be deleted.';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_assess_insert` AFTER INSERT ON `assessments` FOR EACH ROW BEGIN
    INSERT INTO `notifications` (`table_name`, `row_id`, `action`, `message`)
    VALUES (
        'assessments',
        NEW.id,
        'insert',
        CONCAT(
            '? New assessment created (ID=', NEW.id, ') by ',
            IFNULL(NEW.entered_by, 'Unknown')
        )
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_assess_update` AFTER UPDATE ON `assessments` FOR EACH ROW BEGIN
    INSERT INTO `notifications` (`table_name`, `row_id`, `action`, `message`)
    VALUES (
        'assessments',
        NEW.id,
        'update',
        CONCAT(
            '? Assessment updated (ID=', NEW.id, ') by ',
            IFNULL(NEW.entered_by, 'Unknown')
        )
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_email_log`
--

CREATE TABLE `assessment_email_log` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('sent','failed','not_sent') NOT NULL DEFAULT 'not_sent',
  `sent_date` datetime DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assessment_email_log`
--

INSERT INTO `assessment_email_log` (`id`, `assessment_id`, `student_id`, `email`, `status`, `sent_date`, `sent_by`, `error_message`) VALUES
(1, 9, 312, 'yournumplz@gmail.com', 'sent', '2026-01-26 14:24:33', 'mirshath', NULL),
(2, 3, 451, 'yournumplz@gmail.com', 'sent', '2026-07-16 09:41:55', 'admin', NULL),
(3, 3, 452, 'xupeqyges@mailinator.com', 'sent', '2026-07-16 09:41:57', 'admin', NULL),
(4, 3, 453, 'fetex@mailinator.com', 'sent', '2026-07-16 09:42:00', 'admin', NULL),
(5, 3, 454, 'fezahyduri@mailinator.com', 'sent', '2026-07-16 09:42:02', 'admin', NULL),
(6, 3, 455, 'kesagyh@mailinator.com', 'sent', '2026-07-16 09:42:05', 'admin', NULL);

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
(74, 'b1', 'Continuous Assessment', '40%'),
(76, 'eee', 'eee', '10'),
(77, '00p1', 'P 1 : Assignment', '50%'),
(78, 'hd_biomed', 'P 2 : Exam', '50%'),
(79, 'btech1s', 'Written Assignment I - (LO1 & LO2)', ''),
(80, 'btech1', 'Case Stdy Based Written Assignment - (LO3 & LO4)', ''),
(81, 'HD1', 'P 1 : Exam - LO: 1,2', ''),
(82, 'comp3', 'Main Component 3', '25%'),
(83, 'comp2', 'Main Component 2', '25%');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_code` varchar(50) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `module_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `attendance_time` time NOT NULL,
  `status` enum('present','absent','late') NOT NULL DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `entered_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `class_counted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch_swap_table`
--

CREATE TABLE `batch_swap_table` (
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
  `entered_by` varchar(50) DEFAULT NULL,
  `OLD_registration_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `intake_end_date` date DEFAULT NULL,
  `batch_no` varchar(10) DEFAULT NULL,
  `intake_no` varchar(10) DEFAULT NULL,
  `year_no` int(10) DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `batch_intake` varchar(255) DEFAULT NULL,
  `batch_limit` int(11) DEFAULT NULL,
  `attendance` varchar(255) DEFAULT NULL,
  `awarded_by` varchar(255) DEFAULT NULL,
  `qualification_level` varchar(255) DEFAULT NULL,
  `recognized_by` varchar(255) DEFAULT NULL,
  `accredited_by` varchar(255) DEFAULT NULL,
  `batch_hide_active` varchar(255) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch_table`
--

INSERT INTO `batch_table` (`id`, `batch_name`, `university`, `programme`, `year_batch_code`, `intake_date`, `intake_end_date`, `batch_no`, `intake_no`, `year_no`, `end_date`, `batch_intake`, `batch_limit`, `attendance`, `awarded_by`, `qualification_level`, `recognized_by`, `accredited_by`, `batch_hide_active`, `created_at`, `updated_at`) VALUES
(12, 'Batch 30', 1, 39, '2024', '2025-01-09', NULL, '10', '12', 25, '2025-01-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:48:35', '2025-08-26 04:16:41'),
(13, 'Batch 31', 1, 39, '2024', '2025-01-08', NULL, '10', '20', 26, '2025-01-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:49:02', '2025-08-26 04:16:56'),
(17, 'Batch 14', 1, 41, '2023', '2025-01-07', NULL, NULL, NULL, NULL, '2025-01-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:51:12', '2025-01-18 10:51:12'),
(18, 'Batch 15', 1, 41, '2023', '2025-01-13', NULL, NULL, NULL, NULL, '2025-01-24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:51:23', '2025-01-18 10:51:23'),
(19, 'Batch 16', 1, 41, '2023', '2025-01-05', NULL, NULL, NULL, NULL, '2025-01-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:51:50', '2025-01-18 10:51:50'),
(20, 'Batch 17', 1, 41, '2024', '2025-01-07', NULL, NULL, NULL, NULL, '2025-01-30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:52:04', '2025-01-18 10:52:04'),
(21, 'Batch 18', 1, 41, '2024', '2025-01-09', NULL, '87', '07', 28, '2025-01-30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:52:18', '2025-07-24 10:23:24'),
(22, 'Batch 76', 1, 46, '2023', '2025-01-13', '2026-08-03', '76', '05', 25, '2025-01-31', 'May (saturday)', 50, 'Full Time', '', '', '', '', 'active', '2025-01-18 10:53:32', '2026-08-11 03:01:58'),
(23, 'Batch 77', 1, 46, '2023', '2025-01-08', NULL, '77', '08', 25, '2025-01-31', 'August (sunday)', 200, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:53:45', '2026-01-10 03:02:15'),
(25, 'Batch 05', 1, 45, '2023', '2025-01-13', NULL, NULL, NULL, NULL, '2025-01-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:54:42', '2025-01-18 10:54:42'),
(26, 'Batch 06', 1, 45, '2023', '2025-01-14', NULL, NULL, NULL, NULL, '2025-01-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:54:52', '2025-01-18 10:54:52'),
(28, 'Batch 07', 1, 45, '2023', '2025-01-06', '2025-01-10', '10', '10', 2025, '2025-01-24', 'ECM October (Sunday)', 50, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:55:29', '2026-01-10 06:17:58'),
(29, 'Batch 01', 1, 47, '2023', '2025-01-14', NULL, NULL, NULL, NULL, '2025-01-30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:56:11', '2025-01-18 10:56:11'),
(30, 'Batch 02', 1, 47, '2024', '2025-01-05', NULL, NULL, NULL, NULL, '2025-01-30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-01-18 10:56:23', '2025-01-18 10:56:23'),
(31, 'Batch 29', 1, 42, '2025', '2025-02-07', NULL, NULL, NULL, NULL, '2025-02-28', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-02-05 10:38:00', '2025-02-05 10:38:00'),
(34, 'Batch 84', 1, 46, '2025', '2025-05-16', '2026-01-29', '01', '01', 2026, '2025-05-17', 'september (saturday)', 3, 'Full Time', 'BMS Campus & ATHE - U', 'Level 06', 'Recognized BY', 'Accredited BY', 'active', '2025-05-16 04:49:28', '2026-07-15 02:41:50'),
(35, 'Batch 85', 1, 46, '2025', '2025-05-16', '2026-01-28', '40', '10', 26, '2025-05-17', 'September (Sunday)', 20, 'Part Time', 'BMS Campus ATHE - UK', 'Level 06 (UK)', 'Recognized BY BMS CITY CAMPUS', 'Accredited BY BMS CITY CAMPUS', 'inactive', '2025-05-16 04:49:55', '2026-07-15 04:50:28'),
(36, 'SE Batch 01', 1, 57, '2022', '2025-05-22', '2026-01-29', '87', '7', 250, '2025-06-07', 'saturday', 250, 'Full Time', 'Awa', 'Ql', 'Recognized BY BMS CITY CAMPUS', 'Accredited BY BMS CITY CAMPUS', NULL, '2025-05-19 08:57:54', '2026-01-28 00:07:23'),
(37, 'Batch 13', 1, 45, '2025', '2025-05-23', '2025-05-26', '02', '02', 2026, '2025-05-23', 'ECM February (saturday)', 60, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-22 06:24:20', '2026-01-10 10:38:48'),
(38, 'Batch 14', 1, 45, '2025', '2025-05-29', '2026-01-25', '14', '05', 25, '2025-06-07', 'ECM May  (saturday)', 20, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-22 06:24:59', '2026-01-10 06:28:59'),
(41, 'Batch 44', 1, 43, '25', '2025-06-01', '2026-01-30', '06', '06', 26, '2025-06-26', 'July (saturday)', 30, 'HD Part Time', 'BMS Campus  ATHE - UKKK', 'Level 07  (USA)', 'BMS CITY CAMPUS', 'BMS CITY CAMPUS', NULL, '2025-06-13 09:30:14', '2026-01-26 03:32:27'),
(42, 'Batch 133', 1, 63, '2022', '2025-06-03', NULL, '05', '05', 26, '2025-07-04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-16 10:20:49', '2025-08-26 03:48:49'),
(43, 'qqq', 1, 57, '222q', '2025-07-19', NULL, '24', '42', 25, '2025-07-19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-19 07:38:08', '2025-07-19 07:38:39'),
(44, 'Garrett Turner', 1, 46, '', '2005-03-24', '2010-05-22', 'Eu quidem ', 'Provident ', 1991, '1998-12-27', 'Id similique non nos', 36, 'Full Time', 'Exercitation nesciun', 'Do quis sed ut dolor', 'Autem expedita nostr', 'Dolor rerum ratione ', NULL, '2026-01-28 00:10:23', '2026-01-28 00:10:23');

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
-- Table structure for table `class_allocation`
--

CREATE TABLE `class_allocation` (
  `id` int(11) NOT NULL,
  `programme_code` varchar(50) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `total_classes` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'aa', 'Mr', 'aaa', 'aa@gmail.com', '$2y$10$f/XtMbI4/M7lG6vmTqSbR.KEk4qdEvigArYclZhKG4DnMAUsMEkBW');

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
(3, 'USD', 'United States Dollar', 'USD', '$'),
(8, 'd', 'd', 'd', 'd');

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
(7, 'Dcode1', 'Testing Decision', 'testion Updated');

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
(6, 'absent'),
(8, 'not submitted'),
(9, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `email_sending_log`
--

CREATE TABLE `email_sending_log` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `main_component_id` int(11) DEFAULT NULL,
  `sub_component_id` int(11) DEFAULT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `sent_date` datetime DEFAULT NULL,
  `sent_by` varchar(50) DEFAULT NULL,
  `status` enum('sent','failed') DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `emailed_result` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_sending_log`
--

INSERT INTO `email_sending_log` (`id`, `student_id`, `program_id`, `batch_id`, `module_id`, `main_component_id`, `sub_component_id`, `email_sent`, `sent_date`, `sent_by`, `status`, `error_message`, `emailed_result`) VALUES
(1, 451, 46, 22, 184, NULL, NULL, 1, '2026-07-16 10:10:09', 'admin', 'sent', NULL, 'pass'),
(2, 452, 46, 22, 184, NULL, NULL, 1, '2026-07-16 10:10:10', 'admin', 'sent', NULL, 'withheld'),
(3, 453, 46, 22, 184, NULL, NULL, 1, '2026-07-16 10:10:11', 'admin', 'sent', NULL, 'withheld'),
(4, 454, 46, 22, 184, NULL, NULL, 1, '2026-07-16 10:10:12', 'admin', 'sent', NULL, 'merit'),
(5, 455, 46, 22, 184, NULL, NULL, 1, '2026-07-16 10:10:14', 'admin', 'sent', NULL, 'merit'),
(6, 450, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:11:00', 'admin', 'sent', NULL, ''),
(7, 451, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:17:42', 'admin', 'sent', NULL, 'merit'),
(8, 452, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:17:43', 'admin', 'sent', NULL, 'pass'),
(9, 451, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:09', 'admin', 'sent', NULL, 'withheld'),
(10, 452, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:10', 'admin', 'sent', NULL, 'withheld'),
(11, 453, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:11', 'admin', 'sent', NULL, 'withheld'),
(12, 454, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:12', 'admin', 'sent', NULL, 'merit'),
(13, 455, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:13', 'admin', 'sent', NULL, 'merit'),
(14, 451, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:32', 'admin', 'sent', NULL, 'merit'),
(15, 452, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:33', 'admin', 'sent', NULL, 'withheld'),
(16, 453, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:34', 'admin', 'sent', NULL, 'withheld'),
(17, 454, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:35', 'admin', 'sent', NULL, 'merit'),
(18, 455, 46, 22, 184, NULL, NULL, 1, '2026-08-12 16:19:36', 'admin', 'sent', NULL, 'merit'),
(19, 451, 46, 22, 184, NULL, NULL, 0, '2026-08-13 15:16:49', 'admin', 'failed', 'SMTP Error: Could not authenticate.', NULL),
(20, 452, 46, 22, 184, NULL, NULL, 0, '2026-08-13 15:16:55', 'admin', 'failed', 'SMTP Error: Could not authenticate.', NULL),
(21, 451, 46, 22, 184, NULL, NULL, 1, '2026-08-13 15:17:33', 'admin', 'sent', NULL, 'merit'),
(22, 452, 46, 22, 184, NULL, NULL, 1, '2026-08-13 15:17:34', 'admin', 'sent', NULL, 'pass'),
(23, 451, 46, 22, 184, 56, 0, 1, '2026-08-13 15:40:07', 'admin', 'sent', NULL, 'Distinction'),
(24, 451, 46, 22, 184, NULL, NULL, 1, '2026-08-15 09:53:13', 'admin', 'sent', NULL, 'pass'),
(25, 452, 46, 22, 184, NULL, NULL, 1, '2026-08-15 09:53:14', 'admin', 'sent', NULL, 'Pending');

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
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `program_id` varchar(50) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `program_name` varchar(255) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `lecturer_name` varchar(255) NOT NULL,
  `presentation` enum('Excellent','Good','Average','Poor') NOT NULL,
  `preparation` enum('Excellent','Good','Average','Poor') NOT NULL,
  `syllabus_coverage` enum('Excellent','Good','Average','Poor') NOT NULL,
  `knowledge_subject` enum('Excellent','Good','Average','Poor') NOT NULL,
  `question_discussion` enum('Excellent','Good','Average','Poor') NOT NULL,
  `interaction_students` enum('Excellent','Good','Average','Poor') NOT NULL,
  `punctuality` enum('Excellent','Good','Average','Poor') NOT NULL,
  `overall` enum('Excellent','Good','Average','Poor') NOT NULL,
  `comments` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `feedback`
--
DELIMITER $$
CREATE TRIGGER `prevent_feedback_delete` BEFORE DELETE ON `feedback` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Deletion of feedback records is not allowed';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_form_fields`
--

CREATE TABLE `feedback_form_fields` (
  `id` int(11) NOT NULL,
  `link_id` int(11) NOT NULL COMMENT 'FK -> feedback_links.id',
  `field_type` enum('rating','comment') NOT NULL DEFAULT 'rating',
  `field_label` varchar(255) NOT NULL COMMENT 'e.g. Presentation, Preparation, Additional Comments',
  `field_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_form_fields`
--

INSERT INTO `feedback_form_fields` (`id`, `link_id`, `field_type`, `field_label`, `field_order`, `created_at`) VALUES
(23, 9, 'rating', 'Preparation', 0, '2026-08-04 11:05:40'),
(24, 9, 'rating', 'Knowledge on the Subject', 1, '2026-08-04 11:05:40'),
(25, 9, 'rating', 'Question Discussion', 2, '2026-08-04 11:05:40'),
(26, 9, 'comment', 'Additional Comments', 3, '2026-08-04 11:05:40'),
(27, 10, 'rating', 'Presentation', 0, '2026-08-04 11:07:42'),
(28, 10, 'comment', 'Additional Comments', 1, '2026-08-04 11:07:42');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_links`
--

CREATE TABLE `feedback_links` (
  `id` int(11) NOT NULL,
  `link_id` varchar(255) NOT NULL,
  `programme_id` varchar(255) NOT NULL,
  `batch_id` varchar(255) NOT NULL,
  `module_id` varchar(255) NOT NULL,
  `lecturer_id` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1,
  `created_by` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_links`
--

INSERT INTO `feedback_links` (`id`, `link_id`, `programme_id`, `batch_id`, `module_id`, `lecturer_id`, `created_at`, `active`, `created_by`) VALUES
(9, 'graduate-diploma-in-management-level-6-batch-76-lec-add-module-lecture-01-f95ad', '46', '22', '215', '11', '2026-08-04 11:05:40', 0, 'admin'),
(10, 'graduate-diploma-in-management-level-6-batch-85-lec-add-module-lecture-01-700d2', '46', '35', '215', '11', '2026-08-04 11:07:42', 1, 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_submissions`
--

CREATE TABLE `feedback_submissions` (
  `id` int(11) NOT NULL,
  `link_id` int(11) NOT NULL COMMENT 'FK -> feedback_links.id',
  `program_id` varchar(50) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `module_id` int(11) DEFAULT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `program_name` varchar(255) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `lecturer_name` varchar(255) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_submissions`
--

INSERT INTO `feedback_submissions` (`id`, `link_id`, `program_id`, `batch_id`, `module_id`, `lecturer_id`, `program_name`, `module_name`, `lecturer_name`, `submitted_at`) VALUES
(5, 9, '46', 22, 215, 11, 'Graduate Diploma in Management (Level 6)', 'Lec add module', 'Lecture 01', '2026-08-04 11:06:19'),
(6, 10, '46', 35, 215, 11, 'Graduate Diploma in Management (Level 6)', 'Lec add module', 'Lecture 01', '2026-08-04 11:13:40');

-- --------------------------------------------------------

--
-- Table structure for table `feedback_submission_answers`
--

CREATE TABLE `feedback_submission_answers` (
  `id` int(11) NOT NULL,
  `submission_id` int(11) NOT NULL COMMENT 'FK -> feedback_submissions.id',
  `field_id` int(11) NOT NULL COMMENT 'FK -> feedback_form_fields.id',
  `field_label` varchar(255) NOT NULL COMMENT 'snapshot of the label at submission time',
  `field_type` enum('rating','comment') NOT NULL,
  `answer_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback_submission_answers`
--

INSERT INTO `feedback_submission_answers` (`id`, `submission_id`, `field_id`, `field_label`, `field_type`, `answer_value`) VALUES
(15, 5, 23, 'Preparation', 'rating', 'Excellent'),
(16, 5, 24, 'Knowledge on the Subject', 'rating', 'Good'),
(17, 5, 25, 'Question Discussion', 'rating', 'Average'),
(18, 5, 26, 'Additional Comments', 'comment', 'Additional CommentsAdditional CommentsAdditional Comments'),
(19, 6, 27, 'Presentation', 'rating', 'Good'),
(20, 6, 28, 'Additional Comments', 'comment', 'sdsd');

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
(269, 451, '476052503', 46, 22, 184, 'pass', NULL, '2026-08-14 04:50:50'),
(270, 452, '476052504', 46, 22, 184, 'Pending', NULL, '2026-08-14 04:50:50'),
(271, 453, '476052505', 46, 22, 184, 'Pending', NULL, '2026-08-14 04:50:50'),
(272, 454, '476052506', 46, 22, 184, 'Pending', NULL, '2026-08-14 04:50:50'),
(273, 455, '476052507', 46, 22, 184, 'Absent / Not Submitted', NULL, '2026-08-14 04:50:50');

-- --------------------------------------------------------

--
-- Table structure for table `final_year_criteria`
--

CREATE TABLE `final_year_criteria` (
  `id` int(11) NOT NULL,
  `criteria_names` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_year_criteria`
--

INSERT INTO `final_year_criteria` (`id`, `criteria_names`) VALUES
(1, 'sentationss'),
(2, 'final_Preparation'),
(3, 'final_Syllabus Coverage'),
(4, 'final_Knowledge on the Subject');

-- --------------------------------------------------------

--
-- Table structure for table `final_yeat_instalment_data`
--

CREATE TABLE `final_yeat_instalment_data` (
  `id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `program` varchar(255) NOT NULL,
  `batch` varchar(255) NOT NULL,
  `installment_no` int(11) NOT NULL,
  `instalment_date` date NOT NULL,
  `instalment_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_yeat_instalment_data`
--

INSERT INTO `final_yeat_instalment_data` (`id`, `program_id`, `batch_id`, `program`, `batch`, `installment_no`, `instalment_date`, `instalment_amount`, `created_at`) VALUES
(19, 46, 35, 'Graduate Diploma in Management (Level 6)', 'Batch 85', 1, '2026-06-02', 70000.00, '2026-06-22 08:23:43'),
(20, 46, 35, 'Graduate Diploma in Management (Level 6)', 'Batch 85', 2, '2026-08-15', 30000.00, '2026-06-22 08:23:43'),
(21, 46, 35, 'Graduate Diploma in Management (Level 6)', 'Batch 85', 3, '2026-10-15', 20000.00, '2026-06-22 08:23:43'),
(22, 46, 35, 'Graduate Diploma in Management (Level 6)', 'Batch 85', 4, '2026-12-15', 80000.00, '2026-06-22 08:23:43'),
(23, 46, 23, 'Graduate Diploma in Management (Level 6)', 'Batch 77', 1, '2025-09-15', 50000.00, '2026-06-24 06:09:39'),
(24, 46, 23, 'Graduate Diploma in Management (Level 6)', 'Batch 77', 2, '2025-12-15', 50000.00, '2026-06-24 06:09:39'),
(25, 46, 23, 'Graduate Diploma in Management (Level 6)', 'Batch 77', 3, '2026-03-15', 50000.00, '2026-06-24 06:09:39'),
(26, 46, 23, 'Graduate Diploma in Management (Level 6)', 'Batch 77', 4, '2026-06-15', 50000.00, '2026-06-24 06:09:39'),
(27, 46, 23, 'Graduate Diploma in Management (Level 6)', 'Batch 77', 5, '2026-09-15', 54000.00, '2026-06-24 06:09:39'),
(28, 46, 22, 'Graduate Diploma in Management (Level 6)', 'Batch 76', 1, '2025-10-14', 63750.00, '2026-06-24 07:33:55'),
(29, 46, 22, 'Graduate Diploma in Management (Level 6)', 'Batch 76', 2, '2025-11-14', 63750.00, '2026-06-24 07:33:55'),
(30, 46, 22, 'Graduate Diploma in Management (Level 6)', 'Batch 76', 3, '2025-12-14', 63750.00, '2026-06-24 07:33:55'),
(31, 46, 22, 'Graduate Diploma in Management (Level 6)', 'Batch 76', 4, '2026-01-14', 63750.00, '2026-06-24 07:33:55');

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `id` int(11) NOT NULL,
  `program_code` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `banner_image` varchar(255) DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `accepting_responses` tinyint(1) NOT NULL DEFAULT 1,
  `collect_email` tinyint(1) NOT NULL DEFAULT 0,
  `one_response_per_user` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forms`
--

INSERT INTO `forms` (`id`, `program_code`, `title`, `description`, `banner_image`, `created_by`, `status`, `accepting_responses`, `collect_email`, `one_response_per_user`, `created_at`, `updated_at`) VALUES
(17, 46, 'Graduate Diploma in Management (GDM) in Sri Lanka', 'The Graduate Diploma in Management (GDM) programme provides an analytical and rigorous management education, enabling candidates to acquire key knowledge and skills required for effective first and middle-level management. The aim of the Graduate Diploma is to enable and encourage candidates to apply their learning at work on operational and strategic issues, and to take actions using their new skills and competencies on a wide range of real-world professional situations', 'uploads/banners/c6bc446491e68b99048b036f1d5bc761.jpg', 'admin', 'published', 1, 0, 0, '2026-08-18 10:37:21', '2026-08-18 10:52:45'),
(18, 41, 'BTECH', 'dsds', 'uploads/banners/3645422121d3e6aa034181fbf9fc3963.jpg', 'admin', 'published', 1, 1, 0, '2026-08-18 10:49:55', '2026-08-18 10:50:05');

-- --------------------------------------------------------

--
-- Table structure for table `form_questions`
--

CREATE TABLE `form_questions` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `question_text` varchar(500) NOT NULL,
  `question_image` varchar(255) DEFAULT NULL,
  `question_type` enum('short_text','paragraph','multiple_choice','checkbox','dropdown','linear_scale','date','time','file_upload') NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `order_index` int(11) NOT NULL DEFAULT 0,
  `scale_min` int(11) DEFAULT 1,
  `scale_max` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_questions`
--

INSERT INTO `form_questions` (`id`, `form_id`, `question_text`, `question_image`, `question_type`, `is_required`, `order_index`, `scale_min`, `scale_max`) VALUES
(111, 18, 'dsds', NULL, 'short_text', 1, 1, NULL, NULL),
(115, 17, 'Leadership Management in a Digital Economy @123', NULL, 'short_text', 1, 1, NULL, NULL),
(116, 17, 'Human Resource Management', NULL, 'short_text', 1, 2, NULL, NULL),
(117, 17, 'Marketing and Digital Strategy', NULL, 'short_text', 1, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `form_responses`
--

CREATE TABLE `form_responses` (
  `id` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  `respondent_name` varchar(150) DEFAULT NULL,
  `respondent_email` varchar(150) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_responses`
--

INSERT INTO `form_responses` (`id`, `form_id`, `respondent_name`, `respondent_email`, `submitted_at`, `ip_address`) VALUES
(36, 17, NULL, NULL, '2026-08-18 10:54:01', '::1'),
(37, 17, NULL, NULL, '2026-08-18 10:55:29', '::1');

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
(3, '0003', 'Pass', '2024-08-16 09:47:38', '2025-06-30 08:36:13');

-- --------------------------------------------------------

--
-- Table structure for table `induction_active_table`
--

CREATE TABLE `induction_active_table` (
  `id` int(11) NOT NULL,
  `program_id` varchar(255) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `induction_active_table`
--

INSERT INTO `induction_active_table` (`id`, `program_id`, `batch_id`, `status`, `created_at`, `updated_at`) VALUES
(14, '46', 23, 'active', '2026-07-01 17:38:18', '2026-07-03 04:57:56'),
(15, '46', 35, 'active', '2026-07-01 17:39:05', '2026-07-03 04:57:56'),
(21, '41', 21, 'active', '2026-07-03 04:57:56', '2026-07-03 04:57:56');

-- --------------------------------------------------------

--
-- Table structure for table `induction_db_email_send_log_table`
--

CREATE TABLE `induction_db_email_send_log_table` (
  `id` int(11) NOT NULL,
  `allocate_programme_id` int(11) NOT NULL,
  `student_code` int(11) NOT NULL,
  `student_name` varchar(255) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `status` enum('sent','failed') NOT NULL,
  `error_message` text DEFAULT NULL,
  `sent_by` varchar(255) NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `induction_db_email_send_log_table`
--

INSERT INTO `induction_db_email_send_log_table` (`id`, `allocate_programme_id`, `student_code`, `student_name`, `program_id`, `batch_id`, `email_address`, `status`, `error_message`, `sent_by`, `sent_at`) VALUES
(1, 551, 469, 'Mr AAAA AAASS', 46, 35, 'mirshath.mmm@gmail.com', 'sent', '', 'admin', '2026-07-10 09:50:31'),
(2, 552, 470, 'ms BBBB BBBB', 46, 35, 'yournumplz@gmail.com', 'sent', '', 'admin', '2026-07-10 09:50:34'),
(3, 539, 456, 'Mrs sajaa sajaa', 46, 23, 'mirshath.mmm@gmail.com', 'sent', '', 'admin', '2026-07-10 10:37:30'),
(4, 538, 455, 'Mr Q4 Boyer', 46, 22, 'cyqe@mailinator.com', 'sent', '', 'admin', '2026-08-12 12:19:54'),
(5, 535, 452, 'Mr Q1 Delacruz', 46, 22, 'zyzuwaqyf@mailinator.com', 'sent', '', 'admin', '2026-08-12 12:19:57'),
(6, 537, 454, 'Mr Q3 Harmon', 46, 22, 'pazefisi@mailinator.com', 'sent', '', 'admin', '2026-08-12 12:19:58'),
(7, 532, 449, 'Mr Mirshath Testing', 46, 22, 'yournumplz@gmail.com', 'sent', '', 'admin', '2026-08-12 12:20:00'),
(8, 534, 451, 'Mrs ASELA Trujillo', 46, 22, 'yournumplz@gmail.com', 'sent', '', 'admin', '2026-08-12 12:20:02'),
(9, 536, 453, 'Prof Q2 Wood', 46, 22, 'zifeqoma@mailinator.com', 'sent', '', 'admin', '2026-08-12 12:20:04');

-- --------------------------------------------------------

--
-- Table structure for table `induction_emails_sent`
--

CREATE TABLE `induction_emails_sent` (
  `id` int(11) NOT NULL,
  `allocate_programme_id` int(11) NOT NULL,
  `student_code` int(11) NOT NULL,
  `nic` varchar(20) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `sent_by` varchar(255) NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `attended` enum('yes','no') DEFAULT NULL,
  `attended_time` datetime DEFAULT NULL,
  `pack_collected` tinyint(1) NOT NULL DEFAULT 0,
  `fees_paid` enum('paid','unpaid') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `induction_emails_sent`
--

INSERT INTO `induction_emails_sent` (`id`, `allocate_programme_id`, `student_code`, `nic`, `program_id`, `batch_id`, `email`, `sent_by`, `sent_at`, `attended`, `attended_time`, `pack_collected`, `fees_paid`) VALUES
(1, 551, 469, '123456789', 46, 35, 'mirshath.mmm@gmail.com', 'admin', '2026-07-10 09:50:31', NULL, NULL, 0, NULL),
(2, 552, 470, '654987321', 46, 35, 'yournumplz@gmail.com', 'admin', '2026-07-10 09:50:34', NULL, NULL, 0, NULL),
(3, 539, 456, '112233', 46, 23, 'mirshath.mmm@gmail.com', 'admin', '2026-07-10 10:37:30', NULL, NULL, 0, NULL),
(4, 538, 455, '0', 46, 22, 'cyqe@mailinator.com', 'admin', '2026-08-12 12:19:54', NULL, NULL, 0, NULL),
(5, 535, 452, '0', 46, 22, 'zyzuwaqyf@mailinator.com', 'admin', '2026-08-12 12:19:57', NULL, NULL, 0, NULL),
(6, 537, 454, '0', 46, 22, 'pazefisi@mailinator.com', 'admin', '2026-08-12 12:19:59', NULL, NULL, 0, NULL),
(7, 532, 449, '0', 46, 22, 'yournumplz@gmail.com', 'admin', '2026-08-12 12:20:00', NULL, NULL, 0, NULL),
(8, 534, 451, '0', 46, 22, 'yournumplz@gmail.com', 'admin', '2026-08-12 12:20:02', NULL, NULL, 0, NULL),
(9, 536, 453, '0', 46, 22, 'zifeqoma@mailinator.com', 'admin', '2026-08-12 12:20:04', NULL, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `induction_email_body_db_table`
--

CREATE TABLE `induction_email_body_db_table` (
  `id` int(11) NOT NULL,
  `program_id` varchar(50) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `banner_image_path` varchar(255) DEFAULT NULL,
  `email_body` text NOT NULL,
  `date` varchar(255) DEFAULT NULL,
  `time` varchar(255) DEFAULT NULL,
  `dress_code` varchar(255) DEFAULT NULL,
  `important_note` text DEFAULT NULL,
  `created_by` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `induction_email_body_db_table`
--

INSERT INTO `induction_email_body_db_table` (`id`, `program_id`, `batch_id`, `banner_image_path`, `email_body`, `date`, `time`, `dress_code`, `important_note`, `created_by`, `created_at`, `updated_at`) VALUES
(2, '46', 35, 'uploads/induction_banners/banner_6a47528ab8b570.40516972.jpg', '<p>The five main parts of an email include the&nbsp;<strong>subject line</strong>,&nbsp;<strong>greeting</strong>,&nbsp;<strong>body</strong>,&nbsp;<strong>closing</strong>, and&nbsp;<strong>signature</strong>. These components work together to ensure your message is professional, readable, and drives action.&nbsp;</p>\r\n\r\n<p><img alt=\"\" src=\"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAYAAACqaXHeAAADCUlEQVR4nO2bO2zaUBSGf1dFCh1CqDOkyUBaS+mQSDgrQ+Is2RDZyJg1nRjYgaEbA1Mzhm4ZG7FFlWKpEksqxZWaoZVcCUWlSxAxi5EY3KEFEWyIr2M4+PFNGN9rn/P5Pi2ZMwwDQeYZdQDUhAKoA6Dm+bgTXL5WBJADEJtZNNNBA1ABUDHK6fvRkyYBXL62BEAGkJx6aLMhBqAA4ACAOHrSqgvk4J/kh0n+b9UPGCfAr5hysxLg9T4/CVNu4SzgtKIk8JCEZQCArN5BVluuBTVLmAVIAo/q4TYS8ejgvwI20GjrODq79pwIpi4gCTwuj1MPku+TiEdxeZyCJPCuBTcLmARUD7ddKTNP2BYgCbzlkx8lEY96qhUwCFi2fVGWstQEfhq0LUBW72xflKUsNQwCWmi09UfLNdq6p6ZCpi5wdHbtSpl5gkmArLawd1K3bAmNto69k7qnnj7gYCUoqy2sv/8c3KVwH1lteTbpYRwLmDb9xZQkLENparjXewAApdkZ/HYDZgFGOT3xPJev2S5fuviJ4sWPwbEk8MjtvEFmc2XiPRptHdWrW1S+/HqyjLloAesvX6CaFbFrcwmdiEdR2N9Abuc1cuc3qF7dOr43uQBxdRHyuxRiCxHmurGFCE6z/95zOpVAuhQW15wnP8xpVnS8ASMVkNlceXLyfYr7bx3V881maFfgIa4uMtfzjQAAONh6xVyHfBAcRev2oPzuDI6XohEkbT5ZJ+PA3AjQur2xU5ok8Khkth4VIa55tAto3R6kD/WxU5mstmztMp0MqHMhIHd+A6XZmVhGaXbw8avzBc84yAX0l7V2GB4b3IJcwKfvf2yXVZqa6/cnF0C9pSYX4ObW1gnkAliYRmshFxD4LkBNKIA6AGpCAdQBUBMKoA6AmlAAdQDUhAKoA6CGG/1miMvXfP0RkVFOc8PHgW8BoQDqAKixEuD+i7f5wZSblYDKDAKhwpSbaRYAAC5fU+C/74a+GeW0rY+mAEACUII/uoMGoGSVPDCmBQSJcBagDoCawAv4CyJz5Ou100U7AAAAAElFTkSuQmCC\" />LinkedIn&middot;Richard Williams&nbsp;+1</p>\r\n\r\n<ul>\r\n	<li><strong>Subject Line:</strong>&nbsp;A brief, punchy summary of your email&#39;s purpose. It is the first thing recipients see and determines whether they will open the message</li>\r\n	<li><strong>Subject Line:</strong>&nbsp;A brief, punchy summary of your email&#39;s purpose. It is the first thing recipients see and determines whether they will open the message</li>\r\n	<li><strong>Subject Line:</strong>&nbsp;A brief, punchy summary of your email&#39;s purpose. It is the first thing recipients see and determines whether they will open the message</li>\r\n</ul>\r\n', NULL, NULL, NULL, NULL, 'mirshath', '2026-07-03 11:41:22', '2026-07-03 11:51:26'),
(3, '46', 23, 'uploads/induction_banners/banner_6a47548c15ded8.95363543.jpg', '<p>We are pleased to invite you to the induction for the Teesside University Masters programme.</p>\r\n', 'Saturday, 07th March 2026', '<p>2.00 PM to 5.00 PM</p>\r\n\r\n<p>(Kindly be present by 1.45 PM to complete registrations)</p>', 'Formal Attire', '<ul>\r\n	<li>Attendance at the Induction Programme is mandatory for all students. For any queries,</li>\r\n	<li>feel free to contact Mr. Randhir Navaratnarajah - 070 632 1227.</li>\r\n</ul>', 'mirshath', '2026-07-03 11:49:56', '2026-07-03 15:57:29');

-- --------------------------------------------------------

--
-- Table structure for table `induction_students`
--

CREATE TABLE `induction_students` (
  `id` int(11) NOT NULL,
  `ref_no` varchar(50) DEFAULT NULL,
  `programme` varchar(100) DEFAULT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `nic` varchar(20) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `landline_no` varchar(20) DEFAULT NULL,
  `fees` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `institute` varchar(150) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `country_of_residence` varchar(100) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `bms_email` varchar(150) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `address_country` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `paid` varchar(100) DEFAULT NULL,
  `attended` varchar(50) DEFAULT 'No',
  `email_sent` tinyint(1) DEFAULT 0,
  `email_sent_time` datetime DEFAULT NULL,
  `email_sent_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `attended_time` timestamp NULL DEFAULT NULL,
  `pack_collected` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `induction_students`
--

INSERT INTO `induction_students` (`id`, `ref_no`, `programme`, `full_name`, `nic`, `contact_no`, `landline_no`, `fees`, `qualification`, `institute`, `gender`, `date_of_birth`, `country_of_residence`, `nationality`, `email`, `bms_email`, `address1`, `address2`, `address_country`, `postal_code`, `paid`, `attended`, `email_sent`, `email_sent_time`, `email_sent_by`, `created_at`, `attended_time`, `pack_collected`) VALUES
(1, '126501', 'BSc (Hons) Biomedical Science', 'Hasni Nihar', '96548745V', '123456789', '9876542365', 'Paid', 'HD', 'BMS', 'Female ', '1996-05-24', 'Sri Lanka ', 'Sri Lankan', 'mirmirsha123@gmail.com', 'mirshath.mmm@gmail.com', 'No. 19, Bodhiraja Mawatha', 'Colombo 06', 'Sri Lanka ', '600', 'Reg paid ', 'No', 1, '2026-01-29 11:46:07', 'mirshath', '2026-01-09 09:01:48', '2026-01-29 05:49:48', 0),
(2, '126502', 'MSc Management', 'Anne Luke', '990190984V', '123456789', '9876542365', 'Unpaid', 'HD', 'BMS', 'Female ', '1999-01-19', 'Sri Lanka ', 'Sri Lankan', 'yournumplz@gmail.com', 'yournumplz@gmail.com', 'No. 223 Fordyce', 'Dickoya', '', '', 'Full paid ', 'Yes', 1, '2026-01-29 11:46:08', 'mirshath', '2026-01-09 09:01:48', '2026-07-01 04:54:23', 1),
(3, '126503', 'MSc Management', 'Thanuja Ekanayake', '95002155V', '123456789', '9876542365', 'Paid', 'HD', 'BMS', 'Female ', '2003-08-05', 'Sri Lanka ', 'Sri Lankan', 'mirshath.mmm@gmail.com', 'bmsmirshath@gmail.com', 'No. 43/122A, Poorvarama Mawatha', 'Colombo 05', 'Sri Lanka ', '500', 'Reg paid ', 'No', 1, '2026-01-29 11:46:10', 'mirshath', '2026-01-09 09:01:48', NULL, 1),
(6, '126501', 'MSc Cancer and Molecular Diagnostics', 'AAAAAAAAAAAA', '99', '123456789', '9876542365', 'Paid ', 'HD', 'BMS', 'Female ', '1970-01-01', 'Sri Lanka ', 'Sri Lankan', 'mirshath.mmm@gmail.com', 'mirshath.mmm@gmail.com', 'No. 19, Bodhiraja Mawatha', 'Colombo 06', 'Sri Lanka ', '600', 'Reg paid ', 'No', 1, '2026-07-03 14:05:05', 'admin', '2026-07-03 08:33:46', NULL, 0);

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
(1, 1, '449', 'Graduate Diploma in Management (Level 6) - Batch 76', 'installment_1', 100000.00, 100000.00, 'N/A', NULL, '2026-10-25', NULL, 'admin', NULL),
(2, 1, '449', 'Graduate Diploma in Management (Level 6) - Batch 76', 'installment_2', 100000.00, 100000.00, 'N/A', NULL, '2026-11-25', NULL, 'admin', NULL);

--
-- Triggers `installment_details_table`
--
DELIMITER $$
CREATE TRIGGER `trg_idt_insert` AFTER INSERT ON `installment_details_table` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('installment_details_table', NEW.id, 'insert',
            CONCAT('New installment detail: id=', NEW.id, ', student=', NEW.student_id));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_idt_update` AFTER UPDATE ON `installment_details_table` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('installment_details_table', NEW.id, 'update',
            CONCAT('Installment detail updated: id=', NEW.id, ', student=', NEW.student_id));
END
$$
DELIMITER ;

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
  `registrationfee` int(11) DEFAULT NULL,
  `discounted_percentage` decimal(5,2) DEFAULT 0.00 COMMENT 'Percentage discount applied from Program Fee Details',
  `dis_yes_no` tinyint(4) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `installment_payment_table`
--

INSERT INTO `installment_payment_table` (`id`, `payment_plans_tb_id`, `student_id`, `programme_batch`, `unifee_lkr_total`, `unifee_lkr`, `unifee_gbp_total`, `unifee_gbp`, `unifee_usd_total`, `unifee_usd`, `fee_type`, `coursefee_total`, `coursefee`, `registrationfee`, `discounted_percentage`, `dis_yes_no`) VALUES
(1, 1, 449, 'Graduate Diploma in Management (Level 6) - Batch 76', NULL, NULL, 230, 230, NULL, NULL, 'installment', 260000, 200000, 60000, 0.00, 0);

--
-- Triggers `installment_payment_table`
--
DELIMITER $$
CREATE TRIGGER `trg_ipt_insert` AFTER INSERT ON `installment_payment_table` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('installment_payment_table', NEW.id, 'insert',
            CONCAT('New installment payment: id=', NEW.id, ', student=', NEW.student_id));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_ipt_update` AFTER UPDATE ON `installment_payment_table` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('installment_payment_table', NEW.id, 'update',
            CONCAT('Installment payment updated: id=', NEW.id, ', student=', NEW.student_id));
END
$$
DELIMITER ;

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
(3, '2025-05-14', 'Websites', '1', 'Executive Certificate in Management', '2025-05-23', 'MIRSHATH', 'MOHAMED', '0254904455', 'mirshath.m@cgs.lk', 'ANURADHAOURA', 'Interested', NULL),
(4, '2025-07-02', 'Walk in', '1', 'International Foundation Diploma (Business) - ATHE Level 3', '2025-07-10', 'ass ameen', 'khan', '0254904455', 'mirshath.mmm@gmail.com', 'sadasdsd ad ad', 'N/A', 'mirshath'),
(5, '2011-07-02', 'sdsd', '1', 'International Foundation Diploma (Business) - ATHE Level 3', '1986-09-07', 'Quin', 'Shannon', 'Tempore quia rerum ', 'pofihiqo@mailinator.com', 'Anim sint non laboru', 'Interested', 'mirshath');

--
-- Triggers `leads`
--
DELIMITER $$
CREATE TRIGGER `prevent_leads_delete` BEFORE DELETE ON `leads` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = '❌ Deletion from leads is not allowed for security reasons.';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_leads_insert` AFTER INSERT ON `leads` FOR EACH ROW BEGIN
    INSERT INTO notifications (table_name, row_id, action, message)
    VALUES (
        'leads',
        NEW.id,
        'insert',
        CONCAT('New record added in leads: ID ', NEW.id)
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_leads_update` AFTER UPDATE ON `leads` FOR EACH ROW BEGIN
    INSERT INTO notifications (table_name, row_id, action, message)
    VALUES (
        'leads',
        NEW.id,
        'update',
        CONCAT('Record updated in leads: ID ', NEW.id)
    );
END
$$
DELIMITER ;

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
(1, 'Websites', 'mirshath', '2024-08-12 01:11:04'),
(2, 'Social Media', NULL, '2024-08-12 01:11:04'),
(3, 'Referrals', NULL, '2024-08-12 01:11:04'),
(96, 'Walk in', NULL, '2024-10-01 05:43:56'),
(102, 'Call', 'mirshath', '2025-03-20 04:52:16'),
(0, 'sdsd', 'mirshath', '2025-10-10 10:45:10'),
(0, 'ss', 'admin', '2026-04-05 04:32:22'),
(0, 'sss', 'admin', '2026-04-05 04:32:33');

-- --------------------------------------------------------

--
-- Table structure for table `lecturer_table`
--

CREATE TABLE `lecturer_table` (
  `id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `lecturer_name` varchar(255) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `qualification` text NOT NULL,
  `programs` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lecturer_table`
--

INSERT INTO `lecturer_table` (`id`, `title`, `lecturer_name`, `hourly_rate`, `qualification`, `programs`, `created_at`) VALUES
(11, 'Dr.', 'Lecture 01', 0.00, 'BBB', '[\"International Foundation Diploma (Business) - ATHE Level 3\",\"BTEC Higher National Diploma in Business\",\"TEST@\",\"Higher Diploma in Biotechnology\",\"Higher Diploma in Food Science and Nutrition\",\"Executive Certificate in Management\",\"Graduate Diploma in Management (Level 6)\",\"Bachelor of Business Management (Hons)\",\"Higher Diploma in Medical Biotechnology\",\"BSc (Hons) in Software Engineering\",\"TEST 1\",\"International Foundation Diploma (Applied Science) - ATHE Level 3\",\"BSc (Hons) International Tourism, Hospitality & Events\"]', '2026-06-11 06:51:47'),
(12, 'Dr.', 'Lecture 01', 0.00, 'GDMMM', '[\"Graduate Diploma in Management (Level 6)\"]', '2026-06-11 06:52:38'),
(13, 'Dr.', 'Lecture 03', 0.00, 'TEST', '[\"BSc (Hons) in Software Engineering\"]', '2026-08-04 05:18:08'),
(14, 'Dr.', 'Lecture 04', 0.00, 'RRRRRRRRRRRRRRRRRRRRR', '[\"BSc (Hons) in Software Engineering\"]', '2026-08-04 09:45:03');

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
  `lecturers` varchar(255) DEFAULT NULL,
  `institution` varchar(255) DEFAULT NULL,
  `module_GPA` int(11) DEFAULT NULL,
  `examinor_1` varchar(100) DEFAULT NULL,
  `examinor_2` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `module_code`, `module_name`, `university_id`, `programme_id`, `year_id`, `semester_id`, `pass_mark`, `type`, `lecturers`, `institution`, `module_GPA`, `examinor_1`, `examinor_2`) VALUES
(171, 'Unit 1', 'Economics for Business', 1, 39, 42, 27, '100', 'Elective', '', '', 0, '', ''),
(172, 'Unit 2', 'Business Essentials', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(174, 'Unit 4', 'Mathematics and Statistics', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(175, 'Unit 5', 'Digital Technology and Study Skills', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(176, 'Unit 6', 'English for Academic Purposes', 1, 39, 42, 27, '100', 'Compulsory', '', '', NULL, NULL, NULL),
(177, 'ECM101', 'Principles of Management', 1, 45, 42, 27, '50', 'Compulsory', '', '', NULL, NULL, NULL),
(178, 'ECM102', 'Marketing Essentials', 1, 45, 42, 27, '50', 'Compulsory', '', '', NULL, NULL, NULL),
(179, 'ECM103', 'Accounting Principles', 1, 45, 42, 27, '50', 'Compulsory', '', '', NULL, NULL, NULL),
(183, 'GDM Unit 1', 'Leadership and Management in a Digital Economy', 1, 46, 42, 27, 'Pass', 'Compulsory', 'lexture 02', '0', NULL, '', ''),
(184, 'GDM Unit 2', 'Human Resource Management', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(185, 'GDM Unit 3', 'Marketing and Digital Strategy', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(186, 'GDM Unit 4', 'Financial Principles and Techniques ', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(187, 'GDM Unit 5', 'Research Methods for Managers', 1, 46, 42, 27, 'Pass', 'Compulsory', '', '', NULL, NULL, NULL),
(188, 'GDM Unit 6', 'sss', 1, 62, 42, 27, '40%', 'Compulsory', '', '', 2, '', ''),
(189, 'MG BM1114', 'Introduction to Business and Management', 1, 47, 17, 1, '40%', 'Compulsory', '', '', 5, '4', '12'),
(190, 'MG BM1124', 'Business Environment', 1, 47, 17, 1, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(191, 'MG BM1134', 'Business Communication', 1, 47, 17, 1, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(192, 'MG BM1143', 'Business Law ', 1, 47, 17, 1, '40%', 'Compulsory', '', '', 3, '4', '12'),
(193, 'MG BM1213', 'Information Technology', 1, 47, 17, 2, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(194, 'MG BM1224', 'Marketing Management', 1, 47, 17, 2, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(195, 'MG BM1234', 'Financial Accounting', 1, 47, 17, 2, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(196, 'MG BM1244', 'Humen Resource Management ', 1, 47, 17, 2, '40%', 'Compulsory', '', '', NULL, NULL, NULL),
(205, 'Unit 3', 'Accounting Fundamentals', 1, 39, 42, 27, '100', 'Compulsory', '', '', 6, '4', '4'),
(206, 'SE M1', 'Software Module 1', 27, 57, 42, 27, '50', 'Compulsory', '', '0', 5, '', ''),
(207, 'BIOM 1', 'Cell Biology', 1, 42, 42, 1, '40%', 'Compulsory', '', '', 0, '', ''),
(208, 'BIOM 2', 'Biochmiestry ', 1, 42, 42, 1, '40%', 'Compulsory', '', '', 0, '', ''),
(209, 'BIMT 411(OT)', 'Cell Biology', 1, 43, 42, 1, '40%', 'Compulsory', 'sdssssssftttt', '', NULL, '', ''),
(210, 'BIMT 412(OT)', 'Biochmiestry ', 1, 43, 42, 1, '40%', 'Compulsory', '', '', 0, '', ''),
(211, 'M1U1', 'Business and the Business Environment', 1, 41, 42, 1, 'Pass', 'Compulsory', '', '', 0, '', ''),
(212, 'IFD s1', 'Mathematics and Statistics', 1, 63, 42, 27, '40%', 'Compulsory', '', '', 0, '', ''),
(213, 'Cum et qui animi cu', 'Keegan Juarez', 1, 43, 17, 3, 'Beatae impedit est', 'Elective', 'Odit asperiores nemo', '', 51, '22', '14'),
(214, 'bt', 'TEST Business ', 1, 41, 42, 1, 'pass', 'Elective', '', '', 0, '', ''),
(215, '3r', 'Lec add module', 1, 46, 18, 3, '30', 'Compulsory', 'Lecture 01,lexture 02', '0', 2, '', ''),
(216, 'rre', 'RETEST SC', 27, 57, 18, 2, '50', 'Compulsory', '', '0', NULL, '', '');

--
-- Triggers `modules`
--
DELIMITER $$
CREATE TRIGGER `prevent_modules_delete` BEFORE DELETE ON `modules` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = '❌ Manual deletion of modules is not allowed.';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_modules_insert` AFTER INSERT ON `modules` FOR EACH ROW BEGIN
    INSERT INTO notifications (table_name, row_id, action, message)
    VALUES (
        'modules',
        NEW.id,
        'insert',
        CONCAT('Module added: ', NEW.module_name, ' (Code: ', NEW.module_code, ')')
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_modules_update` AFTER UPDATE ON `modules` FOR EACH ROW BEGIN
    INSERT INTO notifications (table_name, row_id, action, message)
    VALUES (
        'modules',
        NEW.id,
        'update',
        CONCAT('Module updated: ', NEW.module_name, ' (Code: ', NEW.module_code, ')')
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `module_result_email_log`
--

CREATE TABLE `module_result_email_log` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `programme_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `status` enum('sent','failed','not_sent') NOT NULL DEFAULT 'not_sent',
  `sent_date` datetime DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'File', 'Lead Types - create_lead,DBTablesCount - db_tables_count,Year - create_year,Semester - create_semester,Criteria - create_criteria,University - create_universities,Coordinator - create_coordinators,Program - create_program,UserProgram - program_to_user,Assignment Components - create_assignment_components,Lecture - create_lecturer,One To One Student Upload - upload_Students_one_to_one_student,Module - create_module,Batch - create_batch,Grade - create_grade,Currency - create_currency,Status - create_status,Decision - create_decision,Batch Payment Allocation - program_payment_allocation,Induction Edit - induction_master,Components Allocations - assign_components_allocations,Induction Edit From DB - induction_master_db,Nav Collection CRUD - master_nav_collection'),
(2, 'Exams', 'Assesment Management - exams,Assesment Document Send - assesmentDocumentSend,Exam / Assignment Result - exam_result,E/A Result Send - exam_result_send,Module Results Mail - module_result_mail,Time Table - AddTimeTable,Daily Time Table Message - ,Special Class Messages - SpecialClassMessages,Special Reason - specialReason,Add Decision -,Alumni -,List Board -'),
(3, 'Edit', 'Edit Programme Allocation - editAllocateProgram,Edit Payment - editPayment'),
(4, 'Re Print', ''),
(5, 'Reports', 'All Student Details - allStudentDetails,Student Wise Details - studentWiseDetails,Leads Report - leadsReport,Outstanding Payment - outstandingPayment,Special Reason Report - specialReasonReport,Exam / Assignment Report - exam_report,Result Mailing Report - result_mailing_report,Payment Report - paymentReport,Penalty Payment Report - penaltyPaymentReport,Additional Payment Report - additionalPaymentReport,Time Table Report - timeTableReport,Alumni Report - aluminiReport,BBM result report - BBM_result_report,Total Induction Student List - total_induction_students,Online Registration Report - get_data_from_std,Payment Check Report - check_all_data_for, Induction DB Student List - induction_fl_report_db'),
(6, 'Options', 'Add Users - addUser,User Permission - userPermission,All Notifications - all_notifications,Student Check Payments - studentCheckPayment'),
(7, 'Dashboard', 'card_enable - index,Inactive Students - inactive_students'),
(8, 'Cancellations', 'payment cancellation - PaymentCancellation,penalty payment cancellation - PenaltyPaymentCancellation,Additional payment cancellation - AdditionalPaymentCancellation'),
(9, 'Button Permission', 'exams_asses_send_button,exams_asses_edit_button,exams_asses_document_send_button,exams_asses_document_edit_button,result_edit_btn'),
(10, 'HR', 'Feedback Link Generate - feedback_link_generate,Feedback Report - feedback_report,Final Year Report - final_year_report'),
(11, 'Induction', 'Induction Scan - induction_scan,Induction Upload Student - upload_induction_students,,Induction Email Send - induction_from_db_email_send,Induction Scan From DB - induction_scan_db,Induction DB Student List - induction_fl_report_db,Induction Email Report - induction_email_report,Induction Email Template - induction_email_body_page'),
(13, 'Recruitment', 'Add Leads - addLeads,Student Registration - studentRegister,Online Registration Data - online_registration_data,Upload Students - uploadStudents,Allocate Program - allocateProgram,BMS Email Allocation - bms_email_allocation,StudentID Allocation - studentID_allocation,Update Student Status - UpdateStudentStatus,Student Batch Transfer - batch_transfer,Student Batch Swap - batchSwap,Student Program Progression - programProgression,Update Students E Module - UpdateElectiveModule,Upload Student Documents - uploadScanCopies,Send Offer Letter - offer_letter\r\n'),
(14, 'Finance', 'Add Payment Plan - add_payment_plan,Batch Wise Payment Plan - batch_wise_payment_plan,Payment - payment,Penalty Payment - penalty_pay,Additional Payment - AdditionalFee'),
(16, 'BMS POS', 'POS Store / Checkout - pos_store,POS Dashboard - pos_dashboard,POS Manage Products - pos_products,POS Reports - pos_reports,POS Detailed Reports - pos_detailed_report,POS Detailed Sales - pos_detailed_sales');

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
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `row_id` int(11) DEFAULT NULL,
  `action` enum('insert','update','delete','auto_change') DEFAULT NULL,
  `message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unread','read') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `notifications`
--
DELIMITER $$
CREATE TRIGGER `prevent_notification_delete` BEFORE DELETE ON `notifications` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Deletion of notifications is not allowed for security reasons.';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `prevent_notification_update` BEFORE UPDATE ON `notifications` FOR EACH ROW BEGIN
    -- Allow only status change (unread → read)
    IF (OLD.status <> NEW.status) AND (
        OLD.table_name = NEW.table_name AND
        OLD.row_id = NEW.row_id AND
        OLD.action = NEW.action AND
        OLD.message = NEW.message AND
        OLD.created_at = NEW.created_at
    ) THEN
        -- Allow only status change, do nothing
        SET NEW.status = NEW.status;
    ELSE
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only status updates (read/unread) are allowed.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `customer_type` varchar(20) NOT NULL DEFAULT 'Cash',
  `customer_name` varchar(255) DEFAULT NULL,
  `created_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_rate` decimal(5,4) NOT NULL DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `subtotal`, `tax`, `total`, `customer_type`, `customer_name`, `created_by`, `created_at`, `discount`, `discount_rate`) VALUES
(1, 3000.00, 0.00, 2700.00, 'Cash', NULL, 'admin', '2026-08-03 08:12:13', 300.00, 0.1000),
(2, 2100.00, 0.00, 1785.00, 'Cash', NULL, 'admin', '2026-08-03 08:12:49', 315.00, 0.1500);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` varchar(64) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `qty`, `price`, `discount`) VALUES
(1, 1, 'GC003', 2, 1500.00, 300.00),
(2, 2, 'GC001', 1, 1500.00, 225.00),
(3, 2, 'GC004', 2, 300.00, 90.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment_batch_allocation`
--

CREATE TABLE `payment_batch_allocation` (
  `id` int(11) NOT NULL,
  `programme_id` varchar(100) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `course_fee_lkr` decimal(10,2) DEFAULT NULL,
  `uni_fee_gbp` decimal(10,2) DEFAULT NULL,
  `uni_fee_usd` decimal(10,2) DEFAULT NULL,
  `uni_fee_euro` decimal(10,2) DEFAULT NULL,
  `register_date` date NOT NULL,
  `installment_no` int(11) NOT NULL,
  `registration_fee` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `only_course_fee` decimal(10,2) DEFAULT NULL,
  `Installment_Interval` double DEFAULT NULL COMMENT 'Installment interval in months (e.g., 1, 1.5, 2, 2.5, 3)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_batch_allocation`
--

INSERT INTO `payment_batch_allocation` (`id`, `programme_id`, `batch_id`, `course_fee_lkr`, `uni_fee_gbp`, `uni_fee_usd`, `uni_fee_euro`, `register_date`, `installment_no`, `registration_fee`, `created_at`, `only_course_fee`, `Installment_Interval`) VALUES
(17, '41', 21, 5000.00, 370.00, 0.00, 0.00, '2025-07-22', 2, 500.00, '2025-07-22 07:03:39', 4500.00, 2),
(18, '39', 13, 5000.00, 500.00, 0.00, 0.00, '2025-07-22', 3, 2000.00, '2025-07-22 09:00:20', 3000.00, NULL),
(19, '43', 41, 7000.00, 0.00, 600.00, 0.00, '2025-07-22', 2, 1000.00, '2025-07-22 09:00:54', 6000.00, NULL),
(30, '39', 12, 5000.00, 500.00, 0.00, 0.00, '2025-07-28', 2, 1000.00, '2025-07-25 04:14:58', 4000.00, NULL),
(31, '63', 42, 6500.00, 350.00, 0.00, 0.00, '2025-08-11', 3, 500.00, '2025-08-26 03:52:55', 6000.00, NULL),
(32, '46', 22, 260000.00, 230.00, 0.00, 0.00, '2025-09-14', 4, 5000.00, '2025-09-16 04:34:25', 255000.00, 1),
(33, '46', 23, 260000.00, 230.00, 0.00, 0.00, '2025-09-15', 5, 5000.00, '2025-09-16 04:40:20', 255000.00, 3),
(34, '45', 38, 45000.00, 362.00, 0.00, 0.00, '2025-09-14', 2, 5000.00, '2025-09-16 07:11:01', 40000.00, 1.5),
(35, '45', 37, 45000.00, 362.00, 0.00, 0.00, '2025-09-09', 2, 4999.98, '2025-09-16 07:14:44', 40000.02, 3),
(37, '46', 34, 260000.00, 230.00, 0.00, 0.00, '2025-09-15', 5, 5000.00, '2025-09-16 04:40:20', 255000.00, 1),
(44, '46', 35, 260000.00, 230.00, 0.00, 0.00, '2026-06-02', 4, 60000.00, '2026-06-22 08:23:43', 200000.00, 2);

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
-- Table structure for table `payment_due_method`
--

CREATE TABLE `payment_due_method` (
  `id` int(11) NOT NULL,
  `payment_method` varchar(200) NOT NULL,
  `payment_value` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_due_method`
--

INSERT INTO `payment_due_method` (`id`, `payment_method`, `payment_value`) VALUES
(1, '30d +', 1),
(2, '60d +', 2);

-- --------------------------------------------------------

--
-- Table structure for table `payment_due_tables`
--

CREATE TABLE `payment_due_tables` (
  `id` int(11) NOT NULL,
  `student_code` varchar(50) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_registration_id` varchar(255) DEFAULT NULL,
  `programme_batch` varchar(255) DEFAULT NULL,
  `due_count_bms_fees` int(11) NOT NULL DEFAULT 0,
  `due_count_uni_fees` int(11) NOT NULL DEFAULT 0,
  `remaining_full_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `payment_method` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_plan_history`
--

CREATE TABLE `payment_plan_history` (
  `id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `discount_type` varchar(50) NOT NULL,
  `discount_value` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL,
  `re_marks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_plan_regfee_discount`
--

CREATE TABLE `payment_plan_regfee_discount` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `d_type` varchar(100) NOT NULL,
  `discount_value` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(100) NOT NULL
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
  `rcpt_number` varchar(50) DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `payment_type` varchar(200) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `card_bank_deposit_dt` date DEFAULT NULL,
  `entered_by` varchar(100) DEFAULT 'admin',
  `entered_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `payment_uni_fee`
--
DELIMITER $$
CREATE TRIGGER `trg_puf_insert` AFTER INSERT ON `payment_uni_fee` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('payment_uni_fee', NEW.id, 'insert',
            CONCAT('University fee paid: student=', NEW.student_id, ', amount=', NEW.paid_amount));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_puf_update` AFTER UPDATE ON `payment_uni_fee` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('payment_uni_fee', NEW.id, 'update',
            CONCAT('University fee payment updated: student=', NEW.student_id, ', amount=', NEW.paid_amount));
END
$$
DELIMITER ;

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
  `card_bank_deposit_dt` date DEFAULT NULL,
  `entered_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `entered_by` varchar(255) NOT NULL,
  `rcpt_number` varchar(50) DEFAULT NULL,
  `status` enum('paid','pending','cancelled') DEFAULT 'paid',
  `cancellation_reason` text DEFAULT NULL,
  `cancelled_by` varchar(50) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `payment_wise_info`
--
DELIMITER $$
CREATE TRIGGER `trg_pwi_insert` AFTER INSERT ON `payment_wise_info` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('payment_wise_info', NEW.id, 'insert',
            CONCAT('New payment: student_id=', NEW.student_id, ', amount=', NEW.paymentAmount));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_pwi_update` AFTER UPDATE ON `payment_wise_info` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('payment_wise_info', NEW.id, 'update',
            CONCAT('Payment updated: student_id=', NEW.student_id, ', amount=', NEW.paymentAmount));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_withheld_table`
--

CREATE TABLE `payment_withheld_table` (
  `id` int(11) NOT NULL,
  `student_code` varchar(50) NOT NULL,
  `student_registration_id` varchar(100) NOT NULL,
  `program_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `payment_status` varchar(250) NOT NULL DEFAULT 'active',
  `due_count_bms_fees` int(11) DEFAULT 0,
  `due_count_uni_fees` int(11) DEFAULT 0,
  `last_payment_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_withheld_table`
--

INSERT INTO `payment_withheld_table` (`id`, `student_code`, `student_registration_id`, `program_id`, `batch_id`, `payment_status`, `due_count_bms_fees`, `due_count_uni_fees`, `last_payment_date`, `created_at`, `updated_at`) VALUES
(1, '449', '', 46, 22, 'withheld', 0, 0, NULL, '2026-08-13 10:59:52', '2026-08-13 10:59:52');

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
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` varchar(64) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `store_stock` int(11) DEFAULT NULL,
  `stock` int(11) NOT NULL,
  `image` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `store_stock`, `stock`, `image`, `active`, `created_at`) VALUES
('GC001', 'Teddy Bear', 1500.00, 200, 44, 'pos-product-image/BMS-Teddy-Bear.jpg', 1, '2026-07-22 10:18:54'),
('GC002', 'Mug', 750.00, 200, 87, 'pos-product-image/prod_20260723_102016_7c65bdd9.jpg', 1, '2026-07-22 10:18:54'),
('GC003', 'Vacuum Tumbler', 1500.00, 200, 60, 'pos-product-image/BMS-Logo-Bottle.jpg', 1, '2026-07-22 10:18:54'),
('GC004', 'Note Book', 300.00, 200, 168, 'pos-product-image/BMS-Note-Book.jpg', 1, '2026-07-22 10:18:54'),
('GC005', 'Wooden Pens', 450.00, 200, 456, 'pos-product-image/prod_20260723_101941_14294086.jpg', 1, '2026-07-22 10:18:54');

-- --------------------------------------------------------

--
-- Table structure for table `program_allocation_user`
--

CREATE TABLE `program_allocation_user` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `program_code` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_allocation_user`
--

INSERT INTO `program_allocation_user` (`id`, `user_id`, `program_code`, `created_at`, `updated_at`) VALUES
(48, 12, '63', '2025-10-17 03:56:56', NULL),
(49, 12, '39', '2025-10-17 03:56:56', NULL),
(50, 12, '62', '2025-10-17 03:56:56', NULL),
(87, 22, '41', '2025-11-21 06:38:00', NULL),
(89, 22, '46', '2026-01-26 04:53:08', NULL),
(90, 22, '45', '2026-01-26 04:55:18', NULL),
(91, 22, '47', '2026-01-26 04:56:45', NULL),
(92, 22, '48', '2026-01-26 04:56:45', NULL),
(93, 14, '57', '2026-01-26 05:24:27', NULL),
(95, 14, '45', '2026-01-26 05:24:27', NULL),
(96, 14, '46', '2026-01-26 05:24:27', NULL),
(98, 4, '46', '2026-04-05 04:02:06', NULL),
(100, 22, '57', '2026-04-05 04:52:31', NULL),
(101, 22, '42', '2026-04-05 04:52:31', NULL),
(102, 22, '43', '2026-04-05 04:52:31', NULL),
(103, 22, '44', '2026-04-05 04:52:32', NULL),
(104, 22, '63', '2026-04-05 04:52:32', NULL),
(105, 22, '39', '2026-04-05 04:52:32', NULL),
(106, 22, '62', '2026-04-05 04:52:32', NULL),
(113, 4, '41', '2026-08-18 10:51:49', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `program_progression_table`
--

CREATE TABLE `program_progression_table` (
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
  `entered_by` varchar(50) DEFAULT NULL,
  `OLD_registration_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_progression_table`
--

INSERT INTO `program_progression_table` (`id`, `student_id`, `from_student`, `from_university`, `from_programme`, `from_batch`, `from_registration_code`, `university_id`, `programme_code`, `batch_id`, `transfer_date`, `active_status`, `allocated_id`, `entered_by`, `OLD_registration_id`) VALUES
(2, 443, 'Mohamed  Mirshath', 'BMS', 'International Foundation Diploma (Business) - ATHE Level 3', 'Batch 30', 'IFD110122503', 1, 39, 12, '2026-01-30 09:17:27', 'active', 528, 'mirshath', 'IFD110122502'),
(3, 445, 'Asela Holman', 'BMS', 'Graduate Diploma in Management (Level 6)', 'Batch 76', 'IFD110122502', 1, 39, 12, '2026-01-30 09:25:51', 'active', 529, 'mirshath', '476052503'),
(4, 443, 'Mohamed  Mirshath', 'BMS', 'International Foundation Diploma (Business) - ATHE Level 3', 'Batch 30', '', 1, 39, 13, '2026-01-30 09:43:56', 'active', 530, 'mirshath', '');

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
  `entry_requirement` varchar(255) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `cetegory` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `program_table`
--

INSERT INTO `program_table` (`program_code`, `university_id`, `program_name`, `prog_code`, `coordinator_name`, `medium`, `duration`, `result_method`, `course_fee_lkr`, `course_fee_gbp`, `course_fee_usd`, `course_fee_euro`, `entry_requirement`, `payment_method`, `cetegory`) VALUES
(39, 1, 'International Foundation Diploma (Business) - ATHE Level 3', 'IFD1', 'aaa', 'English', '12 Months', '', 5000.00, 500.00, 0.00, 0.00, 'Bachelor,Masters,Diploma,CBM,A/L', '1', NULL),
(41, 1, 'BTEC Higher National Diploma in Business', '6', 'aaa', 'English', '18 months', '', 5000.00, 370.00, 0.00, 0.00, 'Diploma,CBM', '1', NULL),
(42, 1, 'TEST@', '5', 'aaa', 'English', '18 months', '', 263000.00, 300.00, 0.00, 0.00, 'Diploma,CBM', '1', NULL),
(43, 1, 'Higher Diploma in Biotechnology', '6', 'aaa', 'English', '18 months', '', 780000.00, 0.00, 0.00, 0.00, 'Masters,Diploma,A/L', '1', NULL),
(44, 1, 'Higher Diploma in Food Science and Nutrition', '10', 'aaa', 'English', '18 months', '', 780000.00, 0.00, 0.00, 0.00, 'A/L', '2', NULL),
(45, 1, 'Executive Certificate in Management', 'ecm', 'aaa', 'English', '6 months', '', 45000.00, 362.00, 0.00, 0.00, 'A/L', '1', NULL),
(46, 1, 'Graduate Diploma in Management (Level 6)', '4', 'aaa', 'English', 'One Academic Years', '', 260000.00, 230.00, 0.00, 0.00, 'A/L', '2', 'final_year'),
(47, 1, 'Bachelor of Business Management (Hons)', 'bbm', 'aaa', 'English', '4 years', '', 12000.00, 0.00, 0.00, 0.00, 'A/L', '1', 'normal'),
(48, 1, 'Higher Diploma in Medical Biotechnology', '11', 'aaa', 'English', '18 months', '', 780000.00, 0.00, 0.00, 0.00, 'A/L', '2', NULL),
(57, 27, 'BSc (Hons) in Software Engineering', 'SE', 'aaa', 'English', '12 months', '', 5000.00, 760.00, 0.00, 0.00, 'Diploma', '1', NULL),
(62, 27, 'TEST 1', 't1', 'aaa', 'English', '12 months', '', 5000.00, 350.00, 0.00, 0.00, 'A/L', '50000', NULL),
(63, 1, 'International Foundation Diploma (Applied Science) - ATHE Level 3', 'IFDS', 'aaa', 'English', '12 months', '', 6500.00, 350.00, 0.00, 0.00, 'Diploma,CBM', '2', NULL),
(64, 1, 'BSc (Hons) International Tourism, Hospitality & Events', 'sss', 'aaa', 'English', '18 month', '', 250.00, 250.00, 0.00, 0.00, 'Masters,Diploma', '1', 'final_year');

-- --------------------------------------------------------

--
-- Table structure for table `question_options`
--

CREATE TABLE `question_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(255) NOT NULL,
  `order_index` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registration_links`
--

CREATE TABLE `registration_links` (
  `id` int(11) NOT NULL,
  `programme_id` varchar(50) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `generated_link` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registration_links`
--

INSERT INTO `registration_links` (`id`, `programme_id`, `batch_id`, `token`, `generated_link`, `created_at`) VALUES
(9, '41', 21, 'd9412b8083c85f7ecb0cdd15f3a6e392', 'http://localhost/ims/registration_link/register.php?token=d9412b8083c85f7ecb0cdd15f3a6e392', '2025-11-29 08:45:45'),
(10, '47', 30, 'f2aa2b8a8eebf14fb6ebfdd99719d9a9', 'http://localhost/ims/registration_link/register.php?token=f2aa2b8a8eebf14fb6ebfdd99719d9a9', '2025-11-30 04:31:38'),
(11, '57', 36, '6aef94df6848fd00c53c5adc99b036e0', 'http://localhost/ims/registration_link/register.php?token=6aef94df6848fd00c53c5adc99b036e0', '2025-11-30 04:32:50');

-- --------------------------------------------------------

--
-- Table structure for table `response_answers`
--

CREATE TABLE `response_answers` (
  `id` int(11) NOT NULL,
  `response_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text DEFAULT NULL,
  `answer_type` enum('text','file') NOT NULL DEFAULT 'text'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `response_answers`
--

INSERT INTO `response_answers` (`id`, `response_id`, `question_id`, `answer_text`, `answer_type`) VALUES
(124, 37, 115, 'sdsd', 'text'),
(125, 37, 116, 'sdsds', 'text'),
(126, 37, 117, 'dsds', 'text');

-- --------------------------------------------------------

--
-- Table structure for table `save_assessment_document_send`
--

CREATE TABLE `save_assessment_document_send` (
  `id` int(11) NOT NULL,
  `programme_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `year_id` varchar(255) NOT NULL,
  `semester_id` varchar(255) NOT NULL,
  `assessment_date` datetime DEFAULT NULL,
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
  `subject_body` varchar(255) DEFAULT NULL,
  `entered_by` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `title` varchar(255) DEFAULT NULL,
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
(449, 'Mr', 'Mirshath', 'Testing', 'Mirshath Testing', 'Mirshath Testing', '2011-10-23', 'Saepe earum minus ve', '95 East Hague Lane, Quae in eos quia ve, Enim ducimus non ci, Facere unde natus si', '910 Oak Boulevard, Sunt et doloribus ve, Corrupti itaque com, Doloremque libero co', 'Tempora in nihil dol', '+1 (304) 422-7471', 'Maryam Perkins', '908', 1, 1, 'mir998855V', '', 'yournumplz@gmail.com', 'yournumplz@gmail.com', '', '', '', 'Bachelors,Diploma,ECM,PGDip,IFD,OL', 1, 'Active', 0, '', 'mirshath'),
(450, 'Mrs', 'Hasni', 'H', 'Hasni H', 'Hasni H', '1996-01-24', 'Hic explicabo Labor', '34 Milton Boulevard, Omnis voluptas quae , Asperiores numquam f, Reiciendis tempora e', '37 North First Street, Aut ex nulla deserun, Commodi alias dolor , Esse est et deleni', 'Accusamus qui do sin', '+1 (574) 588-4368', 'Melvin Woods', '474', 1, 1, 'has66332V', '', 'yournumplz@gmail.com', 'yournumplz@gmail.com', '', '', '', 'Bachelors,Masters,Diploma,AL,IFD,OL', 1, 'completed', 0, 'FFF', 'mirshath'),
(451, 'Mrs', 'ASELA', 'Trujillo', 'ASELA Trujillo', 'ASELA Trujillo', '1993-12-14', 'Amet dignissimos la', '85 South Green Hague Drive, Cillum est voluptate, Quia consectetur di, Placeat nostrud cup', '52 South Rocky Cowley Boulevard, Provident eum offic, Quos necessitatibus , Et ipsa quos exerci', 'Fugiat sed et venia', '+1 (663) 517-6337', 'Bernard Gilmore', '922', 1, 1, 'ase6633V', '', 'yournumplz@gmail.com', 'yournumplz@gmail.com', '', '', '', 'Masters,IFD,OL', 1, 'Active', 0, '', 'mirshath'),
(452, 'Mr', 'Q1', 'Delacruz', 'Q1 Delacruz', 'Q1 Delacruz', '1977-07-06', 'Sit et ab nihil fac', '218 East Green Fabien Parkway, Repudiandae tempor i, Sunt quidem iste ap, Voluptatem Exercita', '42 Clarendon Parkway, Mollitia qui ea et d, Voluptas excepturi v, Ut temporibus repreh', 'Qui enim illum qui ', '+1 (531) 272-9796', 'Francesca Wilcox', '557', 0, 1, 'Facilis in ipsam aut', 'In dolor expedita de', 'zyzuwaqyf@mailinator.com', 'xupeqyges@mailinator.com', 'Et hic consequuntur ', 'Hampton and Manning Traders', 'Berry and Cross Plc', 'Masters,ECM,PGDip,IFD', 1, 'Active', 0, '', 'admin'),
(453, 'Prof', 'Q2', 'Wood', 'Q2 Wood', 'Q2 Wood', '2021-08-01', 'Voluptas occaecat pe', '922 East White Clarendon Parkway, Unde corrupti nihil, Iure aut reprehender, Labore ad eum ducimu', '998 Old Avenue, Ex iure vel et labor, Sed et molestiae quo, Qui earum nulla labo', 'Quo atque qui verita', '+1 (331) 924-8311', 'Orson Bass', '103', 1, 0, 'Quod in est porro e', 'Fuga Deserunt odio ', 'zifeqoma@mailinator.com', 'fetex@mailinator.com', 'Quo enim deserunt ni', 'Vaughn and Hurst Co', 'Hoover and Dickerson Traders', 'Bachelors,ECM', 1, 'Active', 0, '', 'admin'),
(454, 'Mr', 'Q3', 'Harmon', 'Q3 Harmon', 'Q3 Harmon', '2019-01-06', 'Mollit similique aut', '764 Nobel Lane, Hic minima illo sunt, Odit aut velit veni, Rerum ut voluptatem', '123 Second Lane, Dolore irure provide, Explicabo Maxime ex, Ullam voluptatem con', 'Explicabo Quidem la', '+1 (634) 747-3008', 'Amir Hogan', '540', 0, 0, 'In laborum magni dic', 'Provident omnis non', 'pazefisi@mailinator.com', 'fezahyduri@mailinator.com', 'Deserunt quia volupt', 'Mann and Pearson Plc', 'Wooten Brewer Plc', 'Bachelors,PGDip', 1, 'Active', 0, '', 'admin'),
(455, 'Mr', 'Q4', 'Boyer', 'Q4 Boyer', 'Q4 Boyer', '2005-03-04', 'Amet aut et aliquam', '411 South Old Road, Voluptate fugiat ea, Aut qui repudiandae , Autem ipsam porro of', '508 West Hague Avenue, Placeat aliquam Nam, Commodi quo voluptat, Ea ab qui non ipsam ', 'Aut quibusdam ipsum', '+1 (481) 466-7179', 'Otto Morse', '686', 1, 0, 'Quibusdam deserunt v', 'Autem voluptas praes', 'cyqe@mailinator.com', 'kesagyh@mailinator.com', 'Alias ea dignissimos', 'Paul Delgado Traders', 'Mack and Buckley Traders', 'Masters,Diploma,PGDip', 1, 'Active', 0, '', 'admin'),
(456, 'Mrs', 'sajaa', 'sajaa', 'Mirshath MMM', 'sajaa sajaa', '2026-06-02', 'Sri Lankan', 'mirsha, Mirsha', 'mirsha, Mirsha', '119', '', 'Mirshath MMM', '', 1, 1, '112233', '', 'mirshath.mmm@gmail.com', 'mirshath.mmm@gmail.com', '', '', '', 'ECM', 1, 'Active', 0, '', 'admin'),
(463, 'Mrs', 't1', 't1', 't1 t1', 't1 t1', '1973-09-01', 'Sunt sit officia ve', '68 South Old Extension, Qui aut molestiae do, Reprehenderit unde , Suscipit ut irure du', '39 South Rocky Fabien Boulevard, Mollit numquam et nu, Mollit numquam a ame, Culpa ducimus dolor', 'Odit vero elit nost', '+1 (371) 263-3457', 'Kevin Sandoval', '672', 1, 1, 'Modi minim enim dolo', 'Reprehenderit volupt', 'lijy@mailinator.com', 'hededyca@mailinator.com', 'Modi aut illo placea', 'Thornton Wade Associates', 'Monroe and Long Trading', 'Bachelors,Masters,Diploma,PGDip,IFD', 1, 'Active', 0, '', 'admin'),
(464, 'Mr', 'T2', 'changed interval', 'T2 changed interval', 'T2 changed interval', '1972-04-23', 'Quam quidem sunt ips', '545 South Clarendon Lane, In qui aliquid aliqu, Sed porro ad occaeca, Veritatis laboris es', '102 New Road, Anim repudiandae rec, Exercitation eum des, Consequuntur illum ', 'Culpa harum quaerat', '+1 (474) 219-6497', 'Michelle Thornton', '606', 0, 0, 'Sequi non dolores qu', 'Ex reprehenderit vo', 'gujyhyh@mailinator.com', 'jovewijy@mailinator.com', 'Consectetur sequi d', 'Booth Underwood Associates', 'Doyle and Beach Inc', 'Diploma,ECM,AL,IFD,OL', 1, 'Active', 0, '', 'admin'),
(469, 'Mr', 'AAAA', 'AAASS', 'AAAA', 'AAA', '2000-01-01', 'Srilanka', 'srilanka', 'srilanka', '0777123456', '123456789', 'mother', '123456789', 1, 1, '123456789V', '', 'mirshath.mmm@gmail.com', 'addaaa@gmail.com', 'aa', 'aa', 'aa', 'Masters,Diploma', 1, 'Active', 0, '', 'admin'),
(470, 'ms', 'BBBB', 'BBBB', 'BBBBB', 'BBBBB', '2000-01-01', 'Srilanka', 'srilanka', 'srilanka', '1122334455', '77712346', 'mother', '77712346', 1, 1, '654987321V', '', 'yournumplz@gmail.com', 'bbbb@gmail.com', '', '', '', 'Masters,Diploma', 1, 'Active', 0, '', 'admin'),
(471, 'Mr', 'AAAA', 'AAA', 'AAAA', 'AAA', '2000-01-01', 'Srilanka', 'srilanka', 'srilanka', '123456789', '123456789', 'mother', '123456789', 1, 1, '12346789ccV', '', 'mirshath.mmm@gmail.com', 'mirshath.mmm@gmail.com', 'aa', 'aa', 'aa', 'Masters,Diploma', 1, 'Active', 0, '', 'admin'),
(472, 'ms', 'BBBB', 'BBBB', 'BBBBB', 'BBBBB', '2000-01-01', 'Srilanka', 'srilanka', 'srilanka', '77712346', '77712346', 'mother', '77712346', 1, 1, '65498732cc1v', '', 'yournumplz@gmail.com', 'yournumplz@gmail.com', '', '', '', 'Masters,Diploma', 1, 'Active', 0, '', 'admin'),
(473, 'Mrs', 'eeeeeeeeeeeee', 'Sykes', 'eeeeeeeeeeeee Sykes', 'eeeeeeeeeeeee Sykes', '2005-01-21', 'Qui in veniam in si', '816 South Clarendon Lane, Delectus mollit eaq, Voluptate esse et te, Cillum quo dolorum o', '929 Hague Parkway, Qui eos necessitatib, Ipsum neque quos exp, Nihil aut aute ducim', 'Sunt duis soluta ver', '+1 (372) 704-8782', 'Lynn Macias', '695', 1, 0, 'Eos dolor officiis ', 'Reprehenderit quo si', 'bybalugej@mailinator.com', 'denozik@mailinator.com', 'Inventore laborum mo', 'Alvarez Roy Associates', 'Hardin Travis Inc', 'Bachelors,Diploma,ECM,AL', 1, 'Active', 0, '', 'admin');

--
-- Triggers `students`
--
DELIMITER $$
CREATE TRIGGER `trg_std_insert` AFTER INSERT ON `students` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('students', NEW.student_code, 'insert',
            CONCAT('New student added: ', NEW.first_name, ' ', NEW.last_name));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_std_update` AFTER UPDATE ON `students` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('students', NEW.student_code, 'update',
            CONCAT('Student updated: ', NEW.first_name, ' ', NEW.last_name));
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `students_temporary_document`
--

CREATE TABLE `students_temporary_document` (
  `id` int(11) NOT NULL,
  `student_auto_id` int(5) NOT NULL,
  `temp_id` varchar(50) NOT NULL,
  `nic` varchar(50) NOT NULL,
  `doc0` varchar(255) DEFAULT NULL,
  `doc1` varchar(255) DEFAULT NULL,
  `doc2` varchar(255) DEFAULT NULL,
  `doc3` varchar(255) DEFAULT NULL,
  `doc4` varchar(255) DEFAULT NULL,
  `degree_certificate` varchar(255) DEFAULT NULL,
  `transcript` varchar(255) DEFAULT NULL,
  `other_qualification_1` varchar(255) DEFAULT NULL,
  `other_qualification_2` varchar(255) DEFAULT NULL,
  `uploaded_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `students_temporary_document`
--
DELIMITER $$
CREATE TRIGGER `trg_no_delete_students_temp_doc` BEFORE DELETE ON `students_temporary_document` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Delete operation is not allowed on students_temporary_document.';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `students_temporary_registration`
--

CREATE TABLE `students_temporary_registration` (
  `id` int(11) NOT NULL,
  `temp_id` varchar(50) NOT NULL,
  `token` varchar(100) NOT NULL,
  `title` varchar(50) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `fullname` varchar(255) DEFAULT NULL,
  `certificate_name` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(50) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `permanent_address` text DEFAULT NULL,
  `current_address` text DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `home_number` varchar(50) DEFAULT NULL,
  `office_number` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(255) DEFAULT NULL,
  `nic` varchar(50) DEFAULT NULL,
  `passport` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `batch` varchar(100) DEFAULT NULL,
  `std_entered_batch` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved` varchar(255) DEFAULT NULL,
  `approved_by` varchar(255) DEFAULT NULL,
  `std_doc_upload_btn` tinyint(4) DEFAULT 0,
  `conditional_offer_letter` varchar(255) DEFAULT NULL,
  `conditional_offer_letter_text` varchar(255) DEFAULT NULL,
  `conditional_offer_letter_text_02` varchar(255) DEFAULT NULL,
  `conditional_offer_letter_text_03` varchar(255) DEFAULT NULL,
  `conditional_offer_letter_text_04` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students_temporary_registration`
--

INSERT INTO `students_temporary_registration` (`id`, `temp_id`, `token`, `title`, `firstname`, `lastname`, `fullname`, `certificate_name`, `dob`, `gender`, `nationality`, `permanent_address`, `current_address`, `mobile`, `home_number`, `office_number`, `emergency_contact`, `nic`, `passport`, `email`, `program`, `batch`, `std_entered_batch`, `created_at`, `approved`, `approved_by`, `std_doc_upload_btn`, `conditional_offer_letter`, `conditional_offer_letter_text`, `conditional_offer_letter_text_02`, `conditional_offer_letter_text_03`, `conditional_offer_letter_text_04`) VALUES
(1, 'BMSBVB', '89fb8333828aeba5340a0e83e9a7158a', 'Other', 'GFG', 'Hopkins', 'GFG Hopkins', 'GFG Hopkins', '2025-12-15', 'Female', 'Indian', 'Kanadara Katukeliyawa, Ihalagama, Mihintale', 'Kanadara Katukeliyawa, Ihalagama, Mihintale', '+9414752285874', '+1 (814) 293-2078', '+1 (471) 661-9255', 'Pariatur Omnis culp', 'HHGGdddf', '', 'yournumplz@gmail.com', 'Graduate Diploma in Management (Level 6)', 'Batch 85', 'Batch 85', '2026-01-28 01:18:39', '1', 'mirshath', 1, '1', 'Some Changes need to be completed before ou join the This Program', 'Some Changes need to be completed before ou join the This Program', 'Some Changes need to be completed before ou join the This Program', 'Some Changes need to be completed before ou join the This Program');

--
-- Triggers `students_temporary_registration`
--
DELIMITER $$
CREATE TRIGGER `trg_no_delete_students_temp_reg` BEFORE DELETE ON `students_temporary_registration` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Delete operation is not allowed on students_temporary_registration.';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_qualifications`
--

CREATE TABLE `student_academic_qualifications` (
  `id` int(11) NOT NULL,
  `temp_id` varchar(40) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `qualification` varchar(200) NOT NULL,
  `institution` varchar(150) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_academic_qualifications`
--

INSERT INTO `student_academic_qualifications` (`id`, `temp_id`, `registration_id`, `qualification`, `institution`, `year`, `notes`, `created_at`) VALUES
(1, 'BMS000000V', 5, 'Cum quis eum invent', 'Minim est sint perfere', '1991', 'Expedita dol', '2026-01-28 04:51:02'),
(2, 'BMS000000V', 5, 'Corporis facere obcaec', 'Nam eos error minus su', '2014', 'Dolor nostr', '2026-01-28 04:51:02'),
(3, 'BMS000000V', 5, 'Vero minim ullamco sin', 'Qui qui non veniam sus', '1988', 'Elit aut qu', '2026-01-28 04:51:02');

--
-- Triggers `student_academic_qualifications`
--
DELIMITER $$
CREATE TRIGGER `trg_no_delete_academic_qual` BEFORE DELETE ON `student_academic_qualifications` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Delete operation is not allowed on student_academic_qualifications.';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_al_results`
--

CREATE TABLE `student_al_results` (
  `id` int(11) NOT NULL,
  `temp_id` varchar(40) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `grade` varchar(10) NOT NULL,
  `exam_year` varchar(10) DEFAULT NULL,
  `school` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_al_results`
--

INSERT INTO `student_al_results` (`id`, `temp_id`, `registration_id`, `subject`, `grade`, `exam_year`, `school`, `created_at`) VALUES
(124, 'BMS998877665544', 112, 'odit voluptates ut pariatur', 'Lib', '2013', 'o e numquam ea in', '2026-01-05 08:20:35'),
(125, 'BMS998877665544', 112, 'te obcaecati quia qu', 'Nat', '2013', 'o e numquam ea in', '2026-01-05 08:20:35'),
(126, 'BMS998877665544', 112, 'a est rem fuga', 'Vit', '2013', 'o e numquam ea in', '2026-01-05 08:20:35'),
(127, 'BMS3331', 113, 'Asperiores neque adipisicing officia eaque consequatur Ea ullam dolorem harum ducimus commodo', 'Ver', '2017', 'A enim anim reprehenderit aut unde nihil sed ea et', '2026-01-05 10:27:31'),
(128, 'BMS3331', 113, 'Aliquip voluptatem nostrum dolor veniam nesciunt autem quia rerum non voluptatem Quo lorem aliqu', 'Exe', '2017', 'A enim anim reprehenderit aut unde nihil sed ea et', '2026-01-05 10:27:31'),
(129, 'BMS3331', 113, 'Ea dolore sit laboriosam fugit velit qui dolorum sed', 'Asp', '2017', 'A enim anim reprehenderit aut unde nihil sed ea et', '2026-01-05 10:27:31'),
(130, 'BMSUT VOLUPTAS TEMPOR A', 114, 'Est consequatur Voluptatem Similique ut lorem nesciunt eveniet autem dolor veniam voluptate', 'Max', '1970', 'Dolorem amet proident officia id qui tempor omnis vel porro voluptatem itaque', '2026-01-05 10:34:02'),
(131, 'BMSUT VOLUPTAS TEMPOR A', 114, 'Do repudiandae reprehenderit facilis labore deserunt non', 'Inc', '1970', 'Dolorem amet proident officia id qui tempor omnis vel porro voluptatem itaque', '2026-01-05 10:34:02'),
(132, 'BMSUT VOLUPTAS TEMPOR A', 114, 'Ut quis aspernatur adipisci nostrud quibusdam dolor deserunt consequatur fugiat est lorem dolorum', 'Lab', '1970', 'Dolorem amet proident officia id qui tempor omnis vel porro voluptatem itaque', '2026-01-05 10:34:02'),
(133, 'BMSULLAM AUT QUIA DISTI', 115, 'Consequat Reprehenderit ducimus qui magna architecto quis voluptatem Qui maiores fugiat nulla', 'Est', '1989', 'Autem culpa qui illo tempore et aut soluta omnis deleniti itaque ad amet in ea quos', '2026-01-05 10:40:23'),
(134, 'BMSULLAM AUT QUIA DISTI', 115, 'Reprehenderit aut amet amet ut velit molestias similique tempore dolorem sunt aut nihil esse', 'Ips', '1989', 'Autem culpa qui illo tempore et aut soluta omnis deleniti itaque ad amet in ea quos', '2026-01-05 10:40:23'),
(135, 'BMSULLAM AUT QUIA DISTI', 115, 'Incidunt aliqua Autem in eligendi ad', 'Cum', '1989', 'Autem culpa qui illo tempore et aut soluta omnis deleniti itaque ad amet in ea quos', '2026-01-05 10:40:23'),
(340, 'BMSMNM001V', 88, 'Ipsam cupiditate neque e', 'Ven', '1972', 'Et velit optio', '2026-01-27 12:15:58'),
(341, 'BMSMNM001V', 88, 'Id nostrud pariatur Qui un', 'Dol', '1972', 'Et velit optio', '2026-01-27 12:15:58'),
(342, 'BMSMNM001V', 88, 'Ut lorem et est doloremqu', 'Pro', '1972', 'Et velit optio', '2026-01-27 12:15:58'),
(343, 'BMSNIC0000000000V', 3, 'Est in quia repudiandae du', 'Sed', '1970', 'Voluptate provi', '2026-01-28 03:35:32'),
(344, 'BMSNIC0000000000V', 3, 'Laborum duis recusand', 'Vel', '1970', 'Voluptate provi', '2026-01-28 03:35:32'),
(345, 'BMSNIC0000000000V', 3, 'Veritatis voluptate blan', 'Arc', '1970', 'Voluptate provi', '2026-01-28 03:35:32'),
(346, 'BMS000000V', 5, 'Fuga Itaque dolores debitis a', 'Sit', '1973', 'Sequi voluptatibqui dolorem', '2026-01-28 04:51:02'),
(347, 'BMS000000V', 5, 'Et sapiente ut atque autem', 'Est', '1973', 'Sequi voluptatibqui dolorem', '2026-01-28 04:51:02'),
(348, 'BMS000000V', 5, 'Eius iure reprehenderit sun', 'Quo', '1973', 'Sequi voluptatibqui dolorem', '2026-01-28 04:51:02');

--
-- Triggers `student_al_results`
--
DELIMITER $$
CREATE TRIGGER `trg_no_delete_al_results` BEFORE DELETE ON `student_al_results` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Delete operation is not allowed on student_al_results.';
END
$$
DELIMITER ;

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
-- Table structure for table `student_ol_results`
--

CREATE TABLE `student_ol_results` (
  `id` int(11) NOT NULL,
  `temp_id` varchar(40) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `grade` varchar(10) NOT NULL,
  `exam_year` varchar(10) DEFAULT NULL,
  `school` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_ol_results`
--

INSERT INTO `student_ol_results` (`id`, `temp_id`, `registration_id`, `subject`, `grade`, `exam_year`, `school`, `created_at`) VALUES
(1, 'BMS000000V', 5, 'Dolor dolore asperiore', 'Sed', '1996', 'Veritatis au', '2026-01-28 04:51:02'),
(2, 'BMS000000V', 5, 'Sed et quo laboris co', 'Quo', '1996', 'Veritatis au', '2026-01-28 04:51:02'),
(3, 'BMS000000V', 5, 'Quas recusandae A', 'Exp', '1996', 'Veritatis au', '2026-01-28 04:51:02'),
(4, 'BMS000000V', 5, 'Quisquam in volupin pr', 'Vol', '1996', 'Veritatis au', '2026-01-28 04:51:02'),
(5, 'BMS000000V', 5, 'Dolor dolor tempo', 'Neq', '1996', 'Veritatis au', '2026-01-28 04:51:02'),
(6, 'BMS000000V', 5, 'Esse praesentium la', 'Eni', '1996', 'Veritatis au', '2026-01-28 04:51:02'),
(7, 'BMS000000V', 5, 'Quae nesciunt aut t', 'Et', '1996', 'Veritatis au', '2026-01-28 04:51:02'),
(8, 'BMS000000V', 5, 'Numquam reprehen', 'Cor', '1996', 'Veritatis au', '2026-01-28 04:51:02'),
(9, 'BMS000000V', 5, 'Voluptatum in impe', 'Eos', '1996', 'Veritatis au', '2026-01-28 04:51:02');

--
-- Triggers `student_ol_results`
--
DELIMITER $$
CREATE TRIGGER `trg_no_delete_ol_results` BEFORE DELETE ON `student_ol_results` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Delete operation is not allowed on student_ol_results.';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `student_other_qualifications`
--

CREATE TABLE `student_other_qualifications` (
  `id` int(11) NOT NULL,
  `temp_id` varchar(40) NOT NULL,
  `registration_id` int(11) NOT NULL,
  `details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_other_qualifications`
--

INSERT INTO `student_other_qualifications` (`id`, `temp_id`, `registration_id`, `details`, `created_at`) VALUES
(1, 'BMS000000V', 5, 'Aut iste ipsa dolor', '2026-01-28 04:51:02');

--
-- Triggers `student_other_qualifications`
--
DELIMITER $$
CREATE TRIGGER `trg_no_delete_other_qual` BEFORE DELETE ON `student_other_qualifications` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Delete operation is not allowed on student_other_qualifications.';
END
$$
DELIMITER ;

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
  `converted_marks_grade` varchar(255) DEFAULT NULL,
  `ecm_r1_converted_marks` varchar(250) DEFAULT NULL,
  `ecm_r1_converted_marks_grade` varchar(255) DEFAULT NULL,
  `ecm_r2_converted_marks` varchar(200) DEFAULT NULL,
  `ecm_r2_converted_marks_grade` varchar(255) DEFAULT NULL,
  `ecm_r3_converted_marks` varchar(200) DEFAULT NULL,
  `ecm_r3_converted_marks_grade` varchar(255) DEFAULT NULL,
  `ecm_r4_converted_marks` varchar(200) DEFAULT NULL,
  `ecm_r4_converted_marks_grade` varchar(255) DEFAULT NULL,
  `hd_full_marks` decimal(10,2) DEFAULT NULL,
  `hd_converted_marks` int(11) DEFAULT NULL,
  `hd_grade` varchar(50) DEFAULT NULL,
  `hd_resit1_full_marks` decimal(10,2) DEFAULT NULL,
  `hd_resit1_converted_marks` int(11) DEFAULT NULL,
  `hd_resit1_grade` varchar(50) DEFAULT NULL,
  `hd_resit2_full_marks` decimal(10,2) DEFAULT NULL,
  `hd_resit2_converted_marks` int(11) DEFAULT NULL,
  `hd_resit2_grade` varchar(40) DEFAULT NULL,
  `hd_resit3_full_marks` decimal(10,2) DEFAULT NULL,
  `hd_resit3_converted_marks` int(11) DEFAULT NULL,
  `hd_resit3_grade` varchar(50) DEFAULT NULL,
  `entered_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `comment` text DEFAULT NULL COMMENT 'Student comment or feedback for this result',
  `description_for_std` text DEFAULT NULL COMMENT 'Description for students for this assessment'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_results`
--

INSERT INTO `student_results` (`id`, `student_id`, `student_registration_id`, `program_id`, `batch_id`, `module_id`, `main_component_id`, `sub_component_id`, `result`, `resit_result_1`, `resit_result_2`, `resit_result_3`, `resit_result_4`, `full_marks`, `converted_marks`, `converted_marks_grade`, `ecm_r1_converted_marks`, `ecm_r1_converted_marks_grade`, `ecm_r2_converted_marks`, `ecm_r2_converted_marks_grade`, `ecm_r3_converted_marks`, `ecm_r3_converted_marks_grade`, `ecm_r4_converted_marks`, `ecm_r4_converted_marks_grade`, `hd_full_marks`, `hd_converted_marks`, `hd_grade`, `hd_resit1_full_marks`, `hd_resit1_converted_marks`, `hd_resit1_grade`, `hd_resit2_full_marks`, `hd_resit2_converted_marks`, `hd_resit2_grade`, `hd_resit3_full_marks`, `hd_resit3_converted_marks`, `hd_resit3_grade`, `entered_by`, `created_at`, `comment`, `description_for_std`) VALUES
(1, 451, '476052503', 46, 22, 184, 56, NULL, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:50:50', '', ''),
(2, 452, '476052504', 46, 22, 184, 56, NULL, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:50:50', '', ''),
(3, 453, '476052505', 46, 22, 184, 56, NULL, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:50:50', '', ''),
(4, 454, '476052506', 46, 22, 184, 56, NULL, 'distinction', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:50:50', '', ''),
(5, 455, '476052507', 46, 22, 184, 56, NULL, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:50:50', '', ''),
(6, 451, '476052503', 46, 22, 184, 55, NULL, 'pass', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:51:40', '', ''),
(7, 452, '476052504', 46, 22, 184, 55, NULL, 'resit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:51:40', '', ''),
(8, 453, '476052505', 46, 22, 184, 55, NULL, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:51:40', '', ''),
(9, 454, '476052506', 46, 22, 184, 55, NULL, 'not submitted', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:51:40', '', ''),
(10, 455, '476052507', 46, 22, 184, 55, NULL, 'absent', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-08-14 04:51:40', '', '');

--
-- Triggers `student_results`
--
DELIMITER $$
CREATE TRIGGER `trg_sr_insert` AFTER INSERT ON `student_results` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('student_results', NEW.id, 'insert',
            CONCAT('New student result added: student_id=', NEW.student_id));
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_sr_update` AFTER UPDATE ON `student_results` FOR EACH ROW BEGIN
    INSERT INTO notifications(table_name, row_id, action, message)
    VALUES ('student_results', NEW.id, 'update',
            CONCAT('Student result updated: student_id=', NEW.student_id));
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
(12, 'Group Assignment / QUI', '40%'),
(13, 'ee', '44');

-- --------------------------------------------------------

--
-- Table structure for table `tessssssssssss`
--

CREATE TABLE `tessssssssssss` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `program_code` varchar(50) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `class_date` date DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `classroom` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `program_code`, `batch_id`, `module_id`, `day_of_week`, `class_date`, `start_time`, `end_time`, `classroom`, `created_at`) VALUES
(2, '46', 34, 184, 'Sunday', '2025-05-18', '10:24:00', '13:16:00', 'E5', '2025-05-18 04:46:50'),
(3, '46', 34, 196, 'Monday', '2025-05-19', '15:21:00', '16:40:00', 'E5', '2025-05-19 10:52:01'),
(4, '46', 34, 193, 'Monday', '2025-05-19', '16:41:00', '18:40:00', 'E5', '2025-05-19 11:09:34');

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
(27, 'UN02', 'CGS', 'Wellawatte', 'UEX02', '2025-12-15 04:51:41'),
(28, 'UN03', 'BCC', 'Matara', 'UEX03', '2025-12-15 04:52:10');

--
-- Triggers `universities`
--
DELIMITER $$
CREATE TRIGGER `prevent_universities_delete` BEFORE DELETE ON `universities` FOR EACH ROW BEGIN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = '❌ University records cannot be deleted.';
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_universities_insert` AFTER INSERT ON `universities` FOR EACH ROW BEGIN
    INSERT INTO `notifications` (`table_name`, `row_id`, `action`, `message`)
    VALUES (
        'universities',
        NEW.id,
        'insert',
        CONCAT(
            '? New university added: ',
            IFNULL(NEW.university_name, 'Unknown')
        )
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_universities_update` AFTER UPDATE ON `universities` FOR EACH ROW BEGIN
    INSERT INTO `notifications` (`table_name`, `row_id`, `action`, `message`)
    VALUES (
        'universities',
        NEW.id,
        'update',
        CONCAT(
            '? University updated: ',
            IFNULL(NEW.university_name, 'Unknown')
        )
    );
END
$$
DELIMITER ;

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
(11579, 12, 1, 'Lead Types - create_lead'),
(11580, 12, 1, 'Year - create_year'),
(11581, 12, 1, 'Semester - create_semester'),
(11582, 12, 1, 'Criteria - create_criteria'),
(11583, 12, 1, 'University - create_universities'),
(11584, 12, 1, 'Coordinator - create_coordinators'),
(11585, 12, 1, 'Program - create_program'),
(11586, 12, 1, 'Assignment Components - create_assignment_components'),
(11587, 12, 1, 'Lecture - create_lecturer'),
(11588, 12, 1, 'Module - create_module'),
(11589, 12, 1, 'Batch - create_batch'),
(11590, 12, 1, 'Grade - create_grade'),
(11591, 12, 1, 'Currency - create_currency'),
(11592, 12, 1, 'Status - create_status'),
(11593, 12, 1, 'Decision - create_decision'),
(11594, 12, 2, 'Add Leads - addLeads'),
(11595, 12, 2, 'Student Registration - studentRegister'),
(11596, 12, 2, 'Upload Students - uploadStudents'),
(11597, 12, 2, 'Allocate Program - allocateProgram'),
(11598, 12, 2, 'Components Allocations - assign_components_allocations'),
(11599, 12, 2, 'Update Student Status - UpdateStudentStatus'),
(11600, 12, 2, 'Student Batch Transfer - batch_transfer'),
(11601, 12, 2, 'Update Students E Module - UpdateElectiveModule'),
(11602, 12, 2, 'Upload Student Documents - uploadScanCopies'),
(11603, 12, 2, 'Add Payment Plan - add_payment_plan'),
(11604, 12, 2, 'Batch Wise Payment Plan - batch_wise_payment_plan'),
(11605, 12, 2, 'Payment - payment'),
(11606, 12, 2, 'Penalty Payment - penalty_pay'),
(11607, 12, 2, 'Additional Payment - AdditionalFee'),
(11608, 12, 2, 'Send Offer Letter - offer_letter'),
(11609, 12, 2, 'Time Table - AddTimeTable'),
(11610, 12, 2, 'Daily Time Table Message -'),
(11611, 12, 2, 'Special Class Messages - SpecialClassMessages'),
(11612, 12, 2, 'Assesment Management - exams'),
(11613, 12, 2, 'Assesment Document Send - assesmentDocumentSend'),
(11614, 12, 2, 'Exam / Assignment Result - exam_result'),
(11615, 12, 2, 'E/A Result Send - exam_result_send'),
(11616, 12, 2, 'Module Results Mail - module_result_mail'),
(11617, 12, 2, 'Special Reason - specialReason'),
(11618, 12, 2, 'Add Decision -'),
(11619, 12, 2, 'Alumni -'),
(11620, 12, 2, 'List Board -'),
(11621, 12, 3, 'Edit Programme Allocation - editAllocateProgram'),
(11622, 12, 3, 'Edit Payment - editPayment'),
(11623, 12, 4, ''),
(11624, 12, 5, 'All Student Details - allStudentDetails'),
(11625, 12, 5, 'Student Wise Details - studentWiseDetails'),
(11626, 12, 5, 'Leads Report - leadsReport'),
(11627, 12, 5, 'Outstanding Payment - outstandingPayment'),
(11628, 12, 5, 'Special Reason Report - specialReasonReport'),
(11629, 12, 5, 'Exam / Assignment Report - exam_report'),
(11630, 12, 5, 'Result Mailing Report - result_mailing_report'),
(11631, 12, 5, 'Payment Report - paymentReport'),
(11632, 12, 5, 'Penalty Payment Report - penaltyPaymentReport'),
(11633, 12, 5, 'Additional Payment Report - additionalPaymentReport'),
(11634, 12, 5, 'Time Table Report - timeTableReport'),
(11635, 12, 5, 'Alumni Report - aluminiReport'),
(11636, 12, 5, 'BBM result report - BBM_result_report'),
(11637, 12, 7, 'card_enable - index'),
(11638, 12, 8, 'payment cancellation - PaymentCancellation'),
(11639, 12, 8, 'penalty payment cancellation - PenaltyPaymentCancellation'),
(11640, 12, 8, 'Additional payment cancellation - AdditionalPaymentCancellation'),
(11641, 12, 9, 'exams_asses_send_button'),
(11642, 12, 9, 'exams_asses_edit_button'),
(11643, 12, 9, 'exams_asses_document_send_button'),
(11644, 12, 9, 'exams_asses_document_edit_button'),
(19657, 4, 1, 'Lead Types - create_lead'),
(19658, 4, 1, 'DBTablesCount - db_tables_count'),
(19659, 4, 1, 'Year - create_year'),
(19660, 4, 1, 'Semester - create_semester'),
(19661, 4, 1, 'Criteria - create_criteria'),
(19662, 4, 1, 'University - create_universities'),
(19663, 4, 1, 'Coordinator - create_coordinators'),
(19664, 4, 1, 'Program - create_program'),
(19665, 4, 1, 'UserProgram - program_to_user'),
(19666, 4, 1, 'Assignment Components - create_assignment_components'),
(19667, 4, 1, 'Lecture - create_lecturer'),
(19668, 4, 1, 'One To One Student Upload - upload_Students_one_to_one_student'),
(19669, 4, 1, 'Module - create_module'),
(19670, 4, 1, 'Batch - create_batch'),
(19671, 4, 1, 'Grade - create_grade'),
(19672, 4, 1, 'Currency - create_currency'),
(19673, 4, 1, 'Status - create_status'),
(19674, 4, 1, 'Decision - create_decision'),
(19675, 4, 1, 'Batch Payment Allocation - program_payment_allocation'),
(19676, 4, 1, 'Induction Edit - induction_master'),
(19677, 4, 1, 'Components Allocations - assign_components_allocations'),
(19678, 4, 1, 'Induction Edit From DB - induction_master_db'),
(19679, 4, 2, 'Assesment Management - exams'),
(19680, 4, 2, 'Assesment Document Send - assesmentDocumentSend'),
(19681, 4, 2, 'Exam / Assignment Result - exam_result'),
(19682, 4, 2, 'E/A Result Send - exam_result_send'),
(19683, 4, 2, 'Module Results Mail - module_result_mail'),
(19684, 4, 2, 'Time Table - AddTimeTable'),
(19685, 4, 2, 'Daily Time Table Message -'),
(19686, 4, 2, 'Special Class Messages - SpecialClassMessages'),
(19687, 4, 2, 'Special Reason - specialReason'),
(19688, 4, 2, 'Add Decision -'),
(19689, 4, 2, 'Alumni -'),
(19690, 4, 2, 'List Board -'),
(19691, 4, 3, 'Edit Programme Allocation - editAllocateProgram'),
(19692, 4, 3, 'Edit Payment - editPayment'),
(19693, 4, 4, ''),
(19694, 4, 5, 'All Student Details - allStudentDetails'),
(19695, 4, 5, 'Student Wise Details - studentWiseDetails'),
(19696, 4, 5, 'Leads Report - leadsReport'),
(19697, 4, 5, 'Outstanding Payment - outstandingPayment'),
(19698, 4, 5, 'Special Reason Report - specialReasonReport'),
(19699, 4, 5, 'Exam / Assignment Report - exam_report'),
(19700, 4, 5, 'Result Mailing Report - result_mailing_report'),
(19701, 4, 5, 'Payment Report - paymentReport'),
(19702, 4, 5, 'Penalty Payment Report - penaltyPaymentReport'),
(19703, 4, 5, 'Additional Payment Report - additionalPaymentReport'),
(19704, 4, 5, 'Time Table Report - timeTableReport'),
(19705, 4, 5, 'Alumni Report - aluminiReport'),
(19706, 4, 5, 'BBM result report - BBM_result_report'),
(19707, 4, 5, 'Total Induction Student List - total_induction_students'),
(19708, 4, 5, 'Online Registration Report - get_data_from_std'),
(19709, 4, 5, 'Payment Check Report - check_all_data_for'),
(19710, 4, 5, 'Induction DB Student List - induction_fl_report_db'),
(19711, 4, 6, 'Add Users - addUser'),
(19712, 4, 6, 'User Permission - userPermission'),
(19713, 4, 6, 'All Notifications - all_notifications'),
(19714, 4, 6, 'Student Check Payments - studentCheckPayment'),
(19715, 4, 7, 'card_enable - index'),
(19716, 4, 7, 'Inactive Students - inactive_students'),
(19717, 4, 8, 'payment cancellation - PaymentCancellation'),
(19718, 4, 8, 'penalty payment cancellation - PenaltyPaymentCancellation'),
(19719, 4, 8, 'Additional payment cancellation - AdditionalPaymentCancellation'),
(19720, 4, 9, 'exams_asses_send_button'),
(19721, 4, 9, 'exams_asses_edit_button'),
(19722, 4, 9, 'exams_asses_document_send_button'),
(19723, 4, 9, 'exams_asses_document_edit_button'),
(19724, 4, 10, 'Feedback Link Generate - feedback_link_generate'),
(19725, 4, 10, 'Feedback Report - feedback_report'),
(19726, 4, 11, 'Induction Scan - induction_scan'),
(19727, 4, 11, 'Induction Upload Student - upload_induction_students'),
(19728, 4, 11, ''),
(19729, 4, 11, 'Induction Email Send - induction_from_db_email_send'),
(19730, 4, 11, 'Induction Scan From DB - induction_scan_db'),
(19731, 4, 11, 'Induction DB Student List - induction_fl_report_db'),
(19732, 4, 11, 'Induction Email Report - induction_email_report'),
(19733, 4, 11, 'Induction Email Template - induction_email_body_page'),
(19734, 4, 13, 'Add Leads - addLeads'),
(19735, 4, 13, 'Student Registration - studentRegister'),
(19736, 4, 13, 'Online Registration Data - online_registration_data'),
(19737, 4, 13, 'Upload Students - uploadStudents'),
(19738, 4, 13, 'Allocate Program - allocateProgram'),
(19739, 4, 13, 'BMS Email Allocation - bms_email_allocation'),
(19740, 4, 13, 'StudentID Allocation - studentID_allocation'),
(19741, 4, 13, 'Update Student Status - UpdateStudentStatus'),
(19742, 4, 13, 'Student Batch Transfer - batch_transfer'),
(19743, 4, 13, 'Student Batch Swap - batchSwap'),
(19744, 4, 13, 'Student Program Progression - programProgression'),
(19745, 4, 13, 'Update Students E Module - UpdateElectiveModule'),
(19746, 4, 13, 'Upload Student Documents - uploadScanCopies'),
(19747, 4, 13, 'Send Offer Letter - offer_letter'),
(19748, 4, 14, 'Add Payment Plan - add_payment_plan'),
(19749, 4, 14, 'Batch Wise Payment Plan - batch_wise_payment_plan'),
(19750, 4, 14, 'Payment - payment'),
(19751, 4, 14, 'Penalty Payment - penalty_pay'),
(19752, 4, 14, 'Additional Payment - AdditionalFee'),
(19753, 4, 16, 'POS Store / Checkout - pos_store'),
(19754, 4, 16, 'POS Dashboard - pos_dashboard'),
(19755, 4, 16, 'POS Manage Products - pos_products'),
(19756, 4, 16, 'POS Reports - pos_reports'),
(19757, 4, 16, 'POS Detailed Reports - pos_detailed_report'),
(19758, 4, 16, 'POS Detailed Sales - pos_detailed_sales'),
(20072, 22, 1, 'Lead Types - create_lead'),
(20073, 22, 1, 'DBTablesCount - db_tables_count'),
(20074, 22, 1, 'Year - create_year'),
(20075, 22, 1, 'Semester - create_semester'),
(20076, 22, 1, 'Criteria - create_criteria'),
(20077, 22, 1, 'University - create_universities'),
(20078, 22, 1, 'Coordinator - create_coordinators'),
(20079, 22, 1, 'Program - create_program'),
(20080, 22, 1, 'UserProgram - program_to_user'),
(20081, 22, 1, 'Assignment Components - create_assignment_components'),
(20082, 22, 1, 'Lecture - create_lecturer'),
(20083, 22, 1, 'One To One Student Upload - upload_Students_one_to_one_student'),
(20084, 22, 1, 'Module - create_module'),
(20085, 22, 1, 'Batch - create_batch'),
(20086, 22, 1, 'Grade - create_grade'),
(20087, 22, 1, 'Currency - create_currency'),
(20088, 22, 1, 'Status - create_status'),
(20089, 22, 1, 'Decision - create_decision'),
(20090, 22, 1, 'Batch Payment Allocation - program_payment_allocation'),
(20091, 22, 1, 'Induction Edit - induction_master'),
(20092, 22, 1, 'Components Allocations - assign_components_allocations'),
(20093, 22, 1, 'Induction Edit From DB - induction_master_db'),
(20094, 22, 1, 'Nav Collection CRUD - master_nav_collection'),
(20095, 22, 2, 'Assesment Management - exams'),
(20096, 22, 2, 'Assesment Document Send - assesmentDocumentSend'),
(20097, 22, 2, 'Exam / Assignment Result - exam_result'),
(20098, 22, 2, 'E/A Result Send - exam_result_send'),
(20099, 22, 2, 'Module Results Mail - module_result_mail'),
(20100, 22, 2, 'Time Table - AddTimeTable'),
(20101, 22, 2, 'Daily Time Table Message -'),
(20102, 22, 2, 'Special Class Messages - SpecialClassMessages'),
(20103, 22, 2, 'Special Reason - specialReason'),
(20104, 22, 2, 'Add Decision -'),
(20105, 22, 2, 'Alumni -'),
(20106, 22, 2, 'List Board -'),
(20107, 22, 3, 'Edit Programme Allocation - editAllocateProgram'),
(20108, 22, 3, 'Edit Payment - editPayment'),
(20109, 22, 4, ''),
(20110, 22, 5, 'All Student Details - allStudentDetails'),
(20111, 22, 5, 'Student Wise Details - studentWiseDetails'),
(20112, 22, 5, 'Leads Report - leadsReport'),
(20113, 22, 5, 'Outstanding Payment - outstandingPayment'),
(20114, 22, 5, 'Special Reason Report - specialReasonReport'),
(20115, 22, 5, 'Exam / Assignment Report - exam_report'),
(20116, 22, 5, 'Result Mailing Report - result_mailing_report'),
(20117, 22, 5, 'Payment Report - paymentReport'),
(20118, 22, 5, 'Penalty Payment Report - penaltyPaymentReport'),
(20119, 22, 5, 'Additional Payment Report - additionalPaymentReport'),
(20120, 22, 5, 'Time Table Report - timeTableReport'),
(20121, 22, 5, 'Alumni Report - aluminiReport'),
(20122, 22, 5, 'BBM result report - BBM_result_report'),
(20123, 22, 5, 'Total Induction Student List - total_induction_students'),
(20124, 22, 5, 'Online Registration Report - get_data_from_std'),
(20125, 22, 5, 'Payment Check Report - check_all_data_for'),
(20126, 22, 5, 'Induction DB Student List - induction_fl_report_db'),
(20127, 22, 6, 'Add Users - addUser'),
(20128, 22, 6, 'User Permission - userPermission'),
(20129, 22, 6, 'All Notifications - all_notifications'),
(20130, 22, 6, 'Student Check Payments - studentCheckPayment'),
(20131, 22, 7, 'card_enable - index'),
(20132, 22, 7, 'Inactive Students - inactive_students'),
(20133, 22, 8, 'payment cancellation - PaymentCancellation'),
(20134, 22, 8, 'penalty payment cancellation - PenaltyPaymentCancellation'),
(20135, 22, 8, 'Additional payment cancellation - AdditionalPaymentCancellation'),
(20136, 22, 9, 'exams_asses_send_button'),
(20137, 22, 9, 'exams_asses_edit_button'),
(20138, 22, 9, 'exams_asses_document_send_button'),
(20139, 22, 9, 'exams_asses_document_edit_button'),
(20140, 22, 9, 'result_edit_btn'),
(20141, 22, 10, 'Feedback Link Generate - feedback_link_generate'),
(20142, 22, 10, 'Feedback Report - feedback_report'),
(20143, 22, 10, 'Final Year Report - final_year_report'),
(20144, 22, 11, 'Induction Scan - induction_scan'),
(20145, 22, 11, 'Induction Upload Student - upload_induction_students'),
(20146, 22, 11, ''),
(20147, 22, 11, 'Induction Email Send - induction_from_db_email_send'),
(20148, 22, 11, 'Induction Scan From DB - induction_scan_db'),
(20149, 22, 11, 'Induction DB Student List - induction_fl_report_db'),
(20150, 22, 11, 'Induction Email Report - induction_email_report'),
(20151, 22, 11, 'Induction Email Template - induction_email_body_page'),
(20152, 22, 13, 'Add Leads - addLeads'),
(20153, 22, 13, 'Student Registration - studentRegister'),
(20154, 22, 13, 'Online Registration Data - online_registration_data'),
(20155, 22, 13, 'Upload Students - uploadStudents'),
(20156, 22, 13, 'Allocate Program - allocateProgram'),
(20157, 22, 13, 'BMS Email Allocation - bms_email_allocation'),
(20158, 22, 13, 'StudentID Allocation - studentID_allocation'),
(20159, 22, 13, 'Update Student Status - UpdateStudentStatus'),
(20160, 22, 13, 'Student Batch Transfer - batch_transfer'),
(20161, 22, 13, 'Student Batch Swap - batchSwap'),
(20162, 22, 13, 'Student Program Progression - programProgression'),
(20163, 22, 13, 'Update Students E Module - UpdateElectiveModule'),
(20164, 22, 13, 'Upload Student Documents - uploadScanCopies'),
(20165, 22, 13, 'Send Offer Letter - offer_letter'),
(20166, 22, 14, 'Add Payment Plan - add_payment_plan'),
(20167, 22, 14, 'Batch Wise Payment Plan - batch_wise_payment_plan'),
(20168, 22, 14, 'Payment - payment'),
(20169, 22, 14, 'Penalty Payment - penalty_pay'),
(20170, 22, 14, 'Additional Payment - AdditionalFee'),
(20171, 22, 16, 'POS Store / Checkout - pos_store'),
(20172, 22, 16, 'POS Dashboard - pos_dashboard'),
(20173, 22, 16, 'POS Manage Products - pos_products'),
(20174, 22, 16, 'POS Reports - pos_reports'),
(20175, 22, 16, 'POS Detailed Reports - pos_detailed_report'),
(20176, 22, 16, 'POS Detailed Sales - pos_detailed_sales'),
(20231, 14, 1, 'Lead Types - create_lead'),
(20232, 14, 1, 'Year - create_year'),
(20233, 14, 1, 'Semester - create_semester'),
(20234, 14, 1, 'Criteria - create_criteria'),
(20235, 14, 1, 'University - create_universities'),
(20236, 14, 1, 'Coordinator - create_coordinators'),
(20237, 14, 1, 'Program - create_program'),
(20238, 14, 1, 'Assignment Components - create_assignment_components'),
(20239, 14, 1, 'Lecture - create_lecturer'),
(20240, 14, 1, 'Module - create_module'),
(20241, 14, 1, 'Batch - create_batch'),
(20242, 14, 1, 'Grade - create_grade'),
(20243, 14, 1, 'Currency - create_currency'),
(20244, 14, 1, 'Status - create_status'),
(20245, 14, 1, 'Decision - create_decision'),
(20246, 14, 2, 'Assesment Management - exams'),
(20247, 14, 2, 'Assesment Document Send - assesmentDocumentSend'),
(20248, 14, 2, 'Exam / Assignment Result - exam_result'),
(20249, 14, 2, 'E/A Result Send - exam_result_send'),
(20250, 14, 2, 'Module Results Mail - module_result_mail'),
(20251, 14, 2, 'Time Table - AddTimeTable'),
(20252, 14, 2, 'Daily Time Table Message -'),
(20253, 14, 2, 'Special Class Messages - SpecialClassMessages'),
(20254, 14, 2, 'Special Reason - specialReason'),
(20255, 14, 2, 'Add Decision -'),
(20256, 14, 2, 'Alumni -'),
(20257, 14, 2, 'List Board -'),
(20258, 14, 3, 'Edit Programme Allocation - editAllocateProgram'),
(20259, 14, 3, 'Edit Payment - editPayment'),
(20260, 14, 4, ''),
(20261, 14, 5, 'All Student Details - allStudentDetails'),
(20262, 14, 5, 'Student Wise Details - studentWiseDetails'),
(20263, 14, 5, 'Leads Report - leadsReport'),
(20264, 14, 5, 'Outstanding Payment - outstandingPayment'),
(20265, 14, 5, 'Special Reason Report - specialReasonReport'),
(20266, 14, 5, 'Exam / Assignment Report - exam_report'),
(20267, 14, 5, 'Result Mailing Report - result_mailing_report'),
(20268, 14, 5, 'Payment Report - paymentReport'),
(20269, 14, 5, 'Penalty Payment Report - penaltyPaymentReport'),
(20270, 14, 5, 'Additional Payment Report - additionalPaymentReport'),
(20271, 14, 5, 'Time Table Report - timeTableReport'),
(20272, 14, 5, 'Alumni Report - aluminiReport'),
(20273, 14, 5, 'BBM result report - BBM_result_report'),
(20274, 14, 6, 'Add Users - addUser'),
(20275, 14, 6, 'User Permission - userPermission'),
(20276, 14, 7, 'card_enable - index'),
(20277, 14, 8, 'payment cancellation - PaymentCancellation'),
(20278, 14, 8, 'penalty payment cancellation - PenaltyPaymentCancellation'),
(20279, 14, 8, 'Additional payment cancellation - AdditionalPaymentCancellation'),
(20280, 14, 9, 'exams_asses_send_button'),
(20281, 14, 9, 'exams_asses_edit_button'),
(20282, 14, 9, 'exams_asses_document_send_button'),
(20283, 14, 9, 'exams_asses_document_edit_button');

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
(42, 'N/A', NULL, '2024-08-17 07:18:28'),
(65, '4th year', 'mirshath', '2025-08-21 11:31:18');

--
-- Triggers `year_table`
--
DELIMITER $$
CREATE TRIGGER `trg_year_table_delete` AFTER DELETE ON `year_table` FOR EACH ROW BEGIN
    INSERT INTO notifications (table_name, row_id, action, message)
    VALUES (
        'year_table',
        OLD.id,
        'delete',
        CONCAT('Year deleted: ', OLD.year_name, ' by ', IFNULL(OLD.entered_by, 'Unknown'))
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_year_table_insert` AFTER INSERT ON `year_table` FOR EACH ROW BEGIN
    INSERT INTO notifications (table_name, row_id, action, message)
    VALUES (
        'year_table',
        NEW.id,
        'insert',
        CONCAT('New year added: ', NEW.year_name, ' by ', IFNULL(NEW.entered_by, 'Unknown'))
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_year_table_update` AFTER UPDATE ON `year_table` FOR EACH ROW BEGIN
    INSERT INTO notifications (table_name, row_id, action, message)
    VALUES (
        'year_table',
        NEW.id,
        'update',
        CONCAT('Year updated to: ', NEW.year_name, ' by ', IFNULL(NEW.entered_by, 'Unknown'))
    );
END
$$
DELIMITER ;

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
  ADD KEY `university_id` (`university_id`),
  ADD KEY `programme_code` (`programme_code`),
  ADD KEY `allocate_programme_ibfk_1` (`student_code`),
  ADD KEY `allocate_programme_ibfk_4` (`batch_id`);

--
-- Indexes for table `assesment_document_send_email_log`
--
ALTER TABLE `assesment_document_send_email_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_assessment_id` (`assessment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessment_email_log`
--
ALTER TABLE `assessment_email_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `assignment_components`
--
ALTER TABLE `assignment_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`student_id`,`module_id`,`attendance_date`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `batch_swap_table`
--
ALTER TABLE `batch_swap_table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_swap_ibfk_1` (`university_id`),
  ADD KEY `batch_swap_ibfk_2` (`programme_code`),
  ADD KEY `batch_swap_ibfk_3` (`batch_id`),
  ADD KEY `batch_swap_ibfk_4` (`student_id`);

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
-- Indexes for table `class_allocation`
--
ALTER TABLE `class_allocation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_allocation` (`programme_code`,`batch_id`,`module_id`),
  ADD KEY `fk_batch_id` (`batch_id`),
  ADD KEY `fk_module_id` (`module_id`);

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
-- Indexes for table `email_sending_log`
--
ALTER TABLE `email_sending_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_module` (`student_id`,`module_id`);

--
-- Indexes for table `failed_emails`
--
ALTER TABLE `failed_emails`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback_form_fields`
--
ALTER TABLE `feedback_form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_link_id` (`link_id`);

--
-- Indexes for table `feedback_links`
--
ALTER TABLE `feedback_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `link_id` (`link_id`);

--
-- Indexes for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_link_id` (`link_id`),
  ADD KEY `idx_lecturer_id` (`lecturer_id`);

--
-- Indexes for table `feedback_submission_answers`
--
ALTER TABLE `feedback_submission_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_submission_id` (`submission_id`),
  ADD KEY `idx_field_id` (`field_id`);

--
-- Indexes for table `final_student_results`
--
ALTER TABLE `final_student_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_result` (`student_id`,`program_id`,`batch_id`,`module_id`);

--
-- Indexes for table `final_year_criteria`
--
ALTER TABLE `final_year_criteria`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `final_yeat_instalment_data`
--
ALTER TABLE `final_yeat_instalment_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_program_batch_installment` (`program`,`batch`,`installment_no`);

--
-- Indexes for table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_forms_program_code` (`program_code`);

--
-- Indexes for table `form_questions`
--
ALTER TABLE `form_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

--
-- Indexes for table `form_responses`
--
ALTER TABLE `form_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `form_id` (`form_id`);

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
-- Indexes for table `induction_active_table`
--
ALTER TABLE `induction_active_table`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_program_batch` (`program_id`,`batch_id`);

--
-- Indexes for table `induction_db_email_send_log_table`
--
ALTER TABLE `induction_db_email_send_log_table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_allocate_programme_id` (`allocate_programme_id`),
  ADD KEY `idx_student_code` (`student_code`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sent_at` (`sent_at`);

--
-- Indexes for table `induction_emails_sent`
--
ALTER TABLE `induction_emails_sent`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_allocate_programme_id` (`allocate_programme_id`),
  ADD KEY `idx_student_code` (`student_code`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_batch_id` (`batch_id`);

--
-- Indexes for table `induction_email_body_db_table`
--
ALTER TABLE `induction_email_body_db_table`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_program_batch` (`program_id`,`batch_id`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_batch_id` (`batch_id`);

--
-- Indexes for table `induction_students`
--
ALTER TABLE `induction_students`
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
  ADD UNIQUE KEY `payment_plans_tb_id` (`payment_plans_tb_id`),
  ADD KEY `idx_discounted_percentage` (`discounted_percentage`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
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
-- Indexes for table `module_result_email_log`
--
ALTER TABLE `module_result_email_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `programme_id` (`programme_id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `email` (`email`);

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
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payment_batch_allocation`
--
ALTER TABLE `payment_batch_allocation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_cancellation_log`
--
ALTER TABLE `payment_cancellation_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_due_method`
--
ALTER TABLE `payment_due_method`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_due_tables`
--
ALTER TABLE `payment_due_tables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_code` (`student_code`),
  ADD KEY `idx_student_registration_id` (`student_registration_id`);

--
-- Indexes for table `payment_plan_history`
--
ALTER TABLE `payment_plan_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_plan_regfee_discount`
--
ALTER TABLE `payment_plan_regfee_discount`
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
-- Indexes for table `payment_withheld_table`
--
ALTER TABLE `payment_withheld_table`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_program` (`student_code`,`program_id`,`batch_id`),
  ADD KEY `idx_student_code` (`student_code`),
  ADD KEY `idx_student_registration_id` (`student_registration_id`),
  ADD KEY `idx_program_id` (`program_id`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_payment_status` (`payment_status`);

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
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `program_allocation_user`
--
ALTER TABLE `program_allocation_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `program_progression_table`
--
ALTER TABLE `program_progression_table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_swap_ibfk_1` (`university_id`),
  ADD KEY `batch_swap_ibfk_2` (`programme_code`),
  ADD KEY `batch_swap_ibfk_3` (`batch_id`),
  ADD KEY `batch_swap_ibfk_4` (`student_id`);

--
-- Indexes for table `program_table`
--
ALTER TABLE `program_table`
  ADD PRIMARY KEY (`program_code`),
  ADD KEY `university_id` (`university_id`);

--
-- Indexes for table `question_options`
--
ALTER TABLE `question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `registration_links`
--
ALTER TABLE `registration_links`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `response_answers`
--
ALTER TABLE `response_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `response_id` (`response_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `save_assessment_document_send`
--
ALTER TABLE `save_assessment_document_send`
  ADD PRIMARY KEY (`id`);

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
  ADD PRIMARY KEY (`student_code`),
  ADD UNIQUE KEY `nic` (`nic`);

--
-- Indexes for table `students_temporary_document`
--
ALTER TABLE `students_temporary_document`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students_temporary_registration`
--
ALTER TABLE `students_temporary_registration`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nic` (`nic`);

--
-- Indexes for table `student_academic_qualifications`
--
ALTER TABLE `student_academic_qualifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `student_al_results`
--
ALTER TABLE `student_al_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_code` (`student_code`);

--
-- Indexes for table `student_ol_results`
--
ALTER TABLE `student_ol_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `student_other_qualifications`
--
ALTER TABLE `student_other_qualifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`);

--
-- Indexes for table `student_results`
--
ALTER TABLE `student_results`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_transfer`
--
ALTER TABLE `student_transfer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sub_assign_components`
--
ALTER TABLE `sub_assign_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tessssssssssss`
--
ALTER TABLE `tessssssssssss`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_schedule` (`program_code`,`batch_id`,`day_of_week`,`start_time`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `allocated_components`
--
ALTER TABLE `allocated_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `allocate_programme`
--
ALTER TABLE `allocate_programme`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=556;

--
-- AUTO_INCREMENT for table `assesment_document_send_email_log`
--
ALTER TABLE `assesment_document_send_email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `assessment_email_log`
--
ALTER TABLE `assessment_email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `assignment_components`
--
ALTER TABLE `assignment_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `batch_swap_table`
--
ALTER TABLE `batch_swap_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch_table`
--
ALTER TABLE `batch_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

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
-- AUTO_INCREMENT for table `class_allocation`
--
ALTER TABLE `class_allocation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `coordinator_table`
--
ALTER TABLE `coordinator_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `criterias`
--
ALTER TABLE `criterias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `currency_table`
--
ALTER TABLE `currency_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `decision_table`
--
ALTER TABLE `decision_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `description_grading`
--
ALTER TABLE `description_grading`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `email_sending_log`
--
ALTER TABLE `email_sending_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `failed_emails`
--
ALTER TABLE `failed_emails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_form_fields`
--
ALTER TABLE `feedback_form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `feedback_links`
--
ALTER TABLE `feedback_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `feedback_submission_answers`
--
ALTER TABLE `feedback_submission_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `final_student_results`
--
ALTER TABLE `final_student_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=279;

--
-- AUTO_INCREMENT for table `final_year_criteria`
--
ALTER TABLE `final_year_criteria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `final_yeat_instalment_data`
--
ALTER TABLE `final_yeat_instalment_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `forms`
--
ALTER TABLE `forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `form_questions`
--
ALTER TABLE `form_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `form_responses`
--
ALTER TABLE `form_responses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `gpa_calculate_tbl`
--
ALTER TABLE `gpa_calculate_tbl`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_table`
--
ALTER TABLE `grade_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `induction_active_table`
--
ALTER TABLE `induction_active_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `induction_db_email_send_log_table`
--
ALTER TABLE `induction_db_email_send_log_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `induction_emails_sent`
--
ALTER TABLE `induction_emails_sent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `induction_email_body_db_table`
--
ALTER TABLE `induction_email_body_db_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `induction_students`
--
ALTER TABLE `induction_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `installment_details_table`
--
ALTER TABLE `installment_details_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `installment_payment_table`
--
ALTER TABLE `installment_payment_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lecturer_table`
--
ALTER TABLE `lecturer_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=217;

--
-- AUTO_INCREMENT for table `module_result_email_log`
--
ALTER TABLE `module_result_email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `nav_collections`
--
ALTER TABLE `nav_collections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_batch_allocation`
--
ALTER TABLE `payment_batch_allocation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `payment_cancellation_log`
--
ALTER TABLE `payment_cancellation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_due_method`
--
ALTER TABLE `payment_due_method`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_due_tables`
--
ALTER TABLE `payment_due_tables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_plan_history`
--
ALTER TABLE `payment_plan_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_plan_regfee_discount`
--
ALTER TABLE `payment_plan_regfee_discount`
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
-- AUTO_INCREMENT for table `payment_withheld_table`
--
ALTER TABLE `payment_withheld_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `penalty_payments`
--
ALTER TABLE `penalty_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `program_allocation_user`
--
ALTER TABLE `program_allocation_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `program_progression_table`
--
ALTER TABLE `program_progression_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `program_table`
--
ALTER TABLE `program_table`
  MODIFY `program_code` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `question_options`
--
ALTER TABLE `question_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `registration_links`
--
ALTER TABLE `registration_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `response_answers`
--
ALTER TABLE `response_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `save_assessment_document_send`
--
ALTER TABLE `save_assessment_document_send`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semester_table`
--
ALTER TABLE `semester_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `special_class_messages`
--
ALTER TABLE `special_class_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status_table`
--
ALTER TABLE `status_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_code` int(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=474;

--
-- AUTO_INCREMENT for table `students_temporary_document`
--
ALTER TABLE `students_temporary_document`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students_temporary_registration`
--
ALTER TABLE `students_temporary_registration`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_academic_qualifications`
--
ALTER TABLE `student_academic_qualifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_al_results`
--
ALTER TABLE `student_al_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=349;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `student_ol_results`
--
ALTER TABLE `student_ol_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `student_other_qualifications`
--
ALTER TABLE `student_other_qualifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_results`
--
ALTER TABLE `student_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `student_transfer`
--
ALTER TABLE `student_transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sub_assign_components`
--
ALTER TABLE `sub_assign_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tessssssssssss`
--
ALTER TABLE `tessssssssssss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `total_mod_rslt`
--
ALTER TABLE `total_mod_rslt`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `universities`
--
ALTER TABLE `universities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `user_permission`
--
ALTER TABLE `user_permission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20284;

--
-- AUTO_INCREMENT for table `year_table`
--
ALTER TABLE `year_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

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
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `batch_swap_table`
--
ALTER TABLE `batch_swap_table`
  ADD CONSTRAINT `batch_swap_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `batch_swap_ibfk_2` FOREIGN KEY (`programme_code`) REFERENCES `program_table` (`program_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `batch_swap_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `batch_table` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `batch_swap_ibfk_4` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `batch_table`
--
ALTER TABLE `batch_table`
  ADD CONSTRAINT `batch_table_ibfk_1` FOREIGN KEY (`university`) REFERENCES `universities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_programme_code` FOREIGN KEY (`programme`) REFERENCES `program_table` (`program_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `class_allocation`
--
ALTER TABLE `class_allocation`
  ADD CONSTRAINT `fk_batch_id` FOREIGN KEY (`batch_id`) REFERENCES `batch_table` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_module_id` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_form_fields`
--
ALTER TABLE `feedback_form_fields`
  ADD CONSTRAINT `fk_feedback_form_fields_link` FOREIGN KEY (`link_id`) REFERENCES `feedback_links` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  ADD CONSTRAINT `fk_feedback_submissions_link` FOREIGN KEY (`link_id`) REFERENCES `feedback_links` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback_submission_answers`
--
ALTER TABLE `feedback_submission_answers`
  ADD CONSTRAINT `fk_feedback_submission_answers_field` FOREIGN KEY (`field_id`) REFERENCES `feedback_form_fields` (`id`),
  ADD CONSTRAINT `fk_feedback_submission_answers_submission` FOREIGN KEY (`submission_id`) REFERENCES `feedback_submissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `form_questions`
--
ALTER TABLE `form_questions`
  ADD CONSTRAINT `form_questions_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `form_responses`
--
ALTER TABLE `form_responses`
  ADD CONSTRAINT `form_responses_ibfk_1` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `program_table`
--
ALTER TABLE `program_table`
  ADD CONSTRAINT `program_table_ibfk_1` FOREIGN KEY (`university_id`) REFERENCES `universities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `question_options`
--
ALTER TABLE `question_options`
  ADD CONSTRAINT `question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `form_questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `response_answers`
--
ALTER TABLE `response_answers`
  ADD CONSTRAINT `response_answers_ibfk_1` FOREIGN KEY (`response_id`) REFERENCES `form_responses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `response_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `form_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

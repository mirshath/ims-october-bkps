-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 21, 2025 at 10:47 AM
-- Server version: 10.11.15-MariaDB
-- PHP Version: 8.4.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bms2102ac_student-feedback-form`
--

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
  `program_name` varchar(50) NOT NULL,
  `module_name` varchar(50) NOT NULL,
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
-- Indexes for dumped tables
--

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- If you need to add these columns to an existing table, run this ALTER statement:
--
-- ALTER TABLE `feedback`
--   ADD COLUMN `program_id` varchar(50) DEFAULT NULL AFTER `id`,
--   ADD COLUMN `batch_id` int(11) DEFAULT NULL AFTER `program_id`,
--   ADD COLUMN `module_id` int(11) DEFAULT NULL AFTER `batch_id`,
--   ADD COLUMN `lecturer_id` int(11) DEFAULT NULL AFTER `module_id`;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

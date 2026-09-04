-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2026 at 05:48 AM
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
-- Database: `ims_new_september`
--

-- --------------------------------------------------------

--
-- Table structure for table `program_reregister_table`
--

CREATE TABLE `program_reregister_table` (
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
-- Indexes for dumped tables
--

--
-- Indexes for table `program_reregister_table`
--
ALTER TABLE `program_reregister_table`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_swap_ibfk_1` (`university_id`),
  ADD KEY `batch_swap_ibfk_2` (`programme_code`),
  ADD KEY `batch_swap_ibfk_3` (`batch_id`),
  ADD KEY `batch_swap_ibfk_4` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `program_reregister_table`
--
ALTER TABLE `program_reregister_table`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

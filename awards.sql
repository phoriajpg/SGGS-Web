-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2025 at 02:22 AM
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
-- Database: `sggs`
--

-- --------------------------------------------------------

--
-- Table structure for table `awards`
--

CREATE TABLE `awards` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `recipient_type` varchar(50) NOT NULL,
  `award_date` date NOT NULL,
  `presented_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `awards`
--

INSERT INTO `awards` (`id`, `title`, `description`, `category`, `recipient_name`, `recipient_type`, `award_date`, `presented_by`, `created_at`) VALUES
(1, 'Best Computer Science Student 2024', 'Awarded for outstanding academic performance and research contributions in Computer Science', 'Academic Excellence', 'John Smith', 'student', '2024-01-15', 'Computer Science Department', '2025-10-11 21:25:24'),
(2, 'Inter-University Basketball Championship', 'First place in the national inter-university basketball tournament', 'Sports', 'SGGS Basketball Team', 'team', '2024-02-20', 'University Sports Board', '2025-10-11 21:25:24'),
(3, 'Research Innovation Award', 'Recognizing groundbreaking research in renewable energy technologies', 'Research', 'Dr. Sarah Johnson', 'faculty', '2024-01-10', 'National Science Foundation', '2025-10-11 21:25:24'),
(4, 'Excellence in Teaching', 'Awarded for exceptional teaching methodology and student engagement', 'Teaching Excellence', 'Prof. Michael Brown', 'faculty', '2024-02-05', 'University Administration', '2025-10-11 21:25:24'),
(5, 'tset', 'test', 'Leadership', 'test', 'faculty', '2025-10-15', 'test', '2025-10-12 13:05:19'),
(6, 'test', 'etst', 'Research', 'setet', 'staff', '2025-10-15', 'test', '2025-10-12 14:03:22'),
(7, 'test', 'etst', 'Research', 'setet', 'staff', '2025-10-15', 'test', '2025-10-12 14:03:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `awards`
--
ALTER TABLE `awards`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `awards`
--
ALTER TABLE `awards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

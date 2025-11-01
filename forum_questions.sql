-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2025 at 02:23 AM
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
-- Table structure for table `forum_questions`
--

CREATE TABLE `forum_questions` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `author_email` varchar(150) DEFAULT NULL,
  `views` int(11) DEFAULT 0,
  `replies_count` int(11) DEFAULT 0,
  `is_resolved` tinyint(1) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_questions`
--

INSERT INTO `forum_questions` (`id`, `category_id`, `title`, `content`, `author_name`, `author_email`, `views`, `replies_count`, `is_resolved`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 1, 'What documents are needed for new student registration?', 'I\'m planning to enroll my daughter in SGGS for the next academic year. Could someone please provide a complete list of required documents for registration? Also, are there any specific deadlines I should be aware of?', 'Sarah Chen', NULL, 171, 3, 0, 1, '2025-10-11 08:09:23', '2025-10-12 06:03:35'),
(2, 2, 'Advanced Mathematics program availability', 'Does SGGS offer any advanced mathematics programs for students who excel in this subject? My daughter is currently in Form 2 and shows exceptional aptitude in mathematics.', 'Mr. Tan', NULL, 93, 2, 0, 1, '2025-10-11 08:09:23', '2025-10-25 07:14:46'),
(3, 3, 'Basketball team tryouts schedule', 'When will the basketball team tryouts be held this semester? Are there any specific requirements or preparations needed?', 'Amina Rodriguez', NULL, 235, 5, 0, 1, '2025-10-11 08:09:23', '2025-10-11 09:06:27'),
(4, 4, 'Library operating hours during exams', 'What are the library operating hours during the examination period? Are there any extended hours for study sessions?', 'Wei Ling', NULL, 67, 1, 0, 0, '2025-10-11 08:09:23', '2025-10-11 08:09:23'),
(5, 5, 'School holiday calendar 2024', 'Has the school holiday calendar for 2024 been released? I need to plan our family vacation accordingly.', 'Mrs. Kumar', NULL, 185, 2, 0, 1, '2025-10-11 08:09:23', '2025-10-11 09:25:12'),
(6, 6, 'Parent-teacher meeting scheduling', 'What is the process for scheduling individual parent-teacher meetings? Can this be done online?', 'David Wong', NULL, 94, 3, 0, 0, '2025-10-11 08:09:23', '2025-10-11 09:07:18'),
(9, 1, 'test', 'test', 'setset', 'setse@s', 1, 0, 0, 0, '2025-10-12 04:08:50', '2025-10-12 04:33:49'),
(10, 4, 'tset', 'test', 'test', 'test@3', 4, 1, 0, 0, '2025-10-12 06:04:48', '2025-10-25 01:32:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `forum_questions`
--
ALTER TABLE `forum_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `forum_questions`
--
ALTER TABLE `forum_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `forum_questions`
--
ALTER TABLE `forum_questions`
  ADD CONSTRAINT `forum_questions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `forum_categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

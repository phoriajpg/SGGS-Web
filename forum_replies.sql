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
-- Table structure for table `forum_replies`
--

CREATE TABLE `forum_replies` (
  `id` int(11) NOT NULL,
  `question_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `author_name` varchar(100) NOT NULL,
  `author_role` enum('student','parent','teacher','admin') DEFAULT 'student',
  `is_official_answer` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forum_replies`
--

INSERT INTO `forum_replies` (`id`, `question_id`, `content`, `author_name`, `author_role`, `is_official_answer`, `created_at`) VALUES
(1, 1, 'For new student registration, you\'ll need:\n- Birth certificate (original + copy)\n- Previous school reports (2 years)\n- Identification documents of both parents\n- 4 passport-sized photographs\n- Transfer letter from previous school\n\nRegistration for the next academic year opens on March 1st. Early registration is recommended as places are limited.', 'Ms. Lim', 'admin', 1, '2025-10-11 08:09:23'),
(2, 1, 'Don\'t forget the medical examination form from a registered clinic. We had to make an extra trip last year because we missed this requirement.', 'Parent2023', 'parent', 0, '2025-10-11 08:09:23'),
(3, 2, 'Yes, SGGS offers an Advanced Mathematics Program for selected students. Students are identified through their mathematics performance and teacher recommendations. The program includes advanced topics and participation in mathematics competitions.', 'Mr. Raj', 'teacher', 1, '2025-10-11 08:09:23'),
(4, 3, 'Basketball tryouts will be held on January 15th and 16th from 3:00 PM to 5:00 PM at the school court. Requirements:\n- Bring sports attire\n- Basic basketball skills assessment\n- Teamwork evaluation\n\nAll interested students from Forms 1-5 are welcome!', 'Coach James', 'teacher', 1, '2025-10-11 08:09:23'),
(5, 3, 'Is there any age restriction for the tryouts? My sister is in Form 1 and wants to join.', 'BasketballFan', 'student', 0, '2025-10-11 08:09:23'),
(6, 5, 'The 2024 school holiday calendar has been approved and is available on the school website. Key dates:\n- Term 1 break: March 23-31\n- Mid-year break: May 25 - June 9\n- Term 3 break: August 31 - September 8\n- Year-end break: November 16 - January 1', 'School Admin', 'admin', 1, '2025-10-11 08:09:23'),
(10, 10, 'asd', 'Student Parent', 'parent', 0, '2025-10-12 06:04:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `forum_replies`
--
ALTER TABLE `forum_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `forum_replies`
--
ALTER TABLE `forum_replies`
  ADD CONSTRAINT `forum_replies_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `forum_questions` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

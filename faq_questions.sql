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
-- Table structure for table `faq_questions`
--

CREATE TABLE `faq_questions` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faq_questions`
--

INSERT INTO `faq_questions` (`id`, `category_id`, `question`, `answer`, `is_featured`, `view_count`, `created_at`, `updated_at`) VALUES
(1, 1, 'What are the admission requirements for new students?', 'Admission requirements include completed application form, birth certificate, previous school records, and an entrance assessment. Please visit our admissions office for detailed information.', 1, 76, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(2, 1, 'When does the enrollment period start?', 'The enrollment period typically starts in March for the upcoming academic year. Exact dates are announced on our website and social media platforms.', 1, 74, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(3, 2, 'What curriculum does SGGS follow?', 'SGGS follows the Malaysian National Curriculum (KSSM) with additional emphasis on character development and co-curricular activities.', 1, 74, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(4, 2, 'Are there any special programs for gifted students?', 'Yes, we offer enrichment programs and accelerated learning opportunities for academically gifted students.', 0, 76, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(5, 3, 'What facilities are available for students?', 'Our campus features modern classrooms, science laboratories, computer labs, library, sports facilities, and art studios.', 1, 78, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(6, 3, 'Is there a cafeteria in the school?', 'Yes, we have a clean and well-maintained cafeteria offering healthy meal options for students.', 0, 78, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(7, 4, 'What extracurricular activities are available?', 'We offer various clubs and societies including sports, arts, music, debate, and community service programs.', 1, 75, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(8, 4, 'How can students join sports teams?', 'Students can try out for sports teams during the beginning of each semester. Announcements are made through the PE department.', 0, 77, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(9, 5, 'What are the school operating hours?', 'School hours are from 7:30 AM to 3:30 PM, Monday through Friday. Office hours are 8:00 AM to 5:00 PM.', 1, 76, '2025-10-11 07:53:03', '2025-10-25 01:32:50'),
(10, 5, 'How can parents schedule meetings with teachers?', 'Parents can schedule meetings through the school office or via our online parent portal system.', 0, 76, '2025-10-11 07:53:03', '2025-10-25 01:32:50');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `faq_questions`
--
ALTER TABLE `faq_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `faq_questions`
--
ALTER TABLE `faq_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `faq_questions`
--
ALTER TABLE `faq_questions`
  ADD CONSTRAINT `faq_questions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `faq_categories` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

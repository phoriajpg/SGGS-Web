-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2025 at 09:41 AM
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
-- Database: `sggs_top_spm`
--

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `id` int(11) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `grade` varchar(50) NOT NULL,
  `year` int(11) NOT NULL,
  `is_straight_a` tinyint(1) NOT NULL,
  `a_plus_count` int(11) DEFAULT NULL,
  `a_count` int(11) DEFAULT NULL,
  `a_minus_count` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_grades`
--

INSERT INTO `student_grades` (`id`, `student_name`, `grade`, `year`, `is_straight_a`, `a_plus_count`, `a_count`, `a_minus_count`) VALUES
(1, 'Alyssa Lim Yeen', '8A+ 1A', 2023, 1, NULL, NULL, NULL),
(2, 'Foo Wern Chyi', '8A+ 1A', 2023, 1, NULL, NULL, NULL),
(3, 'Diviya', '7A+ 2A', 2023, 1, NULL, NULL, NULL),
(4, 'Resshma Anpalakan', '7A+ 2A', 2023, 1, NULL, NULL, NULL),
(5, 'Nur Farhanah Binti Md Farid', '7A+ 2A', 2023, 1, NULL, NULL, NULL),
(6, 'Lakshna Prabavathy A/P Sri Ram', '6A+ 2A 1A-', 2023, 1, NULL, NULL, NULL),
(7, 'Celeste Heng Wei Jie', '5A+ 3A 1A-', 2023, 1, NULL, NULL, NULL),
(8, 'Heidi Ho Xinli', '5A+ 3A 1A-', 2023, 1, NULL, NULL, NULL),
(9, 'Elena Chan Huey Huey', '5A+ 2A 2A-', 2023, 1, NULL, NULL, NULL),
(10, 'Nur Fazlin Binti Abdul Mazeed', '4A+ 5A', 2023, 1, NULL, NULL, NULL),
(11, 'Celine Teh Wooi Wei', '4A+ 4A 1A-', 2023, 1, NULL, NULL, NULL),
(12, 'Hasyna Ali Binti Irfhan Ali', '4A+ 4A 1A-', 2023, 1, NULL, NULL, NULL),
(13, 'Ariel Lee Hooi Chang', '4A+ 3A 2A-', 2023, 1, NULL, NULL, NULL),
(14, 'Nur Dhiya Damia Binti Ahmad Nadzri', '4A+ 2A 3A-', 2023, 1, NULL, NULL, NULL),
(15, 'Presita A/P Mahendiran', '3A+ 4A 2A-', 2023, 1, NULL, NULL, NULL),
(16, 'Umi Adriena Jasmine Binti Mohamed Omar Khan', '3A+ 3A 3A-', 2023, 1, NULL, NULL, NULL),
(17, 'Isabelle Chee Soo Wei', '3A+ 2A 4A-', 2023, 1, NULL, NULL, NULL),
(18, 'Aisyah Rusydina Binti Mohamed Hassan', '7A 1A-', 2023, 1, NULL, NULL, NULL),
(22, 'Wazirah Asma Binti Mohamed Ali', '9A+', 2024, 1, NULL, NULL, NULL),
(23, 'Sabari Laxmi A/P Harikumar', '7A+ 4A', 2024, 1, NULL, NULL, NULL),
(24, 'Alissa Teh Wei Xuen', '7A+ 2A', 2024, 1, NULL, NULL, NULL),
(25, 'Isabel Lim Jia Sin', '6A+ 3A', 2024, 1, NULL, NULL, NULL),
(26, 'Nur Alyaa Batrisyia Binti Azrul', '6A+ 3A', 2024, 1, NULL, NULL, NULL),
(27, 'Sarekaa Naidu A/P Saravanan', '6A+ 3A', 2024, 1, NULL, NULL, NULL),
(28, 'Shelina Deva Moses', '6A+ 3A', 2024, 1, NULL, NULL, NULL),
(29, 'Alivia Teh Wei Yi', '6A+ 1A 1A-', 2024, 1, NULL, NULL, NULL),
(30, 'Foo Xue Hui', '5A+ 4A', 2024, 1, NULL, NULL, NULL),
(31, 'Dhurghashini A/P Ramesh', '5A+ 4A', 2024, 1, NULL, NULL, NULL),
(32, 'Nur Athiqah Binti Ahmad Marzuki', '5A+ 3A', 2024, 1, NULL, NULL, NULL),
(33, 'NurJasmine Khadeeja Binti Mohd Fauzi', '4A+ 5A 1A-', 2024, 1, NULL, NULL, NULL),
(34, 'Sheryn Reana A/P Kumarasamy', '4A+ 4A 1A-', 2024, 1, NULL, NULL, NULL),
(35, 'Nur Fatini Binti Yasir', '4A+ 4A', 2024, 1, NULL, NULL, NULL),
(36, 'Shireen Zia Binti Anwar', '4A+ 3A 2A-', 2024, 1, NULL, NULL, NULL),
(37, 'Nur Syaqirah Ezany Binti Johardi', '4A+ 3A 2A-', 2024, 1, NULL, NULL, NULL),
(38, 'Nor Alissa Binti Anuar', '4A+ 3A 2A-', 2024, 1, NULL, NULL, NULL),
(39, 'Nor Fashaa Dayana Binti Nor Hazaimi', '3A+ 6A', 2024, 1, NULL, NULL, NULL),
(40, 'Lau Weng Hei', '3A+ 5A 1A-', 2024, 1, NULL, NULL, NULL),
(41, 'Teh Hui En', '3A+ 5A 1A-', 2024, 1, NULL, NULL, NULL),
(42, 'Amira Syahirah Binti Zul Azmi', '3A+ 5A', 2024, 1, NULL, NULL, NULL),
(43, 'Jessica Ann Christopher', '3A+ 5A', 2024, 1, NULL, NULL, NULL),
(44, 'Mishalini A/P Sumati Pala', '3A+ 4A 1A-', 2024, 1, NULL, NULL, NULL),
(45, 'Kamilia Binti Mohamad Noor Affendy', '3A+ 4A 1A-', 2024, 1, NULL, NULL, NULL),
(46, 'Sashmitaa A/P Thiraviyam', '2A+ 5A 3A-', 2024, 1, NULL, NULL, NULL),
(47, 'Fatin Najiha Binti Abu Osman', '2A+ 5A 2A-', 2024, 1, NULL, NULL, NULL),
(48, 'Piriyaasagi A/P Ganesan', '2A+ 5A 2A-', 2024, 1, NULL, NULL, NULL),
(49, 'Nik Nur Syakirah Binti Nik Khuzaini', '2A+ 4A 2A-', 2024, 1, NULL, NULL, NULL),
(50, 'Azrina Balqis Binti Abdul Wazit', '1A+ 7A 1A-', 2024, 1, NULL, NULL, NULL),
(51, 'Adnin Adriana Binti Ahmad Faizal', '1A+ 3A 4A-', 2024, 1, NULL, NULL, NULL),
(52, 'Rashimiitha Surin', '1A+ 3A 3A-', 2024, 1, NULL, NULL, NULL),
(53, 'Qurratu\'Ain Amani Binti Mokhzani', '6A 2A-', 2024, 1, NULL, NULL, NULL),
(54, 'Navishaa A/P Shankar', '6A 1A-', 2024, 1, NULL, NULL, NULL),
(55, 'Norsyazana Batrisya Binti Suhaidi', '5A 4A-', 2024, 1, NULL, NULL, NULL),
(56, 'Nurul Iman Binti Abdul Khaliq', '4A 4A-', 2024, 1, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

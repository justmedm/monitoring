-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Mar 19, 2025 at 03:46 PM
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
-- Database: `login_register`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `date` date NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `subject` varchar(100) NOT NULL,
  `status` enum('pending','approved','disapproved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sit_in_records`
--

CREATE TABLE `sit_in_records` (
  `id` int(11) NOT NULL,
  `student_id` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `course` varchar(255) NOT NULL,
  `time_in` datetime NOT NULL,
  `time_out` datetime DEFAULT NULL,
  `purpose` varchar(255) NOT NULL,
  `sitlab` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_assignments`
--

CREATE TABLE `student_assignments` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `idno` varchar(20) NOT NULL,
  `lastname` varchar(50) NOT NULL,
  `firstname` varchar(50) NOT NULL,
  `midname` varchar(50) NOT NULL,
  `course` varchar(100) NOT NULL,
  `yearlvl` int(11) NOT NULL,
  `emailadd` varchar(50) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(250) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(50) DEFAULT NULL,
  `remaining_sessions` int(11) DEFAULT 30,
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idno`, `lastname`, `firstname`, `midname`, `course`, `yearlvl`, `emailadd`, `username`, `password`, `created_at`, `role`, `remaining_sessions`, `profile_pic`) VALUES
(10, '22653414', 'Muñasque', 'Stanlee', 'Riveral', 'BSIT', 3, 'munasquestanlee@gmail.com', '22653414', '$2y$10$ZW26cliLfepLZSHs4y2h3OQoxzNr9gRVVVhoQq2QiTzEIYlx9caQ.', '2025-02-26 07:52:51', NULL, 30, NULL),
(11, '12345', 'Garcia', 'Vaugn', 'Xhander', 'BSIT', 2, 'vaughn@gmail.com', '12345', '$2y$10$fw2HqfR2dtj.jVsllio8a.QmG/y0N4ODaDpvKKMZwyOCqx7AQD1su', '2025-02-26 08:51:57', NULL, 30, NULL),
(12, '123', 'Rotaqiuo', 'Kester', 'Jude', 'ACT', 1, 'kester@gmail.com', '123', '$2y$10$ZzLBXmjEixgeXYEJJcY27OjkZOIYvswIBAMwnSfqBkeiH5gAQ65Ji', '2025-02-26 10:51:36', NULL, 30, NULL),
(16, '226534', 'Muñasque', 'Stanlee', 'Riveral', 'BSIT', 3, 'munasstanlee@gmail.com', '226534', '$2y$10$zT6N3QjciCKdk4fS.IkXneCbR4cJ9S88/G95OWnBRw7owFFFAmIVq', '2025-02-26 10:55:04', NULL, 30, NULL),
(17, '22653412', 'Muñasque', 'Stanlee', 'Riveral', 'BSIT', 3, 'munasquestan@gmail.com', '22653412', '$2y$10$pEnPj7umm/nD5T4cJPdYDOygAYI4jRgftQxgoO.coZem8Sd7X/rNC', '2025-02-26 11:07:01', NULL, 30, NULL),
(18, 'admin', 'Admin', 'System', '', 'N/A', 0, 'admin@example.com', 'admin', '$2y$10$xjnRVmNRi6zz0yRm92Z3n.gW8YU9ohh2XrMXHz6ocjP0U.Ir.8q/C', '2025-03-13 15:02:44', 'admin', 30, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idno` (`idno`,`username`),
  ADD UNIQUE KEY `emailadd` (`emailadd`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sit_in_records`
--
ALTER TABLE `sit_in_records`
  ADD CONSTRAINT `fk_student_id` FOREIGN KEY (`student_id`) REFERENCES `users` (`idno`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

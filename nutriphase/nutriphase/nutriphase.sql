-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2026 at 07:23 AM
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
-- Database: `nutriphase`
--

-- --------------------------------------------------------

--
-- Table structure for table `body_metrics`
--

CREATE TABLE `body_metrics` (
  `metric_id` int(11) NOT NULL,
  `P_SSN` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `height` float DEFAULT NULL,
  `weight` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `developer`
--

CREATE TABLE `developer` (
  `developer_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disease`
--

CREATE TABLE `disease` (
  `disease_id` int(11) NOT NULL,
  `disease_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `id` int(11) NOT NULL,
  `patient1_SSN` int(11) NOT NULL,
  `patient2_SSN` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `friends`
--

INSERT INTO `friends` (`id`, `patient1_SSN`, `patient2_SSN`, `created_at`) VALUES
(1, 121, 24101494, '2026-04-28 13:58:03');

-- --------------------------------------------------------

--
-- Table structure for table `friend_request`
--

CREATE TABLE `friend_request` (
  `request_id` int(11) NOT NULL,
  `sender_P_SSN` int(11) DEFAULT NULL,
  `receiver_P_SSN` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `group_member`
--

CREATE TABLE `group_member` (
  `group_id` int(11) NOT NULL,
  `P_SSN` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_member`
--

INSERT INTO `group_member` (`group_id`, `P_SSN`) VALUES
(1, 121),
(1, 24101494);

-- --------------------------------------------------------

--
-- Table structure for table `habit`
--

CREATE TABLE `habit` (
  `record_id` int(11) NOT NULL,
  `P_SSN` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `calories` int(11) DEFAULT NULL,
  `sleep_hours` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `habit`
--

INSERT INTO `habit` (`record_id`, `P_SSN`, `date`, `calories`, `sleep_hours`) VALUES
(0, 24101494, '2026-04-28', 3600, 7);

-- --------------------------------------------------------

--
-- Table structure for table `health_prediction`
--

CREATE TABLE `health_prediction` (
  `prediction_id` int(11) NOT NULL,
  `P_SSN` int(11) DEFAULT NULL,
  `predicted_issue` varchar(100) DEFAULT NULL,
  `risk_level` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nutritionist`
--

CREATE TABLE `nutritionist` (
  `N_SSN` int(11) NOT NULL,
  `bio` varchar(255) DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL,
  `qualification` varchar(100) DEFAULT NULL,
  `subscription_fee` int(11) DEFAULT 500
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nutritionist`
--

INSERT INTO `nutritionist` (`N_SSN`, `bio`, `experience_years`, `qualification`, `subscription_fee`) VALUES
(2147483647, 'Im an experienced nutrionist', 7, 'Msc Nutrition', 500);

-- --------------------------------------------------------

--
-- Table structure for table `patient`
--

CREATE TABLE `patient` (
  `P_SSN` int(11) NOT NULL,
  `goal` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patient`
--

INSERT INTO `patient` (`P_SSN`, `goal`) VALUES
(121, 'fit'),
(24101494, 'lose weight');

-- --------------------------------------------------------

--
-- Table structure for table `patient_disease`
--

CREATE TABLE `patient_disease` (
  `P_SSN` int(11) NOT NULL,
  `disease_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `transaction_id` int(11) NOT NULL,
  `amount` int(11) DEFAULT NULL,
  `developer_id` int(11) DEFAULT NULL,
  `N_SSN` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscribe`
--

CREATE TABLE `subscribe` (
  `P_SSN` int(11) NOT NULL,
  `N_SSN` int(11) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscribe`
--

INSERT INTO `subscribe` (`P_SSN`, `N_SSN`, `start_date`, `end_date`) VALUES
(24101494, 2147483647, '2026-04-28', '2026-05-28');

-- --------------------------------------------------------

--
-- Table structure for table `suggestion`
--

CREATE TABLE `suggestion` (
  `suggestion_id` int(11) NOT NULL,
  `predicted_issue` varchar(100) DEFAULT NULL,
  `suggestion_text` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `track`
--

CREATE TABLE `track` (
  `track_id` int(11) NOT NULL,
  `P_SSN` int(11) DEFAULT NULL,
  `N_SSN` int(11) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `SSN` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`SSN`, `name`, `email`, `password`, `gender`) VALUES
(121, 'zafrin zaman', 'zafrin@gmail.com', '$2y$10$Ij2v9583xODO.I8A2oZtPeqx8RuhyKYwglNeqSkXjus8OJ3JTHsWi', 'Female'),
(24101494, 'Sadman Hasan', 'sadman.hasan@g.bracu.ac.bd', '$2y$10$5YimaDL832QoKexG.EixDOGFJ5gHhfb3TkbgIblOiWrOy1rP8tQ7W', 'Male'),
(2147483647, 'Sidratul Muntaha', 'smp001@gmail.com', '$2y$10$3FyHC8Q2HhO2kxHlE0JEZuMAbjGIwxglM2TabMjJkPz9NlsNNwzM6', 'Female');

-- --------------------------------------------------------

--
-- Table structure for table `user_group`
--

CREATE TABLE `user_group` (
  `group_id` int(11) NOT NULL,
  `group_name` varchar(100) DEFAULT NULL,
  `access` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_group`
--

INSERT INTO `user_group` (`group_id`, `group_name`, `access`) VALUES
(1, 'FIT CRACKERS', 'public');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `body_metrics`
--
ALTER TABLE `body_metrics`
  ADD PRIMARY KEY (`metric_id`),
  ADD KEY `P_SSN` (`P_SSN`);

--
-- Indexes for table `developer`
--
ALTER TABLE `developer`
  ADD PRIMARY KEY (`developer_id`);

--
-- Indexes for table `disease`
--
ALTER TABLE `disease`
  ADD PRIMARY KEY (`disease_id`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `friend_request`
--
ALTER TABLE `friend_request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `sender_P_SSN` (`sender_P_SSN`),
  ADD KEY `receiver_P_SSN` (`receiver_P_SSN`);

--
-- Indexes for table `group_member`
--
ALTER TABLE `group_member`
  ADD PRIMARY KEY (`group_id`,`P_SSN`),
  ADD KEY `P_SSN` (`P_SSN`);

--
-- Indexes for table `habit`
--
ALTER TABLE `habit`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `P_SSN` (`P_SSN`);

--
-- Indexes for table `health_prediction`
--
ALTER TABLE `health_prediction`
  ADD PRIMARY KEY (`prediction_id`),
  ADD KEY `P_SSN` (`P_SSN`);

--
-- Indexes for table `nutritionist`
--
ALTER TABLE `nutritionist`
  ADD PRIMARY KEY (`N_SSN`);

--
-- Indexes for table `patient`
--
ALTER TABLE `patient`
  ADD PRIMARY KEY (`P_SSN`);

--
-- Indexes for table `patient_disease`
--
ALTER TABLE `patient_disease`
  ADD PRIMARY KEY (`P_SSN`,`disease_id`),
  ADD KEY `disease_id` (`disease_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `developer_id` (`developer_id`),
  ADD KEY `N_SSN` (`N_SSN`);

--
-- Indexes for table `subscribe`
--
ALTER TABLE `subscribe`
  ADD PRIMARY KEY (`P_SSN`,`N_SSN`),
  ADD KEY `N_SSN` (`N_SSN`);

--
-- Indexes for table `suggestion`
--
ALTER TABLE `suggestion`
  ADD PRIMARY KEY (`suggestion_id`);

--
-- Indexes for table `track`
--
ALTER TABLE `track`
  ADD PRIMARY KEY (`track_id`),
  ADD KEY `P_SSN` (`P_SSN`),
  ADD KEY `N_SSN` (`N_SSN`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`SSN`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_group`
--
ALTER TABLE `user_group`
  ADD PRIMARY KEY (`group_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `body_metrics`
--
ALTER TABLE `body_metrics`
  ADD CONSTRAINT `body_metrics_ibfk_1` FOREIGN KEY (`P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE;

--
-- Constraints for table `friend_request`
--
ALTER TABLE `friend_request`
  ADD CONSTRAINT `friend_request_ibfk_1` FOREIGN KEY (`sender_P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE,
  ADD CONSTRAINT `friend_request_ibfk_2` FOREIGN KEY (`receiver_P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE;

--
-- Constraints for table `group_member`
--
ALTER TABLE `group_member`
  ADD CONSTRAINT `group_member_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `user_group` (`group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_member_ibfk_2` FOREIGN KEY (`P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE;

--
-- Constraints for table `habit`
--
ALTER TABLE `habit`
  ADD CONSTRAINT `habit_ibfk_1` FOREIGN KEY (`P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE;

--
-- Constraints for table `health_prediction`
--
ALTER TABLE `health_prediction`
  ADD CONSTRAINT `health_prediction_ibfk_1` FOREIGN KEY (`P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE;

--
-- Constraints for table `nutritionist`
--
ALTER TABLE `nutritionist`
  ADD CONSTRAINT `nutritionist_ibfk_1` FOREIGN KEY (`N_SSN`) REFERENCES `user` (`SSN`) ON DELETE CASCADE;

--
-- Constraints for table `patient`
--
ALTER TABLE `patient`
  ADD CONSTRAINT `patient_ibfk_1` FOREIGN KEY (`P_SSN`) REFERENCES `user` (`SSN`) ON DELETE CASCADE;

--
-- Constraints for table `patient_disease`
--
ALTER TABLE `patient_disease`
  ADD CONSTRAINT `patient_disease_ibfk_1` FOREIGN KEY (`P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE,
  ADD CONSTRAINT `patient_disease_ibfk_2` FOREIGN KEY (`disease_id`) REFERENCES `disease` (`disease_id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`developer_id`) REFERENCES `developer` (`developer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`N_SSN`) REFERENCES `nutritionist` (`N_SSN`) ON DELETE CASCADE;

--
-- Constraints for table `subscribe`
--
ALTER TABLE `subscribe`
  ADD CONSTRAINT `subscribe_ibfk_1` FOREIGN KEY (`P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscribe_ibfk_2` FOREIGN KEY (`N_SSN`) REFERENCES `nutritionist` (`N_SSN`) ON DELETE CASCADE;

--
-- Constraints for table `track`
--
ALTER TABLE `track`
  ADD CONSTRAINT `track_ibfk_1` FOREIGN KEY (`P_SSN`) REFERENCES `patient` (`P_SSN`) ON DELETE CASCADE,
  ADD CONSTRAINT `track_ibfk_2` FOREIGN KEY (`N_SSN`) REFERENCES `nutritionist` (`N_SSN`) ON DELETE CASCADE,
  ADD CONSTRAINT `track_ibfk_3` FOREIGN KEY (`record_id`) REFERENCES `habit` (`record_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

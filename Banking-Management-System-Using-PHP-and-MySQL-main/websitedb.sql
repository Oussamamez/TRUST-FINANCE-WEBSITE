-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 08:33 PM
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
-- Database: `transactionssystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `bank_info`
--

CREATE TABLE `bank_info` (
  `bank_name` varchar(100) NOT NULL,
  `bank_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_info`
--

INSERT INTO `bank_info` (`bank_name`, `bank_address`) VALUES
('SMART TRUST BANK', 'GUELMA-Centre ville');

-- --------------------------------------------------------

--
-- Table structure for table `loan`
--

CREATE TABLE `loan` (
  `loan_id` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `loan_status` varchar(50) DEFAULT NULL,
  `loan_date` date DEFAULT NULL,
  `loan_tax` decimal(10,2) DEFAULT NULL,
  `RIP` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mono_acc_transaction`
--

CREATE TABLE `mono_acc_transaction` (
  `transaction_id` varchar(50) NOT NULL,
  `type_of_transaction` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL,
  `Validated` tinyint(1) NOT NULL DEFAULT 0,
  `RIP` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `normaluser`
--

CREATE TABLE `normaluser` (
  `national_identifier_number` varchar(50) NOT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `uPassword` varchar(255) DEFAULT NULL,
  `frozen` tinyint(1) NOT NULL DEFAULT 0,
  `superuser_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `super_user`
--

CREATE TABLE `super_user` (
  `superuser_id` varchar(50) NOT NULL,
  `family_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `type_of_user` varchar(50) DEFAULT NULL,
  `suPassword` varchar(255) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `super_user`
--

INSERT INTO `super_user` (`superuser_id`, `family_name`, `first_name`, `phone_number`, `date_of_birth`, `type_of_user`, `suPassword`, `email`, `bank_name`) VALUES
('1', 'ADMIN', 'admin', '1', '1999-12-31', 'Admin', 'admin', 'admin', 'SMART TRUST BANK'),
('2', 'CASHIER', 'Cashier', '2', '2000-01-01', 'Cashier', 'cashier', 'cashier', 'SMART TRUST BANK');

-- --------------------------------------------------------

--
-- Table structure for table `transfer`
--

CREATE TABLE `transfer` (
  `transaction_id` varchar(50) NOT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `transfer_reason` varchar(255) DEFAULT NULL,
  `tax` decimal(10,2) DEFAULT NULL,
  `Validated` tinyint(1) NOT NULL DEFAULT 0,
  `from_RIP` varchar(50) DEFAULT NULL,
  `to_RIP` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `useraccount`
--

CREATE TABLE `useraccount` (
  `RIP` varchar(50) NOT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `national_identifier_number` varchar(50) DEFAULT NULL,
  `Status` tinyint(1) NOT NULL DEFAULT 0,
  `superuser_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bank_info`
--
ALTER TABLE `bank_info`
  ADD PRIMARY KEY (`bank_name`);

--
-- Indexes for table `loan`
--
ALTER TABLE `loan`
  ADD PRIMARY KEY (`loan_id`),
  ADD KEY `RIP` (`RIP`);

--
-- Indexes for table `mono_acc_transaction`
--
ALTER TABLE `mono_acc_transaction`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `RIP` (`RIP`);

--
-- Indexes for table `normaluser`
--
ALTER TABLE `normaluser`
  ADD PRIMARY KEY (`national_identifier_number`),
  ADD KEY `normaluser_superuser` (`superuser_id`);

--
-- Indexes for table `super_user`
--
ALTER TABLE `super_user`
  ADD PRIMARY KEY (`superuser_id`),
  ADD KEY `bank_name` (`bank_name`);

--
-- Indexes for table `transfer`
--
ALTER TABLE `transfer`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `from_RIP` (`from_RIP`),
  ADD KEY `to_RIP` (`to_RIP`);

--
-- Indexes for table `useraccount`
--
ALTER TABLE `useraccount`
  ADD PRIMARY KEY (`RIP`),
  ADD KEY `national_identifier_number` (`national_identifier_number`),
  ADD KEY `useraccount_superuser` (`superuser_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `loan`
--
ALTER TABLE `loan`
  ADD CONSTRAINT `loan_ibfk_1` FOREIGN KEY (`RIP`) REFERENCES `useraccount` (`RIP`);

--
-- Constraints for table `mono_acc_transaction`
--
ALTER TABLE `mono_acc_transaction`
  ADD CONSTRAINT `mono_acc_transaction_ibfk_1` FOREIGN KEY (`RIP`) REFERENCES `useraccount` (`RIP`);

--
-- Constraints for table `normaluser`
--
ALTER TABLE `normaluser`
  ADD CONSTRAINT `normaluser_superuser` FOREIGN KEY (`superuser_id`) REFERENCES `super_user` (`superuser_id`);

--
-- Constraints for table `super_user`
--
ALTER TABLE `super_user`
  ADD CONSTRAINT `super_user_ibfk_1` FOREIGN KEY (`bank_name`) REFERENCES `bank_info` (`bank_name`);

--
-- Constraints for table `transfer`
--
ALTER TABLE `transfer`
  ADD CONSTRAINT `transfer_ibfk_1` FOREIGN KEY (`from_RIP`) REFERENCES `useraccount` (`RIP`),
  ADD CONSTRAINT `transfer_ibfk_2` FOREIGN KEY (`to_RIP`) REFERENCES `useraccount` (`RIP`);

--
-- Constraints for table `useraccount`
--
ALTER TABLE `useraccount`
  ADD CONSTRAINT `useraccount_ibfk_1` FOREIGN KEY (`national_identifier_number`) REFERENCES `normaluser` (`national_identifier_number`),
  ADD CONSTRAINT `useraccount_superuser` FOREIGN KEY (`superuser_id`) REFERENCES `super_user` (`superuser_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
-- phpMyAdmin SQL Dump
-- version 4.6.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 30, 2016 at 11:05 PM
-- Server version: 8.0.0-dmr
-- PHP Version: 7.0.8-0ubuntu0.16.04.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `slim`
--

-- --------------------------------------------------------

--
-- Table structure for table `rel_timepixels_users`
--

CREATE TABLE `rel_timepixels_users` (
  `timepixel_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rel_timepixels_users`
--

INSERT INTO `rel_timepixels_users` (`timepixel_id`, `user_id`) VALUES
(1, 1),
(1, 2);

-- --------------------------------------------------------

--
-- Table structure for table `timepixels`
--

CREATE TABLE `timepixels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `json` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timepixels`
--

INSERT INTO `timepixels` (`id`, `json`) VALUES
(1, '{"type": "task", "title": "The Timepixel", "dt_end": "2016-01-21 09:10:00", "version": "0.1", "announce": {"url": "https://www.facebook.com/events/1725843737689335/", "oauth2": "key"}, "dt_start": "2016-01-20 13:11:56", "duration": {"counted": "450", "expected": "3d"}, "timezone": "Europe/Prague"}'),
(2, '{"title": "New event", "dt_end": "2017-02-13 16:00", "dt_start": "2017-02-13 15:00"}');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `info` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `info`, `created_at`, `updated_at`) VALUES
(1, 'Slim Shady', 'shady@slim.com', '', 'null', NULL, NULL),
(2, 'W. A. Mozart', 'wa@mozart.xyz', '$2y$10$U0.NzJymMhI7aWBzkL8dV.PJnR3kMKBmVMNzQjKh6ECC0wlx.pchu', NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rel_timepixels_users`
--
ALTER TABLE `rel_timepixels_users`
  ADD KEY `timepixel_id` (`timepixel_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `timepixels`
--
ALTER TABLE `timepixels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `timepixels`
--
ALTER TABLE `timepixels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
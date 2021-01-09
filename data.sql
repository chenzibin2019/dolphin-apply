-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 09, 2021 at 12:14 PM
-- Server version: 5.6.44-log
-- PHP Version: 7.1.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bkzspy`
--

-- --------------------------------------------------------

--
-- Table structure for table `bks_apply_intervtime`
--

CREATE TABLE `bks_apply_intervtime` (
  `ID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mid` int(11) NOT NULL,
  `maxstudent` int(255) NOT NULL,
  `status` int(255) NOT NULL DEFAULT '1',
  `create_time` varchar(255) NOT NULL,
  `order` int(10) NOT NULL DEFAULT '100'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bks_apply_model`
--

CREATE TABLE `bks_apply_model` (
  `ID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `creater` int(255) NOT NULL,
  `applystarttime` varchar(255) NOT NULL,
  `applyendtime` varchar(255) NOT NULL,
  `intervstarttime` varchar(255) NOT NULL,
  `intervendtime` varchar(255) NOT NULL,
  `adminids` varchar(255) DEFAULT NULL,
  `maxstudent` int(255) NOT NULL DEFAULT '-1',
  `maxintervtime` int(11) NOT NULL DEFAULT '-1',
  `create_time` varchar(255) NOT NULL,
  `update_time` varchar(255) DEFAULT NULL,
  `appid` varchar(255) NOT NULL,
  `appsecret` varchar(255) NOT NULL,
  `template_id` varchar(255) DEFAULT NULL,
  `needfile` int(255) NOT NULL DEFAULT '0',
  `status` int(255) NOT NULL DEFAULT '1',
  `lockquestion` int(255) NOT NULL DEFAULT '0',
  `allowchoosetime` int(11) NOT NULL DEFAULT '1',
  `intervtemplate` varchar(1024) DEFAULT NULL,
  `resulttemplate` varchar(1024) DEFAULT NULL,
  `notemplate` varchar(1024) DEFAULT NULL,
  `notice` longtext,
  `write_ims` int(4) NOT NULL DEFAULT '0',
  `ims_course_id` varchar(64) DEFAULT NULL,
  `ims_student_email` varchar(128) DEFAULT NULL,
  `ims_student_name` varchar(128) DEFAULT NULL,
  `ims_student_id` varchar(128) DEFAULT NULL,
  `ims_min_status` int(11) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `bks_apply_question`
--

CREATE TABLE `bks_apply_question` (
  `ID` int(11) NOT NULL,
  `mid` int(11) NOT NULL,
  `create_time` varchar(255) NOT NULL,
  `status` int(255) NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL,
  `type` int(255) NOT NULL,
  `options` varchar(255) DEFAULT NULL,
  `orders` int(128) NOT NULL DEFAULT '100',
  `required` int(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bks_apply_intervtime`
--
ALTER TABLE `bks_apply_intervtime`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `bks_apply_model`
--
ALTER TABLE `bks_apply_model`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `bks_apply_question`
--
ALTER TABLE `bks_apply_question`
  ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bks_apply_intervtime`
--
ALTER TABLE `bks_apply_intervtime`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `bks_apply_model`
--
ALTER TABLE `bks_apply_model`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `bks_apply_question`
--
ALTER TABLE `bks_apply_question`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

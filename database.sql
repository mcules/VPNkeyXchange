-- phpMyAdmin SQL Dump
-- version 4.2.12deb2+deb8u2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 26, 2017 at 10:32 AM
-- Server version: 5.7.17-11-log
-- PHP Version: 5.6.30-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `keyserver`
--

-- --------------------------------------------------------

--
-- Table structure for table `hoods`
--

CREATE TABLE IF NOT EXISTS `hoods` (
`ID` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `net` varchar(255) NOT NULL,
  `lat` double DEFAULT NULL,
  `lon` double DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `hoods`
--

INSERT INTO `hoods` (`ID`, `name`, `net`, `lat`, `lon`) VALUES
(0, 'trainstation', '10.50.0.0/22', NULL, NULL),
(1, 'default', '10.50.16.0/20', NULL, NULL),
(2, 'fuerth', '10.50.32.0/21', 49.4814, 10.966),
(3, 'nuernberg', '10.50.40.0/21', 49.444, 11.05),
(4, 'ansbach', '10.50.48.0/21', 49.300833, 10.571667),
(5, 'haßberge', '10.50.56.0/22', 50.093555895082, 10.568013390003),
(6, 'erlangen', '10.50.64.0/21', 49.6005981, 11.0019221),
(7, 'wuerzburg', '10.50.72.0/21', 49.79688, 9.93489),
(8, 'Bamberg', '10.50.124.0/22', 49.89, 10.95),
(9, 'bgl', '10.50.80.0/21', 47.7314, 12.8825),
(10, 'HassbergeSued', '10.50.60.0/22', 50.04501, 10.568013390003),
(11, 'nbgland', '10.50.88.0/21', 49.39200496388418, 11.162796020507812),
(12, 'hof', '10.50.104.0/21', 50.3, 11.9),
(13, 'aschaffenburg', '10.50.96.0/22', 49.986113, 9.886394),
(14, 'marktredwitz', '10.50.112.0/22', 50.027736, 12.000519),
(15, 'forchheim', '10.50.116.0/22', 49.68, 11.1),
(16, 'Muenchberg', '10.50.120.0/22', 50.19, 11.79),
(17, 'Adelsdorf', '10.50.144.0/22', 49.6035981, 10.984488),
(18, 'Schweinfurt', '10.50.160.0/22', 50.04683, 10.21267);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `hoods`
--
ALTER TABLE `hoods`
 ADD PRIMARY KEY (`ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `hoods`
--
ALTER TABLE `hoods`
MODIFY `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=19;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

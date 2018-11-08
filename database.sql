-- EXAMPLE DATA NOT FOR USE!

-- phpMyAdmin SQL Dump
-- version 4.6.6deb4
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 25, 2018 at 03:11 PM
-- Server version: 10.1.26-MariaDB-0+deb9u1
-- PHP Version: 5.6.36-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `keyxchange`
--

-- --------------------------------------------------------

--
-- Table structure for table `gateways`
--

CREATE TABLE `gateways` (
  `ID` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `port` smallint(5) UNSIGNED NOT NULL,
  `hood_ID` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `gateways`
--

INSERT INTO `gateways` (`ID`, `name`, `key`, `ip`, `port`, `hood_ID`, `timestamp`) VALUES
(2, 'vm3fffgwcd1', '373cf6dca701a8b1516b816a13c91dc9df29ac5a822d12331b503982d655399b', '144.76.70.186', 10007, 0, '2017-09-27 07:15:09'),
(7, 'fff-nue2-gw2', '07be3d18b703e6e040a6920afb3e226ded6aa474961d8eecbb77b623bdd21059', '81.95.4.187', 10000, 2, '2017-10-28 05:45:51'),
(8, 'vm3fffgwcd1', '373cf6dca701a8b1516b816a13c91dc9df29ac5a822d12331b503982d655399b', '144.76.70.186', 10006, 1, '2017-09-27 07:15:09'),
(9, 'vm3fffgwcd1', '373cf6dca701a8b1516b816a13c91dc9df29ac5a822d12331b503982d655399b', '144.76.70.186', 10005, 30, '2017-09-27 07:15:09'),
(46, 'fff-neptun', '3834e45fa33c048f975e81042c1e93bb11dac82d9f03a0b24071bb72205247a8', '84.23.95.3', 10011, 31, '2018-09-02 09:00:59'),
(47, 'fff-neptun', '3834e45fa33c048f975e81042c1e93bb11dac82d9f03a0b24071bb72205247a8', '84.23.95.3', 10012, 0, '2018-09-02 14:36:14'),
(49, 'fff-neptun', '3834e45fa33c048f975e81042c1e93bb11dac82d9f03a0b24071bb72205247a8', '84.23.95.3', 10013, 1, '2018-09-02 14:36:14'),


-- --------------------------------------------------------

--
-- Table structure for table `hoods`
--

CREATE TABLE `hoods` (
  `ID` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `net` varchar(255) NOT NULL,
  `lat` double DEFAULT NULL,
  `lon` double DEFAULT NULL,
  `prefix` varchar(255) NOT NULL,
  `ntp_ip` varchar(255) NOT NULL,
  `ESSID_AP` varchar(32) NOT NULL,
  `ESSID_MESH` varchar(32) NOT NULL,
  `BSSID_MESH` varchar(17) NOT NULL,
  `mesh_id` varchar(32) NOT NULL,
  `protocol` varchar(50) NOT NULL DEFAULT 'batman-adv-v15',
  `channel2` int(11) NOT NULL DEFAULT '13',
  `mode2` varchar(30) NOT NULL DEFAULT 'ht20',
  `mesh_type2` varchar(30) NOT NULL DEFAULT '802.11s',
  `channel5` int(11) NOT NULL DEFAULT '40',
  `mode5` varchar(30) NOT NULL DEFAULT 'ht20',
  `mesh_type5` varchar(30) NOT NULL DEFAULT '802.11s',
  `upgrade_path` varchar(255) NOT NULL,
  `changedOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `hoods`
--

INSERT INTO `hoods` (`ID`, `name`, `net`, `lat`, `lon`, `prefix`, `ntp_ip`, `ESSID_AP`, `ESSID_MESH`, `BSSID_MESH`, `mesh_id`, `protocol`, `channel2`, `mode2`, `mesh_type2`, `channel5`, `mode5`, `mesh_type5`, `upgrade_path`, `changedOn`) VALUES
(0, 'TrainstationV2', '10.83.0.0/22', NULL, NULL, 'fd43:5602:29bd:0:/64', 'fd43:5602:29bd:ffff::1', 'trainstation.freifunk', 'batman.trainstation.freifunk', 'ca:ff:ee:ba:be:00', 'mesh.trainstation.freifunk', 'batman-adv-v15', 13, 'ht20', '802.11s', 40, 'ht20', '802.11s', '', '2017-11-22 13:08:54'),
(1, 'NuernbergV2', '10.83.4.0/22', 49.444, 11.05, 'fd43:5602:29bd:3:/64', 'fd43:5602:29bd:ffff::1', 'nuernberg.freifunk', 'batman.nuernberg.freifunk', 'ca:ff:ee:ba:be:03', 'mesh.nuernberg.freifunk', 'batman-adv-v15', 13, 'ht20', '802.11s', 40, 'ht20', '802.11s', '', '2017-10-22 01:47:41'),
(2, 'FuerthV2', '10.83.8.0/22', 49.4814, 10.966, 'fd43:5602:29bd:4:/64', 'fd43:5602:29bd:ffff::1', 'fuerth.freifunk', 'mesh.fue.fff', 'ca:ff:ee:ba:be:02', 'mesh.fue.fff', 'batman-adv-v15', 13, 'ht20', '802.11s', 40, 'ht20', '802.11s', '', '2018-08-05 08:01:24'),
(31, 'ErlangenStadt', '1234', NULL, NULL, '1234', '1234', 'ErlangenStadt', 'ErlangenStadt', 'ErlangenStadt', 'ErlangenStadt', 'batman-adv-v15', 13, 'ht20', '802.11s', 40, 'ht20', '802.11s', '', '2018-10-24 12:19:14'),
(32, 'FuerthStadt', '1234', NULL, NULL, '1234', '1234', 'FuerthStadt', 'FuerthStadt', 'FuerthStadt', 'FuerthStadt', 'batman-adv-v15', 13, 'ht20', '802.11s', 40, 'ht20', '802.11s', '', '2018-10-24 10:19:14');

-- --------------------------------------------------------

--
-- Table structure for table `polyhood`
--

CREATE TABLE `polyhood` (
  `id` int(10) NOT NULL,
  `polyid` int(10) NOT NULL,
  `lat` varchar(50) NOT NULL,
  `lon` varchar(50) NOT NULL,
  `hoodid` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `polyhood`
--

INSERT INTO `polyhood` (`id`, `polyid`, `lat`, `lon`, `hoodid`) VALUES
(18, 1, '49.58982152', '10.99503994', 31),
(19, 1, '49.58940422', '11.01199150', 31),
(20, 1, '49.59685950', '11.01787090', 31),
(21, 1, '49.60270052', '11.01722717', 31),
(22, 1, '49.60712255', '10.99988937', 31),
(23, 1, '49.58982152', '10.99503994', 31),
(24, 2, '49.46979740', '11.01302147', 32),
(25, 2, '49.47983623', '10.99259377', 32),
(26, 2, '49.48569126', '10.98083496', 32),
(27, 2, '49.45546063', '10.97740173', 32),
(28, 2, '49.44798376', '10.99851608', 32),
(29, 2, '49.45395418', '11.00915909', 32),
(30, 2, '49.46979740', '11.01302147', 32);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gateways`
--
ALTER TABLE `gateways`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `ID` (`ID`);

--
-- Indexes for table `hoods`
--
ALTER TABLE `hoods`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `ID` (`ID`);

--
-- Indexes for table `polyhood`
--
ALTER TABLE `polyhood`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gateways`
--
ALTER TABLE `gateways`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;
--
-- AUTO_INCREMENT for table `polyhood`
--
ALTER TABLE `polyhood`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

--- Updates for productive database
ALTER TABLE `hoods` ADD INDEX `coords` (`lat`, `lon`);
ALTER TABLE `gateways` ADD INDEX(`hood_ID`);
ALTER TABLE `polyhood` ADD INDEX(`polyid`);
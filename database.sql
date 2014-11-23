-- phpMyAdmin SQL Dump
-- version 3.4.11.1deb2+deb7u1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 10. Nov 2014 um 15:02
-- Server Version: 5.5.38
-- PHP-Version: 5.4.4-14+deb7u14

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Datenbank: `fff_xchange`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `hoods`
--

CREATE TABLE IF NOT EXISTS `hoods` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `net` varchar(255) NOT NULL,
  `lat` double NULL,
  `lon` double NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Daten für Tabelle `hoods`
--

INSERT INTO `hoods` (`ID`, `name`, `net`, `lat`, `lon`) VALUES
(1, 'trainstation', '10.50.16.0/20',    NULL,      NULL     ),
(2, 'fuerth',       '10.50.32.0/21',    49.478330, 10.990270),
(3, 'nuernberg',    '10.50.40.0/21',    49.448856, 11.082108),
(4, 'ansbach',      '10.50.48.0/21',    49.300833, 10.571667),
(5, 'haßberge',     '10.50.56.0/22',    50.093555, 10.568013),
(6, 'erlangen',     '10.50.64.0/21',    49.600598, 11.001922),
(7, 'wuerzburg',    '10.50.72.0/21',    49.796880,  9.934890);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `nodes`
--

CREATE TABLE IF NOT EXISTS `nodes` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mac` varchar(30) NOT NULL DEFAULT '000000000000',
  `name` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `ip` varchar(255) NOT NULL,
  `port` smallint(5) unsigned NOT NULL,
  `readonly` tinyint(1) NOT NULL DEFAULT '0',
  `isgateway` tinyint(1) NOT NULL DEFAULT '0',
  `hood_ID` int(10) unsigned NOT NULL DEFAULT '1',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `mac` (`mac`),
  KEY `hood_ID` (`hood_ID`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=569 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
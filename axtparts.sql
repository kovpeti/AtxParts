-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2019 at 04:50 PM
-- Server version: 10.4.8-MariaDB
-- PHP Version: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `axtparts`
--

-- --------------------------------------------------------

--
-- Table structure for table `assemblies`
--

CREATE TABLE `assemblies` (
  `assyid` int(10) UNSIGNED NOT NULL,
  `partid` int(10) UNSIGNED DEFAULT NULL,
  `assydescr` varchar(100) DEFAULT NULL,
  `assyrev` tinyint(3) UNSIGNED DEFAULT NULL,
  `assyaw` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `boms`
--

CREATE TABLE `boms` (
  `bomid` int(10) UNSIGNED NOT NULL,
  `partid` int(10) UNSIGNED DEFAULT NULL,
  `assyid` int(10) UNSIGNED DEFAULT NULL,
  `qty` decimal(8,2) DEFAULT NULL,
  `ref` text DEFAULT NULL,
  `um` varchar(10) DEFAULT NULL,
  `alt` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `bomvariants`
--

CREATE TABLE `bomvariants` (
  `bomvid` int(10) UNSIGNED NOT NULL,
  `variantid` int(10) UNSIGNED DEFAULT NULL,
  `bomid` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `components`
--

CREATE TABLE `components` (
  `compid` int(10) UNSIGNED NOT NULL,
  `partid` int(10) UNSIGNED DEFAULT NULL,
  `mfgname` text DEFAULT NULL,
  `mfgcode` varchar(25) DEFAULT NULL,
  `datasheet` int(10) UNSIGNED DEFAULT NULL,
  `compstateid` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `components`
--

INSERT INTO `components` (`compid`, `partid`, `mfgname`, `mfgcode`, `datasheet`, `compstateid`) VALUES
(1, 1, 'Murata', 'BLM21PG300SH1D', 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `compstates`
--

CREATE TABLE `compstates` (
  `compstateid` int(10) UNSIGNED NOT NULL,
  `statedescr` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `compstates`
--

INSERT INTO `compstates` (`compstateid`, `statedescr`) VALUES
(1, 'Not approved'),
(2, 'Approved - production'),
(3, 'Obsolete - pending'),
(4, 'Obsolete');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `contid` int(10) UNSIGNED NOT NULL,
  `cvid` int(10) UNSIGNED DEFAULT NULL,
  `contname` varchar(100) DEFAULT NULL,
  `contposn` varchar(50) DEFAULT NULL,
  `conttel` varchar(30) DEFAULT NULL,
  `contmob` varchar(30) DEFAULT NULL,
  `contemail` varchar(40) DEFAULT NULL,
  `contcomment` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `custvend`
--

CREATE TABLE `custvend` (
  `cvid` int(10) UNSIGNED NOT NULL,
  `cvname` varchar(50) DEFAULT NULL,
  `cvaddr1` varchar(80) DEFAULT NULL,
  `cvaddr2` varchar(80) DEFAULT NULL,
  `cvcity` varchar(50) DEFAULT NULL,
  `cvstate` varchar(20) DEFAULT NULL,
  `cvpcode` varchar(20) DEFAULT NULL,
  `cvcountry` varchar(30) DEFAULT NULL,
  `cvweb` varchar(80) DEFAULT NULL,
  `cvabn` varchar(20) DEFAULT NULL,
  `cvtel` varchar(40) DEFAULT NULL,
  `cvfax` varchar(40) DEFAULT NULL,
  `cvcomment` varchar(255) DEFAULT NULL,
  `cvtype` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `custvend`
--

INSERT INTO `custvend` (`cvid`, `cvname`, `cvaddr1`, `cvaddr2`, `cvcity`, `cvstate`, `cvpcode`, `cvcountry`, `cvweb`, `cvabn`, `cvtel`, `cvfax`, `cvcomment`, `cvtype`) VALUES
(1, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'RS', '', '', '', '', '', '', '', NULL, '', '', '', 17),
(5, 'Murata', '', '', '', '', '', '', '', NULL, '', '', '', 48),
(6, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'Farnell', '', '', '', '', '', '', '', NULL, '', '', '', 1);

-- --------------------------------------------------------

--
-- Table structure for table `datasheets`
--

CREATE TABLE `datasheets` (
  `dataid` int(10) UNSIGNED NOT NULL,
  `datasheetpath` varchar(120) DEFAULT NULL,
  `datadescr` varchar(250) DEFAULT NULL,
  `partcatid` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `engdocs`
--

CREATE TABLE `engdocs` (
  `engdocid` int(10) UNSIGNED NOT NULL,
  `assyid` int(10) UNSIGNED DEFAULT NULL,
  `engdocpath` varchar(120) DEFAULT NULL,
  `engdocdescr` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `fault`
--

CREATE TABLE `fault` (
  `faultid` int(10) UNSIGNED NOT NULL,
  `unitid` int(10) UNSIGNED DEFAULT NULL,
  `faultdescr` text DEFAULT NULL,
  `unitin` date DEFAULT NULL,
  `unitout` date DEFAULT NULL,
  `repairdescr` text DEFAULT NULL,
  `repairer` varchar(50) DEFAULT NULL,
  `fldrtnid` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `fldrtn`
--

CREATE TABLE `fldrtn` (
  `fldrtnid` int(10) UNSIGNED NOT NULL,
  `unitid` int(10) UNSIGNED DEFAULT NULL,
  `custdescr` text DEFAULT NULL,
  `allocdate` date DEFAULT NULL,
  `uranum` varchar(10) DEFAULT NULL,
  `custref` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `footprint`
--

CREATE TABLE `footprint` (
  `fprintid` int(10) UNSIGNED NOT NULL,
  `fprintdescr` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `footprint`
--

INSERT INTO `footprint` (`fprintid`, `fprintdescr`) VALUES
(1, 'DIP-4 300mil'),
(2, 'DIP-6 300mil'),
(3, 'DIP-8 300mil'),
(4, 'DIP-14 300mil'),
(5, 'DIP-14 300mil'),
(6, 'DIP-16 300mil'),
(7, 'DIP-18 300mil'),
(8, 'DIP-20 300mil'),
(9, 'DIP-24 300mil'),
(10, 'DIP-24 400mil'),
(11, 'DIP-24 600mil'),
(12, 'DIP-22 300mil'),
(13, 'DIP-28 300mil'),
(14, 'DIP-28 600mil'),
(15, 'DIP-32 600mil'),
(16, 'DIP-40 600mil'),
(17, 'SMD 0805'),
(18, 'SMD 0603'),
(19, 'SMD 1206'),
(20, 'SMD custom'),
(21, 'thru-hole custom');

-- --------------------------------------------------------

--
-- Table structure for table `locn`
--

CREATE TABLE `locn` (
  `locid` int(10) UNSIGNED NOT NULL,
  `locref` varchar(50) DEFAULT NULL,
  `locdescr` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE `log` (
  `logid` int(10) UNSIGNED NOT NULL,
  `logtype` int(11) DEFAULT NULL,
  `logdate` datetime DEFAULT NULL,
  `uid` int(10) UNSIGNED DEFAULT NULL,
  `logmsg` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `log`
--

INSERT INTO `log` (`logid`, `logtype`, `logdate`, `uid`, `logmsg`) VALUES
(1, 1, '2019-10-31 11:24:15', 1, 'Logged in.'),
(2, 1, '2019-11-01 09:28:20', 1, 'Logged in.'),
(3, 54, '2019-11-01 13:03:15', 1, 'Part created: BLM21PG300SH1D -  Ferrite Bead, 0805 [2012 Metric], 30 ohm, 4 A, BLM15A SH Series, 0.014 ohm, ± 25%'),
(4, 63, '2019-11-01 13:45:15', 1, 'Supplier added: 7, catnum: 2443253');

-- --------------------------------------------------------

--
-- Table structure for table `macs`
--

CREATE TABLE `macs` (
  `macid` int(10) UNSIGNED NOT NULL,
  `macaddr` varchar(20) DEFAULT NULL,
  `unitid` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `mfgdocs`
--

CREATE TABLE `mfgdocs` (
  `mfgdocid` int(10) UNSIGNED NOT NULL,
  `assyid` int(10) UNSIGNED DEFAULT NULL,
  `mfgdocpath` varchar(120) DEFAULT NULL,
  `mfgdocdescr` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `parts`
--

CREATE TABLE `parts` (
  `partid` int(10) UNSIGNED NOT NULL,
  `partdescr` varchar(100) DEFAULT NULL,
  `footprint` int(10) UNSIGNED DEFAULT NULL,
  `partcatid` int(10) UNSIGNED DEFAULT NULL,
  `partnumber` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `parts`
--

INSERT INTO `parts` (`partid`, `partdescr`, `footprint`, `partcatid`, `partnumber`) VALUES
(1, 'BLM21PG300SH1D -  Ferrite Bead, 0805 [2012 Metric], 30 ohm, 4 A, BLM15A SH Series, 0.014 ohm, ± 25%', 17, 51, 'PK000001C');

-- --------------------------------------------------------

--
-- Table structure for table `pgroups`
--

CREATE TABLE `pgroups` (
  `partcatid` int(10) UNSIGNED NOT NULL,
  `catdescr` varchar(50) DEFAULT NULL,
  `datadir` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pgroups`
--

INSERT INTO `pgroups` (`partcatid`, `catdescr`, `datadir`) VALUES
(1, 'Assembly (internal)', 'assembly/internal'),
(2, 'Assembly (external)', 'assembly/external'),
(3, 'Battery', 'battery'),
(4, 'Cable', 'cable'),
(5, 'Capacitor other', 'capacitor/other'),
(6, 'Capacitor SMD ceramic', 'capacitor/smd'),
(7, 'Capacitor SMD tantalum', 'capacitor/smd'),
(8, 'Capacitor SMD electrolytic', 'capacitor/smd'),
(9, 'Capacitor thru-hole ceramic', 'capacitor/thruhole'),
(10, 'Capacitor thru-hole electrolytic', 'capacitor/thruhole'),
(11, 'Capacitor thru-hole poly', 'capacitor/thruhole'),
(12, 'Capacitor thru-hole tantalum', 'capacitor/thruhole'),
(13, 'Capacitor thru-hole trimmer', 'capacitor/thruhole'),
(14, 'Capacitor SMD trimmer', 'capacitor/smd'),
(15, 'Connector audio RCA', 'connector/audio'),
(16, 'Connector audio phone', 'connector/audio'),
(17, 'Connector audio XLR', 'connector/audio'),
(18, 'Connector crimp lug', 'connector/crimp'),
(19, 'Connector D type', 'connector/dtype'),
(20, 'Connector DIN41612', 'connector/din41612'),
(21, 'Connector modular RJ', 'connector/modular'),
(22, 'Connector multipole crimp/PCB', 'connector/crimp'),
(23, 'Connector multipole MIL', 'connector/crimp'),
(24, 'Connector other', 'connector/other'),
(25, 'Connector DC barrel', 'connector/power'),
(26, 'Connector power', 'connector/power'),
(27, 'Connector RF BNC/PL/F', 'connector/rf'),
(28, 'Connector ribbon header', 'connector/ribbon'),
(29, 'Connector terminal block', 'connector/tb'),
(30, 'Crystal SMD', 'crystal/smd'),
(31, 'Crystal thru-hole', 'crystal/thruhole'),
(32, 'Diode bridge other', 'diode/bridge'),
(33, 'Diode bridge thru-hole', 'diode/bridge'),
(34, 'Diode bridge SMD', 'diode/bridge'),
(35, 'Diode rectifier SMD', 'diode/rectifier'),
(36, 'Diode rectifier thru-hole', 'diode/rectifier'),
(37, 'Diode schottky SMD', 'diode/schottky'),
(38, 'Enclosure', 'enclosure'),
(39, 'Fuse', 'fuse'),
(40, 'Fuse mounting SMD', 'fuse'),
(41, 'Fuse mounting thru-hole', 'fuse'),
(42, 'IC interface', 'ic/interface'),
(43, 'IC linear op amp', 'ic/linear/opamp'),
(44, 'IC voltage regulator', 'ic/linear/regulator'),
(45, 'IC linear special function', 'ic/linear/special'),
(46, 'IC logic', 'ic/logic'),
(47, 'IC memory DRAM', 'ic/memory/dram'),
(48, 'IC MPU Coldfire', 'ic/mpu/coldfire'),
(49, 'IC peripheral ethernet', 'ic/peripheral/ethernet'),
(50, 'IC PLD/CPLD', 'ic/pld/cpld'),
(51, 'Inductor ferrite SMD', 'inductor/ferrite'),
(52, 'Inductor power thru-hole', 'inductor/power'),
(53, 'Inductor other', 'inductor/other'),
(54, 'Label', 'label'),
(55, 'LED display other', 'display/led'),
(56, 'LED display SMD', 'display/led'),
(57, 'LED display thru-hole', 'display/led'),
(58, 'LED SMD', 'led/smd'),
(59, 'LED thru-hole', 'led/thruhole'),
(60, 'LED other', 'led/other'),
(61, 'LCD display', 'display/lcd'),
(62, 'Misc', 'misc'),
(63, 'Oscillator SMD', 'oscillator'),
(64, 'Oscillator thru-hole', 'oscillator'),
(65, 'PCB', 'pcb'),
(66, 'Potentiometer', 'potentiometer'),
(67, 'Resistor other', 'resistor/other'),
(68, 'Resistor SMD', 'resistor/smd'),
(69, 'Resistor thru-hole', 'resistor/thruhole'),
(70, 'Transformer other', 'transformer/other'),
(71, 'Transformer audio', 'transformer/audio'),
(72, 'Transformer network', 'transformer/network'),
(73, 'Transistor SMD BJT', 'transistor/bjt/smd'),
(74, 'Transistor thru-hole BJT', 'transistor/bjt/thruhole'),
(75, 'Transistor other BJT', 'transistor/bjt/other'),
(76, 'Transistor SMD FET', 'transistor/fet/smd'),
(77, 'Transistor thru-hole FET', 'transistor/fet/thruhole'),
(78, 'Transistor other FET', 'transistor/fet/other'),
(79, 'Transistor SMD MOSFET', 'transistor/fet/smd'),
(80, 'Transistor thru-hole MOSFET', 'transistor/fet/thruhole'),
(81, 'Transistor other MOSFET', 'transistor/fet/other'),
(82, 'Varistor SMD', 'varistor'),
(83, 'Varistor thru-hole', 'varistor'),
(84, 'Varistor other', 'varistor'),
(85, 'IC peripheral supervisory', 'ic/peripheral/supervisory'),
(86, 'Switch toggle', 'switch/toggle'),
(87, 'Switch pushbutton', 'switch/pushbutton'),
(88, 'Switch rotary', 'switch/rotary'),
(89, 'Switch slide', 'switch/slide'),
(90, 'Switch other', 'switch/other'),
(91, 'Heatsink', 'heatsink'),
(92, 'Resistor network SMD', 'resistor/network'),
(93, 'Resistor network thru-hole', 'resistor/network'),
(94, 'IC MPU PIC', 'ic/mpu/pic'),
(95, 'IC MPU ARM', 'ic/mpu/arm'),
(96, 'IC memory EEPROM', 'ic/memory/eeprom'),
(97, 'IC memory FLASH', 'ic/memory/flash'),
(98, 'IC memory SRAM', 'ic/memory/sram'),
(99, 'IC peripheral serial', 'ic/peripheral/serial'),
(100, 'Inductor ferrite thruhole', 'inductor/ferrite'),
(101, 'Inductor power SMD', 'inductor/power'),
(102, 'Transformer power', 'transformer/power'),
(103, 'Diode small signal thru-hole', 'diode/signal'),
(104, 'Diode small signal SMD', 'diode/signal'),
(105, 'Diode zener thru-hole', 'diode/zener'),
(106, 'Diode zener SMD', 'diode/zener'),
(107, 'IC opto', 'ic/opto'),
(108, 'Diode schottky thru-hole', 'diode/schottky'),
(109, 'Hardware knob 6.4mm grub screw', 'hardware/knob'),
(110, 'Hardware knob metric 18T spline', 'hardware/knob'),
(111, 'Switch rocker', 'switch/rocker'),
(112, 'LDR', 'resistor/ldr'),
(113, 'Relay thruhole', 'relay'),
(114, 'Relay SMD', 'relay'),
(115, 'Relay chassis mount', 'relay'),
(116, 'Connector IC socket', 'connector/icsocket'),
(117, 'Diode network SMD', 'diode/network'),
(118, 'Diode network thru-hole', 'diode/network'),
(119, 'Hardware knob 6.4mm collet', 'hardware/knob'),
(120, 'Connector SIL header', 'connector/sil'),
(121, 'Sensor', 'sensor'),
(122, 'IC memory EPROM', 'ic/memory/eprom'),
(123, 'Triac thru-hole', 'triac/thruhole');

-- --------------------------------------------------------

--
-- Table structure for table `produnits`
--

CREATE TABLE `produnits` (
  `produnitid` int(10) UNSIGNED NOT NULL,
  `unitidsub` int(10) UNSIGNED DEFAULT NULL,
  `unitidmaster` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `roleid` int(10) UNSIGNED NOT NULL,
  `rolename` varchar(64) DEFAULT NULL,
  `privilege` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`roleid`, `rolename`, `privilege`) VALUES
(1, 'su', 2147483623);

-- --------------------------------------------------------

--
-- Table structure for table `stock`
--

CREATE TABLE `stock` (
  `stockid` int(10) UNSIGNED NOT NULL,
  `qty` int(10) UNSIGNED DEFAULT NULL,
  `note` varchar(64) DEFAULT NULL,
  `locid` int(10) UNSIGNED DEFAULT NULL,
  `partid` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `compsuppid` int(10) UNSIGNED NOT NULL,
  `compid` int(10) UNSIGNED DEFAULT NULL,
  `suppid` int(10) UNSIGNED DEFAULT NULL,
  `suppcatno` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`compsuppid`, `compid`, `suppid`, `suppcatno`) VALUES
(1, 1, 7, '2443253');

-- --------------------------------------------------------

--
-- Table structure for table `swbuild`
--

CREATE TABLE `swbuild` (
  `swbuildid` int(10) UNSIGNED NOT NULL,
  `swname` varchar(100) DEFAULT NULL,
  `buildhost` varchar(25) DEFAULT NULL,
  `buildimage` varchar(100) DEFAULT NULL,
  `releaserev` varchar(20) DEFAULT NULL,
  `releasedate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `swlicence`
--

CREATE TABLE `swlicence` (
  `swlid` int(10) UNSIGNED NOT NULL,
  `unitid` int(10) UNSIGNED DEFAULT NULL,
  `swbuildid` int(10) UNSIGNED DEFAULT NULL,
  `licencenum` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `unit`
--

CREATE TABLE `unit` (
  `unitid` int(10) UNSIGNED NOT NULL,
  `serialnum` varchar(20) DEFAULT NULL,
  `assyid` int(10) UNSIGNED DEFAULT NULL,
  `variantid` int(10) UNSIGNED DEFAULT NULL,
  `mfgid` int(10) UNSIGNED DEFAULT NULL,
  `mfgdate` date DEFAULT NULL,
  `custid` int(10) UNSIGNED DEFAULT NULL,
  `custordnum` varchar(20) DEFAULT NULL,
  `myinvnum` varchar(20) DEFAULT NULL,
  `shipdate` date DEFAULT NULL,
  `warranty` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `uid` int(10) UNSIGNED NOT NULL,
  `loginid` varchar(16) DEFAULT NULL,
  `passwd` varchar(128) DEFAULT NULL,
  `username` varchar(128) DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `logincount` int(10) UNSIGNED DEFAULT 0,
  `status` int(10) UNSIGNED DEFAULT NULL,
  `roleid` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`uid`, `loginid`, `passwd`, `username`, `lastlogin`, `logincount`, `status`, `roleid`) VALUES
(1, 'admin', 'zZ0MXYLX07ks8rgt1vW48AFzBq9pWTR0RAYohA==', 'Administrator', '2019-11-01 09:28:20', 2, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `variant`
--

CREATE TABLE `variant` (
  `variantid` int(10) UNSIGNED NOT NULL,
  `variantname` varchar(25) DEFAULT NULL,
  `variantdescr` text DEFAULT NULL,
  `variantstate` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `warranty`
--

CREATE TABLE `warranty` (
  `wrntid` int(10) UNSIGNED NOT NULL,
  `warrantydescr` varchar(25) DEFAULT NULL,
  `wtyweeks` smallint(5) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assemblies`
--
ALTER TABLE `assemblies`
  ADD PRIMARY KEY (`assyid`),
  ADD KEY `partid` (`partid`);

--
-- Indexes for table `boms`
--
ALTER TABLE `boms`
  ADD PRIMARY KEY (`bomid`),
  ADD KEY `partid` (`partid`),
  ADD KEY `assyid` (`assyid`);

--
-- Indexes for table `bomvariants`
--
ALTER TABLE `bomvariants`
  ADD PRIMARY KEY (`bomvid`),
  ADD KEY `variantid` (`variantid`),
  ADD KEY `bomid` (`bomid`);

--
-- Indexes for table `components`
--
ALTER TABLE `components`
  ADD PRIMARY KEY (`compid`),
  ADD KEY `partid` (`partid`);

--
-- Indexes for table `compstates`
--
ALTER TABLE `compstates`
  ADD PRIMARY KEY (`compstateid`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`contid`),
  ADD KEY `cvid` (`cvid`);

--
-- Indexes for table `custvend`
--
ALTER TABLE `custvend`
  ADD PRIMARY KEY (`cvid`),
  ADD KEY `cvname` (`cvname`),
  ADD KEY `cvtype` (`cvtype`);

--
-- Indexes for table `datasheets`
--
ALTER TABLE `datasheets`
  ADD PRIMARY KEY (`dataid`),
  ADD KEY `partcatid` (`partcatid`);

--
-- Indexes for table `engdocs`
--
ALTER TABLE `engdocs`
  ADD PRIMARY KEY (`engdocid`),
  ADD KEY `assyid` (`assyid`);

--
-- Indexes for table `fault`
--
ALTER TABLE `fault`
  ADD PRIMARY KEY (`faultid`),
  ADD KEY `unitid` (`unitid`),
  ADD KEY `fldrtnid` (`fldrtnid`);

--
-- Indexes for table `fldrtn`
--
ALTER TABLE `fldrtn`
  ADD PRIMARY KEY (`fldrtnid`),
  ADD KEY `unitid` (`unitid`);

--
-- Indexes for table `footprint`
--
ALTER TABLE `footprint`
  ADD PRIMARY KEY (`fprintid`);

--
-- Indexes for table `locn`
--
ALTER TABLE `locn`
  ADD PRIMARY KEY (`locid`);

--
-- Indexes for table `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`logid`),
  ADD KEY `logtype` (`logtype`),
  ADD KEY `logdate` (`logdate`),
  ADD KEY `uid` (`uid`);

--
-- Indexes for table `macs`
--
ALTER TABLE `macs`
  ADD PRIMARY KEY (`macid`),
  ADD UNIQUE KEY `macaddr` (`macaddr`),
  ADD KEY `unitid` (`unitid`);

--
-- Indexes for table `mfgdocs`
--
ALTER TABLE `mfgdocs`
  ADD PRIMARY KEY (`mfgdocid`),
  ADD KEY `assyid` (`assyid`);

--
-- Indexes for table `parts`
--
ALTER TABLE `parts`
  ADD PRIMARY KEY (`partid`),
  ADD UNIQUE KEY `partnumber` (`partnumber`),
  ADD KEY `partcatid` (`partcatid`),
  ADD KEY `partdescr` (`partdescr`);

--
-- Indexes for table `pgroups`
--
ALTER TABLE `pgroups`
  ADD PRIMARY KEY (`partcatid`);

--
-- Indexes for table `produnits`
--
ALTER TABLE `produnits`
  ADD PRIMARY KEY (`produnitid`),
  ADD KEY `unitidsub` (`unitidsub`),
  ADD KEY `unitidmaster` (`unitidmaster`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`roleid`),
  ADD UNIQUE KEY `rolename` (`rolename`);

--
-- Indexes for table `stock`
--
ALTER TABLE `stock`
  ADD PRIMARY KEY (`stockid`),
  ADD KEY `locid` (`locid`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`compsuppid`),
  ADD KEY `compid` (`compid`),
  ADD KEY `suppid` (`suppid`);

--
-- Indexes for table `swbuild`
--
ALTER TABLE `swbuild`
  ADD PRIMARY KEY (`swbuildid`);

--
-- Indexes for table `swlicence`
--
ALTER TABLE `swlicence`
  ADD PRIMARY KEY (`swlid`),
  ADD KEY `unitid` (`unitid`),
  ADD KEY `swbuildid` (`swbuildid`);

--
-- Indexes for table `unit`
--
ALTER TABLE `unit`
  ADD PRIMARY KEY (`unitid`),
  ADD UNIQUE KEY `serialnum` (`serialnum`),
  ADD KEY `assyid` (`assyid`),
  ADD KEY `variantid` (`variantid`),
  ADD KEY `mfgid` (`mfgid`),
  ADD KEY `mfgdate` (`mfgdate`),
  ADD KEY `custid` (`custid`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `loginid` (`loginid`),
  ADD KEY `status` (`status`),
  ADD KEY `roleid` (`roleid`);

--
-- Indexes for table `variant`
--
ALTER TABLE `variant`
  ADD PRIMARY KEY (`variantid`);

--
-- Indexes for table `warranty`
--
ALTER TABLE `warranty`
  ADD PRIMARY KEY (`wrntid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assemblies`
--
ALTER TABLE `assemblies`
  MODIFY `assyid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `boms`
--
ALTER TABLE `boms`
  MODIFY `bomid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bomvariants`
--
ALTER TABLE `bomvariants`
  MODIFY `bomvid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `components`
--
ALTER TABLE `components`
  MODIFY `compid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `compstates`
--
ALTER TABLE `compstates`
  MODIFY `compstateid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `contid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custvend`
--
ALTER TABLE `custvend`
  MODIFY `cvid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `datasheets`
--
ALTER TABLE `datasheets`
  MODIFY `dataid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `engdocs`
--
ALTER TABLE `engdocs`
  MODIFY `engdocid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fault`
--
ALTER TABLE `fault`
  MODIFY `faultid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fldrtn`
--
ALTER TABLE `fldrtn`
  MODIFY `fldrtnid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `footprint`
--
ALTER TABLE `footprint`
  MODIFY `fprintid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `locn`
--
ALTER TABLE `locn`
  MODIFY `locid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log`
--
ALTER TABLE `log`
  MODIFY `logid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `macs`
--
ALTER TABLE `macs`
  MODIFY `macid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mfgdocs`
--
ALTER TABLE `mfgdocs`
  MODIFY `mfgdocid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parts`
--
ALTER TABLE `parts`
  MODIFY `partid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pgroups`
--
ALTER TABLE `pgroups`
  MODIFY `partcatid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `produnits`
--
ALTER TABLE `produnits`
  MODIFY `produnitid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `roleid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock`
--
ALTER TABLE `stock`
  MODIFY `stockid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `compsuppid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `swbuild`
--
ALTER TABLE `swbuild`
  MODIFY `swbuildid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `swlicence`
--
ALTER TABLE `swlicence`
  MODIFY `swlid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `unit`
--
ALTER TABLE `unit`
  MODIFY `unitid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `uid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `variant`
--
ALTER TABLE `variant`
  MODIFY `variantid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `warranty`
--
ALTER TABLE `warranty`
  MODIFY `wrntid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

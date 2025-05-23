-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2025 at 02:12 PM
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
-- Database: `hotel_reservation_systemdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE `account` (
  `ID` int(11) NOT NULL,
  `FirstName` varchar(255) NOT NULL,
  `LastName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `PhoneNumber` bigint(11) NOT NULL,
  `Position` enum('','Hotel Staff','Hotel Admin','') NOT NULL,
  `Status` enum('Active','Inactive','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`ID`, `FirstName`, `LastName`, `Email`, `Password`, `PhoneNumber`, `Position`, `Status`) VALUES
(202310637, 'Phoebe Ann', 'Balderamos', 'phoebeann.balderamos@cvsu.edu.ph', 'phoebe@cvsu', 9471275781, 'Hotel Staff', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `BookingID` int(11) NOT NULL,
  `ReservationID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `StaffID` bigint(11) NOT NULL,
  `RoomNumber` int(11) NOT NULL,
  `RoomType` enum('Standard','Deluxe','Suite','') NOT NULL,
  `BookingStatus` enum('Pending','Confirmed','Cancelled','Completed') NOT NULL,
  `RoomStatus` enum('Available','Booked','Reserved','Maintenance','Cleaning') NOT NULL,
  `Notes` text NOT NULL,
  `CheckInDate` datetime NOT NULL,
  `CheckOutDate` datetime NOT NULL,
  `BookingDate` datetime NOT NULL,
  `Price` decimal(10,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `booking`
--

INSERT INTO `booking` (`BookingID`, `ReservationID`, `StudentID`, `StaffID`, `RoomNumber`, `RoomType`, `BookingStatus`, `RoomStatus`, `Notes`, `CheckInDate`, `CheckOutDate`, `BookingDate`, `Price`) VALUES
(10013, 0, 0, 0, 101, 'Standard', 'Pending', 'Available', 'Extra one pillow', '2025-05-23 18:48:15', '2025-05-26 18:48:15', '2025-05-23 18:48:15', 1500);

-- --------------------------------------------------------

--
-- Table structure for table `guest_request`
--

CREATE TABLE `guest_request` (
  `RequestID` int(15) NOT NULL,
  `GuestID` int(15) NOT NULL,
  `StaffID` int(15) NOT NULL,
  `ItemID` int(15) NOT NULL,
  `RoomNumber` int(5) NOT NULL,
  `RequestTimeStamp` datetime NOT NULL,
  `RequestStatus` enum('Approved','Completed','No stocks','') NOT NULL,
  `SpecificRequest` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guest_request`
--

INSERT INTO `guest_request` (`RequestID`, `GuestID`, `StaffID`, `ItemID`, `RoomNumber`, `RequestTimeStamp`, `RequestStatus`, `SpecificRequest`) VALUES
(1, 202310637, 203, 145660, 1101, '2025-05-23 16:28:17', 'Approved', 'Can I please request two extra pillows and a blanket? Also, if possible, I would like a room freshener sprayed while I\'m out this afternoon. Thank you!');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `ItemID` int(15) NOT NULL,
  `AdminID` int(15) NOT NULL,
  `StaffID` int(15) NOT NULL,
  `RequestID` int(15) NOT NULL,
  `ItemName` varchar(255) NOT NULL,
  `DateReceived` datetime NOT NULL,
  `DateExpiry` datetime NOT NULL,
  `Quantity` int(255) NOT NULL,
  `Price` decimal(10,0) NOT NULL,
  `Total` int(255) NOT NULL,
  `CurrentStocks` bigint(255) NOT NULL,
  `RqStocks` bigint(255) NOT NULL,
  `Status` enum('Approved','Denied','','') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`ItemID`, `AdminID`, `StaffID`, `RequestID`, `ItemName`, `DateReceived`, `DateExpiry`, `Quantity`, `Price`, `Total`, `CurrentStocks`, `RqStocks`, `Status`) VALUES
(1, 0, 0, 0, 'Pillow', '2025-05-23 15:09:29', '2025-06-09 15:09:29', 10, 250, 2500, 20, 0, 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `menu_service`
--

CREATE TABLE `menu_service` (
  `menu_serviceID` int(11) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `Type` enum('Reception/Front Desk','Housekeeping','Room Service','Food & Beverage','Wi-Fi & Technology','Spa & Wellness','Safety & Security') NOT NULL,
  `Description` varchar(255) NOT NULL,
  `SellingPrice` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_service`
--

INSERT INTO `menu_service` (`menu_serviceID`, `Name`, `Type`, `Description`, `SellingPrice`) VALUES
(1, 'Gym', 'Spa & Wellness', 'Stay fit during your stay with access to our fully equipped gym, available daily for all guests', 1500);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(15) NOT NULL,
  `ReservationID` int(15) NOT NULL,
  `AdminID` int(15) NOT NULL,
  `BookingID` int(15) NOT NULL,
  `Amount` decimal(10,0) NOT NULL,
  `PaymentStatus` enum('Paid','Unpaid','Failed','Refunded') NOT NULL,
  `PaymentDate` datetime NOT NULL,
  `PaymentMethod` enum('Cash','Card','Online','') NOT NULL,
  `Discount` decimal(10,0) NOT NULL,
  `TotalBill` decimal(10,0) NOT NULL,
  `ReferenceCode` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`PaymentID`, `ReservationID`, `AdminID`, `BookingID`, `Amount`, `PaymentStatus`, `PaymentDate`, `PaymentMethod`, `Discount`, `TotalBill`, `ReferenceCode`) VALUES
(2, 20, 1111, 21, 2000, 'Unpaid', '2025-05-23 16:54:08', 'Cash', 400, 1600, '545465323ab'),
(202310637, 20, 1111, 21, 2000, 'Paid', '2025-05-23 16:22:07', 'Cash', 400, 1600, '20265644522Fr88');

-- --------------------------------------------------------

--
-- Table structure for table `room`
--

CREATE TABLE `room` (
  `RoomNumber` int(11) NOT NULL,
  `RoomType` enum('Standard','Deluxe','Suite','') NOT NULL,
  `RoomPerHour` int(11) NOT NULL,
  `RoomStatus` enum('Available','Occupied','Maintenance','Cleaning') NOT NULL,
  `Capacity` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room`
--

INSERT INTO `room` (`RoomNumber`, `RoomType`, `RoomPerHour`, `RoomStatus`, `Capacity`) VALUES
(1101, 'Standard', 5, 'Available', '2-3 pax'),
(1102, 'Deluxe', 5, 'Occupied', '3'),
(1103, 'Suite', 10, 'Maintenance', '5');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int(9) NOT NULL,
  `FirstName` varchar(255) NOT NULL,
  `LastName` varchar(255) NOT NULL,
  `Gender` enum('Male','Female','Prefer not to say','') NOT NULL,
  `PhoneNumber` int(11) NOT NULL,
  `Address` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Nationality` text NOT NULL,
  `Birthdate` date NOT NULL,
  `DocumentIDType` enum('UMID','Government Service Insurance System (GSIS)','Philippine Identification (Philid)/ephilid','School ID','PWD ID','Drivers License','Passport','Postal ID','TIN ID','Philippine Passport','Philippine Health Insurance Corporation','Senior Citizen ID','SSS ID','Voters ID','National ID','Commission On Elections (Comelec) ID') NOT NULL,
  `IDNumber` varchar(255) NOT NULL,
  `IssuedDate` date NOT NULL,
  `ExpiryDate` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`StudentID`, `FirstName`, `LastName`, `Gender`, `PhoneNumber`, `Address`, `Email`, `Nationality`, `Birthdate`, `DocumentIDType`, `IDNumber`, `IssuedDate`, `ExpiryDate`) VALUES
(202310637, 'Phoebe Ann', 'Balderamos', 'Female', 565323, 'Blk 6 Lot 27 Villa Arcadia Carsadang Bago I', 'phoebeann.balderamos@cvsu.edu.ph', 'Filipino', '2015-05-07', 'School ID', '202310637', '2023-09-18', '2027-09-18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`BookingID`);

--
-- Indexes for table `guest_request`
--
ALTER TABLE `guest_request`
  ADD PRIMARY KEY (`RequestID`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`ItemID`),
  ADD UNIQUE KEY `RequestID` (`RequestID`);

--
-- Indexes for table `menu_service`
--
ALTER TABLE `menu_service`
  ADD PRIMARY KEY (`menu_serviceID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`);

--
-- Indexes for table `room`
--
ALTER TABLE `room`
  ADD PRIMARY KEY (`RoomNumber`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202310638;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `BookingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10014;

--
-- AUTO_INCREMENT for table `guest_request`
--
ALTER TABLE `guest_request`
  MODIFY `RequestID` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `ItemID` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `menu_service`
--
ALTER TABLE `menu_service`
  MODIFY `menu_serviceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202310638;

--
-- AUTO_INCREMENT for table `room`
--
ALTER TABLE `room`
  MODIFY `RoomNumber` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1104;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `StudentID` int(9) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202310638;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

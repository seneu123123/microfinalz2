-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2026 at 01:04 PM
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
-- Database: `logistics_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `archived_documents`
--

CREATE TABLE `archived_documents` (
  `id` int(11) NOT NULL,
  `document_id` varchar(20) NOT NULL,
  `report_id` varchar(20) NOT NULL,
  `document_category` enum('Audit Reports','Compliance Documents','Management Reports','Regulatory Filings','Internal Controls') NOT NULL,
  `retention_period` enum('1 Year','3 Years','5 Years','7 Years','Permanent') NOT NULL,
  `access_level` enum('Public','Internal','Confidential','Restricted') NOT NULL,
  `archive_notes` text DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` varchar(100) DEFAULT 'System'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) DEFAULT NULL COMMENT 'Links to warehouse if converted from inventory',
  `asset_name` varchar(150) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Active','In Maintenance','Retired','Lost') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `inventory_id`, `asset_name`, `serial_number`, `supplier_id`, `purchase_date`, `warranty_expiry`, `status`, `created_at`) VALUES
(1, 4, 'Aggin', '123456', NULL, NULL, '2026-02-05', 'Active', '2026-03-12 03:02:43');

-- --------------------------------------------------------

--
-- Table structure for table `asset_audits`
--

CREATE TABLE `asset_audits` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `auditor_id` int(11) NOT NULL,
  `audit_date` date NOT NULL,
  `condition_status` enum('Excellent','Good','Fair','Poor','Broken') NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_monitoring`
--

CREATE TABLE `asset_monitoring` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `health_percentage` int(11) DEFAULT 100,
  `usage_reading` varchar(50) DEFAULT NULL,
  `last_check_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `inspector` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` varchar(20) NOT NULL,
  `reservation_id` varchar(20) NOT NULL,
  `driver_id` varchar(20) NOT NULL,
  `vehicle_id` varchar(20) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_checklists`
--

CREATE TABLE `audit_checklists` (
  `id` int(11) NOT NULL,
  `checklist_id` varchar(20) NOT NULL,
  `checklist_name` varchar(255) NOT NULL,
  `audit_type` enum('Internal','External','Regulatory','Compliance') NOT NULL,
  `department` varchar(100) NOT NULL,
  `checklist_items` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checklist_items`)),
  `status` enum('Active','Inactive','Draft') DEFAULT 'Active',
  `created_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_checklists`
--

INSERT INTO `audit_checklists` (`id`, `checklist_id`, `checklist_name`, `audit_type`, `department`, `checklist_items`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CHK-0001', 'Financial Controls Checklist', 'Internal', 'Finance', '[{\"item\": \"Review cash handling procedures\", \"required\": true}, {\"item\": \"Verify bank reconciliations\", \"required\": true}, {\"item\": \"Check expense approvals\", \"required\": true}]', 'Active', 'Admin', '2026-03-18 18:02:59', '2026-03-18 18:02:59'),
(2, 'CHK-0002', 'Vendor Management Checklist', 'External', 'Vendor Portal', '[{\"item\": \"Verify vendor credentials\", \"required\": true}, {\"item\": \"Check compliance documentation\", \"required\": true}, {\"item\": \"Review contract terms\", \"required\": false}]', 'Active', 'Admin', '2026-03-18 18:02:59', '2026-03-18 18:02:59'),
(3, 'CHK-4410', 'saddsdf', 'Internal', 'Finance', '[{\"item\":\"dasda\",\"required\":false}]', 'Active', 'sads', '2026-03-18 18:47:56', '2026-03-18 18:47:56');

-- --------------------------------------------------------

--
-- Table structure for table `audit_evidence`
--

CREATE TABLE `audit_evidence` (
  `id` int(11) NOT NULL,
  `evidence_id` varchar(20) NOT NULL,
  `finding_id` varchar(20) NOT NULL,
  `evidence_type` enum('Document','Photo','Video','Audio','Screenshot','Email','Other') NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_findings`
--

CREATE TABLE `audit_findings` (
  `id` int(11) NOT NULL,
  `finding_id` varchar(20) NOT NULL,
  `audit_id` varchar(20) NOT NULL,
  `category` enum('Compliance','Financial','Operational','Control','Documentation','Process','Other') NOT NULL,
  `severity` enum('Critical','High','Medium','Low') NOT NULL,
  `date_identified` date NOT NULL,
  `department` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `recommendation` text DEFAULT NULL,
  `evidence_count` int(11) DEFAULT 0,
  `severity_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_findings`
--

INSERT INTO `audit_findings` (`id`, `finding_id`, `audit_id`, `category`, `severity`, `date_identified`, `department`, `description`, `recommendation`, `evidence_count`, `severity_reason`, `created_at`, `updated_at`) VALUES
(1, 'FND-6358', 'AUD-0001', 'Financial', 'High', '2026-04-06', 'dasda', 'dasdas', 'dasda', 0, '', '2026-03-18 18:53:25', '2026-03-18 18:53:33');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL,
  `vendor_id` varchar(20) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_progress`
--

CREATE TABLE `audit_progress` (
  `id` int(11) NOT NULL,
  `progress_id` varchar(20) NOT NULL,
  `audit_id` varchar(20) NOT NULL,
  `current_status` enum('Not Started','In Progress','Review','Completed','On Hold') DEFAULT 'Not Started',
  `completion_percentage` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `evidence_count` int(11) DEFAULT 0,
  `created_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_progress`
--

INSERT INTO `audit_progress` (`id`, `progress_id`, `audit_id`, `current_status`, `completion_percentage`, `notes`, `evidence_count`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PRG-1920', 'AUD-0581', 'Review', 0, 'Progress tracking started for audit: dsadsda', 0, 'System', '2026-03-18 19:03:14', '2026-03-18 19:03:34');

-- --------------------------------------------------------

--
-- Table structure for table `audit_reports`
--

CREATE TABLE `audit_reports` (
  `id` int(11) NOT NULL,
  `report_id` varchar(20) NOT NULL,
  `report_type` enum('Summary','Detailed','Findings','Compliance','Corrective Actions','Trends') NOT NULL,
  `audit_period` varchar(50) NOT NULL,
  `department` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `report_description` text DEFAULT NULL,
  `include_sections` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`include_sections`)),
  `status` enum('Draft','Generated','Archived') DEFAULT 'Draft',
  `report_format` enum('PDF','Excel','Word','HTML') DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `pdf_options` enum('standard','high','compressed') DEFAULT NULL,
  `watermark` enum('none','confidential','draft','internal') DEFAULT NULL,
  `email_to` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `exported_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_reports`
--

INSERT INTO `audit_reports` (`id`, `report_id`, `report_type`, `audit_period`, `department`, `start_date`, `end_date`, `report_description`, `include_sections`, `status`, `report_format`, `additional_notes`, `pdf_options`, `watermark`, `email_to`, `generated_at`, `exported_at`, `archived_at`, `generated_date`, `updated_at`) VALUES
(1, 'RPT-1153', 'Detailed', 'Q2 2024', 'Fleet Management', '2026-04-07', '2026-03-28', 'hih', '[\"executive_summary\",\"methodology\",\"findings\",\"recommendations\"]', 'Draft', NULL, NULL, 'high', 'confidential', '', NULL, '2026-03-18 19:06:16', NULL, '2026-03-18 19:05:41', '2026-03-18 19:06:16');

-- --------------------------------------------------------

--
-- Table structure for table `audit_schedules`
--

CREATE TABLE `audit_schedules` (
  `id` int(11) NOT NULL,
  `audit_id` varchar(20) NOT NULL,
  `audit_title` varchar(255) NOT NULL,
  `audit_type` enum('Internal','External','Regulatory','Compliance') NOT NULL,
  `department` varchar(100) NOT NULL,
  `auditor` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_schedules`
--

INSERT INTO `audit_schedules` (`id`, `audit_id`, `audit_title`, `audit_type`, `department`, `auditor`, `start_date`, `end_date`, `status`, `description`, `created_at`, `updated_at`) VALUES
(1, 'AUD-0001', 'Q1 2024 Internal Audit', 'Internal', 'Finance', 'John Smith', '2024-01-15', '2024-01-31', 'Completed', 'Quarterly internal audit of financial controls', '2026-03-18 18:02:59', '2026-03-18 18:02:59'),
(2, 'AUD-0002', 'Vendor Compliance Audit', 'External', 'Vendor Portal', 'Jane Doe', '2024-02-01', '2024-02-15', 'In Progress', 'Annual vendor compliance review', '2026-03-18 18:02:59', '2026-03-18 18:02:59'),
(3, 'AUD-0003', 'Fleet Management Audit', 'Internal', 'Fleet Management', 'Mike Johnson', '2024-03-01', '2024-03-20', 'Scheduled', 'Comprehensive fleet management audit', '2026-03-18 18:02:59', '2026-03-18 18:02:59'),
(4, 'AUD-0581', 'dsad', 'External', 'Document Tracking', 'xCzcz', '2026-03-06', '2026-04-10', 'Scheduled', 'czxczcz', '2026-03-18 18:45:19', '2026-03-18 18:45:19');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `department` varchar(100) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `total_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `first_name`, `last_name`, `status`, `email`) VALUES
(1, 'John', 'Doe', 'Active', 'john@gmail.com'),
(2, 'Sarah', 'Miller', 'Active', 'sarah@gmail.com'),
(3, 'Robert', 'Johnson', 'Active', 'rob@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `contract_documents`
--

CREATE TABLE `contract_documents` (
  `doc_id` varchar(20) NOT NULL,
  `contract_id` varchar(20) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `e_signature` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Sent','Executed') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `corrective_actions`
--

CREATE TABLE `corrective_actions` (
  `id` int(11) NOT NULL,
  `action_id` varchar(20) NOT NULL,
  `finding_id` varchar(20) NOT NULL,
  `action_title` varchar(255) NOT NULL,
  `priority` enum('High','Medium','Low') NOT NULL,
  `department` varchar(100) NOT NULL,
  `assigned_to` varchar(100) NOT NULL,
  `target_date` date NOT NULL,
  `action_description` text NOT NULL,
  `resources_required` text DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Overdue','Cancelled') DEFAULT 'Pending',
  `assignment_notes` text DEFAULT NULL,
  `deadline_reason` text DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `completion_evidence` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `corrective_actions`
--

INSERT INTO `corrective_actions` (`id`, `action_id`, `finding_id`, `action_title`, `priority`, `department`, `assigned_to`, `target_date`, `action_description`, `resources_required`, `status`, `assignment_notes`, `deadline_reason`, `completion_date`, `completion_notes`, `completion_evidence`, `created_at`, `updated_at`) VALUES
(1, 'ACT-7464', 'FND-6358', 'dfsdf', 'High', 'Document Tracking', 'dsadas', '2026-04-01', 'dfsadfsdfas', 'fdasf', 'Pending', 'dsad', NULL, NULL, NULL, NULL, '2026-03-18 19:07:06', '2026-03-18 19:07:12');

-- --------------------------------------------------------

--
-- Table structure for table `disbursements`
--

CREATE TABLE `disbursements` (
  `id` int(11) NOT NULL,
  `project_id` int(11) DEFAULT NULL,
  `po_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `status` enum('Pending','Approved','Released','Rejected') DEFAULT 'Pending',
  `release_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `doc_type` varchar(100) DEFAULT NULL,
  `doc_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL,
  `status` enum('Valid','Expired','Pending') DEFAULT 'Valid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` varchar(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `license_number` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('Available','Assigned','On Leave','Inactive') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department` varchar(50) NOT NULL,
  `position` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fleet`
--

CREATE TABLE `fleet` (
  `fleet_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `total_vehicles` int(11) DEFAULT 0,
  `active_vehicles` int(11) DEFAULT 0,
  `utilization_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fleet_management`
--

CREATE TABLE `fleet_management` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL COMMENT 'Links to the vehicle in the assets table',
  `license_plate` varchar(50) NOT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `current_mileage` int(11) DEFAULT 0,
  `last_service_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_usage`
--

CREATE TABLE `fuel_usage` (
  `usage_id` varchar(20) NOT NULL,
  `trip_id` varchar(20) DEFAULT NULL,
  `liters` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(20) DEFAULT 'pcs',
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `zone_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `sku`, `quantity`, `unit`, `unit_price`, `last_updated`, `zone_id`) VALUES
(1, 'Papers', NULL, 11, 'lot', 600.00, '2026-02-26 03:30:55', NULL),
(2, 'Milk', NULL, 1, 'lot', 1000.00, '2026-03-05 00:01:51', NULL),
(3, 'krap', NULL, 5, '10', 0.00, '2026-03-05 00:03:01', NULL),
(4, 'Ballpen', NULL, 4, '15', NULL, '2026-03-12 03:02:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `change_amount` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_logs`
--

INSERT INTO `inventory_logs` (`id`, `item_id`, `change_amount`, `reason`, `created_at`) VALUES
(1, 1, 1, 'Received PO #1', '2026-02-26 03:01:50'),
(2, 1, 10, 'Received PO #2', '2026-02-26 03:30:55'),
(3, 2, 1, 'Received PO #3', '2026-03-05 00:01:51'),
(4, 3, 5, 'Received PO #4', '2026-03-05 00:03:01'),
(5, 4, 5, 'Received PO #5', '2026-03-12 02:58:43');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

CREATE TABLE `loans` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `interest_rate` decimal(5,2) DEFAULT 0.00,
  `status` enum('Pending','Approved','Active','Completed','Rejected') DEFAULT 'Pending',
  `release_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_payments`
--

CREATE TABLE `loan_payments` (
  `id` int(11) NOT NULL,
  `loan_id` int(11) NOT NULL,
  `teller_id` int(11) NOT NULL,
  `amount_paid` decimal(15,2) NOT NULL,
  `payment_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_requests`
--

CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `issue_description` text NOT NULL,
  `priority` enum('Low','Normal','High','Urgent') DEFAULT 'Normal',
  `status` enum('Pending','In Progress','Resolved','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_schedule`
--

CREATE TABLE `maintenance_schedule` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `task_description` varchar(255) NOT NULL,
  `frequency_days` int(11) NOT NULL,
  `next_due_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `otp_codes`
--

CREATE TABLE `otp_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `otp_code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `used_at` datetime DEFAULT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_codes`
--

INSERT INTO `otp_codes` (`id`, `user_id`, `email`, `otp_code`, `expires_at`, `created_at`, `used_at`, `is_used`) VALUES
(17, 0, 'admin@lemon.com', '954687', '2026-03-24 19:21:34', '2026-03-24 19:11:34', NULL, 0),
(18, 18, 'test@example.com', '283803', '2026-03-24 19:27:43', '2026-03-24 19:17:43', '2026-03-24 19:17:43', 1),
(23, 19, 'admin@gmail.com', '171562', '2026-03-24 20:05:51', '2026-03-24 19:55:51', '2026-03-24 19:56:25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_name` varchar(100) NOT NULL,
  `position` varchar(50) NOT NULL,
  `pay_period` varchar(50) NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_pay` decimal(10,2) NOT NULL,
  `status` enum('Pending','Paid') DEFAULT 'Pending',
  `payment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_incidents`
--

CREATE TABLE `performance_incidents` (
  `incident_id` varchar(20) NOT NULL,
  `performance_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `incident_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('Low','Medium','High') DEFAULT 'Low',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `procurement_vendors`
--

CREATE TABLE `procurement_vendors` (
  `id` int(11) NOT NULL,
  `vendor_id` varchar(50) NOT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `business_type` varchar(255) DEFAULT NULL,
  `business_details` text DEFAULT NULL,
  `sent_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Planning','Active','On Hold','Completed') DEFAULT 'Planning',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `budget_limit` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `project_name`, `description`, `status`, `start_date`, `end_date`, `budget_limit`, `created_at`) VALUES
(1, 'Aggin', 'Boobas', 'Planning', '2026-03-12', NULL, 500.00, '2026-03-12 02:59:16');

-- --------------------------------------------------------

--
-- Table structure for table `project_documents`
--

CREATE TABLE `project_documents` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `document_name` varchar(200) NOT NULL,
  `file_url` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `status` enum('Ordered','Received','Cancelled') DEFAULT 'Ordered',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `requisition_id`, `supplier_id`, `total_cost`, `status`, `created_at`) VALUES
(1, 1, 1, 600.00, 'Received', '2026-02-26 03:01:42'),
(2, 2, 2, 300.00, 'Received', '2026-02-26 03:30:50'),
(3, 3, 2, 1000.00, 'Received', '2026-03-05 00:01:48'),
(4, 4, 2, 1000.00, 'Received', '2026-03-05 00:02:58'),
(5, 5, 4, 0.00, 'Received', '2026-03-12 02:58:37');

-- --------------------------------------------------------

--
-- Table structure for table `requisitions`
--

CREATE TABLE `requisitions` (
  `id` int(11) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisitions`
--

INSERT INTO `requisitions` (`id`, `request_date`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(5, '2026-03-24 09:38:49', 'bestlink', 'pending', '2026-03-24 09:38:49', '2026-03-24 09:38:49'),
(6, '2026-03-24 09:45:49', 'Office Supplies', 'pending', '2026-03-24 09:45:49', '2026-03-24 09:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `requisition_items`
--

CREATE TABLE `requisition_items` (
  `id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisition_items`
--

INSERT INTO `requisition_items` (`id`, `requisition_id`, `item_name`, `quantity`, `unit`, `created_at`) VALUES
(6, 5, 'papers', 12.00, 'set', '2026-03-24 09:38:49'),
(7, 6, 'Monitors', 3.00, 'pcs', '2026-03-24 09:45:49');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `vehicle_id` varchar(20) DEFAULT NULL,
  `requestor` varchar(255) DEFAULT NULL,
  `vehicle_type` varchar(100) DEFAULT NULL,
  `schedule` varchar(255) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `reservation_date` date DEFAULT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservation_schedules`
--

CREATE TABLE `reservation_schedules` (
  `schedule_id` varchar(50) NOT NULL,
  `reservation_id` varchar(50) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `priority_level` enum('Normal','High','Urgent','VIP') DEFAULT 'Normal',
  `location` varchar(255) DEFAULT NULL,
  `assigned_driver` varchar(50) DEFAULT NULL,
  `assigned_vehicle` varchar(50) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `safety_audits`
--

CREATE TABLE `safety_audits` (
  `id` int(11) NOT NULL,
  `audit_type` varchar(100) NOT NULL,
  `auditor_name` varchar(100) NOT NULL,
  `audit_date` date NOT NULL,
  `status` enum('Passed','Failed','Needs Action') DEFAULT 'Passed',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `safety_incidents`
--

CREATE TABLE `safety_incidents` (
  `id` int(11) NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `location` varchar(255) NOT NULL,
  `incident_date` date NOT NULL,
  `description` text NOT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Low',
  `action_taken` text DEFAULT NULL,
  `status` enum('Reported','Under Investigation','Resolved') DEFAULT 'Reported',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `storage_zones`
--

CREATE TABLE `storage_zones` (
  `id` int(11) NOT NULL,
  `zone_name` varchar(50) NOT NULL,
  `capacity_level` enum('Low','Medium','Full') DEFAULT 'Low',
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `storage_zones`
--

INSERT INTO `storage_zones` (`id`, `zone_name`, `capacity_level`, `description`) VALUES
(1, 'Main Rack - Aisle A', 'Low', 'General dry goods and office supplies'),
(2, 'Warehouse Floor - Zone B', 'Low', 'Bulk construction and heavy materials'),
(3, 'Secure Vault', 'Low', 'High-value equipment and electronics');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `company_name`, `contact_person`, `email`, `phone`, `address`, `description`, `status`) VALUES
(1, 'Maniac Corp', 'Dabu', 'dabuemailko@gmail.com', '09676902222', '17 Aparri New Zealand', NULL, 'Active'),
(2, 'Kook Corp', 'Trigga', 'triggaemail@gm.com', '0920920292', '18 Uwu Street', 'Milk', 'Active'),
(3, 'Nega Corp', 'Aggin', 'Aggin420@gmail.com', '09696969696', 'Quezon City', NULL, 'Active'),
(4, 'Nega Corp', 'Aggin', 'Aggin420@gmail.com', '09696969696', 'Commonwealth Ave', NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('To Do','In Progress','Done','Blocked') DEFAULT 'To Do',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `project_id`, `task_name`, `assigned_to`, `due_date`, `priority`, `status`, `created_at`) VALUES
(1, 1, 'Conduct inventory management', 'Chris Brown', '2026-03-04', 'Urgent', 'In Progress', '2026-03-12 03:00:45');

-- --------------------------------------------------------

--
-- Table structure for table `teller_drawers`
--

CREATE TABLE `teller_drawers` (
  `id` int(11) NOT NULL,
  `teller_name` varchar(100) NOT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('Open','Closed') DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teller_drawers`
--

INSERT INTO `teller_drawers` (`id`, `teller_name`, `current_balance`, `status`) VALUES
(1, 'Counter 1 - Front Desk', 0.00, 'Open');

-- --------------------------------------------------------

--
-- Table structure for table `trip_logs`
--

CREATE TABLE `trip_logs` (
  `trip_id` varchar(20) NOT NULL,
  `reservation_id` varchar(20) NOT NULL,
  `vehicle_id` varchar(20) NOT NULL,
  `driver_id` varchar(20) NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `fuel_used` decimal(10,2) DEFAULT NULL,
  `distance` decimal(10,2) DEFAULT NULL,
  `status` enum('In Progress','Completed','Cancelled') DEFAULT 'In Progress',
  `incident` text DEFAULT NULL,
  `vendor_service` varchar(255) DEFAULT NULL,
  `audit_reference` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('admin','admin2','vendor_user') NOT NULL DEFAULT 'vendor_user',
  `otp_token` varchar(6) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `status`, `created_at`, `updated_at`, `role`, `otp_token`, `otp_expires_at`) VALUES
(1, 'System Administrator', 'admin@logistics.com', '$2y$10$wfbyjvSQ3k4LxVD5XkQuc..JmerirQgP4ch60mMNNYvUVlSHg4y9C', 'active', '2026-03-16 07:06:56', '2026-03-18 16:06:16', 'admin', NULL, NULL),
(2, 'Finance Manager', 'finance@microfinance.com', '$2y$10$wjD.BSqalsPlQczFoQEHxujO7CWvC0ybox8DvVTZLcMZAuVjnTF/G', 'active', '2026-03-16 07:06:56', '2026-03-18 16:06:16', 'admin', NULL, NULL),
(3, 'Operations Manager', 'operations@company.admin', '$2y$10$w4QNHVAiZ.7/jC8yAro6..ifYegXNpQRKf.JDSB948BWQzgQ6/AGW', 'active', '2026-03-16 07:06:56', '2026-03-18 16:06:16', 'admin', NULL, NULL),
(4, 'Test Vendor User', 'vendor@supplier.com', '$2y$10$r00I2WnwgcvDmMJTl8hXi.2rOHlc0Xercw75E7DUv3v750M2a8eyK', 'active', '2026-03-16 07:06:56', '2026-03-16 07:06:56', 'vendor_user', NULL, NULL),
(5, 'Partner User', 'partner@partner.com', '$2y$10$6xbfAA6NpjjW62P7HVFb4ukwSyfFBgNBT5fwgHRaVaPlt/JEfPDgG', 'active', '2026-03-16 07:06:56', '2026-03-16 07:06:56', 'vendor_user', NULL, NULL),
(6, 'Supplier User', 'supplier@vendor.com', '$2y$10$qr.X6ju8Jb1xgU.cY4frzeyZfLitxfgkLkSzilDhK9vVKymtOebPC', 'active', '2026-03-16 07:06:56', '2026-03-16 07:06:56', 'vendor_user', NULL, NULL),
(7, 'New Admin User', 'newadmin@microfinance.com', '$2y$10$83AI4zmvJklLzpbghbDQdemLNJ9S4CFJMAVUmy4pL.j6b5UhSw.YC', 'pending', '2026-03-16 07:07:25', '2026-03-18 16:06:16', 'admin', NULL, NULL),
(8, 'New Vendor User', 'newvendor@partner.com', '$2y$10$jl/nA.f0y4NIJeQ3NeT0Me8OwUHqFRkzvt95qLfGqGnS6RE/wk0l.', 'pending', '2026-03-16 07:07:25', '2026-03-16 07:07:25', 'vendor_user', NULL, NULL),
(10, 'Test User', 'test1773810513@test.com', '$2y$10$xx4Ht6lP/Y8amggb21jmcu9YJ.i3CQm1ZswEtrSv5XdKxlzsEWQw.', 'pending', '2026-03-18 05:08:33', '2026-03-18 05:08:33', 'vendor_user', NULL, NULL),
(11, 'creasa', 'creasan@gmail.com', '$2y$10$o1urfrf4T5yut1RW.IIw7.rThm8.cAKEzVlonOmyipvZcsdTJQR1W', 'pending', '2026-03-18 05:11:53', '2026-03-18 05:11:53', 'vendor_user', NULL, NULL),
(12, 'Administrator', 'admin@microfinance.com', '$2y$10$81Ek36.4lxaAkAGaNsLnm.DLqmfcXsgAQF4Ro0AoMzcc0NI0bGbua', 'active', '2026-03-18 05:30:52', '2026-03-18 16:06:16', 'admin', NULL, NULL),
(13, 'Test Vendor', 'vendor@company.com', '$2y$10$smIyVWLTP408mTtdpvYKIOHBcPAB63dnTTd5p0eIFDetJnPsb4vB2', 'active', '2026-03-18 05:30:52', '2026-03-18 05:30:52', 'vendor_user', NULL, NULL),
(14, 'Test Admin', 'testadmin@microfinance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '2026-03-18 16:24:20', '2026-03-18 16:24:20', 'admin', NULL, NULL),
(15, 'Test Admin2', 'testadmin2@microfinance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '2026-03-18 16:24:20', '2026-03-18 16:24:20', 'admin2', NULL, NULL),
(16, 'Test Vendor', 'testvendor@microfinance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '2026-03-18 16:24:20', '2026-03-18 16:24:20', 'vendor_user', NULL, NULL),
(17, 'Another Vendor', 'anothervendor@microfinance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '2026-03-18 16:24:20', '2026-03-18 16:24:20', 'vendor_user', NULL, NULL),
(18, 'Test User', 'test@example.com', '$2y$10$0QmzKUD0/X5JrDiVv796oOmgG1dcJOi7WCzVx5y.rjEMRx.MExNvK', 'active', '2026-03-24 11:16:47', '2026-03-24 11:16:47', 'admin', NULL, NULL),
(19, 'Admin', 'admin@gmail.com', '$2y$10$JGPvvF0QFjrMIEdkM9iJU.QDqzlEHlh4A1wx4V4DFRUOCq2F4R7Ge', 'active', '2026-03-24 11:40:51', '2026-03-24 11:55:51', 'admin', '171562', '2026-03-24 20:05:51');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `expires_at`, `created_at`) VALUES
(5, 12, '545a64fbd5010add216ea4b7dde198642f539e435e39b81725a1b330f5ece89d', '2026-03-18 22:35:55', '2026-03-18 05:35:55'),
(6, 12, 'e392cff04777d2bbfb39cb4f05317bd491579d14272c3bc9600b4cd49a77f302', '2026-03-18 22:38:25', '2026-03-18 05:38:25'),
(7, 12, 'abb2eb370ef4bcb471e459c694d34456c32eda7a97cd844c1af41d31db870243', '2026-03-18 22:38:29', '2026-03-18 05:38:29'),
(8, 12, '0798c026740b9af0a0ee0990d086ba13c0ba696ef3ccebcf205abc96e8eae972', '2026-03-18 22:38:29', '2026-03-18 05:38:29'),
(9, 12, '74e9e775d00e1b3098251503f386941d18ea7e92585394a5b94be5053c805096', '2026-03-18 22:38:29', '2026-03-18 05:38:29'),
(10, 12, '916c80101a5148d728d12e0fd2ba7a67d321f57006c512bc777af7c90a5e6647', '2026-03-18 22:38:31', '2026-03-18 05:38:31'),
(11, 12, 'c333bc897ff02bcb4df85807e970b5e9d256c0bae9ee3010ef15ce3d33848546', '2026-03-18 22:38:32', '2026-03-18 05:38:32'),
(12, 12, 'e720d036a08a701140c3b953d31ca427365936495211ba3cdf0e363873838326', '2026-03-18 22:38:32', '2026-03-18 05:38:32');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `vehicle_type` varchar(100) DEFAULT NULL,
  `registration_no` varchar(50) DEFAULT NULL,
  `make_model` varchar(255) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive','Maintenance','Retired') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `vendor_id` varchar(20) NOT NULL,
  `vendor_name` varchar(255) NOT NULL,
  `business_type` varchar(100) DEFAULT NULL,
  `registration_no` varchar(100) DEFAULT NULL,
  `tin` varchar(50) DEFAULT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Active','Inactive') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `business_permit_path` varchar(500) DEFAULT NULL,
  `company_registration_path` varchar(500) DEFAULT NULL,
  `business_details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_accreditation`
--

CREATE TABLE `vendor_accreditation` (
  `accreditation_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `license_expiry` date DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `compliance_checklist` text DEFAULT NULL,
  `risk_score` int(11) DEFAULT 0,
  `status` enum('Pending Review','Accredited','Suspended','Revoked') DEFAULT 'Pending Review',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_contracts`
--

CREATE TABLE `vendor_contracts` (
  `contract_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `contract_type` enum('Service','Fuel','Lease','Maintenance','Other') DEFAULT 'Service',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `contract_amount` decimal(12,2) DEFAULT NULL,
  `contract_terms` text DEFAULT NULL,
  `approval_status` enum('Draft','Pending','Approved','Active','Expired','Cancelled') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_documents`
--

CREATE TABLE `vendor_documents` (
  `id` int(11) NOT NULL,
  `vendor_id` varchar(50) NOT NULL,
  `document_type` enum('business_permit','company_registration') NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_performance`
--

CREATE TABLE `vendor_performance` (
  `performance_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `on_time_rate` decimal(5,2) DEFAULT 0.00,
  `quality_score` decimal(5,2) DEFAULT 0.00,
  `incident_reports` int(11) DEFAULT 0,
  `average_cost` decimal(12,2) DEFAULT NULL,
  `performance_rating` decimal(5,2) DEFAULT 0.00,
  `sla_document` varchar(500) DEFAULT NULL,
  `performance_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_profiles`
--

CREATE TABLE `vendor_profiles` (
  `profile_id` int(11) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `service_category` varchar(100) DEFAULT NULL,
  `coverage_area` varchar(255) DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `performance_rating` decimal(3,1) DEFAULT 0.0,
  `active_status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_quotations`
--

CREATE TABLE `vendor_quotations` (
  `quotation_id` varchar(20) NOT NULL,
  `vendor_id` varchar(20) NOT NULL,
  `date_created` date DEFAULT NULL,
  `content` text DEFAULT NULL,
  `type` enum('Quotation','MaterialOffer','POHistory') DEFAULT 'Quotation',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_requisitions`
--

CREATE TABLE `vendor_requisitions` (
  `id` int(11) NOT NULL,
  `requisition_id` int(11) NOT NULL,
  `vendor_id` varchar(50) DEFAULT NULL,
  `sent_to` varchar(50) NOT NULL DEFAULT 'vendor_registration',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sent_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_by` varchar(50) NOT NULL DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vendor_requisitions`
--

INSERT INTO `vendor_requisitions` (`id`, `requisition_id`, `vendor_id`, `sent_to`, `status`, `updated_at`, `sent_date`, `sent_by`, `created_at`) VALUES
(1, 8, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 15:06:42', 'admin', '2026-03-18 15:06:42'),
(2, 8, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 15:06:48', 'admin', '2026-03-18 15:06:48'),
(3, 8, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 15:08:15', 'admin', '2026-03-18 15:08:15'),
(4, 7, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 15:08:22', 'admin', '2026-03-18 15:08:22'),
(5, 6, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 15:53:06', 'admin', '2026-03-18 15:53:06'),
(6, 1, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 15:53:23', 'admin', '2026-03-18 15:53:23'),
(7, 6, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 16:37:11', 'admin', '2026-03-18 16:37:11'),
(8, 2, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 16:37:29', 'admin', '2026-03-18 16:37:29'),
(9, 6, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 16:42:17', 'admin', '2026-03-18 16:42:17'),
(10, 2, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-18 16:42:35', 'admin', '2026-03-18 16:42:35'),
(11, 3, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-24 08:03:01', 'admin', '2026-03-24 08:03:01'),
(12, 3, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-24 08:03:14', 'admin', '2026-03-24 08:03:14'),
(13, 4, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 09:48:52', '2026-03-24 08:05:46', 'admin', '2026-03-24 08:05:46'),
(14, 5, 'VENDOR_REGISTRATION', 'vendor_registration', 'pending', '2026-03-24 10:19:59', '2026-03-24 10:19:59', 'admin', '2026-03-24 10:19:59');

-- --------------------------------------------------------

--
-- Table structure for table `work_orders`
--

CREATE TABLE `work_orders` (
  `id` int(11) NOT NULL,
  `maintenance_request_id` int(11) NOT NULL,
  `assigned_technician` varchar(100) DEFAULT NULL,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `actual_cost` decimal(10,2) DEFAULT 0.00,
  `parts_requisition_id` int(11) DEFAULT NULL COMMENT 'Links to Requisition if parts need to be ordered',
  `status` enum('Open','Waiting on Parts','Completed') DEFAULT 'Open',
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `archived_documents`
--
ALTER TABLE `archived_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `document_id` (`document_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `asset_audits`
--
ALTER TABLE `asset_audits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `auditor_id` (`auditor_id`);

--
-- Indexes for table `asset_monitoring`
--
ALTER TABLE `asset_monitoring`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `idx_reservation_id` (`reservation_id`),
  ADD KEY `idx_driver_id` (`driver_id`);

--
-- Indexes for table `audit_checklists`
--
ALTER TABLE `audit_checklists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `checklist_id` (`checklist_id`);

--
-- Indexes for table `audit_evidence`
--
ALTER TABLE `audit_evidence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `evidence_id` (`evidence_id`);

--
-- Indexes for table `audit_findings`
--
ALTER TABLE `audit_findings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `finding_id` (`finding_id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_action` (`action`);

--
-- Indexes for table `audit_progress`
--
ALTER TABLE `audit_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `progress_id` (`progress_id`);

--
-- Indexes for table `audit_reports`
--
ALTER TABLE `audit_reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `report_id` (`report_id`);

--
-- Indexes for table `audit_schedules`
--
ALTER TABLE `audit_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `audit_id` (`audit_id`);

--
-- Indexes for table `contract_documents`
--
ALTER TABLE `contract_documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `corrective_actions`
--
ALTER TABLE `corrective_actions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `action_id` (`action_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_doc_type` (`doc_type`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`),
  ADD UNIQUE KEY `license_number` (`license_number`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `fleet`
--
ALTER TABLE `fleet`
  ADD PRIMARY KEY (`fleet_id`),
  ADD UNIQUE KEY `unique_vendor` (`vendor_id`);

--
-- Indexes for table `fuel_usage`
--
ALTER TABLE `fuel_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD KEY `idx_trip_id` (`trip_id`);

--
-- Indexes for table `otp_codes`
--
ALTER TABLE `otp_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_otp_code` (`otp_code`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `performance_incidents`
--
ALTER TABLE `performance_incidents`
  ADD PRIMARY KEY (`incident_id`),
  ADD KEY `performance_id` (`performance_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `procurement_vendors`
--
ALTER TABLE `procurement_vendors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requisitions`
--
ALTER TABLE `requisitions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `requisition_id` (`requisition_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_approval_status` (`approval_status`);

--
-- Indexes for table `reservation_schedules`
--
ALTER TABLE `reservation_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `idx_reservation_id` (`reservation_id`),
  ADD KEY `idx_start_time` (`start_time`);

--
-- Indexes for table `trip_logs`
--
ALTER TABLE `trip_logs`
  ADD PRIMARY KEY (`trip_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `idx_reservation_id` (`reservation_id`),
  ADD KEY `idx_vehicle_id` (`vehicle_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD UNIQUE KEY `registration_no` (`registration_no`),
  ADD KEY `idx_vendor_id` (`vendor_id`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`vendor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `vendor_accreditation`
--
ALTER TABLE `vendor_accreditation`
  ADD PRIMARY KEY (`accreditation_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `vendor_contracts`
--
ALTER TABLE `vendor_contracts`
  ADD PRIMARY KEY (`contract_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_status` (`approval_status`);

--
-- Indexes for table `vendor_documents`
--
ALTER TABLE `vendor_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `vendor_performance`
--
ALTER TABLE `vendor_performance`
  ADD PRIMARY KEY (`performance_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`);

--
-- Indexes for table `vendor_profiles`
--
ALTER TABLE `vendor_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `vendor_id` (`vendor_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`);

--
-- Indexes for table `vendor_quotations`
--
ALTER TABLE `vendor_quotations`
  ADD PRIMARY KEY (`quotation_id`),
  ADD KEY `vendor_id` (`vendor_id`);

--
-- Indexes for table `vendor_requisitions`
--
ALTER TABLE `vendor_requisitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_requisition_id` (`requisition_id`),
  ADD KEY `idx_vendor_id` (`vendor_id`),
  ADD KEY `idx_sent_to` (`sent_to`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `archived_documents`
--
ALTER TABLE `archived_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_checklists`
--
ALTER TABLE `audit_checklists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `audit_evidence`
--
ALTER TABLE `audit_evidence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_findings`
--
ALTER TABLE `audit_findings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_progress`
--
ALTER TABLE `audit_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_reports`
--
ALTER TABLE `audit_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_schedules`
--
ALTER TABLE `audit_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `corrective_actions`
--
ALTER TABLE `corrective_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `otp_codes`
--
ALTER TABLE `otp_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `procurement_vendors`
--
ALTER TABLE `procurement_vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `requisitions`
--
ALTER TABLE `requisitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vendor_documents`
--
ALTER TABLE `vendor_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_profiles`
--
ALTER TABLE `vendor_profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_requisitions`
--
ALTER TABLE `vendor_requisitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`),
  ADD CONSTRAINT `assignments_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE SET NULL;

--
-- Constraints for table `contract_documents`
--
ALTER TABLE `contract_documents`
  ADD CONSTRAINT `contract_documents_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `vendor_contracts` (`contract_id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `fleet`
--
ALTER TABLE `fleet`
  ADD CONSTRAINT `fleet_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `fuel_usage`
--
ALTER TABLE `fuel_usage`
  ADD CONSTRAINT `fuel_usage_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trip_logs` (`trip_id`) ON DELETE SET NULL;

--
-- Constraints for table `performance_incidents`
--
ALTER TABLE `performance_incidents`
  ADD CONSTRAINT `performance_incidents_ibfk_1` FOREIGN KEY (`performance_id`) REFERENCES `vendor_performance` (`performance_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `performance_incidents_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD CONSTRAINT `requisition_items_ibfk_1` FOREIGN KEY (`requisition_id`) REFERENCES `requisitions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE SET NULL;

--
-- Constraints for table `trip_logs`
--
ALTER TABLE `trip_logs`
  ADD CONSTRAINT `trip_logs_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`reservation_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trip_logs_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`),
  ADD CONSTRAINT `trip_logs_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_accreditation`
--
ALTER TABLE `vendor_accreditation`
  ADD CONSTRAINT `vendor_accreditation_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_contracts`
--
ALTER TABLE `vendor_contracts`
  ADD CONSTRAINT `vendor_contracts_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_documents`
--
ALTER TABLE `vendor_documents`
  ADD CONSTRAINT `vendor_documents_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_performance`
--
ALTER TABLE `vendor_performance`
  ADD CONSTRAINT `vendor_performance_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_profiles`
--
ALTER TABLE `vendor_profiles`
  ADD CONSTRAINT `vendor_profiles_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_quotations`
--
ALTER TABLE `vendor_quotations`
  ADD CONSTRAINT `vendor_quotations_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

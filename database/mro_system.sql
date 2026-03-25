-- MRO System Database Extension for Logistics 1
-- Integrates with existing logistics_db structure

USE logistics_db;

-- Enhanced MRO Work Orders Table
CREATE TABLE IF NOT EXISTS `mro_work_orders` (
  `work_order_id` varchar(20) NOT NULL,
  `maintenance_request_id` int(11) DEFAULT NULL,
  `fleet_vehicle_id` varchar(20) DEFAULT NULL COMMENT 'From fleet system',
  `asset_id` int(11) DEFAULT NULL COMMENT 'From assets table',
  `work_order_type` enum('Preventive','Corrective','Emergency','Inspection') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('Low','Normal','High','Urgent','Critical') DEFAULT 'Normal',
  `status` enum('Draft','Pending','In Progress','On Hold','Completed','Cancelled') DEFAULT 'Draft',
  `assigned_technician` varchar(100) DEFAULT NULL,
  `estimated_hours` decimal(5,2) DEFAULT NULL,
  `actual_hours` decimal(5,2) DEFAULT NULL,
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `parts_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `scheduled_date` datetime DEFAULT NULL,
  `started_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`work_order_id`),
  KEY `idx_maintenance_request` (`maintenance_request_id`),
  KEY `idx_fleet_vehicle` (`fleet_vehicle_id`),
  KEY `idx_asset` (`asset_id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_technician` (`assigned_technician`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MRO Parts Usage Table
CREATE TABLE IF NOT EXISTS `mro_parts_usage` (
  `usage_id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_id` varchar(20) NOT NULL,
  `inventory_id` int(11) DEFAULT NULL,
  `part_name` varchar(255) NOT NULL,
  `part_number` varchar(50) DEFAULT NULL,
  `quantity_used` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usage_id`),
  KEY `idx_work_order` (`work_order_id`),
  KEY `idx_inventory` (`inventory_id`),
  FOREIGN KEY (`work_order_id`) REFERENCES `mro_work_orders`(`work_order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MRO Maintenance Planning Table
CREATE TABLE IF NOT EXISTS `mro_maintenance_planning` (
  `plan_id` varchar(20) NOT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `fleet_vehicle_id` varchar(20) DEFAULT NULL,
  `plan_type` enum('Preventive','Predictive','Inspection','Overhaul') NOT NULL,
  `plan_title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `frequency_miles` int(11) DEFAULT NULL,
  `frequency_hours` int(11) DEFAULT NULL,
  `last_performed_date` date DEFAULT NULL,
  `next_due_date` date NOT NULL,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `required_parts` text DEFAULT NULL COMMENT 'JSON array of required parts',
  `required_tools` text DEFAULT NULL COMMENT 'JSON array of required tools',
  `safety_requirements` text DEFAULT NULL,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`plan_id`),
  KEY `idx_asset` (`asset_id`),
  KEY `idx_fleet_vehicle` (`fleet_vehicle_id`),
  KEY `idx_next_due` (`next_due_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MRO Compliance and Safety Table
CREATE TABLE IF NOT EXISTS `mro_compliance_safety` (
  `check_id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_id` varchar(20) NOT NULL,
  `check_type` enum('Pre-Work','Post-Work','Safety','Quality','Environmental') NOT NULL,
  `checklist_items` longtext DEFAULT NULL COMMENT 'JSON checklist',
  `performed_by` varchar(100) NOT NULL,
  `check_date` datetime NOT NULL,
  `results` longtext DEFAULT NULL COMMENT 'JSON results',
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `issues_found` text DEFAULT NULL,
  `corrective_actions` text DEFAULT NULL,
  `signature_required` tinyint(1) DEFAULT 0,
  `signature_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`check_id`),
  KEY `idx_work_order` (`work_order_id`),
  KEY `idx_check_type` (`check_type`),
  KEY `idx_check_date` (`check_date`),
  FOREIGN KEY (`work_order_id`) REFERENCES `mro_work_orders`(`work_order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MRO Technicians Table
CREATE TABLE IF NOT EXISTS `mro_technicians` (
  `technician_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL COMMENT 'JSON array of skills',
  `certifications` text DEFAULT NULL COMMENT 'JSON array of certifications',
  `availability_status` enum('Available','Busy','On Leave','Unavailable') DEFAULT 'Available',
  `hourly_rate` decimal(8,2) DEFAULT 0.00,
  `max_work_orders` int(11) DEFAULT 5,
  `current_workload` int(11) DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`technician_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_specialization` (`specialization`),
  KEY `idx_availability` (`availability_status`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MRO Integration Log Table (for Fleet integration)
CREATE TABLE IF NOT EXISTS `mro_integration_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_system` enum('Fleet','Asset','Manual') NOT NULL,
  `source_id` varchar(50) NOT NULL,
  `action` enum('Request_Received','Work_Order_Created','Status_Update','Completed','Report_Sent') NOT NULL,
  `data` longtext DEFAULT NULL COMMENT 'JSON data received/sent',
  `response_status` enum('Success','Error','Pending') DEFAULT 'Pending',
  `response_message` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_source_system` (`source_system`),
  KEY `idx_source_id` (`source_id`),
  KEY `idx_action` (`action`),
  KEY `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- MRO Reports Table
CREATE TABLE IF NOT EXISTS `mro_reports` (
  `report_id` varchar(20) NOT NULL,
  `report_type` enum('Work_Order_Summary','Maintenance_History','Parts_Usage','Technician_Performance','Compliance_Report','Cost_Analysis') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parameters` longtext DEFAULT NULL COMMENT 'JSON parameters',
  `data` longtext DEFAULT NULL COMMENT 'JSON report data',
  `generated_by` int(11) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('Generated','Exported','Archived') DEFAULT 'Generated',
  PRIMARY KEY (`report_id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_generated_by` (`generated_by`),
  KEY `idx_generated_at` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update existing maintenance_requests table to integrate with MRO
ALTER TABLE `maintenance_requests` 
ADD COLUMN `fleet_vehicle_id` varchar(20) DEFAULT NULL COMMENT 'From fleet system integration',
ADD COLUMN `source_system` enum('Manual','Fleet','Asset') DEFAULT 'Manual',
ADD COLUMN `integration_data` text DEFAULT NULL COMMENT 'JSON data from source system',
ADD COLUMN `work_order_id` varchar(20) DEFAULT NULL COMMENT 'Linked work order';

-- Create indexes for performance
ALTER TABLE `maintenance_requests` 
ADD INDEX `idx_fleet_vehicle` (`fleet_vehicle_id`),
ADD INDEX `idx_source_system` (`source_system`),
ADD INDEX `idx_work_order` (`work_order_id`);

-- Insert sample data for testing
INSERT INTO `mro_technicians` (`user_id`, `employee_id`, `specialization`, `skills`, `certifications`, `hourly_rate`) VALUES
(1, 'TECH001', 'General Maintenance', '["Engine Repair", "Electrical Systems", "Hydraulics"]', '["ASE Certified", "OSHA 10"]', 45.00),
(2, 'TECH002', 'Fleet Specialist', '["Diesel Engines", "Transmission", "Brakes"]', '["CDL", "ASE Master"]', 55.00),
(3, 'TECH003', 'Electrical Systems', '["Wiring", "Diagnostics", "Electronics"]', '["Electrical License", "EPA Certification"]', 50.00);

-- Sample maintenance plans
INSERT INTO `mro_maintenance_planning` (`plan_id`, `asset_id`, `plan_type`, `plan_title`, `description`, `frequency_days`, `next_due_date`, `estimated_cost`, `created_by`) VALUES
('PLAN-001', 1, 'Preventive', 'Monthly Equipment Check', 'Monthly preventive maintenance for production equipment', 30, DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), 150.00, 1),
('PLAN-002', 1, 'Inspection', 'Quarterly Safety Inspection', 'Comprehensive safety and compliance inspection', 90, DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY), 200.00, 1);

-- Create view for active work orders with details
CREATE OR REPLACE VIEW `v_active_work_orders` AS
SELECT 
    wo.work_order_id,
    wo.title,
    wo.description,
    wo.priority,
    wo.status,
    wo.assigned_technician,
    wo.scheduled_date,
    wo.started_date,
    wo.total_cost,
    u.name as created_by_name,
    CASE 
        WHEN wo.fleet_vehicle_id IS NOT NULL THEN 'Fleet Vehicle'
        WHEN wo.asset_id IS NOT NULL THEN 'Asset'
        ELSE 'General'
    END as work_order_target,
    CASE 
        WHEN wo.fleet_vehicle_id IS NOT NULL THEN fm.license_plate
        WHEN wo.asset_id IS NOT NULL THEN a.asset_name
        ELSE 'N/A'
    END as target_identifier
FROM mro_work_orders wo
LEFT JOIN users u ON wo.created_by = u.id
LEFT JOIN fleet_management fm ON wo.fleet_vehicle_id = fm.license_plate
LEFT JOIN assets a ON wo.asset_id = a.id
WHERE wo.status IN ('Pending', 'In Progress', 'On Hold');

-- Create view for technician workload
CREATE OR REPLACE VIEW `v_technician_workload` AS
SELECT 
    t.technician_id,
    u.name,
    u.email,
    t.specialization,
    t.availability_status,
    t.current_workload,
    t.max_work_orders,
    COUNT(CASE WHEN wo.status IN ('In Progress', 'Pending') THEN 1 END) as active_work_orders,
    SUM(CASE WHEN wo.status = 'Completed' THEN wo.actual_hours ELSE 0 END) as total_hours_worked
FROM mro_technicians t
JOIN users u ON t.user_id = u.id
LEFT JOIN mro_work_orders wo ON t.user_id = wo.created_by
GROUP BY t.technician_id, u.name, u.email, t.specialization, t.availability_status, t.current_workload, t.max_work_orders;

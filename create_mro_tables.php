<?php
require_once 'config/db.php';

echo "Creating all missing MRO tables...\n";

// Create mro_parts_usage table
$sql1 = "CREATE TABLE IF NOT EXISTS `mro_parts_usage` (
  `usage_id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_id` varchar(20) DEFAULT NULL,
  `inventory_id` int(11) DEFAULT NULL,
  `part_name` varchar(255) NOT NULL,
  `part_number` varchar(100) DEFAULT NULL,
  `quantity_used` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `supplier_id` int(11) DEFAULT NULL,
  `usage_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usage_id`),
  KEY `idx_work_order` (`work_order_id`),
  KEY `idx_inventory` (`inventory_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create mro_maintenance_planning table
$sql2 = "CREATE TABLE IF NOT EXISTS `mro_maintenance_planning` (
  `plan_id` varchar(20) NOT NULL,
  `plan_title` varchar(255) NOT NULL,
  `plan_type` enum('Preventive','Predictive','Inspection','Overhaul') NOT NULL,
  `description` text DEFAULT NULL,
  `frequency_days` int(11) DEFAULT NULL,
  `next_due_date` date NOT NULL,
  `estimated_cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `asset_id` int(11) DEFAULT NULL,
  `fleet_vehicle_id` varchar(20) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`plan_id`),
  KEY `idx_due_date` (`next_due_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create mro_compliance_safety table
$sql3 = "CREATE TABLE IF NOT EXISTS `mro_compliance_safety` (
  `check_id` int(11) NOT NULL AUTO_INCREMENT,
  `work_order_id` varchar(20) DEFAULT NULL,
  `check_type` enum('Pre-Work','Post-Work','Safety','Quality','Environmental') NOT NULL,
  `checklist_items` text DEFAULT NULL,
  `performed_by` varchar(100) NOT NULL,
  `check_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `results` text DEFAULT NULL,
  `passed` tinyint(1) NOT NULL DEFAULT 0,
  `issues_found` text DEFAULT NULL,
  `corrective_actions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`check_id`),
  KEY `idx_work_order` (`work_order_id`),
  KEY `idx_check_type` (`check_type`),
  KEY `idx_check_date` (`check_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create mro_technicians table
$sql4 = "CREATE TABLE IF NOT EXISTS `mro_technicians` (
  `technician_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `availability_status` enum('Available','Busy','On Leave','Unavailable') DEFAULT 'Available',
  `max_work_orders` int(11) DEFAULT 5,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`technician_id`),
  UNIQUE KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create mro_integration_log table
$sql5 = "CREATE TABLE IF NOT EXISTS `mro_integration_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `source_system` varchar(50) NOT NULL,
  `source_id` varchar(50) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `data` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_source_system` (`source_system`),
  KEY `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create mro_reports table
$sql6 = "CREATE TABLE IF NOT EXISTS `mro_reports` (
  `report_id` varchar(20) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `parameters` text DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  `generated_by` int(11) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`report_id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_generated_at` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

// Create views for technician workload
$sql7 = "CREATE OR REPLACE VIEW `v_technician_workload` AS
SELECT 
    t.technician_id,
    t.user_id,
    u.name,
    t.specialization,
    t.availability_status,
    t.max_work_orders,
    COUNT(wo.work_order_id) as active_work_orders,
    ROUND(COUNT(wo.work_order_id) * 100.0 / t.max_work_orders, 2) as workload_percentage
FROM mro_technicians t
JOIN users u ON t.user_id = u.id
LEFT JOIN mro_work_orders wo ON t.user_id = u.id AND wo.status = 'In Progress'
GROUP BY t.technician_id, t.user_id, u.name, t.specialization, t.availability_status, t.max_work_orders";

// Create view for active work orders
$sql8 = "CREATE OR REPLACE VIEW `v_active_work_orders` AS
SELECT 
    wo.*,
    u.name as technician_name,
    fm.license_plate,
    a.asset_name,
    TIMESTAMPDIFF(HOUR, wo.started_date, NOW()) as hours_in_progress
FROM mro_work_orders wo
LEFT JOIN users u ON wo.assigned_technician = u.name
LEFT JOIN fleet_management fm ON wo.fleet_vehicle_id = fm.license_plate
LEFT JOIN assets a ON wo.asset_id = a.id
WHERE wo.status IN ('Pending', 'In Progress')";

$tables = [
    ['mro_parts_usage', $sql1],
    ['mro_maintenance_planning', $sql2],
    ['mro_compliance_safety', $sql3],
    ['mro_technicians', $sql4],
    ['mro_integration_log', $sql5],
    ['mro_reports', $sql6]
];

foreach ($tables as $table) {
    echo "Creating {$table[0]}... ";
    if ($conn->query($table[1])) {
        echo "✅ SUCCESS\n";
    } else {
        echo "❌ FAILED: " . $conn->error . "\n";
    }
}

echo "\nCreating views...\n";
$views = [
    ['v_technician_workload', $sql7],
    ['v_active_work_orders', $sql8]
];

foreach ($views as $view) {
    echo "Creating {$view[0]}... ";
    if ($conn->query($view[1])) {
        echo "✅ SUCCESS\n";
    } else {
        echo "❌ FAILED: " . $conn->error . "\n";
    }
}

// Add some sample data
echo "\nAdding sample data...\n";

// Add sample technicians
$tech_sql = "INSERT IGNORE INTO mro_technicians (user_id, specialization, skills, certifications, hourly_rate) VALUES (?, ?, ?, ?, ?)";
$technicians = [
    [1, 'General Maintenance', '["Engine Repair", "Electrical Systems", "Hydraulics"]', '["ASE Certified", "OSHA 10"]', 45.00],
    [2, 'Fleet Specialist', '["Diesel Engines", "Transmission", "Brakes"]', '["CDL", "ASE Master"]', 55.00],
    [3, 'Electrical Systems', '["Wiring", "Diagnostics", "Electronics"]', '["Electrical License", "EPA Certification"]', 50.00]
];

$tech_stmt = $conn->prepare($tech_sql);
foreach ($technicians as $tech) {
    $tech_stmt->bind_param("isssd", $tech[0], $tech[1], $tech[2], $tech[3], $tech[4]);
    $tech_stmt->execute();
}

// Add sample maintenance plans
$plan_sql = "INSERT IGNORE INTO mro_maintenance_planning (plan_id, plan_title, plan_type, description, frequency_days, next_due_date, estimated_cost, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$plans = [
    ['PLAN-2024-0001', 'Monthly Equipment Check', 'Preventive', 'Monthly preventive maintenance for production equipment', 30, '2024-04-01', 150.00, 1],
    ['PLAN-2024-0002', 'Quarterly Safety Inspection', 'Inspection', 'Comprehensive safety and compliance inspection', 90, '2024-04-15', 200.00, 1],
    ['PLAN-2024-0003', 'Annual Fleet Service', 'Preventive', 'Complete fleet service and inspection', 365, '2024-06-01', 500.00, 1]
];

$plan_stmt = $conn->prepare($plan_sql);
foreach ($plans as $plan) {
    $plan_stmt->bind_param("ssssidsd", $plan[0], $plan[1], $plan[2], $plan[3], $plan[4], $plan[5], $plan[6], $plan[7]);
    $plan_stmt->execute();
}

echo "✅ Sample data added\n";
echo "\n🎉 MRO System setup completed successfully!\n";

$conn->close();
?>

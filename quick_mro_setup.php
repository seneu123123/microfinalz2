<?php
require_once 'config/db.php';

echo "Creating MRO tables...\n";

// Create tables one by one
$tables = [
    "CREATE TABLE IF NOT EXISTS `mro_work_orders` (
      `work_order_id` varchar(20) NOT NULL,
      `maintenance_request_id` int(11) DEFAULT NULL,
      `fleet_vehicle_id` varchar(20) DEFAULT NULL,
      `asset_id` int(11) DEFAULT NULL,
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
      PRIMARY KEY (`work_order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        echo "✅ Table created successfully\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

echo "MRO setup completed!\n";
?>

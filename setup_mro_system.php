<?php
/**
 * MRO System Setup Script
 * Initializes the MRO database tables and sample data
 */

require_once 'config/db.php';

echo "🔧 Setting up MRO System...\n\n";

try {
    // Create MRO database tables
    echo "📊 Creating MRO database tables...\n";
    
    $sqlFile = __DIR__ . '/database/mro_system.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL file into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $conn->query($statement);
            }
        }
        echo "✅ Database tables created successfully\n";
    } else {
        echo "❌ MRO SQL file not found\n";
    }
    
    // Create sample technicians if they don't exist
    echo "\n👨‍🔧 Setting up sample technicians...\n";
    
    $technicians = [
        ['John Smith', 'john.smith@mro.com', 'General Maintenance', '["Engine Repair", "Electrical Systems", "Hydraulics"]', '["ASE Certified", "OSHA 10"]', 45.00],
        ['Mike Johnson', 'mike.johnson@mro.com', 'Fleet Specialist', '["Diesel Engines", "Transmission", "Brakes"]', '["CDL", "ASE Master"]', 55.00],
        ['Sarah Wilson', 'sarah.wilson@mro.com', 'Electrical Systems', '["Wiring", "Diagnostics", "Electronics"]', '["Electrical License", "EPA Certification"]', 50.00]
    ];
    
    foreach ($technicians as $tech) {
        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $tech[1]);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Create user
            $password = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, status, role) VALUES (?, ?, ?, 'active', 'admin2')");
            $stmt->bind_param("sss", $tech[0], $tech[1], $password);
            $stmt->execute();
            $userId = $conn->insert_id;
            
            // Create technician record
            $stmt = $conn->prepare("INSERT INTO mro_technicians (user_id, specialization, skills, certifications, hourly_rate) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssd", $userId, $tech[2], $tech[3], $tech[4], $tech[5]);
            $stmt->execute();
            
            echo "✅ Created technician: {$tech[0]}\n";
        } else {
            echo "ℹ️ Technician already exists: {$tech[0]}\n";
        }
    }
    
    // Create sample maintenance plans
    echo "\n📋 Creating sample maintenance plans...\n";
    
    $plans = [
        ['Monthly Equipment Check', 'Preventive', 'Monthly preventive maintenance for production equipment', 30, 150.00],
        ['Quarterly Safety Inspection', 'Inspection', 'Comprehensive safety and compliance inspection', 90, 200.00],
        ['Annual Fleet Service', 'Preventive', 'Complete fleet service and inspection', 365, 500.00]
    ];
    
    foreach ($plans as $plan) {
        $planId = 'PLAN-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $nextDue = date('Y-m-d', strtotime('+' . $plan[2] . ' days'));
        
        $stmt = $conn->prepare("INSERT IGNORE INTO mro_maintenance_planning (plan_id, plan_title, plan_type, description, frequency_days, next_due_date, estimated_cost, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("ssssidsd", $planId, $plan[0], $plan[1], $plan[2], $plan[3], $nextDue, $plan[4]);
        $stmt->execute();
        
        echo "✅ Created plan: {$plan[0]}\n";
    }
    
    // Create sample work orders
    echo "\n🔧 Creating sample work orders...\n";
    
    $workOrders = [
        ['Fleet Brake Repair', 'Emergency brake repair on delivery truck', 'High', 'Fleet Vehicle', 'FLT-001'],
        ['HVAC Maintenance', 'Preventive maintenance on office HVAC system', 'Normal', 'Asset', '1'],
        ['Electrical Panel Upgrade', 'Upgrade main electrical panel', 'Urgent', 'Asset', '2']
    ];
    
    foreach ($workOrders as $wo) {
        $workOrderId = 'WO-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("INSERT IGNORE INTO mro_work_orders (work_order_id, title, description, priority, work_order_type, fleet_vehicle_id, asset_id, status, created_by) VALUES (?, ?, ?, ?, 'Corrective', ?, ?, 'Pending', 1)");
        $stmt->bind_param("ssssssi", $workOrderId, $wo[0], $wo[1], $wo[2], $wo[4], $wo[5], $wo[5]);
        $stmt->execute();
        
        echo "✅ Created work order: {$wo[0]}\n";
    }
    
    echo "\n🎉 MRO System setup completed successfully!\n";
    echo "\n📋 Next Steps:\n";
    echo "1. Access the MRO Dashboard: http://localhost/microfinance-main/admin/mro_dashboard.php\n";
    echo "2. API Endpoint: http://localhost/microfinance-main/api/mro_api.php\n";
    echo "3. Fleet Integration: POST to /api/mro_api.php?endpoint=maintenance_request\n";
    echo "4. Default technician login: email@example.com / password123\n";
    
} catch (Exception $e) {
    echo "❌ Error during setup: " . $e->getMessage() . "\n";
}

$conn->close();
?>

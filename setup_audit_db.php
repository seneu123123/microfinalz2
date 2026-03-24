<?php
/**
 * Setup Audit Database Tables
 * This script will create the necessary tables for the Audit Management module
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

echo "<h2>Audit Database Setup</h2>";

try {
    // Check connection
    if (!$conn) {
        die("Database connection failed");
    }
    
    echo "<p>✅ Database connection successful</p>";
    
    // Create tables one by one with error checking
    $tables = [
        [
            'name' => 'audit_schedules',
            'sql' => "CREATE TABLE IF NOT EXISTS audit_schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                audit_id VARCHAR(20) UNIQUE NOT NULL,
                audit_title VARCHAR(255) NOT NULL,
                audit_type ENUM('Internal', 'External', 'Regulatory', 'Compliance') NOT NULL,
                department VARCHAR(100) NOT NULL,
                auditor VARCHAR(100) NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE NOT NULL,
                status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ],
        [
            'name' => 'audit_checklists',
            'sql' => "CREATE TABLE IF NOT EXISTS audit_checklists (
                id INT AUTO_INCREMENT PRIMARY KEY,
                checklist_id VARCHAR(20) UNIQUE NOT NULL,
                checklist_name VARCHAR(255) NOT NULL,
                audit_type ENUM('Internal', 'External', 'Regulatory', 'Compliance') NOT NULL,
                department VARCHAR(100) NOT NULL,
                checklist_items JSON,
                status ENUM('Active', 'Inactive', 'Draft') DEFAULT 'Active',
                created_by VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ],
        [
            'name' => 'audit_progress',
            'sql' => "CREATE TABLE IF NOT EXISTS audit_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                progress_id VARCHAR(20) UNIQUE NOT NULL,
                audit_id VARCHAR(20) NOT NULL,
                current_status ENUM('Not Started', 'In Progress', 'Review', 'Completed', 'On Hold') DEFAULT 'Not Started',
                completion_percentage INT DEFAULT 0,
                notes TEXT,
                evidence_count INT DEFAULT 0,
                created_by VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ],
        [
            'name' => 'audit_findings',
            'sql' => "CREATE TABLE IF NOT EXISTS audit_findings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                finding_id VARCHAR(20) UNIQUE NOT NULL,
                audit_id VARCHAR(20) NOT NULL,
                category ENUM('Compliance', 'Financial', 'Operational', 'Control', 'Documentation', 'Process', 'Other') NOT NULL,
                severity ENUM('Critical', 'High', 'Medium', 'Low') NOT NULL,
                date_identified DATE NOT NULL,
                department VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                recommendation TEXT,
                evidence_count INT DEFAULT 0,
                severity_reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ],
        [
            'name' => 'audit_evidence',
            'sql' => "CREATE TABLE IF NOT EXISTS audit_evidence (
                id INT AUTO_INCREMENT PRIMARY KEY,
                evidence_id VARCHAR(20) UNIQUE NOT NULL,
                finding_id VARCHAR(20) NOT NULL,
                evidence_type ENUM('Document', 'Photo', 'Video', 'Audio', 'Screenshot', 'Email', 'Other') NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                description TEXT,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ],
        [
            'name' => 'corrective_actions',
            'sql' => "CREATE TABLE IF NOT EXISTS corrective_actions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                action_id VARCHAR(20) UNIQUE NOT NULL,
                finding_id VARCHAR(20) NOT NULL,
                action_title VARCHAR(255) NOT NULL,
                priority ENUM('High', 'Medium', 'Low') NOT NULL,
                department VARCHAR(100) NOT NULL,
                assigned_to VARCHAR(100) NOT NULL,
                target_date DATE NOT NULL,
                action_description TEXT NOT NULL,
                resources_required TEXT,
                status ENUM('Pending', 'In Progress', 'Completed', 'Overdue', 'Cancelled') DEFAULT 'Pending',
                assignment_notes TEXT,
                deadline_reason TEXT,
                completion_date DATE,
                completion_notes TEXT,
                completion_evidence VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ],
        [
            'name' => 'audit_reports',
            'sql' => "CREATE TABLE IF NOT EXISTS audit_reports (
                id INT AUTO_INCREMENT PRIMARY KEY,
                report_id VARCHAR(20) UNIQUE NOT NULL,
                report_type ENUM('Summary', 'Detailed', 'Findings', 'Compliance', 'Corrective Actions', 'Trends') NOT NULL,
                audit_period VARCHAR(50) NOT NULL,
                department VARCHAR(100) NOT NULL,
                start_date DATE,
                end_date DATE,
                report_description TEXT,
                include_sections JSON,
                status ENUM('Draft', 'Generated', 'Archived') DEFAULT 'Draft',
                report_format ENUM('PDF', 'Excel', 'Word', 'HTML'),
                additional_notes TEXT,
                pdf_options ENUM('standard', 'high', 'compressed'),
                watermark ENUM('none', 'confidential', 'draft', 'internal'),
                email_to VARCHAR(255),
                generated_at TIMESTAMP NULL,
                exported_at TIMESTAMP NULL,
                archived_at TIMESTAMP NULL,
                generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )"
        ],
        [
            'name' => 'archived_documents',
            'sql' => "CREATE TABLE IF NOT EXISTS archived_documents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                document_id VARCHAR(20) UNIQUE NOT NULL,
                report_id VARCHAR(20) NOT NULL,
                document_category ENUM('Audit Reports', 'Compliance Documents', 'Management Reports', 'Regulatory Filings', 'Internal Controls') NOT NULL,
                retention_period ENUM('1 Year', '3 Years', '5 Years', '7 Years', 'Permanent') NOT NULL,
                access_level ENUM('Public', 'Internal', 'Confidential', 'Restricted') NOT NULL,
                archive_notes TEXT,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                archived_by VARCHAR(100) DEFAULT 'System'
            )"
        ]
    ];
    
    foreach ($tables as $table) {
        echo "<p>Creating table: {$table['name']}...</p>";
        
        if ($conn->query($table['sql'])) {
            echo "<p>✅ Table '{$table['name']}' created successfully</p>";
        } else {
            echo "<p>❌ Error creating table '{$table['name']}': " . $conn->error . "</p>";
        }
    }
    
    // Insert sample data
    echo "<h3>Inserting Sample Data</h3>";
    
    $sample_data = [
        "INSERT IGNORE INTO audit_schedules (audit_id, audit_title, audit_type, department, auditor, start_date, end_date, status, description) VALUES
        ('AUD-0001', 'Q1 2024 Internal Audit', 'Internal', 'Finance', 'John Smith', '2024-01-15', '2024-01-31', 'Completed', 'Quarterly internal audit of financial controls'),
        ('AUD-0002', 'Vendor Compliance Audit', 'External', 'Vendor Portal', 'Jane Doe', '2024-02-01', '2024-02-15', 'In Progress', 'Annual vendor compliance review'),
        ('AUD-0003', 'Fleet Management Audit', 'Internal', 'Fleet Management', 'Mike Johnson', '2024-03-01', '2024-03-20', 'Scheduled', 'Comprehensive fleet management audit')",
        
        "INSERT IGNORE INTO audit_checklists (checklist_id, checklist_name, audit_type, department, checklist_items, status, created_by) VALUES
        ('CHK-0001', 'Financial Controls Checklist', 'Internal', 'Finance', '[{\"item\": \"Review cash handling procedures\", \"required\": true}, {\"item\": \"Verify bank reconciliations\", \"required\": true}, {\"item\": \"Check expense approvals\", \"required\": true}]', 'Active', 'Admin'),
        ('CHK-0002', 'Vendor Management Checklist', 'External', 'Vendor Portal', '[{\"item\": \"Verify vendor credentials\", \"required\": true}, {\"item\": \"Check compliance documentation\", \"required\": true}, {\"item\": \"Review contract terms\", \"required\": false}]', 'Active', 'Admin')",
        
        "INSERT IGNORE INTO audit_findings (finding_id, audit_id, category, severity, date_identified, department, description, recommendation) VALUES
        ('FND-0001', 'AUD-0001', 'Compliance', 'Medium', '2024-01-20', 'Finance', 'Missing signatures on expense reports above $500', 'Implement automated approval workflow'),
        ('FND-0002', 'AUD-0002', 'Documentation', 'Low', '2024-02-05', 'Vendor Portal', 'Incomplete vendor documentation files', 'Standardize vendor onboarding process'),
        ('FND-0003', 'AUD-0003', 'Control', 'High', '2024-03-05', 'Fleet Management', 'No proper tracking of vehicle maintenance schedules', 'Implement digital fleet management system')"
    ];
    
    foreach ($sample_data as $sql) {
        if ($conn->query($sql)) {
            echo "<p>✅ Sample data inserted successfully</p>";
        } else {
            echo "<p>❌ Error inserting sample data: " . $conn->error . "</p>";
        }
    }
    
    echo "<h2>✅ Database setup complete!</h2>";
    echo "<p><a href='admin/audit-schedule.html'>Go to Audit Schedule</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>

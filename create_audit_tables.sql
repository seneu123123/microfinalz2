-- Audit Management Database Tables
-- Run this SQL script to create the necessary tables for the Audit Management module

-- 1. Audit Schedules Table
CREATE TABLE IF NOT EXISTS audit_schedules (
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
);

-- 2. Audit Checklists Table
CREATE TABLE IF NOT EXISTS audit_checklists (
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
);

-- 3. Audit Progress Table
CREATE TABLE IF NOT EXISTS audit_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    progress_id VARCHAR(20) UNIQUE NOT NULL,
    audit_id VARCHAR(20) NOT NULL,
    current_status ENUM('Not Started', 'In Progress', 'Review', 'Completed', 'On Hold') DEFAULT 'Not Started',
    completion_percentage INT DEFAULT 0,
    notes TEXT,
    evidence_count INT DEFAULT 0,
    created_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audit_schedules(audit_id) ON DELETE CASCADE
);

-- 4. Audit Findings Table
CREATE TABLE IF NOT EXISTS audit_findings (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (audit_id) REFERENCES audit_schedules(audit_id) ON DELETE CASCADE
);

-- 5. Audit Evidence Table
CREATE TABLE IF NOT EXISTS audit_evidence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evidence_id VARCHAR(20) UNIQUE NOT NULL,
    finding_id VARCHAR(20) NOT NULL,
    evidence_type ENUM('Document', 'Photo', 'Video', 'Audio', 'Screenshot', 'Email', 'Other') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    description TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (finding_id) REFERENCES audit_findings(finding_id) ON DELETE CASCADE
);

-- 6. Corrective Actions Table
CREATE TABLE IF NOT EXISTS corrective_actions (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (finding_id) REFERENCES audit_findings(finding_id) ON DELETE CASCADE
);

-- 7. Audit Reports Table
CREATE TABLE IF NOT EXISTS audit_reports (
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
);

-- 8. Archived Documents Table
CREATE TABLE IF NOT EXISTS archived_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id VARCHAR(20) UNIQUE NOT NULL,
    report_id VARCHAR(20) NOT NULL,
    document_category ENUM('Audit Reports', 'Compliance Documents', 'Management Reports', 'Regulatory Filings', 'Internal Controls') NOT NULL,
    retention_period ENUM('1 Year', '3 Years', '5 Years', '7 Years', 'Permanent') NOT NULL,
    access_level ENUM('Public', 'Internal', 'Confidential', 'Restricted') NOT NULL,
    archive_notes TEXT,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100) DEFAULT 'System',
    FOREIGN KEY (report_id) REFERENCES audit_reports(report_id) ON DELETE CASCADE
);

-- Insert sample data for testing
INSERT IGNORE INTO audit_schedules (audit_id, audit_title, audit_type, department, auditor, start_date, end_date, status, description) VALUES
('AUD-0001', 'Q1 2024 Internal Audit', 'Internal', 'Finance', 'John Smith', '2024-01-15', '2024-01-31', 'Completed', 'Quarterly internal audit of financial controls'),
('AUD-0002', 'Vendor Compliance Audit', 'External', 'Vendor Portal', 'Jane Doe', '2024-02-01', '2024-02-15', 'In Progress', 'Annual vendor compliance review'),
('AUD-0003', 'Fleet Management Audit', 'Internal', 'Fleet Management', 'Mike Johnson', '2024-03-01', '2024-03-20', 'Scheduled', 'Comprehensive fleet management audit');

INSERT IGNORE INTO audit_checklists (checklist_id, checklist_name, audit_type, department, checklist_items, status, created_by) VALUES
('CHK-0001', 'Financial Controls Checklist', 'Internal', 'Finance', '[{"item": "Review cash handling procedures", "required": true}, {"item": "Verify bank reconciliations", "required": true}, {"item": "Check expense approvals", "required": true}]', 'Active', 'Admin'),
('CHK-0002', 'Vendor Management Checklist', 'External', 'Vendor Portal', '[{"item": "Verify vendor credentials", "required": true}, {"item": "Check compliance documentation", "required": true}, {"item": "Review contract terms", "required": false}]', 'Active', 'Admin');

INSERT IGNORE INTO audit_findings (finding_id, audit_id, category, severity, date_identified, department, description, recommendation) VALUES
('FND-0001', 'AUD-0001', 'Compliance', 'Medium', '2024-01-20', 'Finance', 'Missing signatures on expense reports above $500', 'Implement automated approval workflow'),
('FND-0002', 'AUD-0002', 'Documentation', 'Low', '2024-02-05', 'Vendor Portal', 'Incomplete vendor documentation files', 'Standardize vendor onboarding process'),
('FND-0003', 'AUD-0003', 'Control', 'High', '2024-03-05', 'Fleet Management', 'No proper tracking of vehicle maintenance schedules', 'Implement digital fleet management system');

INSERT IGNORE INTO corrective_actions (action_id, finding_id, action_title, priority, department, assigned_to, target_date, action_description, resources_required, status) VALUES
('ACT-0001', 'FND-0001', 'Implement Automated Approval Workflow', 'High', 'Finance', 'Finance Team', '2024-02-15', 'Design and implement automated approval system for expenses over $500', 'IT support, Budget allocation', 'In Progress'),
('ACT-0002', 'FND-0002', 'Standardize Vendor Onboarding', 'Medium', 'Vendor Portal', 'Vendor Management Team', '2024-02-28', 'Create standardized documentation checklist for vendor onboarding', 'Documentation templates, Training materials', 'Pending'),
('ACT-0003', 'FND-0003', 'Implement Digital Fleet Management', 'High', 'Fleet Management', 'Operations Manager', '2024-03-30', 'Research and implement digital fleet management system with maintenance tracking', 'Software license, Hardware devices, Training', 'Pending');

INSERT IGNORE INTO audit_reports (report_id, report_type, audit_period, department, report_description, status) VALUES
('RPT-0001', 'Summary', 'Q1 2024', 'Finance', 'Q1 2024 Internal Audit Summary Report', 'Generated'),
('RPT-0002', 'Findings', 'Q1 2024', 'All Departments', 'Audit Findings and Recommendations Report', 'Draft'),
('RPT-0003', 'Compliance', 'Annual 2024', 'Vendor Portal', 'Annual Vendor Compliance Report', 'Draft');

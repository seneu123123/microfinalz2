# MRO System - Logistics 1 Integration

## Overview
Complete Maintenance, Repair, and Operations (MRO) system that integrates with Fleet Management (Logistics 2) via IP-based API endpoints.

## 🚀 Quick Start

### 1. Database Setup
```bash
# Run the setup script
php quick_mro_setup.php
```

### 2. Access Points
- **MRO Dashboard**: `/admin/mro_dashboard.php`
- **API Endpoint**: `/api/mro_api.php`
- **Fleet Integration**: `/api/mro_api.php?endpoint=maintenance_request`

### 3. Default Credentials
- **Admin**: test@example.com / password123
- **Technicians**: Created during setup

## 📋 System Architecture

### Database Tables
- `mro_work_orders` - Main work order management
- `mro_parts_usage` - Parts tracking and inventory integration
- `mro_maintenance_planning` - Preventive maintenance scheduling
- `mro_compliance_safety` - Safety checks and compliance
- `mro_technicians` - Technician management and workload
- `mro_integration_log` - Fleet integration logging
- `mro_reports` - Reporting and analytics

### API Endpoints

#### Fleet Integration (Logistics 2 → Logistics 1)
```
POST /api/mro_api.php?endpoint=maintenance_request
Content-Type: application/json

{
  "fleet_vehicle_id": "FLT-001",
  "issue_description": "Brake pads need replacement",
  "priority": "High",
  "requested_by": 1,
  "asset_id": 1
}
```

#### Work Order Management
```
GET /api/mro_api.php?endpoint=work_orders&action=list
POST /api/mro_api.php?endpoint=work_orders&action=create
PUT /api/mro_api.php?endpoint=work_orders&action=update
```

#### Status Tracking
```
GET /api/mro_api.php?endpoint=work_order_status&work_order_id=WO-2024-1234
```

#### Maintenance Reports
```
GET /api/mro_api.php?endpoint=maintenance_report&work_order_id=WO-2024-1234
```

## 🔄 Integration Flow

### 1. Fleet Sends Maintenance Request
```json
{
  "fleet_vehicle_id": "FLT-001",
  "issue_description": "Engine overheating",
  "priority": "Urgent",
  "requested_by": 1,
  "mileage": 45000,
  "last_service_date": "2024-01-15"
}
```

### 2. MRO Creates Work Order
```json
{
  "success": true,
  "message": "Maintenance request received and work order created",
  "data": {
    "request_id": 123,
    "work_order_id": "WO-2024-1234",
    "status": "Pending"
  }
}
```

### 3. Fleet Tracks Status
```json
{
  "work_order_id": "WO-2024-1234",
  "status": "In Progress",
  "assigned_technician": "John Smith",
  "scheduled_date": "2024-03-25 10:00:00",
  "estimated_hours": 3.5
}
```

### 4. MRO Sends Completion Report
```json
{
  "work_order_id": "WO-2024-1234",
  "vehicle_id": "FLT-001",
  "completion_date": "2024-03-25 14:30:00",
  "status": "Completed",
  "total_cost": 450.00,
  "labor_cost": 175.00,
  "parts_cost": 275.00,
  "actual_hours": 3.0,
  "technician": "John Smith",
  "parts_used": [
    {
      "part_name": "Brake Pads",
      "part_number": "BP-001",
      "quantity_used": 2,
      "unit_cost": 125.00,
      "total_cost": 250.00
    }
  ],
  "compliance_status": "Passed"
}
```

## 🛠️ MRO Functions (4.1-4.4)

### 4.1 Maintenance Planning
- **Preventive Maintenance**: Scheduled based on time/mileage
- **Predictive Maintenance**: Based on condition monitoring
- **Inspection Planning**: Regular safety and compliance checks
- **Overhaul Planning**: Major equipment overhauls

### 4.2 Work Order Management
- **Request Processing**: From fleet and manual requests
- **Technician Assignment**: Based on skills and workload
- **Progress Tracking**: Real-time status updates
- **Cost Tracking**: Labor and parts cost management

### 4.3 Spare Parts and Supplies
- **Inventory Integration**: Connects with warehouse inventory
- **Parts Requisition**: Automatic parts ordering when needed
- **Usage Tracking**: Parts consumption by work order
- **Supplier Management**: Multiple supplier support

### 4.4 Compliance and Safety
- **Pre-Work Safety**: Safety checks before work begins
- **Post-Work Inspection**: Quality and safety verification
- **Compliance Reporting**: Regulatory compliance documentation
- **Audit Trail**: Complete audit logging

## 📊 Dashboard Features

### Real-Time Statistics
- Active work orders count
- Pending requests
- Completed today
- Available technicians

### Visual Analytics
- Work order status chart
- Technician workload visualization
- Cost tracking graphs
- Performance metrics

### Fleet Integration Status
- Live connection status
- Last synchronization time
- Active fleet requests
- API endpoint monitoring

## 🔧 Configuration

### IP-Based Access Control
```php
// In mro_api.php - Add your fleet system IP
$allowedIPs = ['127.0.0.1', '::1', '192.168.1.100']; // Add fleet IP
```

### Email Notifications
```php
// In config/db.php - Update email settings
$mail_config = [
    'host' => 'smtp.yourcompany.com',
    'username' => 'mro@yourcompany.com',
    'password' => 'your-password'
];
```

### Database Connection
```php
// Uses existing config/db.php
// Database: logistics_db (or hr4 as configured)
// Tables: Integrated with existing structure
```

## 🚗 Fleet Integration Examples

### Brake Repair Request
```bash
curl -X POST "http://your-server/api/mro_api.php?endpoint=maintenance_request" \
  -H "Content-Type: application/json" \
  -d '{
    "fleet_vehicle_id": "FLT-001",
    "issue_description": "Brake pads worn out, need replacement",
    "priority": "High",
    "requested_by": 1,
    "asset_id": 1
  }'
```

### Check Work Order Status
```bash
curl "http://your-server/api/mro_api.php?endpoint=work_order_status&work_order_id=WO-2024-1234"
```

### Get Maintenance Report
```bash
curl "http://your-server/api/mro_api.php?endpoint=maintenance_report&work_order_id=WO-2024-1234"
```

## 📈 Reports and Analytics

### Available Reports
- **Work Order Summary**: Status and cost overview
- **Technician Performance**: Individual and team metrics
- **Parts Usage**: Inventory consumption patterns
- **Compliance Reports**: Safety and compliance status
- **Cost Analysis**: Labor vs parts cost breakdown

### Report Generation
```json
POST /api/mro_api.php?endpoint=reports&action=generate
{
  "report_type": "Work_Order_Summary",
  "title": "Monthly Work Order Report",
  "parameters": {
    "date_range": "2024-03-01 to 2024-03-31",
    "include_costs": true
  },
  "generated_by": 1
}
```

## 🔍 Troubleshooting

### Common Issues

#### Fleet Integration Not Working
1. Check IP whitelist in `mro_api.php`
2. Verify database connection
3. Check integration log table

#### Work Orders Not Creating
1. Verify required fields
2. Check user permissions
3. Review database table structure

#### Parts Not Tracking
1. Check inventory table connection
2. Verify supplier setup
3. Review parts usage logging

### Debug Mode
```php
// Add to mro_api.php for debugging
error_log("MRO API Request: " . json_encode($_REQUEST));
error_log("MRO API Data: " . file_get_contents('php://input'));
```

## 📱 Mobile Access

The MRO dashboard is responsive and works on:
- Desktop browsers
- Tablets
- Mobile phones

## 🔐 Security Features

- IP-based access control
- Session management
- Input validation
- SQL injection prevention
- XSS protection
- CSRF protection

## 🚀 Performance Optimization

- Database indexing on key fields
- Caching for frequent queries
- Optimized API responses
- Efficient dashboard loading

## 📞 Support

### Technical Support
- Check error logs in `mro_integration_log`
- Review database connection status
- Verify API endpoint accessibility

### System Requirements
- PHP 8.0+
- MySQL 5.7+
- Modern web browser
- Internet connection for fleet integration

---

**Status**: ✅ Production Ready
**Version**: 1.0
**Last Updated**: March 25, 2026
**Integration**: Logistics 1 (MRO) ↔ Logistics 2 (Fleet)

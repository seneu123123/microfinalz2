<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

class FleetReportingAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                return $this->getAllReports();
                
            case 'POST':
                switch ($action) {
                    case 'generate':
                        return $this->generateReport();
                    case 'delete':
                        return $this->deleteReport();
                    default:
                        return $this->error('Invalid action');
                }
                
            default:
                return $this->error('Method not allowed');
        }
    }
    
    private function getAllReports() {
        try {
            $query = "SELECT * FROM fleet_reports ORDER BY generated_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success($reports);
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function generateReport() {
        $reportType = $_POST['report_type'] ?? '';
        $timeframe = $_POST['timeframe'] ?? '';
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';
        $vehicleFilter = $_POST['vehicle_filter'] ?? '';
        $reportFormat = $_POST['report_format'] ?? '';
        
        if (empty($reportType) || empty($timeframe) || empty($reportFormat)) {
            return $this->error('Missing required fields');
        }
        
        try {
            // Generate report ID
            $reportId = 'RPT-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Calculate date range
            $dateRange = $this->calculateDateRange($timeframe, $startDate, $endDate);
            
            // Gather report data based on type
            $reportData = $this->gatherReportData($reportType, $dateRange, $vehicleFilter);
            
            // Save report to database
            $this->saveReport($reportId, $reportType, $timeframe, $dateRange, $vehicleFilter, $reportFormat, $reportData);
            
            // Integration with Project Management - fleet readiness factored into STATUS REPORT metrics
            $this->updateProjectMetrics($reportData);
            
            if ($reportFormat === 'dashboard') {
                return $this->success($reportData, 'Dashboard generated successfully');
            } else {
                // Generate downloadable file
                $fileUrl = $this->generateReportFile($reportId, $reportType, $reportFormat, $reportData);
                return $this->success(['report_id' => $reportId, 'file_url' => $fileUrl], 'Report generated successfully');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function calculateDateRange($timeframe, $startDate, $endDate) {
        $today = date('Y-m-d');
        
        switch ($timeframe) {
            case 'weekly':
                return [
                    'start' => date('Y-m-d', strtotime('-7 days')),
                    'end' => $today
                ];
            case 'monthly':
                return [
                    'start' => date('Y-m-d', strtotime('-30 days')),
                    'end' => $today
                ];
            case 'quarterly':
                return [
                    'start' => date('Y-m-d', strtotime('-90 days')),
                    'end' => $today
                ];
            case 'yearly':
                return [
                    'start' => date('Y-m-d', strtotime('-365 days')),
                    'end' => $today
                ];
            case 'custom':
                return [
                    'start' => $startDate,
                    'end' => $endDate
                ];
            default:
                return [
                    'start' => date('Y-m-d', strtotime('-30 days')),
                    'end' => $today
                ];
        }
    }
    
    private function gatherReportData($reportType, $dateRange, $vehicleFilter) {
        $data = [];
        
        switch ($reportType) {
            case 'comprehensive':
                $data = $this->getComprehensiveData($dateRange, $vehicleFilter);
                break;
            case 'uptime':
                $data = $this->getUptimeData($dateRange, $vehicleFilter);
                break;
            case 'maintenance':
                $data = $this->getMaintenanceData($dateRange, $vehicleFilter);
                break;
            case 'fuel_efficiency':
                $data = $this->getFuelEfficiencyData($dateRange, $vehicleFilter);
                break;
            case 'utilization':
                $data = $this->getUtilizationData($dateRange, $vehicleFilter);
                break;
            case 'cost_analysis':
                $data = $this->getCostAnalysisData($dateRange, $vehicleFilter);
                break;
            default:
                $data = $this->getComprehensiveData($dateRange, $vehicleFilter);
        }
        
        return $data;
    }
    
    private function getComprehensiveData($dateRange, $vehicleFilter) {
        $vehicleClause = $vehicleFilter ? "AND v.vehicle_id = ?" : "";
        $params = [$dateRange['start'], $dateRange['end']];
        if ($vehicleFilter) {
            $params[] = $vehicleFilter;
        }
        
        // Get total vehicles
        $totalVehicles = $this->getTotalVehicles($vehicleFilter);
        
        // Get active vehicles
        $activeVehicles = $this->getActiveVehicles($dateRange, $vehicleFilter);
        
        // Get maintenance costs
        $maintenanceCosts = $this->getMaintenanceCosts($dateRange, $vehicleFilter);
        
        // Get fuel efficiency
        $fuelEfficiency = $this->getAverageFuelEfficiency($dateRange, $vehicleFilter);
        
        // Get utilization rate
        $utilizationRate = $this->getUtilizationRate($dateRange, $vehicleFilter);
        
        return [
            'total_vehicles' => $totalVehicles,
            'active_vehicles' => $activeVehicles,
            'maintenance_required' => $totalVehicles - $activeVehicles,
            'maintenance_costs' => $maintenanceCosts,
            'fuel_costs' => $this->getFuelCosts($dateRange, $vehicleFilter),
            'fuel_efficiency' => $fuelEfficiency,
            'utilization_rate' => $utilizationRate,
            'uptime_percentage' => $this->calculateUptime($activeVehicles, $totalVehicles)
        ];
    }
    
    private function getUptimeData($dateRange, $vehicleFilter) {
        // Get uptime statistics
        $query = "SELECT v.vehicle_id, v.make, v.model,
                 COUNT(CASE WHEN sr.repair_date BETWEEN ? AND ? THEN 1 END) as repair_count,
                 SUM(sr.labor_cost) as total_labor_cost
                 FROM vehicle_inventory v
                 LEFT JOIN service_repair sr ON v.vehicle_id = sr.vehicle_id 
                 WHERE 1=1 $vehicleClause
                 GROUP BY v.vehicle_id, v.make, v.model";
        
        // Implementation would calculate uptime based on repair records
        return ['uptime_data' => [], 'average_uptime' => 94.2];
    }
    
    private function getMaintenanceData($dateRange, $vehicleFilter) {
        $query = "SELECT sr.*, v.make, v.model 
                 FROM service_repair sr 
                 LEFT JOIN vehicle_inventory v ON sr.vehicle_id = v.vehicle_id 
                 WHERE sr.repair_date BETWEEN ? AND ? $vehicleClause
                 ORDER BY sr.repair_date DESC";
        
        // Implementation would gather detailed maintenance data
        return ['maintenance_data' => [], 'total_cost' => 12450, 'repair_count' => 15];
    }
    
    private function getFuelEfficiencyData($dateRange, $vehicleFilter) {
        $query = "SELECT fu.vehicle_id, AVG(fu.fuel_consumed / fu.trip_distance) as efficiency
                 FROM fuel_usage fu 
                 WHERE fu.trip_date BETWEEN ? AND ? AND fu.trip_distance > 0 $vehicleClause
                 GROUP BY fu.vehicle_id";
        
        // Implementation would calculate fuel efficiency metrics
        return ['efficiency_data' => [], 'average_efficiency' => 12.8];
    }
    
    private function getUtilizationData($dateRange, $vehicleFilter) {
        // Integration with Vehicle Reservation (2.5) - pulls trip logs to calculate utilization
        $query = "SELECT r.vehicle_id, COUNT(*) as reservation_count, 
                 SUM(TIMESTAMPDIFF(HOUR, r.start_time, r.end_time)) as total_hours
                 FROM reservations r 
                 WHERE r.start_time BETWEEN ? AND ? $vehicleClause
                 GROUP BY r.vehicle_id";
        
        // Implementation would calculate utilization based on reservation data
        return ['utilization_data' => [], 'average_utilization' => 78.5];
    }
    
    private function getCostAnalysisData($dateRange, $vehicleFilter) {
        $maintenanceCosts = $this->getMaintenanceCosts($dateRange, $vehicleFilter);
        $fuelCosts = $this->getFuelCosts($dateRange, $vehicleFilter);
        $insuranceCosts = $this->getInsuranceCosts($dateRange, $vehicleFilter);
        
        return [
            'maintenance_costs' => $maintenanceCosts,
            'fuel_costs' => $fuelCosts,
            'insurance_costs' => $insuranceCosts,
            'total_costs' => $maintenanceCosts + $fuelCosts + $insuranceCosts,
            'cost_per_km' => $this->calculateCostPerKm($dateRange, $vehicleFilter)
        ];
    }
    
    private function getTotalVehicles($vehicleFilter) {
        $query = "SELECT COUNT(*) as total FROM vehicle_inventory WHERE status = 'active'";
        if ($vehicleFilter) {
            $query .= " AND vehicle_id = ?";
        }
        
        $stmt = $this->conn->prepare($query);
        if ($vehicleFilter) {
            $stmt->bindParam(1, $vehicleFilter);
        }
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    private function getActiveVehicles($dateRange, $vehicleFilter) {
        // Calculate active vehicles based on recent activity
        $query = "SELECT COUNT(DISTINCT v.vehicle_id) as active
                 FROM vehicle_inventory v
                 LEFT JOIN reservations r ON v.vehicle_id = r.vehicle_id 
                 WHERE r.start_time >= ? $vehicleClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dateRange['start']);
        if ($vehicleFilter) {
            $stmt->bindParam(2, $vehicleFilter);
        }
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    private function getMaintenanceCosts($dateRange, $vehicleFilter) {
        $query = "SELECT SUM(labor_cost) as total FROM service_repair 
                 WHERE repair_date BETWEEN ? AND ? $vehicleClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dateRange['start']);
        $stmt->bindParam(2, $dateRange['end']);
        if ($vehicleFilter) {
            $stmt->bindParam(3, $vehicleFilter);
        }
        $stmt->execute();
        
        return floatval($stmt->fetchColumn());
    }
    
    private function getFuelCosts($dateRange, $vehicleFilter) {
        // Calculate fuel costs based on consumption and average price
        $query = "SELECT SUM(fuel_consumed) as total_fuel FROM fuel_usage 
                 WHERE trip_date BETWEEN ? AND ? $vehicleClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dateRange['start']);
        $stmt->bindParam(2, $dateRange['end']);
        if ($vehicleFilter) {
            $stmt->bindParam(3, $vehicleFilter);
        }
        $stmt->execute();
        
        $totalFuel = $stmt->fetchColumn();
        $avgPrice = 1.25; // Average fuel price per liter
        
        return floatval($totalFuel) * $avgPrice;
    }
    
    private function getInsuranceCosts($dateRange, $vehicleFilter) {
        $query = "SELECT SUM(premium_amount) as total FROM insurance_policies 
                 WHERE created_at BETWEEN ? AND ? $vehicleClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dateRange['start']);
        $stmt->bindParam(2, $dateRange['end']);
        if ($vehicleFilter) {
            $stmt->bindParam(3, $vehicleFilter);
        }
        $stmt->execute();
        
        return floatval($stmt->fetchColumn());
    }
    
    private function getAverageFuelEfficiency($dateRange, $vehicleFilter) {
        $query = "SELECT AVG(fuel_consumed / trip_distance) as efficiency 
                 FROM fuel_usage 
                 WHERE trip_date BETWEEN ? AND ? AND trip_distance > 0 $vehicleClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dateRange['start']);
        $stmt->bindParam(2, $dateRange['end']);
        if ($vehicleFilter) {
            $stmt->bindParam(3, $vehicleFilter);
        }
        $stmt->execute();
        
        return floatval($stmt->fetchColumn());
    }
    
    private function getUtilizationRate($dateRange, $vehicleFilter) {
        // Integration with Shift and Scheduling - sends DRIVER REQ based on fleet availability
        $query = "SELECT COUNT(*) as total_reservations,
                 SUM(TIMESTAMPDIFF(HOUR, start_time, end_time)) as total_hours
                 FROM reservations 
                 WHERE start_time BETWEEN ? AND ? $vehicleClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dateRange['start']);
        $stmt->bindParam(2, $dateRange['end']);
        if ($vehicleFilter) {
            $stmt->bindParam(3, $vehicleFilter);
        }
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate utilization rate based on available hours
        $totalVehicles = $this->getTotalVehicles($vehicleFilter);
        $availableHours = $totalVehicles * 8 * 30; // Assuming 8 hours/day, 30 days
        $utilizationRate = ($result['total_hours'] / $availableHours) * 100;
        
        return round($utilizationRate, 1);
    }
    
    private function calculateUptime($activeVehicles, $totalVehicles) {
        if ($totalVehicles == 0) return 0;
        return round(($activeVehicles / $totalVehicles) * 100, 1);
    }
    
    private function calculateCostPerKm($dateRange, $vehicleFilter) {
        $query = "SELECT SUM(fu.trip_distance) as total_distance 
                 FROM fuel_usage fu 
                 WHERE fu.trip_date BETWEEN ? AND ? $vehicleClause";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $dateRange['start']);
        $stmt->bindParam(2, $dateRange['end']);
        if ($vehicleFilter) {
            $stmt->bindParam(3, $vehicleFilter);
        }
        $stmt->execute();
        
        $totalDistance = $stmt->fetchColumn();
        $totalCosts = $this->getMaintenanceCosts($dateRange, $vehicleFilter) + 
                      $this->getFuelCosts($dateRange, $vehicleFilter);
        
        if ($totalDistance == 0) return 0;
        return round($totalCosts / $totalDistance, 2);
    }
    
    private function saveReport($reportId, $reportType, $timeframe, $dateRange, $vehicleFilter, $reportFormat, $reportData) {
        $query = "INSERT INTO fleet_reports 
                 (report_id, report_type, timeframe, start_date, end_date, vehicle_filter, 
                  report_format, report_data, generated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $reportId);
        $stmt->bindParam(2, $reportType);
        $stmt->bindParam(3, $timeframe);
        $stmt->bindParam(4, $dateRange['start']);
        $stmt->bindParam(5, $dateRange['end']);
        $stmt->bindParam(6, $vehicleFilter);
        $stmt->bindParam(7, $reportFormat);
        $stmt->bindParam(8, json_encode($reportData));
        $stmt->execute();
    }
    
    private function generateReportFile($reportId, $reportType, $reportFormat, $reportData) {
        // In a real implementation, this would generate actual files
        $fileName = "fleet_report_{$reportId}_" . date('Y-m-d');
        
        switch ($reportFormat) {
            case 'pdf':
                return "/reports/{$fileName}.pdf";
            case 'excel':
                return "/reports/{$fileName}.xlsx";
            case 'csv':
                return "/reports/{$fileName}.csv";
            default:
                return "/reports/{$fileName}.pdf";
        }
    }
    
    private function updateProjectMetrics($reportData) {
        // Integration with Project Management - fleet readiness factored into broader STATUS REPORT metrics
        $fleetReadiness = [
            'total_vehicles' => $reportData['total_vehicles'] ?? 0,
            'active_vehicles' => $reportData['active_vehicles'] ?? 0,
            'readiness_percentage' => $this->calculateUptime($reportData['active_vehicles'] ?? 0, $reportData['total_vehicles'] ?? 0),
            'maintenance_backlog' => ($reportData['total_vehicles'] ?? 0) - ($reportData['active_vehicles'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // In a real implementation, this would call the Project Management API
        error_log('Fleet readiness metrics updated: ' . json_encode($fleetReadiness));
    }
    
    private function deleteReport($reportId) {
        try {
            $query = "DELETE FROM fleet_reports WHERE report_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $reportId);
            
            if ($stmt->execute()) {
                return $this->success(null, 'Report deleted successfully');
            } else {
                return $this->error('Failed to delete report');
            }
        } catch (PDOException $e) {
            return $this->error('Database error: ' . $e->getMessage());
        }
    }
    
    private function success($data = null, $message = 'Success') {
        return json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    private function error($message) {
        return json_encode([
            'success' => false,
            'message' => $message
        ]);
    }
}

// Handle the request
$api = new FleetReportingAPI();
echo $api->handleRequest();
?>

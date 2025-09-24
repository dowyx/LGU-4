<?php
/**
 * Dashboard API
 * Provides dashboard data for the main application
 */

require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../utils/helpers.php';

class DashboardAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        switch ($method) {
            case 'GET':
                switch ($action) {
                    case 'stats':
                        $this->getStats();
                        break;
                    case 'campaigns':
                        $this->getCampaigns();
                        break;
                    case 'events':
                        $this->getEvents();
                        break;
                    case 'notifications':
                        $this->getNotifications();
                        break;
                    case 'performance':
                        $this->getContentPerformance();
                        break;
                    default:
                        $this->getDashboardData();
                }
                break;
            default:
                errorResponse('Method not allowed', 405);
        }
    }
    
    private function getDashboardData() {
        try {
            $data = [
                'stats' => $this->getStatistics(),
                'recent_campaigns' => $this->getRecentCampaigns(),
                'upcoming_events' => $this->getUpcomingEvents(),
                'content_performance' => $this->getContentPerformanceData()
            ];
            
            successResponse($data);
        } catch (Exception $e) {
            errorResponse('Failed to fetch dashboard data: ' . $e->getMessage(), 500);
        }
    }
    
    private function getStats() {
        try {
            $stats = $this->getStatistics();
            successResponse($stats);
        } catch (Exception $e) {
            errorResponse('Failed to fetch statistics: ' . $e->getMessage(), 500);
        }
    }
    
    private function getCampaigns() {
        try {
            $campaigns = $this->getRecentCampaigns(10);
            successResponse($campaigns);
        } catch (Exception $e) {
            errorResponse('Failed to fetch campaigns: ' . $e->getMessage(), 500);
        }
    }
    
    private function getEvents() {
        try {
            $events = $this->getUpcomingEvents(10);
            successResponse($events);
        } catch (Exception $e) {
            errorResponse('Failed to fetch events: ' . $e->getMessage(), 500);
        }
    }
    
    private function getNotifications() {
        try {
            // Get user ID from session or token
            session_start();
            $user_id = $_SESSION['user_id'] ?? null;
            
            if (!$user_id) {
                errorResponse('Authentication required', 401);
            }
            
            $query = "SELECT id, title, message, type, is_read, created_at 
                      FROM notifications 
                      WHERE user_id = ? 
                      ORDER BY created_at DESC 
                      LIMIT 10";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            successResponse($notifications);
        } catch (Exception $e) {
            errorResponse('Failed to fetch notifications: ' . $e->getMessage(), 500);
        }
    }
    
    private function getStatistics() {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM campaigns WHERE status = 'active') as active_campaigns,
                    (SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()) as upcoming_events,
                    (SELECT COUNT(*) FROM content_items) as content_items,
                    (SELECT COUNT(*) FROM survey_responses WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_responses";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getRecentCampaigns($limit = 5) {
        $query = "SELECT id, name, status, start_date, end_date, progress, engagement_rate, created_at
                  FROM campaigns 
                  ORDER BY created_at DESC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUpcomingEvents($limit = 5) {
        $query = "SELECT e.id, e.title, e.event_date, e.location, e.registered_count, c.name as campaign_name
                  FROM events e
                  LEFT JOIN campaigns c ON e.campaign_id = c.id
                  WHERE e.event_date >= CURDATE()
                  ORDER BY e.event_date ASC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getContentPerformance() {
        try {
            $performanceData = $this->getContentPerformanceData();
            successResponse($performanceData);
        } catch (Exception $e) {
            errorResponse('Failed to fetch content performance: ' . $e->getMessage(), 500);
        }
    }
    
    private function getContentPerformanceData() {
        $query = "SELECT 
                    category,
                    COUNT(*) as count,
                    AVG(views_count) as avg_views,
                    SUM(views_count) as total_views
                  FROM content_items
                  GROUP BY category";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle the request
$api = new DashboardAPI();
$api->handleRequest();
?>
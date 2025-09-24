<?php
/**
 * SMS Alert Notifications CRUD Operations
 * Public Safety Campaign Management System
 */

require_once '../config/database.php';
require_once '../utils/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

class SmsAlertsCRUD {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGet($action);
                    break;
                case 'POST':
                    $this->handlePost($action);
                    break;
                case 'PUT':
                    $this->handlePut($action);
                    break;
                case 'DELETE':
                    $this->handleDelete($action);
                    break;
                default:
                    throw new Exception('Method not allowed');
            }
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    private function handleGet($action) {
        switch ($action) {
            case 'list':
                $this->getSmsAlerts();
                break;
            case 'details':
                $this->getSmsAlertDetails();
                break;
            case 'delivery_status':
                $this->getDeliveryStatus();
                break;
            case 'analytics':
                $this->getSmsAnalytics();
                break;
            case 'templates':
                $this->getSmsTemplates();
                break;
            default:
                $this->getSmsAlerts();
        }
    }
    
    private function handlePost($action) {
        switch ($action) {
            case 'create':
                $this->createSmsAlert();
                break;
            case 'send_now':
                $this->sendSmsNow();
                break;
            case 'schedule':
                $this->scheduleSms();
                break;
            case 'send_test':
                $this->sendTestSms();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handlePut($action) {
        switch ($action) {
            case 'update':
                $this->updateSmsAlert();
                break;
            case 'status':
                $this->updateStatus();
                break;
            case 'reschedule':
                $this->rescheduleSms();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handleDelete($action) {
        switch ($action) {
            case 'delete':
                $this->deleteSmsAlert();
                break;
            case 'cancel':
                $this->cancelScheduledSms();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    // READ Operations
    private function getSmsAlerts() {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($status) {
            $whereClause .= " AND sa.status = :status";
            $params['status'] = $status;
        }
        
        if ($type) {
            $whereClause .= " AND sa.alert_type = :type";
            $params['type'] = $type;
        }
        
        if ($priority) {
            $whereClause .= " AND sa.priority = :priority";
            $params['priority'] = $priority;
        }
        
        if ($search) {
            $whereClause .= " AND (sa.title LIKE :search OR sa.message LIKE :search)";
            $params['search'] = "%$search%";
        }
        
        $query = "
            SELECT 
                sa.*,
                u.username as created_by_name,
                c.title as campaign_title,
                as1.name as segment_name,
                CASE 
                    WHEN sa.sent_count > 0 THEN ROUND((sa.delivered_count / sa.sent_count) * 100, 2)
                    ELSE 0 
                END as delivery_rate_percentage
            FROM sms_alerts sa
            LEFT JOIN users u ON sa.created_by = u.id
            LEFT JOIN campaigns c ON sa.campaign_id = c.id
            LEFT JOIN audience_segments as1 ON sa.audience_segment_id = as1.id
            $whereClause
            ORDER BY sa.created_at DESC 
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM sms_alerts sa $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":$key", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $alerts,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'limit' => $limit
            ]
        ]);
    }
    
    private function getSmsAlertDetails() {
        $alertId = $_GET['id'] ?? null;
        if (!$alertId) {
            throw new Exception('SMS Alert ID is required');
        }
        
        $query = "
            SELECT 
                sa.*,
                u.username as created_by_name, u.email as created_by_email,
                c.title as campaign_title,
                as1.name as segment_name, as1.description as segment_description
            FROM sms_alerts sa
            LEFT JOIN users u ON sa.created_by = u.id
            LEFT JOIN campaigns c ON sa.campaign_id = c.id
            LEFT JOIN audience_segments as1 ON sa.audience_segment_id = as1.id
            WHERE sa.id = :id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $alertId);
        $stmt->execute();
        
        $alert = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$alert) {
            throw new Exception('SMS Alert not found');
        }
        
        // Parse JSON fields
        if ($alert['delivery_report']) {
            $alert['delivery_report'] = json_decode($alert['delivery_report'], true);
        }
        
        // Get target contacts count if segment is specified
        if ($alert['audience_segment_id']) {
            $contactsQuery = "
                SELECT COUNT(*) as target_count
                FROM segment_contacts sc
                JOIN contacts c ON sc.contact_id = c.id
                WHERE sc.segment_id = :segment_id 
                AND c.phone IS NOT NULL 
                AND c.phone != ''
                AND c.status = 'active'
            ";
            $contactsStmt = $this->db->prepare($contactsQuery);
            $contactsStmt->bindParam(':segment_id', $alert['audience_segment_id']);
            $contactsStmt->execute();
            $alert['target_contacts'] = $contactsStmt->fetch(PDO::FETCH_ASSOC)['target_count'];
        }
        
        echo json_encode(['success' => true, 'data' => $alert]);
    }
    
    private function getDeliveryStatus() {
        $alertId = $_GET['alert_id'] ?? null;
        if (!$alertId) {
            throw new Exception('SMS Alert ID is required');
        }
        
        $query = "
            SELECT 
                status, sent_count, delivered_count, failed_count,
                delivery_rate, response_count, response_rate,
                total_cost, sent_at, delivery_report
            FROM sms_alerts 
            WHERE id = :id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $alertId);
        $stmt->execute();
        
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$status) {
            throw new Exception('SMS Alert not found');
        }
        
        if ($status['delivery_report']) {
            $status['delivery_report'] = json_decode($status['delivery_report'], true);
        }
        
        echo json_encode(['success' => true, 'data' => $status]);
    }
    
    private function getSmsAnalytics() {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $analytics = [];
        
        // Overall statistics
        $overallQuery = "
            SELECT 
                COUNT(*) as total_alerts,
                SUM(sent_count) as total_sent,
                SUM(delivered_count) as total_delivered,
                SUM(failed_count) as total_failed,
                SUM(response_count) as total_responses,
                SUM(total_cost) as total_cost,
                AVG(delivery_rate) as avg_delivery_rate,
                AVG(response_rate) as avg_response_rate
            FROM sms_alerts 
            WHERE DATE(created_at) BETWEEN :date_from AND :date_to
        ";
        $overallStmt = $this->db->prepare($overallQuery);
        $overallStmt->bindParam(':date_from', $dateFrom);
        $overallStmt->bindParam(':date_to', $dateTo);
        $overallStmt->execute();
        $analytics['overall'] = $overallStmt->fetch(PDO::FETCH_ASSOC);
        
        // By alert type
        $typeQuery = "
            SELECT 
                alert_type,
                COUNT(*) as count,
                SUM(sent_count) as total_sent,
                AVG(delivery_rate) as avg_delivery_rate
            FROM sms_alerts 
            WHERE DATE(created_at) BETWEEN :date_from AND :date_to
            GROUP BY alert_type
        ";
        $typeStmt = $this->db->prepare($typeQuery);
        $typeStmt->bindParam(':date_from', $dateFrom);
        $typeStmt->bindParam(':date_to', $dateTo);
        $typeStmt->execute();
        $analytics['by_type'] = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // By priority
        $priorityQuery = "
            SELECT 
                priority,
                COUNT(*) as count,
                AVG(delivery_rate) as avg_delivery_rate,
                AVG(response_rate) as avg_response_rate
            FROM sms_alerts 
            WHERE DATE(created_at) BETWEEN :date_from AND :date_to
            GROUP BY priority
        ";
        $priorityStmt = $this->db->prepare($priorityQuery);
        $priorityStmt->bindParam(':date_from', $dateFrom);
        $priorityStmt->bindParam(':date_to', $dateTo);
        $priorityStmt->execute();
        $analytics['by_priority'] = $priorityStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Daily trend
        $trendQuery = "
            SELECT 
                DATE(sent_at) as send_date,
                COUNT(*) as alerts_sent,
                SUM(sent_count) as messages_sent,
                AVG(delivery_rate) as avg_delivery_rate
            FROM sms_alerts 
            WHERE DATE(sent_at) BETWEEN :date_from AND :date_to
            AND status = 'sent'
            GROUP BY DATE(sent_at)
            ORDER BY send_date ASC
        ";
        $trendStmt = $this->db->prepare($trendQuery);
        $trendStmt->bindParam(':date_from', $dateFrom);
        $trendStmt->bindParam(':date_to', $dateTo);
        $trendStmt->execute();
        $analytics['daily_trend'] = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $analytics]);
    }
    
    private function getSmsTemplates() {
        $templates = [
            'emergency' => [
                'title' => 'Emergency Alert',
                'message' => 'EMERGENCY ALERT: {message}. Follow safety protocols. More info: {url}',
                'variables' => ['message', 'url']
            ],
            'warning' => [
                'title' => 'Warning Notification',
                'message' => 'WARNING: {message}. Please take necessary precautions. Info: {url}',
                'variables' => ['message', 'url']
            ],
            'event_reminder' => [
                'title' => 'Event Reminder',
                'message' => 'Reminder: {event_name} on {date} at {time}. Location: {location}. Reply STOP to opt out.',
                'variables' => ['event_name', 'date', 'time', 'location']
            ],
            'safety_tip' => [
                'title' => 'Safety Tip',
                'message' => 'SAFETY TIP: {tip}. Stay safe! More tips: {url}',
                'variables' => ['tip', 'url']
            ],
            'update' => [
                'title' => 'Update Notification',
                'message' => 'UPDATE: {message}. For more information visit: {url}',
                'variables' => ['message', 'url']
            ]
        ];
        
        echo json_encode(['success' => true, 'data' => $templates]);
    }
    
    // CREATE Operations
    private function createSmsAlert() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['title', 'message', 'alert_type', 'created_by'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Validate message length (SMS limit is typically 160 characters)
        if (strlen($input['message']) > 160) {
            throw new Exception('Message exceeds 160 character SMS limit');
        }
        
        $query = "
            INSERT INTO sms_alerts (
                title, message, alert_type, audience_segment_id, campaign_id,
                scheduled_date, scheduled_time, priority, sender_id, webhook_url,
                created_by, status
            ) VALUES (
                :title, :message, :alert_type, :audience_segment_id, :campaign_id,
                :scheduled_date, :scheduled_time, :priority, :sender_id, :webhook_url,
                :created_by, :status
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':title', $input['title']);
        $stmt->bindParam(':message', $input['message']);
        $stmt->bindParam(':alert_type', $input['alert_type']);
        $stmt->bindParam(':audience_segment_id', $input['audience_segment_id'] ?? null);
        $stmt->bindParam(':campaign_id', $input['campaign_id'] ?? null);
        $stmt->bindParam(':scheduled_date', $input['scheduled_date'] ?? null);
        $stmt->bindParam(':scheduled_time', $input['scheduled_time'] ?? null);
        $stmt->bindParam(':priority', $input['priority'] ?? 'normal');
        $stmt->bindParam(':sender_id', $input['sender_id'] ?? 'SafetyCampaign');
        $stmt->bindParam(':webhook_url', $input['webhook_url'] ?? null);
        $stmt->bindParam(':created_by', $input['created_by']);
        $stmt->bindParam(':status', $input['status'] ?? 'draft');
        
        if ($stmt->execute()) {
            $alertId = $this->db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'SMS Alert created successfully',
                'alert_id' => $alertId
            ]);
        } else {
            throw new Exception('Failed to create SMS alert');
        }
    }
    
    private function sendSmsNow() {
        $input = json_decode(file_get_contents('php://input'), true);
        $alertId = $input['alert_id'] ?? null;
        
        if (!$alertId) {
            throw new Exception('Alert ID is required');
        }
        
        // Get alert details
        $alertQuery = "SELECT * FROM sms_alerts WHERE id = :id AND status IN ('draft', 'scheduled')";
        $alertStmt = $this->db->prepare($alertQuery);
        $alertStmt->bindParam(':id', $alertId);
        $alertStmt->execute();
        $alert = $alertStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$alert) {
            throw new Exception('Alert not found or already sent');
        }
        
        // Get target contacts
        $contacts = $this->getTargetContacts($alert['audience_segment_id']);
        $contactCount = count($contacts);
        
        if ($contactCount === 0) {
            throw new Exception('No valid contacts found for this alert');
        }
        
        // Simulate SMS sending (In real implementation, integrate with SMS provider)
        $sentCount = 0;
        $deliveredCount = 0;
        $failedCount = 0;
        $costPerMessage = 0.05; // Example cost
        $deliveryReport = [];
        
        foreach ($contacts as $contact) {
            // Simulate SMS sending logic
            $success = $this->simulateSmsDelivery($contact, $alert['message']);
            
            if ($success) {
                $sentCount++;
                $deliveredCount++;
                $deliveryReport[] = [
                    'contact_id' => $contact['id'],
                    'phone' => $contact['phone'],
                    'status' => 'delivered',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                $sentCount++;
                $failedCount++;
                $deliveryReport[] = [
                    'contact_id' => $contact['id'],
                    'phone' => $contact['phone'],
                    'status' => 'failed',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'error' => 'Network timeout'
                ];
            }
        }
        
        $deliveryRate = $sentCount > 0 ? round(($deliveredCount / $sentCount) * 100, 2) : 0;
        $totalCost = $sentCount * $costPerMessage;
        
        // Update alert status
        $updateQuery = "
            UPDATE sms_alerts SET 
                status = 'sent',
                sent_count = :sent_count,
                delivered_count = :delivered_count,
                failed_count = :failed_count,
                delivery_rate = :delivery_rate,
                cost_per_message = :cost_per_message,
                total_cost = :total_cost,
                delivery_report = :delivery_report,
                sent_at = NOW()
            WHERE id = :id
        ";
        
        $updateStmt = $this->db->prepare($updateQuery);
        $updateStmt->bindParam(':sent_count', $sentCount);
        $updateStmt->bindParam(':delivered_count', $deliveredCount);
        $updateStmt->bindParam(':failed_count', $failedCount);
        $updateStmt->bindParam(':delivery_rate', $deliveryRate);
        $updateStmt->bindParam(':cost_per_message', $costPerMessage);
        $updateStmt->bindParam(':total_cost', $totalCost);
        $updateStmt->bindValue(':delivery_report', json_encode($deliveryReport));
        $updateStmt->bindParam(':id', $alertId);
        $updateStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'SMS Alert sent successfully',
            'stats' => [
                'sent' => $sentCount,
                'delivered' => $deliveredCount,
                'failed' => $failedCount,
                'delivery_rate' => $deliveryRate,
                'total_cost' => $totalCost
            ]
        ]);
    }
    
    private function scheduleSms() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['alert_id']) || empty($input['scheduled_date']) || empty($input['scheduled_time'])) {
            throw new Exception('Alert ID, scheduled date and time are required');
        }
        
        $scheduledDateTime = $input['scheduled_date'] . ' ' . $input['scheduled_time'];
        
        // Validate future date
        if (strtotime($scheduledDateTime) <= time()) {
            throw new Exception('Scheduled time must be in the future');
        }
        
        $query = "
            UPDATE sms_alerts SET 
                scheduled_date = :scheduled_date,
                scheduled_time = :scheduled_time,
                status = 'scheduled'
            WHERE id = :id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':scheduled_date', $input['scheduled_date']);
        $stmt->bindParam(':scheduled_time', $input['scheduled_time']);
        $stmt->bindParam(':id', $input['alert_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'SMS Alert scheduled successfully']);
        } else {
            throw new Exception('Failed to schedule SMS alert');
        }
    }
    
    private function sendTestSms() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['phone']) || empty($input['message'])) {
            throw new Exception('Phone number and message are required');
        }
        
        // Simulate test SMS sending
        $success = $this->simulateSmsDelivery(['phone' => $input['phone']], $input['message']);
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Test SMS sent successfully']);
        } else {
            throw new Exception('Failed to send test SMS');
        }
    }
    
    // UPDATE Operations
    private function updateSmsAlert() {
        $input = json_decode(file_get_contents('php://input'), true);
        $alertId = $input['id'] ?? null;
        
        if (!$alertId) {
            throw new Exception('Alert ID is required');
        }
        
        $updateFields = [];
        $params = ['id' => $alertId];
        
        $allowedFields = [
            'title', 'message', 'alert_type', 'audience_segment_id', 'campaign_id',
            'scheduled_date', 'scheduled_time', 'priority', 'sender_id', 'webhook_url'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = :$field";
                $params[$field] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }
        
        $updateFields[] = "updated_at = NOW()";
        
        $query = "UPDATE sms_alerts SET " . implode(', ', $updateFields) . " WHERE id = :id AND status IN ('draft', 'scheduled')";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'SMS Alert updated successfully']);
        } else {
            throw new Exception('Failed to update SMS alert or alert already sent');
        }
    }
    
    private function updateStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        $alertId = $input['alert_id'] ?? null;
        $status = $input['status'] ?? null;
        
        if (!$alertId || !$status) {
            throw new Exception('Alert ID and status are required');
        }
        
        $validStatuses = ['draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status');
        }
        
        $query = "UPDATE sms_alerts SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $alertId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'SMS Alert status updated successfully']);
        } else {
            throw new Exception('Failed to update SMS alert status');
        }
    }
    
    private function rescheduleSms() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['alert_id']) || empty($input['scheduled_date']) || empty($input['scheduled_time'])) {
            throw new Exception('Alert ID, scheduled date and time are required');
        }
        
        $scheduledDateTime = $input['scheduled_date'] . ' ' . $input['scheduled_time'];
        
        // Validate future date
        if (strtotime($scheduledDateTime) <= time()) {
            throw new Exception('Scheduled time must be in the future');
        }
        
        $query = "
            UPDATE sms_alerts SET 
                scheduled_date = :scheduled_date,
                scheduled_time = :scheduled_time,
                status = 'scheduled',
                updated_at = NOW()
            WHERE id = :id AND status IN ('scheduled', 'failed')
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':scheduled_date', $input['scheduled_date']);
        $stmt->bindParam(':scheduled_time', $input['scheduled_time']);
        $stmt->bindParam(':id', $input['alert_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'SMS Alert rescheduled successfully']);
        } else {
            throw new Exception('Failed to reschedule SMS alert');
        }
    }
    
    // DELETE Operations
    private function deleteSmsAlert() {
        $alertId = $_GET['id'] ?? null;
        if (!$alertId) {
            throw new Exception('Alert ID is required');
        }
        
        // Only allow deletion of draft alerts
        $query = "DELETE FROM sms_alerts WHERE id = :id AND status = 'draft'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $alertId);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'SMS Alert deleted successfully']);
            } else {
                throw new Exception('SMS Alert not found or cannot be deleted (only draft alerts can be deleted)');
            }
        } else {
            throw new Exception('Failed to delete SMS alert');
        }
    }
    
    private function cancelScheduledSms() {
        $alertId = $_GET['id'] ?? null;
        if (!$alertId) {
            throw new Exception('Alert ID is required');
        }
        
        $query = "UPDATE sms_alerts SET status = 'cancelled', updated_at = NOW() WHERE id = :id AND status = 'scheduled'";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $alertId);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Scheduled SMS Alert cancelled successfully']);
            } else {
                throw new Exception('SMS Alert not found or not scheduled');
            }
        } else {
            throw new Exception('Failed to cancel scheduled SMS alert');
        }
    }
    
    // Helper Methods
    private function getTargetContacts($segmentId) {
        if (!$segmentId) {
            return [];
        }
        
        $query = "
            SELECT c.id, c.first_name, c.last_name, c.phone, c.email
            FROM contacts c
            JOIN segment_contacts sc ON c.id = sc.contact_id
            WHERE sc.segment_id = :segment_id 
            AND c.phone IS NOT NULL 
            AND c.phone != ''
            AND c.status = 'active'
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function simulateSmsDelivery($contact, $message) {
        // Simulate SMS delivery with 95% success rate
        return (rand(1, 100) <= 95);
    }
}

// Handle the request
$smsAlertsCRUD = new SmsAlertsCRUD();
$smsAlertsCRUD->handleRequest();
?>
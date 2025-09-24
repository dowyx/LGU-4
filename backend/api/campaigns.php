<?php
/**
 * Campaigns CRUD Operations
 * Public Safety Campaign Management System
 */

require_once '../config/database.php';
require_once '../utils/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

class CampaignsCRUD {
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
                $this->getCampaigns();
                break;
            case 'details':
                $this->getCampaignDetails();
                break;
            case 'statistics':
                $this->getCampaignStatistics();
                break;
            default:
                $this->getCampaigns();
        }
    }
    
    private function handlePost($action) {
        switch ($action) {
            case 'create':
                $this->createCampaign();
                break;
            case 'objective':
                $this->addObjective();
                break;
            case 'milestone':
                $this->addMilestone();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handlePut($action) {
        switch ($action) {
            case 'update':
                $this->updateCampaign();
                break;
            case 'status':
                $this->updateStatus();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handleDelete($action) {
        switch ($action) {
            case 'delete':
                $this->deleteCampaign();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    // READ Operations
    private function getCampaigns() {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($status) {
            $whereClause .= " AND c.status = :status";
            $params['status'] = $status;
        }
        
        if ($search) {
            $whereClause .= " AND (c.title LIKE :search OR c.description LIKE :search)";
            $params['search'] = "%$search%";
        }
        
        if ($category) {
            $whereClause .= " AND c.category = :category";
            $params['category'] = $category;
        }
        
        $query = "
            SELECT 
                c.*, 
                u.username as created_by_name,
                (SELECT COUNT(*) FROM campaign_objectives WHERE campaign_id = c.id) as objectives_count,
                (SELECT COUNT(*) FROM campaign_milestones WHERE campaign_id = c.id) as milestones_count,
                (SELECT COUNT(*) FROM campaign_collaborators WHERE campaign_id = c.id) as collaborators_count
            FROM campaigns c
            LEFT JOIN users u ON c.created_by = u.id
            $whereClause
            ORDER BY c.created_at DESC 
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM campaigns c $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":$key", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $campaigns,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'limit' => $limit
            ]
        ]);
    }
    
    private function getCampaignDetails() {
        $campaignId = $_GET['id'] ?? null;
        if (!$campaignId) {
            throw new Exception('Campaign ID is required');
        }
        
        // Get campaign basic info
        $query = "
            SELECT 
                c.*, 
                u.username as created_by_name, u.email as created_by_email
            FROM campaigns c
            LEFT JOIN users u ON c.created_by = u.id
            WHERE c.id = :id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $campaignId);
        $stmt->execute();
        
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$campaign) {
            throw new Exception('Campaign not found');
        }
        
        // Get objectives
        $objectivesQuery = "
            SELECT * FROM campaign_objectives 
            WHERE campaign_id = :campaign_id 
            ORDER BY priority DESC, created_at ASC
        ";
        $objectivesStmt = $this->db->prepare($objectivesQuery);
        $objectivesStmt->bindParam(':campaign_id', $campaignId);
        $objectivesStmt->execute();
        $campaign['objectives'] = $objectivesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get milestones
        $milestonesQuery = "
            SELECT * FROM campaign_milestones 
            WHERE campaign_id = :campaign_id 
            ORDER BY due_date ASC
        ";
        $milestonesStmt = $this->db->prepare($milestonesQuery);
        $milestonesStmt->bindParam(':campaign_id', $campaignId);
        $milestonesStmt->execute();
        $campaign['milestones'] = $milestonesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get collaborators
        $collaboratorsQuery = "
            SELECT 
                cc.*, u.username, u.email, u.first_name, u.last_name
            FROM campaign_collaborators cc
            JOIN users u ON cc.user_id = u.id
            WHERE cc.campaign_id = :campaign_id
            ORDER BY cc.role, u.first_name
        ";
        $collaboratorsStmt = $this->db->prepare($collaboratorsQuery);
        $collaboratorsStmt->bindParam(':campaign_id', $campaignId);
        $collaboratorsStmt->execute();
        $campaign['collaborators'] = $collaboratorsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get related events
        $eventsQuery = "
            SELECT id, title, event_date, status, max_participants 
            FROM events 
            WHERE campaign_id = :campaign_id 
            ORDER BY event_date DESC
        ";
        $eventsStmt = $this->db->prepare($eventsQuery);
        $eventsStmt->bindParam(':campaign_id', $campaignId);
        $eventsStmt->execute();
        $campaign['events'] = $eventsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $campaign]);
    }
    
    private function getCampaignStatistics() {
        $campaignId = $_GET['id'] ?? null;
        if (!$campaignId) {
            throw new Exception('Campaign ID is required');
        }
        
        $stats = [];
        
        // Basic metrics
        $metricsQuery = "
            SELECT 
                (SELECT COUNT(*) FROM events WHERE campaign_id = :campaign_id) as total_events,
                (SELECT COUNT(*) FROM surveys WHERE campaign_id = :campaign_id) as total_surveys,
                (SELECT SUM(response_count) FROM surveys WHERE campaign_id = :campaign_id) as total_responses,
                (SELECT COUNT(DISTINCT er.contact_id) FROM events e JOIN event_registrations er ON e.id = er.event_id WHERE e.campaign_id = :campaign_id) as unique_participants
        ";
        $metricsStmt = $this->db->prepare($metricsQuery);
        $metricsStmt->bindParam(':campaign_id', $campaignId);
        $metricsStmt->execute();
        $stats = $metricsStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $stats]);
    }
    
    // CREATE Operations
    private function createCampaign() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['title', 'description', 'category', 'created_by'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        $query = "
            INSERT INTO campaigns (
                title, description, category, objectives_summary, target_audience,
                start_date, end_date, budget, status, priority, created_by
            ) VALUES (
                :title, :description, :category, :objectives_summary, :target_audience,
                :start_date, :end_date, :budget, :status, :priority, :created_by
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':title', $input['title']);
        $stmt->bindParam(':description', $input['description']);
        $stmt->bindParam(':category', $input['category']);
        $stmt->bindParam(':objectives_summary', $input['objectives_summary'] ?? null);
        $stmt->bindParam(':target_audience', $input['target_audience'] ?? null);
        $stmt->bindParam(':start_date', $input['start_date'] ?? null);
        $stmt->bindParam(':end_date', $input['end_date'] ?? null);
        $stmt->bindParam(':budget', $input['budget'] ?? null);
        $stmt->bindParam(':status', $input['status'] ?? 'draft');
        $stmt->bindParam(':priority', $input['priority'] ?? 'medium');
        $stmt->bindParam(':created_by', $input['created_by']);
        
        if ($stmt->execute()) {
            $campaignId = $this->db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Campaign created successfully',
                'campaign_id' => $campaignId
            ]);
        } else {
            throw new Exception('Failed to create campaign');
        }
    }
    
    private function addObjective() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['campaign_id']) || empty($input['title'])) {
            throw new Exception('Campaign ID and title are required');
        }
        
        $query = "
            INSERT INTO campaign_objectives (
                campaign_id, title, description, target_metrics, 
                priority, status, due_date
            ) VALUES (
                :campaign_id, :title, :description, :target_metrics, 
                :priority, :status, :due_date
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':campaign_id', $input['campaign_id']);
        $stmt->bindParam(':title', $input['title']);
        $stmt->bindParam(':description', $input['description'] ?? null);
        $stmt->bindParam(':target_metrics', $input['target_metrics'] ?? null);
        $stmt->bindParam(':priority', $input['priority'] ?? 'medium');
        $stmt->bindParam(':status', $input['status'] ?? 'pending');
        $stmt->bindParam(':due_date', $input['due_date'] ?? null);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Objective added successfully']);
        } else {
            throw new Exception('Failed to add objective');
        }
    }
    
    private function addMilestone() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['campaign_id']) || empty($input['title'])) {
            throw new Exception('Campaign ID and title are required');
        }
        
        $query = "
            INSERT INTO campaign_milestones (
                campaign_id, title, description, due_date, 
                status, completion_percentage
            ) VALUES (
                :campaign_id, :title, :description, :due_date, 
                :status, :completion_percentage
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':campaign_id', $input['campaign_id']);
        $stmt->bindParam(':title', $input['title']);
        $stmt->bindParam(':description', $input['description'] ?? null);
        $stmt->bindParam(':due_date', $input['due_date'] ?? null);
        $stmt->bindParam(':status', $input['status'] ?? 'pending');
        $stmt->bindParam(':completion_percentage', $input['completion_percentage'] ?? 0);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Milestone added successfully']);
        } else {
            throw new Exception('Failed to add milestone');
        }
    }
    
    // UPDATE Operations
    private function updateCampaign() {
        $input = json_decode(file_get_contents('php://input'), true);
        $campaignId = $input['id'] ?? null;
        
        if (!$campaignId) {
            throw new Exception('Campaign ID is required');
        }
        
        $updateFields = [];
        $params = ['id' => $campaignId];
        
        $allowedFields = [
            'title', 'description', 'category', 'objectives_summary', 'target_audience',
            'start_date', 'end_date', 'budget', 'status', 'priority'
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
        
        $query = "UPDATE campaigns SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Campaign updated successfully']);
        } else {
            throw new Exception('Failed to update campaign');
        }
    }
    
    private function updateStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        $campaignId = $input['campaign_id'] ?? null;
        $status = $input['status'] ?? null;
        
        if (!$campaignId || !$status) {
            throw new Exception('Campaign ID and status are required');
        }
        
        $validStatuses = ['draft', 'active', 'paused', 'completed', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status');
        }
        
        $query = "UPDATE campaigns SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $campaignId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Campaign status updated successfully']);
        } else {
            throw new Exception('Failed to update campaign status');
        }
    }
    
    // DELETE Operations
    private function deleteCampaign() {
        $campaignId = $_GET['id'] ?? null;
        if (!$campaignId) {
            throw new Exception('Campaign ID is required');
        }
        
        // Check for dependencies
        $checkQuery = "
            SELECT 
                (SELECT COUNT(*) FROM events WHERE campaign_id = :campaign_id) as events,
                (SELECT COUNT(*) FROM surveys WHERE campaign_id = :campaign_id) as surveys
        ";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':campaign_id', $campaignId);
        $checkStmt->execute();
        $dependencies = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dependencies['events'] > 0 || $dependencies['surveys'] > 0) {
            throw new Exception('Cannot delete campaign with associated events or surveys');
        }
        
        // Delete related records first
        $this->db->prepare("DELETE FROM campaign_objectives WHERE campaign_id = ?")->execute([$campaignId]);
        $this->db->prepare("DELETE FROM campaign_milestones WHERE campaign_id = ?")->execute([$campaignId]);
        $this->db->prepare("DELETE FROM campaign_collaborators WHERE campaign_id = ?")->execute([$campaignId]);
        
        // Delete campaign
        $query = "DELETE FROM campaigns WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $campaignId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Campaign deleted successfully']);
        } else {
            throw new Exception('Failed to delete campaign');
        }
    }
}

// Handle the request
$campaignsCRUD = new CampaignsCRUD();
$campaignsCRUD->handleRequest();
?>
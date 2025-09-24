<?php
/**
 * Database Helper Class for Public Safety Campaign Management System
 * Provides easy access to all imported database tables
 */

require_once 'config/database.php';

class DatabaseHelper {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        if (!$this->db) {
            throw new Exception('Database connection failed');
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->db;
    }
    
    /**
     * Execute a prepared statement with parameters
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query execution failed: " . $e->getMessage());
        }
    }
    
    /**
     * Fetch all records from a query
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch single record from a query
     */
    public function fetch($query, $params = []) {
        $stmt = $this->execute($query, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert record and return last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $this->execute($query, $data);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update records
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $key) {
            $setClause[] = "$key = :$key";
        }
        $setClause = implode(', ', $setClause);
        
        $query = "UPDATE $table SET $setClause WHERE $where";
        $params = array_merge($data, $whereParams);
        
        return $this->execute($query, $params);
    }
    
    /**
     * Delete records
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM $table WHERE $where";
        return $this->execute($query, $params);
    }
    
    // ========================================
    // Specific helper methods for your tables
    // ========================================
    
    /**
     * Get all users with their roles
     */
    public function getUsers($limit = 50) {
        return $this->fetchAll("
            SELECT u.*, ur.role_name 
            FROM users u 
            LEFT JOIN user_roles ur ON u.role_id = ur.id 
            ORDER BY u.created_at DESC 
            LIMIT $limit
        ");
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        return $this->fetch("
            SELECT u.*, ur.role_name 
            FROM users u 
            LEFT JOIN user_roles ur ON u.role_id = ur.id 
            WHERE u.email = :email
        ", ['email' => $email]);
    }
    
    /**
     * Get all campaigns with basic info
     */
    public function getCampaigns($status = null) {
        $query = "SELECT * FROM campaigns";
        $params = [];
        
        if ($status) {
            $query .= " WHERE status = :status";
            $params['status'] = $status;
        }
        
        $query .= " ORDER BY created_at DESC";
        return $this->fetchAll($query, $params);
    }
    
    /**
     * Get campaign with full details
     */
    public function getCampaignDetails($campaignId) {
        $campaign = $this->fetch("SELECT * FROM campaigns WHERE id = :id", ['id' => $campaignId]);
        
        if ($campaign) {
            // Get objectives
            $campaign['objectives'] = $this->fetchAll("
                SELECT * FROM campaign_objectives 
                WHERE campaign_id = :campaign_id 
                ORDER BY priority DESC
            ", ['campaign_id' => $campaignId]);
            
            // Get milestones
            $campaign['milestones'] = $this->fetchAll("
                SELECT * FROM campaign_milestones 
                WHERE campaign_id = :campaign_id 
                ORDER BY due_date ASC
            ", ['campaign_id' => $campaignId]);
            
            // Get collaborators
            $campaign['collaborators'] = $this->fetchAll("
                SELECT cc.*, u.username, u.email 
                FROM campaign_collaborators cc 
                JOIN users u ON cc.user_id = u.id 
                WHERE cc.campaign_id = :campaign_id
            ", ['campaign_id' => $campaignId]);
        }
        
        return $campaign;
    }
    
    /**
     * Get all surveys
     */
    public function getSurveys($status = null) {
        $query = "SELECT * FROM surveys";
        $params = [];
        
        if ($status) {
            $query .= " WHERE status = :status";
            $params['status'] = $status;
        }
        
        $query .= " ORDER BY created_at DESC";
        return $this->fetchAll($query, $params);
    }
    
    /**
     * Get survey with questions
     */
    public function getSurveyWithQuestions($surveyId) {
        $survey = $this->fetch("SELECT * FROM surveys WHERE id = :id", ['id' => $surveyId]);
        
        if ($survey) {
            $survey['questions'] = $this->fetchAll("
                SELECT * FROM survey_questions 
                WHERE survey_id = :survey_id 
                ORDER BY question_order ASC
            ", ['survey_id' => $surveyId]);
        }
        
        return $survey;
    }
    
    /**
     * Get all events
     */
    public function getEvents($upcoming = false) {
        $query = "SELECT * FROM events";
        $params = [];
        
        if ($upcoming) {
            $query .= " WHERE event_date >= CURDATE()";
        }
        
        $query .= " ORDER BY event_date ASC";
        return $this->fetchAll($query, $params);
    }
    
    /**
     * Get contacts by segment
     */
    public function getContactsBySegment($segmentId) {
        return $this->fetchAll("
            SELECT c.* 
            FROM contacts c 
            JOIN segment_contacts sc ON c.id = sc.contact_id 
            WHERE sc.segment_id = :segment_id
        ", ['segment_id' => $segmentId]);
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        return [
            'total_users' => $this->fetch("SELECT COUNT(*) as count FROM users")['count'],
            'total_campaigns' => $this->fetch("SELECT COUNT(*) as count FROM campaigns")['count'],
            'active_campaigns' => $this->fetch("SELECT COUNT(*) as count FROM campaigns WHERE status = 'active'")['count'],
            'total_surveys' => $this->fetch("SELECT COUNT(*) as count FROM surveys")['count'],
            'total_events' => $this->fetch("SELECT COUNT(*) as count FROM events")['count'],
            'total_contacts' => $this->fetch("SELECT COUNT(*) as count FROM contacts")['count'],
            'survey_responses' => $this->fetch("SELECT COUNT(*) as count FROM survey_responses")['count']
        ];
    }
    
    /**
     * Search across multiple tables
     */
    public function globalSearch($searchTerm) {
        $results = [];
        $searchParam = ['search' => "%$searchTerm%"];
        
        // Search campaigns
        $results['campaigns'] = $this->fetchAll("
            SELECT id, title, 'campaign' as type, description 
            FROM campaigns 
            WHERE title LIKE :search OR description LIKE :search 
            LIMIT 10
        ", $searchParam);
        
        // Search surveys
        $results['surveys'] = $this->fetchAll("
            SELECT id, title, 'survey' as type, description 
            FROM surveys 
            WHERE title LIKE :search OR description LIKE :search 
            LIMIT 10
        ", $searchParam);
        
        // Search events
        $results['events'] = $this->fetchAll("
            SELECT id, title, 'event' as type, description 
            FROM events 
            WHERE title LIKE :search OR description LIKE :search 
            LIMIT 10
        ", $searchParam);
        
        return $results;
    }
}
?>
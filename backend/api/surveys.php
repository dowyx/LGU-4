<?php
/**
 * Surveys CRUD Operations
 * Public Safety Campaign Management System
 */

require_once '../config/database.php';
require_once '../utils/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

class SurveysCRUD {
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
                $this->getSurveys();
                break;
            case 'details':
                $this->getSurveyDetails();
                break;
            case 'questions':
                $this->getSurveyQuestions();
                break;
            case 'responses':
                $this->getSurveyResponses();
                break;
            case 'analytics':
                $this->getSurveyAnalytics();
                break;
            default:
                $this->getSurveys();
        }
    }
    
    private function handlePost($action) {
        switch ($action) {
            case 'create':
                $this->createSurvey();
                break;
            case 'question':
                $this->addQuestion();
                break;
            case 'response':
                $this->submitResponse();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handlePut($action) {
        switch ($action) {
            case 'update':
                $this->updateSurvey();
                break;
            case 'question':
                $this->updateQuestion();
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
                $this->deleteSurvey();
                break;
            case 'question':
                $this->deleteQuestion();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    // READ Operations
    private function getSurveys() {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $search = $_GET['search'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($status) {
            $whereClause .= " AND s.status = :status";
            $params['status'] = $status;
        }
        
        if ($type) {
            $whereClause .= " AND s.survey_type = :type";
            $params['type'] = $type;
        }
        
        if ($search) {
            $whereClause .= " AND (s.title LIKE :search OR s.description LIKE :search)";
            $params['search'] = "%$search%";
        }
        
        $query = "
            SELECT 
                s.*,
                u.username as created_by_name,
                c.title as campaign_title,
                e.title as event_title,
                (SELECT COUNT(*) FROM survey_questions WHERE survey_id = s.id) as questions_count
            FROM surveys s
            LEFT JOIN users u ON s.created_by = u.id
            LEFT JOIN campaigns c ON s.campaign_id = c.id
            LEFT JOIN events e ON s.event_id = e.id
            $whereClause
            ORDER BY s.created_at DESC 
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM surveys s $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":$key", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $surveys,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'limit' => $limit
            ]
        ]);
    }
    
    private function getSurveyDetails() {
        $surveyId = $_GET['id'] ?? null;
        if (!$surveyId) {
            throw new Exception('Survey ID is required');
        }
        
        $query = "
            SELECT 
                s.*,
                u.username as created_by_name, u.email as created_by_email,
                c.title as campaign_title,
                e.title as event_title
            FROM surveys s
            LEFT JOIN users u ON s.created_by = u.id
            LEFT JOIN campaigns c ON s.campaign_id = c.id
            LEFT JOIN events e ON s.event_id = e.id
            WHERE s.id = :id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $surveyId);
        $stmt->execute();
        
        $survey = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$survey) {
            throw new Exception('Survey not found');
        }
        
        // Get questions
        $questionsQuery = "
            SELECT * FROM survey_questions 
            WHERE survey_id = :survey_id 
            ORDER BY question_order ASC
        ";
        $questionsStmt = $this->db->prepare($questionsQuery);
        $questionsStmt->bindParam(':survey_id', $surveyId);
        $questionsStmt->execute();
        $survey['questions'] = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $survey]);
    }
    
    private function getSurveyQuestions() {
        $surveyId = $_GET['survey_id'] ?? null;
        if (!$surveyId) {
            throw new Exception('Survey ID is required');
        }
        
        $query = "
            SELECT * FROM survey_questions 
            WHERE survey_id = :survey_id 
            ORDER BY question_order ASC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':survey_id', $surveyId);
        $stmt->execute();
        
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse JSON fields
        foreach ($questions as &$question) {
            if ($question['options']) {
                $question['options'] = json_decode($question['options'], true);
            }
            if ($question['validation_rules']) {
                $question['validation_rules'] = json_decode($question['validation_rules'], true);
            }
            if ($question['conditional_logic']) {
                $question['conditional_logic'] = json_decode($question['conditional_logic'], true);
            }
        }
        
        echo json_encode(['success' => true, 'data' => $questions]);
    }
    
    private function getSurveyResponses() {
        $surveyId = $_GET['survey_id'] ?? null;
        if (!$surveyId) {
            throw new Exception('Survey ID is required');
        }
        
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 50;
        $offset = ($page - 1) * $limit;
        
        $query = "
            SELECT 
                sr.*,
                c.email as contact_email, c.first_name, c.last_name
            FROM survey_responses sr
            LEFT JOIN contacts c ON sr.contact_id = c.id
            WHERE sr.survey_id = :survey_id
            ORDER BY sr.submitted_at DESC
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':survey_id', $surveyId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse response data JSON
        foreach ($responses as &$response) {
            $response['response_data'] = json_decode($response['response_data'], true);
        }
        
        echo json_encode(['success' => true, 'data' => $responses]);
    }
    
    private function getSurveyAnalytics() {
        $surveyId = $_GET['survey_id'] ?? null;
        if (!$surveyId) {
            throw new Exception('Survey ID is required');
        }
        
        $analytics = [];
        
        // Basic stats
        $statsQuery = "
            SELECT 
                s.response_count,
                s.max_responses,
                AVG(sr.completion_time) as avg_completion_time,
                COUNT(DISTINCT DATE(sr.submitted_at)) as response_days
            FROM surveys s
            LEFT JOIN survey_responses sr ON s.id = sr.survey_id
            WHERE s.id = :survey_id
            GROUP BY s.id
        ";
        $statsStmt = $this->db->prepare($statsQuery);
        $statsStmt->bindParam(':survey_id', $surveyId);
        $statsStmt->execute();
        $analytics['basic_stats'] = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Response rate over time
        $timeQuery = "
            SELECT 
                DATE(submitted_at) as response_date,
                COUNT(*) as daily_responses
            FROM survey_responses 
            WHERE survey_id = :survey_id
            GROUP BY DATE(submitted_at)
            ORDER BY response_date ASC
        ";
        $timeStmt = $this->db->prepare($timeQuery);
        $timeStmt->bindParam(':survey_id', $surveyId);
        $timeStmt->execute();
        $analytics['response_timeline'] = $timeStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Question analysis (for choice-based questions)
        $questionsQuery = "
            SELECT * FROM survey_questions 
            WHERE survey_id = :survey_id 
            AND question_type IN ('radio', 'checkbox', 'select')
            ORDER BY question_order ASC
        ";
        $questionsStmt = $this->db->prepare($questionsQuery);
        $questionsStmt->bindParam(':survey_id', $surveyId);
        $questionsStmt->execute();
        $questions = $questionsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $analytics['question_analysis'] = [];
        foreach ($questions as $question) {
            $analytics['question_analysis'][$question['id']] = [
                'question' => $question['question_text'],
                'type' => $question['question_type'],
                'options' => json_decode($question['options'], true),
                'responses' => []
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $analytics]);
    }
    
    // CREATE Operations
    private function createSurvey() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['title', 'survey_type', 'created_by'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        $query = "
            INSERT INTO surveys (
                title, description, survey_type, campaign_id, event_id,
                start_date, end_date, max_responses, is_anonymous,
                allow_multiple_responses, require_login, thank_you_message,
                completion_redirect_url, notification_email, survey_settings,
                created_by, status
            ) VALUES (
                :title, :description, :survey_type, :campaign_id, :event_id,
                :start_date, :end_date, :max_responses, :is_anonymous,
                :allow_multiple_responses, :require_login, :thank_you_message,
                :completion_redirect_url, :notification_email, :survey_settings,
                :created_by, :status
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':title', $input['title']);
        $stmt->bindParam(':description', $input['description'] ?? null);
        $stmt->bindParam(':survey_type', $input['survey_type']);
        $stmt->bindParam(':campaign_id', $input['campaign_id'] ?? null);
        $stmt->bindParam(':event_id', $input['event_id'] ?? null);
        $stmt->bindParam(':start_date', $input['start_date'] ?? null);
        $stmt->bindParam(':end_date', $input['end_date'] ?? null);
        $stmt->bindParam(':max_responses', $input['max_responses'] ?? 0);
        $stmt->bindValue(':is_anonymous', $input['is_anonymous'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':allow_multiple_responses', $input['allow_multiple_responses'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':require_login', $input['require_login'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindParam(':thank_you_message', $input['thank_you_message'] ?? null);
        $stmt->bindParam(':completion_redirect_url', $input['completion_redirect_url'] ?? null);
        $stmt->bindParam(':notification_email', $input['notification_email'] ?? null);
        $stmt->bindValue(':survey_settings', isset($input['survey_settings']) ? json_encode($input['survey_settings']) : null);
        $stmt->bindParam(':created_by', $input['created_by']);
        $stmt->bindParam(':status', $input['status'] ?? 'draft');
        
        if ($stmt->execute()) {
            $surveyId = $this->db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Survey created successfully',
                'survey_id' => $surveyId
            ]);
        } else {
            throw new Exception('Failed to create survey');
        }
    }
    
    private function addQuestion() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['survey_id']) || empty($input['question_text']) || empty($input['question_type'])) {
            throw new Exception('Survey ID, question text, and question type are required');
        }
        
        // Get next order number
        $orderQuery = "SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM survey_questions WHERE survey_id = :survey_id";
        $orderStmt = $this->db->prepare($orderQuery);
        $orderStmt->bindParam(':survey_id', $input['survey_id']);
        $orderStmt->execute();
        $nextOrder = $orderStmt->fetch(PDO::FETCH_ASSOC)['next_order'];
        
        $query = "
            INSERT INTO survey_questions (
                survey_id, question_text, question_type, question_order,
                is_required, options, validation_rules, help_text, conditional_logic
            ) VALUES (
                :survey_id, :question_text, :question_type, :question_order,
                :is_required, :options, :validation_rules, :help_text, :conditional_logic
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':survey_id', $input['survey_id']);
        $stmt->bindParam(':question_text', $input['question_text']);
        $stmt->bindParam(':question_type', $input['question_type']);
        $stmt->bindParam(':question_order', $input['question_order'] ?? $nextOrder);
        $stmt->bindValue(':is_required', $input['is_required'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':options', isset($input['options']) ? json_encode($input['options']) : null);
        $stmt->bindValue(':validation_rules', isset($input['validation_rules']) ? json_encode($input['validation_rules']) : null);
        $stmt->bindParam(':help_text', $input['help_text'] ?? null);
        $stmt->bindValue(':conditional_logic', isset($input['conditional_logic']) ? json_encode($input['conditional_logic']) : null);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Question added successfully']);
        } else {
            throw new Exception('Failed to add question');
        }
    }
    
    private function submitResponse() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['survey_id']) || empty($input['response_data'])) {
            throw new Exception('Survey ID and response data are required');
        }
        
        $query = "
            INSERT INTO survey_responses (
                survey_id, respondent_email, respondent_name, contact_id,
                response_data, completion_time, ip_address, user_agent,
                referrer_url, is_complete
            ) VALUES (
                :survey_id, :respondent_email, :respondent_name, :contact_id,
                :response_data, :completion_time, :ip_address, :user_agent,
                :referrer_url, :is_complete
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':survey_id', $input['survey_id']);
        $stmt->bindParam(':respondent_email', $input['respondent_email'] ?? null);
        $stmt->bindParam(':respondent_name', $input['respondent_name'] ?? null);
        $stmt->bindParam(':contact_id', $input['contact_id'] ?? null);
        $stmt->bindValue(':response_data', json_encode($input['response_data']));
        $stmt->bindParam(':completion_time', $input['completion_time'] ?? null);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        $stmt->bindParam(':referrer_url', $_SERVER['HTTP_REFERER'] ?? null);
        $stmt->bindValue(':is_complete', $input['is_complete'] ?? true, PDO::PARAM_BOOL);
        
        if ($stmt->execute()) {
            // Update survey response count
            $updateQuery = "UPDATE surveys SET response_count = response_count + 1 WHERE id = :survey_id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':survey_id', $input['survey_id']);
            $updateStmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Response submitted successfully']);
        } else {
            throw new Exception('Failed to submit response');
        }
    }
    
    // UPDATE Operations
    private function updateSurvey() {
        $input = json_decode(file_get_contents('php://input'), true);
        $surveyId = $input['id'] ?? null;
        
        if (!$surveyId) {
            throw new Exception('Survey ID is required');
        }
        
        $updateFields = [];
        $params = ['id' => $surveyId];
        
        $allowedFields = [
            'title', 'description', 'start_date', 'end_date', 'max_responses',
            'is_anonymous', 'allow_multiple_responses', 'require_login',
            'thank_you_message', 'completion_redirect_url', 'notification_email',
            'survey_settings', 'status'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'survey_settings') {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = json_encode($input[$field]);
                } else {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = $input[$field];
                }
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }
        
        $updateFields[] = "updated_at = NOW()";
        
        $query = "UPDATE surveys SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Survey updated successfully']);
        } else {
            throw new Exception('Failed to update survey');
        }
    }
    
    private function updateQuestion() {
        $input = json_decode(file_get_contents('php://input'), true);
        $questionId = $input['id'] ?? null;
        
        if (!$questionId) {
            throw new Exception('Question ID is required');
        }
        
        $updateFields = [];
        $params = ['id' => $questionId];
        
        $allowedFields = [
            'question_text', 'question_type', 'question_order', 'is_required',
            'options', 'validation_rules', 'help_text', 'conditional_logic'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if (in_array($field, ['options', 'validation_rules', 'conditional_logic'])) {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = json_encode($input[$field]);
                } else {
                    $updateFields[] = "$field = :$field";
                    $params[$field] = $input[$field];
                }
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }
        
        $query = "UPDATE survey_questions SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Question updated successfully']);
        } else {
            throw new Exception('Failed to update question');
        }
    }
    
    private function updateStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        $surveyId = $input['survey_id'] ?? null;
        $status = $input['status'] ?? null;
        
        if (!$surveyId || !$status) {
            throw new Exception('Survey ID and status are required');
        }
        
        $validStatuses = ['draft', 'active', 'paused', 'closed'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception('Invalid status');
        }
        
        $query = "UPDATE surveys SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $surveyId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Survey status updated successfully']);
        } else {
            throw new Exception('Failed to update survey status');
        }
    }
    
    // DELETE Operations
    private function deleteSurvey() {
        $surveyId = $_GET['id'] ?? null;
        if (!$surveyId) {
            throw new Exception('Survey ID is required');
        }
        
        // Check if survey has responses
        $checkQuery = "SELECT COUNT(*) as response_count FROM survey_responses WHERE survey_id = :survey_id";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':survey_id', $surveyId);
        $checkStmt->execute();
        $responseCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['response_count'];
        
        if ($responseCount > 0) {
            throw new Exception('Cannot delete survey with existing responses');
        }
        
        // Delete questions first
        $deleteQuestionsQuery = "DELETE FROM survey_questions WHERE survey_id = :survey_id";
        $deleteQuestionsStmt = $this->db->prepare($deleteQuestionsQuery);
        $deleteQuestionsStmt->bindParam(':survey_id', $surveyId);
        $deleteQuestionsStmt->execute();
        
        // Delete survey
        $query = "DELETE FROM surveys WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $surveyId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Survey deleted successfully']);
        } else {
            throw new Exception('Failed to delete survey');
        }
    }
    
    private function deleteQuestion() {
        $questionId = $_GET['id'] ?? null;
        if (!$questionId) {
            throw new Exception('Question ID is required');
        }
        
        $query = "DELETE FROM survey_questions WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $questionId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Question deleted successfully']);
        } else {
            throw new Exception('Failed to delete question');
        }
    }
}

// Handle the request
$surveysCRUD = new SurveysCRUD();
$surveysCRUD->handleRequest();
?>
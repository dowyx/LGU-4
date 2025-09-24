<?php
/**
 * Audience Segmentation CRUD Operations
 * Public Safety Campaign Management System
 */

require_once '../config/database.php';
require_once '../utils/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

class AudienceSegmentationCRUD {
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
            case 'contacts':
                $this->getContacts();
                break;
            case 'segments':
                $this->getSegments();
                break;
            case 'segment_contacts':
                $this->getSegmentContacts();
                break;
            case 'contact_details':
                $this->getContactDetails();
                break;
            case 'segment_analytics':
                $this->getSegmentAnalytics();
                break;
            default:
                $this->getContacts();
        }
    }
    
    private function handlePost($action) {
        switch ($action) {
            case 'create_contact':
                $this->createContact();
                break;
            case 'create_segment':
                $this->createSegment();
                break;
            case 'add_to_segment':
                $this->addContactToSegment();
                break;
            case 'import_contacts':
                $this->importContacts();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handlePut($action) {
        switch ($action) {
            case 'update_contact':
                $this->updateContact();
                break;
            case 'update_segment':
                $this->updateSegment();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handleDelete($action) {
        switch ($action) {
            case 'delete_contact':
                $this->deleteContact();
                break;
            case 'delete_segment':
                $this->deleteSegment();
                break;
            case 'remove_from_segment':
                $this->removeFromSegment();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    // READ Operations
    private function getContacts() {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $search = $_GET['search'] ?? '';
        $segment = $_GET['segment'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($search) {
            $whereClause .= " AND (c.first_name LIKE :search OR c.last_name LIKE :search OR c.email LIKE :search OR c.phone LIKE :search)";
            $params['search'] = "%$search%";
        }
        
        if ($status) {
            $whereClause .= " AND c.status = :status";
            $params['status'] = $status;
        }
        
        if ($segment) {
            $whereClause .= " AND c.id IN (SELECT contact_id FROM segment_contacts WHERE segment_id = :segment)";
            $params['segment'] = $segment;
        }
        
        $query = "
            SELECT 
                c.*,
                (SELECT GROUP_CONCAT(as1.name) FROM segment_contacts sc 
                 JOIN audience_segments as1 ON sc.segment_id = as1.id 
                 WHERE sc.contact_id = c.id) as segments,
                (SELECT COUNT(*) FROM event_registrations WHERE contact_id = c.id) as events_attended,
                (SELECT COUNT(*) FROM survey_responses WHERE contact_id = c.id) as surveys_completed
            FROM contacts c
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
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM contacts c $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":$key", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $contacts,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'limit' => $limit
            ]
        ]);
    }
    
    private function getSegments() {
        $query = "
            SELECT 
                s.*,
                (SELECT COUNT(*) FROM segment_contacts WHERE segment_id = s.id) as contact_count,
                u.username as created_by_name
            FROM audience_segments s
            LEFT JOIN users u ON s.created_by = u.id
            ORDER BY s.created_at DESC
        ";
        
        $stmt = $this->db->query($query);
        $segments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $segments]);
    }
    
    private function getSegmentContacts() {
        $segmentId = $_GET['segment_id'] ?? null;
        if (!$segmentId) {
            throw new Exception('Segment ID is required');
        }
        
        $query = "
            SELECT 
                c.*,
                sc.added_at as segment_added_date
            FROM contacts c
            JOIN segment_contacts sc ON c.id = sc.contact_id
            WHERE sc.segment_id = :segment_id
            ORDER BY sc.added_at DESC
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':segment_id', $segmentId);
        $stmt->execute();
        
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $contacts]);
    }
    
    private function getContactDetails() {
        $contactId = $_GET['id'] ?? null;
        if (!$contactId) {
            throw new Exception('Contact ID is required');
        }
        
        $query = "SELECT * FROM contacts WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $contactId);
        $stmt->execute();
        
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contact) {
            throw new Exception('Contact not found');
        }
        
        // Get segments
        $segmentsQuery = "
            SELECT as1.* FROM audience_segments as1
            JOIN segment_contacts sc ON as1.id = sc.segment_id
            WHERE sc.contact_id = :contact_id
        ";
        $segmentsStmt = $this->db->prepare($segmentsQuery);
        $segmentsStmt->bindParam(':contact_id', $contactId);
        $segmentsStmt->execute();
        $contact['segments'] = $segmentsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recent activity
        $activityQuery = "
            SELECT 'event_registration' as type, er.created_at, e.title as description
            FROM event_registrations er 
            JOIN events e ON er.event_id = e.id 
            WHERE er.contact_id = :contact_id
            UNION ALL
            SELECT 'survey_response' as type, sr.submitted_at as created_at, s.title as description
            FROM survey_responses sr
            JOIN surveys s ON sr.survey_id = s.id
            WHERE sr.contact_id = :contact_id
            ORDER BY created_at DESC
            LIMIT 10
        ";
        $activityStmt = $this->db->prepare($activityQuery);
        $activityStmt->bindParam(':contact_id', $contactId);
        $activityStmt->execute();
        $contact['recent_activity'] = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $contact]);
    }
    
    private function getSegmentAnalytics() {
        $segmentId = $_GET['segment_id'] ?? null;
        if (!$segmentId) {
            throw new Exception('Segment ID is required');
        }
        
        $analytics = [];
        
        // Basic metrics
        $metricsQuery = "
            SELECT 
                COUNT(*) as total_contacts,
                COUNT(CASE WHEN c.status = 'active' THEN 1 END) as active_contacts,
                COUNT(CASE WHEN c.phone IS NOT NULL AND c.phone != '' THEN 1 END) as contacts_with_phone,
                COUNT(CASE WHEN c.email IS NOT NULL AND c.email != '' THEN 1 END) as contacts_with_email
            FROM segment_contacts sc
            JOIN contacts c ON sc.contact_id = c.id
            WHERE sc.segment_id = :segment_id
        ";
        $metricsStmt = $this->db->prepare($metricsQuery);
        $metricsStmt->bindParam(':segment_id', $segmentId);
        $metricsStmt->execute();
        $analytics['metrics'] = $metricsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Demographics
        $demographicsQuery = "
            SELECT 
                age_group,
                COUNT(*) as count
            FROM segment_contacts sc
            JOIN contacts c ON sc.contact_id = c.id
            WHERE sc.segment_id = :segment_id AND c.age_group IS NOT NULL
            GROUP BY age_group
        ";
        $demographicsStmt = $this->db->prepare($demographicsQuery);
        $demographicsStmt->bindParam(':segment_id', $segmentId);
        $demographicsStmt->execute();
        $analytics['demographics'] = $demographicsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $analytics]);
    }
    
    // CREATE Operations
    private function createContact() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Check if email already exists
        if (!empty($input['email'])) {
            $checkQuery = "SELECT id FROM contacts WHERE email = :email";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':email', $input['email']);
            $checkStmt->execute();
            
            if ($checkStmt->fetch()) {
                throw new Exception('Email already exists');
            }
        }
        
        $query = "
            INSERT INTO contacts (
                first_name, last_name, email, phone, address, city, state, zip_code,
                age_group, gender, occupation, interests, communication_preferences,
                language_preference, status, source, notes
            ) VALUES (
                :first_name, :last_name, :email, :phone, :address, :city, :state, :zip_code,
                :age_group, :gender, :occupation, :interests, :communication_preferences,
                :language_preference, :status, :source, :notes
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':first_name', $input['first_name']);
        $stmt->bindParam(':last_name', $input['last_name']);
        $stmt->bindParam(':email', $input['email'] ?? null);
        $stmt->bindParam(':phone', $input['phone'] ?? null);
        $stmt->bindParam(':address', $input['address'] ?? null);
        $stmt->bindParam(':city', $input['city'] ?? null);
        $stmt->bindParam(':state', $input['state'] ?? null);
        $stmt->bindParam(':zip_code', $input['zip_code'] ?? null);
        $stmt->bindParam(':age_group', $input['age_group'] ?? null);
        $stmt->bindParam(':gender', $input['gender'] ?? null);
        $stmt->bindParam(':occupation', $input['occupation'] ?? null);
        $stmt->bindValue(':interests', isset($input['interests']) ? json_encode($input['interests']) : null);
        $stmt->bindValue(':communication_preferences', isset($input['communication_preferences']) ? json_encode($input['communication_preferences']) : null);
        $stmt->bindParam(':language_preference', $input['language_preference'] ?? 'english');
        $stmt->bindParam(':status', $input['status'] ?? 'active');
        $stmt->bindParam(':source', $input['source'] ?? 'manual');
        $stmt->bindParam(':notes', $input['notes'] ?? null);
        
        if ($stmt->execute()) {
            $contactId = $this->db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Contact created successfully',
                'contact_id' => $contactId
            ]);
        } else {
            throw new Exception('Failed to create contact');
        }
    }
    
    private function createSegment() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['name']) || empty($input['created_by'])) {
            throw new Exception('Name and created_by are required');
        }
        
        $query = "
            INSERT INTO audience_segments (
                name, description, criteria, segment_type, status, created_by
            ) VALUES (
                :name, :description, :criteria, :segment_type, :status, :created_by
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $input['name']);
        $stmt->bindParam(':description', $input['description'] ?? null);
        $stmt->bindValue(':criteria', isset($input['criteria']) ? json_encode($input['criteria']) : null);
        $stmt->bindParam(':segment_type', $input['segment_type'] ?? 'manual');
        $stmt->bindParam(':status', $input['status'] ?? 'active');
        $stmt->bindParam(':created_by', $input['created_by']);
        
        if ($stmt->execute()) {
            $segmentId = $this->db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'Segment created successfully',
                'segment_id' => $segmentId
            ]);
        } else {
            throw new Exception('Failed to create segment');
        }
    }
    
    private function addContactToSegment() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['contact_id']) || empty($input['segment_id'])) {
            throw new Exception('Contact ID and Segment ID are required');
        }
        
        // Check if already exists
        $checkQuery = "SELECT id FROM segment_contacts WHERE contact_id = :contact_id AND segment_id = :segment_id";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':contact_id', $input['contact_id']);
        $checkStmt->bindParam(':segment_id', $input['segment_id']);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            throw new Exception('Contact already in segment');
        }
        
        $query = "INSERT INTO segment_contacts (contact_id, segment_id) VALUES (:contact_id, :segment_id)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':contact_id', $input['contact_id']);
        $stmt->bindParam(':segment_id', $input['segment_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Contact added to segment successfully']);
        } else {
            throw new Exception('Failed to add contact to segment');
        }
    }
    
    private function importContacts() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['contacts']) || !is_array($input['contacts'])) {
            throw new Exception('Contacts array is required');
        }
        
        $imported = 0;
        $errors = [];
        
        foreach ($input['contacts'] as $index => $contactData) {
            try {
                if (empty($contactData['first_name']) || empty($contactData['last_name'])) {
                    throw new Exception('First name and last name are required');
                }
                
                $query = "
                    INSERT INTO contacts (
                        first_name, last_name, email, phone, address, city, state, zip_code,
                        age_group, gender, occupation, language_preference, status, source
                    ) VALUES (
                        :first_name, :last_name, :email, :phone, :address, :city, :state, :zip_code,
                        :age_group, :gender, :occupation, :language_preference, :status, :source
                    )
                ";
                
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':first_name', $contactData['first_name']);
                $stmt->bindParam(':last_name', $contactData['last_name']);
                $stmt->bindParam(':email', $contactData['email'] ?? null);
                $stmt->bindParam(':phone', $contactData['phone'] ?? null);
                $stmt->bindParam(':address', $contactData['address'] ?? null);
                $stmt->bindParam(':city', $contactData['city'] ?? null);
                $stmt->bindParam(':state', $contactData['state'] ?? null);
                $stmt->bindParam(':zip_code', $contactData['zip_code'] ?? null);
                $stmt->bindParam(':age_group', $contactData['age_group'] ?? null);
                $stmt->bindParam(':gender', $contactData['gender'] ?? null);
                $stmt->bindParam(':occupation', $contactData['occupation'] ?? null);
                $stmt->bindParam(':language_preference', $contactData['language_preference'] ?? 'english');
                $stmt->bindParam(':status', $contactData['status'] ?? 'active');
                $stmt->bindParam(':source', $contactData['source'] ?? 'import');
                
                if ($stmt->execute()) {
                    $imported++;
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Failed to insert";
                }
                
            } catch (Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Imported $imported contacts",
            'imported_count' => $imported,
            'errors' => $errors
        ]);
    }
    
    // UPDATE Operations
    private function updateContact() {
        $input = json_decode(file_get_contents('php://input'), true);
        $contactId = $input['id'] ?? null;
        
        if (!$contactId) {
            throw new Exception('Contact ID is required');
        }
        
        $updateFields = [];
        $params = ['id' => $contactId];
        
        $allowedFields = [
            'first_name', 'last_name', 'email', 'phone', 'address', 'city', 'state', 'zip_code',
            'age_group', 'gender', 'occupation', 'interests', 'communication_preferences',
            'language_preference', 'status', 'notes'
        ];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if (in_array($field, ['interests', 'communication_preferences'])) {
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
        
        $query = "UPDATE contacts SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Contact updated successfully']);
        } else {
            throw new Exception('Failed to update contact');
        }
    }
    
    private function updateSegment() {
        $input = json_decode(file_get_contents('php://input'), true);
        $segmentId = $input['id'] ?? null;
        
        if (!$segmentId) {
            throw new Exception('Segment ID is required');
        }
        
        $updateFields = [];
        $params = ['id' => $segmentId];
        
        $allowedFields = ['name', 'description', 'criteria', 'segment_type', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'criteria') {
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
        
        $query = "UPDATE audience_segments SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'Segment updated successfully']);
        } else {
            throw new Exception('Failed to update segment');
        }
    }
    
    // DELETE Operations
    private function deleteContact() {
        $contactId = $_GET['id'] ?? null;
        if (!$contactId) {
            throw new Exception('Contact ID is required');
        }
        
        // Check for dependencies
        $checkQuery = "
            SELECT 
                (SELECT COUNT(*) FROM event_registrations WHERE contact_id = :contact_id) as events,
                (SELECT COUNT(*) FROM survey_responses WHERE contact_id = :contact_id) as surveys
        ";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':contact_id', $contactId);
        $checkStmt->execute();
        $dependencies = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dependencies['events'] > 0 || $dependencies['surveys'] > 0) {
            // Soft delete
            $query = "UPDATE contacts SET status = 'deleted', updated_at = NOW() WHERE id = :id";
        } else {
            // Hard delete - remove from segments first
            $this->db->prepare("DELETE FROM segment_contacts WHERE contact_id = ?")->execute([$contactId]);
            $query = "DELETE FROM contacts WHERE id = :id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $contactId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Contact deleted successfully']);
        } else {
            throw new Exception('Failed to delete contact');
        }
    }
    
    private function deleteSegment() {
        $segmentId = $_GET['id'] ?? null;
        if (!$segmentId) {
            throw new Exception('Segment ID is required');
        }
        
        // Remove all contacts from segment first
        $this->db->prepare("DELETE FROM segment_contacts WHERE segment_id = ?")->execute([$segmentId]);
        
        // Delete segment
        $query = "DELETE FROM audience_segments WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $segmentId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Segment deleted successfully']);
        } else {
            throw new Exception('Failed to delete segment');
        }
    }
    
    private function removeFromSegment() {
        $contactId = $_GET['contact_id'] ?? null;
        $segmentId = $_GET['segment_id'] ?? null;
        
        if (!$contactId || !$segmentId) {
            throw new Exception('Contact ID and Segment ID are required');
        }
        
        $query = "DELETE FROM segment_contacts WHERE contact_id = :contact_id AND segment_id = :segment_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':contact_id', $contactId);
        $stmt->bindParam(':segment_id', $segmentId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Contact removed from segment successfully']);
        } else {
            throw new Exception('Failed to remove contact from segment');
        }
    }
}

// Handle the request
$audienceSegmentationCRUD = new AudienceSegmentationCRUD();
$audienceSegmentationCRUD->handleRequest();
?>
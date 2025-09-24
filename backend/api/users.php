<?php
/**
 * Users CRUD Operations
 * Public Safety Campaign Management System
 */

require_once '../config/database.php';
require_once '../utils/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

class UsersCRUD {
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
                $this->getUsers();
                break;
            case 'details':
                $this->getUserDetails();
                break;
            case 'roles':
                $this->getUserRoles();
                break;
            default:
                $this->getUsers();
        }
    }
    
    private function handlePost($action) {
        switch ($action) {
            case 'create':
                $this->createUser();
                break;
            case 'login':
                $this->loginUser();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handlePut($action) {
        switch ($action) {
            case 'update':
                $this->updateUser();
                break;
            case 'password':
                $this->updatePassword();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    private function handleDelete($action) {
        switch ($action) {
            case 'delete':
                $this->deleteUser();
                break;
            default:
                throw new Exception('Invalid action');
        }
    }
    
    // READ Operations
    private function getUsers() {
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if ($search) {
            $whereClause .= " AND (u.username LIKE :search OR u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
            $params['search'] = "%$search%";
        }
        
        if ($role) {
            $whereClause .= " AND ur.role_name = :role";
            $params['role'] = $role;
        }
        
        $query = "
            SELECT 
                u.id, u.username, u.email, u.first_name, u.last_name, 
                u.phone, u.department, u.position, u.status, u.created_at,
                ur.role_name, ur.permissions
            FROM users u 
            LEFT JOIN user_roles ur ON u.role_id = ur.id 
            $whereClause
            ORDER BY u.created_at DESC 
            LIMIT :limit OFFSET :offset
        ";
        
        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM users u LEFT JOIN user_roles ur ON u.role_id = ur.id $whereClause";
        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $countStmt->bindValue(":$key", $value);
        }
        $countStmt->execute();
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        echo json_encode([
            'success' => true,
            'data' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($total / $limit),
                'total_records' => $total,
                'limit' => $limit
            ]
        ]);
    }
    
    private function getUserDetails() {
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            throw new Exception('User ID is required');
        }
        
        $query = "
            SELECT 
                u.*, ur.role_name, ur.permissions
            FROM users u 
            LEFT JOIN user_roles ur ON u.role_id = ur.id 
            WHERE u.id = :id
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $userId);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Get user's recent activity
        $activityQuery = "
            SELECT * FROM activity_logs 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT 10
        ";
        $activityStmt = $this->db->prepare($activityQuery);
        $activityStmt->bindParam(':user_id', $userId);
        $activityStmt->execute();
        $user['recent_activity'] = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $user]);
    }
    
    private function getUserRoles() {
        $query = "SELECT * FROM user_roles ORDER BY role_name";
        $stmt = $this->db->query($query);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $roles]);
    }
    
    // CREATE Operations
    private function createUser() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['username', 'email', 'password', 'first_name', 'last_name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }
        
        // Check if username or email already exists
        $checkQuery = "SELECT id FROM users WHERE username = :username OR email = :email";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $input['username']);
        $checkStmt->bindParam(':email', $input['email']);
        $checkStmt->execute();
        
        if ($checkStmt->fetch()) {
            throw new Exception('Username or email already exists');
        }
        
        $query = "
            INSERT INTO users (
                username, email, password_hash, first_name, last_name, 
                phone, department, position, role_id, status
            ) VALUES (
                :username, :email, :password, :first_name, :last_name, 
                :phone, :department, :position, :role_id, :status
            )
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $input['username']);
        $stmt->bindParam(':email', $input['email']);
        $stmt->bindValue(':password', password_hash($input['password'], PASSWORD_DEFAULT));
        $stmt->bindParam(':first_name', $input['first_name']);
        $stmt->bindParam(':last_name', $input['last_name']);
        $stmt->bindParam(':phone', $input['phone'] ?? null);
        $stmt->bindParam(':department', $input['department'] ?? null);
        $stmt->bindParam(':position', $input['position'] ?? null);
        $stmt->bindParam(':role_id', $input['role_id'] ?? 2); // Default role
        $stmt->bindParam(':status', $input['status'] ?? 'active');
        
        if ($stmt->execute()) {
            $userId = $this->db->lastInsertId();
            echo json_encode([
                'success' => true, 
                'message' => 'User created successfully',
                'user_id' => $userId
            ]);
        } else {
            throw new Exception('Failed to create user');
        }
    }
    
    private function loginUser() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['email']) || empty($input['password'])) {
            throw new Exception('Email and password are required');
        }
        
        $query = "
            SELECT 
                u.id, u.username, u.email, u.password_hash, u.status,
                u.first_name, u.last_name, ur.role_name, ur.permissions
            FROM users u 
            LEFT JOIN user_roles ur ON u.role_id = ur.id 
            WHERE u.email = :email
        ";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $input['email']);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($input['password'], $user['password_hash'])) {
            throw new Exception('Invalid email or password');
        }
        
        if ($user['status'] !== 'active') {
            throw new Exception('Account is not active');
        }
        
        // Create session
        $sessionToken = bin2hex(random_bytes(32));
        $sessionQuery = "
            INSERT INTO user_sessions (user_id, session_token, expires_at, ip_address, user_agent) 
            VALUES (:user_id, :token, DATE_ADD(NOW(), INTERVAL 24 HOUR), :ip, :user_agent)
        ";
        $sessionStmt = $this->db->prepare($sessionQuery);
        $sessionStmt->bindParam(':user_id', $user['id']);
        $sessionStmt->bindParam(':token', $sessionToken);
        $sessionStmt->bindParam(':ip', $_SERVER['REMOTE_ADDR']);
        $sessionStmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        $sessionStmt->execute();
        
        unset($user['password_hash']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'session_token' => $sessionToken
        ]);
    }
    
    // UPDATE Operations
    private function updateUser() {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['id'] ?? null;
        
        if (!$userId) {
            throw new Exception('User ID is required');
        }
        
        $updateFields = [];
        $params = ['id' => $userId];
        
        $allowedFields = ['username', 'email', 'first_name', 'last_name', 'phone', 'department', 'position', 'role_id', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = :$field";
                $params[$field] = $input[$field];
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception('No fields to update');
        }
        
        $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
        $stmt = $this->db->prepare($query);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } else {
            throw new Exception('Failed to update user');
        }
    }
    
    private function updatePassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        
        if (!$userId || empty($input['new_password'])) {
            throw new Exception('User ID and new password are required');
        }
        
        $query = "UPDATE users SET password_hash = :password WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':password', password_hash($input['new_password'], PASSWORD_DEFAULT));
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
        } else {
            throw new Exception('Failed to update password');
        }
    }
    
    // DELETE Operations
    private function deleteUser() {
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            throw new Exception('User ID is required');
        }
        
        // Check if user has dependencies
        $checkQuery = "
            SELECT 
                (SELECT COUNT(*) FROM campaigns WHERE created_by = :user_id) as campaigns,
                (SELECT COUNT(*) FROM surveys WHERE created_by = :user_id) as surveys
        ";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':user_id', $userId);
        $checkStmt->execute();
        $dependencies = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($dependencies['campaigns'] > 0 || $dependencies['surveys'] > 0) {
            // Soft delete instead of hard delete
            $query = "UPDATE users SET status = 'deleted', deleted_at = NOW() WHERE id = :id";
        } else {
            // Hard delete
            $query = "DELETE FROM users WHERE id = :id";
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
        } else {
            throw new Exception('Failed to delete user');
        }
    }
}

// Handle the request
$usersCRUD = new UsersCRUD();
$usersCRUD->handleRequest();
?>
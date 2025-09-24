<?php
/**
 * Authentication API
 * Handles user login, logout, and session management
 */

require_once '../config/cors.php';
require_once '../config/database.php';
require_once '../utils/helpers.php';

class AuthAPI {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';
        
        switch ($method) {
            case 'POST':
                switch ($action) {
                    case 'login':
                        $this->login();
                        break;
                    case 'logout':
                        $this->logout();
                        break;
                    case 'refresh':
                        $this->refreshToken();
                        break;
                    default:
                        errorResponse('Invalid action', 400);
                }
                break;
            case 'GET':
                switch ($action) {
                    case 'profile':
                        $this->getProfile();
                        break;
                    case 'verify':
                        $this->verifyToken();
                        break;
                    default:
                        errorResponse('Invalid action', 400);
                }
                break;
            default:
                errorResponse('Method not allowed', 405);
        }
    }
    
    private function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['email']) || !isset($input['password'])) {
            errorResponse('Email and password are required');
        }
        
        $email = sanitizeInput($input['email']);
        $password = $input['password'];
        
        if (!validateEmail($email)) {
            errorResponse('Invalid email format');
        }
        
        try {
            $query = "SELECT id, first_name, last_name, email, password, role, department, 
                             phone, location, timezone, language, email_notifications, 
                             push_notifications, sms_notifications, avatar 
                      FROM users WHERE email = ? LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (verifyPassword($password, $user['password'])) {
                    // Update last login
                    $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = ?";
                    $updateStmt = $this->conn->prepare($updateQuery);
                    $updateStmt->execute([$user['id']]);
                    
                    // Generate token
                    $token = generateToken($user['id']);
                    
                    // Remove password from response
                    unset($user['password']);
                    
                    // Generate avatar initials if no avatar
                    if (empty($user['avatar'])) {
                        $user['avatar'] = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                    }
                    
                    successResponse([
                        'token' => $token,
                        'user' => $user
                    ], 'Login successful');
                } else {
                    errorResponse('Invalid credentials', 401);
                }
            } else {
                errorResponse('Invalid credentials', 401);
            }
        } catch (Exception $e) {
            errorResponse('Login failed: ' . $e->getMessage(), 500);
        }
    }
    
    private function logout() {
        // In a more complex system, you might want to blacklist the token
        successResponse([], 'Logged out successfully');
    }
    
    private function getProfile() {
        $token = $this->getAuthToken();
        if (!$token) {
            errorResponse('Authentication required', 401);
        }
        
        $payload = verifyToken($token);
        if (!$payload) {
            errorResponse('Invalid token', 401);
        }
        
        try {
            $query = "SELECT id, first_name, last_name, email, role, department, 
                             phone, location, timezone, language, email_notifications, 
                             push_notifications, sms_notifications, avatar, last_login 
                      FROM users WHERE id = ? LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$payload['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Generate avatar initials if no avatar
                if (empty($user['avatar'])) {
                    $user['avatar'] = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                }
                
                successResponse($user);
            } else {
                errorResponse('User not found', 404);
            }
        } catch (Exception $e) {
            errorResponse('Error fetching profile: ' . $e->getMessage(), 500);
        }
    }
    
    private function verifyToken() {
        $token = $this->getAuthToken();
        if (!$token) {
            errorResponse('Token required', 400);
        }
        
        $payload = verifyToken($token);
        if ($payload) {
            successResponse(['valid' => true, 'user_id' => $payload['user_id']]);
        } else {
            errorResponse('Invalid token', 401);
        }
    }
    
    private function refreshToken() {
        $token = $this->getAuthToken();
        if (!$token) {
            errorResponse('Token required', 400);
        }
        
        $payload = verifyToken($token);
        if ($payload) {
            $newToken = generateToken($payload['user_id']);
            successResponse(['token' => $newToken], 'Token refreshed');
        } else {
            errorResponse('Invalid token', 401);
        }
    }
    
    private function getAuthToken() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            return str_replace('Bearer ', '', $headers['Authorization']);
        }
        
        if (isset($_GET['token'])) {
            return $_GET['token'];
        }
        
        return null;
    }
}

// Handle the request
$api = new AuthAPI();
$api->handleRequest();
?>
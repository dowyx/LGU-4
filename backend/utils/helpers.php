<?php
/**
 * Helper Functions
 * Common utility functions for the application
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data);
    exit();
}

/**
 * Generate error response
 */
function errorResponse($message, $status_code = 400) {
    jsonResponse([
        'success' => false,
        'message' => $message
    ], $status_code);
}

/**
 * Generate success response
 */
function successResponse($data = [], $message = 'Success') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate JWT token (simple implementation)
 */
function generateToken($user_id) {
    $payload = [
        'user_id' => $user_id,
        'timestamp' => time(),
        'expires' => time() + (24 * 60 * 60) // 24 hours
    ];
    return base64_encode(json_encode($payload));
}

/**
 * Verify JWT token
 */
function verifyToken($token) {
    try {
        $payload = json_decode(base64_decode($token), true);
        if ($payload && isset($payload['expires']) && $payload['expires'] > time()) {
            return $payload;
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get current timestamp for database
 */
function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'M j, Y') {
    return date($format, strtotime($date));
}

/**
 * Calculate percentage
 */
function calculatePercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 1);
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}
?>
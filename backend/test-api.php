<?php
/**
 * Simple API Test Script
 * Verify that PHP backend and database connections are working
 */

require_once 'config/cors.php';
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    // Test database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Test basic queries
    $tests = [
        'database_connection' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion(),
        'tables' => []
    ];
    
    // Check if required tables exist
    $requiredTables = ['users', 'campaigns', 'events', 'content', 'notifications'];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $tests['tables'][$table] = [
                'exists' => true,
                'count' => (int)$count
            ];
        } catch (Exception $e) {
            $tests['tables'][$table] = [
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Test sample data
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE email LIKE '%@safetycampaign.org'");
        $demoUsers = $stmt->fetchColumn();
        $tests['demo_users'] = (int)$demoUsers;
    } catch (Exception $e) {
        $tests['demo_users'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'API test completed successfully',
        'data' => $tests
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
<?php
/**
 * Test Database Connection and User Data
 */

require_once 'backend/config/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Test users table
        $query = "SELECT COUNT(*) as count FROM users";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Total users in database: " . $result['count'] . "</p>";
        
        // Test specific user
        $query = "SELECT id, first_name, last_name, email, role FROM users WHERE email = 'demo@safetycampaign.org' LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✓ Demo user found:</p>";
            echo "<pre>";
            print_r($user);
            echo "</pre>";
        } else {
            echo "<p style='color: red;'>✗ Demo user not found</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<h2>Session Test</h2>";
session_start();

if (isset($_SESSION)) {
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<p>Session data:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "<p>No session data</p>";
}
?>
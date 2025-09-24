<?php
require_once 'backend/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "✅ Database connection successful\n\n";
        
        // Get all tables
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "📋 Database tables:\n";
        echo "----------------\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
        
        // Check for specific tables needed by dashboard API
        $requiredTables = ['campaigns', 'events', 'content', 'survey_responses', 'notifications'];
        echo "\n🔍 Required tables check:\n";
        echo "----------------------\n";
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $tables)) {
                echo "✅ $table exists\n";
            } else {
                echo "❌ $table does not exist\n";
            }
        }
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
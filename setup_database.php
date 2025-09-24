<?php
/**
 * Database Setup Script
 * This script will create the database and import all tables
 */

echo "Starting database setup...\n";

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "public_safety_campaigns";

try {
    // Connect to MySQL without selecting a database
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create the database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sql);
    echo "Database '$database' created or already exists\n";
    
    // Select the database
    $conn->exec("USE `$database`");
    
    // Import all SQL files from the database directory
    $sqlFiles = [
        '00_master_setup.sql',
        '01_users_login.sql',
        '02_campaign_planning.sql',
        '03_content_repository.sql',
        '04_audience_segmentation.sql',
        '05_event_management.sql',
        '06_feedback_surveys.sql',
        '07_impact_monitoring.sql',
        '08_collaboration.sql'
    ];
    
    foreach ($sqlFiles as $file) {
        $filePath = __DIR__ . '/database/' . $file;
        if (file_exists($filePath)) {
            $sql = file_get_contents($filePath);
            $conn->exec($sql);
            echo "Imported: $file\n";
        } else {
            echo "Warning: File not found - $file\n";
        }
    }
    
    echo "\nDatabase setup completed successfully!\n";
    echo "You can now access the application.\n";
    
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>

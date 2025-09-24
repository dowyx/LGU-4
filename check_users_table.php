<?php
require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if users table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "Users table exists.\n";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE users");
        echo "Table structure:\n";
        echo "----------------\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Field: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
        }
        
        // Check if there are any users
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\nTotal users: " . $count . "\n";
        
        if ($count > 0) {
            // Show first user (without password)
            $stmt = $conn->query("SELECT id, first_name, last_name, email, role FROM users LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "\nSample user:\n";
            print_r($user);
        }
    } else {
        echo "Users table does not exist.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check if we can connect to the database
try {
    $testDb = new Database();
    $testConn = $testDb->getConnection();
    if ($testConn) {
        echo "\nDatabase connection successful!\n";
    } else {
        echo "\nDatabase connection failed.\n";
    }
} catch (Exception $e) {
    echo "\nDatabase connection error: " . $e->getMessage() . "\n";
}
?>

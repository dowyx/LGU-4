<?php
require_once 'backend/config/database.php';

header('Content-Type: text/plain');

try {
    // Test database connection
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✅ Successfully connected to the database.\n\n";
        
        // Check if users table exists
        $stmt = $conn->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Users table exists.\n";
            
            // Get table structure
            $stmt = $conn->query("DESCRIBE users");
            echo "\n📋 Table structure:\n";
            echo "----------------\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "- " . $row['Field'] . " (" . $row['Type'] . ")";
                if ($row['Key'] === 'PRI') echo " [PRIMARY KEY]";
                if ($row['Null'] === 'NO') echo " [NOT NULL]";
                if ($row['Default'] !== null) echo " [DEFAULT: " . $row['Default'] . "]";
                echo "\n";
            }
            
            // Check if there are any users
            $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "\n👥 Total users: " . $count . "\n";
            
            if ($count > 0) {
                // Show first user (without password)
                $stmt = $conn->query("SELECT id, first_name, last_name, email, role, created_at FROM users LIMIT 1");
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "\n👤 Sample user:\n";
                foreach ($user as $key => $value) {
                    echo "- $key: " . ($value === null ? 'NULL' : $value) . "\n";
                }
            } else {
                echo "\nℹ️ No users found in the database.\n";
            }
        } else {
            echo "❌ Users table does not exist in the database.\n";
        }
    } else {
        echo "❌ Failed to connect to the database.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

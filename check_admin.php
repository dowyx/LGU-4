<?php
require_once 'backend/config/database.php';

header('Content-Type: text/plain');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== Database Connection Test ===\n";
    echo "Connected to database successfully!\n\n";
    
    // Check if users table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists\n";
        
        // Show table structure
        $stmt = $conn->query("DESCRIBE users");
        echo "\n=== Table Structure ===\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")";
            if ($row['Key'] === 'PRI') echo " [PRIMARY KEY]";
            if ($row['Null'] === 'NO') echo " [NOT NULL]";
            if ($row['Default'] !== null) echo " [DEFAULT: " . $row['Default'] . "]";
            echo "\n";
        }
        
        // Check if admin user exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = 'admin'");
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "\n=== Admin User Found ===\n";
            foreach ($admin as $key => $value) {
                if ($key === 'password') {
                    echo "- $key: " . (empty($value) ? 'EMPTY' : 'HASHED_PASSWORD') . "\n";
                } else {
                    echo "- $key: " . ($value === null ? 'NULL' : $value) . "\n";
                }
            }
            
            // Test password verification
            $testPassword = 'admin123';
            if (password_verify($testPassword, $admin['password'])) {
                echo "\n✅ Password verification SUCCESSFUL with 'admin123'\n";
            } else {
                echo "\n❌ Password verification FAILED with 'admin123'\n";
                echo "- Try creating a new password hash and updating the admin user\n";
                $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                echo "- New password hash: " . $newHash . "\n";
                
                // Uncomment to automatically update the password
                /*
                $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
                if ($updateStmt->execute([$newHash])) {
                    echo "✅ Admin password updated successfully!\n";
                } else {
                    echo "❌ Failed to update admin password\n";
                }
                */
            }
        } else {
            echo "\n❌ No admin user found in the database\n";
            
            // Create admin user if not exists
            $password = 'admin123';
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $createAdmin = $conn->prepare("
                INSERT INTO users (username, password, email, first_name, last_name, role, created_at, updated_at)
                VALUES ('admin', :password, 'admin@example.com', 'Admin', 'User', 'admin', NOW(), NOW())
            ");
            
            try {
                $createAdmin->execute([':password' => $hashedPassword]);
                echo "✅ Admin user created successfully!\n";
                echo "- Username: admin\n";
                echo "- Password: admin123\n";
            } catch (PDOException $e) {
                echo "❌ Failed to create admin user: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "❌ Users table does not exist in the database\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>

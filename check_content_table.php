<?php
require_once 'backend/config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "✅ Database connection successful\n\n";
        
        // Get content_items table structure
        $stmt = $conn->query("DESCRIBE content_items");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📋 content_items table structure:\n";
        echo "-------------------------------\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})";
            if ($column['Key'] == 'PRI') echo " [PRIMARY KEY]";
            if ($column['Null'] == 'NO') echo " [NOT NULL]";
            if (!empty($column['Default'])) echo " [DEFAULT: {$column['Default']}]";
            echo "\n";
        }
        
        echo "\n🔍 Checking for views_count column...\n";
        $hasViewsCount = false;
        foreach ($columns as $column) {
            if ($column['Field'] == 'views_count') {
                $hasViewsCount = true;
                break;
            }
        }
        
        if ($hasViewsCount) {
            echo "✅ views_count column exists\n";
        } else {
            echo "❌ views_count column does not exist\n";
            echo "We'll need to modify the query to use an alternative approach\n";
        }
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
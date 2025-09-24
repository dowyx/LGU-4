<?php
// Test script to verify dashboard API functionality
require_once 'backend/config/database.php';

echo "=== Dashboard API Test ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($conn) {
        echo "✅ Database connection successful\n\n";
        
        // Test statistics query
        echo "Test 1: Statistics query...\n";
        $query = "SELECT 
                    (SELECT COUNT(*) FROM campaigns WHERE status = 'active') as active_campaigns,
                    (SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()) as upcoming_events,
                    (SELECT COUNT(*) FROM content_items) as content_items,
                    (SELECT COUNT(*) FROM survey_responses WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_responses";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Statistics query successful\n";
        print_r($stats);
        
        // Test campaigns query
        echo "\nTest 2: Campaigns query...\n";
        $query = "SELECT id, name, status, start_date, end_date, progress, engagement_rate, created_at
                  FROM campaigns 
                  ORDER BY created_at DESC 
                  LIMIT 5";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Campaigns query successful\n";
        echo "Found " . count($campaigns) . " campaigns\n";
        
        // Test events query
        echo "\nTest 3: Events query...\n";
        $query = "SELECT e.id, e.title, e.event_date, e.location, e.registered_count, c.name as campaign_name
                  FROM events e
                  LEFT JOIN campaigns c ON e.campaign_id = c.id
                  WHERE e.event_date >= CURDATE()
                  ORDER BY e.event_date ASC 
                  LIMIT 5";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Events query successful\n";
        echo "Found " . count($events) . " upcoming events\n";
        
        // Test content performance query
        echo "\nTest 4: Content performance query...\n";
        $query = "SELECT 
                    category,
                    COUNT(*) as count,
                    AVG(views_count) as avg_views,
                    SUM(views_count) as total_views
                  FROM content_items
                  GROUP BY category";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $contentPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✅ Content performance query successful\n";
        echo "Found " . count($contentPerformance) . " content categories\n";
        
        echo "\n🎉 All dashboard API tests passed!\n";
        echo "The dashboard API should now work correctly.\n";
        
    } else {
        echo "❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
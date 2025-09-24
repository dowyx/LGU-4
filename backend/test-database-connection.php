<?php
/**
 * Database Connection Test for Public Safety Campaign Management System
 * Tests all imported tables and displays connection status
 */

require_once 'config/database.php';

header('Content-Type: application/json');

function testDatabaseConnection() {
    $database = new Database();
    $db = $database->getConnection();
    
    $result = [
        'status' => 'success',
        'database' => 'lgu_public',
        'connection' => 'established',
        'tables' => [],
        'sample_data' => [],
        'errors' => []
    ];
    
    try {
        if (!$db) {
            throw new Exception('Failed to connect to database');
        }
        
        // List of expected tables based on your imported SQL files
        $expectedTables = [
            // Users & Authentication (01_users_login.sql)
            'users', 'user_roles', 'user_permissions', 'user_sessions',
            
            // Campaign Planning (02_campaign_planning.sql)
            'campaigns', 'campaign_objectives', 'campaign_milestones', 'campaign_collaborators',
            
            // Content Repository (03_content_repository.sql)
            'content_items', 'content_categories', 'content_tags', 'content_versions',
            
            // Audience Segmentation (04_audience_segmentation.sql)
            'contacts', 'audience_segments', 'segment_contacts', 'contact_groups',
            
            // Event Management (05_event_management.sql)
            'events', 'event_registrations', 'event_sessions', 'event_feedback',
            
            // Feedback & Surveys (06_feedback_surveys.sql)
            'surveys', 'survey_questions', 'survey_responses', 'sms_alerts',
            
            // Impact Monitoring (07_impact_monitoring.sql)
            'impact_metrics', 'metric_data', 'reports', 'analytics_dashboards',
            
            // Collaboration (08_collaboration.sql)
            'projects', 'team_members', 'project_assignments', 'shared_resources',
            'collaboration_spaces', 'activity_logs', 'notifications'
        ];
        
        // Check which tables exist
        $stmt = $db->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($expectedTables as $table) {
            $tableExists = in_array($table, $existingTables);
            $result['tables'][$table] = [
                'exists' => $tableExists,
                'status' => $tableExists ? 'OK' : 'MISSING'
            ];
            
            // Get record count for existing tables
            if ($tableExists) {
                try {
                    $countStmt = $db->query("SELECT COUNT(*) as count FROM `$table`");
                    $count = $countStmt->fetch(PDO::FETCH_ASSOC);
                    $result['tables'][$table]['record_count'] = $count['count'];
                } catch (Exception $e) {
                    $result['tables'][$table]['record_count'] = 'Error: ' . $e->getMessage();
                }
            }
        }
        
        // Test sample data from key tables
        $sampleQueries = [
            'users' => "SELECT id, username, email, role FROM users LIMIT 3",
            'campaigns' => "SELECT id, title, status, start_date FROM campaigns LIMIT 3",
            'surveys' => "SELECT id, title, survey_type, status FROM surveys LIMIT 3"
        ];
        
        foreach ($sampleQueries as $table => $query) {
            if (in_array($table, $existingTables)) {
                try {
                    $stmt = $db->query($query);
                    $result['sample_data'][$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $result['sample_data'][$table] = 'Error: ' . $e->getMessage();
                }
            }
        }
        
        // Check database charset and collation
        $stmt = $db->query("SELECT DEFAULT_CHARACTER_SET_NAME, DEFAULT_COLLATION_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'lgu_public'");
        $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['database_info'] = $dbInfo;
        
    } catch (Exception $e) {
        $result['status'] = 'error';
        $result['connection'] = 'failed';
        $result['errors'][] = $e->getMessage();
    }
    
    return $result;
}

// Execute the test
$testResult = testDatabaseConnection();

// Output results
echo json_encode($testResult, JSON_PRETTY_PRINT);

// Also create an HTML version for browser viewing
if (isset($_GET['format']) && $_GET['format'] === 'html') {
    echo "<html><head><title>Database Connection Test</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-ok { background-color: #d4edda; }
        .status-missing { background-color: #f8d7da; }
    </style></head><body>";
    
    echo "<h1>Database Connection Test Results</h1>";
    
    if ($testResult['status'] === 'success') {
        echo "<p class='success'>✓ Successfully connected to database: " . $testResult['database'] . "</p>";
        
        echo "<h2>Database Information</h2>";
        if (isset($testResult['database_info'])) {
            echo "<p>Character Set: " . $testResult['database_info']['DEFAULT_CHARACTER_SET_NAME'] . "</p>";
            echo "<p>Collation: " . $testResult['database_info']['DEFAULT_COLLATION_NAME'] . "</p>";
        }
        
        echo "<h2>Table Status</h2>";
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Status</th><th>Record Count</th></tr>";
        
        foreach ($testResult['tables'] as $table => $info) {
            $statusClass = $info['exists'] ? 'status-ok' : 'status-missing';
            echo "<tr class='$statusClass'>";
            echo "<td>$table</td>";
            echo "<td>" . ($info['exists'] ? '✓ EXISTS' : '✗ MISSING') . "</td>";
            echo "<td>" . (isset($info['record_count']) ? $info['record_count'] : 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (!empty($testResult['sample_data'])) {
            echo "<h2>Sample Data</h2>";
            foreach ($testResult['sample_data'] as $table => $data) {
                echo "<h3>$table</h3>";
                if (is_array($data) && !empty($data)) {
                    echo "<table>";
                    echo "<tr>";
                    foreach (array_keys($data[0]) as $column) {
                        echo "<th>$column</th>";
                    }
                    echo "</tr>";
                    foreach ($data as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value) . "</td>";
                        }
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>No data or error: " . (is_string($data) ? $data : 'Unknown error') . "</p>";
                }
            }
        }
        
    } else {
        echo "<p class='error'>✗ Database connection failed</p>";
        foreach ($testResult['errors'] as $error) {
            echo "<p class='error'>Error: $error</p>";
        }
    }
    
    echo "</body></html>";
}
?>
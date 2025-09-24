<?php
/**
 * Database Setup Script
 * Run this file once to create all necessary tables
 * Access via: http://localhost/your_project/backend/setup/install.php
 */

require_once '../config/database.php';

class DatabaseSetup {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        
        // First create the database
        if ($database->createDatabase()) {
            echo "<p>✅ Database created successfully</p>";
        }
        
        // Then connect to it
        $this->conn = $database->getConnection();
    }
    
    public function createTables() {
        echo "<h2>Creating Tables...</h2>";
        
        // Users table
        $this->createUsersTable();
        
        // Campaigns table
        $this->createCampaignsTable();
        
        // Events table
        $this->createEventsTable();
        
        // Content table
        $this->createContentTable();
        
        // Notifications table
        $this->createNotificationsTable();
        
        // Audience segments table
        $this->createAudienceSegmentsTable();
        
        // Survey responses table
        $this->createSurveyResponsesTable();
        
        // Insert sample data
        $this->insertSampleData();
        
        echo "<h2>✅ Database setup completed!</h2>";
    }
    
    private function createUsersTable() {
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            role ENUM('Administrator', 'Campaign Manager', 'Content Creator', 'Data Analyst', 'Community Coordinator') DEFAULT 'Campaign Manager',
            department VARCHAR(100),
            location VARCHAR(100),
            timezone VARCHAR(50) DEFAULT 'Eastern Time (ET)',
            language VARCHAR(20) DEFAULT 'English',
            email_notifications BOOLEAN DEFAULT TRUE,
            push_notifications BOOLEAN DEFAULT TRUE,
            sms_notifications BOOLEAN DEFAULT FALSE,
            avatar VARCHAR(255),
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Users table created</p>";
        } else {
            echo "<p>❌ Error creating users table</p>";
        }
    }
    
    private function createCampaignsTable() {
        $query = "CREATE TABLE IF NOT EXISTS campaigns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            objective ENUM('awareness', 'education', 'behavior', 'policy') NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status ENUM('draft', 'planning', 'active', 'completed', 'cancelled') DEFAULT 'draft',
            progress INT DEFAULT 0,
            engagement_rate DECIMAL(5,2) DEFAULT 0.00,
            target_audience JSON,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Campaigns table created</p>";
        } else {
            echo "<p>❌ Error creating campaigns table</p>";
        }
    }
    
    private function createEventsTable() {
        $query = "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATETIME NOT NULL,
            location VARCHAR(255),
            campaign_id INT,
            registered_count INT DEFAULT 0,
            attended_count INT DEFAULT 0,
            status ENUM('planned', 'active', 'completed', 'cancelled') DEFAULT 'planned',
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Events table created</p>";
        } else {
            echo "<p>❌ Error creating events table</p>";
        }
    }
    
    private function createContentTable() {
        $query = "CREATE TABLE IF NOT EXISTS content (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category ENUM('posters', 'videos', 'guidelines', 'social') NOT NULL,
            file_path VARCHAR(500),
            file_size INT,
            file_type VARCHAR(50),
            campaign_id INT,
            status ENUM('draft', 'review', 'approved', 'published') DEFAULT 'draft',
            views_count INT DEFAULT 0,
            tags JSON,
            version_notes TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Content table created</p>";
        } else {
            echo "<p>❌ Error creating content table</p>";
        }
    }
    
    private function createNotificationsTable() {
        $query = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Notifications table created</p>";
        } else {
            echo "<p>❌ Error creating notifications table</p>";
        }
    }
    
    private function createAudienceSegmentsTable() {
        $query = "CREATE TABLE IF NOT EXISTS audience_segments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            criteria JSON,
            member_count INT DEFAULT 0,
            engagement_rate DECIMAL(5,2) DEFAULT 0.00,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Audience segments table created</p>";
        } else {
            echo "<p>❌ Error creating audience segments table</p>";
        }
    }
    
    private function createSurveyResponsesTable() {
        $query = "CREATE TABLE IF NOT EXISTS survey_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            campaign_id INT,
            respondent_email VARCHAR(100),
            responses JSON,
            satisfaction_rating INT,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
        )";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Survey responses table created</p>";
        } else {
            echo "<p>❌ Error creating survey responses table</p>";
        }
    }
    
    private function insertSampleData() {
        echo "<h3>Inserting Sample Data...</h3>";
        
        // Insert demo users
        $query = "INSERT IGNORE INTO users (first_name, last_name, email, password, role, department) VALUES 
            ('John', 'Doe', 'john.doe@safetycampaign.org', ?, 'Campaign Manager', 'Safety Operations'),
            ('Jane', 'Smith', 'jane.smith@safetycampaign.org', ?, 'Content Creator', 'Marketing'),
            ('Mike', 'Johnson', 'mike.johnson@safetycampaign.org', ?, 'Data Analyst', 'Analytics'),
            ('Admin', 'User', 'admin@safetycampaign.org', ?, 'Administrator', 'IT')";
        
        $stmt = $this->conn->prepare($query);
        $demo_password = password_hash('demo123', PASSWORD_DEFAULT);
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        if ($stmt->execute([$demo_password, $demo_password, $demo_password, $admin_password])) {
            echo "<p>✅ Sample users created</p>";
        }
        
        // Insert demo campaigns
        $query = "INSERT IGNORE INTO campaigns (name, description, objective, start_date, end_date, status, progress, engagement_rate, target_audience, created_by) VALUES 
            ('Road Safety Awareness', 'Comprehensive campaign to improve road safety awareness', 'awareness', '2023-10-01', '2023-12-15', 'active', 75, 68.5, '[\"general\", \"drivers\"]', 1),
            ('Cybersecurity for Seniors', 'Digital safety education for senior citizens', 'education', '2023-11-01', '2024-01-30', 'planning', 30, 0, '[\"seniors\"]', 1),
            ('Disaster Preparedness', 'Emergency preparedness education campaign', 'behavior', '2024-01-15', '2024-03-15', 'draft', 15, 0, '[\"general\", \"parents\"]', 1),
            ('Fire Prevention Campaign', 'Fire safety awareness and prevention', 'awareness', '2023-08-01', '2023-09-30', 'completed', 100, 52.3, '[\"general\", \"businesses\"]', 1)";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Sample campaigns created</p>";
        }
        
        // Insert demo events
        $query = "INSERT IGNORE INTO events (title, description, event_date, location, campaign_id, registered_count, created_by) VALUES 
            ('Road Safety Workshop', 'Interactive workshop on road safety best practices', '2023-10-15 10:00:00', 'City Community Center', 1, 45, 1),
            ('Cyber Safety Seminar', 'Digital safety seminar for seniors', '2023-10-22 14:00:00', 'Central Library', 2, 32, 1),
            ('Emergency Response Training', 'Hands-on emergency response training', '2023-11-05 09:00:00', 'Fire Station #3', 3, 28, 1)";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Sample events created</p>";
        }
        
        // Insert demo content
        $query = "INSERT IGNORE INTO content_items (title, description, category, campaign_id, status, views_count, tags, created_by) VALUES 
            ('Road Safety Poster', 'Educational poster about road safety rules', 'posters', 1, 'approved', 245, '[\"safety\", \"traffic\", \"education\"]', 2),
            ('Fire Safety Video', 'Instructional video on fire prevention', 'videos', 4, 'published', 1240, '[\"fire\", \"prevention\", \"safety\"]', 2),
            ('Cyber Safety Guidelines', 'Comprehensive guidelines for digital safety', 'guidelines', 2, 'published', 876, '[\"cyber\", \"digital\", \"seniors\"]', 2)";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Sample content created</p>";
        }
        
        // Insert demo notifications
        $query = "INSERT IGNORE INTO notifications (user_id, title, message, type) VALUES 
            (1, 'New Campaign Created', 'Road Safety Awareness campaign has been created and is ready for review.', 'success'),
            (1, 'Event Registration Update', 'Road Safety Workshop has 45 new registrations.', 'info'),
            (1, 'Content Approval Needed', 'Fire Safety Video is pending your approval.', 'warning')";
        
        if ($this->conn->exec($query)) {
            echo "<p>✅ Sample notifications created</p>";
        }
    }
}

// HTML output for browser access
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - Public Safety Campaign</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; background: #f5f5f5; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        h2 { color: #1e293b; margin-top: 2rem; }
        p { margin: 0.5rem 0; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Public Safety Campaign Database Setup</h1>
        <p>Setting up your XAMPP database...</p>
        
        <?php
        try {
            $setup = new DatabaseSetup();
            $setup->createTables();
            echo "<div style='background: #f0f9ff; padding: 1rem; border-radius: 4px; margin-top: 2rem;'>";
            echo "<h3>🎉 Setup Complete!</h3>";
            echo "<p>Your database is ready. You can now use the login system with these demo accounts:</p>";
            echo "<ul>";
            echo "<li><strong>Campaign Manager:</strong> john.doe@safetycampaign.org / demo123</li>";
            echo "<li><strong>Content Creator:</strong> jane.smith@safetycampaign.org / demo123</li>";
            echo "<li><strong>Data Analyst:</strong> mike.johnson@safetycampaign.org / demo123</li>";
            echo "<li><strong>Administrator:</strong> admin@safetycampaign.org / admin123</li>";
            echo "</ul>";
            echo "<p><strong>Next:</strong> Access your application at <a href='../../login.php'>login.php</a> or <a href='../../index.php'>index.php</a></p>";
            echo "</div>";
        } catch (Exception $e) {
            echo "<div style='background: #fef2f2; padding: 1rem; border-radius: 4px; margin-top: 2rem;'>";
            echo "<h3>❌ Setup Error</h3>";
            echo "<p>Error: " . $e->getMessage() . "</p>";
            echo "<p>Please make sure XAMPP MySQL is running and try again.</p>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>
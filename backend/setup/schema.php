<?php
/**
 * Database Schema Setup
 * Creates all necessary tables for the Public Safety Campaign Management System
 */

require_once '../config/database.php';

class DatabaseSchema {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $database->createDatabase(); // Create database if it doesn't exist
        $this->conn = $database->getConnection();
    }
    
    public function createAllTables() {
        $this->createUsersTable();
        $this->createCampaignsTable();
        $this->createEventsTable();
        $this->createContentItemsTable();
        $this->createSurveyResponsesTable();
        $this->createAudienceSegmentsTable();
        $this->createNotificationsTable();
        $this->createSMSAlertsTable();
        
        // New interconnected tables
        $this->createVenuesTable();
        $this->createEventRegistrationsTable();
        $this->createSurveysTable();
        $this->createSurveyQuestionsTable();
        $this->createContactListsTable();
        $this->createContactsTable();
        $this->createPersonalOutreachTable();
        $this->createKPIMetricsTable();
        $this->createImpactReportsTable();
        $this->createTeamMembersTable();
        $this->createProjectsTable();
        $this->createProjectTasksTable();
        $this->createTeamMessagesTable();
        $this->createSharedResourcesTable();
        $this->createActivityLogTable();
        $this->createCampaignMetricsTable();
        
        $this->insertSampleData();
    }
    
    private function createUsersTable() {
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(100) DEFAULT 'Campaign Manager',
            department VARCHAR(100),
            phone VARCHAR(20),
            location VARCHAR(255),
            timezone VARCHAR(100) DEFAULT 'Eastern Time (ET)',
            language VARCHAR(50) DEFAULT 'English',
            email_notifications BOOLEAN DEFAULT TRUE,
            push_notifications BOOLEAN DEFAULT TRUE,
            sms_notifications BOOLEAN DEFAULT FALSE,
            avatar VARCHAR(10),
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->conn->exec($query);
        echo "Users table created successfully.\n";
    }
    
    private function createCampaignsTable() {
        $query = "CREATE TABLE IF NOT EXISTS campaigns (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            objective ENUM('awareness', 'education', 'behavior', 'policy') DEFAULT 'awareness',
            status ENUM('draft', 'planning', 'active', 'completed', 'cancelled') DEFAULT 'draft',
            start_date DATE,
            end_date DATE,
            target_audience JSON,
            engagement_rate DECIMAL(5,2) DEFAULT 0.00,
            progress INT DEFAULT 0,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        $this->conn->exec($query);
        echo "Campaigns table created successfully.\n";
    }
    
    private function createEventsTable() {
        $query = "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATETIME NOT NULL,
            location VARCHAR(255),
            registered_count INT DEFAULT 0,
            max_capacity INT,
            campaign_id INT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        $this->conn->exec($query);
        echo "Events table created successfully.\n";
    }
    
    private function createContentItemsTable() {
        $query = "CREATE TABLE IF NOT EXISTS content_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            category ENUM('posters', 'videos', 'guidelines', 'social') NOT NULL,
            file_path VARCHAR(500),
            file_size INT,
            file_format VARCHAR(10),
            campaign_id INT,
            status ENUM('draft', 'review', 'approved', 'published') DEFAULT 'draft',
            tags JSON,
            version_notes TEXT,
            views_count INT DEFAULT 0,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        $this->conn->exec($query);
        echo "Content items table created successfully.\n";
    }
    
    private function createSurveyResponsesTable() {
        $query = "CREATE TABLE IF NOT EXISTS survey_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            respondent_email VARCHAR(255),
            response_data JSON,
            campaign_id INT,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (campaign_id) REFERENCES campaigns(id)
        )";
        
        $this->conn->exec($query);
        echo "Survey responses table created successfully.\n";
    }
    
    private function createAudienceSegmentsTable() {
        $query = "CREATE TABLE IF NOT EXISTS audience_segments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            criteria JSON,
            contact_count INT DEFAULT 0,
            engagement_rate DECIMAL(5,2) DEFAULT 0.00,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        $this->conn->exec($query);
        echo "Audience segments table created successfully.\n";
    }
    
    private function createNotificationsTable() {
        $query = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            title VARCHAR(255) NOT NULL,
            message TEXT,
            type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        
        $this->conn->exec($query);
        echo "Notifications table created successfully.\n";
    }
    
    private function createSMSAlertsTable() {
        $query = "CREATE TABLE IF NOT EXISTS sms_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            audience_segment_id INT,
            scheduled_date DATE,
            scheduled_time TIME,
            status ENUM('draft', 'scheduled', 'sent', 'failed') DEFAULT 'draft',
            sent_count INT DEFAULT 0,
            delivery_rate DECIMAL(5,2) DEFAULT 0.00,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (audience_segment_id) REFERENCES audience_segments(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        )";
        
        $this->conn->exec($query);
        echo "SMS alerts table created successfully.\n";
    }
    
    private function insertSampleData() {
        // Insert demo user
        $hashedPassword = password_hash('demo123', PASSWORD_DEFAULT);
        
        $query = "INSERT IGNORE INTO users (first_name, last_name, email, password, role, department, avatar) 
                  VALUES ('John', 'Doe', 'demo@safetycampaign.org', ?, 'Campaign Manager', 'Public Safety', 'JD')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$hashedPassword]);
        
        // Get user ID
        $userId = $this->conn->lastInsertId();
        if (!$userId) {
            $query = "SELECT id FROM users WHERE email = 'demo@safetycampaign.org'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userId = $user['id'];
        }
        
        // Insert sample campaigns
        $campaigns = [
            ['Road Safety Awareness', 'Comprehensive road safety campaign targeting urban drivers', 'awareness', 'active', '2023-10-01', '2023-12-15', 75, 68],
            ['Cybersecurity for Seniors', 'Educational campaign about online safety for senior citizens', 'education', 'planning', '2023-11-01', '2024-01-30', 30, 0],
            ['Disaster Preparedness', 'Community preparedness for natural disasters', 'behavior', 'draft', '2024-01-15', '2024-03-15', 15, 0]
        ];
        
        foreach ($campaigns as $campaign) {
            $query = "INSERT IGNORE INTO campaigns (name, description, objective, status, start_date, end_date, progress, engagement_rate, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(array_merge($campaign, [$userId]));
        }
        
        // Insert sample events
        $events = [
            ['Road Safety Workshop', 'Interactive workshop on road safety best practices', '2023-10-15 10:00:00', 'City Community Center', 45],
            ['Cyber Safety Seminar', 'Educational seminar on cybersecurity for seniors', '2023-10-22 14:30:00', 'Central Library', 32],
            ['Emergency Response Training', 'Hands-on emergency response training', '2023-11-05 09:00:00', 'Fire Station #3', 28]
        ];
        
        foreach ($events as $event) {
            $query = "INSERT IGNORE INTO events (title, description, event_date, location, registered_count, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(array_merge($event, [$userId]));
        }
        
        // Insert sample content items
        $contentItems = [
            ['Road Safety Poster', 'Educational poster about road safety rules', 'posters', 'approved'],
            ['Fire Safety Video', 'Instructional video on fire prevention', 'videos', 'review'],
            ['Cyber Safety Guidelines', 'Comprehensive guidelines for online safety', 'guidelines', 'published']
        ];
        
        foreach ($contentItems as $item) {
            $query = "INSERT IGNORE INTO content_items (title, description, category, status, created_by) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(array_merge($item, [$userId]));
        }
        
        // Insert sample survey responses
        for ($i = 0; $i < 10; $i++) {
            $query = "INSERT IGNORE INTO survey_responses (respondent_email) VALUES (CONCAT('user', ?, '@example.com'))";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$i]);
        }
        
        // Insert sample audience segments
        $segments = [
            ['Urban Residents', 'Residents living in urban areas interested in safety', 1250, 24.7],
            ['Seniors', 'Senior citizens interested in cybersecurity education', 874, 31.2],
            ['Community Emergency Response', 'Community members trained in emergency response', 642, 18.5]
        ];
        
        foreach ($segments as $segment) {
            $query = "INSERT IGNORE INTO audience_segments (name, description, contact_count, engagement_rate, created_by) 
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute(array_merge($segment, [$userId]));
        }
        
        echo "Sample data inserted successfully.\n";
    }
}

// Run schema creation if called directly
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    try {
        $schema = new DatabaseSchema();
        $schema->createAllTables();
        echo "Database schema created successfully!";
    } catch (Exception $e) {
        echo "Error creating database schema: " . $e->getMessage();
    }
}
?>
-- ===================================
-- Content Repository Module Tables
-- ===================================

-- Drop tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS content_versions;
DROP TABLE IF EXISTS content_usage;
DROP TABLE IF EXISTS content_items;

-- Content items table for managing all campaign materials
CREATE TABLE content_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('posters', 'videos', 'guidelines', 'social', 'documents', 'infographics', 'presentations') NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    file_format VARCHAR(20),
    thumbnail_path VARCHAR(500),
    campaign_id INT,
    status ENUM('draft', 'review', 'approved', 'published', 'archived') DEFAULT 'draft',
    tags JSON,
    version VARCHAR(10) DEFAULT '1.0',
    version_notes TEXT,
    views_count INT DEFAULT 0,
    downloads_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    shares_count INT DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    expiry_date DATE NULL,
    created_by INT,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_campaign (campaign_id),
    INDEX idx_created_by (created_by),
    INDEX idx_featured (is_featured),
    INDEX idx_views (views_count),
    FULLTEXT(title, description)
);

-- Content versions for version control
CREATE TABLE content_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_id INT NOT NULL,
    version VARCHAR(10) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    changes_description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_content_version (content_id, version),
    INDEX idx_content_version (content_id, version)
);

-- Content usage tracking
CREATE TABLE content_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content_id INT NOT NULL,
    campaign_id INT,
    usage_type ENUM('view', 'download', 'share', 'embed', 'print') NOT NULL,
    user_id INT NULL,
    user_ip VARCHAR(45),
    user_agent TEXT,
    referrer_url VARCHAR(500),
    usage_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_content_usage (content_id, usage_type),
    INDEX idx_campaign_usage (campaign_id, usage_type),
    INDEX idx_usage_date (usage_date)
);

-- Insert sample content items
INSERT INTO content_items (title, description, category, file_path, file_size, file_format, campaign_id, status, tags, views_count, downloads_count, likes_count, shares_count, is_featured, created_by, approved_by) VALUES

-- Road Safety Campaign Content
('Road Safety Rules Poster', 'Comprehensive poster showing essential road safety rules for drivers and pedestrians', 'posters', '/content/posters/road-safety-rules-v1.jpg', 2048576, 'JPG', 1, 'published', '["road-safety", "rules", "drivers", "pedestrians"]', 1250, 89, 45, 23, TRUE, 1, 1),

('Defensive Driving Infographic', 'Visual guide to defensive driving techniques and best practices', 'infographics', '/content/infographics/defensive-driving.png', 1536000, 'PNG', 1, 'published', '["defensive-driving", "techniques", "safety"]', 890, 67, 34, 18, FALSE, 2, 1),

('Road Safety Educational Video', 'Comprehensive 5-minute video covering road safety basics for all road users', 'videos', '/content/videos/road-safety-basics.mp4', 157286400, 'MP4', 1, 'approved', '["video", "education", "road-safety", "basics"]', 2340, 156, 78, 92, TRUE, 2, 1),

('Traffic Signal Guidelines', 'Detailed document explaining traffic signal meanings and proper responses', 'documents', '/content/documents/traffic-signals-guide.pdf', 3145728, 'PDF', 1, 'published', '["traffic-signals", "guidelines", "rules"]', 567, 234, 12, 8, FALSE, 1, 1),

-- Fire Prevention Campaign Content
('Home Fire Safety Checklist', 'Printable checklist for home fire safety inspection and prevention', 'documents', '/content/documents/fire-safety-checklist.pdf', 1048576, 'PDF', 4, 'published', '["fire-safety", "home", "checklist", "prevention"]', 1890, 445, 67, 34, TRUE, 2, 1),

('Smoke Detector Installation Video', 'Step-by-step video guide for proper smoke detector installation and maintenance', 'videos', '/content/videos/smoke-detector-install.mp4', 134217728, 'MP4', 4, 'approved', '["smoke-detector", "installation", "maintenance", "safety"]', 1456, 89, 56, 45, FALSE, 1, 1),

('Fire Escape Plan Template', 'Customizable template for creating home fire escape plans', 'documents', '/content/documents/fire-escape-template.pdf', 2097152, 'PDF', 4, 'published', '["fire-escape", "plan", "template", "safety"]', 2100, 567, 89, 56, TRUE, 2, 1),

('Kitchen Fire Safety Poster', 'Visual guide to preventing and handling kitchen fires', 'posters', '/content/posters/kitchen-fire-safety.jpg', 1572864, 'JPG', 4, 'published', '["kitchen", "fire-safety", "prevention", "cooking"]', 987, 123, 45, 23, FALSE, 1, 1),

-- Cybersecurity Campaign Content
('Senior Cybersecurity Guide', 'Comprehensive guide to online safety specifically designed for senior citizens', 'documents', '/content/documents/senior-cyber-guide.pdf', 4194304, 'PDF', 2, 'review', '["cybersecurity", "seniors", "online-safety", "guide"]', 234, 45, 12, 3, FALSE, 1, NULL),

('Password Security Infographic', 'Visual guide to creating and managing strong passwords', 'infographics', '/content/infographics/password-security.png', 1048576, 'PNG', 2, 'draft', '["passwords", "security", "cybersecurity", "online-safety"]', 0, 0, 0, 0, FALSE, 2, NULL),

-- Disaster Preparedness Campaign Content
('Emergency Kit Checklist', 'Complete checklist for building a comprehensive emergency preparedness kit', 'documents', '/content/documents/emergency-kit-checklist.pdf', 1572864, 'PDF', 3, 'draft', '["emergency", "preparedness", "kit", "checklist"]', 0, 0, 0, 0, FALSE, 1, NULL),

('Evacuation Plan Template', 'Template for creating family and business evacuation plans', 'documents', '/content/documents/evacuation-plan-template.pdf', 2621440, 'PDF', 3, 'draft', '["evacuation", "plan", "emergency", "preparedness"]', 0, 0, 0, 0, FALSE, 2, NULL);

-- Insert sample content versions
INSERT INTO content_versions (content_id, version, file_path, file_size, changes_description, created_by) VALUES
(1, '1.0', '/content/posters/road-safety-rules-v1.jpg', 2048576, 'Initial version of road safety rules poster', 1),
(1, '1.1', '/content/posters/road-safety-rules-v1-1.jpg', 2097152, 'Updated color scheme and added bicycle safety section', 2),
(3, '1.0', '/content/videos/road-safety-basics-v1.mp4', 157286400, 'Initial 5-minute educational video', 2),
(5, '1.0', '/content/documents/fire-safety-checklist-v1.pdf', 1048576, 'Initial home fire safety checklist', 2),
(5, '1.1', '/content/documents/fire-safety-checklist-v1-1.pdf', 1073741824, 'Added section on carbon monoxide detectors', 1);

-- Insert sample content usage data
INSERT INTO content_usage (content_id, campaign_id, usage_type, user_id, user_ip, usage_date) VALUES
-- Road Safety Poster usage
(1, 1, 'view', 1, '192.168.1.100', '2023-10-01 09:30:00'),
(1, 1, 'download', 1, '192.168.1.100', '2023-10-01 09:32:00'),
(1, 1, 'view', 2, '192.168.1.101', '2023-10-01 14:15:00'),
(1, 1, 'share', 2, '192.168.1.101', '2023-10-01 14:20:00'),

-- Road Safety Video usage
(3, 1, 'view', 1, '192.168.1.100', '2023-10-02 11:00:00'),
(3, 1, 'view', 3, '192.168.1.102', '2023-10-02 15:30:00'),
(3, 1, 'download', 3, '192.168.1.102', '2023-10-02 15:35:00'),

-- Fire Safety Checklist usage
(5, 4, 'view', 2, '192.168.1.101', '2023-09-20 10:15:00'),
(5, 4, 'download', 2, '192.168.1.101', '2023-09-20 10:18:00'),
(5, 4, 'view', 1, '192.168.1.100', '2023-09-21 16:45:00');
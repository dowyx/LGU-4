-- ===================================
-- Campaign Planning Module Tables
-- ===================================

-- Drop tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS campaign_metrics;
DROP TABLE IF EXISTS campaigns;

-- Campaigns table for campaign planning and management
CREATE TABLE campaigns (
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
    budget DECIMAL(12,2) DEFAULT 0.00,
    actual_spend DECIMAL(12,2) DEFAULT 0.00,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    tags JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_priority (priority)
);

-- Campaign metrics for detailed tracking
CREATE TABLE campaign_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    metric_date DATE NOT NULL,
    impressions INT DEFAULT 0,
    clicks INT DEFAULT 0,
    conversions INT DEFAULT 0,
    engagement_count INT DEFAULT 0,
    reach INT DEFAULT 0,
    cost_per_click DECIMAL(8,2) DEFAULT 0.00,
    conversion_rate DECIMAL(5,2) DEFAULT 0.00,
    click_through_rate DECIMAL(5,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    UNIQUE KEY unique_campaign_date (campaign_id, metric_date),
    INDEX idx_campaign_date (campaign_id, metric_date)
);

-- Insert sample campaigns
INSERT INTO campaigns (name, description, objective, status, start_date, end_date, progress, engagement_rate, budget, priority, target_audience, tags, created_by) VALUES
('Road Safety Awareness', 'Comprehensive road safety campaign targeting urban drivers with focus on reducing accidents and promoting safe driving habits', 'awareness', 'active', '2023-10-01', '2023-12-15', 75, 68.50, 25000.00, 'high', '["general", "drivers", "parents"]', '["road-safety", "awareness", "urban"]', 1),

('Cybersecurity for Seniors', 'Educational campaign about online safety for senior citizens, covering phishing, password security, and safe browsing practices', 'education', 'planning', '2023-11-01', '2024-01-30', 30, 0.00, 15000.00, 'medium', '["seniors", "general"]', '["cybersecurity", "education", "seniors"]', 1),

('Disaster Preparedness', 'Community preparedness for natural disasters including emergency kits, evacuation plans, and response procedures', 'behavior', 'draft', '2024-01-15', '2024-03-15', 15, 0.00, 30000.00, 'critical', '["general", "families", "businesses"]', '["disaster", "preparedness", "emergency"]', 1),

('Fire Prevention Initiative', 'Community-wide fire prevention education covering home safety, smoke detectors, and escape plans', 'awareness', 'active', '2023-09-15', '2023-11-30', 85, 72.30, 18000.00, 'high', '["families", "homeowners", "businesses"]', '["fire-safety", "prevention", "home-safety"]', 2),

('Youth Safety Education', 'School-based safety education program covering internet safety, stranger danger, and emergency procedures', 'education', 'planning', '2024-02-01', '2024-05-15', 25, 0.00, 22000.00, 'medium', '["students", "parents", "teachers"]', '["youth", "education", "school-safety"]', 2),

('Workplace Safety Standards', 'B2B campaign promoting workplace safety standards and OSHA compliance for local businesses', 'policy', 'draft', '2024-03-01', '2024-06-30', 10, 0.00, 35000.00, 'medium', '["businesses", "employers", "workers"]', '["workplace", "OSHA", "compliance"]', 3);

-- Insert sample campaign metrics
INSERT INTO campaign_metrics (campaign_id, metric_date, impressions, clicks, conversions, engagement_count, reach, cost_per_click, conversion_rate, click_through_rate) VALUES
-- Road Safety Campaign metrics
(1, '2023-10-01', 15000, 420, 35, 280, 12500, 2.15, 8.33, 2.80),
(1, '2023-10-02', 16200, 445, 42, 315, 13200, 2.08, 9.44, 2.75),
(1, '2023-10-03', 18500, 512, 48, 380, 15100, 1.95, 9.38, 2.77),
(1, '2023-10-04', 17800, 489, 51, 365, 14600, 2.02, 10.43, 2.75),
(1, '2023-10-05', 19200, 567, 55, 420, 16000, 1.88, 9.70, 2.95),

-- Fire Prevention Campaign metrics
(4, '2023-09-15', 12000, 360, 28, 245, 10500, 2.25, 7.78, 3.00),
(4, '2023-09-16', 13500, 405, 35, 290, 11800, 2.10, 8.64, 3.00),
(4, '2023-09-17', 14200, 426, 38, 312, 12400, 2.05, 8.92, 3.00),
(4, '2023-09-18', 15800, 474, 42, 348, 13600, 1.98, 8.86, 3.00),
(4, '2023-09-19', 16500, 495, 45, 375, 14200, 1.92, 9.09, 3.00);
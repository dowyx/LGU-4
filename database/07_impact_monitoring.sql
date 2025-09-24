-- ===================================
-- Impact Monitoring Module Tables
-- ===================================

-- Drop tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS impact_reports;
DROP TABLE IF EXISTS kpi_metrics;

-- KPI Metrics table for tracking key performance indicators
CREATE TABLE kpi_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT,
    event_id INT,
    metric_name VARCHAR(255) NOT NULL,
    metric_category ENUM('awareness', 'engagement', 'behavior_change', 'reach', 'cost_effectiveness', 'satisfaction') NOT NULL,
    metric_type ENUM('count', 'percentage', 'ratio', 'currency', 'duration', 'score') NOT NULL,
    current_value DECIMAL(15,4) NOT NULL,
    target_value DECIMAL(15,4),
    previous_value DECIMAL(15,4),
    unit VARCHAR(50),
    measurement_date DATE NOT NULL,
    data_source VARCHAR(100),
    calculation_method TEXT,
    notes TEXT,
    is_primary_kpi BOOLEAN DEFAULT FALSE,
    trend ENUM('up', 'down', 'stable', 'unknown') DEFAULT 'unknown',
    performance_status ENUM('excellent', 'good', 'average', 'poor', 'critical') DEFAULT 'average',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaign_metric (campaign_id, metric_name),
    INDEX idx_event_metric (event_id, metric_name),
    INDEX idx_category (metric_category),
    INDEX idx_date (measurement_date),
    INDEX idx_primary (is_primary_kpi)
);

-- Impact Reports table for comprehensive reporting
CREATE TABLE impact_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    report_type ENUM('campaign_summary', 'monthly_overview', 'quarterly_review', 'annual_report', 'event_impact', 'roi_analysis') NOT NULL,
    campaign_id INT,
    event_id INT,
    reporting_period_start DATE NOT NULL,
    reporting_period_end DATE NOT NULL,
    status ENUM('draft', 'in_progress', 'review', 'approved', 'published') DEFAULT 'draft',
    
    -- Key Metrics Summary
    total_reach INT DEFAULT 0,
    total_engagement INT DEFAULT 0,
    engagement_rate DECIMAL(5,2) DEFAULT 0.00,
    cost_per_engagement DECIMAL(8,2) DEFAULT 0.00,
    roi_percentage DECIMAL(8,2) DEFAULT 0.00,
    
    -- Content Performance
    content_pieces_created INT DEFAULT 0,
    content_views INT DEFAULT 0,
    content_shares INT DEFAULT 0,
    
    -- Event Performance
    events_conducted INT DEFAULT 0,
    total_attendees INT DEFAULT 0,
    avg_event_rating DECIMAL(3,2) DEFAULT 0.00,
    
    -- Survey and Feedback
    surveys_completed INT DEFAULT 0,
    avg_satisfaction_score DECIMAL(3,2) DEFAULT 0.00,
    
    -- Behavioral Impact
    behavior_change_indicators JSON,
    knowledge_improvement_metrics JSON,
    
    -- Financial Information
    total_budget DECIMAL(12,2) DEFAULT 0.00,
    actual_spend DECIMAL(12,2) DEFAULT 0.00,
    cost_efficiency_ratio DECIMAL(8,4) DEFAULT 0.00,
    
    -- Detailed Analysis
    key_achievements TEXT,
    challenges_identified TEXT,
    lessons_learned TEXT,
    recommendations TEXT,
    future_improvements TEXT,
    
    -- Report Metadata
    report_data JSON, -- Detailed data and charts
    executive_summary TEXT,
    methodology_notes TEXT,
    data_sources TEXT,
    
    generated_by INT,
    approved_by INT,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaign_report (campaign_id, report_type),
    INDEX idx_period (reporting_period_start, reporting_period_end),
    INDEX idx_status (status),
    INDEX idx_type (report_type)
);

-- Activity Log for tracking all system activities
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity_type ENUM('create', 'update', 'delete', 'view', 'login', 'logout', 'upload', 'download', 'send', 'export', 'import') NOT NULL,
    entity_type ENUM('campaign', 'event', 'content', 'user', 'survey', 'contact', 'report', 'segment', 'venue', 'notification') NOT NULL,
    entity_id INT,
    activity_description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    additional_data JSON,
    severity ENUM('info', 'warning', 'error', 'critical') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_activity (user_id, activity_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at),
    INDEX idx_severity (severity)
);

-- Insert sample KPI metrics
INSERT INTO kpi_metrics (campaign_id, event_id, metric_name, metric_category, metric_type, current_value, target_value, previous_value, unit, measurement_date, data_source, is_primary_kpi, trend, performance_status, created_by) VALUES

-- Road Safety Campaign KPIs
(1, NULL, 'Campaign Reach', 'reach', 'count', 45250, 50000, 42100, 'people', '2023-10-15', 'Social Media Analytics', TRUE, 'up', 'good', 1),
(1, NULL, 'Engagement Rate', 'engagement', 'percentage', 68.50, 70.00, 65.20, 'percent', '2023-10-15', 'Multi-platform Analytics', TRUE, 'up', 'good', 1),
(1, NULL, 'Content Shares', 'engagement', 'count', 2847, 3000, 2654, 'shares', '2023-10-15', 'Social Media Tracking', FALSE, 'up', 'good', 1),
(1, NULL, 'Cost Per Engagement', 'cost_effectiveness', 'currency', 2.15, 2.50, 2.28, 'USD', '2023-10-15', 'Financial Tracking', TRUE, 'up', 'excellent', 1),
(1, NULL, 'Safety Knowledge Improvement', 'behavior_change', 'percentage', 34.60, 40.00, 28.30, 'percent', '2023-10-15', 'Pre/Post Surveys', TRUE, 'up', 'good', 1),

-- Fire Prevention Campaign KPIs
(4, NULL, 'Smoke Detector Installation Rate', 'behavior_change', 'percentage', 78.90, 85.00, 71.20, 'percent', '2023-10-15', 'Community Survey', TRUE, 'up', 'good', 2),
(4, NULL, 'Workshop Attendance', 'engagement', 'count', 423, 500, 387, 'attendees', '2023-10-15', 'Registration System', FALSE, 'up', 'good', 2),
(4, NULL, 'Fire Safety Plan Creation', 'behavior_change', 'percentage', 56.70, 65.00, 48.90, 'percent', '2023-10-15', 'Follow-up Surveys', TRUE, 'up', 'good', 2),
(4, NULL, 'Community Satisfaction', 'satisfaction', 'score', 4.60, 4.50, 4.40, 'out of 5', '2023-10-15', 'Feedback Surveys', FALSE, 'up', 'excellent', 2),

-- Cybersecurity Campaign KPIs
(2, NULL, 'Senior Engagement Rate', 'engagement', 'percentage', 31.20, 35.00, 28.50, 'percent', '2023-10-15', 'Email & Event Analytics', TRUE, 'up', 'good', 1),
(2, NULL, 'Password Security Adoption', 'behavior_change', 'percentage', 42.80, 50.00, 35.60, 'percent', '2023-10-15', 'Assessment Surveys', TRUE, 'up', 'good', 1),
(2, NULL, 'Two-Factor Auth Setup', 'behavior_change', 'percentage', 28.90, 40.00, 22.10, 'percent', '2023-10-15', 'Follow-up Surveys', TRUE, 'up', 'average', 1),

-- Event-specific KPIs
(1, 1, 'Workshop Satisfaction', 'satisfaction', 'score', 4.75, 4.50, 4.20, 'out of 5', '2023-10-15', 'Post-Event Survey', FALSE, 'up', 'excellent', 1),
(1, 1, 'Knowledge Retention Rate', 'behavior_change', 'percentage', 89.30, 85.00, 82.10, 'percent', '2023-10-15', 'Follow-up Assessment', TRUE, 'up', 'excellent', 1),
(2, 2, 'Senior Tech Confidence', 'behavior_change', 'score', 6.80, 7.00, 5.90, 'out of 10', '2023-10-22', 'Pre/Post Assessment', TRUE, 'up', 'good', 1);

-- Insert sample impact reports
INSERT INTO impact_reports (title, description, report_type, campaign_id, reporting_period_start, reporting_period_end, status, total_reach, total_engagement, engagement_rate, cost_per_engagement, roi_percentage, content_pieces_created, content_views, events_conducted, total_attendees, avg_event_rating, surveys_completed, avg_satisfaction_score, behavior_change_indicators, total_budget, actual_spend, key_achievements, challenges_identified, lessons_learned, recommendations, generated_by) VALUES

('Road Safety Campaign Q4 2023 Impact Report', 'Comprehensive analysis of road safety campaign performance for October-December 2023', 'campaign_summary', 1, '2023-10-01', '2023-12-31', 'approved', 
 45250, 31008, 68.50, 2.15, 234.70, 12, 89470, 6, 342, 4.60, 156, 4.40,
 '{"knowledge_improvement": 34.6, "behavior_adoption": 78.3, "recommendation_rate": 92.1}',
 25000.00, 21750.00,
 'Exceeded engagement targets by 18%. Successfully reached urban driver demographic. High satisfaction scores across all events. Strong social media performance with viral content pieces.',
 'Limited rural area penetration. Difficulty reaching younger demographics through traditional channels. Weather-related event cancellations affected attendance.',
 'Interactive content performs significantly better than static materials. Local partnerships essential for community trust. Multi-channel approach increases overall reach.',
 'Expand digital marketing for younger audiences. Develop weather contingency plans. Create rural-specific content and distribution channels.',
 1),

('Fire Prevention Campaign Monthly Report - October 2023', 'Monthly impact assessment for fire prevention initiatives', 'monthly_overview', 4, '2023-10-01', '2023-10-31', 'published',
 18650, 14752, 79.10, 1.85, 189.40, 8, 34520, 4, 187, 4.70, 98, 4.60,
 '{"smoke_detector_installation": 78.9, "escape_plan_creation": 56.7, "fire_safety_knowledge": 43.2}',
 5000.00, 4200.00,
 'High community engagement in fire safety workshops. Significant increase in smoke detector installations. Strong partnership with local fire department.',
 'Limited evening workshop availability for working parents. Need more Spanish-language materials. Difficulty tracking long-term behavior changes.',
 'Hands-on demonstrations are highly effective. Community leaders as advocates increase participation. Follow-up is crucial for behavior maintenance.',
 'Add evening and weekend session options. Develop multilingual materials. Implement 6-month follow-up surveys.',
 2),

('Emergency Preparedness Baseline Assessment', 'Initial community readiness assessment for emergency preparedness campaign', 'quarterly_review', 3, '2023-07-01', '2023-09-30', 'review',
 5230, 1876, 35.90, 3.45, 0.00, 3, 8940, 2, 67, 3.80, 234, 3.20,
 '{"emergency_kit_ownership": 23.4, "evacuation_plan_awareness": 18.7, "emergency_contact_list": 45.6}',
 8000.00, 2400.00,
 'Established baseline metrics for emergency preparedness. Identified key community vulnerability areas. Built foundation partnerships with emergency services.',
 'Low initial community awareness levels. Limited existing emergency preparedness resources. Need for more engaging educational materials.',
 'Community needs comprehensive education starting from basics. Visual and practical demonstrations more effective than text-based materials.',
 'Develop comprehensive educational campaign. Create practical demonstration events. Partner with schools for family engagement.',
 3),

('Annual Safety Campaign Overview 2023', 'Comprehensive annual review of all safety campaign initiatives', 'annual_report', NULL, '2023-01-01', '2023-12-31', 'draft',
 127850, 89456, 69.95, 2.28, 198.60, 47, 287940, 18, 1024, 4.55, 567, 4.35,
 '{"overall_safety_awareness": 56.8, "behavior_change_rate": 47.3, "community_engagement": 71.2}',
 75000.00, 68450.00,
 'Successfully launched 4 major safety campaigns. Exceeded annual engagement targets. Strong community partnerships established. Positive behavior change indicators across all campaigns.',
 'Resource allocation challenges across multiple campaigns. Difficulty in measuring long-term impact. Limited staff for expanded programming.',
 'Integrated campaigns more effective than isolated efforts. Community partnerships essential for success. Data-driven approach improves targeting and outcomes.',
 'Increase staffing for campaign management. Develop long-term impact measurement framework. Create integrated campaign planning process.',
 1);

-- Insert sample activity log entries
INSERT INTO activity_log (user_id, activity_type, entity_type, entity_id, activity_description, ip_address, additional_data, severity) VALUES

(1, 'login', 'user', 1, 'User logged in successfully', '192.168.1.100', '{"login_method": "email", "remember_me": true}', 'info'),
(1, 'create', 'campaign', 1, 'Created new campaign: Road Safety Awareness', '192.168.1.100', '{"campaign_name": "Road Safety Awareness", "objective": "awareness"}', 'info'),
(1, 'update', 'campaign', 1, 'Updated campaign status to active', '192.168.1.100', '{"old_status": "draft", "new_status": "active"}', 'info'),
(2, 'upload', 'content', 5, 'Uploaded new content: Fire Safety Video', '192.168.1.101', '{"file_name": "fire-safety-basics.mp4", "file_size": 134217728}', 'info'),
(1, 'create', 'event', 1, 'Created new event: Road Safety Workshop', '192.168.1.100', '{"event_title": "Road Safety Workshop", "date": "2023-10-15"}', 'info'),
(3, 'send', 'notification', NULL, 'Sent emergency weather alert to 2847 recipients', '192.168.1.102', '{"alert_type": "weather", "recipients": 2847, "delivery_rate": 98.38}', 'warning'),
(2, 'export', 'report', 1, 'Exported road safety campaign report', '192.168.1.101', '{"report_type": "campaign_summary", "format": "PDF"}', 'info'),
(1, 'view', 'report', 2, 'Viewed fire prevention monthly report', '192.168.1.100', '{"report_id": 2, "view_duration": 180}', 'info'),
(4, 'create', 'survey', 3, 'Created cybersecurity awareness assessment', '192.168.1.103', '{"survey_type": "assessment", "questions": 4}', 'info'),
(1, 'update', 'user', 1, 'Updated user profile information', '192.168.1.100', '{"fields_updated": ["phone", "department"]}', 'info'),
(2, 'delete', 'content', NULL, 'Attempted to delete protected content item', '192.168.1.101', '{"content_id": 1, "reason": "content_protected"}', 'warning'),
(3, 'create', 'contact', 9, 'Added new emergency response volunteer contact', '192.168.1.102', '{"contact_name": "Lisa Taylor", "source": "volunteer_signup"}', 'info'),
(1, 'login', 'user', 1, 'Failed login attempt - invalid password', '192.168.1.105', '{"login_attempt": "failed", "reason": "invalid_password"}', 'warning'),
(2, 'update', 'event', 4, 'Updated fire safety meeting location', '192.168.1.101', '{"old_location": "Fire Station", "new_location": "City Park Pavilion"}', 'info'),
(1, 'export', 'contact', NULL, 'Exported contact list for road safety campaign', '192.168.1.100', '{"list_name": "Urban Drivers", "count": 1250, "format": "CSV"}', 'info');
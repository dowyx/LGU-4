-- ===================================
-- Audience Segmentation Module Tables
-- ===================================

-- Drop tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS audience_segment_members;
DROP TABLE IF EXISTS contact_interactions;
DROP TABLE IF EXISTS contacts;
DROP TABLE IF EXISTS contact_lists;
DROP TABLE IF EXISTS audience_segments;

-- Audience segments for targeted campaigns
CREATE TABLE audience_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    criteria JSON,
    contact_count INT DEFAULT 0,
    engagement_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_age INT NULL,
    gender_distribution JSON NULL,
    location_data JSON NULL,
    interests JSON NULL,
    communication_preferences JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_active (is_active),
    INDEX idx_engagement (engagement_rate),
    INDEX idx_created_by (created_by)
);

-- Contact lists for organizing contacts
CREATE TABLE contact_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    list_type ENUM('static', 'dynamic', 'imported') DEFAULT 'static',
    source VARCHAR(100),
    total_contacts INT DEFAULT 0,
    active_contacts INT DEFAULT 0,
    opted_in_contacts INT DEFAULT 0,
    tags JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_name (name),
    INDEX idx_type (list_type),
    INDEX idx_created_by (created_by)
);

-- Contacts table for individual contact management
CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(50),
    zip_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'USA',
    age INT,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say'),
    occupation VARCHAR(100),
    interests JSON,
    communication_preferences JSON,
    opt_in_email BOOLEAN DEFAULT TRUE,
    opt_in_sms BOOLEAN DEFAULT FALSE,
    opt_in_phone BOOLEAN DEFAULT FALSE,
    language_preference VARCHAR(50) DEFAULT 'English',
    source VARCHAR(100),
    notes TEXT,
    last_contacted TIMESTAMP NULL,
    engagement_score INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_name (first_name, last_name),
    INDEX idx_location (city, state),
    INDEX idx_active (is_active),
    INDEX idx_engagement (engagement_score),
    INDEX idx_last_contacted (last_contacted)
);

-- Many-to-many relationship between audience segments and contacts
CREATE TABLE audience_segment_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    contact_id INT NOT NULL,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    added_by INT,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (segment_id) REFERENCES audience_segments(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_segment_contact (segment_id, contact_id),
    INDEX idx_segment (segment_id),
    INDEX idx_contact (contact_id),
    INDEX idx_active (is_active)
);

-- Contact interactions tracking
CREATE TABLE contact_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NOT NULL,
    interaction_type ENUM('email_sent', 'email_opened', 'email_clicked', 'sms_sent', 'sms_replied', 
                         'call_made', 'call_answered', 'event_attended', 'survey_completed', 
                         'content_viewed', 'website_visited') NOT NULL,
    campaign_id INT NULL,
    content_id INT NULL,
    interaction_details JSON,
    interaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (content_id) REFERENCES content_items(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_contact_type (contact_id, interaction_type),
    INDEX idx_campaign (campaign_id),
    INDEX idx_date (interaction_date)
);

-- Insert sample audience segments
INSERT INTO audience_segments (name, description, criteria, contact_count, engagement_rate, avg_age, gender_distribution, location_data, interests, communication_preferences, created_by) VALUES

('Urban Drivers', 'Residents living in urban areas who drive regularly, interested in road safety campaigns', 
 '{"age_min": 18, "age_max": 65, "location": "urban", "interests": ["driving", "safety"], "owns_vehicle": true}', 
 1250, 24.7, 35, 
 '{"male": 52, "female": 46, "other": 2}',
 '{"urban": 100, "primary_cities": ["New York", "Los Angeles", "Chicago", "Houston"]}',
 '["road_safety", "driving", "automotive", "traffic_rules"]',
 '{"email": 85, "sms": 60, "phone": 25, "social_media": 70}', 1),

('Senior Citizens', 'Senior citizens aged 65+ interested in cybersecurity and online safety education', 
 '{"age_min": 65, "interests": ["technology", "online_safety"], "tech_comfort": "basic"}', 
 874, 31.2, 72, 
 '{"male": 48, "female": 50, "prefer_not_to_say": 2}',
 '{"suburban": 60, "urban": 25, "rural": 15}',
 '["cybersecurity", "online_safety", "technology", "education"]',
 '{"email": 45, "phone": 80, "mail": 90, "sms": 25}', 1),

('Young Families', 'Parents with children under 18, interested in family safety and child protection', 
 '{"age_min": 25, "age_max": 45, "has_children": true, "children_age_max": 18}', 
 642, 42.8, 32, 
 '{"male": 35, "female": 63, "other": 2}',
 '{"suburban": 70, "urban": 25, "rural": 5}',
 '["child_safety", "family_protection", "education", "parenting"]',
 '{"email": 90, "sms": 75, "social_media": 85, "phone": 40}', 2),

('Emergency Response Volunteers', 'Community members trained or interested in emergency response and disaster preparedness', 
 '{"interests": ["emergency_response", "disaster_prep"], "volunteer_experience": true}', 
 328, 56.4, 38, 
 '{"male": 58, "female": 40, "other": 2}',
 '{"suburban": 45, "urban": 35, "rural": 20}',
 '["emergency_response", "disaster_preparedness", "community_service", "training"]',
 '{"email": 95, "sms": 85, "phone": 70, "radio": 60}', 3),

('Local Business Owners', 'Small and medium business owners interested in workplace safety and compliance', 
 '{"role": "business_owner", "business_size": "small_medium", "interests": ["workplace_safety"]}', 
 156, 38.9, 45, 
 '{"male": 62, "female": 36, "other": 2}',
 '{"urban": 60, "suburban": 35, "rural": 5}',
 '["workplace_safety", "compliance", "business", "employee_welfare"]',
 '{"email": 100, "phone": 60, "mail": 40, "linkedin": 80}', 2),

('College Students', 'College students interested in personal safety, cybersecurity, and campus safety', 
 '{"age_min": 18, "age_max": 25, "education_level": "college", "interests": ["personal_safety"]}', 
 892, 28.6, 20, 
 '{"male": 45, "female": 52, "other": 3}',
 '{"urban": 80, "suburban": 15, "rural": 5}',
 '["personal_safety", "cybersecurity", "campus_safety", "technology"]',
 '{"email": 70, "sms": 95, "social_media": 98, "phone": 20}', 1);

-- Insert sample contact lists
INSERT INTO contact_lists (name, description, list_type, source, total_contacts, active_contacts, opted_in_contacts, tags, created_by) VALUES
('Road Safety Newsletter Subscribers', 'Subscribers to our road safety newsletter', 'static', 'website_signup', 1847, 1652, 1652, '["newsletter", "road_safety", "subscribers"]', 1),
('Fire Safety Workshop Attendees', 'People who attended fire safety workshops', 'static', 'event_registration', 423, 398, 385, '["workshop", "fire_safety", "attendees"]', 2),
('Cybersecurity Webinar Participants', 'Participants from cybersecurity webinars', 'static', 'webinar_registration', 756, 689, 645, '["webinar", "cybersecurity", "participants"]', 1),
('Community Emergency Contacts', 'Local emergency response volunteers and coordinators', 'dynamic', 'volunteer_database', 234, 221, 218, '["emergency", "volunteers", "community"]', 3),
('Business Safety Network', 'Local business owners interested in workplace safety', 'static', 'business_outreach', 167, 152, 149, '["business", "workplace_safety", "network"]', 2);

-- Insert sample contacts
INSERT INTO contacts (first_name, last_name, email, phone, address, city, state, zip_code, age, gender, occupation, interests, communication_preferences, opt_in_email, opt_in_sms, language_preference, source, engagement_score, created_by) VALUES

('Jennifer', 'Martinez', 'jennifer.martinez@email.com', '555-0101', '123 Main St', 'New York', 'NY', '10001', 34, 'female', 'Marketing Manager', 
 '["road_safety", "family_safety", "technology"]', '{"email": true, "sms": true, "phone": false}', TRUE, TRUE, 'English', 'website_signup', 75, 1),

('Robert', 'Johnson', 'robert.johnson@email.com', '555-0102', '456 Oak Ave', 'Los Angeles', 'CA', '90210', 28, 'male', 'Software Developer', 
 '["cybersecurity", "technology", "privacy"]', '{"email": true, "sms": false, "phone": false}', TRUE, FALSE, 'English', 'webinar_registration', 82, 1),

('Mary', 'Williams', 'mary.williams@email.com', '555-0103', '789 Pine St', 'Chicago', 'IL', '60601', 67, 'female', 'Retired Teacher', 
 '["online_safety", "education", "community"]', '{"email": true, "sms": false, "phone": true}', TRUE, FALSE, 'English', 'community_outreach', 68, 2),

('David', 'Brown', 'david.brown@email.com', '555-0104', '321 Elm St', 'Houston', 'TX', '77001', 42, 'male', 'Fire Chief', 
 '["fire_safety", "emergency_response", "training"]', '{"email": true, "sms": true, "phone": true}', TRUE, TRUE, 'English', 'professional_network', 91, 3),

('Lisa', 'Davis', 'lisa.davis@email.com', '555-0105', '654 Maple Dr', 'Phoenix', 'AZ', '85001', 29, 'female', 'Elementary Teacher', 
 '["child_safety", "education", "family"]', '{"email": true, "sms": true, "phone": false}', TRUE, TRUE, 'English', 'event_registration', 77, 1),

('Michael', 'Wilson', 'michael.wilson@email.com', '555-0106', '987 Cedar Ln', 'Philadelphia', 'PA', '19101', 53, 'male', 'Small Business Owner', 
 '["workplace_safety", "business", "compliance"]', '{"email": true, "sms": false, "phone": true}', TRUE, FALSE, 'English', 'business_outreach', 84, 2),

('Sarah', 'Miller', 'sarah.miller@email.com', '555-0107', '147 Birch St', 'San Antonio', 'TX', '78201', 21, 'female', 'College Student', 
 '["personal_safety", "campus_safety", "technology"]', '{"email": true, "sms": true, "phone": false}', TRUE, TRUE, 'English', 'campus_outreach', 63, 1),

('James', 'Moore', 'james.moore@email.com', '555-0108', '258 Spruce Ave', 'San Diego', 'CA', '92101', 36, 'male', 'EMT', 
 '["emergency_response", "medical", "community_service"]', '{"email": true, "sms": true, "phone": true}', TRUE, TRUE, 'English', 'volunteer_database', 89, 3);

-- Insert sample audience segment members
INSERT INTO audience_segment_members (segment_id, contact_id, added_by) VALUES
-- Urban Drivers
(1, 1, 1), (1, 2, 1), (1, 6, 1),
-- Senior Citizens  
(2, 3, 2),
-- Young Families
(3, 1, 1), (3, 5, 1),
-- Emergency Response Volunteers
(4, 4, 3), (4, 8, 3),
-- Local Business Owners
(5, 6, 2),
-- College Students
(6, 7, 1);

-- Insert sample contact interactions
INSERT INTO contact_interactions (contact_id, interaction_type, campaign_id, interaction_details, interaction_date, created_by) VALUES
(1, 'email_sent', 1, '{"subject": "Road Safety Tips for Urban Drivers", "template": "road_safety_newsletter"}', '2023-10-01 09:00:00', 1),
(1, 'email_opened', 1, '{"open_time": "2023-10-01 09:15:00", "device": "mobile"}', '2023-10-01 09:15:00', NULL),
(1, 'email_clicked', 1, '{"link": "road-safety-video", "click_time": "2023-10-01 09:18:00"}', '2023-10-01 09:18:00', NULL),

(2, 'email_sent', 2, '{"subject": "Cybersecurity Best Practices", "template": "cyber_safety_tips"}', '2023-10-02 10:00:00', 1),
(2, 'email_opened', 2, '{"open_time": "2023-10-02 14:30:00", "device": "desktop"}', '2023-10-02 14:30:00', NULL),

(4, 'sms_sent', 4, '{"message": "Fire safety workshop tomorrow at 2 PM. Bring ID.", "type": "reminder"}', '2023-09-19 16:00:00', 2),
(4, 'event_attended', 4, '{"event": "Fire Safety Workshop", "attendance_duration": "2 hours"}', '2023-09-20 14:00:00', 2),

(7, 'email_sent', 1, '{"subject": "Campus Safety Awareness Week", "template": "student_safety"}', '2023-10-03 11:00:00', 1),
(7, 'email_opened', 1, '{"open_time": "2023-10-03 19:45:00", "device": "mobile"}', '2023-10-03 19:45:00', NULL);
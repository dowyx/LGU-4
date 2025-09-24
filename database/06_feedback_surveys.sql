-- ===================================
-- Feedback & Surveys Module Tables
-- ===================================

-- Drop tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS survey_responses;
DROP TABLE IF EXISTS survey_questions;
DROP TABLE IF EXISTS surveys;
DROP TABLE IF EXISTS sms_alerts;

-- Surveys table for managing feedback collection
CREATE TABLE surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    survey_type ENUM('feedback', 'assessment', 'registration', 'evaluation', 'poll') DEFAULT 'feedback',
    campaign_id INT,
    event_id INT,
    status ENUM('draft', 'active', 'paused', 'closed') DEFAULT 'draft',
    start_date DATETIME,
    end_date DATETIME,
    max_responses INT DEFAULT 0, -- 0 means unlimited
    response_count INT DEFAULT 0,
    is_anonymous BOOLEAN DEFAULT FALSE,
    allow_multiple_responses BOOLEAN DEFAULT FALSE,
    require_login BOOLEAN DEFAULT FALSE,
    thank_you_message TEXT,
    completion_redirect_url VARCHAR(500),
    notification_email VARCHAR(255),
    survey_settings JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_campaign (campaign_id),
    INDEX idx_event (event_id),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- Survey questions for detailed feedback collection
CREATE TABLE survey_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('text', 'textarea', 'radio', 'checkbox', 'select', 'rating', 'scale', 'date', 'email', 'phone') NOT NULL,
    question_order INT NOT NULL,
    is_required BOOLEAN DEFAULT FALSE,
    options JSON, -- For radio, checkbox, select questions
    validation_rules JSON,
    help_text TEXT,
    conditional_logic JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    INDEX idx_survey_order (survey_id, question_order),
    INDEX idx_survey_type (survey_id, question_type)
);

-- Survey responses - updated to reference surveys table
CREATE TABLE survey_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    respondent_email VARCHAR(255),
    respondent_name VARCHAR(200),
    contact_id INT,
    response_data JSON NOT NULL,
    completion_time INT, -- seconds taken to complete
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer_url VARCHAR(500),
    is_complete BOOLEAN DEFAULT TRUE,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    INDEX idx_survey (survey_id),
    INDEX idx_respondent (respondent_email),
    INDEX idx_contact (contact_id),
    INDEX idx_submitted (submitted_at)
);

-- SMS alerts table for emergency notifications
CREATE TABLE sms_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    alert_type ENUM('emergency', 'warning', 'info', 'reminder', 'update') DEFAULT 'info',
    audience_segment_id INT,
    campaign_id INT,
    scheduled_date DATE,
    scheduled_time TIME,
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled') DEFAULT 'draft',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    sent_count INT DEFAULT 0,
    delivered_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    delivery_rate DECIMAL(5,2) DEFAULT 0.00,
    response_count INT DEFAULT 0,
    response_rate DECIMAL(5,2) DEFAULT 0.00,
    cost_per_message DECIMAL(6,4) DEFAULT 0.0000,
    total_cost DECIMAL(10,2) DEFAULT 0.00,
    sender_id VARCHAR(20),
    webhook_url VARCHAR(500),
    delivery_report JSON,
    created_by INT,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (audience_segment_id) REFERENCES audience_segments(id) ON DELETE SET NULL,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_scheduled (scheduled_date, scheduled_time),
    INDEX idx_alert_type (alert_type),
    INDEX idx_priority (priority),
    INDEX idx_audience (audience_segment_id)
);

-- Insert sample surveys
INSERT INTO surveys (title, description, survey_type, campaign_id, event_id, status, start_date, end_date, is_anonymous, thank_you_message, created_by) VALUES

('Road Safety Campaign Feedback', 'Feedback survey for participants in our road safety awareness campaign', 'feedback', 1, NULL, 'active', 
 '2023-10-01 00:00:00', '2023-12-15 23:59:59', FALSE, 
 'Thank you for your feedback! Your input helps us improve our road safety campaigns.', 1),

('Post-Workshop Evaluation', 'Evaluation survey for road safety workshop attendees', 'evaluation', 1, 1, 'active', 
 '2023-10-15 16:00:00', '2023-10-30 23:59:59', FALSE, 
 'Thank you for attending our workshop! Your feedback is valuable for future improvements.', 1),

('Cybersecurity Awareness Assessment', 'Pre and post campaign assessment to measure cybersecurity knowledge improvement', 'assessment', 2, NULL, 'active', 
 '2023-11-01 00:00:00', '2024-01-30 23:59:59', TRUE, 
 'Thank you for participating in our cybersecurity assessment!', 1),

('Fire Safety Community Survey', 'Community-wide survey about fire safety practices and awareness', 'poll', 4, NULL, 'active', 
 '2023-09-15 00:00:00', '2023-11-30 23:59:59', TRUE, 
 'Thank you for helping us understand community fire safety needs!', 2),

('Emergency Preparedness Needs Assessment', 'Survey to assess community emergency preparedness needs and gaps', 'assessment', 3, NULL, 'draft', 
 '2024-01-15 00:00:00', '2024-03-15 23:59:59', FALSE, 
 'Thank you for helping us improve emergency preparedness in our community!', 3);

-- Insert sample survey questions
INSERT INTO survey_questions (survey_id, question_text, question_type, question_order, is_required, options, help_text) VALUES

-- Road Safety Campaign Feedback Survey Questions
(1, 'How would you rate the overall effectiveness of our road safety campaign?', 'rating', 1, TRUE, '{"scale": 5, "labels": ["Poor", "Fair", "Good", "Very Good", "Excellent"]}', 'Please rate from 1 (Poor) to 5 (Excellent)'),
(1, 'Which road safety topics were most relevant to you?', 'checkbox', 2, TRUE, '["Defensive driving", "Traffic rules", "Pedestrian safety", "Vehicle maintenance", "Weather driving", "Emergency procedures"]', 'Select all that apply'),
(1, 'How did you hear about our road safety campaign?', 'radio', 3, TRUE, '["Social media", "Email newsletter", "Word of mouth", "Community event", "Local news", "Website", "Other"]', NULL),
(1, 'What improvements would you suggest for future campaigns?', 'textarea', 4, FALSE, NULL, 'Please provide specific suggestions'),
(1, 'Would you recommend our safety campaigns to others?', 'radio', 5, TRUE, '["Definitely yes", "Probably yes", "Maybe", "Probably no", "Definitely no"]', NULL),

-- Post-Workshop Evaluation Questions
(2, 'How would you rate the workshop content?', 'rating', 1, TRUE, '{"scale": 5, "labels": ["Poor", "Fair", "Good", "Very Good", "Excellent"]}', NULL),
(2, 'How would you rate the presenter?', 'rating', 2, TRUE, '{"scale": 5, "labels": ["Poor", "Fair", "Good", "Very Good", "Excellent"]}', NULL),
(2, 'Was the workshop duration appropriate?', 'radio', 3, TRUE, '["Too short", "Just right", "Too long"]', NULL),
(2, 'What was the most valuable part of the workshop?', 'textarea', 4, FALSE, NULL, NULL),
(2, 'What topics would you like to see covered in future workshops?', 'textarea', 5, FALSE, NULL, NULL),

-- Cybersecurity Assessment Questions
(3, 'How often do you update your passwords?', 'radio', 1, TRUE, '["Never", "Once a year", "Every 6 months", "Every 3 months", "Monthly", "As needed"]', NULL),
(3, 'Can you identify a phishing email?', 'radio', 2, TRUE, '["Always", "Usually", "Sometimes", "Rarely", "Never"]', NULL),
(3, 'Do you use two-factor authentication?', 'radio', 3, TRUE, '["Always", "For important accounts", "Sometimes", "Rarely", "Never", "Don''t know what it is"]', NULL),
(3, 'Rate your overall cybersecurity knowledge', 'scale', 4, TRUE, '{"min": 1, "max": 10, "step": 1}', 'Scale of 1-10, where 10 is expert level'),

-- Fire Safety Community Survey Questions
(4, 'Do you have working smoke detectors in your home?', 'radio', 1, TRUE, '["Yes, all working", "Yes, but some need batteries", "Yes, but unsure if working", "No", "Don''t know"]', NULL),
(4, 'When did you last check your smoke detector batteries?', 'radio', 2, TRUE, '["Within last month", "Within last 6 months", "Within last year", "Over a year ago", "Never checked", "Don''t remember"]', NULL),
(4, 'Do you have a fire escape plan for your home?', 'radio', 3, TRUE, '["Yes, written and practiced", "Yes, but not written", "Thought about it", "No plan", "Not applicable"]', NULL),
(4, 'What fire safety topics interest you most?', 'checkbox', 4, FALSE, '["Home fire prevention", "Kitchen safety", "Electrical safety", "Escape planning", "Fire extinguisher use", "Child fire safety"]', 'Select all that apply');

-- Insert sample survey responses
INSERT INTO survey_responses (survey_id, respondent_email, respondent_name, contact_id, response_data, completion_time, submitted_at) VALUES

-- Road Safety Campaign Feedback responses
(1, 'jennifer.martinez@email.com', 'Jennifer Martinez', 1, 
 '{"q1": 5, "q2": ["Defensive driving", "Traffic rules", "Emergency procedures"], "q3": "Email newsletter", "q4": "More interactive content and local examples", "q5": "Definitely yes"}',
 180, '2023-10-05 14:30:00'),

(1, 'robert.johnson@email.com', 'Robert Johnson', 2,
 '{"q1": 4, "q2": ["Traffic rules", "Vehicle maintenance"], "q3": "Social media", "q4": "More technical information about car safety features", "q5": "Probably yes"}',
 145, '2023-10-07 09:15:00'),

(1, 'sarah.miller@email.com', 'Sarah Miller', 7,
 '{"q1": 5, "q2": ["Pedestrian safety", "Emergency procedures"], "q3": "Community event", "q4": "Focus on campus safety and student-specific scenarios", "q5": "Definitely yes"}',
 165, '2023-10-08 19:45:00'),

-- Post-Workshop Evaluation responses
(2, 'jennifer.martinez@email.com', 'Jennifer Martinez', 1,
 '{"q1": 5, "q2": 5, "q3": "Just right", "q4": "The hands-on driving scenarios were excellent", "q5": "Advanced defensive driving techniques, night driving safety"}',
 120, '2023-10-15 15:30:00'),

(2, 'tom.anderson@email.com', 'Tom Anderson', NULL,
 '{"q1": 4, "q2": 5, "q3": "Just right", "q4": "Interactive discussions and real-world examples", "q5": "Weather-related driving, motorcycle safety"}',
 95, '2023-10-15 15:45:00'),

-- Cybersecurity Assessment responses
(3, 'mary.williams@email.com', 'Mary Williams', 3,
 '{"q1": "Once a year", "q2": "Sometimes", "q3": "For important accounts", "q4": 6}',
 240, '2023-11-02 16:20:00'),

(3, NULL, 'Anonymous User', NULL,
 '{"q1": "Every 3 months", "q2": "Usually", "q3": "Always", "q4": 8}',
 180, '2023-11-03 10:15:00'),

-- Fire Safety Community Survey responses
(4, NULL, 'Anonymous Resident', NULL,
 '{"q1": "Yes, all working", "q2": "Within last 6 months", "q3": "Yes, written and practiced", "q4": ["Home fire prevention", "Kitchen safety", "Escape planning"]}',
 155, '2023-09-25 20:30:00'),

(4, NULL, 'Anonymous Resident', NULL,
 '{"q1": "Yes, but some need batteries", "q2": "Within last year", "q3": "Thought about it", "q4": ["Kitchen safety", "Fire extinguisher use"]}',
 135, '2023-09-28 14:15:00');

-- Insert sample SMS alerts
INSERT INTO sms_alerts (title, message, alert_type, audience_segment_id, campaign_id, scheduled_date, scheduled_time, status, priority, sent_count, delivered_count, delivery_rate, created_by, sent_at) VALUES

('Road Safety Workshop Reminder', 'Reminder: Road Safety Workshop tomorrow at 10 AM at Community Center. Bring ID for registration. Reply STOP to opt out.', 'reminder', 1, 1, 
 '2023-10-14', '18:00:00', 'sent', 'normal', 45, 43, 95.56, 1, '2023-10-14 18:00:00'),

('Emergency Weather Alert', 'WEATHER ALERT: Severe thunderstorm warning in effect. Avoid unnecessary travel. Stay indoors and away from windows. More info: bit.ly/weather-safety', 'warning', NULL, NULL, 
 '2023-10-10', '15:30:00', 'sent', 'urgent', 2847, 2801, 98.38, 3, '2023-10-10 15:30:00'),

('Fire Safety Tips Weekly', 'FIRE SAFETY TIP: Test your smoke detectors monthly. Change batteries twice a year. A working detector can save your life! More tips: safetycampaign.org/fire', 'info', 4, 4, 
 '2023-10-01', '09:00:00', 'sent', 'normal', 328, 321, 97.87, 2, '2023-10-01 09:00:00'),

('Cybersecurity Seminar Confirmation', 'Your registration for the Cybersecurity Seminar on Oct 22 at 2:30 PM is confirmed. Central Library Conference Room. Questions? Call 555-0200', 'reminder', 2, 2, 
 '2023-10-20', '10:00:00', 'sent', 'normal', 38, 36, 94.74, 1, '2023-10-20 10:00:00'),

('Community Emergency Drill', 'SCHEDULED: Community Emergency Drill this Saturday 10 AM. Practice evacuation routes. Not mandatory but encouraged. Info: emergency.gov/drill', 'info', 4, 3, 
 '2023-11-15', '08:00:00', 'scheduled', 'normal', 0, 0, 0.00, 3, NULL);

-- Update response counts for surveys
UPDATE surveys SET response_count = (
    SELECT COUNT(*) FROM survey_responses 
    WHERE survey_responses.survey_id = surveys.id
) WHERE id IN (1, 2, 3, 4);
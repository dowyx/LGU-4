-- ===================================
-- Event Management Module Tables
-- ===================================

-- Drop tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS event_feedback;
DROP TABLE IF EXISTS event_registrations;
DROP TABLE IF EXISTS event_staff;
DROP TABLE IF EXISTS venues;
DROP TABLE IF EXISTS events;

-- Venues table for event locations
CREATE TABLE venues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(50),
    zip_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'USA',
    capacity INT,
    venue_type ENUM('indoor', 'outdoor', 'hybrid') DEFAULT 'indoor',
    facilities JSON,
    accessibility_features JSON,
    parking_available BOOLEAN DEFAULT TRUE,
    public_transport_access BOOLEAN DEFAULT FALSE,
    contact_person VARCHAR(200),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(255),
    hourly_rate DECIMAL(8,2),
    setup_time INT DEFAULT 60, -- minutes
    cleanup_time INT DEFAULT 60, -- minutes
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_city_state (city, state),
    INDEX idx_capacity (capacity),
    INDEX idx_venue_type (venue_type),
    INDEX idx_active (is_active)
);

-- Events table for managing all campaign events
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_type ENUM('workshop', 'seminar', 'webinar', 'training', 'conference', 'community_meeting', 'awareness_drive') DEFAULT 'workshop',
    event_date DATETIME NOT NULL,
    end_date DATETIME,
    venue_id INT,
    location VARCHAR(255), -- For manual location entry or online events
    max_capacity INT DEFAULT 0,
    registered_count INT DEFAULT 0,
    attended_count INT DEFAULT 0,
    campaign_id INT,
    status ENUM('planning', 'scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'planning',
    registration_required BOOLEAN DEFAULT TRUE,
    registration_deadline DATETIME,
    is_online BOOLEAN DEFAULT FALSE,
    meeting_link VARCHAR(500),
    materials_needed JSON,
    staff_required JSON,
    target_audience JSON,
    agenda TEXT,
    special_requirements TEXT,
    cost_per_person DECIMAL(8,2) DEFAULT 0.00,
    total_budget DECIMAL(10,2) DEFAULT 0.00,
    actual_cost DECIMAL(10,2) DEFAULT 0.00,
    feedback_avg_rating DECIMAL(3,2) DEFAULT 0.00,
    follow_up_required BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE SET NULL,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date),
    INDEX idx_campaign (campaign_id),
    INDEX idx_status (status),
    INDEX idx_venue (venue_id),
    INDEX idx_online (is_online)
);

-- Event staff assignments
CREATE TABLE event_staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('organizer', 'presenter', 'coordinator', 'technical_support', 'registration', 'security') NOT NULL,
    responsibilities TEXT,
    arrival_time DATETIME,
    is_confirmed BOOLEAN DEFAULT FALSE,
    compensation DECIMAL(8,2) DEFAULT 0.00,
    notes TEXT,
    assigned_by INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_event_user_role (event_id, user_id, role),
    INDEX idx_event_staff (event_id, role),
    INDEX idx_user_events (user_id)
);

-- Event registrations for tracking attendees
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    contact_id INT,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    organization VARCHAR(200),
    dietary_restrictions TEXT,
    accessibility_needs TEXT,
    registration_source ENUM('website', 'email', 'phone', 'walk_in', 'social_media') DEFAULT 'website',
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    attendance_status ENUM('registered', 'confirmed', 'attended', 'no_show', 'cancelled') DEFAULT 'registered',
    attended_at TIMESTAMP NULL,
    check_in_method ENUM('manual', 'qr_code', 'app', 'email') DEFAULT 'manual',
    payment_status ENUM('free', 'paid', 'pending', 'refunded') DEFAULT 'free',
    payment_amount DECIMAL(8,2) DEFAULT 0.00,
    confirmation_sent BOOLEAN DEFAULT FALSE,
    reminder_sent BOOLEAN DEFAULT FALSE,
    follow_up_sent BOOLEAN DEFAULT FALSE,
    notes TEXT,
    
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE SET NULL,
    INDEX idx_event_registration (event_id, attendance_status),
    INDEX idx_email (email),
    INDEX idx_registration_date (registration_date),
    INDEX idx_contact (contact_id)
);

-- Event feedback collection
CREATE TABLE event_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    registration_id INT,
    overall_rating INT CHECK (overall_rating >= 1 AND overall_rating <= 5),
    content_rating INT CHECK (content_rating >= 1 AND content_rating <= 5),
    presenter_rating INT CHECK (presenter_rating >= 1 AND presenter_rating <= 5),
    venue_rating INT CHECK (venue_rating >= 1 AND venue_rating <= 5),
    organization_rating INT CHECK (organization_rating >= 1 AND organization_rating <= 5),
    would_recommend BOOLEAN DEFAULT TRUE,
    most_valuable_aspect TEXT,
    suggested_improvements TEXT,
    additional_topics_interest TEXT,
    would_attend_future BOOLEAN DEFAULT TRUE,
    comments TEXT,
    feedback_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (registration_id) REFERENCES event_registrations(id) ON DELETE SET NULL,
    INDEX idx_event_feedback (event_id),
    INDEX idx_ratings (overall_rating, content_rating),
    INDEX idx_feedback_date (feedback_date)
);

-- Insert sample venues
INSERT INTO venues (name, address, city, state, zip_code, capacity, venue_type, facilities, accessibility_features, parking_available, public_transport_access, contact_person, contact_phone, contact_email, hourly_rate, created_by) VALUES

('Community Center Main Hall', '123 Community Drive', 'Springfield', 'IL', '62701', 200, 'indoor', 
 '["projector", "sound_system", "microphones", "chairs", "tables", "air_conditioning", "wifi"]', 
 '["wheelchair_accessible", "accessible_parking", "accessible_restrooms", "hearing_loop"]', TRUE, TRUE, 
 'Sarah Johnson', '555-0201', 'sarah@communitycenter.org', 75.00, 1),

('Central Library Conference Room', '456 Library Street', 'Springfield', 'IL', '62702', 50, 'indoor', 
 '["projector", "whiteboard", "wifi", "video_conferencing", "chairs", "tables"]', 
 '["wheelchair_accessible", "accessible_parking", "accessible_restrooms"]', TRUE, TRUE, 
 'Mike Wilson', '555-0202', 'mike@centrallibrary.org', 50.00, 2),

('Fire Station #3 Training Room', '789 Safety Avenue', 'Springfield', 'IL', '62703', 80, 'indoor', 
 '["training_equipment", "projector", "sound_system", "emergency_equipment", "parking"]', 
 '["wheelchair_accessible", "accessible_parking"]', TRUE, FALSE, 
 'Chief Robert Brown', '555-0203', 'chief@firestation3.gov', 25.00, 3),

('City Park Pavilion', '321 Park Lane', 'Springfield', 'IL', '62704', 150, 'outdoor', 
 '["covered_area", "tables", "benches", "electrical_outlets", "restrooms"]', 
 '["wheelchair_accessible", "accessible_parking", "accessible_restrooms"]', TRUE, FALSE, 
 'Park Services', '555-0204', 'info@cityparks.gov', 40.00, 1),

('Springfield Convention Center', '987 Convention Way', 'Springfield', 'IL', '62705', 500, 'indoor', 
 '["multiple_rooms", "projectors", "sound_systems", "wifi", "catering_kitchen", "registration_area"]', 
 '["fully_accessible", "accessible_parking", "accessible_restrooms", "assistive_listening"]', TRUE, TRUE, 
 'Events Coordinator', '555-0205', 'events@springfieldcc.com', 150.00, 2);

-- Insert sample events
INSERT INTO events (title, description, event_type, event_date, end_date, venue_id, max_capacity, campaign_id, status, registration_required, registration_deadline, target_audience, agenda, total_budget, created_by) VALUES

('Road Safety Workshop for New Drivers', 'Comprehensive workshop covering road safety basics for new and inexperienced drivers', 'workshop', 
 '2023-10-15 10:00:00', '2023-10-15 15:00:00', 1, 50, 1, 'scheduled', TRUE, '2023-10-14 23:59:59', 
 '["new_drivers", "teen_drivers", "parents"]', 
 'Introduction to Road Safety, Traffic Rules Overview, Defensive Driving Techniques, Emergency Procedures, Q&A Session', 
 500.00, 1),

('Cybersecurity Seminar for Seniors', 'Educational seminar focused on online safety and cybersecurity for senior citizens', 'seminar', 
 '2023-10-22 14:30:00', '2023-10-22 17:00:00', 2, 40, 2, 'scheduled', TRUE, '2023-10-21 23:59:59', 
 '["seniors", "retirees", "older_adults"]', 
 'Online Safety Basics, Password Security, Recognizing Scams, Safe Online Shopping, Social Media Safety', 
 300.00, 1),

('Emergency Response Training', 'Hands-on training for community emergency response team members', 'training', 
 '2023-11-05 09:00:00', '2023-11-05 16:00:00', 3, 30, 3, 'scheduled', TRUE, '2023-11-04 23:59:59', 
 '["volunteers", "emergency_responders", "community_leaders"]', 
 'Emergency Assessment, First Aid Basics, Communication Protocols, Evacuation Procedures, Equipment Training', 
 800.00, 3),

('Fire Safety Community Meeting', 'Community meeting to discuss fire prevention and home safety measures', 'community_meeting', 
 '2023-10-28 19:00:00', '2023-10-28 21:00:00', 4, 100, 4, 'scheduled', FALSE, NULL, 
 '["homeowners", "renters", "families", "general_public"]', 
 'Fire Prevention Tips, Smoke Detector Maintenance, Escape Plan Development, Q&A with Fire Chief', 
 200.00, 2),

('Youth Safety Awareness Drive', 'Interactive awareness event for teenagers about personal safety', 'awareness_drive', 
 '2023-11-12 10:00:00', '2023-11-12 14:00:00', 4, 80, 1, 'planning', TRUE, '2023-11-11 23:59:59', 
 '["teenagers", "high_school_students", "youth_groups"]', 
 'Personal Safety Tips, Cyber Bullying Prevention, Stranger Safety, Emergency Contacts, Interactive Activities', 
 600.00, 1),

('Business Safety Compliance Workshop', 'Workshop for business owners on workplace safety regulations and compliance', 'workshop', 
 '2023-11-18 13:00:00', '2023-11-18 17:00:00', 5, 60, 1, 'planning', TRUE, '2023-11-17 23:59:59', 
 '["business_owners", "managers", "hr_professionals"]', 
 'OSHA Compliance Overview, Workplace Hazard Assessment, Employee Safety Training, Emergency Procedures', 
 750.00, 2);

-- Insert sample event staff assignments
INSERT INTO event_staff (event_id, user_id, role, responsibilities, arrival_time, is_confirmed, assigned_by) VALUES
(1, 1, 'organizer', 'Overall event coordination and logistics', '2023-10-15 08:30:00', TRUE, 1),
(1, 2, 'presenter', 'Present road safety content and lead discussions', '2023-10-15 09:30:00', TRUE, 1),
(1, 4, 'registration', 'Handle participant registration and check-in', '2023-10-15 09:00:00', TRUE, 1),

(2, 1, 'organizer', 'Event coordination and senior audience management', '2023-10-22 13:30:00', TRUE, 1),
(2, 3, 'presenter', 'Lead cybersecurity presentation and demonstrations', '2023-10-22 14:00:00', TRUE, 1),
(2, 5, 'technical_support', 'Assist with technology and equipment', '2023-10-22 13:00:00', TRUE, 1),

(3, 3, 'organizer', 'Training coordination and emergency protocols', '2023-11-05 08:00:00', TRUE, 3),
(3, 1, 'presenter', 'Lead emergency response training sessions', '2023-11-05 08:30:00', TRUE, 3),

(4, 2, 'organizer', 'Community meeting facilitation', '2023-10-28 18:30:00', TRUE, 2),
(4, 3, 'presenter', 'Fire safety presentation and Q&A', '2023-10-28 18:45:00', TRUE, 2);

-- Insert sample event registrations
INSERT INTO event_registrations (event_id, contact_id, first_name, last_name, email, phone, organization, registration_source, attendance_status, confirmation_sent, reminder_sent) VALUES

-- Road Safety Workshop registrations
(1, 1, 'Jennifer', 'Martinez', 'jennifer.martinez@email.com', '555-0101', NULL, 'website', 'confirmed', TRUE, TRUE),
(1, 7, 'Sarah', 'Miller', 'sarah.miller@email.com', '555-0107', 'Springfield College', 'website', 'registered', TRUE, FALSE),
(1, NULL, 'Tom', 'Anderson', 'tom.anderson@email.com', '555-0301', NULL, 'email', 'registered', TRUE, FALSE),
(1, NULL, 'Maria', 'Garcia', 'maria.garcia@email.com', '555-0302', NULL, 'website', 'confirmed', TRUE, TRUE),

-- Cybersecurity Seminar registrations
(2, 3, 'Mary', 'Williams', 'mary.williams@email.com', '555-0103', NULL, 'phone', 'confirmed', TRUE, TRUE),
(2, NULL, 'Harold', 'Smith', 'harold.smith@email.com', '555-0303', NULL, 'website', 'registered', TRUE, FALSE),
(2, NULL, 'Betty', 'Jones', 'betty.jones@email.com', '555-0304', 'Senior Center', 'walk_in', 'registered', FALSE, FALSE),

-- Emergency Response Training registrations
(3, 4, 'David', 'Brown', 'david.brown@email.com', '555-0104', 'Fire Department', 'phone', 'confirmed', TRUE, TRUE),
(3, 8, 'James', 'Moore', 'james.moore@email.com', '555-0108', 'Emergency Services', 'email', 'confirmed', TRUE, TRUE),
(3, NULL, 'Lisa', 'Taylor', 'lisa.taylor@email.com', '555-0305', 'Red Cross', 'website', 'registered', TRUE, FALSE),

-- Fire Safety Community Meeting registrations (walk-ins welcome)
(4, 5, 'Lisa', 'Davis', 'lisa.davis@email.com', '555-0105', NULL, 'email', 'registered', TRUE, FALSE),
(4, 6, 'Michael', 'Wilson', 'michael.wilson@email.com', '555-0106', 'Wilson Hardware', 'website', 'registered', TRUE, FALSE);

-- Insert sample event feedback
INSERT INTO event_feedback (event_id, registration_id, overall_rating, content_rating, presenter_rating, venue_rating, organization_rating, would_recommend, most_valuable_aspect, suggested_improvements, would_attend_future, comments) VALUES

(1, 1, 5, 5, 5, 4, 5, TRUE, 'Practical driving scenarios and hands-on demonstrations', 'More time for Q&A session', TRUE, 'Excellent workshop! Very informative and well-organized.'),
(1, 4, 4, 4, 5, 4, 4, TRUE, 'Interactive nature of the presentation', 'Provide printed materials to take home', TRUE, 'Great presenter, very engaging content.'),

(2, 5, 5, 5, 4, 5, 5, TRUE, 'Step-by-step password security demonstration', 'More examples of common scams', TRUE, 'Perfect for seniors! Very patient instructor.'),
(2, 6, 4, 4, 4, 5, 4, TRUE, 'Clear explanations of technical concepts', 'Slower pace for note-taking', TRUE, 'Good content, appreciated the simple explanations.');

-- Update registered_count for events based on registrations
UPDATE events SET registered_count = (
    SELECT COUNT(*) FROM event_registrations 
    WHERE event_registrations.event_id = events.id
) WHERE id IN (1, 2, 3, 4);
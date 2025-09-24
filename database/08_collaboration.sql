-- ===================================
-- Collaboration Module Tables
-- ===================================

-- Drop tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS shared_resources;
DROP TABLE IF EXISTS team_messages;
DROP TABLE IF EXISTS project_tasks;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS team_members;

-- Team members table for collaboration management
CREATE TABLE team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    team_role ENUM('lead', 'coordinator', 'specialist', 'contributor', 'observer') DEFAULT 'contributor',
    department VARCHAR(100),
    specialties JSON,
    availability_hours JSON,
    contact_preferences JSON,
    skills JSON,
    experience_level ENUM('junior', 'intermediate', 'senior', 'expert') DEFAULT 'intermediate',
    max_concurrent_projects INT DEFAULT 3,
    current_project_count INT DEFAULT 0,
    workload_percentage DECIMAL(5,2) DEFAULT 0.00,
    last_active TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    joined_date DATE DEFAULT (CURRENT_DATE),
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_team (user_id),
    INDEX idx_team_role (team_role),
    INDEX idx_department (department),
    INDEX idx_active (is_active),
    INDEX idx_availability (workload_percentage)
);

-- Projects table for collaborative work management
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    project_type ENUM('campaign', 'content_creation', 'event_planning', 'research', 'training', 'outreach') DEFAULT 'campaign',
    campaign_id INT,
    status ENUM('planning', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'planning',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    start_date DATE,
    due_date DATE,
    completion_date DATE,
    progress_percentage INT DEFAULT 0,
    
    -- Team Management
    project_lead INT,
    team_size INT DEFAULT 0,
    required_skills JSON,
    
    -- Budget and Resources
    budget DECIMAL(12,2) DEFAULT 0.00,
    actual_cost DECIMAL(12,2) DEFAULT 0.00,
    resource_requirements JSON,
    
    -- Collaboration Settings
    visibility ENUM('public', 'team', 'private') DEFAULT 'team',
    allow_external_collaboration BOOLEAN DEFAULT FALSE,
    communication_channels JSON,
    
    -- Project Metrics
    total_tasks INT DEFAULT 0,
    completed_tasks INT DEFAULT 0,
    overdue_tasks INT DEFAULT 0,
    team_satisfaction DECIMAL(3,2) DEFAULT 0.00,
    
    -- Documentation
    project_charter TEXT,
    success_criteria TEXT,
    risk_assessment TEXT,
    lessons_learned TEXT,
    final_report TEXT,
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (project_lead) REFERENCES team_members(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_dates (start_date, due_date),
    INDEX idx_campaign (campaign_id),
    INDEX idx_lead (project_lead)
);

-- Project tasks for detailed work tracking
CREATE TABLE project_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    task_type ENUM('design', 'content', 'development', 'research', 'coordination', 'review', 'approval', 'delivery') DEFAULT 'coordination',
    status ENUM('todo', 'in_progress', 'review', 'blocked', 'completed', 'cancelled') DEFAULT 'todo',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    
    -- Assignment and Timing
    assigned_to INT,
    assigned_by INT,
    due_date DATE,
    estimated_hours DECIMAL(5,2),
    actual_hours DECIMAL(5,2) DEFAULT 0.00,
    completion_date TIMESTAMP NULL,
    
    -- Dependencies and Prerequisites
    depends_on JSON, -- Array of task IDs
    blocks_tasks JSON, -- Array of task IDs
    prerequisites TEXT,
    
    -- Progress Tracking
    progress_percentage INT DEFAULT 0,
    quality_score DECIMAL(3,2) DEFAULT 0.00,
    review_status ENUM('not_required', 'pending', 'approved', 'needs_revision') DEFAULT 'not_required',
    reviewed_by INT,
    review_date TIMESTAMP NULL,
    review_comments TEXT,
    
    -- Collaboration
    collaborators JSON, -- Array of team member IDs
    comments_count INT DEFAULT 0,
    attachments_count INT DEFAULT 0,
    
    -- Metadata
    tags JSON,
    custom_fields JSON,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES team_members(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES team_members(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES team_members(id) ON DELETE SET NULL,
    INDEX idx_project_status (project_id, status),
    INDEX idx_assigned (assigned_to),
    INDEX idx_due_date (due_date),
    INDEX idx_priority (priority)
);

-- Team messages for communication
CREATE TABLE team_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    task_id INT,
    sender_id INT NOT NULL,
    message_type ENUM('general', 'update', 'question', 'announcement', 'urgent', 'feedback') DEFAULT 'general',
    subject VARCHAR(255),
    message_content TEXT NOT NULL,
    
    -- Recipients
    recipients JSON, -- Array of team member IDs, empty for project-wide
    is_broadcast BOOLEAN DEFAULT FALSE,
    
    -- Message Properties
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    requires_response BOOLEAN DEFAULT FALSE,
    response_deadline TIMESTAMP NULL,
    
    -- Attachments and References
    attachments JSON,
    referenced_content JSON, -- References to content, campaigns, etc.
    
    -- Thread Management
    parent_message_id INT,
    thread_id INT,
    is_thread_starter BOOLEAN DEFAULT TRUE,
    replies_count INT DEFAULT 0,
    
    -- Status Tracking
    is_read JSON, -- Object mapping team member IDs to read status
    read_count INT DEFAULT 0,
    response_count INT DEFAULT 0,
    
    -- Message Metadata
    edit_history JSON,
    is_edited BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES project_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES team_members(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES team_messages(id) ON DELETE SET NULL,
    INDEX idx_project_messages (project_id, created_at),
    INDEX idx_task_messages (task_id, created_at),
    INDEX idx_sender (sender_id),
    INDEX idx_thread (thread_id),
    INDEX idx_priority (priority)
);

-- Shared resources for collaboration
CREATE TABLE shared_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    resource_type ENUM('template', 'guide', 'tool', 'dataset', 'reference', 'checklist', 'contact_list') NOT NULL,
    category VARCHAR(100),
    file_path VARCHAR(500),
    file_size INT,
    file_format VARCHAR(20),
    thumbnail_path VARCHAR(500),
    
    -- Access Control
    visibility ENUM('public', 'team', 'project', 'private') DEFAULT 'team',
    project_id INT,
    owner_id INT NOT NULL,
    access_permissions JSON, -- Team member access levels
    
    -- Usage Tracking
    download_count INT DEFAULT 0,
    views_count INT DEFAULT 0,
    last_accessed TIMESTAMP NULL,
    usage_statistics JSON,
    
    -- Version Control
    version VARCHAR(20) DEFAULT '1.0',
    version_notes TEXT,
    is_latest_version BOOLEAN DEFAULT TRUE,
    parent_resource_id INT, -- For version tracking
    
    -- Metadata
    tags JSON,
    keywords JSON,
    related_campaigns JSON,
    related_projects JSON,
    
    -- Quality and Approval
    approval_status ENUM('draft', 'review', 'approved', 'archived') DEFAULT 'draft',
    approved_by INT,
    approval_date TIMESTAMP NULL,
    quality_rating DECIMAL(3,2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (owner_id) REFERENCES team_members(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES team_members(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_resource_id) REFERENCES shared_resources(id) ON DELETE SET NULL,
    INDEX idx_type_category (resource_type, category),
    INDEX idx_project (project_id),
    INDEX idx_owner (owner_id),
    INDEX idx_visibility (visibility),
    INDEX idx_approval (approval_status),
    FULLTEXT(name, description)
);

-- Insert sample team members
INSERT INTO team_members (user_id, team_role, department, specialties, availability_hours, skills, experience_level, max_concurrent_projects, current_project_count, workload_percentage, created_by) VALUES

(1, 'lead', 'Public Safety', '["campaign_management", "public_speaking", "community_outreach"]', 
 '{"monday": "9-17", "tuesday": "9-17", "wednesday": "9-17", "thursday": "9-17", "friday": "9-15"}',
 '["project_management", "public_relations", "data_analysis", "presentation"]', 'senior', 5, 3, 75.00, 1),

(2, 'specialist', 'Marketing', '["content_creation", "social_media", "graphic_design"]',
 '{"monday": "10-18", "tuesday": "10-18", "wednesday": "10-18", "thursday": "10-18", "friday": "10-16"}',
 '["adobe_creative", "social_media_management", "copywriting", "video_editing"]', 'intermediate', 4, 2, 50.00, 1),

(3, 'specialist', 'Analytics', '["data_analysis", "reporting", "research"]',
 '{"monday": "8-16", "tuesday": "8-16", "wednesday": "8-16", "thursday": "8-16", "friday": "8-14"}',
 '["statistical_analysis", "excel", "tableau", "survey_design", "research_methods"]', 'senior', 3, 2, 65.00, 1),

(4, 'coordinator', 'Outreach', '["community_engagement", "event_planning", "volunteer_management"]',
 '{"monday": "9-17", "tuesday": "9-17", "wednesday": "9-17", "thursday": "9-17", "friday": "9-17", "saturday": "10-14"}',
 '["event_management", "volunteer_coordination", "public_speaking", "networking"]', 'intermediate', 4, 3, 80.00, 1),

(5, 'contributor', 'IT', '["technical_support", "web_development", "database_management"]',
 '{"monday": "9-17", "tuesday": "9-17", "wednesday": "9-17", "thursday": "9-17", "friday": "9-17"}',
 '["php", "mysql", "javascript", "system_administration", "troubleshooting"]', 'expert', 2, 1, 30.00, 1);

-- Insert sample projects
INSERT INTO projects (name, description, project_type, campaign_id, status, priority, start_date, due_date, project_lead, budget, required_skills, visibility, total_tasks, completed_tasks, success_criteria, created_by) VALUES

('Road Safety Campaign Content Development', 'Comprehensive content creation project for road safety awareness campaign including videos, posters, and social media materials', 'content_creation', 1, 'active', 'high', '2023-09-15', '2023-11-30', 1, 8000.00,
 '["graphic_design", "video_editing", "copywriting", "social_media"]', 'team', 15, 11,
 'Create 12 unique content pieces, achieve 70% engagement rate, deliver on time and within budget', 1),

('Fire Prevention Community Outreach', 'Multi-faceted community outreach project for fire prevention campaign including workshops, door-to-door visits, and partnership development', 'outreach', 4, 'active', 'high', '2023-09-01', '2023-12-15', 4, 5000.00,
 '["community_engagement", "public_speaking", "event_planning", "partnership_development"]', 'team', 20, 14,
 'Conduct 8 workshops, reach 500 households, establish 5 community partnerships', 2),

('Cybersecurity Education Research', 'Research project to develop effective cybersecurity education materials for senior citizens', 'research', 2, 'active', 'medium', '2023-10-01', '2024-01-15', 3, 3000.00,
 '["research_methods", "survey_design", "data_analysis", "report_writing"]', 'team', 8, 3,
 'Complete needs assessment, develop curriculum framework, create evaluation metrics', 1),

('Emergency Response Training Program', 'Development of comprehensive training program for community emergency response volunteers', 'training', 3, 'planning', 'high', '2023-11-01', '2024-02-29', 1, 12000.00,
 '["training_development", "emergency_response", "curriculum_design", "assessment_creation"]', 'team', 12, 0,
 'Develop 40-hour training curriculum, train 50 volunteers, achieve 90% certification rate', 3),

('Annual Safety Campaign Report', 'Comprehensive annual report compilation and analysis of all safety campaign initiatives', 'research', NULL, 'active', 'medium', '2023-12-01', '2024-01-31', 3, 2000.00,
 '["data_analysis", "report_writing", "graphic_design", "presentation"]', 'public', 6, 1,
 'Complete comprehensive annual analysis, present findings to stakeholders, publish public report', 1);

-- Insert sample project tasks
INSERT INTO project_tasks (project_id, title, description, task_type, status, priority, assigned_to, due_date, estimated_hours, actual_hours, progress_percentage, tags, assigned_by) VALUES

-- Road Safety Content Development Tasks
(1, 'Create Road Safety Video Script', 'Write comprehensive script for 3-minute road safety awareness video', 'content', 'completed', 'high', 2, '2023-10-01', 8.0, 9.5, 100, '["script", "video", "content"]', 1),
(1, 'Design Safety Infographic Series', 'Create 5 infographics covering key road safety topics', 'design', 'completed', 'medium', 2, '2023-10-15', 16.0, 18.0, 100, '["design", "infographic", "series"]', 1),
(1, 'Social Media Content Calendar', 'Develop 30-day social media content calendar with daily posts', 'content', 'in_progress', 'medium', 2, '2023-10-25', 12.0, 8.0, 75, '["social_media", "calendar", "planning"]', 1),
(1, 'Video Production and Editing', 'Produce and edit road safety awareness video', 'development', 'review', 'high', 2, '2023-10-30', 20.0, 22.0, 95, '["video", "production", "editing"]', 1),

-- Fire Prevention Outreach Tasks
(2, 'Workshop Curriculum Development', 'Develop comprehensive fire safety workshop curriculum', 'content', 'completed', 'high', 4, '2023-09-15', 15.0, 16.5, 100, '["curriculum", "workshop", "fire_safety"]', 4),
(2, 'Community Partnership Outreach', 'Establish partnerships with local organizations', 'coordination', 'completed', 'medium', 4, '2023-09-30', 10.0, 12.0, 100, '["partnership", "outreach", "community"]', 4),
(2, 'Door-to-Door Campaign Planning', 'Plan and organize door-to-door fire safety visits', 'coordination', 'in_progress', 'medium', 4, '2023-11-15', 8.0, 4.0, 60, '["door_to_door", "planning", "visits"]', 4),
(2, 'Workshop Material Preparation', 'Prepare all materials and supplies for workshops', 'coordination', 'todo', 'medium', 4, '2023-11-30', 6.0, 0.0, 0, '["materials", "preparation", "workshop"]', 4),

-- Cybersecurity Research Tasks
(3, 'Literature Review on Senior Cybersecurity', 'Comprehensive review of existing research on senior cybersecurity education', 'research', 'completed', 'high', 3, '2023-10-15', 20.0, 18.5, 100, '["literature_review", "research", "seniors"]', 3),
(3, 'Senior Focus Group Planning', 'Plan and organize focus groups with senior citizens', 'coordination', 'in_progress', 'medium', 3, '2023-11-01', 8.0, 5.0, 70, '["focus_group", "planning", "seniors"]', 3),
(3, 'Survey Instrument Development', 'Develop survey instruments for needs assessment', 'research', 'todo', 'high', 3, '2023-11-15', 12.0, 0.0, 0, '["survey", "instrument", "assessment"]', 3),

-- Emergency Response Training Tasks
(4, 'Training Needs Assessment', 'Assess current community emergency response training needs', 'research', 'todo', 'high', 1, '2023-11-15', 16.0, 0.0, 0, '["needs_assessment", "training", "emergency"]', 1),
(4, 'Curriculum Framework Design', 'Design overall framework for emergency response training curriculum', 'design', 'todo', 'high', 1, '2023-12-01', 20.0, 0.0, 0, '["curriculum", "framework", "design"]', 1),

-- Annual Report Tasks
(5, 'Data Collection and Compilation', 'Collect and compile data from all campaign initiatives', 'research', 'completed', 'high', 3, '2023-12-15', 24.0, 26.0, 100, '["data_collection", "compilation", "analysis"]', 3),
(5, 'Statistical Analysis', 'Perform statistical analysis of campaign effectiveness', 'research', 'in_progress', 'high', 3, '2023-12-31', 16.0, 8.0, 50, '["statistics", "analysis", "effectiveness"]', 3);

-- Insert sample team messages
INSERT INTO team_messages (project_id, task_id, sender_id, message_type, subject, message_content, recipients, priority, attachments, created_at) VALUES

(1, 1, 1, 'update', 'Video Script Review Complete', 'The road safety video script has been reviewed and approved. Great work on incorporating the community feedback! Ready to move to production phase.', '[2]', 'normal', '[]', '2023-10-02 09:15:00'),

(1, 4, 2, 'question', 'Video Production Timeline', 'Hi team, I need clarification on the video production timeline. The editing is taking longer than expected due to additional footage requirements. Can we extend the deadline by 3 days?', '[1]', 'high', '[]', '2023-10-28 14:30:00'),

(2, 3, 4, 'announcement', 'Door-to-Door Campaign Volunteer Meeting', 'Reminder: Volunteer coordination meeting for the door-to-door fire safety campaign is scheduled for tomorrow at 2 PM in the community center. Please bring your assigned neighborhood maps.', '[]', 'normal', '[]', '2023-11-10 16:45:00'),

(3, 2, 3, 'update', 'Focus Group Sessions Scheduled', 'I have successfully scheduled 3 focus group sessions with senior citizens for next week. Each session will have 8-10 participants. Looking forward to gathering valuable insights for our cybersecurity education materials.', '[1]', 'normal', '[]', '2023-10-25 11:20:00'),

(5, 6, 3, 'urgent', 'Data Analysis Delay', 'There is a delay in the statistical analysis due to incomplete data from Q3. I need the missing campaign metrics by Friday to stay on schedule. Please prioritize this request.', '[1]', 'urgent', '[]', '2023-12-20 13:00:00');

-- Insert sample shared resources
INSERT INTO shared_resources (name, description, resource_type, category, file_path, file_size, file_format, visibility, owner_id, download_count, views_count, version, tags, approval_status, approved_by) VALUES

('Campaign Planning Template', 'Comprehensive template for planning public safety campaigns including timeline, budget, and resource allocation sections', 'template', 'Planning', '/resources/templates/campaign-planning-template.docx', 245760, 'DOCX', 'team', 1, 23, 87, '2.1', '["template", "planning", "campaign", "budget"]', 'approved', 1),

('Social Media Content Guidelines', 'Guidelines for creating effective social media content for safety campaigns including tone, messaging, and visual standards', 'guide', 'Content Creation', '/resources/guides/social-media-guidelines.pdf', 1572864, 'PDF', 'team', 2, 15, 64, '1.3', '["guidelines", "social_media", "content", "standards"]', 'approved', 1),

('Event Registration Checklist', 'Step-by-step checklist for managing event registrations from setup to post-event follow-up', 'checklist', 'Event Management', '/resources/checklists/event-registration-checklist.pdf', 524288, 'PDF', 'team', 4, 31, 95, '1.0', '["checklist", "events", "registration", "management"]', 'approved', 1),

('Community Safety Survey Dataset', 'Anonymized dataset from 2023 community safety surveys including demographics, awareness levels, and behavior patterns', 'dataset', 'Research', '/resources/datasets/community-safety-survey-2023.xlsx', 3145728, 'XLSX', 'team', 3, 8, 34, '1.0', '["dataset", "survey", "community", "safety", "2023"]', 'approved', 3),

('Emergency Contact Database Template', 'Structured template for maintaining emergency contact databases with standardized fields and validation rules', 'template', 'Emergency Response', '/resources/templates/emergency-contacts-template.xlsx', 786432, 'XLSX', 'team', 1, 12, 45, '1.2', '["template", "emergency", "contacts", "database"]', 'approved', 1),

('Fire Safety Inspection Tool', 'Digital tool for conducting home fire safety inspections with automated scoring and recommendation generation', 'tool', 'Fire Safety', '/resources/tools/fire-safety-inspector.html', 2097152, 'HTML', 'public', 2, 67, 234, '3.0', '["tool", "fire_safety", "inspection", "home"]', 'approved', 2),

('Grant Writing Reference Guide', 'Comprehensive reference guide for writing successful grant applications for safety campaign funding', 'reference', 'Funding', '/resources/references/grant-writing-guide.pdf', 4194304, 'PDF', 'team', 1, 19, 78, '1.1', '["reference", "grants", "funding", "writing"]', 'review', NULL);

-- Update project statistics
UPDATE projects SET 
    team_size = (SELECT COUNT(*) FROM project_tasks WHERE project_tasks.project_id = projects.id AND assigned_to IS NOT NULL),
    completed_tasks = (SELECT COUNT(*) FROM project_tasks WHERE project_tasks.project_id = projects.id AND status = 'completed'),
    total_tasks = (SELECT COUNT(*) FROM project_tasks WHERE project_tasks.project_id = projects.id)
WHERE id IN (1, 2, 3, 4, 5);

-- Update team member project counts
UPDATE team_members SET 
    current_project_count = (
        SELECT COUNT(DISTINCT project_id) 
        FROM project_tasks 
        WHERE project_tasks.assigned_to = team_members.id 
        AND status IN ('todo', 'in_progress', 'review', 'blocked')
    );

-- Update message thread information
UPDATE team_messages SET thread_id = id WHERE parent_message_id IS NULL;
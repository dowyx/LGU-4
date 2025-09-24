-- ============================================================
-- Import All Modules - Complete Database Setup
-- ============================================================
-- This file imports all modules in the correct order
-- Execute this single file to set up the complete system
-- ============================================================

-- Create and use database
CREATE DATABASE IF NOT EXISTS `public_safety_campaigns` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `public_safety_campaigns`;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- IMPORT ORDER (Execute these files in XAMPP phpMyAdmin):
-- ============================================================
-- 1. 00_master_setup.sql (this file)
-- 2. 01_users_login.sql
-- 3. 02_campaign_planning.sql  
-- 4. 03_content_repository.sql
-- 5. 04_audience_segmentation.sql
-- 6. 05_event_management.sql
-- 7. 06_feedback_surveys.sql
-- 8. 07_impact_monitoring.sql
-- 9. 08_collaboration.sql

-- ============================================================
-- XAMPP IMPORT INSTRUCTIONS:
-- ============================================================
-- 1. Start XAMPP (Apache + MySQL)
-- 2. Go to http://localhost/phpmyadmin/
-- 3. Click "Import" tab
-- 4. Select and import each SQL file in the order listed above
-- 5. After importing all files, verify tables were created
-- 6. Test login at: http://localhost/fck/login.php
--    Email: demo@safetycampaign.org
--    Password: password

-- ============================================================
-- MODULE SUMMARY:
-- ============================================================

/*
01_users_login.sql:
- users (authentication, profiles)
- notifications (user alerts)

02_campaign_planning.sql:  
- campaigns (campaign management)
- campaign_metrics (performance tracking)

03_content_repository.sql:
- content_items (media files, documents)
- content_versions (version control)
- content_usage (usage analytics)

04_audience_segmentation.sql:
- audience_segments (target groups)
- contact_lists (contact organization) 
- contacts (individual contacts)
- audience_segment_members (many-to-many)
- contact_interactions (engagement tracking)

05_event_management.sql:
- venues (event locations)
- events (event management)
- event_staff (staff assignments)
- event_registrations (attendee management)
- event_feedback (post-event surveys)

06_feedback_surveys.sql:
- surveys (survey management)
- survey_questions (question bank)
- survey_responses (response data)
- sms_alerts (emergency notifications)

07_impact_monitoring.sql:
- kpi_metrics (key performance indicators)
- impact_reports (comprehensive reporting)
- activity_log (system audit trail)

08_collaboration.sql:
- team_members (team management)
- projects (collaborative projects)
- project_tasks (task management)
- team_messages (team communication)
- shared_resources (resource sharing)
*/

-- Verification queries
SELECT 'Setup Complete! Next steps:' as Status;
SELECT '1. Import all 8 module SQL files in order' as Step_1;
SELECT '2. Test login at http://localhost/fck/' as Step_2;  
SELECT '3. Use demo@safetycampaign.org / password' as Step_3;
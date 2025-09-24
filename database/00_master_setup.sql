-- ============================================================
-- Public Safety Campaign Management System - Master Setup
-- ============================================================
-- Complete database setup with all modules and relationships
-- Execute this file to set up the entire system at once
-- ============================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `public_safety_campaigns` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `public_safety_campaigns`;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- EXECUTION INSTRUCTIONS:
-- ============================================================
-- 1. Import this file first to create the database
-- 2. Then import the individual module files in order:
--    - 01_users_login.sql
--    - 02_campaign_planning.sql
--    - 03_content_repository.sql
--    - 04_audience_segmentation.sql
--    - 05_event_management.sql
--    - 06_feedback_surveys.sql  
--    - 07_impact_monitoring.sql
--    - 08_collaboration.sql
-- 
-- OR use the import_all.sql file to import everything at once
-- ============================================================

-- System Information
-- Database created: Public Safety Campaign Management System Database - Created on 2023-10-24

-- Default admin user (password: admin123)
-- This will be created in 01_users_login.sql

SELECT 'Database public_safety_campaigns created successfully!' as Status;
SELECT 'Ready to import module-specific SQL files' as Next_Step;
SELECT 'Default login: demo@safetycampaign.org / password' as Demo_Account;
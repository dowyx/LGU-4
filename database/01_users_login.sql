-- ===================================
-- Users and Login System Tables
-- ===================================

-- Drop tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS users;

-- Users table for authentication and user management
CREATE TABLE users (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_department (department)
);

-- Notifications table for user alerts
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created_at (created_at)
);

-- Insert sample users
INSERT INTO users (first_name, last_name, email, password, role, department, avatar) VALUES
('John', 'Doe', 'demo@safetycampaign.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Campaign Manager', 'Public Safety', 'JD'),
('Sarah', 'Johnson', 'sarah@safetycampaign.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Content Creator', 'Marketing', 'SJ'),
('Michael', 'Brown', 'michael@safetycampaign.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Data Analyst', 'Analytics', 'MB'),
('Emily', 'Davis', 'emily@safetycampaign.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Community Coordinator', 'Outreach', 'ED'),
('Admin', 'User', 'admin@safetycampaign.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'IT', 'AU');

-- Insert sample notifications
INSERT INTO notifications (user_id, title, message, type) VALUES
(1, 'Welcome!', 'Welcome to the Public Safety Campaign Management System', 'success'),
(1, 'Campaign Update', 'Road Safety campaign has reached 75% completion', 'info'),
(1, 'Event Reminder', 'Road Safety Workshop starts in 2 hours', 'warning'),
(2, 'Content Review', 'Fire Safety Video needs your review', 'info'),
(3, 'Data Analysis', 'Monthly analytics report is ready', 'success');

-- Note: Default password for all demo accounts is 'password'
-- In production, users should change their passwords on first login
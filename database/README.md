# Public Safety Campaign Management System - Database Setup

This directory contains MySQL database files for the complete Public Safety Campaign Management System.

## Quick Start

1. **Start XAMPP**
   - Start Apache and MySQL services

2. **Access phpMyAdmin**
   - Go to `http://localhost/phpmyadmin/`

3. **Import Database Files**
   - Import the files in this exact order:

```
00_master_setup.sql      (Creates database)
01_users_login.sql       (Users & Authentication)
02_campaign_planning.sql (Campaign Management)
03_content_repository.sql (Content Management)
04_audience_segmentation.sql (Audience & Contacts)
05_event_management.sql  (Event Management)
06_feedback_surveys.sql  (Surveys & SMS Alerts)
07_impact_monitoring.sql (Analytics & Reporting)
08_collaboration.sql     (Team Collaboration)
```

4. **Test the System**
   - Go to `http://localhost/fck/`
   - Login with: `demo@safetycampaign.org` / `password`

## Module Overview

### 01 - Users & Login System
- **users**: User authentication and profiles
- **notifications**: System notifications and alerts
- **Features**: Secure login, user management, role-based access

### 02 - Campaign Planning
- **campaigns**: Campaign lifecycle management
- **campaign_metrics**: Performance tracking and analytics
- **Features**: Campaign creation, scheduling, progress tracking

### 03 - Content Repository
- **content_items**: Media files, documents, and materials
- **content_versions**: Version control for content
- **content_usage**: Usage analytics and tracking
- **Features**: File management, version control, usage statistics

### 04 - Audience Segmentation
- **audience_segments**: Target audience groups
- **contacts**: Individual contact management
- **contact_lists**: Contact organization and grouping
- **contact_interactions**: Engagement tracking
- **Features**: Audience targeting, contact management, interaction tracking

### 05 - Event Management
- **venues**: Event location management
- **events**: Event planning and scheduling
- **event_registrations**: Attendee management
- **event_staff**: Staff assignment and coordination
- **event_feedback**: Post-event surveys and feedback
- **Features**: Complete event lifecycle management

### 06 - Feedback & Surveys
- **surveys**: Survey creation and management
- **survey_questions**: Question bank and templates
- **survey_responses**: Response collection and analysis
- **sms_alerts**: Emergency notification system
- **Features**: Comprehensive feedback collection, SMS alerting

### 07 - Impact Monitoring
- **kpi_metrics**: Key performance indicators
- **impact_reports**: Comprehensive reporting system
- **activity_log**: System audit trail
- **Features**: Performance analytics, impact measurement, reporting

### 08 - Collaboration
- **team_members**: Team management and roles
- **projects**: Collaborative project management
- **project_tasks**: Task assignment and tracking
- **team_messages**: Internal communication
- **shared_resources**: Resource sharing and templates
- **Features**: Team collaboration, project management, resource sharing

## Sample Data Included

Each module includes comprehensive sample data:
- **5 demo users** with different roles
- **6 sample campaigns** across various safety topics
- **12+ content items** including posters, videos, documents
- **8 contacts** with interaction history
- **6 events** with registrations and feedback
- **4 surveys** with sample responses
- **Comprehensive metrics** and performance data
- **5 collaborative projects** with tasks and messages

## Default Login Credentials

- **Email**: `demo@safetycampaign.org`
- **Password**: `password`
- **Role**: Campaign Manager

Additional demo users:
- `sarah@safetycampaign.org` (Content Creator)
- `michael@safetycampaign.org` (Data Analyst)  
- `emily@safetycampaign.org` (Community Coordinator)
- `admin@safetycampaign.org` (Administrator)

All demo accounts use the password: `password`

## Troubleshooting

### Common Issues

1. **Import Errors**
   - Ensure MySQL is running in XAMPP
   - Import files in the exact order specified
   - Check for foreign key constraint errors

2. **Login Issues**
   - Verify the users table was created
   - Check that sample data was imported
   - Ensure session support is enabled in PHP

3. **Database Connection**
   - Verify MySQL credentials in `backend/config/database.php`
   - Default: localhost, no username/password for XAMPP

### Database Settings

- **Character Set**: `utf8mb4`
- **Collation**: `utf8mb4_unicode_ci`
- **Engine**: `InnoDB` (for foreign key support)

## File Structure

```
database/
├── 00_master_setup.sql         # Database creation
├── 01_users_login.sql          # Authentication system
├── 02_campaign_planning.sql    # Campaign management
├── 03_content_repository.sql   # Content management
├── 04_audience_segmentation.sql # Audience & contacts
├── 05_event_management.sql     # Event management
├── 06_feedback_surveys.sql     # Surveys & feedback
├── 07_impact_monitoring.sql    # Analytics & reporting
├── 08_collaboration.sql        # Team collaboration
├── import_all.sql              # Master import script
└── README.md                   # This file
```

## Security Notes

- All passwords are hashed using PHP's `password_hash()`
- Demo passwords are for development only
- Change all default passwords in production
- Review user permissions and roles before deployment

## Next Steps

After importing the database:

1. **Test Core Functionality**
   - Login/logout
   - Navigate between modules
   - Create/edit campaigns
   - Upload content

2. **Customize Data**
   - Update sample campaigns with your data
   - Add your organization's contacts
   - Configure notification settings

3. **Configure System**
   - Update email settings for notifications
   - Configure SMS provider for alerts
   - Set up file upload directories

4. **Production Deployment**
   - Change all default passwords
   - Review security settings
   - Set up regular backups
   - Configure SSL certificates

For support or questions, refer to the main system documentation.
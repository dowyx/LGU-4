# Public Safety Campaign Management System

> **🆕 NEW: PHP Version Available!**  
> The system now includes PHP files (`login.php`, `index.php`) with full database integration.  
> Use these for a complete web application with user authentication and dynamic data.

## XAMPP Setup Guide

### Prerequisites
- XAMPP installed and running
- PHP 7.4+ 
- MySQL 5.7+

### Installation Steps

1. **Start XAMPP Services**
   - Start Apache and MySQL from XAMPP Control Panel

2. **Setup Database**
   - Navigate to: `http://localhost/your_project_folder/backend/setup/install.php`
   - This will create the database and all necessary tables
   - Sample data will be inserted automatically

3. **Demo Login Credentials**
   ```
   Campaign Manager: john.doe@safetycampaign.org / demo123
   Content Creator:  jane.smith@safetycampaign.org / demo123  
   Data Analyst:     mike.johnson@safetycampaign.org / demo123
   Administrator:    admin@safetycampaign.org / admin123
   ```

### File Structure
```
project/
├── frontend files (login.html, index.html, etc.)
├── backend/
│   ├── config/
│   │   ├── database.php     # Database connection
│   │   └── cors.php         # CORS headers
│   ├── api/
│   │   ├── auth.php         # Authentication endpoints
│   │   └── dashboard.php    # Dashboard data endpoints
│   ├── utils/
│   │   └── helpers.php      # Utility functions
│   └── setup/
│       └── install.php      # Database setup script
```

### API Endpoints

#### Authentication
- `POST /backend/api/auth.php?action=login` - User login
- `GET /backend/api/auth.php?action=profile` - Get user profile
- `POST /backend/api/auth.php?action=logout` - User logout

#### Dashboard
- `GET /backend/api/dashboard.php?action=stats` - Get statistics
- `GET /backend/api/dashboard.php?action=campaigns` - Get campaigns
- `GET /backend/api/dashboard.php?action=events` - Get events
- `GET /backend/api/dashboard.php?action=performance` - Get performance data

### Database Tables
- `users` - User accounts and profiles
- `campaigns` - Campaign information
- `events` - Event management
- `content` - Content repository
- `notifications` - User notifications
- `audience_segments` - Audience targeting
- `survey_responses` - Feedback data

### Features
✅ **Authentication System**
- Secure login/logout
- JWT-style token authentication
- User profile management
- Password hashing

✅ **Dashboard Integration**
- Real-time statistics
- Interactive charts
- Campaign management
- Event tracking

✅ **Fallback Support**
- Works with or without database
- Graceful degradation to demo data
- Error handling and recovery

### Configuration
Edit `backend/config/database.php` to modify database settings:
```php
private $host = "localhost";
private $db_name = "safety_campaign_db";
private $username = "root";
private $password = "";
```

### Troubleshooting
1. **Database Connection Issues**
   - Ensure MySQL is running in XAMPP
   - Check database credentials in `config/database.php`

2. **CORS Errors**
   - Ensure `cors.php` is included in API files
   - Check browser developer console for errors

3. **API Not Working**
   - Application will fallback to demo data
   - Check PHP error logs in XAMPP

### Next Steps
- Customize database schema as needed
- Add more API endpoints for additional features
- Implement file upload functionality
- Add email notification system
- Enhance security features
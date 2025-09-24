# Public Safety Campaign Management System - Login Guide

## System Status

✅ Database initialized successfully
✅ Demo users created
✅ Login system verified
✅ Session management working

## Default Login Credentials

Use any of these accounts to test the system:

| Email | Password | Role |
|-------|----------|------|
| demo@safetycampaign.org | password | Campaign Manager |
| sarah@safetycampaign.org | password | Content Creator |
| michael@safetycampaign.org | password | Data Analyst |
| emily@safetycampaign.org | password | Community Coordinator |
| admin@safetycampaign.org | password | Administrator |

## How to Access the System

1. **Open your web browser**
2. **Go to**: http://localhost/fck/login.php
3. **Login options**:
   - Click "Try Demo Account" button (automatically logs in as demo user)
   - Or manually enter credentials:
     - Email: demo@safetycampaign.org
     - Password: password
   - Click "Sign In"

## Troubleshooting

### If login doesn't work:

1. **Check database connection**:
   - Ensure XAMPP is running (Apache and MySQL)
   - Verify database credentials in `backend/config/database.php`

2. **Verify database tables**:
   - Run `init_database.php` script if needed
   - Check that `users` table exists with demo data

3. **Check PHP errors**:
   - Look at Apache error logs
   - Enable error reporting in PHP if needed

### Common Issues:

1. **"Invalid email/username or password"**:
   - Make sure you're using the correct credentials
   - Run `verify_login.php` to test credentials

2. **Redirect issues**:
   - Ensure `home.php` file exists
   - Check file permissions

3. **Session problems**:
   - Verify PHP sessions are working
   - Check `session.save_path` in php.ini

## File Structure

```
fck/
├── login.php          # Login page (modified to work correctly)
├── home.php           # Main dashboard (after successful login)
├── backend/
│   ├── config/
│   │   └── database.php  # Database configuration
│   └── utils/
│       └── helpers.php   # Helper functions
├── database/
│   └── 01_users_login.sql # Database schema
└── README_LOGIN.md        # This file
```

## Testing Scripts

- `init_database.php` - Sets up database with demo data
- `verify_login.php` - Tests login credentials
- `test_session.php` - Tests session management

## Security Notes

- All demo passwords are set to "password" for testing
- In production, users should change their passwords immediately
- Passwords are properly hashed using PHP's password_hash()

## Support

If you continue to have issues:
1. Restart XAMPP services
2. Re-run the `init_database.php` script
3. Check PHP and Apache error logs
4. Verify all files are in the correct locations
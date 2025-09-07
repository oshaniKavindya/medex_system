# MedEx System - Medical Excuse Application System

A PHP-based web application for managing medical excuse applications in educational institutions.

## Setup Instructions

### 1. Database Configuration
1. Copy `config/database.template.php` to `config/database.php`
2. Update the database credentials in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'medex_system');
   ```

### 2. Directory Permissions
Ensure the uploads directory has write permissions:
```bash
chmod -R 755 assets/uploads/
```

### 3. Database Setup
The application will automatically create the database and tables on first run.

## File Structure
```
medex_system/
├── admin/              # Admin panel files
├── assets/
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── uploads/       # File uploads (ignored by git)
├── auth/              # Authentication files
├── config/            # Configuration files
├── includes/          # Shared includes
├── student/           # Student portal files
├── lecturer/          # Lecturer portal files
├── hod/              # Head of Department files
└── sql/              # Database schema files
```

## Features
- Student medical excuse application submission
- Multi-level approval workflow (Lecturer → HOD)
- File upload management
- User role management
- Application tracking and status updates

## Security
- All uploaded files are excluded from version control
- Database configuration is template-based
- Input sanitization and validation
- Role-based access control

## Development
When pushing to GitHub, sensitive files and uploads are automatically excluded via `.gitignore`.

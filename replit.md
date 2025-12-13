# Angling Club Manager

## Overview
A PHP-based web application for managing angling clubs. Includes user accounts, club management, and admin functionality.

## Project Structure
- `index.php` - Main entry point
- `app/bootstrap.php` - Application bootstrap (session, database connection, helper functions)
- `config.local.php` - Database configuration (uses environment variables or localhost defaults)
- `dashboard.php` - User dashboard showing clubs and club creation link
- `create-club.php` - Club creation form
- `schema.sql` - Database schema
- `db_test.php` - Database connection test script

## Technical Stack
- **Language**: PHP 8.4
- **Database**: MySQL/MariaDB (primary), with PostgreSQL support for Replit
- **Server**: PHP built-in development server

## Running the Application
The app runs via PHP's built-in server on port 5000:
```bash
php -S 0.0.0.0:5000 -t .
```

## Database
The application supports both MySQL and PostgreSQL:
- **Local development**: MySQL/MariaDB on localhost (via Laragon)
- **Replit**: PostgreSQL (via built-in database)

Tables:
- `users` - User accounts
- `clubs` - Club information
- `club_admins` - Club administrator relationships (tracks admin_role: owner/admin)

## Features Implemented
- User authentication with helpers: `current_user_id()`, `current_user()`, `require_login()`
- Dashboard page showing user's clubs and "Create Club" link
- Create Club form with fields: name, contact_email, location_text, about_text
- Automatic club admin relationship creation with 'owner' role

## Configuration
For local Laragon development, update `config.local.php`:
```php
$host = 'localhost';
$dbname = 'angling_club_manager';
$user = 'root';
$pass = '';
```

For Replit (PostgreSQL), use environment variables: PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD

## Important Notes
- config.local.php is .gitignored (contains sensitive credentials)
- Code supports both MySQL and PostgreSQL drivers
- Trial logic, billing, and subscriptions are NOT implemented (future scope)

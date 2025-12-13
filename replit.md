# Angling Club Manager

## Overview
A PHP-based web application for managing angling clubs. Includes user accounts, club management, and admin functionality.

## Project Structure
- `index.php` - Main entry point
- `app/bootstrap.php` - Application bootstrap (session, database connection)
- `config.local.php` - Database configuration (uses environment variables)
- `schema.sql` - PostgreSQL database schema
- `db_test.php` - Database connection test script

## Technical Stack
- **Language**: PHP 8.4
- **Database**: PostgreSQL (via Replit's built-in database)
- **Server**: PHP built-in development server

## Running the Application
The app runs via PHP's built-in server on port 5000:
```bash
php -S 0.0.0.0:5000 -t .
```

## Database
Uses PostgreSQL with the following tables:
- `users` - Site user accounts
- `clubs` - Club information and billing
- `club_admins` - Club administrator relationships

Database connection uses these environment variables:
- `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`

## Recent Changes
- Migrated from MySQL to PostgreSQL for Replit compatibility
- Created config.local.php using Replit's database environment variables
- Updated schema.sql for PostgreSQL syntax

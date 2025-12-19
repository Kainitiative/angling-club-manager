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
- `users` - User accounts (everyone who signs up)
- `clubs` - Club information
- `club_admins` - Club administrator relationships (admin_role: owner/admin)
- `club_members` - Club membership (users who join clubs, membership_status: pending/active/suspended/expired)

User Roles:
- **User** - Anyone who signs up on the site
- **Club Member** - A user who joins a club (stored in club_members)
- **Club Admin** - A user with admin privileges for a club (stored in club_admins with admin_role='admin')
- **Club Owner** - The user who created the club (stored in club_admins with admin_role='owner')

## Features Implemented
- User authentication with helpers: `current_user_id()`, `current_user()`, `require_login()`
- Dashboard page showing user's clubs and "Create Club" link
- Create Club form with fields: name, contact_email, location_text, about_text
- Automatic club admin relationship creation with 'owner' role
- Membership request system: users can request to join clubs
- Admin member management: view pending requests, approve/reject, suspend/remove members
- Membership status display on club pages (pending, active, suspended, expired)
- Club profile customization with branding, colors, and custom content
- Image upload with GD library resizing (logos: 200px max, gallery: 1200px max)
- Financial management with 12 categories and reporting
- Club news system with draft/publish workflow, pinning, and officer access
- Internal notifications for membership approvals, COTM selection, and more
- Club messaging system with direct messages and announcements

## Member Engagement Features
- **Catch Logging**: Members can log their catches with species, weight, length, location, photos, and notes
- **Personal Bests Tracking**: Automatic detection and flagging when a catch exceeds previous personal best
- **Club Records**: Automatic club record tracking per species, displayed on catch log page
- **Catch of the Month**: Highlights the heaviest catch from the last 30 days on the club page
- **Competition Seasons/Leagues**: Group competitions into seasons with cumulative standings
- **Season Leaderboards**: Rankings with points, weights, wins, and podiums
- **Member Stats Dashboard**: Personal fishing statistics displayed on user dashboard

## Image Uploads
Images are processed using PHP's GD library:
- **Logo uploads**: Resized to max 200x200px, saved as PNG (preserves transparency) or JPEG
- **Gallery uploads**: Resized to max 1200px wide, saved as optimized JPEG (85% quality)
- **Catch photos**: Resized to max 800px wide, saved as optimized JPEG (80% quality)
- **Validation**: File type (JPEG, PNG, GIF, WebP), max size (5MB logos, 10MB gallery/catches)
- **Storage**: `uploads/logos/`, `uploads/gallery/`, and `uploads/catches/` directories
- **Helper file**: `app/image_upload.php` contains `processLogoUpload()`, `processGalleryUpload()`, and `processCatchUpload()` functions

## Key Pages
- `/public/catches.php?slug={club_slug}` - Catch log page for a club
- `/public/leaderboard.php?season_id={id}` - Season leaderboard
- `/public/admin/seasons.php?club_id={id}` - Manage competition seasons (admin)
- `/public/admin/news.php?club_id={id}` - Manage club news (admin/chairperson/secretary)
- `/public/notifications.php` - View and manage notifications
- `/public/messages.php` - Club messaging inbox and compose

## Configuration
For local Laragon development, update `config.local.php`:
```php
$host = 'localhost';
$dbname = 'angling_club_manager';
$user = 'root';
$pass = '';
```

For Replit (PostgreSQL), use environment variables: PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD

## Database Migrations
When adding or changing database columns:
1. Create a new file in `/db/updates/` named `NNN_description.sql` (e.g., `001_add_user_bio.sql`)
2. Use only ALTER statements - no DROP TABLE, no engine changes
3. Do NOT use `ADD COLUMN IF NOT EXISTS` syntax
4. Migrations auto-run on page refresh via `app/migrate.php`
5. Applied migrations are tracked in the `schema_migrations` table

Example migration file:
```sql
ALTER TABLE users ADD COLUMN bio TEXT NULL;
ALTER TABLE users ADD COLUMN website VARCHAR(255) NULL;
```

## Fish Species
The `fish_species` table contains common Irish fish species categorized as:
- **Coarse**: Pike, Perch, Bream, Roach, Rudd, Tench, Carp, Eel
- **Game**: Brown Trout, Rainbow Trout, Salmon, Sea Trout
- **Sea**: Bass, Pollack, Cod, Mackerel, Wrasse, Ray, Flounder
- **Other**: For unlisted species

## Important Notes
- config.local.php is .gitignored (contains sensitive credentials)
- Code supports both MySQL and PostgreSQL drivers
- Trial logic, billing, and subscriptions are NOT implemented (future scope)

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

## Navigation System
The application uses a unified shell-based navigation system with four distinct layouts:

### Layout Files (`app/layout/`)
- `header.php` - Shared HTML head, CSS variables, common styles
- `footer.php` - Shared scripts, mobile sidebar toggle
- `member_shell.php` - For logged-in user pages (dashboard, messages, tasks, profile)
- `club_admin_shell.php` - For club administration pages with organized sidebar
- `super_admin_shell.php` - For platform-wide admin pages
- `public_shell.php` - For public-facing pages (homepage, browse clubs)

### Navigation Config (`app/nav_config.php`)
Central place for all menu definitions. Functions:
- `get_member_nav()` - Member sidebar navigation
- `get_club_admin_nav()` - Club admin sidebar (grouped by: Overview, People, Engagement, Governance, Finance, Settings)
- `get_super_admin_nav()` - Super admin sidebar
- `get_public_nav()` - Public header navigation
- `render_breadcrumbs()` - Breadcrumb rendering

### Usage Example
```php
require_once __DIR__ . '/../app/layout/club_admin_shell.php';

$currentPage = 'members';
club_admin_shell_start($pdo, $club, ['title' => 'Members', 'page' => $currentPage]);
// ... page content ...
club_admin_shell_end();
```

## Angling Affiliations (National Governing Bodies)
The platform tracks and integrates with the **Angling Council of Ireland (ACI)** and its member federations:

### ACI Federations & Fishing Styles:
- **Irish Federation of Sea Anglers (IFSA)**: Focuses on **Sea Angling** (Boat and Shore).
- **Irish Federation of Pike Angling Clubs (IFPAC)**: Dedicated to **Pike/Predator Angling**.
- **National Coarse Fishing Federation of Ireland (NCFFI)**: Governing body for **Coarse & Predator Angling** (traditional match fishing).
- **Salmon & Sea Trout Recreational Anglers of Ireland (SSTRAI)**: Focuses on **Game Fishing** (Salmon and Sea Trout).
- **Trout Anglers Federation of Ireland (TAFI)**: Governing body for **Trout Angling (Fly Fishing)**.

### Integration Vision:
- **Affiliation Tracking**: Clubs can select their affiliated federation during signup.
- **Unified Standards**: Use ACI-approved coaching and safety standards for junior development.
- **National Competitions**: Support for federation-level competition logging and results.

## Account Types
The platform supports multiple entity types, each with a tailored profile:
- **Angling Club**: Standard membership-based club.
- **Syndicate**: Private, invite-only fishing groups.
- **Commercial Fishery**: Business-run fishing venues.
- **Angling Guide**: Professional fishing instructors/coaches.
- **Charter Boat**: Skippers providing sea angling trips.

## Branding & Advertising
Entity profiles serve as microsites/advertisements:
- Customizable colors and logo
- Social media links (Facebook, Instagram, etc.)
- Taglines and "About" sections
- Display settings for gallery, catches, and news

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
- Club policies and constitution pages (text-based, editable by admins)
- Super admin panel for platform-wide management

## Subscription & Billing (Planned)
- **Subscription Plans**: Free, Club Admin (€10/mo), Club Pro (€15/mo)
- **Trial System**: 3-month free trial for new club admins
- **Status Tracking**: trial, active, cancelled, expired, suspended
- **Super Admin Features**: View all clubs, users, subscriptions, extend trials, change status

## Member Engagement Features
- **Catch Logging**: Members can log their catches with species, weight, length, location, photos, and notes
- **Personal Bests Tracking**: Automatic detection and flagging when a catch exceeds previous personal best
- **Club Records**: Automatic club record tracking per species, displayed on catch log page
- **Catch of the Month**: Highlights the heaviest catch from the last 30 days on the club page
- **Competition Seasons/Leagues**: Group competitions into seasons with cumulative standings
- **Season Leaderboards**: Rankings with points, weights, wins, and podiums
- **Member Stats Dashboard**: Personal fishing statistics displayed on user dashboard
- **Specimen Wishlist & Targeting**:
    - Species Selection: Users can pick a target species to see official ISFC specimen weights and record info.
    - Wishlist: Create a personal list of specimen awards to target.
    - Seasonality Guides: App presents the best dates and locations for the highest chance of a specimen catch.

## Future Exploration & Research (from Clubmate & ClubNest)
- **Junior & Parent Management**: 
    - Parent-managed accounts (one parent, multiple children)
    - Automated consent forms and medical notes tracking for coaching
- **Match Day Operations**:
    - Digital peg draws (randomizer for match positions)
    - Live weigh-in capture and instant leaderboard updates
- **Commercial & Bookings**:
    - Day ticket sales with automated capacity limits
    - Swim (fishing spot) or boat bookings
- **Field Tools**:
    - Bailiff App for bankside membership verification (QR/Photo)
    - Bankside card payment processing
- **Hardware & Automation**:
    - Physical ID card printing service
    - Access control integration (gate codes/smart locks)
    - Accounting sync (e.g., Xero)
- **Education**:
    - Structured coaching courses with quizzes and certificates
    - Coaching progress logs for junior development
- **Marketing**:
    - SEO-optimized event snippets and "What's On" global calendar
    - Club merchandise shop integration
- **National Protection System (IFI Integration)**:
    - Incident Reporting: Public/Member form for pollution, poaching, damage, or illegal netting.
    - Regional Routing: Super Admin can input Officer names, counties, and direct emails.
    - Local Alerts: Reports are automatically dispatched to the specific local officers on the ground via email with GPS pins and photos.
    - Officer Availability: Future capability for officers to toggle "On Duty / Off Duty" status via a mobile dashboard.
    - IFI News: Integration of official IFI RSS feeds and regulatory rules.

## RSS Feeds (National Angling Data)
- **Fishing in Ireland (Main Feed)**: `https://fishinginireland.info/feed`
- **Irish Specimen Fish Committee (ISFC)**: `https://specimenfish.ie/feed`
- **Angling Council of Ireland (News)**: `https://www.anglingcouncil.ie/feed` (if available, check for updates)

### Personalization Strategy (Planned)
- **User Targeting**: When a logged-in user has specified favorite fishing styles (e.g., Pike, Salmon, Sea) in their profile, the RSS feed on their dashboard or landing page will be filtered/prioritized.
- **Filtering Logic**: 
    - The `fetch_rss_feed` helper will be updated to accept a `$keyword` or `$category` parameter.
    - It will match user preferences against the `<category>` tags or keywords in the RSS item titles/descriptions.
    - High-relevance items will be boosted to the top of the list to ensure the user sees their preferred content first.
- **Technical Path**:
    1. Update `users` table to store `favorite_styles` (JSON or separate table).
    2. Modify `fetch_rss_feed` in `app/rss_helper.php` to handle keyword-based scoring.
    3. Update `index.php` and `dashboard.php` to pass user preferences into the fetcher.

## Image Uploads
Images are processed using PHP's GD library:
- **Logo uploads**: Resized to max 200x200px, saved as PNG (preserves transparency) or JPEG
- **Gallery uploads**: Resized to max 1200px wide, saved as optimized JPEG (85% quality)
- **Catch photos**: Resized to max 800px wide, saved as optimized JPEG (80% quality)
- **Validation**: File type (JPEG, PNG, GIF, WebP), max size (5MB logos, 10MB gallery/catches)
- **Storage**: `uploads/logos/`, `uploads/gallery/`, and `uploads/catches/` directories
- **Helper file**: `app/image_upload.php` contains `processLogoUpload()`, `processGalleryUpload()`, and `processCatchUpload()` functions

## Club Finances
- **Transaction Tracking**: Log income and expenses by category (membership, sponsorship, equipment, etc.)
- **Account Balances**: Track bank accounts, cash floats, PayPal - manual balance entry, no bank connection
- **Summary Reports**: Monthly/yearly breakdown by category
- **Total Overview**: Shows total across all active accounts
- **Access Control**: Committee members can view, chairperson/treasurer can edit

## Meeting Management
- **Meetings**: Track committee, AGM, EGM, and general meetings with date/time/location
- **Minutes**: Record meeting attendees, apologies, and full minutes (visible to all members)
- **Decisions**: Track motions with proposer, seconder, and vote status (visible to committee only)
- **Tasks**: Assign action items to members with priority, due dates, and notifications
- **Notes**: Internal committee notes for private reference
- **Access Control**: `can_manage_meetings()` for admin/chairperson/secretary, `can_view_decisions()` for committee members

## Key Pages
- `/public/catches.php?slug={club_slug}` - Catch log page for a club
- `/public/leaderboard.php?season_id={id}` - Season leaderboard
- `/public/admin/seasons.php?club_id={id}` - Manage competition seasons (admin)
- `/public/admin/news.php?club_id={id}` - Manage club news (admin/chairperson/secretary)
- `/public/admin/meetings.php?club_id={id}` - Manage meetings (admin/chairperson/secretary)
- `/public/meetings.php?slug={club_slug}` - View meeting minutes (members)
- `/public/tasks.php` - View and update assigned tasks
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

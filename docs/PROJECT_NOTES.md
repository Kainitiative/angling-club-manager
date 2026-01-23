# Angling Ireland - Project Documentation

## Overview
Angling Ireland is a free-forever PHP-based web application designed to streamline administration and enhance community experience for various angling entities in Ireland, including clubs, syndicates, commercial fisheries, guides, and charter boats.

## User Preferences
The user prefers a conversational and helpful interaction style. When making changes, the user expects the agent to ask clarifying questions and confirm understanding before proceeding. Do not make changes to folder Z and file Y.

## System Architecture
The application is built on a PHP foundation, utilizing a unified shell-based navigation system with distinct layouts for different user roles (member, club admin, super admin, public).

### UI/UX Decisions
Mobile-first design with:
- Collapsible sidebar navigation hidden by default on mobile
- Persistent bottom navigation bar (5 key tabs per shell)
- Touch-friendly 44px minimum tap targets
- iOS safe area support for notched devices
- Responsive breakpoints: mobile (<768px), tablet (768-991px), desktop (992px+)

Shell navigation:
- Member shell: Home, Clubs, Comps, Messages, Profile
- Club admin shell: Members, Catches, Comps, Finance, Settings  
- Super admin shell: Dashboard, Clubs, Users, Subs, Exit
- Public shell: For non-logged-in visitors

### Club Roles & Permissions System
Implemented in app/permissions.php with granular access control.

Role Types:
- Admin Roles: Owner, Admin (stored in club_admins table)
- Committee Roles: Chairperson, Secretary, Treasurer, PRO, Safety Officer, Child Liaison Officer
- Regular Role: Member

Detailed Permission Matrix:
| Feature | Owner/Admin | Chairperson | Secretary | Treasurer | PRO | Safety Officer | Member |
|---------|-------------|-------------|-----------|-----------|-----|----------------|--------|
| Members | Full | Full | Accept/Reject | View | View | View | View |
| Meetings | Full | Full | Full | View | View | View | - |
| News | Full | Full | Full | View | Full | View | - |
| Profile | Full | Full | Full | View | Full | View | - |
| Policies | Full | Full | Full | View | Full | Full | - |
| Finances | Full | Full | View | Full | View | View | - |
| Competitions | Full | Full | View | View | View | View | - |
| Catches | Full | Full | View | View | View | View | - |
| Documents | Full | Full | Full | View | View | View | - |

### Notification System
Located in app/notifications.php:
- notify_membership_request() - Notifies admins & secretary
- notify_membership_approved() - Notifies member when approved
- notify_membership_rejected() - Notifies member when rejected
- notify_new_news() - Notifies all members of club news
- notify_catch_of_month() - Notifies member of catch of month award
- notify_competition_results() - Notifies members when results posted

## Recent Changes (January 2026)

### Role-Based Permission System (v29)
- Centralized permission system in app/permissions.php
- Granular permissions by role (view, create, edit, delete per feature)
- Dual role support - owners can also hold committee roles
- Member-facing club members list at /public/club_members.php

### Navigation Overhaul
- Converted all pages to use consistent shell layouts
- Fixed mobile bottom navigation across all pages

### Club Browsing & Join System
- Guests can now browse clubs without logging in
- Individual club pages viewable by guests
- Join Club button requires login but browsing does not

## Deployment Notes
- Production Host: LetsHost at anglingireland.ie
- Production Database: MySQL 8.2.29 (anglingireland_clubmanager)
- Development Database: PostgreSQL (Replit)
- Current Version: v29
- Zip file named v25 due to Replit download caching issue
- Excluded from zip: config.local.php, install.php, setup.lock, db/, replit.md

### Production Migration Notes (v29)
Completed January 2026. SQL run to sync production:
- Created tables: club_transactions, club_accounts, club_sponsors
- Added columns to: club_profile_settings, club_membership_fees, club_gallery, club_perks
- Data cleanup for billing_period values

## Future Plans

### High Priority
- Pollution/poaching reporting system
- Email member invitations
- Data export functionality
- Weather/conditions integration for catch logs

### Medium Priority
- RSS feed personalization
- Public interactive map of clubs/fisheries
- Club challenges (inter-club competitions)
- Internal club leaderboards (seasonal)
- Gamification/badges system

### Low Priority
- Fish species encyclopedia
- Personal statistics dashboards (detailed)
- Fishing journal feature
- Angler goals/wishlists

### Custom Permissions System (Future)
Potential enhancement to give club admins full control over member permissions.

Current System: Role-based - assign a role and permissions are fixed.

Proposed System: Admin-controlled with flexibility:
- Admins assign custom permissions to each member individually
- Permission templates based on current roles as starting points
- Custom club templates (e.g., "Event Organizer", "Junior Member")

Hybrid Approach (Recommended):
- Default: Role-based permissions work automatically
- Optional: Admins can customize when they need more control
- Templates: Roles become templates that can be tweaked per member

Database Tables Needed:
- club_permission_templates - Custom templates per club
- club_permission_template_items - Permissions each template grants
- club_member_permissions - Individual member overrides
- club_member_permission_profiles - Links member to assigned template

Permission Resolution Order:
1. Owner/Admin - Full access by default
2. Individual overrides - Custom permissions set for this person
3. Assigned template - Template permissions
4. Default role - Fall back to current role-based system

### SEO Strategy (Future)
Club and member profiles have SEO potential for local search visibility.

Club Profiles SEO:
- Dynamic meta tags per club (title/description with location + club type)
- Schema.org LocalBusiness/SportsOrganization structured data
- Expose public content: waters, species, competitions, news, sponsors
- Friendly URLs: /club/dublin-pike-anglers instead of ?id=5
- Open Graph tags for social sharing

Member Profiles SEO (Privacy-First):
- Keep member data private by default
- Optional public angler profiles (opt-in)
- Aggregated public content: club records, competition results (with consent)
- Public leaderboards with member consent

Technical SEO Tasks:
- Add dynamic title and meta description to club pages
- Implement Open Graph tags for social sharing
- Add schema.org structured data markup
- Create sitemap.xml (clubs, competitions, news)
- Add robots.txt with crawl rules
- Friendly URL rewrites for clubs
- Internal linking improvements

### Google Maps Integration (Future)

**Current State:**
- Competition location picker exists in `public/admin/competitions.php` with full map modal
- Features: click to place marker, draggable marker, reverse geocoding for address auto-fill
- Currently broken: uses placeholder `YOUR_GOOGLE_MAPS_API_KEY`
- Dashboard shows "Map" links for competitions with lat/lng stored
- Database: `competitions` table has `latitude` and `longitude` columns

**Where Maps Could Be Used:**
| Feature | Priority | Use Case |
|---------|----------|----------|
| Competition locations | High | Pick venue on map, show to members |
| Club locations | High | Display club meeting point / waters |
| Catch log locations | Medium | Pin where fish was caught |
| Public club directory map | Medium | Interactive map showing all clubs |
| Fishing waters/venues | Low | Map of club waters |

**Implementation Steps:**
1. Create Google Cloud project
2. Enable Maps JavaScript API + Geocoding API
3. Create restricted API key (HTTP referrer restrictions for anglingireland.ie)
4. Store key as secret in config
5. Update code to load key from config/environment variable
6. Add enable/disable toggle for fallback

**Cost Control Strategies (Recommended: Combine Quota + Toggle):**

1. **API Key Quota Limits (Primary Protection)**
   - Set daily quotas in Google Cloud Console
   - Maps JavaScript API: 900 requests/day (safe limit)
   - Geocoding API: 1,000 requests/day
   - Once limit hit, Google returns errors instead of charging

2. **Application-Level Toggle (Manual Control)**
   - Add config setting: `GOOGLE_MAPS_ENABLED=true/false`
   - Code checks before loading map
   - If disabled, show simple text input for location instead
   - Manual toggle if needed

3. **Budget Alerts (Monitoring)**
   - Set up in Google Cloud Console
   - Email/SMS alerts at 50%, 90%, 100% thresholds
   - Doesn't auto-disable, just warns

4. **Budget Auto-Disable (Advanced)**
   - Google Cloud can disable billing when budget exceeded
   - Requires Cloud Function setup
   - Fully automatic protection

**Config Variables Needed:**
- `GOOGLE_MAPS_API_KEY` - The API key (stored as secret)
- `GOOGLE_MAPS_ENABLED` - Toggle on/off (default: true)

**Fallback Behavior When Disabled:**
- Show text input fields for location (address, town, county)
- Hide "Pick on Map" button
- Existing lat/lng data still works for "View on Map" links

**Free Tier Info:**
- Google provides $200/month credit
- Covers approximately 28,000 map loads/month
- For a growing angling platform, this should be sufficient

### Technical Debt
- Review LSP diagnostics in admin/members.php and create_club.php
- Consider adding database migrations system for production updates
- Review and optimize SQL queries for larger clubs
- Update remaining admin pages to use permission system

## External Dependencies
- Database Systems: MySQL/MariaDB (production) and PostgreSQL (development)
- PHP GD Library: For image manipulation
- Angling Council of Ireland Federations: IFSA, IFPAC, NCFFI, SSTRAI, TAFI
- RSS Feeds: Fishing in Ireland, Irish Specimen Fish Committee, Angling Council of Ireland

## Key Files Reference
- index.php - Public landing page
- app/bootstrap.php - Core application setup
- app/permissions.php - Role-based permission system
- app/notifications.php - Notification system functions
- app/layout/member_shell.php - Member navigation shell
- app/layout/club_admin_shell.php - Club admin navigation shell
- app/layout/public_shell.php - Public (guest) navigation shell
- public/club.php - Individual club page with join functionality
- public/clubs.php - Browse all clubs
- public/club_members.php - Member-facing club members list
- public/admin/members.php - Club member management (admin)
- under_construction.php - Coming soon / maintenance page

# Angling Ireland

## Overview
Angling Ireland is a free-forever PHP-based web application designed to streamline administration and enhance community experience for various angling entities in Ireland, including clubs, syndicates, commercial fisheries, guides, and charter boats. Its primary purpose is to provide a central digital platform for managing memberships, finances, communications, and member engagement (e.g., catch logging, competitions). The project aims to integrate with national angling governing bodies like the Angling Council of Ireland (ACI) to promote standardized practices and support national events, ultimately modernizing angling club operations and fostering a more connected environment for anglers.

## User Preferences
The user prefers a conversational and helpful interaction style. When making changes, the user expects the agent to ask clarifying questions and confirm understanding before proceeding. The user appreciates detailed explanations for complex tasks or significant architectural decisions. The user explicitly stated to not make changes to folder `Z` and file `Y`.

## System Architecture
The application is built on a PHP foundation, utilizing a unified shell-based navigation system with distinct layouts for different user roles (member, club admin, super admin, public).

### UI/UX Decisions
The UI/UX prioritizes a **mobile-first** design with:
- Collapsible sidebar navigation hidden by default on mobile
- Persistent bottom navigation bar showing account-relevant tabs on mobile (5 key tabs per shell)
- Touch-friendly 44px minimum tap targets
- iOS safe area support for notched devices
- Responsive breakpoints: mobile (<768px), tablet (768-991px), desktop (992px+)

Different shells have tailored mobile bottom navigation:
- **Member shell**: Home, Clubs, Comps, Messages (with badge), Profile
- **Club admin shell**: Members, Catches, Comps, Finance, Settings  
- **Super admin shell**: Dashboard, Clubs, Users, Subs, Exit
- **Public shell**: Used for non-logged-in visitors browsing clubs

SSL enforcement is managed via Cloudflare.

### Club Roles & Permissions

| Role | Type | Permissions |
|------|------|-------------|
| **Owner** | Admin | Full control, cannot be removed/suspended, receives join notifications |
| **Admin** | Admin | Full admin access, cannot be removed/suspended, receives join notifications |
| **Chairperson** | Committee | Can send announcements to all members |
| **Secretary** | Committee | Can send announcements, receives join notifications |
| **Treasurer** | Committee | Standard member access |
| **PRO** | Committee | Standard member access |
| **Safety Officer** | Committee | Standard member access |
| **Child Liaison Officer** | Committee | Standard member access |
| **Member** | Regular | Standard member access |

**Note:** Admins are automatically added to club_members table with 'owner' or 'admin' committee_role so they appear in member counts and lists.

### Technical Implementations
The core structure uses `index.php` as the entry point and `app/bootstrap.php` for essential application setup. It features robust production error handling, security measures via `.htaccess` and security headers, and a planned multi-database architecture for isolating identity, PII, and application data. Navigation is centrally managed, and the system supports both MySQL and PostgreSQL with a migration system for schema evolution. User management includes multiple account types and roles, and image processing uses PHP's GD library for optimization. Branding allows for customizable entity profiles, and the system includes internal notifications and messaging features. An automatic database installer simplifies initial setup.

### Notification System
Located in `app/notifications.php`, provides functions for:
- `notify_membership_request()` - Notifies admins & secretary when someone requests to join
- `notify_membership_approved()` - Notifies member when approved
- `notify_membership_rejected()` - Notifies member when rejected
- `notify_new_news()` - Notifies all members of club news
- `notify_catch_of_month()` - Notifies member of catch of month award
- `notify_competition_results()` - Notifies members when results posted

### Feature Specifications
Key features include a robust user authentication system, comprehensive club management (creation, membership requests, profile customization), and financial tracking. Member engagement is fostered through catch logging, personal bests, club records, 'Catch of the Month', competitions, and personal statistics dashboards. The platform supports sponsors and supporters, includes a governance hub with best practice guides, and provides a document template library for clubs. It also features a committee guide and aims for integration with angling affiliations.

## Recent Changes (January 2026)

### Navigation Overhaul
- Converted all pages to use consistent shell layouts (member_shell, club_admin_shell, public_shell)
- Fixed mobile bottom navigation across all pages
- Pages updated: messages, competitions, profile, notifications, clubs (browse), create_club, tasks, meetings, catches, club pages, leaderboards, policies, competition_results

### Club Browsing & Join System
- Fixed: Guests can now browse clubs without logging in (public_shell for non-logged-in users)
- Fixed: Individual club pages viewable by guests
- Join Club button requires login but browsing does not

### Membership Request Notifications
- Added: Admins and secretaries now receive notifications when someone requests to join
- Notifications link directly to the Members management page

### Club Roles Update
- Added: Club owners are now automatically added to club_members when creating a club
- Added: Owner and Admin badges displayed in member lists
- Added: Protection for admins - cannot be suspended, removed, or have role changed via UI
- Updated: Admins appear first in member lists

## Deployment Notes
- **Production Host**: LetsHost at anglingireland.ie
- **Production Database**: MySQL 8.2.29 (anglingireland_clubmanager)
- **Development Database**: PostgreSQL (Replit)
- **Zip Naming Convention**: Use `anglingirelandv{version}.zip` for releases
- **Current Version**: v28
- **Note**: Zip file named v25 due to Replit download caching issue
- **Excluded from zip**: config.local.php, install.php, setup.lock, db/, replit.md

### Production Migration Notes
For existing clubs in production, run this SQL to add owners to club_members:
```sql
INSERT INTO club_members (club_id, user_id, membership_status, committee_role)
SELECT ca.club_id, ca.user_id, 'active', ca.admin_role
FROM club_admins ca
WHERE NOT EXISTS (
  SELECT 1 FROM club_members cm 
  WHERE cm.club_id = ca.club_id AND cm.user_id = ca.user_id
);
```

## Future Plans

### High Priority
- [ ] Pollution/poaching reporting system
- [ ] Email member invitations
- [ ] Data export functionality
- [ ] Weather/conditions integration for catch logs

### Medium Priority
- [ ] RSS feed personalization
- [ ] Public interactive map of clubs/fisheries
- [ ] Club challenges (inter-club competitions)
- [ ] Internal club leaderboards (seasonal)
- [ ] Gamification/badges system

### Low Priority / Nice to Have
- [ ] Fish species encyclopedia
- [ ] Personal statistics dashboards (detailed)
- [ ] Fishing journal feature
- [ ] Angler goals/wishlists
- [ ] Integration with national specimen fish committee

### Technical Debt
- [ ] Review LSP diagnostics in admin/members.php and create_club.php
- [ ] Consider adding database migrations system for production updates
- [ ] Review and optimize SQL queries for larger clubs

## External Dependencies
- **Database Systems**: MySQL/MariaDB (production) and PostgreSQL (development on Replit).
- **PHP GD Library**: For image manipulation.
- **Angling Council of Ireland (ACI) Federations**:
    - Irish Federation of Sea Anglers (IFSA)
    - Irish Federation of Pike Angling Clubs (IFPAC)
    - National Coarse Fishing Federation of Ireland (NCFFI)
    - Salmon & Sea Trout Recreational Anglers of Ireland (SSTRAI)
    - Trout Anglers Federation of Ireland (TAFI)
- **RSS Feeds**:
    - Fishing in Ireland: `https://fishinginireland.info/feed`
    - Irish Specimen Fish Committee (ISFC): `https://specimenfish.ie/feed`
    - Angling Council of Ireland (News): `https://www.anglingcouncil.ie/feed`

## Key Files Reference
- `index.php` - Public landing page
- `app/bootstrap.php` - Core application setup
- `app/notifications.php` - Notification system functions
- `app/layout/member_shell.php` - Member navigation shell
- `app/layout/club_admin_shell.php` - Club admin navigation shell
- `app/layout/public_shell.php` - Public (guest) navigation shell
- `public/club.php` - Individual club page with join functionality
- `public/clubs.php` - Browse all clubs
- `public/admin/members.php` - Club member management

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

SSL enforcement is managed via Cloudflare.

### Technical Implementations
The core structure uses `index.php` as the entry point and `app/bootstrap.php` for essential application setup. It features robust production error handling, security measures via `.htaccess` and security headers, and a planned multi-database architecture for isolating identity, PII, and application data. Navigation is centrally managed, and the system supports both MySQL and PostgreSQL with a migration system for schema evolution. User management includes multiple account types and roles, and image processing uses PHP's GD library for optimization. Branding allows for customizable entity profiles, and the system includes internal notifications and messaging features. An automatic database installer simplifies initial setup.

### Feature Specifications
Key features include a robust user authentication system, comprehensive club management (creation, membership requests, profile customization), and financial tracking. Member engagement is fostered through catch logging, personal bests, club records, 'Catch of the Month', competitions, and personal statistics dashboards. The platform supports sponsors and supporters, includes a governance hub with best practice guides, and provides a document template library for clubs. It also features a committee guide and aims for integration with angling affiliations. Planned features include pollution/poaching reporting, RSS feed personalization, a public interactive map, club challenges, internal club leaderboards, gamification/badges, a fish species encyclopedia, personal statistics dashboards, a fishing journal, angler goals/wishlists, weather/conditions integration, data export, and email member invitations.

## Deployment Notes
- **Production Host**: LetsHost at anglingireland.ie
- **Production Database**: MySQL 8.2.29 (anglingireland_clubmanager)
- **Development Database**: PostgreSQL (Replit)
- **Zip Naming Convention**: Use `anglingirelandv{version}.zip` for releases
- **Current Version**: v27
- **Note**: Zip file named v25 due to Replit download caching issue
- **Excluded from zip**: config.local.php, install.php, setup.lock, db/, replit.md

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
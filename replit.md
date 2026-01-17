# Angling Club Manager

## Overview
The Angling Club Manager is a PHP-based web application designed to streamline the administration and enhance the community experience for angling clubs, syndicates, commercial fisheries, angling guides, and charter boats. It aims to be the central digital platform for managing memberships, finances, communications, and member engagement (e.g., catch logging, competitions). The project's vision includes integrating with national angling governing bodies like the Angling Council of Ireland (ACI) and its federations to promote standardized practices and support national events. Ultimately, it seeks to modernize angling club operations and foster a more connected and engaging environment for anglers across Ireland.

## User Preferences
The user prefers a conversational and helpful interaction style. When making changes, the user expects the agent to ask clarifying questions and confirm understanding before proceeding. The user appreciates detailed explanations for complex tasks or significant architectural decisions. The user explicitly stated to not make changes to folder `Z` and file `Y`.

## System Architecture
The application is built on a PHP foundation, utilizing a unified shell-based navigation system with distinct layouts for different user roles (member, club admin, super admin, public).

### UI/UX Decisions
The UI/UX prioritizes a polished and responsive experience with subtle animations and micro-interactions. Global CSS (`enhancements.css`) and JavaScript (`enhancements.js`) provide smooth page transitions, button and card hover effects, form input focus states, modal/dropdown animations, and mobile-friendly features like touch feedback and swipe gestures for the sidebar. Accessibility is considered, with animations respecting `prefers-reduced-motion` and utilizing GPU-accelerated CSS transitions.

### Technical Implementations
- **Core Structure**: `index.php` as the entry point, `app/bootstrap.php` for core application setup (session, DB connection, helpers).
- **Navigation**: Managed centrally via `app/nav_config.php` which defines menus for different user roles and includes a breadcrumb rendering function.
- **Database**: Supports both MySQL and PostgreSQL, with specific configurations for local development (MySQL) and Replit (PostgreSQL via environment variables). A migration system is in place (`/db/updates/` and `schema_migrations` table) for schema evolution.
- **User Management**: Supports multiple account types (Angling Club, Syndicate, Commercial Fishery, Angling Guide, Charter Boat) and user roles (User, Club Member, Club Admin, Club Owner) with tailored profiles.
- **Image Processing**: Uses PHP's GD library for resizing and optimizing uploaded images (logos, gallery, catch photos) with specific dimensions and quality settings, and validates file types and sizes.
- **Branding**: Entity profiles function as customizable microsites, allowing for color customization, logo uploads, social media links, taglines, and "About" sections.
- **Internal Notifications**: System for various events like membership approvals, Catch of the Month selection.
- **Messaging**: Club-specific direct messages and announcement features.

### Feature Specifications
- **Authentication**: User authentication system with helper functions (`current_user_id()`, `current_user()`, `require_login()`).
- **Club Management**: Club creation, membership request system (approve/reject, suspend/remove members), and profile customization.
- **Financial Tracking**: Income/expense logging by category, manual account balance tracking, summary reports, and access control.
- **Meeting Management**: Track meetings, minutes, decisions, and assign tasks with associated access controls.
- **Member Engagement**: Catch logging (species, weight, length, photos), personal bests, club records, Catch of the Month, competition seasons, leaderboards, and personal statistics dashboards.
- **Sponsors & Supporters**: Clubs can add sponsors/supporters with name, company, logo, website, and description. Sponsors display on public club profiles. Competitions can also have their own sponsors that display on competition results pages. Managed via `public/admin/sponsors.php` for clubs and directly on competition cards for competitions.
- **Governance Hub**: Best practice guides for club committees based on Sport Ireland's Governance Code and NCFFI guidelines. Covers the 5 governance principles, committee role descriptions, AGM/meeting guidance, safeguarding requirements, financial controls, and external resources. Accessible only to committee members via `public/admin/governance.php`.
- **Club Documents & Templates**: Comprehensive document template library for clubs including constitution template, membership application form, safeguarding policy, parental consent form, code of conduct, and privacy policy. Includes links to official NCFFI/Sport Ireland resources and a document compliance checklist. Accessible via `public/admin/documents.php`.
- **Angling Affiliations**: Tracking and potential integration with national bodies like the Angling Council of Ireland and its federations, with a vision for affiliation tracking and support for unified standards and national competitions.
- **RSS Feed Personalization (Planned)**: Strategy to filter and prioritize RSS feed content based on user-defined favorite fishing styles.

## External Dependencies
- **Database Systems**: MySQL/MariaDB (for local development) and PostgreSQL (for Replit deployment).
- **PHP GD Library**: Used for image manipulation (resizing, optimization).
- **Angling Council of Ireland (ACI) Federations**:
    - Irish Federation of Sea Anglers (IFSA)
    - Irish Federation of Pike Angling Clubs (IFPAC)
    - National Coarse Fishing Federation of Ireland (NCFFI)
    - Salmon & Sea Trout Recreational Anglers of Ireland (SSTRAI)
    - Trout Anglers Federation of Ireland (TAFI)
- **RSS Feeds**:
    - Fishing in Ireland: `https://fishinginireland.info/feed`
    - Irish Specimen Fish Committee (ISFC): `https://specimenfish.ie/feed`
    - Angling Council of Ireland (News): `https://www.anglingcouncil.ie/feed` (to be confirmed/verified)
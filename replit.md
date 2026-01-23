# Angling Ireland

## Overview
Angling Ireland is a free, PHP-based web application designed to centralize administration and enhance community engagement for various angling entities in Ireland, including clubs, syndicates, commercial fisheries, guides, and charter boats. It provides a platform for managing memberships, finances, communications, and member activities like catch logging and competitions. The project aims to integrate with national angling governing bodies to standardize practices, support national events, and modernize angling club operations for a more connected angling community.

## User Preferences
The user prefers a conversational and helpful interaction style. When making changes, the user expects the agent to ask clarifying questions and confirm understanding before proceeding. The user appreciates detailed explanations for complex tasks or significant architectural decisions. Do not make changes to folder `Z` and file `Y`.

## System Architecture
The application is built on a PHP foundation, utilizing a unified shell-based navigation system with distinct layouts for different user roles (member, club admin, super admin, public).

### UI/UX Decisions
The UI/UX prioritizes a **mobile-first** design with collapsible sidebars, persistent bottom navigation on mobile (5 key tabs per shell), touch-friendly targets, and iOS safe area support. SSL enforcement is managed via Cloudflare.

### Club Roles & Permissions System
A granular permission system, implemented in `app/permissions.php`, controls access based on roles such as Owner, Admin, Chairperson, Secretary, Treasurer, PRO, Safety Officer, Child Liaison Officer, and Member. It supports dual roles (e.g., Owner + Chairperson) where admin permissions take precedence. Member visibility is controlled, showing only essential details to regular members and allowing contact via the messaging system.

### Technical Implementations
The core structure uses `index.php` as the entry point and `app/bootstrap.php` for setup. It features robust production error handling, security measures via `.htaccess`, and a planned multi-database architecture. Navigation is centrally managed, and the system supports both MySQL and PostgreSQL with a migration system. User management includes multiple account types and roles, image processing uses PHP's GD library, and branding allows for customizable entity profiles. Internal notifications and messaging features are included, alongside an automatic database installer.

### Notification System
Located in `app/notifications.php`, this system handles various alerts including membership requests (approval/rejection), new club news, catch of the month awards, and competition results.

### Feature Specifications
Key features include robust user authentication, comprehensive club management (creation, membership requests, profile customization), and financial tracking. Member engagement is supported through catch logging, personal bests, club records, 'Catch of the Month', competitions, and personal statistics dashboards. The platform includes sponsor management, a governance hub with best practice guides, a document template library, a committee guide, and aims for integration with angling affiliations.

## External Dependencies
- **Database Systems**: MySQL/MariaDB (production) and PostgreSQL (development).
- **PHP GD Library**: For image manipulation.
- **Angling Council of Ireland (ACI) Federations**: Integration with various federations like IFSA, IFPAC, NCFFI, SSTRAI, and TAFI.
- **RSS Feeds**: Integrates with feeds from Fishing in Ireland, Irish Specimen Fish Committee (ISFC), and Angling Council of Ireland (News).
# Angling Ireland

## Overview
Angling Ireland is a free-forever PHP-based web application designed to streamline administration and enhance community experience for various angling entities in Ireland, including clubs, syndicates, commercial fisheries, guides, and charter boats. Its primary purpose is to provide a central digital platform for managing memberships, finances, communications, and member engagement (e.g., catch logging, competitions). The project aims to integrate with national angling governing bodies to promote standardized practices and support national events, ultimately modernizing angling club operations and fostering a more connected environment for anglers.

## User Preferences
The user prefers a conversational and helpful interaction style. When making changes, the user expects the agent to ask clarifying questions and confirm understanding before proceeding. The user appreciates detailed explanations for complex tasks or significant architectural decisions. The user explicitly stated to not make changes to folder `Z` and file `Y`.

## System Architecture
The application is built on a PHP foundation, utilizing a unified shell-based navigation system with distinct layouts for different user roles (member, club admin, super admin, public).

### UI/UX Decisions
The UI/UX prioritizes a **mobile-first** design with:
- Collapsible sidebar navigation, persistent bottom navigation, touch-friendly tap targets, and iOS safe area support.
- Responsive breakpoints for mobile, tablet, and desktop.
- Tailored mobile bottom navigation for Member, Club Admin, Super Admin, and Public shells.
- SSL enforcement managed via Cloudflare.

### Club Roles & Permissions System
A granular permission system is implemented in `app/permissions.php`.
- **Role Types**: Admin Roles (Owner, Admin), Committee Roles (Chairperson, Secretary, Treasurer, PRO, Safety Officer, Child Liaison Officer), and Regular Member.
- **Dual Role Support**: Owners/Admins can also hold committee roles, with admin permissions taking precedence.
- **Permission Matrix**: Defines access levels (Full, View, Accept/Reject, etc.) for various features across all roles (Members, Meetings, News, Finances, Competitions, etc.).
- **Permission Functions**: `has_permission()`, `can_view()`, `can_edit()`, `get_user_club_roles()`, etc., for robust access control and UI rendering.
- **Member Visibility**: Members can view a public list of other club members (name, profile picture, location, role); contact details are hidden from regular members.

### Technical Implementations
The core structure uses `index.php` as the entry point and `app/bootstrap.php` for essential setup. It features production error handling, security measures via `.htaccess` and security headers, and a multi-database architecture for data isolation. Navigation is centrally managed. The system supports MySQL and PostgreSQL with a migration system. User management includes multiple account types and roles, and image processing uses PHP's GD library. Branding allows for customizable entity profiles, and the system includes internal notifications and messaging. An automatic database installer simplifies initial setup.

### Notification System
Located in `app/notifications.php`, it provides functions for:
- Notifying about membership requests, approvals, and rejections.
- Notifying members of new club news, 'Catch of the Month' awards, and competition results.

### Feature Specifications
Key features include user authentication, comprehensive club management (creation, membership requests, profile customization), and financial tracking. Member engagement is fostered through catch logging, personal bests, club records, 'Catch of the Month', competitions, and personal statistics dashboards. The platform supports sponsors and supporters, includes a governance hub with best practice guides, and provides a document template library. It also features a committee guide and aims for integration with angling affiliations.

## External Dependencies
- **Database Systems**: MySQL/MariaDB (production) and PostgreSQL (development).
- **PHP GD Library**: For image manipulation.
- **Angling Council of Ireland (ACI) Federations**: Integration with various federations like IFSA, IFPAC, NCFFI, SSTRAI, TAFI.
- **RSS Feeds**: Integrates feeds from Fishing in Ireland, Irish Specimen Fish Committee, and Angling Council of Ireland for news and updates.
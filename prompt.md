# PROJECT OVERVIEW


Your task is to build a COMPLETE PRODUCTION-READY web application named:

# KHFinaM
(KH Finance Management)

The final output MUST be a fully functional, deployable, secure production-ready PHP financial management application that can run immediately in:

- XAMPP (Windows)
- AMPPS (Mac)
- cPanel shared hosting
- Apache server
- PHP 8.2+
- MySQL 8+

DO NOT build a prototype.  
DO NOT build a mockup.  
DO NOT leave TODO placeholders.  
DO NOT create incomplete pages.

Everything must work fully end-to-end.

---

# CORE TECH STACK

## Backend
- Pure PHP 8.2+
- NO PHP framework
- Use MVC-inspired clean architecture
- PDO prepared statements only
- REST-like internal API architecture
- Secure session management
- CSRF protection
- Rate limiting
- Secure authentication

## Frontend
- HTML5
- TailwindCSS via CDN
- Material Design inspired UI
- Vanilla JavaScript
- Chart.js
- AJAX interactions
- Responsive mobile-first design
- Progressive Web App (PWA)

## Database
- MySQL 8+
- SQL migration scripts included
- Seeder/demo data included

---

# APPLICATION GOALS

KHFinaM is a multi-user financial management application focused on:

- income tracking
- expense tracking
- recurring financial records
- analytics
- reporting
- savings visibility
- mobile-first quick entry
- desktop admin analytics

The system must feel modern, fast, and production quality.

---

# USER ROLES

## 1. Super Admin

Capabilities:
- create/manage users
- system settings
- SMTP settings
- manage exchange rates
- audit logs
- backup/restore
- full analytics access
- manage notifications
- manage categories
- access all user records

## 2. User

Capabilities:
- manage own records
- manage recurring records
- upload receipts/documents
- view reports/statistics
- manage own settings
- manage own wallets/accounts

---

# AUTHENTICATION REQUIREMENTS

Implement:

- login via username OR email
- password login
- remember me
- secure logout
- password hashing using `password_hash()`
- forgot password flow
- reset password token flow
- session timeout
- brute force protection
- rate limiting
- secure cookies
- HTTPS-aware sessions
- CSRF protection everywhere

Only super admin can create users.

NO public registration.

---

# APPLICATION MODULES

# 1. MOBILE FRONTEND WEB APP

Purpose:
Fast financial entry and quick statistics.

Requirements:
- mobile-first
- responsive
- app-like UI
- installable PWA
- theme switcher
- dark/light mode
- bottom navigation
- floating action button for quick add

Pages:
- dashboard
- add transaction
- recurring records
- wallets/accounts
- statistics
- notifications
- profile/settings

Dashboard should show:
- current balance
- total income
- total expense
- savings
- low balance warnings
- recent transactions
- recurring upcoming transactions
- quick statistics charts

---

# 2. DESKTOP ADMIN DASHBOARD

Purpose:
Detailed reporting and administration.

Requirements:
- responsive desktop analytics dashboard
- sidebar navigation
- advanced filtering
- export functionality
- analytics-heavy UI

Modules:
- overview dashboard
- transaction management
- recurring management
- user management
- category management
- exchange rate management
- notifications
- reports
- analytics
- backups
- audit logs
- system settings

---

# 3. WALLET / ACCOUNT SYSTEM

Implement multiple accounts/wallets.

Examples:
- Cash
- Maybank
- CIMB
- Touch n Go
- Credit Card

Features:
- wallet balance
- transfer between wallets
- wallet type
- opening balance
- active/inactive
- default wallet

System must also show:
- combined total balance across wallets

---

# 4. TRANSACTION SYSTEM

Each transaction supports:

- income or expense
- title
- amount
- wallet/account
- category
- notes/remarks
- transaction date
- currency
- exchange rate
- tags
- attachments
- created by
- soft delete
- edit history

Categories are fully customizable.

---

# 5. CONSOLIDATED RECORDS

Support parent-child transaction structure.

Example:

Parent:
“Monthly Groceries RM500”

Child records:
- Milk RM20
- Chicken RM50
- Rice RM30

Requirements:
- child records are normal records
- child total must NOT exceed parent amount
- auto validation
- expandable UI
- edit support

---

# 6. RECURRING TRANSACTION ENGINE

Support:
- daily
- weekly
- monthly
- yearly
- custom intervals

Features:
- start date
- end date
- pause recurring
- skip next occurrence
- editable generated records
- auto generation via cron job
- manual trigger option
- recurring preview

Allow:
- recurring salary
- recurring loan deductions
- recurring bills

---

# 7. LOW BALANCE WARNING SYSTEM

Users can set:
- minimum balance threshold per wallet

System behavior:
- warning banner
- in-app notification
- daily email reminders
- user can mute reminders

Notification center required.

---

# 8. ATTACHMENT SYSTEM

Allow multiple uploads per transaction.

Allowed:
- JPG
- JPEG
- PNG
- PDF

Requirements:
- max 10MB per file
- secure upload handling
- file validation
- rename uploaded files securely
- attachment preview
- attachment deletion/edit
- image thumbnail preview

Folder security required.

---

# 9. REPORTS & ANALYTICS

Implement:

- income vs expense
- monthly summary
- yearly summary
- category breakdown
- savings analysis
- wallet performance
- recurring obligations
- spending trend
- cashflow chart
- custom date range reports
- top expense categories
- transaction heatmap
- export CSV
- export PDF

Use Chart.js.

---

# 10. SAVINGS CALCULATION

System must calculate:

Savings = total income - total expense

Show:
- monthly savings
- yearly savings
- trend analysis

---

# 11. MULTI CURRENCY SUPPORT

Requirements:
- default MYR
- customizable currencies
- manual exchange rates
- base currency support
- historical exchange storage

---

# 12. NOTIFICATION SYSTEM

Implement:
- in-app notifications
- email notifications
- notification preferences
- unread counter
- mark as read
- low balance notifications
- recurring reminders

SMTP settings configurable from admin panel.

Support:
- SMTP
- PHP mail fallback

Include:
- test email feature

---

# 13. BACKUP & RESTORE

Implement:
- manual DB backup
- scheduled backup
- downloadable backup ZIP
- restore backup
- backup logs

---

# 14. AUDIT LOG SYSTEM

Track:
- logins
- failed logins
- transaction edits
- deletions
- user changes
- system changes
- notification logs

Super admin can view logs.

---

# 15. PWA FEATURES

Implement:
- installable app
- manifest.json
- offline page
- service worker
- splash screen
- app icons
- caching strategy

Push notifications can be placeholder-ready.

---

# 16. FUTURE PHASE ARCHITECTURE PREPARATION

Prepare architecture for:
- OCR receipt scanning
- AI insights
- Telegram integration
- WhatsApp notifications
- mobile app integration
- external API integrations

Create clean extensible structure.

---

# SECURITY REQUIREMENTS

MANDATORY:

- CSRF protection
- prepared statements only
- XSS sanitization
- secure file uploads
- MIME validation
- rate limiting
- session regeneration
- secure cookies
- CSP headers
- .htaccess protection
- directory traversal prevention
- password hashing
- login throttling
- audit logging
- input validation everywhere

---

# DATABASE REQUIREMENTS

Create:
- complete SQL schema
- migration scripts
- seeders
- indexes
- foreign keys

Use:
- utf8mb4
- InnoDB

---

# DEMO DATA REQUIREMENTS

Create realistic seeded demo data.

Include:
- multiple users
- super admin
- wallets
- categories
- recurring salary
- recurring loans
- recurring utilities
- hundreds of transactions
- attachments
- notifications
- reports-ready data

Demo should properly showcase all analytics.

Provide test accounts:

## Super Admin
- username: `superadmin`
- password: `Admin@123`

## User
- username: `demo`
- password: `Demo@123`

---

# UI/UX REQUIREMENTS

Design style:
- Material Design inspired
- clean fintech feel
- premium modern UI
- responsive
- smooth transitions
- mobile optimized
- desktop optimized
- accessible color contrast

Include:
- dark mode
- light mode
- theme persistence

---

# PROJECT STRUCTURE REQUIREMENTS

Create organized structure:

```text
/app
/config
/public
/uploads
/storage
/routes
/database
/resources
/assets
/cron
/logs
```

Use:
- controllers
- models
- services
- repositories
- helpers
- middleware

---

# REQUIRED DOCUMENTATION

Generate complete documentation.

## 1. README.md

Include:
- features
- setup
- requirements
- troubleshooting
- deployment

## 2. INSTALLATION.md

Include:
- XAMPP setup
- AMPPS setup
- cPanel deployment
- SSL setup
- cron setup
- permissions
- SMTP setup

## 3. API_DOCS.md

Document internal APIs.

## 4. SECURITY.md

Document security architecture.

---

# CPANEL DEPLOYMENT REQUIREMENTS

Application must be directly deployable to cPanel.

Include:
- `.htaccess`
- environment config
- production optimization
- upload paths
- cron commands
- SSL recommendations

Provide exact cron examples.

---

# CRON JOB REQUIREMENTS

Create cron scripts for:
- recurring generation
- email notifications
- scheduled backups
- cleanup tasks

---

# ERROR HANDLING

Implement:
- centralized logging
- production-safe error pages
- debug mode
- exception handling
- graceful failures

---

# PERFORMANCE REQUIREMENTS

Optimize:
- SQL queries
- indexes
- asset loading
- lazy loading
- pagination
- caching where appropriate

---

# FINAL OUTPUT REQUIREMENTS

The final generated project MUST:

- run immediately after setup
- contain complete working code
- contain NO placeholders
- contain NO fake APIs
- contain NO missing implementations
- contain NO TODOs

Everything must function.

---

# DELIVERY REQUIREMENTS

Generate:
- full source code
- SQL schema
- seeded demo data
- all frontend pages
- backend dashboard
- cron scripts
- documentation
- deployment instructions
- PWA assets
- authentication system
- reports
- charts
- uploads system
- backup system

---

# CODING STANDARDS

Use:
- clean code
- reusable components
- secure architecture
- comments where needed
- readable naming
- consistent formatting

---

# IMPORTANT IMPLEMENTATION NOTES

- DO NOT use Laravel
- DO NOT use Symfony
- DO NOT use CodeIgniter
- DO NOT use external paid services
- DO NOT depend on Node.js build pipeline
- CDN usage is acceptable
- Everything must work in shared hosting

---

# SUCCESS CRITERIA

The application should feel like:
- a real commercial fintech app
- stable
- secure
- polished
- modern
- production-ready

The owner should be able to:
1. upload to cPanel
2. import database
3. configure env
4. use immediately

without additional development.

---

# EXECUTION PLAN

Build in phases:

1. Project architecture
2. Database schema
3. Authentication
4. Wallet system
5. Transaction engine
6. Recurring engine
7. Reporting
8. Notifications
9. Upload system
10. Admin dashboard
11. Mobile frontend
12. Security hardening
13. PWA
14. Backup system
15. Documentation
16. Final QA/testing

Each phase must be completed fully before next phase.

At the end:
- perform final QA review
- ensure all routes/pages work
- ensure responsive design works
- ensure seeded data works
- ensure exports work
- ensure cron scripts work
- ensure deployment instructions are complete

Build the complete application now.
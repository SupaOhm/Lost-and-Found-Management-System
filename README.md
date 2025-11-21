## Lost & Found Management System – Quickstart

Instructions to set up, run, and reference database logic.

### 1. Clone

#### If starting from a zipped archive

```
1. Unzip the folder into your web root (e.g. `/Applications/MAMP/htdocs/`).
2. Rename the directory if desired (ensure URLs match new name).
3. Skip cloning; continue with Database Initialization (Step 2).
```
#### or using git
```bash
git clone https://github.com/SupaOhm/Lost-and-Found-Management-System.git
cd Lost-Found   # Or project root name
```

### 2. Initialize Database
Adjust credentials as needed; run in order:
```bash
mysql -u root -p < sql/schema.sql
mysql -u root -p lost_found_db < sql/config.sql
mysql -u root -p lost_found_db < sql/scripts.sql
mysql -u root -p lost_found_db < sql/privileges.sql
mysql -u root -p lost_found_db < sql/syntheticrecords.sql   # optional sample data
```

### 3. Configure DB Connections
Edit `config/userconfig.php`, `config/adminconfig.php`, `config/staffconfig.php`:
```php
$host = 'localhost';
$port = '8889';        // Change to your MySql port
$dbname = 'lost_found_db';
$user = 'user_user';   // or admin_user / staff_user
$password = 'user_password';
```

### 4. Access URLs (Local)
- User: `http://localhost/Lost-Found-Submission/` (index page)
- Admin: `http://localhost/Lost-Found-Submission/pages/admin_login.php`
- Staff: `http://localhost/Lost-Found-Submission/pages/staff_login.php`

**Default Credentials** (if using `syntheticrecords.sql`):
- **User:** Username: `user` | Password: `user1234`
- **Admin:** Username: `admin` | Password: `admin123`
- **Staff:** Username: `staff` | Password: `staff123`
  
*Note: You can create additional staff accounts from the Admin Staff Management page.*


### 5. Project Tree
```
Lost-Found/
├── index.php                          # Root entry point (redirects to login)
├── pages/
│   ├── login.php                      # User login page
│   ├── register.php                   # User registration
│   ├── logout.php                     # Logout handler
│   ├── admin_login.php                # Admin login
│   ├── staff_login.php                # Staff login
│   ├── user/
│   │   ├── userdash.php               # User dashboard with notifications
│   │   ├── userprofile.php            # User profile management
│   │   ├── lost.php                   # Report lost item
│   │   ├── found.php                  # Report found item
│   │   ├── search.php                 # Search items
│   │   ├── claim.php                  # Manage claims & reports
│   │   ├── match_results.php          # View potential matches
│   │   ├── item_detail.php            # Item detail view with guidance
│   │   ├── edit_profile.php           # Edit user profile
│   │   ├── changeuserpassword.php     # Change password
│   │   └── includes/
│   │       └── header.php             # User header component
│   ├── admin/
│   │   ├── admin_dashboard.php        # Admin dashboard
│   │   ├── admin_staff.php            # Manage staff
│   │   ├── admin_users.php            # Manage users
│   │   ├── admin_claim.php            # Manage claims
│   │   ├── admin_report.php           # Manage reports
│   │   └── includes/
│   │       └── header.php             # Admin header component
│   └── staff/
│       ├── staff_dashboard.php        # Staff dashboard
│       ├── staff_claim.php            # Manage claims
│       ├── staff_report.php           # Manage reports
│       └── includes/
│           └── header.php             # Staff header component
├── config/
│   ├── userconfig.php                 # User database config
│   ├── adminconfig.php                # Admin configuration
│   └── staffconfig.php                # Staff configuration
├── includes/
│   └── functions.php                  # Helper functions (encrypt/decrypt phone, sanitization)
├── assets/
│   ├── style.css                      # Global styles
│   └── admin-style.css                # Admin & Staff styles
├── sql/
│   ├── schema.sql                     # Database schema
│   ├── scripts.sql                    # Stored procedures & triggers
│   ├── privileges.sql                 # Database user privileges
│   └── syntheticrecords.sql           # Sample data for testing
└── README.md                          # This file
```

### 6. Database Triggers
- `BeforeUserDelete` – Cleans up related claims, found items, lost items prior to user removal.
- `BeforeFoundItemDelete` – Removes related `ClaimRequest` rows before deleting a found item.

### 7. Stored Procedures
- `GetUserById()` – Fetch user profile.
- `ReportFoundItem()` – Insert found item report.
- `ReportLostItem()` – Insert lost item report.
- `SubmitClaim()` – Create claim request.
- `ViewPendingClaimsWithFoundDetails()` – Pending claims + associated found item.
- `ViewProcessedClaimsWithDetails()` – Approved/rejected claims + approver info.
- `ViewStaffProcessedClaims()` – Processed claims approved by a specific staff member.
- `ApproveClaim()` – Sets status approved; writes approver to `admin_approver_id` or `staff_approver_id`.
- `RejectClaim()` – Sets status rejected; writes approver column appropriately.
- `GetUserLostItems()` – All lost items for user.
- `GetUserFoundItems()` – All found items for user.
- `GetUserLostItemsCount()` – Count of user lost items.
- `GetUserFoundItemsCount()` – Count of user found items.
- `GetUserClaims()` – User’s claim requests with item details.
- `GetUserClaimsCount()` – Count of user claims.
- `FindPotentialMatches()` – Matching algorithm (category, location, date window, name similarity scoring).
- `GetUserMatchCount()` – Count potential matches for user.
- `GetUserClaimNotifications()` – Recent claim decisions (approved/rejected).
- `GetFoundItemClaimNotifications()` – Notifications for found item owners.
- `SearchItems()` – Unified search with filters.
- `UpdateUserProfile()` – Update profile data (with phone encryption handling).

### 8. Appendix
### Overview

Lost & Found is a community-driven platform designed to help reunite people with their belongings. Users can:
- **Report Lost Items** — Post details about items they've lost with descriptions
- **Report Found Items** — Help reunite found items with their owners
- **Smart Matching** — Automatic detection of potential matches between lost and found items
- **Search Database** — Browse and filter through lost and found reports
- **Manage Claims** — Submit, track, and manage claims on found items
- **Contact Owners** — Direct contact information for lost item reporters
- **Admin Dashboard** — Administrative staff can approve/reject claims and manage reports

### Features

#### User Features
- **User Authentication** — Secure registration and login with encrypted phone storage
- **Report Items** — Report lost or found items with detailed descriptions
- **Intelligent Matching** — System automatically finds potential matches (5-10 point scoring based on category, location, date, and name similarity)
- **Real-time Notifications** — Bell icon notifications for potential matches and claim updates
- **Search & Filter** — Find items by category, status, date, location, and type
- **Claims Management** — Submit and track claims on found items with approval workflow
  - View button for approved claims to access owner contact info
  - Track claim status (pending/approved/rejected) with visual indicators
- **Contact Information Sharing** — Smart contact info visibility:
  - Lost items: Contact info always visible to help return items
  - Found items: Contact info revealed only after claim approval
  - Approved claimers can view owner contact details
  - Found item owners can view claimer contact details after approval
- **Profile Management** — View and update user profile information with encrypted phone numbers
- **Enhanced Dashboard** — Personal dashboard with stats, notifications, and quick actions
- **Step-by-Step Guidance** — Context-aware guidance for claiming found items or contacting lost item reporters
- **Status Management** — Mark lost items as found or found items as returned
- **Responsive Design** — Modern gradient-based UI that works seamlessly on all devices

#### Admin Features
- **Claim Approval/Rejection** — Review and approve/reject user claims with admin notes
  - Full item details displayed in claim review interface
  - Contact information sharing automatically triggered upon approval
- **Report Management** — View and manage all lost and found reports with complete item details
- **User Management** — View and manage user accounts
- **Staff Management** — Create and manage staff accounts
- **Dashboard Analytics** — View system-wide statistics and activity

#### Staff Features
- **Report Verification** — Verify and manage item reports with full item details
- **Claim Processing** — Assist with claim verification and approval
  - Complete item information displayed during claim review
- **User Support** — Help users with claims and inquiries

### Tech Stack

- **Backend:** PHP 7+/8+ with PDO
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Bootstrap 5.3.3
- **Icons:** Bootstrap Icons 1.11.3
- **Additional:** JavaScript (Bootstrap Bundle)
- **Encryption:** AES-256-CBC for phone numbers
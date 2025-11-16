# Lost & Found Management System

A comprehensive web-based platform that connects people who have lost items with those who have found them. The system streamlines the process of reporting lost/found items, searching databases, and managing claims efficiently.

## Overview

Lost & Found is a community-driven platform designed to help reunite people with their belongings. Users can:
- **Report Lost Items** — Post details about items they've lost with descriptions and photos
- **Report Found Items** — Help reunite found items with their owners
- **Search Database** — Browse and filter through lost and found reports
- **Manage Claims** — Track and manage claims on items
- **Admin Dashboard** — Administrative staff can approve/reject claims and manage reports

## Features

### User Features
- **User Authentication** — Secure registration and login
- **Report Items** — Report lost or found items with detailed descriptions
- **Search & Filter** — Find items by category, status, date, and type
- **Claims Management** — Submit, track, and manage claims on items
- **Profile Management** — View and update user profile information
- **Dashboard** — Personal dashboard showing lost items, found items, and active claims
- **Responsive Design** — Works seamlessly on desktop, tablet, and mobile devices

### Admin Features
- **Claim Approval/Rejection** — Review and approve/reject user claims
- **Report Management** — Manage all lost and found reports
- **User Management** — View and manage user accounts
- **Dashboard Analytics** — View system statistics and activity

### Staff Features
- **Report Verification** — Verify and manage reports
- **User Support** — Assist users with claims and inquiries

## Technology Stack

- **Backend:** PHP 7+/8+ with PDO
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Bootstrap 5.3.3
- **Icons:** Bootstrap Icons 1.11.3
- **Additional:** JavaScript (Bootstrap Bundle)

## Project Structure

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
│   │   ├── userdash.php               # User dashboard
│   │   ├── userprofile.php            # User profile management
│   │   ├── lost.php                   # Report lost item
│   │   ├── found.php                  # Report found item
│   │   ├── search.php                 # Search items
│   │   ├── claim.php                  # Manage claims & reports
│   │   ├── item_detail.php            # Item detail view
│   │   ├── changeuserpassword.php     # Change password
│   │   └── includes/
│   │       └── header.html            # User header component
│   ├── admin/
│   │   ├── admin_dashboard.php        # Admin dashboard
│   │   ├── admin_staff.php            # Manage staff
│   │   ├── admin_users.php            # Manage users
│   │   ├── admin_claim.php            # Manage claims
│   │   └── admin_report.php           # Manage reports
├── config/
│   ├── db.php                         # Database connection
│   ├── adminconfig.php                # Admin configuration
│   └── staffconfig.php                # Staff configuration
├── includes/
│   └── functions.php                  # Helper functions
├── assets/
│   ├── style.css                      # Global styles
│   └── admin-style.css                # Admin styles
├── sql/
│   ├── schema.sql                     # Database schema
│   ├── config.sql                     # Database configuration
│   ├── scripts.sql                    # Stored procedures & triggers
│   └── users.sql                      # Default users
└── README.md                          # This file
```

## Database Schema

### Main Tables
- **User** — User accounts (username, email, password, etc.)
- **LostItem** — Lost item reports (item_name, description, status, etc.)
- **FoundItem** — Found item reports (item_name, description, status, etc.)
- **ClaimRequest** — Claims linking users to items (status: pending/approved/rejected)

### Status Values
- **LostItem.status:** `pending`, `claimed`
- **FoundItem.status:** `available`, `returned`
- **ClaimRequest.status:** `pending`, `approved`, `rejected`

### Stored Procedures
- `GetUserById()` — Retrieve user information
- `ReportFoundItem()` — Insert found item report
- `ReportLostItem()` — Insert lost item report
- `SubmitClaim()` — Submit a claim request
- `ViewPendingClaims()` — Get pending claims with item details
- `ApproveClaim()` — Approve a claim
- `RejectClaim()` — Reject a claim
- `GetUserLostItemsCount()` — Count user's lost items
- `GetUserFoundItemsCount()` — Count user's found items

## Installation

### Prerequisites
- PHP 7.4+ or PHP 8+
- MySQL 5.7+
- A web server (Apache, Nginx, etc.)
- MAMP/LAMP/LEMP stack or similar

### Setup Steps

1. **Clone/Extract** the project to your web server root:
   ```bash
   git clone https://github.com/SupaOhm/Lost-and-Found-Management-System.git
   cd Lost-Found
   ```

2. **Create Database:**
   ```bash
   mysql -u root -p < sql/schema.sql
   mysql -u root -p lost_found_db < sql/scripts.sql
   mysql -u root -p lost_found_db < sql/users.sql
   ```

3. **Configure Database Connection:**
   Edit `config/db.php` with your database credentials:
   ```php
   $host = 'localhost';
   $dbname = 'lost_found_db';
   $user = 'root';
   $password = 'your_password';
   ```

4. **Access the Application:**
   - User Login: `http://localhost/Lost-Found/`
   - Admin Login: `http://localhost/Lost-Found/pages/admin_login.php`
   - Staff Login: `http://localhost/Lost-Found/pages/staff_login.php`

### Default Credentials (Development)
Check `sql/users.sql` for default test users created during database initialization.

## Key Features in Detail

### User Dashboard
- View stats on lost items, found items, and active claims
- Quick access to report new items
- Links to search database and manage claims

### Claims & Reports Management
- **Left Panel:** View all your reported items (lost/found) with filters
  - Filter by Type (Lost/Found) and Status (Open/Closed)
  - Delete reports
  - Mark items as found/returned
- **Right Panel:** View your claims on other users' items
  - Track claim status (pending/approved/rejected)
  - Delete claims
- **Profile Stats:** View counts of your lost items, found items, and claims

### Search & Discovery
- Search through all lost and found items in the database
- Filter by category, location, date range, and status
- View item details including owner contact information (if available)
- Submit claims directly from item detail page

### Admin Panel
- View all reports and claims in the system
- Approve or reject claims
- Manage user accounts and staff members
- View system-wide statistics

## Usage

### For Users

1. **Register** — Create an account on the registration page
2. **Report Lost Item** — Navigate to "Report Lost Item" and fill in details
3. **Report Found Item** — Navigate to "Report Found Item" and describe the item
4. **Search** — Use the search feature to find items matching your needs
5. **Claim** — Submit a claim on a found item if you believe it's yours
6. **Manage** — Track your claims and reports from the dashboard

### For Admins

1. **Login** — Use admin credentials to access the admin panel
2. **Review Claims** — Review pending claims and approve/reject them
3. **Manage Reports** — Monitor and manage all system reports
4. **Manage Users** — View and manage user accounts

## Security Features

- **Password Hashing** — User passwords are hashed using bcrypt
- **Prepared Statements** — PDO prepared statements prevent SQL injection
- **Session Management** — Secure session-based authentication
- **Input Validation** — Server-side validation and sanitization
- **Ownership Checks** — Users can only modify their own reports/claims

## UI/UX Features

- **Responsive Design** — Optimized for all screen sizes
- **Bootstrap Framework** — Clean, modern interface
- **Intuitive Navigation** — Easy-to-use menu structure
- **Visual Feedback** — Status badges, loading states, confirmations
- **Aesthetic Components** — Color-coded badges, icon buttons, smooth transitions
- **Accessibility** — Semantic HTML, ARIA labels, keyboard navigation

## Known Limitations & Future Improvements

- Currently storing items locally; file upload for photos could be added
- Email notifications not yet implemented
- Two-factor authentication not yet supported
- Advanced analytics dashboard planned
- Mobile app version under consideration

## Contributing

This project is in active development. Contributions are welcome!

## License

This project is licensed under the MIT License.

## Support & Contact

For issues, feature requests, or support:
- Create an issue on the repository
- Contact: help@lostfound.local

## Project Status

🔄 **In Active Development** — Features and improvements are being added regularly.
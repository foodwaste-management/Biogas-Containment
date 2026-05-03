# Biogas Containment Monitoring System (BCMS)

## Purpose
The **Biogas Containment Monitoring System (BCMS)** is a real-time web application designed to track and manage biogas operations. It provides critical monitoring for methane levels, gas usage, and storage capacity to ensure operational safety and efficiency.

## Project Structure
```text
bcms/
├── app/                 # Role-based application modules
│   ├── admin/           # Admin dashboard and user management
│   ├── manager/         # Reporting and analytics
│   └── user/            # User personal dashboard and readings
├── assets/              # Static assets (CSS, JS, Images)
├── config/              # Database and application configuration
├── database/            # SQL schema and database migrations
├── includes/            # Reusable components (Sidebar, Header, Logger)
├── index.php            # Main login entry point
├── logout.php           # Session termination
└── README.md            # Project documentation
```

## Key Modules
The application uses a role-based access control system:

### 1. Admin Module (`/app/admin`)
- **Purpose**: Full system control and user management.
- **Inputs**: User registration data, system logs.
- **Outputs**: User accounts, global activity logs, system-wide sensor overview.

### 2. Manager Module (`/app/manager`)
- **Purpose**: Data analysis and reporting.
- **Inputs**: Filtered date ranges for sensor data.
- **Outputs**: Detailed reports, activity summaries, performance tracking.

### 3. User Module (`/app/user`)
- **Purpose**: Personal monitoring for biogas facility owners.
- **Inputs**: Real-time sensor readings (Methane, Gas Flow, Pressure).
- **Outputs**: Interactive gauges, consumption charts, CSV data exports.

## Integration & Setup
To deploy or integrate this system, follow these steps:

1.  **Configuration**: 
    - Open `config/config.php`.
    - Update `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` with your server credentials.
2.  **Database**:
    - Import the database schema into your MySQL server.
3.  **Environment**:
    - Host the files on a PHP-enabled server (XAMPP, Hostinger, etc.).
    - Ensure `BASE_URL` in `config.php` matches your deployment path.
4.  **Security**:
    - Verify that `.htaccess` or server settings protect the `config/` and `includes/` directories.

## Technical Details
- **Frontend**: Vanilla CSS, Inter/Google Fonts, Chart.js for data visualization.
- **Backend**: PHP (PDO & MySQLi), Role-based session management.
- **Database**: MySQL with relational integrity for logs and readings.
- **Naming Conventions**: Consistent camelCase for JavaScript and snake_case for PHP variables/database columns.

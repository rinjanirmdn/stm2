<p align="center">
  <img src="public/img/e-Docking%20Control%20System.png" alt="e-Docking Control System Logo" width="400">
</p>

# e-Docking Control System (e-DCS)

## Overview
e-Docking Control System (e-DCS) is a web-based application designed to streamline, monitor, and manage warehouse docking activities. It provides an efficient way to handle incoming and outgoing shipments, manage vendor bookings, allocate gates, and monitor the overall lifecycle of a slot or booking request.

## Key Features
- **Vendor Booking Management**: Allows the PPIC, Purchasing, and EXIM divisions to submit requests and schedule loading and unloading times for vendors
- **Gate Allocation**: Efficiently assign warehouse gates to scheduled trucks.
- **Live Status Monitoring**: Real-time tracking of slot statuses (Scheduled, Arrived, In Progress, Completed).
- **Photo Documentation**: Capture start and completion photos directly in the system via a robust backend storage.
- **Reporting & Dashboards**: Comprehensive analytics and operational dashboards for Section Heads and Administrators.
- **Conflict Management**: Detect overlapping schedules and provide early warnings for lane congestion.

## Technology Stack
- **Framework**: Laravel 11
- **Language**: PHP 8.3
- **Database**: PostgreSQL / MySQL
- **Frontend**: Blade Templates, Vanilla JS, Custom CSS (e-DCS UI Kit)

## Installation & Setup

1. Clone the repository:
   ```bash
   git clone <repository_url>
   cd stm2
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Setup environment configuration:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your `.env` file with proper database credentials and ensure `APP_URL` is set correctly.

5. Run database migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```

6. Link the storage directory (critical for photo documentation):
   ```bash
   php artisan storage:link
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

## Photo Documentation Flow
This system is equipped with cross-device photo capture capabilities. Photos taken during the Start and Complete processes are securely saved to the database (via `SlotPhotoController`) and accessed dynamically to avoid cross-domain symlink issues.

## Roles & Permissions
- **Super Admin & Admin**: Full access to all configurations, master data, and manual overrides.
- **Section Head**: Can view dashboards, approve backdates, and receive critical notifications.
- **Security / Gate Checker**: Handles vehicle arrival logs and ticket scanning.
- **Warehouse Operator**: Records start and complete processes along with photo proofs.
- **Vendor**: Books slots based on PO/SO availability.

---
*Built for efficient warehouse logistics and gate control.*

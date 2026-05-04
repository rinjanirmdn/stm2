<p align="center">
  <img src="https://raw.githubusercontent.com/rinjanirmdn/stm2/main/public/img/e-Docking%20Control%20System.png" alt="e-Docking Control System Logo" width="400">
</p>

# e-Docking Control System (e-DCS)

## Overview
e-Docking Control System (e-DCS) is a comprehensive web-based application designed to streamline, monitor, and manage warehouse docking activities. It provides an efficient way to handle incoming and outgoing shipments, manage vendor bookings, allocate gates, and monitor the overall lifecycle of booking requests.

## 🚀 Key Features
- **📅 Vendor Booking Management**: PPIC, Purchasing, and EXIM divisions can submit requests and schedule loading/unloading times
- **🚪 Gate Allocation**: Efficient assignment of warehouse gates to scheduled trucks
- **📊 Live Status Monitoring**: Real-time tracking of slot statuses (Scheduled, Arrived, In Progress, Completed)
- **📸 Photo Documentation**: Capture start and completion photos with robust backend storage
- **📈 Reporting & Dashboards**: Comprehensive analytics and operational dashboards
- **⚠️ Conflict Management**: Detect overlapping schedules and lane congestion warnings
- **🔔 Real-time Notifications**: WebSocket-based notifications for critical updates
- **📱 Responsive Design**: Mobile-friendly interface for all user roles
- **📄 Export Capabilities**: Excel exports for reports and offline templates
- **🎫 QR Code Support**: Ticket scanning and validation system

## 🛠 Technology Stack

### Backend
- **Framework**: Laravel 11
- **Language**: PHP 8.3+
- **Database**: PostgreSQL / MySQL
- **Queue**: Redis/Database
- **WebSocket**: Laravel Reverb (Pusher compatibility)

### Frontend
- **Templates**: Blade
- **JavaScript**: Vanilla JS + React (for dashboards)
- **CSS**: Custom e-DCS UI Kit + TailwindCSS
- **Charts**: Recharts
- **Icons**: Lucide React
- **Build Tool**: Vite

### Key Packages
- `spatie/laravel-permission` - Role-based permissions
- `maatwebsite/excel` - Excel imports/exports
- `barryvdh/laravel-dompdf` - PDF generation
- `milon/barcode` - Barcode generation
- `laravel/dusk` - Browser testing

## 📋 System Requirements

- PHP 8.3+
- Composer 2.0+
- Node.js 18+
- PostgreSQL 13+ / MySQL 8.0+
- Redis (optional, for queues)

## 🚀 Installation & Setup

### Quick Setup (Recommended)
```bash
# Clone the repository
git clone https://github.com/rinjanirmdn/stm2.git
cd stm2

# Run automated setup
composer run setup
```

### Manual Setup

1. **Clone and install dependencies**:
   ```bash
   git clone https://github.com/rinjanirmdn/stm2.git
   cd stm2
   composer install
   npm install
   ```

2. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Configure `.env`**:
   ```env
   APP_NAME="e-Docking Control System"
   APP_URL=http://localhost:8000
   
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=stm2
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   BROADCAST_DRIVER=reverb
   REVERB_APP_ID=your_app_id
   REVERB_APP_KEY=your_key
   REVERB_APP_SECRET=your_secret
   ```

4. **Database setup**:
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```

5. **Build assets**:
   ```bash
   npm run build
   ```

6. **Start development**:
   ```bash
   # All services (server, queue, logs, vite)
   composer run dev
   
   # Or individually
   php artisan serve
   php artisan queue:work
   npm run dev
   ```

## 🏗 Project Structure

```
stm2/
├── app/
│   ├── Http/Controllers/          # API and Web controllers
│   ├── Models/                    # Eloquent models
│   ├── Services/                  # Business logic services
│   └── Jobs/                      # Queue jobs
├── database/
│   ├── migrations/                # Database migrations
│   └── seeders/                   # Database seeders
├── resources/
│   ├── views/                     # Blade templates
│   ├── js/                        # JavaScript files
│   └── css/                       # Stylesheets
├── routes/                        # Route definitions
├── tests/                         # Test files
└── public/                        # Public assets
```

## 👥 User Roles & Permissions

### Super Admin & Admin
- Full access to all configurations
- Master data management
- User and role management
- System settings and overrides

### Section Head
- View comprehensive dashboards
- Approve backdate requests
- Receive critical notifications
- Access to all reports

### Security / Gate Checker
- Vehicle arrival logging
- Ticket scanning and validation
- Gate status updates
- Security dashboard access

### Warehouse Operator
- Start and complete process recording
- Photo documentation
- Real-time status updates
- Mobile-optimized interface

### Vendor
- Slot booking based on PO/SO availability
- View own booking history
- Receive booking notifications
- QR code ticket access

## 📊 Core Modules

### Booking Management
- Planned and unplanned slot bookings
- PO/SO validation
- Conflict detection
- Approval workflows

### Gate Control
- Gate allocation and monitoring
- Real-time gate status
- Queue management
- Traffic optimization

### Reporting System
- Daily/weekly/monthly reports
- Performance analytics
- Export capabilities
- Custom report builder

### Notification System
- Real-time WebSocket notifications
- Email notifications
- Mobile push notifications
- Reminder alerts

## 🧪 Testing

```bash
# Run all tests
composer run test

# Run specific test
php artisan test --filter BookingTest

# Run browser tests
php artisan dusk
```

## 📦 Deployment

### Environment Configuration
1. Set production environment variables
2. Configure database and cache
3. Set up queue workers
4. Configure WebSocket broadcasting

### Build & Deploy
```bash
# Production build
npm run build
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'feat: add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue in this repository
- Check the [documentation](docs/)
- Review existing issues and discussions

---

**Built with ❤️ for efficient warehouse logistics and gate control**

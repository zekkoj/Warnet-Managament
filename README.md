# 🎮 Warnet Management System

**Integrated PC Rental & F&B Management System** for Internet Cafe (Warnet) with 45 PC units, complex pricing logic, QRIS/Cash payment, and analytics dashboard.

---

## 📊 Project Overview

This system manages:
- **45 PC Units** (30 Regular + 15 VIP)
- **PC Rental** with dynamic tier-based pricing
- **F&B Orders** integrated with PC rental
- **Dual Payment Methods** (QRIS & Cash)
- **Analytics & Reporting**

**Built With:** Laravel 12 + MySQL + Sanctum API

---

## ✨ Key Features

### 🖥️ PC Management
- Real-time PC status monitoring (IDLE, IN_USE, MAINTENANCE)
- Resource monitoring (CPU, RAM, Disk usage)
- Remote control commands (Shutdown, Restart, Lock)
- PC usage history & analytics

### ⏱️ Rental System
- **Complex Pricing Logic:**
  - Base rates: REGULER (Rp 7,000/jam), VIP (Rp 10,000/jam)
  - Tier discount: Hour 2+ discounted (REGULER Rp 6,000, VIP Rp 8,000)
  - Night rate: 20% discount (22:00 - 05:00)
  - Mixed period calculation for sessions crossing night boundaries
- Session extension with auto-recalculation
- Detailed pricing breakdown per period

### 🍔 F&B Management
- 5 menu categories (Makanan Berat, Makanan Ringan, Minuman Dingin/Panas/Sachet)
- Order workflow: RECEIVED → PREPARING → READY → DELIVERED
- Integration with PC rental (orders linked to sessions)
- Automatic total calculation

### 💳 Payment System
- QRIS payment (ready for provider integration)
- Cash payment with manual confirmation
- Unified payment for both PC rental & F&B
- Payment history & export

### 📊 Analytics Dashboard
- Revenue analytics (PC vs F&B breakdown by date/hour)
- PC usage statistics (most used PCs, peak hours, occupancy rate)
- F&B analytics (top selling items, category revenue, AOV)
- Export to PDF/Excel (placeholder ready)

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+ with extensions: BCMath, Ctype, JSON, Mbstring, PDO MySQL
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- XAMPP (optional, for local development)

### Installation

1. **Clone & Install:**
   ```bash
   cd c:\xampp\htdocs
   composer install
   ```

2. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Edit `.env`:**
   ```ini
   APP_NAME="Warnet Management System"
   DB_DATABASE=warnet_management
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Setup Database:**
   ```bash
   # Create database in MySQL
   mysql -u root -e "CREATE DATABASE warnet_management"
   
   # Run migrations & seeders
   php artisan migrate:fresh --seed
   ```
   
   **Seeded Data:**
   - 1 Owner user: `owner@warnet.local` / `password123`
   - 45 PCs (PC-01 to PC-30 REGULER, VIP-01 to VIP-15)
   - 15 Menu items across 5 categories

5. **Start Server:**
   ```bash
   php artisan serve
   ```
   
   Server runs at: http://127.0.0.1:8000

---

## 🧪 Testing the API

### Option 1: PowerShell Test Script
```bash
.\test-api.ps1
```

### Option 2: Postman
1. Import `postman_collection.json`
2. Run "Login" request (token auto-saved)
3. Test other endpoints with saved token

### Option 3: cURL
See [API_TESTING_GUIDE.md](API_TESTING_GUIDE.md) for complete examples.

**Quick Test:**
```bash
# Login
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"owner@warnet.local","password":"password123"}'

# Response: { "token": "1|abc123..." }

# Get PCs
curl -X GET http://127.0.0.1:8000/api/pc \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📁 Project Structure

```
PPL_Warnet/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php          # Login/logout
│   │   ├── PCController.php            # PC management
│   │   ├── RentalSessionController.php # Session management
│   │   ├── MenuController.php          # Menu CRUD
│   │   ├── OrderController.php         # Order management
│   │   ├── PaymentController.php       # Payment processing
│   │   └── AnalyticsController.php     # Analytics & reports
│   ├── Models/
│   │   ├── PC.php                      # PC model
│   │   ├── RentalSession.php          # Session model
│   │   ├── Menu.php                    # Menu model
│   │   ├── Order.php / OrderItem.php  # Order models
│   │   ├── Payment.php                 # Payment model
│   │   └── RevenueLog.php              # Analytics model
│   └── Services/
│       └── PricingService.php          # Pricing calculation engine
├── database/
│   ├── migrations/                     # 11 migration files
│   └── seeders/                        # UserSeeder, PCSeeder, MenuSeeder
├── routes/
│   └── api.php                         # API routes
├── bootstrap/
│   └── app.php                         # App configuration
├── WARNET_MANAGEMENT_SYSTEM_SPEC.md    # Complete specification
├── QUICK_START.md                       # Quick start guide
├── API_TESTING_GUIDE.md                 # API testing guide
├── IMPLEMENTATION_PROGRESS.md           # Progress tracker
├── postman_collection.json              # Postman collection
├── test-api.ps1                         # PowerShell test script
└── README.md                            # This file
```

---

## 🔑 API Endpoints

### Authentication
- `POST /api/login` - Login & get token
- `POST /api/logout` - Logout

### PC Management
- `GET /api/pc` - List all PCs
- `POST /api/pc` - Create PC
- `GET /api/pc/{id}` - Get PC details
- `PUT /api/pc/{id}` - Update PC
- `POST /api/pc/{id}/control` - Send control command
- `GET /api/pc/{id}/history` - Get PC usage history

### Rental Sessions
- `GET /api/sessions` - List active sessions
- `POST /api/sessions` - Create session (with pricing calculation)
- `GET /api/sessions/{id}` - Get session details
- `PATCH /api/sessions/{id}/extend` - Extend session
- `POST /api/sessions/{id}/complete` - Complete session

### Menu & Orders
- `GET /api/menu` - List menu items
- `POST /api/menu` - Create menu item
- `POST /api/orders` - Create order
- `PATCH /api/orders/{id}/status` - Update order status

### Payments
- `POST /api/payments` - Create payment (QRIS/CASH)
- `POST /api/payments/{id}/confirm` - Confirm CASH payment
- `POST /api/payments/webhook/qris` - QRIS webhook (public)

### Analytics
- `GET /api/analytics/revenue` - Revenue report
- `GET /api/analytics/pc-usage` - PC usage stats
- `GET /api/analytics/f&b` - F&B analytics

**Full Documentation:** [API_TESTING_GUIDE.md](API_TESTING_GUIDE.md)

---

## 💡 Pricing Logic Examples

| Scenario | Tier | Duration | Time | Total |
|----------|------|----------|------|-------|
| Basic | REGULER | 1 jam | 14:00 | **Rp 7,000** |
| Multi-hour | REGULER | 3 jam | 15:00 | **Rp 19,000** (7k + 6k + 6k) |
| VIP | VIP | 4 jam | 11:00 | **Rp 34,000** (10k + 3×8k) |
| Night rate | REGULER | 3 jam | 23:00 | **Rp 16,800** (3×7k × 0.8) |
| Mixed period | REGULER | 3 jam | 21:00 | **Rp 18,200** (split calculation) |

**PricingService Implementation:** [app/Services/PricingService.php](app/Services/PricingService.php)

---

## 🗄️ Database Schema

**11 Tables:**
- `users` - Owner/admin accounts
- `personal_access_tokens` - Sanctum API tokens
- `pcs` - PC units (45 units)
- `rental_sessions` - PC rental history
- `menus` - F&B menu items (15 items)
- `orders` / `order_items` - F&B orders
- `payments` - Payment transactions
- `revenue_logs` - Analytics data

**ERD & Relationships:** See [WARNET_MANAGEMENT_SYSTEM_SPEC.md](WARNET_MANAGEMENT_SYSTEM_SPEC.md)

---

## 🔨 Development

### Artisan Commands
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# List routes
php artisan route:list --path=api

# Re-seed database
php artisan migrate:fresh --seed

# Run tests (coming soon)
php artisan test
```

### Adding New Features
1. Create migration: `php artisan make:migration create_xxx_table`
2. Create model: `php artisan make:model Xxx`
3. Create controller: `php artisan make:controller Api/XxxController --api`
4. Add routes in `routes/api.php`
5. Run migration: `php artisan migrate`

---

## 📋 Roadmap

### Phase 1: Backend API ✅ COMPLETE
- ✅ Database schema & migrations
- ✅ Eloquent models with relationships
- ✅ API controllers with CRUD
- ✅ Sanctum authentication
- ✅ Pricing calculation service
- ✅ API documentation

### Phase 2: Advanced Features (In Progress)
- [ ] QRIS payment provider integration (Midtrans/Xendit)
- [ ] PC monitoring agent/service
- [ ] PDF/Excel report generation
- [ ] WebSocket real-time updates

### Phase 3: Frontend Development
- [ ] Owner dashboard (React/Vue/Blade)
- [ ] Customer mobile app (React Native)
- [ ] Real-time PC monitoring grid
- [ ] Kitchen display system
- [ ] Analytics charts & reports

### Phase 4: Deployment & Testing
- [ ] Unit & feature tests
- [ ] Production server setup
- [ ] SSL & security hardening
- [ ] Backup & monitoring

---

## 🛠️ Tech Stack

- **Backend:** Laravel 12.x
- **Database:** MySQL 8.0
- **Authentication:** Laravel Sanctum (API tokens)
- **ORM:** Eloquent
- **API:** RESTful JSON API
- **Server:** Apache (XAMPP) or Nginx

**Dependencies:**
- `laravel/sanctum` - API authentication
- `guzzlehttp/guzzle` - HTTP client (for QRIS integration)

---

## 🔐 Security

- ✅ API token authentication (Sanctum)
- ✅ CSRF protection
- ✅ Input validation on all endpoints
- ✅ Password hashing (bcrypt)
- ⚠️ QRIS webhook signature verification (TODO)
- ⚠️ Rate limiting (TODO)

---

## 👥 User Roles

### Owner/Operator
- Access all features via API/Dashboard
- PC monitoring & control
- Order management
- Payment processing
- Analytics & reports

### Customer (Mobile App)
- Scan QR to login
- Browse menu & place orders
- View active session
- Make payments
- Track order status

---

## 📝 License

This project is proprietary software for internal use.

---

## 📞 Support

**Developer:** AI Agent  
**Last Updated:** December 15, 2024  
**Status:** ✅ Phase 1 Complete - Ready for Testing

---

## 🎯 Quick Links

- [System Specification](WARNET_MANAGEMENT_SYSTEM_SPEC.md)
- [Quick Start Guide](QUICK_START.md)
- [API Testing Guide](API_TESTING_GUIDE.md)
- [Implementation Progress](IMPLEMENTATION_PROGRESS.md)
- [Postman Collection](postman_collection.json)

---

**Ready to Start?**
1. Run `php artisan serve`
2. Execute `.\test-api.ps1`
3. Import Postman collection
4. Start building! 🚀

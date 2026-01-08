# 📘 ANALISIS SISTEM LENGKAP - Warnet Management System

**Generated:** January 8, 2026  
**System Version:** Laravel 12 + MySQL + Sanctum API  
**Total PC:** 45 units (30 REGULER + 15 VIP)

---

## 🎯 OVERVIEW SISTEM

Sistem ini adalah **Integrated PC Rental & F&B Management System** untuk warnet dengan fitur:

1. **PC Rental Management** - Sistem rental PC dengan pricing kompleks
2. **F&B Orders** - Sistem pemesanan makanan/minuman terintegrasi dengan rental
3. **Payment System** - QRIS & Cash payment gateway
4. **Real-time Monitoring** - WebSocket broadcasting untuk update real-time
5. **Analytics & Reporting** - Revenue analytics, PC usage stats, F&B reports

---

## 📂 ARSITEKTUR SISTEM

### **Struktur MVC (Model-View-Controller)**

```
app/
├── Http/Controllers/Api/          # API Controllers
│   ├── PCController.php           # Mengelola PC (CRUD + control)
│   ├── RentalSessionController.php # Mengelola sesi rental (CORE LOGIC)
│   ├── OrderController.php        # Mengelola F&B orders
│   ├── PaymentController.php      # Mengelola pembayaran
│   ├── AnalyticsController.php    # Analytics & reporting
│   └── AuthController.php         # Authentication (Sanctum)
│
├── Models/                        # Eloquent Models
│   ├── PC.php                    # Model PC dengan relationships
│   ├── RentalSession.php         # Model rental session
│   ├── Order.php                 # Model F&B order
│   ├── OrderItem.php             # Model item dalam order
│   ├── Payment.php               # Model payment
│   ├── Menu.php                  # Model menu F&B
│   └── User.php                  # Model user
│
├── Services/                      # Business Logic Services
│   ├── PricingService.php        # Perhitungan harga rental (KOMPLEKS)
│   ├── RevenueService.php        # Revenue calculations & analytics
│   └── PaymentService.php        # Payment gateway integration
│
└── Events/                        # Broadcasting Events
    ├── PcStatusChanged.php       # Event PC status berubah
    ├── SessionUpdated.php        # Event session update
    └── OrderStatusChanged.php    # Event order status berubah
```

---

## 🔑 KOMPONEN UTAMA SISTEM

### **1. PCController** - Manajemen PC

**File:** `app/Http/Controllers/Api/PCController.php`

**Fungsi Utama:**
- `index()` - List semua PC dengan status real-time
- `store()` - Tambah PC baru
- `show()` - Detail PC + history
- `update()` - Update status PC (monitoring: CPU, RAM, Disk)
- `control()` - Kirim perintah ke PC (shutdown, restart, lock)
- `history()` - Riwayat penggunaan PC

**Status PC:**
- `ONLINE` - PC hidup dan siap digunakan
- `OFFLINE` - PC mati
- `IDLE` - PC online tapi tidak digunakan
- `IN_USE` - PC sedang digunakan (ada rental session aktif)
- `MAINTENANCE` - PC dalam perbaikan

**Relasi:**
- `currentSession` - Sesi rental yang sedang aktif (BelongsTo)
- `rentalSessions` - History semua sesi rental (HasMany)

---

### **2. RentalSessionController** - CORE BUSINESS LOGIC

**File:** `app/Http/Controllers/Api/RentalSessionController.php`

**Ini adalah controller TERPENTING dalam sistem!**

#### **Method Utama:**

##### **a. `store()` - Membuat Rental Session Baru**

**Alur:**
1. Validasi input (PC ID, duration, tier)
2. Check PC availability (conflict check)
3. **Hitung harga** menggunakan `PricingService`
4. Create session di database
5. Update PC status → `IN_USE`
6. Set `current_session_id` di PC
7. Broadcast update via WebSocket

**Perhitungan Harga:**
```php
// Contoh: REGULER, 3 jam (180 menit)
PricingService::calculatePrice('REGULER', now(), 180)

// Hasil:
// Jam 1: Rp 7,000 (base rate)
// Jam 2: Rp 6,000 (tier 2)
// Jam 3: Rp 6,000 (tier 2)
// TOTAL: Rp 19,000
```

##### **b. `pause()` - Pause Session**

**Use Case:** Customer mau istirahat, waktu tidak berjalan

**Mekanisme:**
1. Terima `remaining_seconds` dari frontend (countdown timer yang exact)
2. Save `remaining_seconds` ke database
3. Set `status = PAUSED`
4. Catat `paused_at` timestamp

**Mengapa save remaining_seconds?**
- Frontend punya countdown timer real-time yang akurat
- Backend save exact value dari frontend
- Ini mencegah loss of precision

##### **c. `resume()` - Resume Session**

**Mekanisme CRITICAL:**
1. Hitung berapa lama di-pause
2. **ADJUST `start_time`** agar sisa waktu tetap akurat
3. Reset `paused_duration = 0`
4. Clear `remaining_seconds`

**Mengapa adjust start_time?**

Problem tanpa adjust:
```
Original: start_time = 10:00, duration = 60 min
After 20 min → remaining = 40 min
Pause 10 min → Resume at 10:30
Frontend calculate: elapsed = 10:30 - 10:00 = 30 min ❌ SALAH!
```

Solution dengan adjust:
```
Original: start_time = 10:00, duration = 60 min
After 20 min → remaining = 40 min, save remaining_seconds = 2400
Pause 10 min → Resume at 10:30
Adjust start_time = 10:30 - 20min = 10:10
Frontend calculate: elapsed = 10:30 - 10:10 = 20 min ✅ BENAR!
```

##### **d. `complete()` - Selesaikan Session**

**Alur Kompleks:**
1. Hitung paused duration terakhir (jika ada)
2. **Ambil semua F&B orders yang PENDING**
3. Mark semua orders → `PAID`
4. Set session → `COMPLETED`
5. Update PC → `IDLE` dan clear `current_session_id`
6. Return **financial summary**

**Financial Summary:**
```json
{
  "rental_cost": 19000,      // Biaya rental PC
  "orders_cost": 25000,      // Total F&B orders
  "grand_total": 44000       // Total yang harus dibayar
}
```

##### **e. `extend()` - Perpanjang Durasi**

**Mekanisme:**
- Customer bisa extend kapan saja
- System **recalculate** harga dari awal dengan total durasi baru
- Night discount akan recalculated jika crossing boundary

```php
// Original: 1 jam REGULER = Rp 7,000
// Extend 1 jam → Total 2 jam
// Recalculate:
//   Jam 1: Rp 7,000
//   Jam 2: Rp 6,000  (tier 2 discount!)
//   TOTAL: Rp 13,000 (bukan Rp 14,000)
```

##### **f. `ensureDataConsistency()` - Self-Healing**

**Dipanggil otomatis sebelum `index()`**

**Fix masalah:**
1. PC `IN_USE` tapi `current_session_id = null` → Set `IDLE`
2. PC `IN_USE` tapi session sudah `COMPLETED` → Set `IDLE`
3. PC `IN_USE` tapi session tidak ada (deleted) → Set `IDLE`

---

### **3. PricingService** - Perhitungan Harga Kompleks

**File:** `app/Services/PricingService.php`

#### **Struktur Harga:**

**1. Base Rates (Jam 1):**
- REGULER: Rp 7,000/jam
- VIP: Rp 10,000/jam

**2. Tier 2+ (Jam 2 dst):**
- REGULER: Rp 6,000/jam (diskon Rp 1,000)
- VIP: Rp 8,000/jam (diskon Rp 2,000)

**3. Night Discount (22:00 - 05:00):**
- 20% OFF dari base rate
- **TIDAK ada tier pricing** saat night (flat rate)

#### **Method Utama:**

##### **`calculatePrice($tier, $startTime, $durationMinutes)`**

**Return:**
```php
[
  'total' => 18200,
  'breakdown' => [
    [
      'period' => '21:00 - 22:00',
      'type' => 'DAY',
      'hours' => 1,
      'base_cost' => 7000,
      'discount' => 0,
      'cost' => 7000
    ],
    [
      'period' => '22:00 - 23:30',
      'type' => 'NIGHT',
      'hours' => 2,
      'base_cost' => 14000,
      'discount' => 2800,
      'cost' => 11200
    ]
  ],
  'currency' => 'IDR'
]
```

##### **`splitIntoPeriods($start, $end)`**

**Fungsi:** Split session menjadi periods (DAY/NIGHT)

**Contoh:**
```
Session: 21:00 - 23:30
Split menjadi:
  Period 1: 21:00 - 22:00 (DAY)
  Period 2: 22:00 - 23:30 (NIGHT)
```

##### **`calculatePeriodCost($tier, $period)`**

**Logika DAY period:**
- Jam 1: base rate
- Jam 2+: tier 2 rate

**Logika NIGHT period:**
- Semua jam: base rate × hours
- Discount 20%
- **NO tier pricing**

---

### **4. OrderController** - F&B Management

**File:** `app/Http/Controllers/Api/OrderController.php`

#### **Fungsi:**

##### **`store()` - Create Order**

**Alur:**
1. Validasi items (menu_id + quantity)
2. Hitung subtotal per item
3. Create order dengan status `RECEIVED`
4. Create order_items
5. Broadcast update

**Status Order:**
- `RECEIVED` - Order baru diterima
- `PREPARING` - Sedang dimasak/dibuat
- `READY` - Siap diantar
- `DELIVERED` - Sudah diantar ke customer
- `COMPLETED` - (sama dengan DELIVERED untuk revenue)

##### **`index()` - List Orders**

**Mode:**
1. **Default Mode** - Show ALL orders (untuk Orders Management)
2. **Analytics Mode** (`?analytics_only=true`) - Show PAID/COMPLETED only

**Mengapa 2 mode?**
- Orders Management perlu lihat PENDING orders
- Analytics hanya hitung COMPLETED orders (revenue)

#### **Integrasi dengan Session:**

Order bisa **linked** ke rental session:
```php
[
  'rental_session_id' => 123,  // Optional
  'table_id' => 'PC-01',
  'items' => [...]
]
```

Benefit:
- Saat session complete, otomatis mark orders as PAID
- Financial summary include F&B cost

---

### **5. RevenueService** - Revenue Analytics

**File:** `app/Services/RevenueService.php`

**Single Source of Truth untuk revenue calculation**

#### **Method Utama:**

##### **`getPaidOrders($startDate, $endDate)`**

**Critical:** Revenue based on `order_status = 'COMPLETED'`

**BUKAN** berdasarkan `payment_status = 'PAID'`

**Mengapa?**
- Order COMPLETED = order delivered = revenue recognized
- Payment bisa PENDING tapi order sudah delivered

##### **`getFbRevenueByDateAndHour()`**

Return revenue per tanggal dan per jam:
```php
[
  ['date' => '2026-01-08', 'hour' => 14, 'amount' => 125000, 'count' => 5],
  ['date' => '2026-01-08', 'hour' => 15, 'amount' => 89000, 'count' => 3],
  ...
]
```

Digunakan oleh AnalyticsController untuk grafik.

##### **`getTopFbItems($limit)`**

Top selling items:
```php
[
  ['name' => 'Nasi Goreng', 'total_quantity' => 45, 'total_revenue' => 450000],
  ['name' => 'Es Teh Manis', 'total_quantity' => 78, 'total_revenue' => 234000],
  ...
]
```

---

### **6. AnalyticsController** - Reporting

**File:** `app/Http/Controllers/Api/AnalyticsController.php`

#### **Method:**

##### **`revenue()` - Revenue Report**

**Data:**
- PC rental revenue (by tier: REGULER vs VIP)
- F&B revenue (menggunakan RevenueService)
- Breakdown by date & hour

##### **`pcUsage()` - PC Usage Stats**

**Data:**
- Most used PCs (top 10)
- Peak hours (jam tersibuk)
- Occupancy rate (% utilization)
- Average session duration

##### **`fAndB()` - F&B Analytics**

**Data:**
- Top selling items
- Revenue by category
- Average order value (AOV)
- Total orders & revenue

---

## 🔄 DATA FLOW

### **Flow 1: Membuat Rental Session**

```
1. POST /api/sessions
   Body: { pc_id, duration, tier, user_name }

2. RentalSessionController::store()
   ├─ Validate input
   ├─ Check PC availability
   └─ PricingService::calculatePrice()
      ├─ Split into periods (DAY/NIGHT)
      ├─ Calculate per period
      └─ Return total + breakdown

3. Create RentalSession
   ├─ status = ACTIVE
   ├─ start_time = now()
   └─ total_cost = calculated price

4. Update PC
   ├─ status = IN_USE
   └─ current_session_id = session.id

5. Broadcast SessionUpdated event
   └─ WebSocket push to all clients

6. Return response
   └─ session + pricing_breakdown
```

### **Flow 2: Order F&B During Session**

```
1. POST /api/orders
   Body: { 
     rental_session_id: 123,
     table_id: 'PC-01',
     items: [
       { menu_id: 5, quantity: 2 },
       { menu_id: 12, quantity: 1 }
     ]
   }

2. OrderController::store()
   ├─ Calculate subtotal per item
   ├─ Calculate total
   └─ Create Order (status = RECEIVED)

3. Create OrderItems
   └─ Link to order

4. Broadcast OrderStatusChanged
   └─ Kitchen display updates

5. Return order dengan items
```

### **Flow 3: Complete Session + F&B Payment**

```
1. POST /api/sessions/{id}/complete
   Body: { payment_method: 'CASH' }

2. RentalSessionController::complete()
   ├─ Get unpaid orders
   │  └─ orders where payment_status = PENDING
   │
   ├─ Calculate orders total
   │
   ├─ Mark all orders as PAID
   │  └─ UPDATE orders SET payment_status = PAID
   │
   ├─ Complete session
   │  ├─ status = COMPLETED
   │  └─ end_time = now()
   │
   └─ Update PC
      ├─ status = IDLE
      └─ current_session_id = null

3. Return financial summary
   └─ { rental_cost, orders_cost, grand_total }
```

---

## 🎨 REAL-TIME FEATURES (WebSocket)

### **Broadcasting Channels:**

**1. `pc-monitoring` Channel**
- Event: `pc.status.changed`
- Trigger: PC status update, resource monitoring
- Listeners: PC Monitoring Dashboard

**2. `sessions` Channel**
- Event: `session.updated`
- Trigger: Session create/update/pause/resume/complete
- Listeners: Sessions Management Page, PC Cards

**3. `orders` Channel**
- Event: `order.status.changed`
- Trigger: Order status update
- Listeners: Kitchen Display, Orders Management

### **Broadcasting Stack:**

- **Laravel Reverb** (WebSocket server)
- **Pusher Protocol** compatible
- **Broadcasting Driver:** reverb (config/broadcasting.php)

---

## 💾 DATABASE SCHEMA

### **Tabel Utama:**

#### **`pcs` Table**
```sql
- id (PK)
- pc_code (unique, e.g. "PC-01", "VIP-15")
- location (e.g. "Lantai 1")
- position (e.g. "Row A-1")
- type (REGULER | VIP)
- status (ONLINE | OFFLINE | IDLE | IN_USE | MAINTENANCE)
- current_session_id (FK → rental_sessions.id)
- cpu_usage (0-100)
- ram_usage (0-100)
- disk_usage (0-100)
- current_process (JSON)
- last_heartbeat (timestamp)
```

#### **`rental_sessions` Table**
```sql
- id (PK)
- pc_id (FK → pcs.id)
- start_time
- duration (minutes)
- end_time (nullable)
- user_name (nullable)
- tier (REGULER | VIP)
- status (ACTIVE | PAUSED | COMPLETED)
- total_cost (decimal)
- paused_at (nullable)
- paused_duration (minutes, default 0)
- remaining_seconds (nullable) - untuk pause/resume
- payment_method (QRIS | CASH)
```

#### **`orders` Table**
```sql
- id (PK)
- table_id (e.g. "PC-01")
- rental_session_id (FK → rental_sessions.id, nullable)
- subtotal
- tax
- discount
- total
- payment_method (QRIS | CASH)
- payment_status (PENDING | PAID | FAILED)
- order_status (RECEIVED | PREPARING | READY | DELIVERED)
- notes (text)
- delivered_at (timestamp)
```

#### **`order_items` Table**
```sql
- id (PK)
- order_id (FK → orders.id)
- menu_id (FK → menus.id)
- quantity
- unit_price
- subtotal
```

#### **`menus` Table**
```sql
- id (PK)
- name
- category (MAKANAN_BERAT | MAKANAN_RINGAN | MINUMAN_DINGIN | MINUMAN_PANAS | MINUMAN_SACHET)
- price
- description
- image_url
- available (boolean)
```

### **Relationships:**

```
PC ──(1:1)── RentalSession [current session]
PC ──(1:M)── RentalSession [history]

RentalSession ──(1:M)── Order [F&B orders during session]

Order ──(1:M)── OrderItem
OrderItem ──(M:1)── Menu
```

---

## 🔐 SECURITY & AUTHENTICATION

### **Laravel Sanctum (API Token)**

**Public Endpoints** (tanpa auth):
- GET `/api/pcs` - monitoring
- GET `/api/menu` - menu list
- POST `/api/sessions` - create rental
- POST `/api/orders` - create order
- Analytics endpoints

**Protected Endpoints** (require token):
- PC management (create/update/delete)
- Session delete
- User management

**Login:**
```
POST /api/login
Body: { username, password }
Response: { token: "..." }

Header: Authorization: Bearer {token}
```

---

## ⚡ PERFORMANCE OPTIMIZATIONS

### **1. Caching Strategy**

```php
// Active sessions cache (5 seconds)
Cache::remember('active_sessions', 5, function() {
  return RentalSession::where('status', '!=', 'COMPLETED')
    ->with(['pc:id,pc_code,type', 'orders'])
    ->select([...]) // Select only needed columns
    ->get();
});
```

**Mengapa 5 detik?**
- Frontend polling setiap beberapa detik
- Balance antara real-time vs database load

### **2. Eager Loading**

```php
// Avoid N+1 queries
PC::with('currentSession')->get();
Order::with('orderItems.menu')->get();
```

### **3. Database Indexes**

Migration `add_indexes_for_performance.php`:
- Index pada `pcs.status`
- Index pada `rental_sessions.status`
- Index pada `orders.order_status`
- Index pada `orders.payment_status`

---

## 🐛 TROUBLESHOOTING COMMON ISSUES

### **Issue 1: PC Stuck in IN_USE**

**Symptom:** PC status IN_USE tapi tidak ada session aktif

**Cause:**
- Session deleted manually
- Session completed tapi PC tidak update
- Bug/crash saat complete

**Solution:**
```php
// Self-healing di RentalSessionController::ensureDataConsistency()
PC::where('status', 'IN_USE')
  ->whereNull('current_session_id')
  ->update(['status' => 'IDLE']);
```

### **Issue 2: Pause/Resume Time Tidak Akurat**

**Symptom:** Setelah resume, remaining time tidak sesuai

**Cause:** Rounding error di paused_duration calculation

**Solution:** Adjust start_time instead of using paused_duration
```php
// Resume: adjust start_time
$elapsedSeconds = $durationSeconds - $remainingSeconds;
$newStartTime = now()->subSeconds($elapsedSeconds);
$session->update(['start_time' => $newStartTime]);
```

### **Issue 3: Revenue Tidak Match dengan Orders**

**Symptom:** Analytics revenue berbeda dengan Orders Management

**Cause:** Berbeda filter (PAID vs COMPLETED)

**Solution:** Gunakan RevenueService sebagai single source of truth
```php
// Revenue based on order_status = COMPLETED
RevenueService::getPaidOrders()
```

---

## 📊 CONTOH SKENARIO PENGGUNAAN

### **Skenario 1: Customer Rental + Order F&B**

```
09:00 - Customer datang, pilih PC-05 (REGULER)
        → POST /api/sessions
        { pc_id: 5, duration: 120, tier: 'REGULER' }
        
        Pricing: 2 jam = Rp 7,000 + Rp 6,000 = Rp 13,000
        PC-05 status → IN_USE

09:30 - Customer pesan makanan
        → POST /api/orders
        { 
          rental_session_id: 123,
          items: [
            { menu_id: 1, quantity: 1 }, // Nasi Goreng Rp 15,000
            { menu_id: 5, quantity: 2 }  // Es Teh Rp 5,000 × 2
          ]
        }
        
        Order total: Rp 25,000
        Status: RECEIVED → PREPARING → READY → DELIVERED

11:00 - Customer selesai
        → POST /api/sessions/123/complete
        { payment_method: 'CASH' }
        
        Financial Summary:
        - Rental: Rp 13,000
        - F&B: Rp 25,000
        - TOTAL: Rp 38,000
        
        PC-05 status → IDLE
```

### **Skenario 2: Crossing Night Boundary**

```
21:30 - Customer rental 3 jam (180 menit)
        → POST /api/sessions
        { pc_id: 10, duration: 180, tier: 'REGULER' }
        
        Pricing breakdown:
        
        Period 1 (21:30 - 22:00): 1 jam DAY
        - Tier 1: Rp 7,000
        
        Period 2 (22:00 - 00:30): 3 jam NIGHT
        - Base: 3 × Rp 7,000 = Rp 21,000
        - Discount 20%: Rp 4,200
        - Subtotal: Rp 16,800
        
        TOTAL: Rp 7,000 + Rp 16,800 = Rp 23,800
```

### **Skenario 3: Pause & Resume**

```
14:00 - Customer rental 2 jam
        Duration: 120 menit = 7200 detik
        
14:40 - Customer pause (40 menit telah berlalu)
        Remaining: 80 menit = 4800 detik
        → POST /api/sessions/456/pause
        { remaining_seconds: 4800 }
        
        Database save: remaining_seconds = 4800

15:00 - Customer resume (pause 20 menit)
        → POST /api/sessions/456/resume
        
        Adjust start_time:
        - Elapsed: 40 menit = 2400 detik
        - New start_time = 15:00 - 40 min = 14:20
        
        Frontend calculate:
        - Elapsed = 15:00 - 14:20 = 40 min ✅
        - Remaining = 120 - 40 = 80 min ✅
```

---

## 🚀 DEPLOYMENT CHECKLIST

### **Environment Setup**

1. **PHP Requirements:**
   - PHP 8.2+
   - Extensions: BCMath, Ctype, JSON, Mbstring, PDO MySQL

2. **Database:**
   - MySQL 5.7+ atau MariaDB 10.3+
   - Create database: `warnet_management`

3. **Composer Install:**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

4. **Environment Configuration:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=...
   
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=warnet_management
   DB_USERNAME=...
   DB_PASSWORD=...
   
   BROADCAST_DRIVER=reverb
   QUEUE_CONNECTION=database
   ```

5. **Run Migrations & Seeders:**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```

6. **Start Services:**
   ```bash
   # Application
   php artisan serve
   
   # WebSocket (Reverb)
   php artisan reverb:start
   
   # Queue Worker
   php artisan queue:listen
   ```

---

## 📞 SUPPORT & MAINTENANCE

### **Logging**

Log files: `storage/logs/laravel.log`

Key logs:
- Pause/Resume operations
- Pricing calculations
- Self-healing actions

### **Monitoring**

Monitor:
- Database connections
- WebSocket connections
- Queue jobs
- Cache hit rate

### **Backup Strategy**

Regular backup:
- Database (daily)
- Uploaded files (if any)
- Environment config

---

## 📝 KESIMPULAN

Sistem Warnet Management ini adalah sistem yang **kompleks** dan **well-architected** dengan fitur:

✅ **Business Logic yang Solid:**
- Tier-based pricing
- Night discount
- Mixed period calculation
- Self-healing mechanism

✅ **Integration yang Baik:**
- PC Rental + F&B terintegrasi
- Payment unified system
- Real-time broadcasting

✅ **Performance Optimized:**
- Caching strategy
- Eager loading
- Database indexes

✅ **Code Quality:**
- Clean separation of concerns
- Service pattern untuk business logic
- Event-driven architecture

**Sistem ini siap production** dengan beberapa catatan:
- Payment gateway (QRIS) perlu integrasi real provider
- PC Control agent perlu implementasi
- Export PDF/Excel perlu implementasi

---

**End of Analysis Document**

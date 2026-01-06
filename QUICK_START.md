# 🚀 Warnet Management System - Quick Start Guide

## ✅ Installation Complete!

Laravel development server is running at: **http://127.0.0.1:8000**

---

## 📊 Database Status

✅ **45 PC Units** (30 Reguler + 15 VIP)
✅ **15 Menu Items** (5 categories)
✅ **1 Owner User** ready for login

---

## 🔑 Login Credentials

**Owner Account:**
- Email: `owner@warnet.local`
- Password: `password123`

---

## 🔌 API Endpoints

### Authentication
```
POST   /api/login              - Login & get API token
POST   /api/logout             - Logout (revoke token)
GET    /api/user               - Get authenticated user
```

### PC Management
```
GET    /api/pc                 - List all PCs
POST   /api/pc                 - Create new PC
GET    /api/pc/{id}            - Get PC details
PUT    /api/pc/{id}            - Update PC
DELETE /api/pc/{id}            - Delete PC
POST   /api/pc/{id}/control    - Send control command
GET    /api/pc/{id}/history    - Get PC usage history
```

### Rental Sessions
```
GET    /api/sessions                  - List active sessions
POST   /api/sessions                  - Create new session
GET    /api/sessions/{id}             - Get session details
PUT    /api/sessions/{id}             - Update session
DELETE /api/sessions/{id}             - Delete session
POST   /api/sessions/{id}/complete    - Complete session
PATCH  /api/sessions/{id}/extend      - Extend session duration
```

### Menu Management
```
GET    /api/menu               - List all menu items
POST   /api/menu               - Create menu item
GET    /api/menu/{id}          - Get menu details
PUT    /api/menu/{id}          - Update menu
DELETE /api/menu/{id}          - Delete menu
```

### Orders (F&B)
```
GET    /api/orders                  - List orders
POST   /api/orders                  - Create new order
GET    /api/orders/{id}             - Get order details
PUT    /api/orders/{id}             - Update order
DELETE /api/orders/{id}             - Delete order
PATCH  /api/orders/{id}/status      - Update order status
```

### Payments
```
GET    /api/payments                    - List payments
POST   /api/payments                    - Create payment
GET    /api/payments/{id}               - Get payment details
PUT    /api/payments/{id}               - Update payment
DELETE /api/payments/{id}               - Delete payment
POST   /api/payments/{id}/confirm       - Confirm CASH payment
POST   /api/payments/webhook/qris       - QRIS webhook (public)
```

### Analytics
```
GET    /api/analytics/revenue       - Revenue analytics
GET    /api/analytics/pc-usage      - PC usage stats
GET    /api/analytics/f&b           - F&B analytics
GET    /api/analytics/export        - Export report
```

---

## 🧪 Testing with cURL

### 1. Login & Get Token
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "owner@warnet.local",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "message": "Login successful",
  "user": {...},
  "token": "1|abc123..."
}
```

### 2. Get All PCs
```bash
curl -X GET http://127.0.0.1:8000/api/pc \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 3. Get All Menu Items
```bash
curl -X GET http://127.0.0.1:8000/api/menu \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 4. Create Rental Session
```bash
curl -X POST http://127.0.0.1:8000/api/sessions \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "pc_id": 1,
    "duration": 120,
    "user_name": "Customer 1",
    "tier": "REGULER"
  }'
```

### 5. Create F&B Order
```bash
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "table_id": "PC-1",
    "items": [
      {"menu_id": 1, "quantity": 1},
      {"menu_id": 7, "quantity": 2}
    ],
    "payment_method": "CASH"
  }'
```

---

## 🛠️ Development Commands

```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Clear cache
php artisan cache:clear
php artisan config:clear

# Generate API documentation
php artisan route:list

# Run tests (coming soon)
php artisan test
```

---

## 📱 Mobile App Integration

Mobile app menggunakan **Sanctum Token Authentication**:

1. User scan QR code → redirect ke login/register
2. Login → receive API token
3. Simpan token di localStorage/SecureStorage
4. Kirim token di header setiap request:
   ```
   Authorization: Bearer {token}
   ```

---

## 🔐 Security Notes

- ✅ All API routes protected with `auth:sanctum`
- ✅ CSRF protection enabled
- ✅ Input validation on all endpoints
- ✅ Password hashing with bcrypt
- ⚠️ QRIS webhook needs signature verification (TODO)

---

## 📊 Database Schema Quick Reference

**Tables:**
- `pcs` - PC units
- `rental_sessions` - PC rental history
- `menus` - F&B menu items
- `orders` - F&B orders
- `order_items` - Order line items
- `payments` - Payment transactions
- `revenue_logs` - Revenue analytics
- `users` - Owner/admin users
- `personal_access_tokens` - API tokens

---

## 🎯 Next Development Steps

- [ ] Implement Pricing Service (complex pricing logic)
- [ ] Add WebSocket for real-time updates
- [ ] Implement QRIS provider integration
- [ ] Add PDF/Excel export for reports
- [ ] Create frontend dashboard (React/Vue)
- [ ] Setup automated testing
- [ ] Deploy to production server

---

## 📞 Support

**Server Status:** ✅ Running at http://127.0.0.1:8000
**API Documentation:** Available via route:list command

---

**Last Updated:** December 15, 2025
**Status:** ✅ Ready for Testing & Development

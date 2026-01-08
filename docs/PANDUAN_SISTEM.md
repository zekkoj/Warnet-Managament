# 🎮 PANDUAN MEMAHAMI SISTEM WARNET - Penjelasan Sederhana

## 🎯 APA ITU SISTEM INI?

Sistem ini adalah **aplikasi manajemen warnet** yang menangani:
1. **Rental PC** - Customer sewa PC per jam
2. **Pesan Makanan/Minuman** - Customer bisa order F&B sambil main
3. **Pembayaran** - Bisa bayar pakai QRIS atau Cash
4. **Monitoring** - Owner bisa pantau semua PC real-time
5. **Laporan** - Ada laporan pendapatan dan statistik

---

## 📁 STRUKTUR FILE PENTING

```
app/Http/Controllers/Api/
├── PCController.php              → Atur PC (45 unit)
├── RentalSessionController.php   → Atur rental (INTI SISTEM!)
├── OrderController.php           → Atur pesanan makanan
├── PaymentController.php         → Atur pembayaran
└── AnalyticsController.php       → Laporan & statistik

app/Services/
├── PricingService.php            → Hitung harga rental (kompleks!)
└── RevenueService.php            → Hitung pendapatan
```

---

## 💰 CARA KERJA HARGA RENTAL

### Harga Dasar:
- **PC REGULER:** Rp 7,000/jam (jam pertama), Rp 6,000/jam (jam kedua dst)
- **PC VIP:** Rp 10,000/jam (jam pertama), Rp 8,000/jam (jam kedua dst)

### Diskon Malam (22:00 - 05:00):
- Dapat **diskon 20%** kalau rental di jam malam
- Tapi **TIDAK dapat** diskon tier (semua jam harga sama)

### Contoh Perhitungan:

**Contoh 1: Rental Siang (PC REGULER, 3 jam)**
```
Jam 1: Rp 7,000  ← harga penuh
Jam 2: Rp 6,000  ← diskon tier
Jam 3: Rp 6,000  ← diskon tier
─────────────────
TOTAL: Rp 19,000
```

**Contoh 2: Rental Malam (PC REGULER, 3 jam, 22:00-01:00)**
```
Harga Normal: 3 jam × Rp 7,000 = Rp 21,000
Diskon 20%: Rp 4,200
─────────────────────────────────────────
TOTAL: Rp 16,800  ← lebih murah!
```

**Contoh 3: Melintas Batas Waktu (21:00-23:30)**
```
21:00-22:00 (1 jam SIANG):
  Rp 7,000

22:00-23:30 (2 jam MALAM):
  Rp 14,000 - (20% × Rp 14,000) = Rp 11,200
─────────────────────────────────────────
TOTAL: Rp 18,200
```

---

## 🔄 ALUR CUSTOMER MENGGUNAKAN SISTEM

### 1. Customer Datang & Pilih PC

```
Staff: "Mau PC biasa atau VIP? Berapa jam?"
Customer: "PC biasa, 2 jam"

➡️ Staff input di sistem:
   - PC: PC-05
   - Durasi: 120 menit
   - Tier: REGULER

💻 Sistem otomatis:
   ✅ Hitung harga: Rp 13,000
   ✅ PC-05 jadi status "IN_USE"
   ✅ Timer mulai jalan
```

### 2. Customer Pesan Makanan (Opsional)

```
Customer: "Pesan nasi goreng sama es teh 2"

➡️ Staff input pesanan:
   - Nasi Goreng: Rp 15,000 × 1 = Rp 15,000
   - Es Teh: Rp 5,000 × 2 = Rp 10,000
   - TOTAL MAKANAN: Rp 25,000

💻 Sistem:
   ✅ Order masuk ke dapur
   ✅ Status: Diterima → Dimasak → Siap → Diantar
```

### 3. Customer Selesai & Bayar

```
Customer: "Udah selesai, mau bayar"

➡️ Staff klik "Complete Session"

💻 Sistem hitung otomatis:
   ━━━━━━━━━━━━━━━━━━━━━━━━
   Biaya Rental PC:  Rp 13,000
   Biaya Makanan:    Rp 25,000
   ━━━━━━━━━━━━━━━━━━━━━━━━
   TOTAL BAYAR:      Rp 38,000
   ━━━━━━━━━━━━━━━━━━━━━━━━

Customer bayar → PC-05 jadi "IDLE" → Siap customer berikutnya
```

---

## ⏸️ FITUR PAUSE & RESUME

Customer bisa **pause** waktu rental (misal mau makan/istirahat).

### Cara Kerja:

```
14:00 - Mulai rental 2 jam (120 menit)
        Timer: 120:00 ... 119:59 ... 119:58 ...

14:40 - Customer klik "Pause" (sudah jalan 40 menit)
        ⏸️ Timer berhenti di 80:00
        💾 Sistem save: "sisa waktu = 80 menit"

15:00 - Customer klik "Resume" (pause 20 menit)
        ▶️ Timer lanjut dari 80:00
        ⏰ Waktu pause TIDAK dihitung (gratis!)

16:00 - Timer habis (total jalan 80 menit)
        ✅ Selesai tepat 2 jam (tidak termasuk pause)
```

### Kenapa Akurat?

Sistem **menyimpan sisa waktu exact** dari timer frontend:
- Saat pause: save `remaining_seconds = 4800` (80 menit)
- Saat resume: adjust `start_time` agar hitungan tetap benar
- Hasilnya: **tidak ada waktu yang hilang!**

---

## 🍔 INTEGRASI RENTAL + MAKANAN

### Kenapa Terintegrasi?

Saat customer rental PC, dia bisa pesan makanan berkali-kali:

```
Session #123 (PC-05, 3 jam)
│
├─ Order #1 (14:30): Nasi Goreng = Rp 15,000
├─ Order #2 (15:00): Es Teh × 2 = Rp 10,000
└─ Order #3 (16:00): Indomie = Rp 8,000

Total Makanan: Rp 33,000
```

**Benefit:** Saat selesai, sistem otomatis hitung SEMUA pesanan!

```
NOTA PEMBAYARAN
─────────────────────
Rental PC:   Rp 19,000
Order #1:    Rp 15,000
Order #2:    Rp 10,000
Order #3:    Rp  8,000
─────────────────────
TOTAL:       Rp 52,000
```

---

## 📊 FITUR MONITORING REAL-TIME

### 1. Monitoring PC

Owner bisa lihat **real-time** status semua PC:

```
┌─────────────────────────────────────────┐
│  PC-01  [IN_USE]   Timer: 45:23        │
│  PC-02  [IDLE]     Siap digunakan      │
│  PC-03  [IN_USE]   Timer: 120:00       │
│  ...                                    │
│  VIP-01 [IN_USE]   Timer: 15:45        │
│  VIP-02 [MAINTENANCE] Dalam perbaikan  │
└─────────────────────────────────────────┘
```

**Update otomatis tanpa refresh!** (pakai WebSocket)

### 2. Monitoring Resource PC

Setiap PC bisa kirim data monitoring:
- **CPU Usage:** 45%
- **RAM Usage:** 78%
- **Disk Usage:** 62%
- **Process:** Chrome, Steam, Discord

### 3. Monitoring Orders (Dapur)

Dapur punya layar khusus yang update real-time:

```
┌─────────────────────────────────────────┐
│  ORDER BARU!                            │
│  ────────────────────                   │
│  PC-05 (14:30)                          │
│  • Nasi Goreng × 1                      │
│  • Es Teh × 2                           │
│                                         │
│  [TERIMA] [MASAK] [SIAP] [ANTAR]       │
└─────────────────────────────────────────┘
```

Staff tinggal klik tombol, status langsung update di semua layar.

---

## 📈 LAPORAN & ANALYTICS

### 1. Laporan Pendapatan

```
PENDAPATAN HARI INI (8 Jan 2026)
═══════════════════════════════════════
Rental PC:         Rp 1,250,000  (60%)
Makanan/Minuman:   Rp   835,000  (40%)
───────────────────────────────────────
TOTAL:             Rp 2,085,000

Grafik per jam:
14:00 ████████░░ Rp 125,000
15:00 ██████████ Rp 180,000
16:00 ███████░░░ Rp 145,000
...
```

### 2. Statistik PC

```
PC PALING LARIS
═══════════════════════════════════════
1. PC-08   45x digunakan  (120 jam)
2. PC-15   42x digunakan  (115 jam)
3. VIP-01  38x digunakan  (98 jam)

JAM TERSIBUK
═══════════════════════════════════════
1. 15:00-16:00   38 sesi
2. 14:00-15:00   35 sesi
3. 20:00-21:00   32 sesi
```

### 3. Top Selling Makanan

```
MENU TERLARIS BULAN INI
═══════════════════════════════════════
1. Nasi Goreng      125× terjual  Rp 1,875,000
2. Indomie Goreng    98× terjual  Rp   784,000
3. Es Teh Manis     234× terjual  Rp   702,000
```

---

## 🔧 FITUR SELF-HEALING (Auto-Fix)

Sistem punya fitur **auto-repair** untuk fix masalah data:

### Masalah yang Sering Terjadi:

**1. PC "nyangkut" status IN_USE**
```
Masalah:
PC-05 status IN_USE, tapi session sudah selesai/dihapus

Auto-fix:
✅ Sistem deteksi PC-05 tidak punya session aktif
✅ Otomatis ubah status → IDLE
✅ PC-05 siap digunakan lagi
```

**2. Session complete tapi PC masih IN_USE**
```
Masalah:
Session #123 sudah COMPLETED, tapi PC-08 masih IN_USE

Auto-fix:
✅ Sistem scan semua PC IN_USE
✅ Check apakah session masih aktif
✅ Kalau session COMPLETED → PC jadi IDLE
```

Auto-fix jalan **otomatis** setiap kali staff buka halaman monitoring!

---

## 🚨 TROUBLESHOOTING - MASALAH & SOLUSI

### Masalah 1: Timer Tidak Akurat Setelah Pause/Resume

**Gejala:**
```
Customer pause di 80 menit
Resume → timer malah 75 menit (hilang 5 menit!)
```

**Penyebab:** Bug perhitungan pause duration

**Solusi:** Update sistem ke versi terbaru yang pakai "adjust start_time" method

---

### Masalah 2: Pendapatan Tidak Sesuai

**Gejala:**
```
Laporan Analytics: Rp 1,500,000
Halaman Orders:     Rp 1,650,000
```

**Penyebab:** 
- Analytics hitung **order COMPLETED** (delivered)
- Orders Management tampilkan **SEMUA order** (termasuk PENDING)

**Solusi:** Pakai filter `?analytics_only=true` di Orders untuk sync dengan Analytics

---

### Masalah 3: PC Tidak Bisa Digunakan (Stuck)

**Gejala:**
```
PC-12 status IN_USE terus
Padahal tidak ada customer yang pakai
```

**Solusi:**
1. Refresh halaman monitoring (auto-fix akan jalan)
2. Atau manual: Update PC-12 status → IDLE
3. Atau via API: `PATCH /api/pc/12 { "status": "IDLE" }`

---

## 🎓 TIPS UNTUK DEVELOPER

### Tip 1: Pahami PricingService Dulu

**Ini adalah jantung sistem!** Semua perhitungan harga ada di sini.

File: `app/Services/PricingService.php`

Method penting:
- `calculatePrice()` - hitung harga total
- `splitIntoPeriods()` - split DAY/NIGHT periods
- `calculatePeriodCost()` - hitung per period

### Tip 2: RentalSessionController adalah CORE

80% business logic ada di controller ini!

Method penting:
- `store()` - create session
- `pause()` / `resume()` - pause/resume logic
- `complete()` - selesaikan + hitung F&B
- `ensureDataConsistency()` - self-healing

### Tip 3: Gunakan RevenueService untuk Revenue

**JANGAN** langsung query Order untuk hitung revenue!

Gunakan:
```php
// ✅ BENAR
RevenueService::getFbRevenueByDateAndHour($start, $end)

// ❌ SALAH
Order::whereBetween('created_at', [$start, $end])->sum('total')
```

Mengapa? RevenueService filter COMPLETED orders, bukan PENDING!

---

## 📚 GLOSSARY (Istilah Penting)

| Istilah | Penjelasan |
|---------|------------|
| **Session** | Sesi rental PC dari mulai sampai selesai |
| **Tier** | Jenis PC (REGULER atau VIP) |
| **Tier Pricing** | Harga bertingkat (jam 1 lebih mahal dari jam 2+) |
| **Night Rate** | Harga malam dengan diskon 20% |
| **Mixed Period** | Session yang melintas batas waktu (siang-malam) |
| **Pause/Resume** | Fitur hentikan waktu sementara |
| **Self-Healing** | Sistem auto-repair data yang error |
| **Broadcasting** | Kirim update real-time via WebSocket |
| **Eager Loading** | Load relasi sekaligus (hindari N+1 query) |

---

## ✅ CHECKLIST UNTUK STAFF BARU

**Hal yang HARUS dipahami:**

- [ ] Cara membuat rental session baru
- [ ] Cara hitung harga (REGULER vs VIP, tier pricing, night rate)
- [ ] Cara pause & resume session
- [ ] Cara input order makanan/minuman
- [ ] Cara complete session & kasih nota
- [ ] Status PC (IDLE, IN_USE, MAINTENANCE)
- [ ] Status Order (RECEIVED → PREPARING → READY → DELIVERED)

**Tips Staff:**
1. **Selalu cek harga** sebelum konfirmasi ke customer
2. **Hati-hati** dengan pause/resume (customer sering lupa)
3. **Update status order** secepat mungkin (biar dapur update)
4. **Complete session** tepat waktu (jangan biarkan timer lewat)

---

## 🎬 KESIMPULAN

Sistem Warnet Management ini adalah sistem yang:

✅ **User-friendly** - Mudah digunakan staff
✅ **Akurat** - Perhitungan harga tepat (tier pricing + night discount)
✅ **Real-time** - Update otomatis tanpa refresh
✅ **Terintegrasi** - Rental PC + F&B dalam satu sistem
✅ **Reliable** - Auto-fix masalah data (self-healing)
✅ **Scalable** - Support 45 PC + bisa ditambah

**Perfect untuk:**
- Warnet kecil-menengah (10-50 PC)
- Warnet yang jual makanan/minuman
- Owner yang mau monitor dari rumah (real-time)
- Warnet yang butuh laporan lengkap

---

**Ada pertanyaan?** 
Baca file `ANALISIS_SISTEM_LENGKAP.md` untuk penjelasan teknis detail.

**Mau mulai pakai?**
Baca file `README.md` untuk instalasi dan setup.

---

**Dibuat dengan ❤️ untuk kemudahan memahami sistem**

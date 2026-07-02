# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Minimarket POS** — Aplikasi Point-of-Sale + manajemen gudang multi-cabang, dibangun dengan Laravel 12 + Filament v3 (panel admin/kasir) + Livewire (POS page) + MySQL/MariaDB.

### Roles & Panel Access

| Role | Panel | Akses |
|------|-------|-------|
| `admin` | Admin (`/admin`) | Semua outlet |
| `manajer` | Admin (`/admin`) | Per outlet via global scope |
| `kasir` | Kasir (`/kasir`) | Hanya POS & shift kasir |
| `staff_gudang` | Admin (`/admin`) | Modul inventory saja |

### Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin Pusat | admin@minimarket.test | password |
| Manajer | manajer@minimarket.test | password |
| Kasir | kasir@minimarket.test | password |
| Staff Gudang | staff@minimarket.test | password |

## Common Commands

```bash
# Development — spin up all services concurrently
composer run dev
# → php artisan serve, queue:listen, pail (logs), npm run dev (Vite)

# Full setup from scratch
composer run setup
# → composer install, cp .env, key:generate, migrate --force, npm install, npm run build

# Database
php artisan migrate --seed          # Migrate + seed demo data
php artisan migrate:fresh --seed    # Reset + seed
php artisan db:seed --class=DemoDataSeeder  # Seed only

# Testing
composer run test         # php artisan config:clear + php artisan test
php artisan test          # Run all tests
php artisan test --filter=StockServiceTest  # Run single test file

# Asset building
npm run dev               # Vite dev
npm run build             # Vite build

# Queue (needed for queued jobs)
php artisan queue:listen --tries=1 --timeout=0

# Logs (real-time)
php artisan pail --timeout=0     # Tail Laravel logs (Tailwind-like)

# Storage
php artisan storage:link         # Symlink public/storage → storage/app/public
```

## Architecture

### Laravel Structure

```php
app/
├── Actions/                   # Action classes (Sale/, Stock/)
├── Enums/                     # StockMovementType, SaleStatus
├── Filament/
│   ├── Clusters/              # (empty — navigasi via Resources)
│   ├── Pages/                 # PointOfSale, CashRegisterShift
│   ├── Resources/             # CRUD resources per entity
│   └── Widgets/               # SalesChart, LowStockWidget, TopProductsWidget
├── Http/Middleware/           # EnsureKasirRole
├── Models/
│   ├── Traits/
│   │   └── BelongsToOutlet.php  # Global scope multi-cabang
│   └── *.php                  # 30+ Eloquent models
├── Observers/                 # (empty)
├── Policies/                  # (empty — auth via role/permission packages)
├── Providers/
│   ├── AppServiceProvider.php
│   └── Filament/
│       ├── AdminPanelProvider.php   # Panel /admin
│       └── KasirPanelProvider.php   # Panel /kasir
├── Services/                  # Business logic layer
│   ├── BarcodeService.php     # Generate & lookup barcode
│   ├── PurchaseService.php    # PO + GoodsReceipt
│   ├── ReportService.php      # Sales/Stock/Profit reports
│   ├── SaleService.php        # Create/void/return sale
│   ├── StockService.php       # add/deduct stock (FEFO)
│   ├── StockTransferService.php  # Transfer antar gudang
│   └── UnitConversionService.php
└── Traits/                    # (same as Models/Traits/)
```

### Multi-Cabang (BelongsToOutlet)

Trait `App\Models\Traits\BelongsToOutlet` digunakan sebagai **global scope** yang otomatis memfilter query berdasarkan `outlet_id` user yang login. Tidak terapkan di model yang tidak memiliki `outlet_id` atau yang perlu lintas outlet.

- **User admin** — dilewati filter (lihat semua outlet)
- **User non-admin** — query otomatis `WHERE outlet_id = ?`

Model yang menggunakan trait ini: `Sale`, `PurchaseOrder`, `StockTransfer`, `StockOpname`, dan model lain dengan kolom `outlet_id`.

### Service Layer Pattern

Semua logika bisnis ada di `app/Services/`, bukan di controller atau resource. Services di-inject via constructor:

```php
class SaleService {
    public function __construct(
        private StockService $stockService,
        private UnitConversionService $unitConversionService
    ) {}
}
```

### FEFO (First Expired First Out)

Saat `StockService::deductStock()` mengurangi stok, otomatis memilih `ProductBatch` dengan `expired_date` paling dekat terlebih dahulu (ASC, NULLS LAST). Jika stok satu batch tidak cukup, lanjut ke batch berikutnya. Semua operasi stock dibungkus `DB::transaction()` dengan `lockForUpdate()` pada query stock/batch.

### Key Transaction Flows

1. **Sale (POS):** `SaleService::createSale()` → convert unit → `StockService::deductStock()` (FEFO) → buat Sale + SaleItem + SalePayment dalam satu transaksi. Validasi cash_register harus `open`.
2. **Purchase:** `PurchaseService::receiveGoods()` → `StockService::addStock()` → buat ProductBatch baru → update status PO (partial/completed).
3. **Stock Transfer:** `pending` → approve → `in_transit` (stock gudang asal berkurang) → receive → `received` (stock gudang tujuan bertambah). Reject mengembalikan stock.
4. **Stock Opname:** Input `qty_fisik` vs `qty_system` → jika approved, seluruh selisih di-adjust via `StockService`.

### Two Filament Panels

| Panel | ID | Path | Pages |
|-------|----|------|-------|
| Admin | `admin` | `/admin` | All Resources + Dashboard |
| Kasir | `kasir` | `/kasir` | PointOfSale, CashRegisterShift |

- **`EnsureKasirRole` middleware** di `/kasir`: redirect admin/manajer/staff_gudang ke `/admin`.
- **`canAccessPanel()`** di User model: admin/manajer/staff_gudang → admin panel; kasir + role lain → kasir panel.

### Navigation Groups (Admin Panel)

```
Master Data → Products, Categories, Product Units, Outlets, Warehouses, Suppliers, Customers
Inventory   → Stock, Product Batches, Stock Transfers, Stock Opnames
Purchasing  → Purchase Orders, Goods Receipts (via PO action)
Sales       → POS, Shift Kasir, Sales History, Returns
Reports     → Sales Report, Stock Report, Profit Report
Settings    → Users, Expense Categories, Expenses
```

## Database

- **DB:** `minimarket` (MySQL, `root@localhost:3306`)
- **Migrations:** 30+ migrations, urut sesuai dependensi (outlets → warehouses → products → stock → PO → sales → transfers → opnames → expenses)
- **Seeder:** `DemoDataSeeder` — truncate + reseed semua data demo (3 outlet, 30 produk, 1 bulan transaksi, stock dengan batch FEFO)

## Testing

Tests ada di `tests/Feature/ExampleTest.php` dan `tests/Unit/ExampleTest.php`. Belum ada dedicated unit test untuk Services (StockService, SaleService, dll) — prioritas jika butuh coverage.

## Package Dependencies

| Package | Purpose |
|---------|---------|
| `filament/filament ^3.2` | Admin panel |
| `spatie/laravel-permission ^6.25` | Role & permission |
| `spatie/laravel-activitylog ^4.12` | Audit trail |
| `picqer/php-barcode-generator ^3.2` | Generate barcode image |
| `tailwindcss ^4` | Styling |

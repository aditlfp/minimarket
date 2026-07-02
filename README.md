# 🏪 Minimarket POS — Aplikasi Kasir + Gudang Multi-Cabang

Aplikasi Point-of-Sale (POS) terintegrasi dengan manajemen stok/gudang untuk bisnis multi-cabang (minimarket).

**Stack:** Laravel 12, Filament v3, Livewire, MySQL/MariaDB

## ✨ Fitur Utama

| Modul | Fitur |
|-------|-------|
| **Master Data** | Outlet, Warehouse, Produk (dengan barcode), Kategori, Satuan & Konversi, Supplier, Customer |
| **Inventory** | Stok per gudang, FEFO (First Expired First Out), Batch/Expired Date, Mutasi Stok |
| **Pembelian** | Purchase Order, Penerimaan Barang (parsial), Generate Batch otomatis |
| **POS** | Point-of-Sale dengan scan barcode, split payment, cetak struk 58mm |
| **Cash Register** | Buka/tutup shift kasir, kas masuk/keluar |
| **Transfer Stok** | Antar gudang dengan approval flow (pending → in_transit → received) |
| **Stok Opname** | Hitung fisik vs sistem, adjustment otomatis |
| **Laporan** | Penjualan, Stok, Profit, Export (PDF/Excel) |
| **Multi-Cabang** | Global scope per outlet, admin pusat bisa lihat semua cabang |
| **Role & Permission** | Admin, Manajer, Kasir, Staff Gudang |

## 🚀 Instalasi

```bash
# Clone & install dependencies
composer install
npm install && npm run build

# Environment
cp .env.example .env
php artisan key:generate

# Setup database (buka .env & isi DB_DATABASE, DB_USERNAME, DB_PASSWORD)
php artisan migrate --seed

# Storage link
php artisan storage:link

# Jalankan
php artisan serve
```

## 🔐 Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Admin Pusat | admin@minimarket.test | password |
| Manajer | manajer@minimarket.test | password |
| Kasir | kasir@minimarket.test | password |
| Staff Gudang | staff@minimarket.test | password |

**Panel Admin:** `http://localhost:8000/admin`
**Panel Kasir:** `http://localhost:8000/kasir`

## 📦 Yang Perlu Dipersiapkan

- PHP 8.1+
- MySQL/MariaDB
- Composer
- Node.js & NPM (untuk asset building)
- Barcode Scanner USB (untuk POS) — opsional

## 📁 Struktur Aplikasi

```
app/
├── Services/       # Logika bisnis (Stock, Sale, Purchase, Barcode, dll)
├── Filament/
│   ├── Resources/  # CRUD admin panel
│   ├── Pages/      # POS & CashRegister custom pages
│   └── Clusters/   # Grup navigasi
└── Models/         # 24+ model Eloquent
```

## 🧪 Testing

```bash
php artisan test
```

## 📄 License

Hak cipta dilindungi undang-undang. Aplikasi ini dikembangkan untuk keperluan internal minimarket.

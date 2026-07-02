# DESIGN.md — Aplikasi Kasir + Gudang Multi-Cabang

## 1. Ringkasan

Aplikasi point-of-sale (POS) terintegrasi dengan manajemen gudang/inventory, untuk bisnis dengan banyak cabang (outlet). Setiap outlet bisa punya satu atau lebih gudang. Stok, transaksi, dan laporan di-scope per outlet, dengan admin pusat yang bisa melihat semua cabang.

**Stack:** Laravel 11, Filament v3, MySQL/MariaDB, Livewire (untuk halaman POS custom).

**Target pengguna:**
- Owner / Admin Pusat — akses penuh semua cabang, laporan konsolidasi.
- Manajer Outlet — akses penuh untuk outlet-nya saja.
- Kasir — hanya halaman POS dan shift kas miliknya.
- Staff Gudang — hanya modul inventory (stok, transfer, opname, penerimaan barang).

---

## 2. Arsitektur Multi-Cabang

- Setiap `User` punya `outlet_id` (nullable untuk admin pusat yang akses semua outlet).
- Global scope `BelongsToOutlet` otomatis filter query berdasarkan outlet user yang login, kecuali role admin pusat.
- Setiap `Warehouse` terikat ke satu `Outlet`, tapi satu outlet boleh punya lebih dari satu gudang (misal: gudang display + gudang stok cadangan).
- Stok TIDAK pernah digabung lintas outlet secara implisit — perpindahan barang antar outlet wajib lewat `StockTransfer` dengan approval flow.

---

## 3. Modul & Entitas Data

### 3.1 Master Data
- `Outlet` — nama, alamat, telepon, status aktif.
- `Warehouse` — nama, outlet_id, tipe (utama/cadangan).
- `Supplier` — data pemasok untuk pembelian.
- `Customer` — opsional, untuk member/piutang.
- `Category`, `Product` — data produk, harga beli, harga jual, kategori, gambar, **barcode** (unique, nullable — kosong jika belum digenerate).
- `ProductUnit` — daftar satuan (pcs, box, lusin, dus).
- `ProductUnitConversion` — relasi konversi: base_unit_id, unit_id, conversion_qty (mis. 1 box = 12 pcs).

### 3.2 Inventory & Stok
- `Stock` — saldo stok per product per warehouse, **selalu dalam base unit** (pcs).
- `StockMovement` — log audit semua pergerakan stok (in/out/transfer/adjustment/opname), immutable.
- `ProductBatch` — nomor batch, tanggal kadaluarsa, qty tersisa, warehouse_id. Sumber kebenaran stok per batch.
- `StockTransfer` + `StockTransferItem` — perpindahan stok antar warehouse, dengan status: `pending → in_transit → received` (atau `rejected`).
- `StockOpname` + `StockOpnameItem` — stok fisik vs sistem, menghasilkan `StockMovement` tipe adjustment saat disetujui.

### 3.3 Pembelian
- `PurchaseOrder` + `PurchaseOrderItem` — order ke supplier.
- `GoodsReceipt` + `GoodsReceiptItem` — penerimaan barang fisik (boleh parsial dari satu PO), otomatis membuat `ProductBatch` baru dan menambah `Stock`.

### 3.4 Penjualan (POS)
- `Sale` + `SaleItem` — transaksi kasir, terikat ke outlet, warehouse, dan cash_register session.
- `SalePayment` — mendukung split payment (tunai + QRIS, dst).
- `SaleReturn` + `SaleReturnItem` — retur barang, mengembalikan stok ke batch asal jika memungkinkan.
- `CashRegister` — sesi buka/tutup kasir per shift, mencatat saldo awal/akhir.
- `CashRegisterTransaction` — kas masuk/keluar di luar transaksi penjualan (mis. ambil kas untuk beli galon).
- `Promotion` / `Discount` — diskon per produk atau per transaksi.

### 3.5 Lainnya
- `ExpenseCategory`, `Expense` — pengeluaran operasional per outlet.
- `ActivityLog` — audit trail menyeluruh (pakai spatie/laravel-activitylog).
- `Setting` — pengaturan per outlet (pajak, printer struk, format invoice).

---

## 4. Logika Bisnis Kunci

### 4.1 Konversi Satuan
Stok selalu disimpan dalam base unit. Saat transaksi/PO menggunakan satuan lain (box, lusin), sistem konversi otomatis lewat `UnitConversionService` sebelum menyentuh tabel `Stock`. Ini mencegah stok pecahan/ambigu.

### 4.2 FEFO (First Expired First Out)
Saat penjualan mengurangi stok, `StockService` otomatis memilih `ProductBatch` dengan `expired_date` paling dekat terlebih dahulu, selama qty mencukupi. Jika tidak cukup dari satu batch, pecah ke batch berikutnya.

### 4.3 Transfer Antar Gudang/Cabang
Status bertahap untuk menghindari selisih stok saat barang masih di perjalanan:
1. `pending` — diajukan, stok gudang asal belum berkurang.
2. `in_transit` — disetujui, stok gudang asal dikurangi, stok belum masuk ke tujuan.
3. `received` — dikonfirmasi diterima, stok gudang tujuan bertambah.
4. `rejected` — dibatalkan, stok asal tidak jadi berkurang (jika sudah in_transit, stok dikembalikan).

### 4.4 Stok Opname
Input qty fisik per produk per warehouse → sistem hitung selisih vs `Stock` saat ini → setelah approval, generate `StockMovement` tipe adjustment (in/out sesuai selisih).

### 4.5 Shift Kasir
Kasir wajib buka `CashRegister` (input saldo awal) sebelum bisa transaksi. Semua `Sale` di shift tersebut terikat ke `cash_register_id`. Saat tutup shift, sistem hitung saldo seharusnya vs input fisik kasir, catat selisih.

### 4.6 Barcode (Scan & Generate)
Dua arah pemakaian, sesuai kebutuhan owner ala minimarket:

1. **Scan barcode existing** (sudah ada dari pabrik/supplier) — di halaman POS, input stok, dan penerimaan barang, ada input field fokus yang menangkap hasil scan dari barcode scanner (USB/handheld bertindak sebagai keyboard input + auto-enter). Sistem cari `Product` berdasarkan kolom `barcode`, kalau ketemu langsung auto-fill (POS: masuk ke cart; input stok/PO: auto-select produk).
2. **Generate barcode sendiri** — untuk produk yang belum punya barcode (curah, racikan toko, dsb), `BarcodeService` generate kode unik (format CODE128 atau EAN-13 internal), simpan ke kolom `barcode` produk. Bisa dicetak jadi label stiker (ukuran umum label printer thermal, mis. 30mm x 20mm) berisi nama produk, harga jual, dan barcode — siap ditempel ke barang sebelum dipajang.

Jika scan tidak menemukan produk (barcode asing/belum terdaftar), sistem tampilkan dialog untuk langsung membuat produk baru dengan barcode tersebut ter-prefill, supaya kasir/staff gudang tidak perlu ketik manual ulang.

---

## 5. Struktur Folder Laravel

```
app/
├── Models/
├── Filament/
│   ├── Resources/
│   │   ├── ProductResource/ (+ RelationManagers: Units, Batches)
│   │   ├── OutletResource/
│   │   ├── WarehouseResource/
│   │   ├── SupplierResource/
│   │   ├── CustomerResource/
│   │   ├── PurchaseOrderResource/
│   │   ├── StockTransferResource/
│   │   ├── StockOpnameResource/
│   │   ├── SaleResource/   (riwayat & laporan, bukan tempat transaksi)
│   │   └── UserResource/
│   ├── Clusters/
│   │   ├── Inventory/   (Product, Stock, Transfer, Opname, Batch)
│   │   ├── Purchasing/  (Supplier, PO, Goods Receipt)
│   │   ├── Sales/       (POS, riwayat Sale, Return)
│   │   └── Reports/     (Sales, Stock, Profit)
│   ├── Pages/
│   │   ├── PointOfSale.php
│   │   ├── CashRegisterShift.php
│   │   └── Dashboard.php
│   └── Widgets/
│       ├── SalesChart.php
│       ├── LowStockWidget.php
│       └── TopProductsWidget.php
├── Services/
│   ├── StockService.php
│   ├── UnitConversionService.php
│   ├── SaleService.php
│   ├── StockTransferService.php
│   ├── PurchaseService.php
│   ├── ReportService.php
│   └── BarcodeService.php
├── Actions/
│   ├── Sale/ (CreateSaleAction, VoidSaleAction, PrintReceiptAction)
│   └── Stock/ (AdjustStockAction, TransferStockAction)
├── Enums/
│   ├── StockMovementType.php
│   ├── SaleStatus.php
│   ├── PaymentStatus.php
│   └── UserRole.php
├── Policies/
├── Observers/
│   └── StockObserver.php
├── Traits/
│   └── BelongsToOutlet.php
└── Providers/Filament/
    ├── AdminPanelProvider.php   (owner/manajer, semua cabang)
    └── KasirPanelProvider.php   (kasir, panel ringkas)
```

---

## 6. Urutan Pengembangan (Fase)

1. **Fase 1 — Pondasi:** migration & model master data (Outlet, Warehouse, User+role, Product, Unit, Conversion). Setup 2 panel Filament + auth + policy dasar.
2. **Fase 2 — Inventory:** Stock, StockMovement, ProductBatch, StockService (logika FEFO + konversi unit). Resource CRUD untuk semua master data.
3. **Fase 3 — Pembelian:** PurchaseOrder, GoodsReceipt, otomatis generate batch & nambah stok.
4. **Fase 4 — POS & Penjualan:** halaman PointOfSale custom Livewire, CashRegister shift, SaleService, cetak struk.
5. **Fase 5 — Transfer & Opname:** StockTransfer dengan approval flow, StockOpname dengan adjustment.
6. **Fase 6 — Laporan & Polish:** dashboard, widget, laporan penjualan/stok/profit per outlet & konsolidasi, export PDF/Excel.

---

## 7. Hal yang Perlu Diputuskan Sebelum Mulai Coding

- Apakah butuh fitur piutang/member customer di fase awal, atau menyusul?
- Metode pembayaran apa saja yang didukung (tunai, QRIS, transfer, kartu)? Perlu integrasi payment gateway atau manual saja?
- Format cetak struk: thermal printer (58mm/80mm) lewat browser print atau perlu integrasi driver khusus?
- Apakah perlu mode offline/PWA untuk POS saat koneksi internet putus?
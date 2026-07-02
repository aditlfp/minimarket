# PROMPT.md — Aplikasi Kasir + Gudang Multi-Cabang

> Gunakan prompt ini secara berurutan per fase di Claude Code/Cursor/Windsurf. Selesaikan satu fase, test, baru lanjut ke fase berikutnya. Selalu lampirkan `DESIGN.md` sebagai konteks di awal sesi baru.

---

## PROMPT 0 — Setup Project

```
Saya membangun aplikasi kasir (POS) + manajemen gudang multi-cabang dengan Laravel 11 + Filament v3.
Baca DESIGN.md yang saya lampirkan untuk konteks arsitektur lengkap sebelum mulai.

Tolong setup awal project:
1. Install Laravel 11 + Filament v3 (panel-nya nanti akan ada 2: admin & kasir, tapi setup dulu Filament default).
2. Install package: spatie/laravel-permission (role & permission), spatie/laravel-activitylog (audit trail).
3. Buat struktur folder sesuai bagian "6. Struktur Folder Laravel" di DESIGN.md (Services, Actions, Enums, Policies, Observers, Traits).
4. Setup koneksi database MySQL.
5. Buat seeder dasar: role (admin, manajer, kasir, staff_gudang) via spatie/laravel-permission.

Jangan buat fitur lain dulu, fokus hanya setup pondasi ini.
```

---

## PROMPT 1 — Fase 1: Master Data & Multi-Cabang

```
Lanjut dari setup sebelumnya. Sekarang buat Fase 1 sesuai DESIGN.md bagian 3.1 dan 2 (Arsitektur Multi-Cabang).

Buat migration + model + Filament Resource untuk:
1. Outlet (nama, alamat, telepon, is_active)
2. Warehouse (nama, outlet_id, tipe: utama/cadangan)
3. User — tambahkan kolom outlet_id (nullable), relasi ke Outlet
4. ProductUnit (nama satuan: pcs, box, lusin, dus)
5. Category (nama, parent_id nullable untuk sub-kategori)
6. Product (nama, sku, **barcode unique nullable**, category_id, base_unit_id, harga_beli, harga_jual, gambar, is_active)
7. ProductUnitConversion (product_id, unit_id, conversion_qty — qty dalam base unit)

Implementasikan juga:
- Trait `BelongsToOutlet` sebagai global scope: otomatis filter query berdasarkan outlet_id user yang login, KECUALI user dengan role admin (admin melihat semua outlet). Terapkan trait ini ke model yang relevan (Warehouse, Sale, StockTransfer, dst — akan dipakai di fase berikutnya juga).
- Filament Resource untuk semua model di atas, dengan form & table yang rapi (pakai Section, relationship select, validasi).
- Policy dasar: hanya admin & manajer yang bisa CRUD master data ini.

Ikuti konvensi penamaan Laravel standar. Gunakan Filament best practice (form schema terpisah jika perlu, gunakan relationship() untuk select).
```

---

## PROMPT 2 — Fase 2: Inventory & Stok (FEFO + Konversi Unit)

```
Lanjut Fase 2 sesuai DESIGN.md bagian 3.2, 4.1, dan 4.2.

Buat migration + model:
1. Stock (product_id, warehouse_id, qty — selalu dalam base unit, unique constraint product+warehouse)
2. StockMovement (product_id, warehouse_id, type: in/out/transfer/adjustment/opname, qty, reference_type, reference_id, notes, created_by) — tabel ini immutable, hanya insert.
3. ProductBatch (product_id, warehouse_id, batch_number, expired_date nullable, qty)

Buat Service classes:
1. `UnitConversionService`:
   - method `toBaseUnit(Product $product, Unit $unit, float $qty): float` — konversi qty dari satuan apapun ke base unit.
   - method `fromBaseUnit(...)` — kebalikannya, untuk tampilan.
2. `StockService`:
   - method `addStock()` — tambah stok + buat StockMovement + buat/update ProductBatch.
   - method `deductStock()` — kurangi stok dengan logika FEFO: ambil dari ProductBatch dengan expired_date terdekat dulu, pecah ke batch berikutnya kalau qty kurang. Lempar exception jika stok total tidak cukup. Buat StockMovement untuk setiap batch yang kepakai.
   - Semua operasi DB wajib dibungkus DB::transaction().

Buat Filament Resource untuk Stock (read-only, tampilkan saldo per warehouse) dan ProductBatch (read-only, dengan highlight warna untuk batch yang mendekati expired — misal kuning jika <30 hari, merah jika sudah lewat).

Buat juga `BarcodeService` sesuai DESIGN.md bagian 4.6:
- method `generate(Product $product): string` — generate kode unik (format CODE128, prefix bisa custom mis. "INT-") kalau produk belum punya barcode, simpan ke kolom barcode.
- method `findByBarcode(string $code): ?Product` — lookup produk by barcode.
- Tambahkan action "Generate Barcode" di ProductResource (tombol per row dan bulk action), pakai package picqer/php-barcode-generator atau sejenisnya untuk render barcode image.
- Tambahkan halaman/print view "Cetak Label" yang menampilkan barcode + nama produk + harga jual dalam ukuran label thermal kecil (30mm x 20mm), bisa multi-label sekaligus (pilih produk + qty label), siap di-print dari browser.

Tulis unit test untuk StockService: test FEFO mengambil batch yang benar, test exception saat stok tidak cukup, test konversi unit. Tulis juga test untuk BarcodeService: generate menghasilkan kode unik, tidak generate ulang kalau produk sudah punya barcode.
```

---

## PROMPT 3 — Fase 3: Pembelian (PO & Penerimaan Barang)

```
Lanjut Fase 3 sesuai DESIGN.md bagian 3.3.

Buat migration + model:
1. Supplier (nama, telepon, alamat, contact_person)
2. PurchaseOrder (supplier_id, outlet_id, warehouse_id, status: draft/ordered/partial/completed/cancelled, total, created_by)
3. PurchaseOrderItem (purchase_order_id, product_id, unit_id, qty, harga_satuan)
4. GoodsReceipt (purchase_order_id, received_by, received_at, notes)
5. GoodsReceiptItem (goods_receipt_id, purchase_order_item_id, qty_received, batch_number, expired_date)

Buat `PurchaseService`:
- method `createPurchaseOrder()`.
- method `receiveGoods()` — terima barang (bisa parsial dari satu PO), panggil StockService::addStock() untuk tiap item, otomatis buat ProductBatch baru, update status PurchaseOrder jadi partial/completed tergantung apakah semua item sudah diterima penuh.

Buat Filament Resource:
- PurchaseOrderResource dengan RelationManager untuk items, action "Terima Barang" yang membuka form GoodsReceipt.
- Tampilkan badge status PO dengan warna berbeda per status.
- Di form GoodsReceipt, sediakan input scan barcode juga (sama seperti di POS nanti) — scan barcode produk untuk langsung pilih item PO yang sesuai dan fokus ke field qty_received-nya, mempercepat input saat barang fisik datang banyak.

Pastikan validasi: qty_received tidak boleh melebihi qty yang dipesan dikurangi yang sudah diterima sebelumnya.
```

---

## PROMPT 4 — Fase 4: POS & Transaksi Penjualan

```
Lanjut Fase 4 sesuai DESIGN.md bagian 3.4 dan 4.5. Ini fase paling kritis, tolong teliti.

Buat migration + model:
1. CashRegister (outlet_id, user_id, opening_balance, closing_balance nullable, opened_at, closed_at nullable, status: open/closed)
2. CashRegisterTransaction (cash_register_id, type: in/out, amount, notes)
3. Sale (outlet_id, warehouse_id, cash_register_id, customer_id nullable, invoice_number, subtotal, discount, tax, total, status: completed/void, created_by)
4. SaleItem (sale_id, product_id, unit_id, qty, harga_satuan, subtotal)
5. SalePayment (sale_id, payment_method, amount)
6. SaleReturn + SaleReturnItem

Buat `SaleService`:
- method `createSale()` — terima cart items, hitung total, panggil StockService::deductStock() per item (gunakan unit conversion dulu ke base unit), buat Sale + SaleItem + SalePayment dalam satu DB::transaction(). Validasi cash_register harus berstatus open.
- method `voidSale()` — batalkan transaksi, kembalikan stok.
- method `processReturn()` — retur barang, kembalikan stok ke batch (buat ProductBatch baru kalau batch asal sudah habis/tidak terlacak).

Buat halaman Filament custom (bukan Resource biasa) `PointOfSale.php`:
- Layout 2 kolom: kiri grid produk dengan search & filter kategori, kanan cart/keranjang dengan qty editable, pilih satuan per item (pcs/box/dst), input diskon, pilih metode pembayaran (mendukung split payment), tombol bayar.
- Gunakan Livewire reactive properties, bukan reload halaman.
- **Integrasi scan barcode**: sediakan input field tersembunyi/fokus otomatis yang selalu siap menangkap input dari barcode scanner USB/handheld (scanner bertindak sebagai keyboard, ketik cepat lalu auto-enter). Pakai `wire:keydown.enter` untuk menangkap submit. Saat barcode masuk, panggil `BarcodeService::findByBarcode()` — kalau produk ketemu, langsung tambah ke cart (qty +1 kalau sudah ada di cart); kalau tidak ketemu, tampilkan notifikasi "Barcode tidak terdaftar" dengan opsi cepat buat produk baru. Pastikan field ini tetap fokus walau user habis klik produk dari grid, supaya alur scan terus-menerus tidak terganggu.
- Setelah bayar sukses, tampilkan modal struk yang bisa langsung di-print (window.print() sederhana, format thermal 58mm pakai CSS).
- Halaman ini hanya bisa diakses jika user punya CashRegister yang sedang open di outlet-nya — kalau belum, redirect ke halaman buka shift.

Buat halaman `CashRegisterShift.php` untuk buka/tutup shift kasir, dengan ringkasan total penjualan & kas masuk/keluar saat tutup shift, serta selisih kas (expected vs actual).
```

---

## PROMPT 5 — Fase 5: Transfer Antar Gudang & Stok Opname

```
Lanjut Fase 5 sesuai DESIGN.md bagian 3.2 dan 4.3, 4.4.

Buat migration + model:
1. StockTransfer (from_warehouse_id, to_warehouse_id, status: pending/in_transit/received/rejected, requested_by, approved_by nullable, received_by nullable)
2. StockTransferItem (stock_transfer_id, product_id, qty)
3. StockOpname (warehouse_id, status: draft/approved, created_by, approved_by nullable)
4. StockOpnameItem (stock_opname_id, product_id, qty_system, qty_fisik, selisih — computed)

Buat `StockTransferService`:
- method `approve()` — ubah status ke in_transit, kurangi stok gudang asal (StockService::deductStock dengan reference ke transfer ini).
- method `receive()` — ubah status ke received, tambah stok gudang tujuan (StockService::addStock).
- method `reject()` — jika masih pending, batalkan langsung. Jika sudah in_transit, kembalikan stok ke gudang asal lalu set status rejected.

Buat logika StockOpname:
- Saat submit, sistem otomatis isi qty_system dari Stock saat ini, user input qty_fisik.
- Saat di-approve, untuk setiap item dengan selisih != 0, panggil StockService (addStock atau deductStock tergantung arah selisih) dengan tipe movement "adjustment".

Buat Filament Resource untuk StockTransfer (dengan action approve/receive/reject sesuai status, hanya muncul tombol yang relevan) dan StockOpname (dengan action submit & approve, role staff_gudang hanya bisa submit, manajer yang approve).
```

---

## PROMPT 6 — Fase 6: Dashboard, Laporan & Polish

```
Lanjut Fase 6 sesuai DESIGN.md bagian 3.5 dan 6.

Buat:
1. Widget dashboard Filament: SalesChart (grafik penjualan 30 hari terakhir, filter per outlet untuk admin), LowStockWidget (produk dengan stok di bawah threshold), TopProductsWidget (produk terlaris).
2. ReportService dengan method untuk: laporan penjualan per periode/outlet, laporan stok (kartu stok per produk), laporan profit (harga jual - harga beli, per periode).
3. Halaman laporan di Filament dengan filter tanggal & outlet (untuk admin pusat, bisa pilih "semua outlet" untuk laporan konsolidasi), tombol export ke PDF dan Excel (gunakan maatwebsite/excel dan barryvdh/laravel-dompdf).
4. Model Expense + ExpenseCategory dengan CRUD sederhana, masuk ke perhitungan laba bersih di laporan profit.
5. Setup dua panel Filament terpisah:
   - AdminPanelProvider — semua resource & cluster, untuk admin/manajer.
   - KasirPanelProvider — hanya PointOfSale, CashRegisterShift, dan riwayat transaksi milik kasir tersebut. Path panel: /kasir.
6. Pastikan navigasi (NavigationGroup) di AdminPanelProvider dikelompokkan sesuai Cluster di DESIGN.md (Inventory, Purchasing, Sales, Reports).

Setelah ini selesai, review ulang seluruh policy & global scope BelongsToOutlet untuk memastikan tidak ada kebocoran data antar outlet.
```

---

## Catatan Pemakaian

- Tiap prompt didesain self-contained tapi tetap merujuk ke `DESIGN.md` — selalu lampirkan dokumen itu di awal sesi baru kalau context AI tool ke-reset.
- Setelah tiap fase, jalankan `php artisan test` dan cek manual di Filament sebelum lanjut prompt berikutnya.
- Kalau ada requirement berubah (misal butuh piutang member atau integrasi payment gateway), update dulu DESIGN.md bagian 7, baru sesuaikan prompt terkait.
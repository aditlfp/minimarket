<?php

namespace Database\Seeders;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductUnit;
use App\Models\ProductUnitConversion;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate all tables
        $tables = [
            'stock_opname_items', 'stock_opnames', 'stock_transfer_items', 'stock_transfers',
            'sale_return_items', 'sale_returns', 'sale_payments', 'sale_items', 'sales',
            'cash_register_transactions', 'cash_registers',
            'goods_receipt_items', 'goods_receipts', 'purchase_order_items', 'purchase_orders',
            'suppliers', 'product_batches', 'stock_movements', 'stock',
            'product_unit_conversions', 'products', 'categories', 'product_units',
            'warehouses', 'expenses', 'expense_categories', 'customers', 'outlets',
            'users', 'model_has_roles', 'model_has_permissions', 'role_has_permissions', 'roles',
        ];
        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ========== ROLES ==========
        $this->call(RoleSeeder::class);

        // ========== OUTLETS ==========
        $outlet1 = Outlet::create(['nama' => 'Minimarket Pusat', 'alamat' => 'Jl. Merdeka No. 1, Jakarta', 'telepon' => '021-12345678', 'is_active' => true]);
        $outlet2 = Outlet::create(['nama' => 'Minimarket Cabang Bandung', 'alamat' => 'Jl. Asia Afrika No. 10, Bandung', 'telepon' => '022-87654321', 'is_active' => true]);
        $outlet3 = Outlet::create(['nama' => 'Minimarket Cabang Surabaya', 'alamat' => 'Jl. Tunjungan No. 5, Surabaya', 'telepon' => '031-5551234', 'is_active' => true]);

        // ========== WAREHOUSES ==========
        $whPusatUtama = Warehouse::create(['nama' => 'Gudang Utama Pusat', 'outlet_id' => $outlet1->id, 'tipe' => 'utama']);
        $whPusatCadangan = Warehouse::create(['nama' => 'Gudang Cadangan Pusat', 'outlet_id' => $outlet1->id, 'tipe' => 'cadangan']);
        $whBandung = Warehouse::create(['nama' => 'Gudang Bandung', 'outlet_id' => $outlet2->id, 'tipe' => 'utama']);
        $whSurabaya = Warehouse::create(['nama' => 'Gudang Surabaya', 'outlet_id' => $outlet3->id, 'tipe' => 'utama']);

        // ========== USERS ==========
        $admin = User::create(['name' => 'Admin Pusat', 'email' => 'admin@minimarket.test', 'password' => Hash::make('password'), 'outlet_id' => null]);
        $admin->assignRole('admin');

        $manajer = User::create(['name' => 'Budi Manajer', 'email' => 'manajer@minimarket.test', 'password' => Hash::make('password'), 'outlet_id' => $outlet1->id]);
        $manajer->assignRole('manajer');

        $kasir1 = User::create(['name' => 'Ani Kasir', 'email' => 'kasir@minimarket.test', 'password' => Hash::make('password'), 'outlet_id' => $outlet1->id]);
        $kasir1->assignRole('kasir');

        $kasir2 = User::create(['name' => 'Rudi Kasir', 'email' => 'kasir2@minimarket.test', 'password' => Hash::make('password'), 'outlet_id' => $outlet2->id]);
        $kasir2->assignRole('kasir');

        $staffGudang = User::create(['name' => 'Siti Gudang', 'email' => 'staff@minimarket.test', 'password' => Hash::make('password'), 'outlet_id' => $outlet1->id]);
        $staffGudang->assignRole('staff_gudang');

        // ========== PRODUCT UNITS ==========
        $pcs = ProductUnit::create(['nama' => 'Pieces', 'singkatan' => 'pcs']);
        $box = ProductUnit::create(['nama' => 'Box', 'singkatan' => 'box']);
        $lusin = ProductUnit::create(['nama' => 'Lusin', 'singkatan' => 'lsn']);
        $dus = ProductUnit::create(['nama' => 'Dus', 'singkatan' => 'dus']);
        $kg = ProductUnit::create(['nama' => 'Kilogram', 'singkatan' => 'kg']);
        $liter = ProductUnit::create(['nama' => 'Liter', 'singkatan' => 'ltr']);

        // ========== CATEGORIES ==========
        $makanan = Category::create(['nama' => 'Makanan']);
        $minuman = Category::create(['nama' => 'Minuman']);
        $sembako = Category::create(['nama' => 'Sembako']);
        $snack = Category::create(['nama' => 'Snack', 'parent_id' => $makanan->id]);
        $mie = Category::create(['nama' => 'Mie & Bumbu', 'parent_id' => $makanan->id]);
        $susu = Category::create(['nama' => 'Susu & Sari', 'parent_id' => $minuman->id]);
        $soda = Category::create(['nama' => 'Soda & Jus', 'parent_id' => $minuman->id]);
        $rumahTangga = Category::create(['nama' => 'Rumah Tangga']);
        $perawatan = Category::create(['nama' => 'Perawatan Diri']);

        // ========== PRODUCTS ==========
        $products = [];
        $productData = [
            // [nama, sku, barcode, category_id, base_unit_id, harga_beli, harga_jual]
            ['Indomie Goreng', 'MIE-001', '8991002100110', $mie->id, $pcs->id, 2500, 3500],
            ['Indomie Kuah Soto', 'MIE-002', '8991002100127', $mie->id, $pcs->id, 2500, 3500],
            ['Mie Sedap Goreng', 'MIE-003', '8991002100134', $mie->id, $pcs->id, 2500, 3500],
            ['Bimoli Minyak Goreng 1L', 'MIN-001', '8991002100141', $sembako->id, $liter->id, 14000, 17500],
            ['Beras Ramos 5kg', 'BER-001', '8991002100158', $sembako->id, $kg->id, 60000, 72000],
            ['Gula Pasir Gulaku 1kg', 'GUL-001', '8991002100165', $sembako->id, $kg->id, 14000, 17500],
            ['Telur Ayam 1kg', 'TEL-001', '8991002100172', $sembako->id, $kg->id, 25000, 30000],
            ['Kopiko 78g', 'SNK-001', '8991002100189', $snack->id, $pcs->id, 4500, 6000],
            ['Tango Wafer Coklat', 'SNK-002', '8991002100196', $snack->id, $pcs->id, 5000, 7000],
            ['Chitato Sapi Panggang', 'SNK-003', '8991002100202', $snack->id, $pcs->id, 9000, 12000],
            ['Aqua Air Mineral 600ml', 'MIN-002', '8991002100219', $minuman->id, $pcs->id, 2500, 3500],
            ['Coca-Cola 390ml', 'MIN-003', '8991002100226', $soda->id, $pcs->id, 5000, 7000],
            ['Fanta 390ml', 'MIN-004', '8991002100233', $soda->id, $pcs->id, 5000, 7000],
            ['Ult Milk Coklat 250ml', 'SUS-001', '8991002100240', $susu->id, $pcs->id, 5500, 7500],
            ['Ult Milk Full Cream 1L', 'SUS-002', '8991002100257', $susu->id, $liter->id, 15000, 19000],
            ['Yakult 5 botol', 'SUS-003', '8991002100264', $susu->id, $pcs->id, 8000, 11000],
            ['Teh Botol Sosro 500ml', 'MIN-005', '8991002100271', $minuman->id, $pcs->id, 4500, 6000],
            ['Sabun Lifebuoy 75g', 'PRW-001', '8991002100288', $perawatan->id, $pcs->id, 3500, 5000],
            ['Pasta Gigi Pepsodent 120g', 'PRW-002', '8991002100295', $perawatan->id, $pcs->id, 8000, 11000],
            ['Shampo Clear 100ml', 'PRW-003', '8991002100301', $perawatan->id, $pcs->id, 12000, 16000],
            ['Sabun Wings 200g', 'RT-001', '8991002100318', $rumahTangga->id, $pcs->id, 4000, 6000],
            ['Pewangi Pakaian 500ml', 'RT-002', '8991002100325', $rumahTangga->id, $liter->id, 10000, 14000],
            ['Kecap Bango 500ml', 'SEM-001', '8991002100332', $sembako->id, $pcs->id, 12000, 16000],
            ['Saos Sambal ABC 300ml', 'SEM-002', '8991002100349', $sembako->id, $pcs->id, 8000, 11000],
            ['Garam Dolphin 250g', 'SEM-003', '8991002100356', $sembako->id, $pcs->id, 3000, 4500],
            ['Kopri Krupuk Udang 150g', 'SNK-004', '8991002100363', $snack->id, $pcs->id, 8000, 11000],
            ['Roti Bimbo Coklat', 'MIE-004', '8991002100370', $makanan->id, $pcs->id, 5000, 7000],
            ['Susu Kental Manis 370g', 'SUS-004', '8991002100387', $susu->id, $pcs->id, 8000, 11000],
            ['Tepung Segitiga Biru 500g', 'SEM-004', '8991002100394', $sembako->id, $kg->id, 6500, 9000],
            ['Minyak Kayu Putih 30ml', 'PRW-004', '8991002100400', $perawatan->id, $pcs->id, 15000, 20000],
        ];

        foreach ($productData as $i => $data) {
            $products[] = Product::create([
                'nama' => $data[0],
                'sku' => $data[1],
                'barcode' => $data[2],
                'category_id' => $data[3],
                'base_unit_id' => $data[4],
                'harga_beli' => $data[5],
                'harga_jual' => $data[6],
                'is_active' => true,
            ]);
        }

        // ========== PRODUCT UNIT CONVERSIONS ==========
        ProductUnitConversion::create(['product_id' => $products[4]->id, 'unit_id' => $kg->id, 'conversion_qty' => 1]); // Beras 5kg -> base kg
        ProductUnitConversion::create(['product_id' => $products[5]->id, 'unit_id' => $kg->id, 'conversion_qty' => 1]); // Gula
        // Box conversions for snack products
        foreach ([$products[7], $products[8], $products[9], $products[25]] as $p) {
            ProductUnitConversion::create(['product_id' => $p->id, 'unit_id' => $box->id, 'conversion_qty' => 24]);
        }
        // Dus conversions for mie
        foreach ([$products[0], $products[1], $products[2]] as $p) {
            ProductUnitConversion::create(['product_id' => $p->id, 'unit_id' => $dus->id, 'conversion_qty' => 40]);
        }

        // ========== SUPPLIERS ==========
        $supplier1 = Supplier::create(['nama' => 'PT Indofood Sukses Makmur', 'telepon' => '021-57951111', 'alamat' => 'Jakarta', 'contact_person' => 'Bpk. Agus']);
        $supplier2 = Supplier::create(['nama' => 'PT Unilever Indonesia', 'telepon' => '021-5261234', 'alamat' => 'Jakarta', 'contact_person' => 'Ibu Dewi']);
        $supplier3 = Supplier::create(['nama' => 'PT Sayuran Segar', 'telepon' => '022-7001122', 'alamat' => 'Bandung', 'contact_person' => 'Bpk. Cecep']);
        $supplier4 = Supplier::create(['nama' => 'UD Sembako Jaya', 'telepon' => '031-5559876', 'alamat' => 'Surabaya', 'contact_person' => 'Bpk. Hasan']);

        // ========== PURCHASE ORDERS ==========
        $po1 = PurchaseOrder::create([
            'supplier_id' => $supplier1->id, 'outlet_id' => $outlet1->id, 'warehouse_id' => $whPusatUtama->id,
            'status' => 'completed', 'total' => 0, 'created_by' => $manajer->id,
        ]);
        $po1Items = [
            [$products[0]->id, $pcs->id, 100, 2400], [$products[1]->id, $pcs->id, 80, 2400], [$products[2]->id, $pcs->id, 60, 2400],
            [$products[7]->id, $pcs->id, 120, 4200], [$products[8]->id, $pcs->id, 100, 4800], [$products[9]->id, $pcs->id, 72, 8500],
        ];
        $po1Total = 0;
        foreach ($po1Items as $item) {
            $subtotal = $item[2] * $item[3];
            $po1Total += $subtotal;
            PurchaseOrderItem::create([
                'purchase_order_id' => $po1->id, 'product_id' => $item[0], 'unit_id' => $item[1],
                'qty' => $item[2], 'harga_satuan' => $item[3],
            ]);
        }
        $po1->update(['total' => $po1Total]);

        // Receive PO1
        $receipt1 = GoodsReceipt::create(['purchase_order_id' => $po1->id, 'received_by' => $staffGudang->id, 'received_at' => Carbon::now()->subDays(7)]);
        foreach ($po1->items as $poItem) {
            GoodsReceiptItem::create([
                'goods_receipt_id' => $receipt1->id, 'purchase_order_item_id' => $poItem->id,
                'qty_received' => $poItem->qty, 'batch_number' => 'BCH-'.str_pad((string) $poItem->id, 4, '0', STR_PAD_LEFT),
                'expired_date' => Carbon::now()->addMonths(8),
            ]);
        }

        $po2 = PurchaseOrder::create([
            'supplier_id' => $supplier2->id, 'outlet_id' => $outlet1->id, 'warehouse_id' => $whPusatUtama->id,
            'status' => 'completed', 'total' => 0, 'created_by' => $manajer->id,
        ]);
        $po2Items = [
            [$products[17]->id, $pcs->id, 100, 3300], [$products[18]->id, $pcs->id, 80, 7500],
            [$products[19]->id, $pcs->id, 60, 11500], [$products[20]->id, $pcs->id, 100, 3800],
        ];
        $po2Total = 0;
        foreach ($po2Items as $item) {
            $subtotal = $item[2] * $item[3];
            $po2Total += $subtotal;
            PurchaseOrderItem::create([
                'purchase_order_id' => $po2->id, 'product_id' => $item[0], 'unit_id' => $item[1],
                'qty' => $item[2], 'harga_satuan' => $item[3],
            ]);
        }
        $po2->update(['total' => $po2Total]);
        $receipt2 = GoodsReceipt::create(['purchase_order_id' => $po2->id, 'received_by' => $staffGudang->id, 'received_at' => Carbon::now()->subDays(5)]);
        foreach ($po2->items as $poItem) {
            GoodsReceiptItem::create([
                'goods_receipt_id' => $receipt2->id, 'purchase_order_item_id' => $poItem->id,
                'qty_received' => $poItem->qty, 'batch_number' => 'BCH-'.str_pad((string) (100 + $poItem->id), 4, '0', STR_PAD_LEFT),
                'expired_date' => Carbon::now()->addMonths(10),
            ]);
        }

        // ========== STOCK & BATCHES ==========
        $stockItems = [
            [$products[0], 80, 'BCH-0001', Carbon::now()->addMonths(8)],
            [$products[1], 60, 'BCH-0002', Carbon::now()->addMonths(8)],
            [$products[2], 50, 'BCH-0003', Carbon::now()->addMonths(8)],
            [$products[3], 40, 'BCH-0004', Carbon::now()->addMonths(10)],
            [$products[4], 15, 'BCH-0005', Carbon::now()->addMonths(6)],
            [$products[5], 30, null, null],
            [$products[6], 25, 'BCH-0007', Carbon::now()->addDays(20)], // Hampir expired!
            [$products[7], 100, 'BCH-0008', Carbon::now()->addMonths(12)],
            [$products[8], 80, 'BCH-0009', Carbon::now()->addMonths(12)],
            [$products[9], 60, 'BCH-0010', Carbon::now()->addMonths(12)],
            [$products[10], 200, null, null],
            [$products[11], 50, 'BCH-0012', Carbon::now()->addMonths(9)],
            [$products[12], 40, 'BCH-0013', Carbon::now()->addMonths(9)],
            [$products[13], 60, 'BCH-0014', Carbon::now()->addDays(15)], // Hampir expired!
            [$products[14], 25, 'BCH-0015', Carbon::now()->addMonths(6)],
            [$products[15], 40, 'BCH-0016', Carbon::now()->addDays(10)], // Hampir expired!
            [$products[16], 70, null, null],
            [$products[17], 80, 'BCH-0018', Carbon::now()->addMonths(14)],
            [$products[18], 60, 'BCH-0019', Carbon::now()->addMonths(14)],
            [$products[19], 45, 'BCH-0020', Carbon::now()->addMonths(14)],
            [$products[20], 90, 'BCH-0021', Carbon::now()->addMonths(11)],
            [$products[21], 30, null, null],
            [$products[22], 35, 'BCH-0023', Carbon::now()->addMonths(8)],
            [$products[23], 40, 'BCH-0024', Carbon::now()->addMonths(8)],
            [$products[24], 100, null, null],
            [$products[25], 50, 'BCH-0026', Carbon::now()->addMonths(7)],
            [$products[26], 30, 'BCH-0027', Carbon::now()->addDays(5)], // Hampir expired!
            [$products[27], 45, 'BCH-0028', Carbon::now()->addMonths(9)],
            [$products[28], 40, null, null],
            [$products[29], 20, 'BCH-0030', Carbon::now()->addMonths(24)],
        ];

        $outletProducts = [];
        foreach ($stockItems as $item) {
            $product = $item[0];
            $qty = $item[1];
            $batchNumber = $item[2];
            $expiredDate = $item[3];

            // Main warehouse stock
            Stock::create(['product_id' => $product->id, 'warehouse_id' => $whPusatUtama->id, 'qty' => $qty]);
            $batchNo = $batchNumber ?: 'DEFAULT';
            ProductBatch::create([
                'product_id' => $product->id, 'warehouse_id' => $whPusatUtama->id,
                'batch_number' => $batchNo, 'expired_date' => $expiredDate, 'qty' => $qty,
            ]);

            // Some stock in secondary warehouse
            if (in_array($product->id, [$products[0]->id, $products[7]->id, $products[10]->id, $products[16]->id])) {
                $secQty = 20;
                Stock::create(['product_id' => $product->id, 'warehouse_id' => $whPusatCadangan->id, 'qty' => $secQty]);
                ProductBatch::create([
                    'product_id' => $product->id, 'warehouse_id' => $whPusatCadangan->id,
                    'batch_number' => $batchNo, 'expired_date' => $expiredDate, 'qty' => $secQty,
                ]);
            }

            // Some stock in branch warehouses
            if ($product->id % 3 === 0) {
                $bdgQty = max(1, intdiv($qty, 3));
                Stock::create(['product_id' => $product->id, 'warehouse_id' => $whBandung->id, 'qty' => $bdgQty]);
                ProductBatch::create([
                    'product_id' => $product->id, 'warehouse_id' => $whBandung->id,
                    'batch_number' => $batchNo, 'expired_date' => $expiredDate, 'qty' => $bdgQty,
                ]);
            }
            if ($product->id % 5 === 0) {
                $sbyQty = max(1, intdiv($qty, 4));
                Stock::create(['product_id' => $product->id, 'warehouse_id' => $whSurabaya->id, 'qty' => $sbyQty]);
                ProductBatch::create([
                    'product_id' => $product->id, 'warehouse_id' => $whSurabaya->id,
                    'batch_number' => $batchNo, 'expired_date' => $expiredDate, 'qty' => $sbyQty,
                ]);
            }

            $outletProducts[] = $product;
        }

        // ========== CUSTOMERS ==========
        $customers = [];
        $customerNames = ['Bpk. Ahmad', 'Ibu Sari', 'Bpk. Dodi', 'Ibu Rina', 'Bpk. Hendra', 'Ibu Maya', 'Bpk. Fajar', 'Ibu Dewi'];
        foreach ($customerNames as $name) {
            $customers[] = Customer::create(['nama' => $name, 'telepon' => '08'.str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT)]);
        }

        // ========== CASH REGISTER (open) ==========
        $cashRegister = CashRegister::create([
            'outlet_id' => $outlet1->id, 'user_id' => $kasir1->id,
            'opening_balance' => 500000, 'opened_at' => Carbon::now()->subHours(6), 'status' => 'open',
        ]);

        // ========== SALES (30 days of history) ==========
        $paymentMethods = ['tunai', 'qris', 'kartu_debit'];
        $now = Carbon::now();

        for ($day = 29; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day)->setHour(random_int(8, 20))->setMinute(random_int(0, 59));

            // 5-15 transactions per day
            $transCount = random_int(5, 15);
            for ($t = 0; $t < $transCount; $t++) {
                $customer = $customers[array_rand($customers)];
                $itemCount = random_int(1, 6);
                $selectedProducts = array_rand(array_slice($products, 0, null, true), $itemCount);
                if (!is_array($selectedProducts)) $selectedProducts = [$selectedProducts];

                $subtotal = 0;
                $saleItems = [];

                foreach ($selectedProducts as $pi) {
                    $product = $products[$pi];
                    $qty = random_int(1, 3);
                    $price = (float) $product->harga_jual;
                    $lineTotal = $qty * $price;
                    $subtotal += $lineTotal;
                    $saleItems[] = [
                        'product_id' => $product->id,
                        'unit_id' => $product->base_unit_id,
                        'qty' => $qty,
                        'harga_satuan' => $price,
                        'subtotal' => $lineTotal,
                    ];
                }

                $discount = $subtotal > 50000 ? random_int(0, 10) * 1000 : 0;
                $total = $subtotal - $discount;

                $sale = Sale::create([
                    'outlet_id' => $outlet1->id,
                    'warehouse_id' => $whPusatUtama->id,
                    'cash_register_id' => $cashRegister->id,
                    'customer_id' => $customer->id,
                    'invoice_number' => 'INV-'.$date->format('Ymd').'-'.str_pad((string) ($day * 20 + $t + 1), 4, '0', STR_PAD_LEFT),
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => 0,
                    'total' => $total,
                    'status' => 'completed',
                    'created_by' => $kasir1->id,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);

                foreach ($saleItems as $si) {
                    SaleItem::create(array_merge($si, ['sale_id' => $sale->id]));
                }

                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                    'amount' => $total,
                ]);
            }
        }

        // ========== EXPENSE CATEGORIES ==========
        $expCat1 = ExpenseCategory::create(['nama' => 'Listrik', 'deskripsi' => 'Biaya listrik outlet']);
        $expCat2 = ExpenseCategory::create(['nama' => 'Air', 'deskripsi' => 'Biaya PDAM']);
        $expCat3 = ExpenseCategory::create(['nama' => 'Kebersihan', 'deskripsi' => 'Biaya cleaning service']);
        $expCat4 = ExpenseCategory::create(['nama' => 'Transport', 'deskripsi' => 'Biaya antar barang']);
        $expCat5 = ExpenseCategory::create(['nama' => 'Lain-lain', 'deskripsi' => 'Biaya operasional lainnya']);

        // ========== EXPENSES ==========
        for ($i = 0; $i < 10; $i++) {
            $date = Carbon::now()->subDays(random_int(0, 30));
            Expense::create([
                'expense_category_id' => [1,1,2,3,4,5][array_rand([1,1,2,3,4,5])],
                'outlet_id' => $outlet1->id,
                'amount' => [50000, 75000, 100000, 150000, 200000][array_rand([50000, 75000, 100000, 150000, 200000])],
                'deskripsi' => 'Biaya operasional '.$date->format('d/m/Y'),
                'tanggal' => $date,
                'created_by' => $manajer->id,
            ]);
        }

        // ========== STOCK TRANSFER DEMO ==========
        $transfer = StockTransfer::create([
            'from_warehouse_id' => $whPusatUtama->id,
            'to_warehouse_id' => $whBandung->id,
            'status' => 'in_transit',
            'requested_by' => $staffGudang->id,
            'approved_by' => $manajer->id,
        ]);
        StockTransferItem::create(['stock_transfer_id' => $transfer->id, 'product_id' => $products[0]->id, 'qty' => 20]);
        StockTransferItem::create(['stock_transfer_id' => $transfer->id, 'product_id' => $products[7]->id, 'qty' => 24]);
        StockTransferItem::create(['stock_transfer_id' => $transfer->id, 'product_id' => $products[10]->id, 'qty' => 48]);

        // ========== STOCK OPNAME DEMO ==========
        $opname = StockOpname::create([
            'warehouse_id' => $whPusatUtama->id,
            'status' => 'draft',
            'created_by' => $staffGudang->id,
        ]);
        StockOpnameItem::create(['stock_opname_id' => $opname->id, 'product_id' => $products[0]->id, 'qty_system' => 80, 'qty_fisik' => 78, 'selisih' => -2]);
        StockOpnameItem::create(['stock_opname_id' => $opname->id, 'product_id' => $products[3]->id, 'qty_system' => 40, 'qty_fisik' => 40, 'selisih' => 0]);

        // ========== CASH REGISTER TRANSACTION ==========
        \App\Models\CashRegisterTransaction::create([
            'cash_register_id' => $cashRegister->id,
            'type' => 'out',
            'amount' => 100000,
            'notes' => 'Ambil kas untuk beli galon',
        ]);

        $this->command->info('✅ Demo data berhasil di-generate!');
        $this->command->info('   Admin: admin@minimarket.test / password');
        $this->command->info('   Manajer: manajer@minimarket.test / password');
        $this->command->info('   Kasir: kasir@minimarket.test / password');
        $this->command->info('   Staff: staff@minimarket.test / password');
        $this->command->info('   Total produk: '.Product::count());
        $this->command->info('   Total transaksi: '.Sale::count());
        $this->command->info('   Total pelanggan: '.Customer::count());
    }
}

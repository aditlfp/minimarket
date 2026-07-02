<?php

namespace App\Filament\Pages;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\BarcodeService;
use App\Services\SaleService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointOfSale extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'POS';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.point-of-sale';

    public ?CashRegister $cashRegister = null;
    public array $cart = [];
    public string $barcodeInput = '';
    public ?string $searchQuery = null;
    public ?int $selectedCategory = null;
    public float $discount = 0;
    public float $taxPercent = 0;

    // Properties for payment modal
    public bool $isPaymentModalOpen = false;
    public float $cashAmountReceived = 0;

    // Properties for receipt modal
    public bool $isReceiptModalOpen = false;
    public ?int $receiptSaleId = null;
    public float $receiptCashReceived = 0;
    public float $receiptChangeReturned = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $this->cashRegister = CashRegister::where('user_id', $user->id)
            ->where('outlet_id', $user->outlet_id)
            ->where('status', 'open')->first();
        if (! $this->cashRegister) $this->redirect(CashRegisterShift::getUrl());
    }

    public function scanBarcode(BarcodeService $barcodeService): void
    {
        $code = trim($this->barcodeInput);
        if (empty($code)) return;
        $product = $barcodeService->findByBarcode($code);
        if (! $product || ! $product->is_active) {
            Notification::make()->title('Barcode tidak terdaftar')->warning()->send();
            $this->barcodeInput = ''; return;
        }
        $this->addToCart($product->id);
        $this->barcodeInput = '';
    }

    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (! $product || ! $product->is_active) return;
        $key = (string) $productId;
        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty'] += 1;
        } else {
            $this->cart[$key] = [
                'product_id' => $product->id, 'nama' => $product->nama,
                'price' => (float) $product->harga_jual, 'qty' => 1,
                'unit_id' => $product->base_unit_id,
                'unit_name' => $product->baseUnit?->nama ?? 'pcs',
            ];
        }
        
        // Dispatches auto refocus to the barcode input field
        $this->dispatch('refocus-barcode');
    }

    public function updateQty(int $productId, float $qty): void
    {
        $key = (string) $productId;
        if (isset($this->cart[$key])) {
            if ($qty <= 0) unset($this->cart[$key]);
            else $this->cart[$key]['qty'] = $qty;
        }
    }

    public function removeItem(int $productId): void
    {
        unset($this->cart[(string) $productId]);
    }

    public function getCartSubtotalProperty(): float
    {
        return collect($this->cart)->sum(fn ($i) => $i['qty'] * $i['price']);
    }

    public function getTotalProperty(): float
    {
        $subtotal = $this->getCartSubtotalProperty();
        $discountAmount = $subtotal * ($this->discount / 100);
        $taxAmount = ($subtotal - $discountAmount) * ($this->taxPercent / 100);
        return $subtotal - $discountAmount + $taxAmount;
    }

    public function getProductsProperty()
    {
        $user = Auth::user();
        $warehouse = Warehouse::where('outlet_id', $user->outlet_id)->where('tipe', 'utama')->first();
        $warehouseId = $warehouse?->id ?? 0;

        $query = Product::where('is_active', true)
            ->with([
                'category', 
                'baseUnit', 
                'stocks' => fn($q) => $q->where('warehouse_id', $warehouseId)
            ]);
        if ($this->searchQuery) {
            $query->where(fn ($q) => $q->where('nama', 'like', "%{$this->searchQuery}%")
                ->orWhere('barcode', 'like', "%{$this->searchQuery}%")
                ->orWhere('sku', 'like', "%{$this->searchQuery}%"));
        }
        if ($this->selectedCategory) $query->where('category_id', $this->selectedCategory);
        return $query->paginate(12);
    }

    public function getCategoriesProperty()
    {
        return Category::orderBy('nama')->get();
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->selectedCategory = $categoryId;
    }

    public function showPaymentModal(): void
    {
        if (empty($this->cart)) {
            Notification::make()->title('Keranjang kosong')->warning()->send();
            return;
        }
        $this->cashAmountReceived = $this->getTotalProperty(); // Default to exact amount
        $this->isPaymentModalOpen = true;
    }

    public function closePaymentModal(): void
    {
        $this->isPaymentModalOpen = false;
    }

    public function closeReceiptModal(): void
    {
        $this->isReceiptModalOpen = false;
        $this->receiptSaleId = null;
    }

    public function setCashReceived(float $amount): void
    {
        $this->cashAmountReceived = $amount;
    }

    public function payExact(): void
    {
        $this->cashAmountReceived = $this->getTotalProperty();
    }

    public function getChangeAmountProperty(): float
    {
        $total = $this->getTotalProperty();
        if ($this->cashAmountReceived < $total) {
            return 0;
        }
        return $this->cashAmountReceived - $total;
    }

    public function getCategoryGradient(?int $categoryId): string
    {
        return match ($categoryId) {
            1 => 'from-orange-400 to-amber-500', // Makanan
            2 => 'from-blue-400 to-indigo-500', // Minuman
            3 => 'from-emerald-400 to-teal-500', // Sembako
            4 => 'from-rose-400 to-pink-500', // Snack
            5 => 'from-yellow-400 to-orange-500', // Mie & Bumbu
            6 => 'from-cyan-400 to-blue-500', // Susu & Sari
            7 => 'from-violet-400 to-purple-500', // Soda & Jus
            8 => 'from-slate-400 to-gray-500', // Rumah Tangga
            9 => 'from-fuchsia-400 to-rose-500', // Perawatan Diri
            default => 'from-blue-500 to-indigo-600',
        };
    }

    public function getProductInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= 2) break;
        }
        return $initials ?: 'PR';
    }

    public function checkout(SaleService $saleService): void
    {
        if (empty($this->cart)) { Notification::make()->title('Keranjang kosong')->warning()->send(); return; }
        $total = $this->getTotalProperty();
        if ($this->cashAmountReceived < $total) {
            Notification::make()->title('Uang pembayaran kurang')->warning()->send();
            return;
        }
        
        $user = Auth::user();
        $warehouse = Warehouse::where('outlet_id', $user->outlet_id)->where('tipe', 'utama')->first();
        if (! $warehouse) { Notification::make()->title('Tidak ada gudang utama')->danger()->send(); return; }
        
        $items = [];
        foreach ($this->cart as $item) $items[] = ['product_id' => $item['product_id'], 'unit_id' => $item['unit_id'], 'qty' => $item['qty'], 'harga_satuan' => $item['price']];
        
        $subtotal = $this->getCartSubtotalProperty();
        $payments = [['method' => 'tunai', 'amount' => $total]];
        
        try {
            $sale = $saleService->createSale([
                'outlet_id' => $user->outlet_id, 'warehouse_id' => $warehouse->id, 'cash_register_id' => $this->cashRegister->id,
                'items' => $items, 'discount' => $subtotal * ($this->discount / 100),
                'tax' => ($subtotal - $subtotal * ($this->discount / 100)) * ($this->taxPercent / 100), 'payments' => $payments,
            ]);

            // Save receipt data to Livewire properties (session flash doesn't work in Livewire AJAX)
            $this->receiptSaleId = $sale->id;
            $this->receiptCashReceived = $this->cashAmountReceived;
            $this->receiptChangeReturned = $this->cashAmountReceived - $total;

            // Reset cart and payment modal
            $this->cart = [];
            $this->discount = 0;
            $this->taxPercent = 0;
            $this->cashAmountReceived = 0;
            $this->isPaymentModalOpen = false;
            $this->isReceiptModalOpen = true;

            // Directly execute JS after Livewire finishes re-rendering the DOM
            $this->js("setTimeout(function() { if (window.printStruk) window.printStruk(); }, 1000);");

            Notification::make()->title("Transaksi {$sale->invoice_number} berhasil!")->success()->send();
        } catch (\Exception $e) { Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->send(); }
    }
}

<x-filament-panels::page>
    {{-- ============================================================
         POINT OF SALE - Main Layout (inline CSS for reliability)
    ============================================================ --}}
    <style>
        .pos-layout {
            display: flex;
            flex-direction: row;
            gap: 20px;
            align-items: flex-start;
        }
        .pos-catalog {
            flex: 1 1 0%;
            min-width: 0;
        }
        .pos-sidebar {
            width: 300px;
            flex-shrink: 0;
            flex-grow: 0;
        }
        .pos-product-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
        @media (max-width: 900px) {
            .pos-layout { flex-direction: column; }
            .pos-sidebar { width: 100%; }
            .pos-product-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1400px) {
            .pos-product-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        .pos-card-box {
            background: var(--tw-bg-opacity, white);
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            padding: 20px;
        }
        .dark .pos-card-box {
            background: #1f2937;
            border-color: #374151;
        }
        .pos-cart-scroll {
            max-height: 340px;
            overflow-y: auto;
        }
        .pos-qty-btn {
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            cursor: pointer;
            font-weight: 900;
            font-size: 13px;
            color: #374151;
            transition: all 0.15s;
        }
        .pos-qty-btn:hover { background: #e5e7eb; }
        .dark .pos-qty-btn { background: #111827; border-color: #374151; color: #d1d5db; }
        .dark .pos-qty-btn:hover { background: #1f2937; }
    </style>

    <div class="pos-layout">

        {{-- ============================
             LEFT: Product Catalog
        ============================ --}}
        <div class="pos-catalog">

            {{-- Search & Barcode --}}
            <div class="flex flex-col md:flex-row gap-3 mb-4 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                <div class="flex-1">
                    <x-filament::input.wrapper>
                        <x-filament::input type="text" placeholder="Cari nama produk, SKU, atau barcode..." wire:model.live.debounce.400ms="searchQuery" />
                    </x-filament::input.wrapper>
                </div>
                <div style="width:220px; flex-shrink:0;">
                    <x-filament::input.wrapper>
                        <x-filament::input id="barcode-input-field" type="text" placeholder="Scan Barcode..." wire:model="barcodeInput" wire:keydown.enter="scanBarcode" autofocus autocomplete="off" />
                    </x-filament::input.wrapper>
                </div>
            </div>

            {{-- Category Pills --}}
            <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-4" style="scrollbar-width:thin;">
                <button wire:click="selectCategory(null)" class="px-4 py-2 text-xs font-semibold rounded-full transition shrink-0 {{ is_null($selectedCategory) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200' }}">
                    Semua Kategori
                </button>
                @foreach($this->categories as $category)
                    <button wire:click="selectCategory({{ $category->id }})" class="px-4 py-2 text-xs font-semibold rounded-full transition shrink-0 {{ $selectedCategory === $category->id ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200' }}">
                        {{ $category->nama }}
                    </button>
                @endforeach
            </div>

            {{-- Product Grid --}}
            <div class="pos-product-grid">
                @forelse($this->products as $product)
                    <div wire:click="addToCart({{ $product->id }})"
                         class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 hover:shadow-md transition duration-200 flex flex-col overflow-hidden cursor-pointer select-none"
                         style="min-height: 180px;">
                        {{-- Thumbnail --}}
                        @if($product->gambar)
                            <img src="{{ asset('storage/' . $product->gambar) }}" alt="{{ $product->nama }}" style="height:112px; width:100%; object-fit:cover;">
                        @else
                            <div class="{{ $this->getCategoryGradient($product->category_id) }} flex flex-col items-center justify-center text-white p-2" style="height:112px; width:100%;">
                                <span class="text-3xl font-extrabold opacity-90">{{ $this->getProductInitials($product->nama) }}</span>
                                <span class="text-[9px] uppercase font-bold opacity-60 mt-1">{{ $product->category?->nama ?? 'Produk' }}</span>
                            </div>
                        @endif
                        {{-- Info --}}
                        <div class="p-3 flex-1 flex flex-col justify-between gap-1">
                            <h4 class="font-bold text-sm text-gray-800 dark:text-gray-200 leading-tight line-clamp-2">{{ $product->nama }}</h4>
                            <span class="text-[10px] text-gray-400">{{ $product->sku }}</span>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-blue-600 dark:text-blue-400 font-extrabold text-sm">Rp {{ number_format($product->harga_jual, 0, ',', '.') }}</span>
                                @php $stock = $product->stocks->first()?->qty ?? 0; @endphp
                                <span class="text-[9px] px-2 py-0.5 rounded-full {{ $stock <= 5 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">Stok: {{ $stock }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center bg-white dark:bg-gray-800 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                        <span class="text-gray-400 text-sm">Tidak ada produk ditemukan.</span>
                    </div>
                @endforelse
            </div>

            <div class="mt-6">{{ $this->products->links() }}</div>
        </div>

        {{-- ============================
             RIGHT: Checkout Sidebar
        ============================ --}}
        <div class="pos-sidebar bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700" style="padding:16px;">

            {{-- Header --}}
            <div class="flex justify-between items-center pb-4 mb-4" style="border-bottom:1px solid #e5e7eb;">
                <h3 class="font-bold text-lg text-gray-900 dark:text-white">Keranjang Belanja</h3>
                <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full text-xs font-bold">{{ count($cart) }} Items</span>
            </div>

            {{-- Cart Items --}}
            <div class="pos-cart-scroll mb-4" style="display:flex; flex-direction:column; gap:10px;">
                @forelse($cart as $key => $item)
                    <div style="padding:12px; background:#f9fafb; border-radius:12px; border:1px solid #e5e7eb;">
                        {{-- Name + delete --}}
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:8px; margin-bottom:8px;">
                            <div style="min-width:0; flex:1;">
                                <div style="font-weight:700; font-size:13px; color:#111827; line-height:1.3; word-break:break-word;">{{ $item['nama'] }}</div>
                                <div style="font-size:11px; color:#9ca3af; margin-top:2px; font-family:monospace;">
                                    Rp {{ number_format($item['price'], 0, ',', '.') }} / {{ $item['unit_name'] }}
                                </div>
                            </div>
                            <button type="button" wire:click="removeItem({{ $item['product_id'] }})"
                                    style="color:#9ca3af; background:none; border:none; cursor:pointer; padding:2px; flex-shrink:0; border-radius:6px;"
                                    onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">
                                <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                        {{-- Qty + Subtotal --}}
                        <div style="display:flex; align-items:center; justify-content:space-between; padding-top:8px; border-top:1px solid #e5e7eb;">
                            <div style="display:flex; align-items:center; gap:4px; background:white; border:1px solid #e5e7eb; border-radius:8px; padding:2px;">
                                <button type="button" wire:click="updateQty({{ $item['product_id'] }}, {{ $item['qty'] - 1 }})" class="pos-qty-btn">-</button>
                                <span style="font-weight:800; font-size:13px; width:24px; text-align:center; color:#111827;">{{ $item['qty'] }}</span>
                                <button type="button" wire:click="updateQty({{ $item['product_id'] }}, {{ $item['qty'] + 1 }})" class="pos-qty-btn">+</button>
                            </div>
                            <span style="font-weight:800; font-size:14px; color:#2563eb; font-family:monospace; white-space:nowrap;">
                                Rp {{ number_format($item['qty'] * $item['price'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div style="padding:48px 0; text-align:center; color:#9ca3af;">
                        <svg style="width:48px;height:48px;margin:0 auto 12px;display:block;color:#d1d5db;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                        </svg>
                        <p style="font-size:13px; font-weight:600; color:#6b7280;">Keranjang belanja kosong</p>
                        <p style="font-size:11px; color:#9ca3af; margin-top:4px;">Pilih produk atau scan barcode.</p>
                    </div>
                @endforelse
            </div>

            {{-- Billing Summary --}}
            <div style="border-top:1px solid #e5e7eb; padding-top:16px; display:flex; flex-direction:column; gap:10px;">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                    <span>Subtotal</span>
                    <span class="font-semibold">Rp {{ number_format($this->cartSubtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Diskon (%)</span>
                    <x-filament::input.wrapper class="w-20">
                        <x-filament::input type="number" wire:model.live.debounce.300ms="discount" class="text-right py-1 text-xs" min="0" max="100" />
                    </x-filament::input.wrapper>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Pajak (%)</span>
                    <x-filament::input.wrapper class="w-20">
                        <x-filament::input type="number" wire:model.live.debounce.300ms="taxPercent" class="text-right py-1 text-xs" min="0" max="100" />
                    </x-filament::input.wrapper>
                </div>
                <div class="flex justify-between font-black text-xl pt-3 border-t dark:border-gray-700">
                    <span class="text-gray-900 dark:text-white">TOTAL</span>
                    <span class="text-blue-600 dark:text-blue-400">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
                </div>
                <div class="pt-2">
                    <x-filament::button wire:click="showPaymentModal" color="success" class="w-full shadow-md font-bold py-3 text-sm rounded-xl transition" size="lg" :disabled="empty($cart)">
                        Bayar
                    </x-filament::button>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================
         PAYMENT MODAL - Premium UX with Blur Backdrop
    ==================================================== -->
    @if($isPaymentModalOpen)
    <style>
        /* toast (fi-no z-50) must sit above this blur overlay */
        .fi-no { z-index: 10050 !important; }
        .pay-overlay { position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,0.55);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);animation:overlayIn .2s ease-out }
        @keyframes overlayIn { from{opacity:0} to{opacity:1} }
        .pay-modal { background:#fff;border-radius:20px;width:100%;max-width:420px;box-shadow:0 25px 60px rgba(0,0,0,0.35);overflow:hidden;animation:modalIn .25s cubic-bezier(.34,1.56,.64,1) }
        @keyframes modalIn { from{opacity:0;transform:scale(.92) translateY(12px)} to{opacity:1;transform:scale(1) translateY(0)} }
        .pay-header { display:flex;align-items:center;justify-content:space-between;padding:18px 20px 14px;border-bottom:1px solid #f1f5f9 }
        .pay-title { font-size:16px;font-weight:800;color:#0f172a;letter-spacing:-.3px }
        .pay-close { background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;width:30px;height:30px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#64748b;transition:all .15s }
        .pay-close:hover { background:#fee2e2;color:#ef4444;border-color:#fecaca }
        .pay-body { padding:16px 20px;display:flex;flex-direction:column;gap:14px }
        .pay-total-banner { background:linear-gradient(135deg,#1d4ed8,#2563eb);border-radius:14px;padding:14px 18px;display:flex;align-items:center;justify-content:space-between }
        .pay-total-label { font-size:12px;font-weight:600;color:rgba(255,255,255,.75) }
        .pay-total-amount { font-size:22px;font-weight:900;color:#fff;font-family:monospace;letter-spacing:-.5px }
        .pay-section-label { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#94a3b8;margin-bottom:6px }
        .pay-method-grid { display:grid;grid-template-columns:1fr 1fr;gap:8px }
        .pay-method-btn { display:flex;align-items:center;justify-content:center;gap:8px;padding:10px 12px;background:#f8fafc;border:2px solid #e2e8f0;border-radius:10px;font-weight:700;font-size:13px;color:#475569;cursor:pointer;width:100%;transition:all .12s }
        .pay-method-btn.active { background:#eff6ff;border-color:#2563eb;color:#1d4ed8 }
        .pay-rfid-info { background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:12px 14px;display:flex;flex-direction:column;gap:4px }
        .pay-rfid-name { font-size:14px;font-weight:800;color:#0c4a6e }
        .pay-rfid-balance { font-size:13px;font-weight:700;color:#0369a1;font-family:monospace }
        .pay-cash-grid { display:grid;grid-template-columns:repeat(3,1fr);gap:6px }
        .pay-cash-btn { padding:8px 4px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;font-weight:700;color:#374151;cursor:pointer;text-align:center;transition:all .12s }
        .pay-cash-btn:hover { background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8 }
        .pay-change-box { background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between }
        .pay-change-label { font-size:12px;font-weight:600;color:#15803d }
        .pay-change-amount { font-size:20px;font-weight:900;color:#16a34a;font-family:monospace }
        .pay-footer { display:flex;gap:10px;padding:14px 20px 18px;border-top:1px solid #f1f5f9 }
        .pay-btn-cancel { flex:1;padding:10px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;font-weight:700;font-size:13px;color:#475569;cursor:pointer;transition:all .12s }
        .pay-btn-cancel:hover { background:#e2e8f0 }
        .pay-btn-process { flex:2;padding:10px;background:linear-gradient(135deg,#16a34a 0,#15803d 100%);border:none;border-radius:10px;font-weight:800;font-size:14px;color:#fff;cursor:pointer;transition:all .15s;box-shadow:0 4px 12px rgba(22,163,74,.35) }
        .pay-btn-process:hover { transform:translateY(-1px);box-shadow:0 6px 16px rgba(22,163,74,.4) }
        .pay-btn-process:disabled { background:#d1fae5;color:#6ee7b7;box-shadow:none;cursor:not-allowed;transform:none }
    </style>
    <div class="pay-overlay" wire:click.self="closePaymentModal">
        <div class="pay-modal">
            <div class="pay-header">
                <div class="pay-title">Proses Pembayaran</div>
                <button type="button" class="pay-close" wire:click="closePaymentModal" aria-label="Tutup">
                    <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="pay-body">
                <div class="pay-total-banner">
                    <div class="pay-total-label">Total Tagihan</div>
                    <div class="pay-total-amount">Rp {{ number_format($this->total, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="pay-section-label">Metode Pembayaran</div>
                    <div class="pay-method-grid">
                        <button type="button" class="pay-method-btn {{ $paymentMethod === 'tunai' ? 'active' : '' }}" wire:click="setPaymentMethod('tunai')">
                            <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Tunai
                        </button>
                        <button type="button" class="pay-method-btn {{ $paymentMethod === 'rfid' ? 'active' : '' }}" wire:click="setPaymentMethod('rfid')">
                            <svg style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            RFID
                        </button>
                    </div>
                </div>

                @if($paymentMethod === 'tunai')
                <div>
                    <div class="pay-section-label">Uang Diterima (Rp)</div>
                    <x-filament::input.wrapper>
                        <x-filament::input type="number" wire:model.live="cashAmountReceived" class="text-right text-xl font-black py-2" min="0" step="500" />
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <div class="pay-section-label">Nominal Cepat</div>
                    <div class="pay-cash-grid">
                        <button type="button" class="pay-cash-btn" wire:click="payExact">Uang Pas</button>
                        <button type="button" class="pay-cash-btn" wire:click="setCashReceived(10000)">10.000</button>
                        <button type="button" class="pay-cash-btn" wire:click="setCashReceived(20000)">20.000</button>
                        <button type="button" class="pay-cash-btn" wire:click="setCashReceived(50000)">50.000</button>
                        <button type="button" class="pay-cash-btn" wire:click="setCashReceived(100000)">100.000</button>
                        <button type="button" class="pay-cash-btn" wire:click="setCashReceived(200000)">200.000</button>
                    </div>
                </div>
                <div class="pay-change-box">
                    <div class="pay-change-label">Kembalian</div>
                    <div class="pay-change-amount">Rp {{ number_format($this->changeAmount, 0, ',', '.') }}</div>
                </div>
                @else
                <div>
                    <div class="pay-section-label">Tap / Scan Kartu RFID</div>
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="rfidUid"
                            wire:keydown.enter="lookupRfid"
                            id="rfid-input"
                            placeholder="Tap kartu di reader..."
                            autocomplete="off"
                            class="font-mono text-lg py-2"
                            x-on:focus="$el.select()"
                            x-on:click="$el.select()"
                        />
                    </x-filament::input.wrapper>
                    <p style="font-size:11px;color:#94a3b8;margin-top:6px;">Reader ketik UID + Enter otomatis</p>
                </div>
                @if($rfidWallet)
                <div class="pay-rfid-info">
                    <div class="pay-rfid-name">{{ $rfidWallet['employee_name'] }}</div>
                    <div class="pay-rfid-balance">Saldo: Rp {{ number_format($rfidWallet['balance'], 0, ',', '.') }}</div>
                    @if($rfidWallet['balance'] < $this->total)
                        <div style="font-size:12px;font-weight:700;color:#dc2626;margin-top:4px;">Saldo tidak cukup untuk total tagihan</div>
                    @else
                        <div style="font-size:12px;font-weight:600;color:#15803d;margin-top:4px;">Sisa setelah bayar: Rp {{ number_format($rfidWallet['balance'] - $this->total, 0, ',', '.') }}</div>
                    @endif
                </div>
                @endif
                @endif
            </div>
            <div class="pay-footer">
                <button type="button" class="pay-btn-cancel" wire:click="closePaymentModal">Batal</button>
                @php
                    $canPay = $paymentMethod === 'tunai'
                        ? $cashAmountReceived >= $this->total
                        : ($rfidWallet && $rfidWallet['balance'] >= $this->total);
                @endphp
                <button type="button" class="pay-btn-process" wire:click="checkout" @if(! $canPay) disabled @endif>Proses Pembayaran</button>
            </div>
        </div>
    </div>
    @endif

    <!-- =============================================
         2. RECEIPT MODAL - Livewire Property Based
    ============================================= -->
    @if($isReceiptModalOpen && $receiptSaleId)
        @php
            $sale = \App\Models\Sale::with('items.product', 'outlet')->find($receiptSaleId);
        @endphp
        @if($sale)
        <style>
            .fi-no { z-index: 10050 !important; }
            .receipt-overlay { position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:16px;background:rgba(0,0,0,.6);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);animation:overlayIn .2s ease-out }
            .receipt-modal { background:#fff;border-radius:20px;width:100%;max-width:480px;box-shadow:0 25px 60px rgba(0,0,0,.35);overflow:hidden;animation:modalIn .25s cubic-bezier(.34,1.56,.64,1);display:flex;flex-direction:column;max-height:90vh }
            .receipt-header { display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f1f5f9;flex-shrink:0 }
            .receipt-success-badge { display:flex;align-items:center;gap:8px;font-size:16px;font-weight:800;color:#0f172a }
            .receipt-body { padding:16px 20px;overflow-y:auto;flex:1 }
            .receipt-footer { display:flex;gap:10px;padding:12px 20px 16px;border-top:1px solid #f1f5f9;flex-shrink:0 }
            .receipt-btn-close { flex:1;padding:10px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:10px;font-weight:700;font-size:13px;color:#475569;cursor:pointer;transition:all .12s }
            .receipt-btn-close:hover { background:#e2e8f0 }
            .receipt-btn-print { flex:2;padding:10px;background:linear-gradient(135deg,#1d4ed8,#2563eb);border:none;border-radius:10px;font-weight:800;font-size:14px;color:#fff;cursor:pointer;transition:all .15s;box-shadow:0 4px 12px rgba(37,99,235,.35);display:flex;align-items:center;justify-content:center;gap:6px }
            .receipt-btn-print:hover { transform:translateY(-1px);box-shadow:0 6px 16px rgba(37,99,235,.4) }
            #print-area { font-family:'Courier New',Courier,monospace;font-size:11px;line-height:1.4;color:#111;width:210px }
        </style>
        <div class="receipt-overlay">
            <div class="receipt-modal">
                <div class="receipt-header">
                    <div class="receipt-success-badge">
                        <span style="width:28px;height:28px;background:#dcfce7;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:14px;color:#16a34a;font-weight:900;">&#10003;</span>
                        Transaksi Berhasil!
                    </div>
                    <button type="button" class="pay-close" wire:click="closeReceiptModal" aria-label="Tutup">
                        <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="receipt-body">
                    <div style="background:#f8fafc;border-radius:12px;padding:16px;display:flex;justify-content:center;border:1px solid #e2e8f0;">
                        <div id="print-area">
                            <div style="text-align:center;margin-bottom:8px;">
                                <div style="font-weight:900;font-size:12px;text-transform:uppercase;">{{ $sale->outlet->nama }}</div>
                                <div style="font-size:9px;color:#555;margin-top:2px;">{{ $sale->outlet->alamat }}<br>Tlp: {{ $sale->outlet->telepon }}</div>
                            </div>
                            <div style="border-top:1px dashed #999;border-bottom:1px dashed #999;padding:3px 0;margin-bottom:6px;text-align:center;font-weight:700;font-size:10px;">STRUK PEMBELIAN (POS)</div>
                            <div style="font-size:10px;margin-bottom:6px;">
                                <div>No : {{ $sale->invoice_number }}</div>
                                <div>Kasir: {{ Auth::user()->name }}</div>
                                <div>Tgl  : {{ $sale->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <div style="border-top:1px dashed #999;margin:4px 0;"></div>
                            <div style="margin-bottom:6px;">
                                @foreach($sale->items as $item)
                                    <div style="margin-bottom:4px;">
                                        <div style="font-weight:700;font-size:10px;">{{ $item->product->nama }}</div>
                                        <div style="display:flex;justify-content:space-between;font-size:9px;color:#444;">
                                            <span>{{ $item->qty }} x {{ number_format($item->harga_satuan, 0, ',', '.') }}</span>
                                            <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div style="border-top:1px dashed #999;margin:4px 0;"></div>
                            <div style="font-size:10px;">
                                <div style="display:flex;justify-content:space-between;"><span>Subtotal</span><span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span></div>
                                @if($sale->discount > 0)<div style="display:flex;justify-content:space-between;color:#dc2626;"><span>Diskon</span><span>-Rp {{ number_format($sale->discount, 0, ',', '.') }}</span></div>@endif
                                @if($sale->tax > 0)<div style="display:flex;justify-content:space-between;"><span>Pajak</span><span>Rp {{ number_format($sale->tax, 0, ',', '.') }}</span></div>@endif
                                <div style="display:flex;justify-content:space-between;font-weight:900;border-top:1px solid #ccc;padding-top:3px;font-size:11px;"><span>TOTAL</span><span>Rp {{ number_format($sale->total, 0, ',', '.') }}</span></div>
                                <div style="border-top:1px dashed #ccc;margin-top:3px;padding-top:3px;">
                                    @php $payMethod = $sale->payments->first()?->payment_method ?? 'tunai'; @endphp
                                    <div style="display:flex;justify-content:space-between;"><span>{{ $payMethod === 'rfid' ? 'RFID' : 'Tunai' }}</span><span>Rp {{ number_format($receiptCashReceived, 0, ',', '.') }}</span></div>
                                    @if($payMethod !== 'rfid')
                                    <div style="display:flex;justify-content:space-between;font-weight:700;"><span>Kembalian</span><span>Rp {{ number_format($receiptChangeReturned, 0, ',', '.') }}</span></div>
                                    @endif
                                </div>
                            </div>
                            <div style="border-top:1px dashed #999;margin:6px 0;"></div>
                            <div style="text-align:center;font-size:9px;color:#555;line-height:1.5;">
                                * Terima Kasih Sudah Berbelanja *<br>
                                Barang yang sudah dibeli tidak dapat<br>
                                ditukar / dikembalikan.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="receipt-footer">
                    <button type="button" class="receipt-btn-close" wire:click="closeReceiptModal">Selesai</button>
                    <button type="button" class="receipt-btn-print" onclick="window.printStruk()">
                        <svg style="width:15px;height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print Struk
                    </button>
                </div>
            </div>
        </div>
        @endif
    @endif

</x-filament-panels::page>

<script>
window.printStruk = function() {
    'use strict';
    var area = document.getElementById('print-area');
    if (!area) return;
    
    var style = 'body{font-family:"Courier New",Courier,monospace;font-size:11px;padding:4px 6px;margin:0;line-height:1.4}@page{size:58mm auto;margin:0}';
    // Split closing tags to prevent Livewire from injecting assets inside the JS string
    var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>' + style + '</' + 'style></' + 'head><body>' + area.innerHTML + '</' + 'body></' + 'html>';
    
    var iframe = document.createElement("iframe");
    iframe.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:220px;height:600px;border:0;visibility:hidden;';
    document.body.appendChild(iframe);
    
    var doc = iframe.contentDocument || iframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();
    
    setTimeout(function() {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (e) {
            console.error("Gagal mencetak struk:", e);
        }
        
        // Bersihkan iframe dari DOM setelah selesai untuk menghindari memory leak
        setTimeout(function() {
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }, 1000);
    }, 500);
};
</script>
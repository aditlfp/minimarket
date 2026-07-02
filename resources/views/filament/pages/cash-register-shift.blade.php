<x-filament-panels::page>
    <style>
        .shift-container {
            max-width: 640px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .premium-card {
            background: var(--tw-bg-opacity, white);
            border-radius: 16px;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .dark .premium-card {
            background: #1f2937;
            border-color: #374151;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }
        .stat-card {
            border-radius: 12px;
            padding: 16px;
            border: 1px solid rgba(229, 231, 235, 0.6);
            display: flex;
            flex-direction: column;
            gap: 4px;
            transition: all 0.2s;
        }
        .dark .stat-card {
            border-color: rgba(55, 65, 81, 0.6);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .pulse-live {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            animation: pulse-animation 1.5s infinite;
        }
        @keyframes pulse-animation {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
    </style>

    <div class="shift-container">
        @if($activeShift)
            <!-- Active Shift Overview -->
            <div class="premium-card">
                <div class="flex items-center justify-between pb-4 mb-6 border-b border-gray-100 dark:border-gray-700">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Shift Kasir Aktif</h3>
                        <p class="text-xs text-gray-500 mt-1">Dimulai sejak: {{ $activeShift->created_at->format('d M Y — H:i') }}</p>
                    </div>
                    <div class="flex items-center gap-2 px-3 py-1 bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 rounded-full text-xs font-bold border border-emerald-100 dark:border-emerald-900/50">
                        <span class="pulse-live"></span>
                        LIVE
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <!-- Saldo Awal -->
                    <div class="stat-card bg-gray-50/50 dark:bg-gray-800/40">
                        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo Awal (Modal)</span>
                        <span class="text-xl font-black text-gray-900 dark:text-white font-mono">Rp {{ number_format($activeShift->opening_balance, 0, ',', '.') }}</span>
                    </div>

                    <!-- Total Penjualan -->
                    <div class="stat-card bg-emerald-50/20 dark:bg-emerald-950/10 border-emerald-100/50 dark:border-emerald-900/30">
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Total Penjualan</span>
                        <span class="text-xl font-black text-emerald-600 dark:text-emerald-400 font-mono">Rp {{ number_format($activeShift->totalSales(), 0, ',', '.') }}</span>
                    </div>

                    <!-- Kas Masuk/Keluar -->
                    <div class="stat-card bg-blue-50/20 dark:bg-blue-950/10 border-blue-100/50 dark:border-blue-900/30">
                        <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Kas Masuk / Keluar</span>
                        <span class="text-xl font-black text-blue-600 dark:text-blue-400 font-mono">
                            @php $cashFlow = $activeShift->totalCashInOut(); @endphp
                            {{ $cashFlow >= 0 ? '+' : '' }}Rp {{ number_format($cashFlow, 0, ',', '.') }}
                        </span>
                    </div>

                    <!-- Saldo Seharusnya -->
                    <div class="stat-card bg-amber-50/20 dark:bg-amber-950/10 border-amber-100/50 dark:border-amber-900/30">
                        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Saldo Seharusnya</span>
                        <span class="text-xl font-black text-amber-600 dark:text-amber-400 font-mono">Rp {{ number_format($activeShift->expectedBalance(), 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Tutup Shift Form -->
                <div class="p-5 bg-gray-50/30 dark:bg-gray-800/20 rounded-xl border border-gray-100 dark:border-gray-700/50">
                    <h4 class="font-bold text-sm text-gray-800 dark:text-gray-200 mb-4">Penyelesaian Shift Kasir</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Masukkan Saldo Fisik Laci Kasir (Rp)</label>
                            <x-filament::input.wrapper>
                                <x-filament::input type="number" wire:model="actualCash" placeholder="Hitung uang fisik di laci dan masukkan di sini..." class="font-black text-lg py-2" />
                            </x-filament::input.wrapper>
                        </div>
                        
                        <p class="text-xs text-gray-400">Pastikan Anda telah menghitung seluruh uang tunai fisik yang ada di laci mesin kasir dengan teliti sebelum menekan tombol Tutup Shift.</p>
                        
                        <div class="pt-2">
                            <x-filament::button color="warning" wire:click="closeShift" class="w-full font-bold py-3 text-sm rounded-xl shadow-md transition duration-150">
                                Tutup Shift Kasir
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Buka Shift Form -->
            <div class="premium-card">
                <div class="text-center pb-4 mb-6 border-b border-gray-100 dark:border-gray-700">
                    <div class="w-16 h-16 bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mx-auto mb-4 border border-green-100 dark:border-green-900/50 shadow-sm">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615 3.001 3.001 0 0 0 3.75.615m-7.5 0h7.5m3-3.75h.008v.008H18V5.25m-9 13.5h.008v.008H9v-.008Zm0-3h.008v.008H9v-.008Zm0-3h.008v.008H9v-.008Zm3 3h.008v.008h-.008v-.008Zm0-3h.008v.008h-.008v-.008Zm0-3h.008v.008h-.008v-.008Zm3 3h.008v.008h-.008v-.008Zm0-3h.008v.008h-.008v-.008Zm0-3h.008v.008h-.008v-.008Z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">Buka Shift Kasir Baru</h3>
                    <p class="text-sm text-gray-500 mt-2">Anda wajib membuka sesi laci kasir terlebih dahulu sebelum dapat mengakses menu Point of Sale (POS).</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Saldo Awal Modal Kasir (Rp)</label>
                        <x-filament::input.wrapper>
                            <x-filament::input type="number" wire:model="openingBalance" placeholder="Masukkan saldo awal kas..." class="font-black text-lg py-2" />
                        </x-filament::input.wrapper>
                        <span class="text-[10px] text-gray-400 mt-1 block">Biasanya merupakan nominal uang pecahan kecil untuk uang kembalian transaksi awal.</span>
                    </div>

                    <div class="pt-2">
                        <x-filament::button color="success" wire:click="openShift" class="w-full font-bold py-3 text-sm rounded-xl shadow-md transition duration-150">
                            Buka Shift Kasir & Mulai Transaksi
                        </x-filament::button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>

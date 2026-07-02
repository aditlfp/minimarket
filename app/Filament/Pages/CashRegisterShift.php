<?php

namespace App\Filament\Pages;

use App\Models\CashRegister;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CashRegisterShift extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?string $navigationLabel = 'Shift Kasir';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.cash-register-shift';

    public ?CashRegister $activeShift = null;
    public float $openingBalance = 0;
    public float $actualCash = 0;

    public function mount(): void
    {
        $user = Auth::user();
        $this->activeShift = CashRegister::where('user_id', $user->id)
            ->where('outlet_id', $user->outlet_id)
            ->where('status', 'open')->first();
    }

    public function openShift(): void
    {
        $user = Auth::user();
        if (! $user->outlet_id) { Notification::make()->title('User harus memiliki outlet')->danger()->send(); return; }
        CashRegister::create([
            'outlet_id' => $user->outlet_id, 'user_id' => $user->id,
            'opening_balance' => $this->openingBalance, 'opened_at' => now(), 'status' => 'open',
        ]);
        Notification::make()->title('Shift berhasil dibuka')->success()->send();
        $this->redirect(PointOfSale::getUrl());
    }

    public function closeShift(): void
    {
        if (! $this->activeShift) return;
        $this->activeShift->update(['closing_balance' => $this->actualCash, 'closed_at' => now(), 'status' => 'closed']);
        Notification::make()->title('Shift berhasil ditutup')->success()->send();
        $this->activeShift = null;
    }
}

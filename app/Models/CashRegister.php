<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    protected $fillable = [
        'outlet_id',
        'user_id',
        'opening_balance',
        'closing_balance',
        'opened_at',
        'closed_at',
        'status',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CashRegisterTransaction::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function totalSales(): mixed
    {
        return $this->sales()->sum('total');
    }

    public function totalCashInOut(): mixed
    {
        return $this->transactions()
            ->whereIn('type', ['cash_in', 'cash_out'])
            ->sum(\DB::raw("CASE WHEN type = 'cash_in' THEN amount WHEN type = 'cash_out' THEN -amount ELSE 0 END"));
    }

    public function expectedBalance(): mixed
    {
        return $this->opening_balance + $this->totalSales() + $this->totalCashInOut();
    }
}

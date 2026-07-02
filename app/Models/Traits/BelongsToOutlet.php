<?php

namespace App\Models\Traits;

use App\Models\Outlet;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToOutlet
{
    protected static function bootBelongsToOutlet(): void
    {
        static::addGlobalScope('outlet', function (Builder $builder) {
            $user = Auth::user();
            if (!$user) return;
            if ($user->hasRole('admin')) return;
            if ($user->outlet_id) {
                $builder->where(is_array($builder->getModel()->getTable()) ? $builder->getModel()->getTable() . '.outlet_id' : 'outlet_id', $user->outlet_id);
            }
        });
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}

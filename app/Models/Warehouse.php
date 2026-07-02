<?php

namespace App\Models;

use App\Models\Traits\BelongsToOutlet;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use BelongsToOutlet;

    protected $fillable = [
        'nama',
        'outlet_id',
        'tipe',
    ];
}

<?php
namespace App\Services;
use App\Models\Product;
use App\Models\ProductUnit;

class UnitConversionService
{
    public function toBaseUnit(Product $product, ProductUnit $unit, float $qty): float
    {
        if ($unit->id === $product->base_unit_id) return $qty;
        $conversion = $product->conversions()->where('unit_id', $unit->id)->first();
        if (!$conversion) throw new \InvalidArgumentException("Konversi satuan {$unit->nama} ke base unit tidak ditemukan.");
        return $qty * (float) $conversion->conversion_qty;
    }

    public function fromBaseUnit(Product $product, ProductUnit $unit, float $qty): float
    {
        if ($unit->id === $product->base_unit_id) return $qty;
        $conversion = $product->conversions()->where('unit_id', $unit->id)->first();
        if (!$conversion) throw new \InvalidArgumentException("Konversi base unit ke satuan {$unit->nama} tidak ditemukan.");
        return $qty / (float) $conversion->conversion_qty;
    }
}

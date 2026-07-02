<?php
namespace App\Services;
use App\Models\Product;

class BarcodeService
{
    public function generate(Product $product, ?string $prefix = 'INT-'): string
    {
        if ($product->barcode) return $product->barcode;
        $barcode = $prefix . str_pad((string)$product->id, 6, '0', STR_PAD_LEFT);
        $attempts = 0;
        while (Product::where('barcode', $barcode)->where('id', '!=', $product->id)->exists() && $attempts < 10) {
            $barcode = $prefix . str_pad((string)$product->id, 6, '0', STR_PAD_LEFT) . chr(65 + $attempts);
            $attempts++;
        }
        $product->update(['barcode' => $barcode]);
        return $barcode;
    }

    public function findByBarcode(string $code): ?Product
    {
        return Product::where('barcode', $code)->first();
    }
}

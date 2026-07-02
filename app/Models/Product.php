<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'nama',
        'sku',
        'barcode',
        'category_id',
        'base_unit_id',
        'harga_beli',
        'harga_jual',
        'gambar',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Product $product) {
            if (!$product->wasChanged('gambar') || !$product->gambar) {
                return;
            }

            $gambar = $product->gambar;

            // Skip if already WebP
            if (str_ends_with($gambar, '.webp')) {
                return;
            }

            $disk = Storage::disk('public');

            if (!$disk->exists($gambar)) {
                return;
            }

            $fullPath = $disk->path($gambar);
            $image = @imagecreatefromstring(file_get_contents($fullPath));
            if (!$image) {
                return;
            }

            // Preserve alpha for PNG
            imagealphablending($image, true);
            imagesavealpha($image, true);

            // Build WebP path
            $dirName = pathinfo($gambar, PATHINFO_DIRNAME);
            $baseName = pathinfo($gambar, PATHINFO_FILENAME);
            $webpPath = ($dirName && $dirName !== '.' ? $dirName . '/' : '') . $baseName . '.webp';
            $webpFullPath = $disk->path($webpPath);

            // Convert to WebP quality 80, delete old file, update DB
            if (imagewebp($image, $webpFullPath, 80)) {
                $disk->delete($gambar);
                Product::withoutEvents(fn () => $product->updateQuietly(['gambar' => $webpPath]));
            }

            imagedestroy($image);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'base_unit_id');
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(ProductUnitConversion::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }
}

<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;

class CetakLabel extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ProductResource::class;
    protected static string $view = 'filament.resources.product-resource.pages.cetak-label';

    public array $items = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Repeater::make('items')
                ->schema([
                    Select::make('product_id')
                        ->label('Produk')
                        ->relationship('product', 'nama')
                        ->searchable()->preload()->required(),
                    TextInput::make('qty')
                        ->label('Jumlah Label')
                        ->numeric()->default(1)->minValue(1)->required(),
                ])
                ->defaultItems(1)->createItemButtonLabel('Tambah Produk'),
        ];
    }

    public function getProducts(): array
    {
        $products = [];
        foreach ($this->items as $item) {
            $product = Product::with('baseUnit')->find($item['product_id'] ?? 0);
            if ($product && $product->barcode) {
                $products[] = ['product' => $product, 'qty' => (int) ($item['qty'] ?? 1)];
            }
        }
        return $products;
    }
}

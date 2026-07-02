<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Services\BarcodeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Unique;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('barcode')
                            ->unique(ignoreRecord: true)
                            ->nullable()
                            ->maxLength(255),
                        Forms\Components\Select::make('category_id')
                            ->relationship('category', 'nama')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('base_unit_id')
                            ->relationship('baseUnit', 'nama')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Satuan Dasar'),
                    ]),
                Forms\Components\Section::make('Harga')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('harga_beli')
                            ->numeric()
                            ->prefix('Rp')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('harga_jual')
                            ->numeric()
                            ->prefix('Rp')
                            ->maxLength(255),
                    ]),
                Forms\Components\Section::make('Lainnya')
                    ->schema([
                        Forms\Components\FileUpload::make('gambar')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->imagePreviewHeight(200)
                            ->maxSize(2048),
                        Forms\Components\Toggle::make('is_active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('gambar')
                    ->label('Gambar')
                    ->disk('public')
                    ->circular()
                    ->size(48)
                    ->extraImgAttributes(['loading' => 'lazy']),
                Tables\Columns\TextColumn::make('nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sku')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('barcode')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('category.nama'),
                Tables\Columns\TextColumn::make('baseUnit.nama')
                    ->label('Satuan'),
                Tables\Columns\TextColumn::make('harga_beli')
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('harga_jual')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'nama')
                    ->label('Kategori'),
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([
                        true => 'Active',
                        false => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('generateBarcode')
                    ->label('Generate Barcode')
                    ->icon('heroicon-o-qr-code')
                    ->action(function (Product $record, BarcodeService $barcodeService) {
                        $barcodeService->generate($record);
                        Notification::make()
                            ->title('Barcode berhasil digenerate: ' . $record->barcode)
                            ->success()
                            ->send();
                    })
                    ->visible(fn(Product $record): bool => is_null($record->barcode)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'cetak-label' => Pages\CetakLabel::route('/cetak-label'),
        ];
    }
}

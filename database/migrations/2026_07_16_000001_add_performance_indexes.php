<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('nama');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index(['outlet_id', 'status', 'created_at']);
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->index(['user_id', 'outlet_id', 'status']);
        });

        Schema::table('stock', function (Blueprint $table) {
            $table->index('qty');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->index(['outlet_id', 'tipe']);
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->index(['product_id', 'warehouse_id', 'expired_date']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['nama']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['outlet_id', 'status', 'created_at']);
        });

        Schema::table('cash_registers', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'outlet_id', 'status']);
        });

        Schema::table('stock', function (Blueprint $table) {
            $table->dropIndex(['qty']);
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropIndex(['outlet_id', 'tipe']);
        });

        Schema::table('product_batches', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'warehouse_id', 'expired_date']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
        });
    }
};

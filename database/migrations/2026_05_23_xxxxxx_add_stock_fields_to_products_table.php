<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_quantity')->default(0)->after('in_stock');
            $table->integer('low_stock_threshold')->default(5)->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'low_stock_threshold']);
        });
    }
};
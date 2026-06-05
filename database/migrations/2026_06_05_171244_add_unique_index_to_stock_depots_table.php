<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_depots', function (Blueprint $table) {
            $table->unique(['shop_id', 'part_id', 'depot_id'], 'stock_depots_shop_part_depot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stock_depots', function (Blueprint $table) {
            $table->dropUnique('stock_depots_shop_part_depot_unique');
        });
    }
};

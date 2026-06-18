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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('shop_id')->after('id')->nullable()->constrained('shops')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('password');
            $table->foreignId("depot_active_id")->constrained("depots")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['shop_id']);
            $table->dropForeign(['depot_active_id']);
            $table->dropColumn(['shop_id', 'is_active', 'depot_active_id']);
        });
    }
};

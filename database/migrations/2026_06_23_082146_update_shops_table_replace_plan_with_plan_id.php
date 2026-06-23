<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Garantit l'existence des offres de référence avant le remappage,
        // même si PlanSeeder n'a pas encore tourné sur cet environnement.
        foreach (['starter', 'pro', 'enterprise'] as $slug) {
            DB::table('plans')->insertOrIgnore([
                'name' => ucfirst($slug),
                'slug' => $slug,
                'price' => 0,
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('plan')->constrained('plans')->nullOnDelete();
        });

        $plans = DB::table('plans')->pluck('id', 'slug');

        foreach ($plans as $slug => $id) {
            DB::table('shops')->where('plan', $slug)->update(['plan_id' => $id]);
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->enum('plan', ['starter', 'pro', 'enterprise'])->default('starter')->after('address');
        });

        $plans = DB::table('plans')->pluck('slug', 'id');

        foreach ($plans as $id => $slug) {
            DB::table('shops')->where('plan_id', $id)->update(['plan' => $slug]);
        }

        Schema::table('shops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
        });
    }
};

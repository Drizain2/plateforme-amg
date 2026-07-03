<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->string('billing_period'); // monthly | annual
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('active'); // trial|active|expired|cancelled|suspended
            // Champ gateway : permet de savoir par quelle passerelle l'abonnement
            // a été créé. 'manual' = validation admin. Future : 'paydunya', 'wave'…
            $table->string('gateway')->default('manual');
            $table->string('gateway_subscription_id')->nullable(); // ID côté passerelle externe
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

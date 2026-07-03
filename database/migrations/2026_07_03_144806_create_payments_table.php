<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            // Nullable : le paiement existe avant que l'abonnement soit créé
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->constrained(); // dénormalisé pour l'historique
            $table->string('billing_period'); // monthly | annual
            $table->unsignedInteger('amount'); // montant en XOF (ou devise de base)
            $table->string('currency', 3)->default('XOF');
            $table->string('reference')->unique(); // ex: PAY-2026-00001
            $table->string('status')->default('pending'); // pending|validated|rejected|refunded
            // Champs gateway — restent null pour les paiements manuels,
            // remplis automatiquement quand une passerelle externe est utilisée.
            $table->string('gateway')->default('manual');
            $table->string('gateway_payment_id')->nullable();
            $table->json('gateway_response')->nullable(); // payload brut du webhook
            // Traitement manuel
            $table->text('notes')->nullable(); // instructions de virement ou commentaires
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

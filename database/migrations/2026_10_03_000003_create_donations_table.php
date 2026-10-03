<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('donation_category_id')->constrained('donation_categories')->restrictOnDelete();

            $table->string('tran_id', 64)->unique();
            $table->string('session_key', 100)->nullable()->index();
            $table->string('gateway', 40)->default('sslcommerz')->index();
            $table->string('payment_method', 80)->nullable()->index();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('status', 30)->default('pending')->index();

            $table->string('donor_name', 120);
            $table->string('donor_email', 255)->nullable();
            $table->string('donor_phone', 30)->nullable();
            $table->text('message')->nullable();

            $table->string('validation_id', 80)->nullable()->unique();
            $table->string('bank_tran_id', 100)->nullable()->index();
            $table->string('card_type', 100)->nullable();
            $table->string('card_brand', 50)->nullable();
            $table->string('card_issuer', 120)->nullable();
            $table->decimal('store_amount', 12, 2)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['donation_category_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
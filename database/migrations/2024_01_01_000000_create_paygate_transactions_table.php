<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paygate_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('tx_reference')->unique()->index();
            $table->string('identifier')->index();
            $table->string('payment_reference')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('phone_number');
            $table->enum('network', ['FLOOZ', 'TMONEY']);
            $table->enum('payment_method', ['FLOOZ', 'TMONEY'])->nullable();
            $table->text('description')->nullable();
            $table->integer('status')->default(2);
            $table->timestamp('payment_datetime')->nullable();
            $table->json('webhook_payload')->nullable();
            $table->timestamps();

            $table->index(['identifier', 'status']);
            $table->index(['phone_number', 'network']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paygate_transactions');
    }
};
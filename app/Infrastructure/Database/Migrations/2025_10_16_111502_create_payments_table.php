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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('payment_method'); // iyzico, paytr
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->string('transaction_id')->nullable()->unique();
            $table->text('error_message')->nullable();
            $table->integer('attempt_number')->default(1);
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['order_id', 'status']);
            $table->index('transaction_id');
            $table->index(['order_id', 'attempt_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

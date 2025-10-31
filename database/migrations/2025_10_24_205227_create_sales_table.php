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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Staff que hizo la venta
            $table->foreignId('patient_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'card', 'transfer', 'other'])->default('cash');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('completed');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};

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
        Schema::create('driver_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->constrained('drivers')->cascadeOnDelete();
            $table->foreignUuid('delivery_company_id')->nullable()->constrained('delivery_companies')->cascadeOnDelete();
            $table->foreignUuid('received_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_receipts');
    }
};

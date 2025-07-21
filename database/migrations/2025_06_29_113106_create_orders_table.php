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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUuid('delivery_company_id')->nullable()->constrained('delivery_companies')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('drivers')->nullOnDelete();

            $table->string('customer_name');
            $table->string('customer_phone')->index();
            $table->text('customer_address');

            $table->timestamp('expected_delivery_time')->nullable()->index();
            $table->timestamp('delivered_at')->nullable()->index();
            $table->string('tracking_number')->unique();
            $table->tinyInteger('status')->default(0)->index();
            $table->enum('upload', ['sent', 'not sent'])->default('not sent');

            $table->decimal('total_price', 10, 2);

            $table->index(['driver_id', 'status']);

            $table->timestamps();
        });
    }


    public function down(): void
    {

        Schema::dropIfExists('orders');
    }
};

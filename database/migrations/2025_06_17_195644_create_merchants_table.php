<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('phone');
            $table->string('country');
            $table->string('city');
            $table->string('address')->nullable();
            $table->string('business_name');
            $table->string('business_license')->nullable();
            $table->string('business_type')->nullable();
            $table->enum('status', ['Active', 'Inactive']);
            $table->uuid('warehouse_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};

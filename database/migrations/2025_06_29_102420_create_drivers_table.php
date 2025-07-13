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
        Schema::create('drivers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('delivery_company_id')->nullable()->constrained('delivery_companies')->cascadeOnDelete();
            $table->enum('status', ['Active', 'Inactive']);
            $table->unsignedTinyInteger('rating')->nullable()->check('rating between 1 and 5'); //! Constraint
            $table->boolean('available')->default(true);
            $table->text('cancel_reason')->nullable()->after('status');
            $table->string('vehicle_number');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};

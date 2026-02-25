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
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_number', 20)->unique();
            $table->foreignId('staff_id')->nullable()->index()->constrained('users')->restrictOnDelete();
            $table->string('order_number', 50)->nullable();
            $table->foreignId('category_id')->index()->constrained('inquiry_categories')->restrictOnDelete();
            $table->string('customer_name', 100);
            $table->string('customer_email', 100);
            $table->string('status', 20)->default('pending')->index()->comment('pending, in_progress, resolved, closed');
            $table->string('priority', 20)->default('medium')->index()->comment('low, medium, high, urgent');
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};

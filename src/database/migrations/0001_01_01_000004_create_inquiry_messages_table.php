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
        Schema::create('inquiry_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->index()->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('message_type', 20)->comment('initial_inquiry, customer_reply, staff_reply');
            $table->string('subject', 200);
            $table->text('body');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_messages');
    }
};

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
        Schema::create('pending_enrollments', function (Blueprint $table) {
            $table->id();
            $table->json('data'); // Store all form data as JSON
            $table->string('status')->default('pending'); // e.g., pending, approved, rejected
            $table->text('remarks')->nullable(); // Optional remarks from admin
            $table->unsignedBigInteger('approved_by')->nullable(); // Admin user ID who approved/rejected
            $table->timestamp('processed_at')->nullable(); // Timestamp when processed
            $table->timestamps(); // created_at, updated_at

            $table->index('status');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_enrollments');
    }
};

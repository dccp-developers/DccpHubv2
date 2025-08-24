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
        Schema::create('oauth_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider');
            $table->string('provider_id');
            $table->json('data')->nullable();
            $table->string('token')->nullable();
            $table->string('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Add unique constraint for user_id and provider combination
            $table->unique(['user_id', 'provider']);

            // Add foreign key constraint to accounts table
            $table->foreign('user_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_connections');
    }
};

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
        // Check if the table exists before trying to modify it
        if (Schema::hasTable('login_links')) {
            Schema::table('login_links', function (Blueprint $table) {
                // Drop the existing foreign key constraint if it exists
                try {
                    $table->dropForeign('login_links_user_id_foreign');
                } catch (\Exception) {
                    // Foreign key might not exist, continue
                }

                // Add the correct foreign key constraint pointing to accounts table
                $table->foreign('user_id')->references('id')->on('accounts')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the table exists before trying to modify it
        if (Schema::hasTable('login_links')) {
            Schema::table('login_links', function (Blueprint $table) {
                // Drop the accounts foreign key
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception) {
                    // Foreign key might not exist, continue
                }

                // Restore the original users foreign key (if users table exists)
                if (Schema::hasTable('users')) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                }
            });
        }
    }
};

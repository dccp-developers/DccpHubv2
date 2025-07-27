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
        Schema::create('routes', function (Blueprint $table) {
            $table->id();

            // Basic route information
            $table->string('name')->unique(); // Laravel route name
            $table->string('display_name'); // Human readable name
            $table->string('path'); // URL path or external URL
            $table->string('method')->default('GET'); // HTTP method
            $table->string('controller')->nullable(); // Controller class
            $table->string('action')->nullable(); // Controller method

            // Route classification
            $table->string('type'); // RouteType enum
            $table->string('status')->default('active'); // RouteStatus enum
            $table->text('description')->nullable();
            $table->string('icon')->nullable(); // Icon for navigation
            $table->integer('sort_order')->default(0);

            // Hierarchy
            $table->foreignId('parent_id')->nullable()->constrained('routes')->onDelete('cascade');

            // Visibility settings
            $table->boolean('is_navigation')->default(false); // Show in navigation
            $table->boolean('is_mobile_visible')->default(true);
            $table->boolean('is_desktop_visible')->default(true);

            // Access control
            $table->json('required_permissions')->nullable(); // Array of permissions
            $table->json('middleware')->nullable(); // Array of middleware
            $table->json('parameters')->nullable(); // Default route parameters

            // Additional data
            $table->json('metadata')->nullable(); // Additional metadata

            // Custom messages for different statuses
            $table->text('disabled_message')->nullable();
            $table->text('maintenance_message')->nullable();
            $table->text('development_message')->nullable();

            // External route settings
            $table->string('redirect_url')->nullable(); // Custom redirect URL
            $table->boolean('is_external')->default(false);
            $table->string('target')->default('_self'); // _self, _blank, etc.

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('accounts')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('accounts')->onDelete('set null');

            // Status tracking
            $table->timestamp('disabled_at')->nullable();
            $table->timestamp('enabled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index(['status', 'is_navigation']);
            $table->index(['parent_id', 'sort_order']);
            $table->index(['type', 'is_navigation', 'status']);
            $table->index(['is_mobile_visible', 'status']);
            $table->index(['is_desktop_visible', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};

<?php

declare(strict_types=1);

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
        Schema::table('attendances', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('attendances', 'class_id')) {
                $table->unsignedBigInteger('class_id')->after('student_id');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            }
            
            if (!Schema::hasColumn('attendances', 'remarks')) {
                $table->text('remarks')->nullable()->after('status');
            }
            
            if (!Schema::hasColumn('attendances', 'marked_at')) {
                $table->timestamp('marked_at')->nullable()->after('remarks');
            }
            
            if (!Schema::hasColumn('attendances', 'marked_by')) {
                $table->uuid('marked_by')->nullable()->after('marked_at');
                $table->foreign('marked_by')->references('id')->on('faculty')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('attendances', 'ip_address')) {
                $table->string('ip_address')->nullable()->after('marked_by');
            }
            
            if (!Schema::hasColumn('attendances', 'location_data')) {
                $table->json('location_data')->nullable()->after('ip_address');
            }
            
            if (!Schema::hasColumn('attendances', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }

            // Note: Status column enum change skipped for PostgreSQL compatibility

            // Add indexes for better performance
            $table->index(['class_id', 'date'], 'idx_attendances_class_date');
            $table->index(['student_id', 'date'], 'idx_attendances_student_date');
            $table->index(['date', 'status'], 'idx_attendances_date_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_attendances_class_date');
            $table->dropIndex('idx_attendances_student_date');
            $table->dropIndex('idx_attendances_date_status');
            
            // Drop foreign keys
            $table->dropForeign(['class_id']);
            $table->dropForeign(['marked_by']);
            
            // Drop columns
            $table->dropColumn([
                'class_id',
                'remarks',
                'marked_at',
                'marked_by',
                'ip_address',
                'location_data',
                'deleted_at'
            ]);
        });
    }
};

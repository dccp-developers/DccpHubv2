<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Faculty;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->string('faculty_code', 20)->nullable()->unique()->after('id');
        });

        // Populate existing faculty records with readable codes
        $this->populateExistingFacultyCodes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->dropColumn('faculty_code');
        });
    }

    /**
     * Populate existing faculty records with readable codes
     */
    private function populateExistingFacultyCodes(): void
    {
        $faculties = Faculty::whereNull('faculty_code')->get();

        foreach ($faculties as $index => $faculty) {
            // Generate a readable faculty code like FAC001, FAC002, etc.
            $code = 'FAC' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);

            // Ensure uniqueness
            while (Faculty::where('faculty_code', $code)->exists()) {
                $index++;
                $code = 'FAC' . str_pad((string)($index + 1), 3, '0', STR_PAD_LEFT);
            }

            $faculty->update(['faculty_code' => $code]);
        }
    }
};

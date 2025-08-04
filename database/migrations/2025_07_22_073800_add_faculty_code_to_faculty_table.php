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
            $table->string('faculty_id_number', 20)->nullable()->unique()->after('id');
        });

        // Populate existing faculty records with numeric IDs
        $this->populateExistingFacultyIds();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faculty', function (Blueprint $table) {
            $table->dropColumn('faculty_id_number');
        });
    }

    /**
     * Populate existing faculty records with numeric IDs
     */
    private function populateExistingFacultyIds(): void
    {
        $faculties = Faculty::whereNull('faculty_id_number')->get();

        foreach ($faculties as $index => $faculty) {
            // Generate a 7-digit faculty ID starting from 1000001
            $facultyIdNumber = str_pad((string)(1000001 + $index), 7, '0', STR_PAD_LEFT);

            // Ensure uniqueness
            while (Faculty::where('faculty_id_number', $facultyIdNumber)->exists()) {
                $index++;
                $facultyIdNumber = str_pad((string)(1000001 + $index), 7, '0', STR_PAD_LEFT);
            }

            $faculty->update(['faculty_id_number' => $facultyIdNumber]);
        }
    }
};

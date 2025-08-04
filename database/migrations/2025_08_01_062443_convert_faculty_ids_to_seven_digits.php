<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Faculty;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Convert existing 8-digit faculty IDs to 7-digit format
        $this->convertFacultyIdsToSevenDigits();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert back to 8-digit format
        $this->convertFacultyIdsToEightDigits();
    }

    /**
     * Convert existing 8-digit faculty IDs to 7-digit format
     */
    private function convertFacultyIdsToSevenDigits(): void
    {
        $faculties = Faculty::whereNotNull('faculty_id_number')
            ->where('faculty_id_number', 'LIKE', '1%')
            ->whereRaw('LENGTH(faculty_id_number) = 8')
            ->get();

        foreach ($faculties as $index => $faculty) {
            // Convert 8-digit ID (10000001) to 7-digit ID (1000001)
            $oldId = $faculty->faculty_id_number;

            // Remove the leading '1' and '0' to convert from 10000001 to 1000001
            if (strlen($oldId) === 8 && str_starts_with($oldId, '10')) {
                $newId = '1' . substr($oldId, 2);

                // Ensure uniqueness
                while (Faculty::where('faculty_id_number', $newId)->where('id', '!=', $faculty->id)->exists()) {
                    $index++;
                    $newId = str_pad((string)(1000001 + $index), 7, '0', STR_PAD_LEFT);
                }

                $faculty->update(['faculty_id_number' => $newId]);
            }
        }
    }

    /**
     * Convert 7-digit faculty IDs back to 8-digit format (for rollback)
     */
    private function convertFacultyIdsToEightDigits(): void
    {
        $faculties = Faculty::whereNotNull('faculty_id_number')
            ->whereRaw('LENGTH(faculty_id_number) = 7')
            ->get();

        foreach ($faculties as $index => $faculty) {
            // Convert 7-digit ID (1000001) to 8-digit ID (10000001)
            $oldId = $faculty->faculty_id_number;

            if (strlen($oldId) === 7) {
                $newId = '10' . substr($oldId, 1);

                // Ensure uniqueness
                while (Faculty::where('faculty_id_number', $newId)->where('id', '!=', $faculty->id)->exists()) {
                    $index++;
                    $newId = str_pad((string)(10000001 + $index), 8, '0', STR_PAD_LEFT);
                }

                $faculty->update(['faculty_id_number' => $newId]);
            }
        }
    }
};

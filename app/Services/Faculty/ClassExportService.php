<?php

declare(strict_types=1);

namespace App\Services\Faculty;

use App\Models\Classes;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class ClassExportService
{
    /**
     * Authorize faculty and build export rows
     *
     * @return array{rows: Collection, class: Classes}
     */
    public function getExportData(Faculty $faculty, Classes $class): array
    {
        // Ensure faculty owns the class
        if (!app(FacultyClassService::class)->facultyTeachesClass($faculty, $class)) {
            abort(403, 'You are not authorized to export this class list.');
        }

        $enrollments = $class->ClassStudents()->with('student')->get();

        $rows = $enrollments->map(function ($enrollment, $idx) {
            return [
                'No.' => $idx + 1,
                'Student Name' => $enrollment->student?->full_name ?? ($enrollment->student?->name ?? 'Unknown'),
                'Student ID' => $enrollment->student?->student_id ?? $enrollment->student_id ?? 'N/A',
                'Email' => $enrollment->student?->email ?? 'N/A',
                'Status' => $enrollment->status ?? 'enrolled',
            ];
        });

        return compact('rows', 'class');
    }
}


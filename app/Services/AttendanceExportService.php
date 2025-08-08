<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Attendance;
use App\Models\Classes;
use App\Models\class_enrollments;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Attendance Export Service
 * 
 * Handles exporting attendance data in various formats
 */
final class AttendanceExportService
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    /**
     * Export class attendance data
     */
    public function exportClassAttendance(
        int $classId,
        Carbon $startDate,
        Carbon $endDate,
        string $format = 'csv'
    ): array {
        $class = Classes::with(['Subject', 'ShsSubject', 'Faculty'])->findOrFail($classId);
        $report = $this->attendanceService->generateClassReport($classId, $startDate, $endDate);

        $filename = $this->generateFilename('class_attendance', $class->subject_code, $startDate, $endDate, $format);

        switch ($format) {
            case 'csv':
                return $this->exportToCSV($report, $filename);
            case 'excel':
                return $this->exportToExcel($report, $filename);
            case 'pdf':
                return $this->exportToPDF($report, $filename);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Export student attendance data
     */
    public function exportStudentAttendance(
        string $studentId,
        ?int $classId = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        string $format = 'csv'
    ): array {
        $attendances = $this->attendanceService->getStudentAttendance($studentId, $classId, $startDate, $endDate);
        $stats = $this->attendanceService->calculateAttendanceStats($attendances);

        $data = $this->prepareStudentExportData($attendances, $stats);
        $filename = $this->generateFilename('student_attendance', $studentId, $startDate, $endDate, $format);

        switch ($format) {
            case 'csv':
                return $this->exportStudentToCSV($data, $filename);
            case 'excel':
                return $this->exportStudentToExcel($data, $filename);
            case 'pdf':
                return $this->exportStudentToPDF($data, $filename);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Export faculty attendance summary
     */
    public function exportFacultySummary(
        string $facultyId,
        Carbon $startDate,
        Carbon $endDate,
        string $format = 'csv'
    ): array {
        $summary = $this->attendanceService->getFacultyAttendanceSummary($facultyId);
        $filename = $this->generateFilename('faculty_summary', $facultyId, $startDate, $endDate, $format);

        switch ($format) {
            case 'csv':
                return $this->exportFacultyToCSV($summary, $filename);
            case 'excel':
                return $this->exportFacultyToExcel($summary, $filename);
            case 'pdf':
                return $this->exportFacultyToPDF($summary, $filename);
            default:
                throw new \InvalidArgumentException("Unsupported export format: {$format}");
        }
    }

    /**
     * Export to CSV format
     */
    private function exportToCSV(array $report, string $filename): array
    {
        $csvData = [];
        
        // Header
        $csvData[] = [
            'Student Name',
            'Student ID',
            'Total Sessions',
            'Present',
            'Absent',
            'Late',
            'Excused',
            'Partial',
            'Attendance Rate (%)',
        ];

        // Student data
        foreach ($report['students'] as $studentData) {
            $student = $studentData['student'];
            $stats = $studentData['stats'];

            $csvData[] = [
                $student->fullname ?? ($student->first_name . ' ' . $student->last_name),
                $student instanceof \App\Models\ShsStudents ? $student->student_lrn : $student->id,
                $stats['total'],
                $stats['present'],
                $stats['absent'],
                $stats['late'],
                $stats['excused'],
                $stats['partial'],
                $stats['attendance_rate'],
            ];
        }

        $filePath = $this->writeCSVFile($csvData, $filename);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filePath,
            'download_url' => Storage::url($filePath),
            'type' => 'csv',
        ];
    }

    /**
     * Export student data to CSV
     */
    private function exportStudentToCSV(array $data, string $filename): array
    {
        $csvData = [];
        
        // Header
        $csvData[] = [
            'Date',
            'Day',
            'Class',
            'Subject',
            'Status',
            'Remarks',
            'Marked At',
        ];

        // Attendance records
        foreach ($data['attendances'] as $attendance) {
            $csvData[] = [
                Carbon::parse($attendance->date)->format('Y-m-d'),
                Carbon::parse($attendance->date)->format('l'),
                $attendance->class->subject_code,
                $attendance->class->Subject?->title ?? $attendance->class->ShsSubject?->title,
                ucfirst($attendance->status),
                $attendance->remarks ?? '',
                $attendance->marked_at ? Carbon::parse($attendance->marked_at)->format('Y-m-d H:i:s') : '',
            ];
        }

        $filePath = $this->writeCSVFile($csvData, $filename);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filePath,
            'download_url' => Storage::url($filePath),
            'type' => 'csv',
            'stats' => $data['stats'],
        ];
    }

    /**
     * Write CSV data to file
     */
    private function writeCSVFile(array $data, string $filename): string
    {
        $filePath = 'exports/attendance/' . $filename;
        
        // Ensure directory exists
        Storage::makeDirectory('exports/attendance');

        $handle = fopen(storage_path('app/' . $filePath), 'w');
        
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);

        return $filePath;
    }

    /**
     * Export to Excel format (placeholder - would need a library like PhpSpreadsheet)
     */
    private function exportToExcel(array $report, string $filename): array
    {
        // For now, fall back to CSV
        // In a real implementation, you would use PhpSpreadsheet or similar
        return $this->exportToCSV($report, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Export student data to Excel
     */
    private function exportStudentToExcel(array $data, string $filename): array
    {
        // For now, fall back to CSV
        return $this->exportStudentToCSV($data, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Export faculty data to Excel
     */
    private function exportFacultyToExcel(array $summary, string $filename): array
    {
        // For now, fall back to CSV
        return $this->exportFacultyToCSV($summary, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Export to PDF format (placeholder - would need a library like DomPDF)
     */
    private function exportToPDF(array $report, string $filename): array
    {
        // For now, fall back to CSV
        // In a real implementation, you would use DomPDF or similar
        return $this->exportToCSV($report, str_replace('.pdf', '.csv', $filename));
    }

    /**
     * Export student data to PDF
     */
    private function exportStudentToPDF(array $data, string $filename): array
    {
        // For now, fall back to CSV
        return $this->exportStudentToCSV($data, str_replace('.pdf', '.csv', $filename));
    }

    /**
     * Export faculty data to PDF
     */
    private function exportFacultyToPDF(array $summary, string $filename): array
    {
        // For now, fall back to CSV
        return $this->exportFacultyToCSV($summary, str_replace('.pdf', '.csv', $filename));
    }

    /**
     * Export faculty data to CSV
     */
    private function exportFacultyToCSV(array $summary, string $filename): array
    {
        $csvData = [];
        
        // Header
        $csvData[] = [
            'Class',
            'Subject',
            'Total Students',
            'Total Sessions',
            'Overall Attendance Rate (%)',
            'Present Count',
            'Absent Count',
            'Late Count',
        ];

        // Class data
        foreach ($summary as $classData) {
            $class = $classData['class'];
            $stats = $classData['stats'];

            $csvData[] = [
                $class->subject_code,
                $class->Subject?->title ?? $class->ShsSubject?->title,
                $classData['enrollment_count'] ?? 0,
                $stats['total'],
                $stats['attendance_rate'],
                $stats['present_count'],
                $stats['absent'],
                $stats['late'],
            ];
        }

        $filePath = $this->writeCSVFile($csvData, $filename);

        return [
            'success' => true,
            'filename' => $filename,
            'path' => $filePath,
            'download_url' => Storage::url($filePath),
            'type' => 'csv',
        ];
    }

    /**
     * Prepare student export data
     */
    private function prepareStudentExportData(Collection $attendances, array $stats): array
    {
        return [
            'attendances' => $attendances,
            'stats' => $stats,
            'summary' => [
                'total_records' => $attendances->count(),
                'date_range' => [
                    'start' => $attendances->min('date'),
                    'end' => $attendances->max('date'),
                ],
            ],
        ];
    }

    /**
     * Generate filename for export
     */
    private function generateFilename(
        string $type,
        string $identifier,
        ?Carbon $startDate,
        ?Carbon $endDate,
        string $format
    ): string {
        $dateRange = '';
        if ($startDate && $endDate) {
            $dateRange = '_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d');
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $extension = $format === 'excel' ? 'xlsx' : $format;

        return "{$type}_{$identifier}{$dateRange}_{$timestamp}.{$extension}";
    }

    /**
     * Clean up old export files
     */
    public function cleanupOldExports(int $daysOld = 7): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $exportPath = 'exports/attendance';
        
        if (!Storage::exists($exportPath)) {
            return 0;
        }

        $files = Storage::files($exportPath);
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp(Storage::lastModified($file));
            
            if ($lastModified->lt($cutoffDate)) {
                Storage::delete($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}

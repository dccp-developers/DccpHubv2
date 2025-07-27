<?php

declare(strict_types=1);

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Services\Faculty\FacultyScheduleService;
use App\Services\GeneralSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

final class FacultyScheduleController extends Controller
{
    public function __construct(
        private readonly FacultyScheduleService $scheduleService,
        private readonly GeneralSettingsService $settingsService
    ) {}

    /**
     * Display the faculty schedule page
     */
    public function index(Request $request): Response
    {
        try {
            $faculty = $request->user()->faculty;

            if (!$faculty) {
                return $this->renderErrorResponse('Faculty profile not found.');
            }

            // Get filter parameters
            $filters = [
                'view' => $request->get('view', 'week'),
                'date' => $request->get('date', now()->format('Y-m-d')),
                'subject' => $request->get('subject'),
                'room' => $request->get('room'),
            ];

            // Get all schedule data from service
            $scheduleData = $this->scheduleService->getScheduleIndexData($faculty, $filters);

            return Inertia::render('Faculty/Schedule/Index', [
                'csrf_token' => csrf_token(),
                'faculty' => [
                    'id' => $faculty->id,
                    'name' => $faculty->first_name . ' ' . $faculty->last_name,
                    'full_name' => $faculty->full_name,
                    'email' => $faculty->email,
                ],
                ...$scheduleData
            ]);

        } catch (\Exception $e) {
            return $this->renderErrorResponse('Failed to load schedule data: ' . $e->getMessage());
        }
    }

    /**
     * Get schedule details for a specific schedule item
     */
    public function show(Request $request, int $scheduleId): Response
    {
        try {
            $faculty = $request->user()->faculty;
            
            if (!$faculty) {
                return $this->renderErrorResponse('Faculty profile not found.');
            }

            $scheduleDetails = $this->scheduleService->getScheduleDetails($faculty, $scheduleId);

            if (!$scheduleDetails) {
                return $this->renderErrorResponse('Schedule not found or access denied.');
            }

            return Inertia::render('Faculty/Schedule/Show', [
                'faculty' => [
                    'id' => $faculty->id,
                    'name' => $faculty->first_name . ' ' . $faculty->last_name,
                    'full_name' => $faculty->full_name,
                    'email' => $faculty->email,
                ],
                'schedule' => $scheduleDetails,
                'currentSemester' => (string) $this->settingsService->getCurrentSemester(),
                'schoolYear' => $this->settingsService->getCurrentSchoolYearString(),
            ]);

        } catch (\Exception $e) {
            return $this->renderErrorResponse('Failed to load schedule details: ' . $e->getMessage());
        }
    }

    /**
     * Export schedule data
     */
    public function export(Request $request)
    {
        try {
            $faculty = $request->user()->faculty;

            if (!$faculty) {
                Log::error('Faculty profile not found for user: ' . $request->user()->id);
                return response()->json(['error' => 'Faculty profile not found.'], 404);
            }

            $format = $request->get('format', 'pdf'); // pdf, excel, csv
            $period = $request->get('period', 'week'); // week, month, semester

            Log::info("Export request: Faculty={$faculty->id}, Format={$format}, Period={$period}");

            $exportData = $this->scheduleService->exportSchedule($faculty, $format, $period);

            Log::info("Export file created: {$exportData['filename']} at {$exportData['path']}");

            // Check if file exists
            if (!file_exists($exportData['path'])) {
                Log::error("Export file not found: {$exportData['path']}");
                return response()->json(['error' => 'Export file could not be created.'], 500);
            }

            // Return JSON response with download URL
            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'download_url' => $exportData['url'],
                'filename' => $exportData['filename'],
                'format' => $format,
                'period' => $period
            ]);

        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()->id ?? null,
                'format' => $request->get('format'),
                'period' => $request->get('period')
            ]);
            return response()->json(['error' => 'Failed to export schedule: ' . $e->getMessage()], 500);
        }
    }



    /**
     * Render error response
     */
    private function renderErrorResponse(string $message): Response
    {
        // Get empty data structure from service
        $emptyData = [
            'weeklySchedule' => [],
            'todaysSchedule' => [],
            'scheduleOverview' => [],
            'scheduleStats' => [],
            'filters' => [],
            'filterOptions' => [],
            'currentSemester' => (string) $this->settingsService->getCurrentSemester(),
            'schoolYear' => $this->settingsService->getCurrentSchoolYearString(),
            'availableSemesters' => $this->settingsService->getAvailableSemesters(),
            'availableSchoolYears' => $this->settingsService->getAvailableSchoolYears(),
        ];

        return Inertia::render('Faculty/Schedule/Index', [
            'error' => $message,
            'faculty' => null,
            ...$emptyData
        ]);
    }
}

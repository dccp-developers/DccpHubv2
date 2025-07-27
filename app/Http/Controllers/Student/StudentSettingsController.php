<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\GeneralSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

final class StudentSettingsController extends Controller
{
    public function __construct(
        private readonly GeneralSettingsService $settingsService
    ) {}

    /**
     * Get current student settings including available options
     */
    public function index(): JsonResponse
    {
        try {
            $currentSemester = $this->settingsService->getCurrentSemester();
            $currentSchoolYear = $this->settingsService->getCurrentSchoolYearStart();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'current_semester' => $currentSemester,
                    'current_school_year' => $currentSchoolYear,
                    'current_school_year_string' => $this->settingsService->getCurrentSchoolYearString(),
                    'available_semesters' => $this->settingsService->getAvailableSemesters(),
                    'available_school_years' => $this->settingsService->getAvailableSchoolYears(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update student semester preference
     */
    public function updateSemester(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'semester' => ['required', 'integer', Rule::in([1, 2])]
            ]);

            $success = $this->settingsService->updateUserSemester($validated['semester']);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Semester updated successfully',
                    'data' => [
                        'semester' => $validated['semester'],
                        'semester_name' => $this->settingsService->getAvailableSemesters()[$validated['semester']]
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update semester'
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update semester',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update student school year preference
     */
    public function updateSchoolYear(Request $request): JsonResponse
    {
        try {
            $availableYears = array_keys($this->settingsService->getAvailableSchoolYears());
            
            $validated = $request->validate([
                'school_year' => ['required', 'integer', Rule::in($availableYears)]
            ]);

            $success = $this->settingsService->updateUserSchoolYear($validated['school_year']);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'School year updated successfully',
                    'data' => [
                        'school_year' => $validated['school_year'],
                        'school_year_string' => $validated['school_year'] . ' - ' . ($validated['school_year'] + 1)
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update school year'
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update school year',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update both semester and school year at once
     */
    public function updateBoth(Request $request): JsonResponse
    {
        try {
            $availableYears = array_keys($this->settingsService->getAvailableSchoolYears());
            
            $validated = $request->validate([
                'semester' => ['required', 'integer', Rule::in([1, 2])],
                'school_year' => ['required', 'integer', Rule::in($availableYears)]
            ]);

            $semesterSuccess = $this->settingsService->updateUserSemester($validated['semester']);
            $schoolYearSuccess = $this->settingsService->updateUserSchoolYear($validated['school_year']);

            if ($semesterSuccess && $schoolYearSuccess) {
                return response()->json([
                    'success' => true,
                    'message' => 'Settings updated successfully',
                    'data' => [
                        'semester' => $validated['semester'],
                        'semester_name' => $this->settingsService->getAvailableSemesters()[$validated['semester']],
                        'school_year' => $validated['school_year'],
                        'school_year_string' => $validated['school_year'] . ' - ' . ($validated['school_year'] + 1)
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings'
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

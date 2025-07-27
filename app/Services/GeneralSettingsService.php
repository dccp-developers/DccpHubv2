<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GeneralSettings as GeneralSetting;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

final class GeneralSettingsService
{
    private ?GeneralSetting $globalSettings = null;

    private ?UserSetting $userSettings = null;

    public function __construct()
    {
        // Use the model's getCached method which handles incomplete classes
        $this->globalSettings = GeneralSetting::getCached();

        if (Auth::check()) {
            $this->userSettings = UserSetting::firstOrNew([
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Get the effective current semester.
     * Prioritizes user's setting, then global, then a default.
     */
    public function getCurrentSemester(): int
    {
        if ($this->userSettings && ! is_null($this->userSettings->semester)) {
            return (int) $this->userSettings->semester;
        }
        if (
            $this->globalSettings &&
            ! is_null($this->globalSettings->semester)
        ) {
            return (int) $this->globalSettings->semester;
        }

        return 1; // Default semester
    }

    /**
     * Get the effective current school year start.
     * Prioritizes user's setting, then global, then a default.
     */
    public function getCurrentSchoolYearStart(): int
    {
        if (
            $this->userSettings &&
            ! is_null($this->userSettings->school_year_start)
        ) {
            return (int) $this->userSettings->school_year_start;
        }
        if (
            $this->globalSettings &&
            $this->globalSettings->school_starting_date
        ) {
            $year = $this->globalSettings->getSchoolYearStarting();

            return $year !== 'N/A' ? (int) $year : (int) date('Y');
        }

        return (int) date('Y'); // Default to current year
    }

    /**
     * Get the display string for the current school year.
     * e.g., "2023 - 2024"
     */
    public function getCurrentSchoolYearString(): string
    {
        $startYear = $this->getCurrentSchoolYearStart();

        return $startYear.' - '.($startYear + 1);
    }

    /**
     * Get the global school starting date.
     */
    public function getGlobalSchoolStartingDate(): ?Carbon
    {
        return $this->globalSettings?->school_starting_date;
    }

    /**
     * Get the global school ending date.
     */
    public function getGlobalSchoolEndingDate(): ?Carbon
    {
        return $this->globalSettings?->school_ending_date;
    }

    /**
     * Update the user's preferred semester.
     */
    public function updateUserSemester(int $semester): bool
    {
        if (Auth::check()) {
            $this->userSettings->semester = $semester;

            return $this->userSettings->save();
        }

        return false;
    }

    /**
     * Update the user's preferred school year start.
     */
    public function updateUserSchoolYear(int $startYear): bool
    {
        if (Auth::check()) {
            $this->userSettings->school_year_start = $startYear;

            return $this->userSettings->save();
        }

        return false;
    }

    /**
     * Get available semesters (can be extended if semesters become dynamic).
     */
    public function getAvailableSemesters(): array
    {
        return [1 => '1st Semester', 2 => '2nd Semester'];
    }

    /**
     * Populate available school years based on a range around a given start year.
     * If no start year is provided, it uses the effective current school year start.
     */
    public function getAvailableSchoolYears(
        ?int $referenceStartYear = null,
        int $range = 3
    ): array {
        $effectiveStartYear =
            $referenceStartYear ?? $this->getCurrentSchoolYearStart();
        $startYearRange = $effectiveStartYear - $range;
        $endYearRange = $effectiveStartYear + $range;
        $availableSchoolYears = [];

        for ($year = $startYearRange; $year <= $endYearRange; $year++) {
            $displayYear = $year.' - '.($year + 1);
            $availableSchoolYears[$year] = $displayYear;
        }

        // Ensure the effective start year is in the list
        if (! array_key_exists($effectiveStartYear, $availableSchoolYears)) {
            $availableSchoolYears[$effectiveStartYear] =
                $effectiveStartYear.' - '.($effectiveStartYear + 1);
            ksort($availableSchoolYears);
        }

        return $availableSchoolYears;
    }

    /**
     * Get a specific global setting by key.
     */
    public function getGlobalSetting(string $key, mixed $default = null): mixed
    {
        if (
            $this->globalSettings &&
            property_exists($this->globalSettings, $key)
        ) {
            return $this->globalSettings->{$key};
        }

        return $default;
    }

    /**
     * Get the entire global settings model instance.
     */
    public function getGlobalSettingsModel(): ?GeneralSetting
    {
        return $this->globalSettings;
    }

    /**
     * Get the entire user settings model instance for the current user.
     */
    public function getUserSettingsModel(): ?UserSetting
    {
        return $this->userSettings;
    }

    /**
     * Clear the cached global settings and reload them.
     */
    public function clearGlobalSettingsCache(): void
    {
        GeneralSetting::clearCache();
        $this->globalSettings = GeneralSetting::getCached();
    }

    /**
     * Refresh the global settings from the database.
     */
    public function refreshGlobalSettings(): void
    {
        $this->clearGlobalSettingsCache();
    }
}

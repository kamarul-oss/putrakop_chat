<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Department;
use Carbon\Carbon;

/**
 * Determines whether a department is currently within its configured business hours.
 *
 * Business hours are stored as a JSON column on the Department model with the structure:
 * {
 *     "open": "09:00",
 *     "close": "17:00",
 *     "days": ["mon", "tue", "wed", "thu", "fri"]
 * }
 *
 * If no business hours are configured, the department is considered always open.
 * All time comparisons use the Asia/Kuala_Lumpur timezone (MYT, UTC+8).
 */
final class BusinessHoursService
{
    private const TIMEZONE = 'Asia/Kuala_Lumpur';

    /**
     * Check if a department is currently within business hours.
     */
    public function isWithinBusinessHours(Department $department): bool
    {
        $businessHours = $department->business_hours;

        if (empty($businessHours)) {
            // No business hours configured = always open
            return true;
        }

        $now = Carbon::now(self::TIMEZONE);
        $currentDay = strtolower($now->format('D')); // mon, tue, wed, etc.
        $currentTime = $now->format('H:i');

        // Check if today is a business day
        $businessDays = $businessHours['days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'];
        if (! in_array($currentDay, $businessDays, true)) {
            return false;
        }

        // Check time range
        $openTime = $businessHours['open'] ?? '09:00';
        $closeTime = $businessHours['close'] ?? '17:00';

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    /**
     * Get business hours status summary for a department.
     *
     * Returns a structured array containing open/close times, business days,
     * current open status, and timezone information.
     */
    public function getStatus(Department $department): array
    {
        $businessHours = $department->business_hours ?? [];

        return [
            'is_open' => $this->isWithinBusinessHours($department),
            'open_time' => $businessHours['open'] ?? '09:00',
            'close_time' => $businessHours['close'] ?? '17:00',
            'business_days' => $businessHours['days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'],
            'timezone' => self::TIMEZONE,
        ];
    }

    /**
     * Get the next opening time if the department is currently closed.
     *
     * Returns a formatted datetime string (e.g. "2026-07-09 09:00") for the
     * next business day's opening time, or null if already open.
     */
    public function getNextOpenTime(Department $department): ?string
    {
        if ($this->isWithinBusinessHours($department)) {
            return null; // Already open
        }

        $businessHours = $department->business_hours ?? [];
        $now = Carbon::now(self::TIMEZONE);
        $businessDays = $businessHours['days'] ?? ['mon', 'tue', 'wed', 'thu', 'fri'];
        $openTime = $businessHours['open'] ?? '09:00';

        // Find next business day (look ahead up to 7 days)
        for ($i = 1; $i <= 7; $i++) {
            $nextDay = $now->copy()->addDays($i);
            $dayName = strtolower($nextDay->format('D'));

            if (in_array($dayName, $businessDays, true)) {
                return $nextDay->format('Y-m-d') . ' ' . $openTime;
            }
        }

        return null;
    }
}

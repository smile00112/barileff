<?php

namespace Webkul\Markup\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class MarkupScheduleService
{
    /**
     * Calculate seconds until the next "apply" moment for a group.
     */
    public function secondsUntilApply(object $group): ?int
    {
        $schedules = $group->schedules;

        if ($schedules->isEmpty()) {
            return null;
        }

        return $this->secondsUntilNextStart($schedules, $group->schedule_type);
    }

    /**
     * Calculate seconds until the next "revert" moment for a group.
     */
    public function secondsUntilRevert(object $group): ?int
    {
        $schedules = $group->schedules;

        if ($schedules->isEmpty()) {
            return null;
        }

        return $this->secondsUntilNextEnd($schedules, $group->schedule_type);
    }

    /**
     * Check if the group is currently within any of its schedule windows.
     */
    public function isInScheduleWindow(object $group): bool
    {
        $now = Carbon::now();
        $currentDayOfWeek = $now->dayOfWeek;

        foreach ($group->schedules as $schedule) {
            if ($group->schedule_type === 'weekly' && $schedule->day_of_week !== $currentDayOfWeek) {
                continue;
            }

            $timeFrom = Carbon::parse($schedule->time_from);
            $timeTo = Carbon::parse($schedule->time_to);
            $nowTime = $now->copy()->setDateFrom($timeFrom);

            if ($nowTime->between($timeFrom, $timeTo)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the next Carbon "apply" time.
     */
    public function nextApplyTime(object $group): ?Carbon
    {
        return $this->nextStartTime($group->schedules, $group->schedule_type);
    }

    /**
     * Get the next Carbon "revert" time.
     */
    public function nextRevertTime(object $group): ?Carbon
    {
        return $this->nextEndTime($group->schedules, $group->schedule_type);
    }

    protected function secondsUntilNextStart(Collection $schedules, string $scheduleType): ?int
    {
        $next = $this->nextStartTime($schedules, $scheduleType);

        return $next ? max(0, (int) Carbon::now()->diffInSeconds($next, false)) : null;
    }

    protected function secondsUntilNextEnd(Collection $schedules, string $scheduleType): ?int
    {
        $next = $this->nextEndTime($schedules, $scheduleType);

        return $next ? max(0, (int) Carbon::now()->diffInSeconds($next, false)) : null;
    }

    protected function nextStartTime(Collection $schedules, string $scheduleType): ?Carbon
    {
        $now = Carbon::now();
        $candidates = [];

        foreach ($schedules as $schedule) {
            $candidates = array_merge(
                $candidates,
                $this->getUpcomingStarts($schedule, $scheduleType, $now)
            );
        }

        if (empty($candidates)) {
            return null;
        }

        sort($candidates);

        return $candidates[0];
    }

    protected function nextEndTime(Collection $schedules, string $scheduleType): ?Carbon
    {
        $now = Carbon::now();
        $candidates = [];

        foreach ($schedules as $schedule) {
            $candidates = array_merge(
                $candidates,
                $this->getUpcomingEnds($schedule, $scheduleType, $now)
            );
        }

        if (empty($candidates)) {
            return null;
        }

        sort($candidates);

        return $candidates[0];
    }

    /**
     * @return Carbon[]
     */
    protected function getUpcomingStarts(object $schedule, string $scheduleType, Carbon $now): array
    {
        $candidates = [];

        if ($scheduleType === 'daily') {
            $today = $now->copy()->setTimeFromTimeString($schedule->time_from);

            if ($today->gt($now)) {
                $candidates[] = $today;
            } else {
                $candidates[] = $today->copy()->addDay();
            }
        } else {
            // Weekly — look at the next 7 days
            for ($offset = 0; $offset <= 7; $offset++) {
                $day = $now->copy()->addDays($offset);

                if ($day->dayOfWeek !== $schedule->day_of_week) {
                    continue;
                }

                $candidate = $day->copy()->setTimeFromTimeString($schedule->time_from);

                if ($candidate->gt($now)) {
                    $candidates[] = $candidate;

                    break;
                }
            }
        }

        return $candidates;
    }

    /**
     * @return Carbon[]
     */
    protected function getUpcomingEnds(object $schedule, string $scheduleType, Carbon $now): array
    {
        $candidates = [];

        if ($scheduleType === 'daily') {
            $today = $now->copy()->setTimeFromTimeString($schedule->time_to);

            if ($today->gt($now)) {
                $candidates[] = $today;
            } else {
                $candidates[] = $today->copy()->addDay();
            }
        } else {
            for ($offset = 0; $offset <= 7; $offset++) {
                $day = $now->copy()->addDays($offset);

                if ($day->dayOfWeek !== $schedule->day_of_week) {
                    continue;
                }

                $candidate = $day->copy()->setTimeFromTimeString($schedule->time_to);

                if ($candidate->gt($now)) {
                    $candidates[] = $candidate;

                    break;
                }
            }
        }

        return $candidates;
    }
}

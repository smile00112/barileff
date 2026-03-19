<?php

namespace Webkul\Markup\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\Markup\Models\MarkupGroupProxy;
use Webkul\Markup\Services\MarkupPriceService;
use Webkul\Markup\Services\MarkupScheduleService;

class ApplyMarkupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $groupId,
        protected int $jobsVersion,
    ) {}

    public function handle(MarkupPriceService $priceService, MarkupScheduleService $scheduleService): void
    {
        $group = MarkupGroupProxy::modelClass()::find($this->groupId);

        if (! $group || ! $group->is_active) {
            return;
        }

        if ($group->jobs_version !== $this->jobsVersion) {
            Log::info("[Markup] ApplyMarkupJob skipped for group #{$this->groupId}: stale jobs_version.");

            return;
        }

        if ($group->is_applied) {
            Log::info("[Markup] ApplyMarkupJob skipped for group #{$this->groupId}: already applied.");

            $this->scheduleRevert($group, $scheduleService);

            return;
        }

        $count = $priceService->apply($group);

        Log::info("[Markup] Applied group #{$this->groupId} \"{$group->name}\" to {$count} products.");

        $this->scheduleRevert($group->fresh(), $scheduleService);
    }

    protected function scheduleRevert(object $group, MarkupScheduleService $scheduleService): void
    {
        $seconds = $scheduleService->secondsUntilRevert($group);

        if ($seconds !== null && $seconds >= 0) {
            RevertMarkupJob::dispatch($group->id, $group->jobs_version)
                ->delay(now()->addSeconds($seconds));
        }
    }
}

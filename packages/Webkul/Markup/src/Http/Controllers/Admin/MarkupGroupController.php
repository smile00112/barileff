<?php

namespace Webkul\Markup\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Markup\DataGrids\MarkupGroupDataGrid;
use Webkul\Markup\Http\Requests\MarkupGroupRequest;
use Webkul\Markup\Jobs\ApplyMarkupJob;
use Webkul\Markup\Repositories\MarkupConditionRepository;
use Webkul\Markup\Repositories\MarkupGroupRepository;
use Webkul\Markup\Repositories\MarkupGroupScheduleRepository;
use Webkul\Markup\Services\MarkupPriceService;
use Webkul\Markup\Services\MarkupScheduleService;

class MarkupGroupController extends Controller
{
    public function __construct(
        protected MarkupGroupRepository $groupRepository,
        protected MarkupGroupScheduleRepository $scheduleRepository,
        protected MarkupConditionRepository $conditionRepository,
        protected MarkupPriceService $priceService,
        protected MarkupScheduleService $scheduleService,
    ) {}

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(MarkupGroupDataGrid::class)->toJson();
        }

        return view('markup::admin.groups.index');
    }

    public function create(): View
    {
        return view('markup::admin.groups.create');
    }

    public function store(MarkupGroupRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $group = $this->groupRepository->create([
            'name'                 => $data['name'],
            'description'          => $data['description'] ?? null,
            'type'                 => $data['type'],
            'is_active'            => $data['is_active'],
            'schedule_type'        => $data['schedule_type'],
            'apply_to_all_sources' => $data['apply_to_all_sources'],
            'sort_order'           => $data['sort_order'] ?? 0,
            'jobs_version'         => 1,
        ]);

        $this->syncRelations($group, $data);
        $this->dispatchApplyJob($group->fresh());

        session()->flash('success', trans('markup::app.admin.groups.created'));

        return redirect()->route('admin.markup.groups.index');
    }

    public function edit(int $id): View
    {
        $group = $this->groupRepository->with([
            'schedules',
            'conditions.categories',
            'conditions.products',
            'inventorySources',
            'logs' => fn ($q) => $q->latest()->limit(20),
        ])->findOrFail($id);

        return view('markup::admin.groups.edit', compact('group'));
    }

    public function update(MarkupGroupRequest $request, int $id): RedirectResponse
    {
        $data = $request->validated();

        $group = $this->groupRepository->findOrFail($id);

        // Revert prices before updating if currently applied
        if ($group->is_applied) {
            $this->priceService->revert($group);
        }

        // Bump jobs_version to invalidate any pending jobs
        $newVersion = $group->jobs_version + 1;

        $this->groupRepository->update([
            'name'                 => $data['name'],
            'description'          => $data['description'] ?? null,
            'type'                 => $data['type'],
            'is_active'            => $data['is_active'],
            'schedule_type'        => $data['schedule_type'],
            'apply_to_all_sources' => $data['apply_to_all_sources'],
            'sort_order'           => $data['sort_order'] ?? 0,
            'jobs_version'         => $newVersion,
        ], $id);

        $group = $group->fresh();

        // Clear and re-sync relations
        $group->schedules()->delete();
        $group->conditions()->delete();

        $this->syncRelations($group, $data);

        if ($group->is_active) {
            $this->dispatchApplyJob($group->fresh());
        }

        session()->flash('success', trans('markup::app.admin.groups.updated'));

        return redirect()->route('admin.markup.groups.index');
    }

    public function destroy(int $id): JsonResponse
    {
        $group = $this->groupRepository->findOrFail($id);

        // Revert prices if applied
        if ($group->is_applied) {
            $this->priceService->revert($group);
        }

        // Bump version to invalidate pending jobs
        $group->update(['jobs_version' => $group->jobs_version + 1]);

        $this->groupRepository->delete($id);

        return new JsonResponse(['message' => trans('markup::app.admin.groups.deleted')]);
    }

    protected function syncRelations(object $group, array $data): void
    {
        // Sync schedules
        foreach ($data['schedules'] ?? [] as $scheduleData) {
            $this->scheduleRepository->create([
                'markup_group_id' => $group->id,
                'day_of_week'     => $scheduleData['day_of_week'] ?? null,
                'time_from'       => $scheduleData['time_from'],
                'time_to'         => $scheduleData['time_to'],
            ]);
        }

        // Sync inventory sources
        if (! ($data['apply_to_all_sources'] ?? true)) {
            $group->inventorySources()->sync($data['inventory_sources'] ?? []);
        } else {
            $group->inventorySources()->detach();
        }

        // Sync conditions
        foreach ($data['conditions'] ?? [] as $conditionData) {
            $condition = $this->conditionRepository->create([
                'markup_group_id'  => $group->id,
                'cost_from'        => $conditionData['cost_from'] ?? null,
                'cost_to'          => $conditionData['cost_to'] ?? null,
                'adjustment_type'  => $conditionData['adjustment_type'],
                'adjustment_value' => $conditionData['adjustment_value'],
                'sort_order'       => $conditionData['sort_order'] ?? 0,
            ]);

            if (! empty($conditionData['categories'])) {
                $condition->categories()->sync($conditionData['categories']);
            }

            if (! empty($conditionData['products'])) {
                $condition->products()->sync($conditionData['products']);
            }
        }
    }

    protected function dispatchApplyJob(object $group): void
    {
        if (! $group->is_active) {
            return;
        }

        if ($this->scheduleService->isInScheduleWindow($group)) {
            // Apply immediately
            ApplyMarkupJob::dispatch($group->id, $group->jobs_version);
        } else {
            $seconds = $this->scheduleService->secondsUntilApply($group);

            if ($seconds !== null) {
                ApplyMarkupJob::dispatch($group->id, $group->jobs_version)
                    ->delay(now()->addSeconds($seconds));
            }
        }
    }
}

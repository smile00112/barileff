<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Settings\OrderStatusDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Settings\StoreOrderStatusRequest;
use Webkul\Admin\Http\Requests\Settings\StoreOrderStatusTransitionRequest;
use Webkul\Admin\Http\Requests\Settings\UpdateOrderStatusRequest;
use Webkul\Sales\Models\OrderStatus;
use Webkul\Sales\Repositories\OrderStatusRepository;
use Webkul\Sales\Repositories\OrderStatusTransitionRepository;
use Webkul\Sales\Repositories\OrderWorkflowSettingRepository;

class OrderStatusController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OrderStatusRepository $orderStatusRepository,
        protected OrderStatusTransitionRepository $orderStatusTransitionRepository,
        protected OrderWorkflowSettingRepository $orderWorkflowSettingRepository
    ) {}

    // -------------------------------------------------------------------------
    // Order Statuses CRUD
    // -------------------------------------------------------------------------

    /**
     * Display a listing of order statuses.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(OrderStatusDataGrid::class)->process();
        }

        return view('admin::settings.order-statuses.index');
    }

    /**
     * Show the form for creating a new order status.
     */
    public function create(): View
    {
        return view('admin::settings.order-statuses.create');
    }

    /**
     * Store a newly created order status.
     */
    public function store(StoreOrderStatusRequest $request): JsonResponse|RedirectResponse
    {
        $orderStatus = $this->orderStatusRepository->create(
            $request->only([
                'code', 'name', 'icon', 'color', 'sort_order',
                'is_active', 'is_terminal', 'is_cancel_state', 'is_payment_required',
            ])
        );

        if ($request->wantsJson()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.order-statuses.index.create-success'),
            ]);
        }

        return redirect()->route('admin.settings.order_statuses.edit', $orderStatus->id)
            ->with('success', trans('admin::app.settings.order-statuses.index.create-success'));
    }

    /**
     * Show the form for editing the specified order status.
     */
    public function edit(int $id): View
    {
        $orderStatus = $this->orderStatusRepository->findOrFail($id);

        $allStatuses = $this->orderStatusRepository->getActive();

        $transitions = $this->orderStatusTransitionRepository->findByField('from_status_code', $orderStatus->code);

        return view('admin::settings.order-statuses.edit', compact('orderStatus', 'allStatuses', 'transitions'));
    }

    /**
     * Update the specified order status.
     */
    public function update(UpdateOrderStatusRequest $request, int $id): RedirectResponse|JsonResponse
    {
        $this->orderStatusRepository->findOrFail($id);

        $this->orderStatusRepository->update(
            $request->only([
                'name', 'icon', 'color', 'sort_order',
                'is_active', 'is_terminal', 'is_cancel_state', 'is_payment_required',
            ]),
            $id
        );

        if ($request->wantsJson()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.order-statuses.index.update-success'),
            ]);
        }

        return redirect()->route('admin.settings.order_statuses.edit', $id)
            ->with('success', trans('admin::app.settings.order-statuses.index.update-success'));
    }

    /**
     * Remove the specified order status.
     * System statuses cannot be deleted.
     */
    public function destroy(int $id): JsonResponse
    {
        $orderStatus = $this->orderStatusRepository->findOrFail($id);

        if ($orderStatus->is_system) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.order-statuses.index.delete-system-error'),
            ], 422);
        }

        $this->orderStatusRepository->delete($id);

        return new JsonResponse([
            'message' => trans('admin::app.settings.order-statuses.index.delete-success'),
        ]);
    }

    // -------------------------------------------------------------------------
    // Transitions
    // -------------------------------------------------------------------------

    /**
     * Store a new transition rule.
     */
    public function storeTransition(StoreOrderStatusTransitionRequest $request): JsonResponse|RedirectResponse
    {
        $this->orderStatusTransitionRepository->create(
            $request->only([
                'from_status_code', 'to_status_code',
                'delivery_type', 'payment_type', 'channel',
                'is_active', 'priority',
            ])
        );

        if ($request->wantsJson()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.order-statuses.transitions.create-success'),
            ]);
        }

        $status = OrderStatus::where('code', $request->from_status_code)->first();

        return redirect()->route('admin.settings.order_statuses.edit', $status->id)
            ->with('success', trans('admin::app.settings.order-statuses.transitions.create-success'));
    }

    /**
     * Update an existing transition rule.
     */
    public function updateTransition(StoreOrderStatusTransitionRequest $request, int $id): JsonResponse
    {
        $this->orderStatusTransitionRepository->update(
            $request->only([
                'from_status_code', 'to_status_code',
                'delivery_type', 'payment_type', 'channel',
                'is_active', 'priority',
            ]),
            $id
        );

        return new JsonResponse([
            'message' => trans('admin::app.settings.order-statuses.transitions.update-success'),
        ]);
    }

    /**
     * Remove a transition rule.
     */
    public function destroyTransition(int $id): JsonResponse|RedirectResponse
    {
        $transition = $this->orderStatusTransitionRepository->findOrFail($id);

        $fromStatusCode = $transition->from_status_code;

        $this->orderStatusTransitionRepository->delete($id);

        if (request()->wantsJson()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.order-statuses.transitions.delete-success'),
            ]);
        }

        $status = OrderStatus::where('code', $fromStatusCode)->first();

        return redirect()->route('admin.settings.order_statuses.edit', $status->id)
            ->with('success', trans('admin::app.settings.order-statuses.transitions.delete-success'));
    }

    // -------------------------------------------------------------------------
    // Workflow settings
    // -------------------------------------------------------------------------

    /**
     * Display the workflow settings page.
     */
    public function workflowSettings(): View
    {
        $newOrderStatus = $this->orderWorkflowSettingRepository->getValue('new_order_status', 'pending');

        $allStatuses = $this->orderStatusRepository->getActive();

        return view('admin::settings.order-statuses.workflow', compact('newOrderStatus', 'allStatuses'));
    }

    /**
     * Save workflow settings.
     */
    public function updateWorkflowSettings(): JsonResponse
    {
        $this->validate(request(), [
            'new_order_status' => ['required', 'string', 'exists:order_statuses,code'],
        ]);

        $this->orderWorkflowSettingRepository->setValue('new_order_status', request('new_order_status'));

        return new JsonResponse([
            'message' => trans('admin::app.settings.order-statuses.workflow.update-success'),
        ]);
    }
}

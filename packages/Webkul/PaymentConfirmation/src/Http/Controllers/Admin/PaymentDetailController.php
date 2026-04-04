<?php

namespace Webkul\PaymentConfirmation\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\Inventory\Repositories\InventorySourceRepository;
use Webkul\PaymentConfirmation\Http\Requests\PaymentDetailRequest;
use Webkul\PaymentConfirmation\Repositories\PaymentDetailRepository;

class PaymentDetailController extends Controller
{
    public function __construct(
        protected PaymentDetailRepository $paymentDetailRepository,
        protected InventorySourceRepository $inventorySourceRepository,
    ) {}

    public function index(): View
    {
        $details = $this->paymentDetailRepository->all();

        return view('paymentconfirmation::admin.payment-details.index', compact('details'));
    }

    public function create(): View
    {
        $inventorySources = $this->inventorySourceRepository->all();

        return view('paymentconfirmation::admin.payment-details.create', compact('inventorySources'));
    }

    public function store(PaymentDetailRequest $request): RedirectResponse
    {
        $this->paymentDetailRepository->create(array_merge(
            $request->validated(),
            ['is_active' => $request->boolean('is_active', true)]
        ));

        session()->flash('success', 'Payment detail created successfully.');

        return redirect()->route('admin.payment-confirmation.payment-details.index');
    }

    public function edit(int $id): View
    {
        $detail = $this->paymentDetailRepository->findOrFail($id);
        $inventorySources = $this->inventorySourceRepository->all();

        return view('paymentconfirmation::admin.payment-details.edit', compact('detail', 'inventorySources'));
    }

    public function update(PaymentDetailRequest $request, int $id): RedirectResponse
    {
        $this->paymentDetailRepository->update(
            array_merge($request->validated(), ['is_active' => $request->boolean('is_active', false)]),
            $id
        );

        session()->flash('success', 'Payment detail updated successfully.');

        return redirect()->route('admin.payment-confirmation.payment-details.index');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->paymentDetailRepository->delete($id);

        session()->flash('success', 'Payment detail deleted.');

        return redirect()->route('admin.payment-confirmation.payment-details.index');
    }
}

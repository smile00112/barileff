<?php

namespace Webkul\Admin\Http\Controllers\Reporting;

use Webkul\Inventory\Repositories\InventorySourceRepository;

class CustomerController extends Controller
{
    /**
     * Request param functions.
     *
     * @var array
     */
    protected $typeFunctions = [
        'total-customers'           => 'getTotalCustomersStats',
        'customers-traffic'         => 'getCustomersTrafficStats',
        'customers-with-most-sales' => 'getCustomersWithMostSales',
        'customers-with-most-orders' => 'getCustomersWithMostOrders',
        'customers-with-most-reviews' => 'getCustomersWithMostReviews',
        'top-customer-groups'       => 'getTopCustomerGroups',
    ];

    /**
     * Create a controller instance.
     */
    public function __construct(
        protected InventorySourceRepository $inventorySourceRepository,
        \Webkul\Admin\Helpers\Reporting $reportingHelper,
    ) {
        parent::__construct($reportingHelper);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin::reporting.customers.index')->with([
            'startDate'        => $this->reportingHelper->getStartDate(),
            'endDate'          => $this->reportingHelper->getEndDate(),
            'inventorySources' => $this->inventorySourceRepository->findWhere(['status' => 1]),
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function view()
    {
        if ($this->validateRequestedType()) {
            abort(404);
        }

        return view('admin::reporting.view')->with([
            'entity'           => 'customers',
            'startDate'        => $this->reportingHelper->getStartDate(),
            'endDate'          => $this->reportingHelper->getEndDate(),
            'inventorySources' => $this->inventorySourceRepository->findWhere(['status' => 1]),
        ]);
    }
}

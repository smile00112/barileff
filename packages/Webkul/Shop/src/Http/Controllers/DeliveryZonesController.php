<?php

namespace Webkul\Shop\Http\Controllers;

/**
 * Storefront delivery zones map page.
 */
class DeliveryZonesController extends Controller
{
    /**
     * Display the delivery zones map.
     */
    public function index()
    {
        return view('shop::delivery-zones.index');
    }
}

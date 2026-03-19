<?php

use Illuminate\Support\Facades\Route;
use Webkul\DeliveryZones\Http\Controllers\Shop\DeliveryZonesController;
use Webkul\Shop\Http\Controllers\API\AddressController;
use Webkul\Shop\Http\Controllers\API\CartController;
use Webkul\Shop\Http\Controllers\API\CategoryController;
use Webkul\Shop\Http\Controllers\API\CompareController;
use Webkul\Shop\Http\Controllers\API\CoreController;
use Webkul\Shop\Http\Controllers\API\CustomerController;
use Webkul\Shop\Http\Controllers\API\OnepageController;
use Webkul\Shop\Http\Controllers\API\ProductController;
use Webkul\Shop\Http\Controllers\API\ReviewController;
use Webkul\Shop\Http\Controllers\API\WishlistController;

Route::group(['prefix' => 'api'], function () {
    /**
     * Cacheable public catalog GET endpoints (1 hour TTL, tag: api-catalog).
     */
    Route::middleware('cache.response:3600')->group(function () {
        Route::controller(DeliveryZonesController::class)->prefix('delivery-zones')->group(function () {
            Route::get('', 'index')->name('shop.api.delivery_zones.index');
        });

        Route::controller(CoreController::class)->prefix('core')->group(function () {
            Route::get('countries', 'getCountries')->name('shop.api.core.countries');

            Route::get('states', 'getStates')->name('shop.api.core.states');
        });

        Route::controller(CategoryController::class)->prefix('categories')->group(function () {
            Route::get('', 'index')->name('shop.api.categories.index');

            Route::get('tree', 'tree')->name('shop.api.categories.tree');

            Route::get('attributes', 'getAttributes')->name('shop.api.categories.attributes');

            Route::get('attributes/{attribute_id}/options', 'getAttributeOptions')
                ->whereNumber('attribute_id')
                ->name('shop.api.categories.attribute_options');

            Route::get('max-price/{id?}', 'getProductMaxPrice')
                ->whereNumber('id')
                ->name('shop.api.categories.max_price');
        });

        Route::controller(ProductController::class)->prefix('products')->group(function () {
            Route::get('', 'index')->name('shop.api.products.index');

            Route::get('{id}/related', 'relatedProducts')
                ->whereNumber('id')
                ->name('shop.api.products.related.index');

            Route::get('{id}/up-sell', 'upSellProducts')
                ->whereNumber('id')
                ->name('shop.api.products.up-sell.index');
        });

        Route::controller(ReviewController::class)->prefix('product/{id}')->group(function () {
            Route::get('reviews', 'index')
                ->whereNumber('id')
                ->name('shop.api.products.reviews.index');

            Route::get('reviews/{review_id}/translate', 'translate')
                ->whereNumber('id')
                ->whereNumber('review_id')
                ->name('shop.api.products.reviews.translate');
        });
    });

    /**
     * Non-cacheable endpoints (mutations, personal data).
     */
    Route::controller(DeliveryZonesController::class)->prefix('delivery-zones')->group(function () {
        Route::post('select', 'select')->name('shop.api.delivery_zones.select');
    });

    Route::controller(ReviewController::class)->prefix('product/{id}')->group(function () {
        Route::post('review', 'store')
            ->whereNumber('id')
            ->name('shop.api.products.reviews.store');
    });

    Route::controller(CompareController::class)->prefix('compare-items')->group(function () {
        Route::get('', 'index')->name('shop.api.compare.index');

        Route::post('', 'store')->name('shop.api.compare.store');

        Route::delete('', 'destroy')->name('shop.api.compare.destroy');

        Route::delete('all', 'destroyAll')->name('shop.api.compare.destroy_all');
    });

    Route::controller(CartController::class)->prefix('checkout/cart')->group(function () {
        Route::get('', 'index')->name('shop.api.checkout.cart.index');

        Route::post('', 'store')->name('shop.api.checkout.cart.store');

        Route::put('', 'update')->name('shop.api.checkout.cart.update');

        Route::delete('', 'destroy')->name('shop.api.checkout.cart.destroy');

        Route::delete('selected', 'destroySelected')->name('shop.api.checkout.cart.destroy_selected');

        Route::post('move-to-wishlist', 'moveToWishlist')->name('shop.api.checkout.cart.move_to_wishlist');

        Route::post('coupon', 'storeCoupon')->name('shop.api.checkout.cart.coupon.apply');

        Route::post('estimate-shipping-methods', 'estimateShippingMethods')->name('shop.api.checkout.cart.estimate_shipping');

        Route::delete('coupon', 'destroyCoupon')->name('shop.api.checkout.cart.coupon.remove');

        Route::get('cross-sell', 'crossSellProducts')->name('shop.api.checkout.cart.cross-sell.index');
    });

    Route::controller(OnepageController::class)->prefix('checkout/onepage')->group(function () {
        Route::get('summary', 'summary')->name('shop.checkout.onepage.summary');

        Route::post('addresses', 'storeAddress')->name('shop.checkout.onepage.addresses.store');

        Route::post('shipping-methods', 'storeShippingMethod')->name('shop.checkout.onepage.shipping_methods.store');

        Route::post('delivery-zone', 'storeDeliveryZone')->name('shop.checkout.onepage.delivery_zone.store');

        Route::post('payment-methods', 'storePaymentMethod')->name('shop.checkout.onepage.payment_methods.store');

        Route::post('orders', 'storeOrder')->name('shop.checkout.onepage.orders.store');
    });

    /**
     * Login routes.
     */
    Route::controller(CustomerController::class)->prefix('customer')->group(function () {
        Route::post('login', 'login')->name('shop.api.customers.session.create');
    });

    Route::group(['middleware' => ['customer'], 'prefix' => 'customer'], function () {
        Route::controller(AddressController::class)->prefix('addresses')->group(function () {
            Route::get('', 'index')->name('shop.api.customers.account.addresses.index');

            Route::post('', 'store')->name('shop.api.customers.account.addresses.store');

            Route::put('edit/{id?}', 'update')
                ->whereNumber('id')
                ->name('shop.api.customers.account.addresses.update');
        });

        Route::controller(WishlistController::class)->prefix('wishlist')->group(function () {
            Route::get('', 'index')->name('shop.api.customers.account.wishlist.index');

            Route::post('', 'store')->name('shop.api.customers.account.wishlist.store');

            Route::post('{id}/move-to-cart', 'moveToCart')
                ->whereNumber('id')
                ->name('shop.api.customers.account.wishlist.move_to_cart');

            Route::delete('all', 'destroyAll')->name('shop.api.customers.account.wishlist.destroy_all');

            Route::delete('{id}', 'destroy')
                ->whereNumber('id')
                ->name('shop.api.customers.account.wishlist.destroy');
        });
    });
});

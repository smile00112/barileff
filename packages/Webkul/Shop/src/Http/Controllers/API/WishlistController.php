<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Webkul\Checkout\Facades\Cart;
use Webkul\Customer\Repositories\WishlistRepository;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shop\Http\Resources\CartResource;
use Webkul\Shop\Http\Resources\WishlistResource;

/**
 * Список желаемого: добавить, получить список, удалить, переместить в корзину. Требуется авторизация.
 *
 * @group Список желаемого
 */
class WishlistController extends APIController
{
    /**
     * Создать экземпляр контроллера.
     *
     * @return void
     */
    public function __construct(
        protected WishlistRepository $wishlistRepository,
        protected ProductRepository $productRepository
    ) {}

    /**
     * Получить список желаемого покупателя.
     */
    public function index(): JsonResource
    {
        $this->removeInactiveItems();

        $items = $this->wishlistRepository
            ->where([
                'channel_id' => core()->getCurrentChannel()->id,
                'customer_id' => auth()->guard('customer')->user()->id,
            ])
            ->get();

        return WishlistResource::collection($items);
    }

    /**
     * Добавить товар в список желаемого.
     */
    public function store(): JsonResource
    {
        $this->validate(request(), [
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $productId = request()->input('product_id');

        $product = $this->productRepository->find($productId);

        if (! $product) {
            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.product-removed'),
            ]);
        }

        $data = [
            'channel_id' => core()->getCurrentChannel()->id,
            'product_id' => $product->id,
            'customer_id' => auth()->guard()->user()->id,
        ];

        if (! $this->wishlistRepository->findOneWhere($data)) {
            Event::dispatch('customer.wishlist.create.before', $productId);

            $wishlist = $this->wishlistRepository->create($data);

            Event::dispatch('customer.wishlist.create.after', $wishlist);

            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.success'),
            ]);
        }

        $this->wishlistRepository->deleteWhere([
            'product_id' => $product->id,
            'customer_id' => auth()->guard()->user()->id,
        ]);

        return new JsonResource([
            'message' => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    /**
     * Переместить товар из списка желаемого в корзину.
     *
     * @urlParam id int required ID элемента списка желаемого. Example: 1
     */
    public function moveToCart(int $id): JsonResource
    {
        $wishlistItem = $this->wishlistRepository->findOneWhere([
            'id' => $id,
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        if (! $wishlistItem) {
            abort(404);
        }

        try {
            Event::dispatch('customer.wishlist.move-to-cart.before', $id);

            $result = Cart::moveToCart($wishlistItem, request()->input('quantity'));

            Event::dispatch('customer.wishlist.move-to-cart.after', $id);

            if ($result) {
                return new JsonResource([
                    'data' => [
                        'wishlist' => WishlistResource::collection($this->wishlistRepository->get()),
                        'cart' => new CartResource(Cart::getCart()),
                    ],

                    'message' => trans('shop::app.customers.account.wishlist.moved-success'),
                ]);
            }

            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.product_or_category.index', $wishlistItem->product->url_key),
                'message' => trans('shop::app.checkout.cart.missing-options'),
            ]);
        } catch (\Exception $exception) {
            return new JsonResource([
                'redirect' => true,
                'data' => route('shop.product_or_category.index', $wishlistItem->product->url_key),
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Удалить товар из списка желаемого.
     *
     * @urlParam id int required ID элемента списка желаемого. Example: 1
     */
    public function destroy(int $id): JsonResource
    {
        Event::dispatch('customer.wishlist.delete.before', $id);

        $success = $this->wishlistRepository->deleteWhere([
            'id' => $id,
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        Event::dispatch('customer.wishlist.delete.after', $id);

        if (! $success) {
            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.remove-fail'),
            ]);
        }

        return new JsonResource([
            'data' => WishlistResource::collection($this->wishlistRepository->get()),
            'message' => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    /**
     * Удалить все товары из списка желаемого.
     */
    public function destroyAll(): JsonResource
    {
        Event::dispatch('customer.wishlist.delete-all.before');

        $success = $this->wishlistRepository->deleteWhere([
            'customer_id' => auth()->guard('customer')->user()->id,
        ]);

        Event::dispatch('customer.wishlist.delete-all.after');

        if (! $success) {
            return new JsonResource([
                'message' => trans('shop::app.customers.account.wishlist.remove-fail'),
            ]);
        }

        return new JsonResource([
            'message' => trans('shop::app.customers.account.wishlist.removed'),
        ]);
    }

    /**
     * Удалить неактивные товары из списка желаемого.
     *
     * @return int
     */
    protected function removeInactiveItems()
    {
        $customer = auth()->guard('customer')->user();

        $customer->load(['wishlist_items.product']);

        $inactiveItemIds = $customer->wishlist_items
            ->filter(fn ($item) => ! $item->product->status)
            ->pluck('product_id')
            ->toArray();

        return $customer->wishlist_items()
            ->whereIn('product_id', $inactiveItemIds)
            ->delete();
    }
}

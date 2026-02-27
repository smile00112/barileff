<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Response;
use Webkul\CartRule\Repositories\CartRuleCouponRepository;
use Webkul\Checkout\Facades\Cart;
use Webkul\Checkout\Models\CartAddress;
use Webkul\Product\Exceptions\InsufficientProductInventoryException;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Shipping\Facades\Shipping;
use Webkul\Shop\Http\Resources\CartResource;
use Webkul\Shop\Http\Resources\ProductResource;

/**
 * Корзина: CRUD, купоны, расчет доставки, cross-sell.
 *
 * @group Корзина
 */
class CartController extends APIController
{
    /**
     * Создать экземпляр контроллера.
     *
     * @return void
     */
    public function __construct(
        protected ProductRepository $productRepository,
        protected CartRuleCouponRepository $cartRuleCouponRepository
    ) {}

    /**
     * Получить корзину.
     */
    public function index(): JsonResource
    {
        Cart::collectTotals();

        $response = [
            'data' => ($cart = Cart::getCart()) ? new CartResource($cart) : null,
        ];

        if (session()->has('info')) {
            $response['message'] = session()->get('info');
        }

        return new JsonResource($response);
    }

    /**
     * Добавить товар в корзину.
     *
     * @bodyParam product_id int required ID товара. Example: 1
     * @bodyParam quantity int Количество (по умолчанию 1). Example: 2
     * @bodyParam is_buy_now int Установите 1 для покупки сразу (редирект на checkout). Example: 0
     *
     * @response 200 {"data": {...}, "message": "Item added to cart."}
     * @response 400 scenario="Insufficient inventory" {"message": "Requested quantity is not available."}
     */
    public function store()
    {
        $this->validate(request(), [
            'product_id' => 'required|integer|exists:products,id',
            'is_buy_now' => 'integer|in:0,1',
            'quantity' => 'integer|min:1',
        ]);

        $product = $this->productRepository->with('parent')->findOrFail(request()->input('product_id'));

        try {
            if (! $product->status) {
                throw new \Exception(trans('shop::app.checkout.cart.inactive-add'));
            }

            $response = [];

            if (request()->get('is_buy_now')) {
                Cart::deActivateCart();

                $response['redirect'] = route('shop.checkout.onepage.index');
            }

            $cart = Cart::addProduct($product, request()->all());

            return new JsonResource(array_merge([
                'data' => new CartResource($cart),
                'message' => trans('shop::app.checkout.cart.item-add-to-cart'),
            ], $response));
        } catch (InsufficientProductInventoryException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $exception) {
            return response()->json([
                'redirect_uri' => route('shop.product_or_category.index', $product->url_key),
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Удалить товар из корзины, если он существует.
     */
    public function destroy(): JsonResource
    {
        $this->validate(request(), [
            'cart_item_id' => 'required|exists:cart_items,id',
        ]);

        Cart::removeItem(request()->input('cart_item_id'));

        Cart::collectTotals();

        return new JsonResource([
            'data' => new CartResource(Cart::getCart()),
            'message' => trans('shop::app.checkout.cart.success-remove'),
        ]);
    }

    /**
     * Удалить выбранные товары из корзины.
     */
    public function destroySelected(): JsonResource
    {
        foreach (request()->input('ids') as $id) {
            Cart::removeItem($id);
        }

        return new JsonResource([
            'data' => new CartResource(Cart::getCart()) ?? null,
            'message' => trans('shop::app.checkout.cart.index.remove-selected-success'),
        ]);
    }

    /**
     * Переместить выбранные товары из корзины в список желаемого.
     */
    public function moveToWishlist(): JsonResource
    {
        foreach (request()->input('ids') as $index => $id) {
            $qty = request()->input('qty')[$index];

            Cart::moveToWishlist($id, $qty);
        }

        return new JsonResource([
            'data' => new CartResource(Cart::getCart()) ?? null,
            'message' => trans('shop::app.checkout.cart.index.move-to-wishlist-success'),
        ]);
    }

    /**
     * Обновить количество товаров в корзине.
     */
    public function update(): JsonResource
    {
        try {
            Cart::updateItems(request()->input());

            return new JsonResource([
                'data' => new CartResource(Cart::getCart()),
                'message' => trans('shop::app.checkout.cart.index.quantity-update'),
            ]);
        } catch (\Exception $exception) {
            return new JsonResource([
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Рассчитать стоимость доставки и налогов.
     */
    public function estimateShippingMethods(): JsonResource
    {
        $this->validate(request(), [
            'country' => 'required',
            'state' => 'required',
            'postcode' => 'required',
            'city' => 'sometimes|string',
            'shipping_method' => 'sometimes|required',
            'delivery_point_lat' => 'nullable|numeric|between:-90,90',
            'delivery_point_lng' => 'nullable|numeric|between:-180,180',
            'delivery_zone_id' => 'nullable|integer|exists:delivery_zones,id',
        ]);

        $cart = Cart::getCart();

        $address = (new CartAddress)->fill([
            'country' => request()->input('country'),
            'state' => request()->input('state'),
            'city' => request()->input('city'),
            'postcode' => request()->input('postcode'),
            'cart_id' => $cart->id,
        ]);

        $cart->setRelation('billing_address', $address);

        $cart->setRelation('shipping_address', $address);

        Cart::setCart($cart);

        $cart->delivery_point_lat = request()->input('delivery_point_lat');
        $cart->delivery_point_lng = request()->input('delivery_point_lng');
        $cart->delivery_zone_id = request()->input('delivery_zone_id');
        $cart->delivery_zone_mode = request()->filled('delivery_zone_id') ? 'manual' : (($cart->delivery_point_lat && $cart->delivery_point_lng) ? 'auto' : null);
        $cart->save();

        if (request()->has('shipping_method')) {
            Cart::saveShippingMethod(request()->input('shipping_method'));
        }

        Cart::collectTotals();

        $cartResource = (new CartResource(Cart::getCart()))->jsonSerialize();

        return new JsonResource([
            'data' => [
                'cart' => $cartResource,
                'shipping_methods' => array_values(Shipping::collectRates()['shippingMethods']),
            ],
        ]);
    }

    /**
     * Применить купон к корзине.
     */
    public function storeCoupon()
    {
        $validatedData = $this->validate(request(), [
            'code' => 'required',
        ]);

        try {
            if (strlen($validatedData['code'])) {
                $coupon = $this->cartRuleCouponRepository->findOneByField('code', $validatedData['code']);

                if (! $coupon) {
                    return (new JsonResource([
                        'data' => new CartResource(Cart::getCart()),
                        'message' => trans('shop::app.checkout.coupon.invalid'),
                    ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                if ($coupon->cart_rule->status) {
                    if (Cart::getCart()->coupon_code == $coupon->code) {
                        return (new JsonResource([
                            'data' => new CartResource(Cart::getCart()),
                            'message' => trans('shop::app.checkout.coupon.already-applied'),
                        ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    Cart::setCouponCode($coupon->code)->collectTotals();

                    if (Cart::getCart()->coupon_code == $coupon->code) {
                        return new JsonResource([
                            'data' => new CartResource(Cart::getCart()),
                            'message' => trans('shop::app.checkout.coupon.success-apply'),
                        ]);
                    }
                }

                return (new JsonResource([
                    'data' => new CartResource(Cart::getCart()),
                    'message' => trans('Coupon not found.'),
                ]))->response()->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        } catch (\Exception $e) {
            return (new JsonResource([
                'data' => new CartResource(Cart::getCart()),
                'message' => trans('shop::app.checkout.coupon.error'),
            ]))->response()->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Удалить примененный купон из корзины.
     */
    public function destroyCoupon(): JsonResource
    {
        Cart::removeCouponCode()->collectTotals();

        return new JsonResource([
            'data' => new CartResource(Cart::getCart()),
            'message' => trans('shop::app.checkout.coupon.remove'),
        ]);
    }

    /**
     * Получить список cross-sell товаров.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource::collection
     */
    public function crossSellProducts()
    {
        $cart = Cart::getCart();

        if (! $cart) {
            return new JsonResource([
                'data' => [],
            ]);
        }

        $productIds = $cart->items->pluck('product_id')->toArray();

        $products = $this->productRepository
            ->select('products.*', 'product_cross_sells.child_id')
            ->join('product_cross_sells', 'products.id', '=', 'product_cross_sells.child_id')
            ->whereIn('product_cross_sells.parent_id', $productIds)
            ->whereNotIn('product_cross_sells.child_id', $productIds)
            ->groupBy('product_cross_sells.child_id')
            ->take(core()->getConfigData('catalog.products.cart_view_page.no_of_cross_sells_products'))
            ->get();

        return ProductResource::collection($products);
    }
}

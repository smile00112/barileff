<?php

namespace Webkul\Shop\Http\Controllers\API;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Shop\Http\Requests\Customer\AddressRequest;
use Webkul\Shop\Http\Resources\AddressResource;

/**
 * Адреса покупателя: список, создание, обновление. Требуется авторизация.
 *
 * @group Адреса покупателя
 */
class AddressController extends APIController
{
    /**
     * Создать экземпляр контроллера.
     *
     * @return void
     */
    public function __construct(protected CustomerAddressRepository $customerAddressRepository) {}

    /**
     * Получить адреса покупателя.
     */
    public function index(): JsonResource
    {
        $customer = auth()->guard('customer')->user();

        return AddressResource::collection($customer->addresses);
    }

    /**
     * Создать новый адрес покупателя.
     */
    public function store(AddressRequest $request): JsonResource
    {
        $customer = auth()->guard('customer')->user();

        Event::dispatch('customer.addresses.create.before');

        $data = array_merge($request->only([
            'company_name',
            'first_name',
            'last_name',
            'address',
            'country',
            'state',
            'city',
            'postcode',
            'phone',
            'default_address',
            'email',
            'additional',
        ]), [
            'customer_id' => $customer->id,
            'address' => implode(PHP_EOL, array_filter($request->input('address'))),
        ]);

        if (! empty($data['default_address'])) {
            $this->customerAddressRepository->where('customer_id', $data['customer_id'])
                ->where('default_address', 1)
                ->update(['default_address' => 0]);
        }

        $customerAddress = $this->customerAddressRepository->create($data);

        Event::dispatch('customer.addresses.create.after', $customerAddress);

        return new JsonResource([
            'data' => new AddressResource($customerAddress),
            'message' => trans('shop::app.customers.account.addresses.index.create-success'),
        ]);
    }

    /**
     * Обновить адрес покупателя.
     *
     * @urlParam id int ID адреса. Example: 1
     */
    public function update(AddressRequest $request, ?int $id = null): JsonResource
    {
        $customer = auth()->guard('customer')->user();

        $addressId = $id ?? (int) $request->input('id');

        $addressToUpdate = $this->customerAddressRepository->findOrFail($addressId);

        if ($addressToUpdate->customer_id !== $customer->id) {
            abort(403);
        }

        Event::dispatch('customer.addresses.update.before');

        $customerAddress = $this->customerAddressRepository->update(array_merge($request->only([
            'company_name',
            'first_name',
            'last_name',
            'address',
            'country',
            'state',
            'city',
            'postcode',
            'phone',
            'default_address',
            'email',
            'additional',
        ]), [
            'customer_id' => $customer->id,
            'address' => implode(PHP_EOL, array_filter($request->input('address'))),
        ]), $addressId);

        Event::dispatch('customer.addresses.update.after', $customerAddress);

        return new JsonResource([
            'data' => new AddressResource($customerAddress),
            'message' => trans('shop::app.customers.account.addresses.index.update-success'),
        ]);
    }

    /**
     * Удалить адрес покупателя.
     */
    public function delete(int $id): JsonResource
    {
        $customer = auth()->guard('customer')->user();

        $address = $this->customerAddressRepository->findOrFail($id);

        if ($address->customer_id !== $customer->id) {
            abort(403);
        }

        Event::dispatch('customer.addresses.delete.before', $id);

        $this->customerAddressRepository->delete($id);

        Event::dispatch('customer.addresses.delete.after', $id);

        return new JsonResource([
            'message' => trans('shop::app.customers.account.addresses.index.delete-success'),
        ]);
    }
}

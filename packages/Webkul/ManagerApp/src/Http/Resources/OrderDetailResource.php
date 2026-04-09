<?php

namespace Webkul\ManagerApp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full order details for the expanded order view.
 */
class OrderDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'increment_id'        => $this->increment_id,
            'status'              => $this->status,
            'status_label'        => $this->status_label,
            'customer_name'       => $this->customer_full_name,
            'customer_email'      => $this->customer_email,
            'grand_total'         => $this->grand_total,
            'sub_total'           => $this->sub_total,
            'discount_amount'     => $this->discount_amount,
            'tax_amount'          => $this->tax_amount,
            'shipping_amount'     => $this->shipping_amount,
            'base_currency_code'  => $this->base_currency_code,
            'total_item_count'    => $this->total_item_count,
            'inventory_source_id' => $this->inventory_source_id,
            'shipping_method'     => $this->shipping_title,
            'payment_method'      => $this->payment?->method_title,
            'billing_address'     => $this->billing_address ? [
                'name'     => $this->billing_address->name,
                'address'  => $this->billing_address->address,
                'city'     => $this->billing_address->city,
                'phone'    => $this->billing_address->phone,
            ] : null,
            'shipping_address'    => $this->shipping_address ? [
                'name'     => $this->shipping_address->name,
                'address'  => $this->shipping_address->address,
                'city'     => $this->shipping_address->city,
                'phone'    => $this->shipping_address->phone,
            ] : null,
            'items'               => $this->items->map(fn ($item) => [
                'id'         => $item->id,
                'name'       => $item->name,
                'sku'        => $item->sku,
                'qty_ordered' => $item->qty_ordered,
                'qty_shipped' => $item->qty_shipped,
                'price'      => $item->price,
                'total'      => $item->total,
            ]),
            'comments'            => $this->comments->map(fn ($c) => [
                'id'         => $c->id,
                'comment'    => $c->comment,
                'created_at' => $c->created_at?->toIso8601String(),
            ]),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}

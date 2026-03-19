<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'sku' => $this->sku,
            'name' => $this->name,
            'price' => $this->price,
            'formatted_price' => core()->formatPrice($this->price),
            'images' => $this->images,
            'inventories' => $this->inventories,
            'is_options_required' => ! $this->getTypeInstance()->canBeAddedToCartWithoutOptions(),
            'is_saleable' => $this->getTypeInstance()->isSaleable(),
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier ? [
                'id' => $this->supplier->id,
                'name' => $this->supplier->name,
                'contact_name' => $this->supplier->contact_name,
                'contact_email' => $this->supplier->contact_email,
                'contact_phone' => $this->supplier->contact_phone,
                'address' => $this->supplier->address,
                'notes' => $this->supplier->notes,
                'status' => $this->supplier->status,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'locale' => $tag->locale,
            ])->all()),
            ...$this->getExposedApiAttributes(),
        ];
    }
}

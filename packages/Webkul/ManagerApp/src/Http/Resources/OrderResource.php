<?php

namespace Webkul\ManagerApp\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight order representation for the list view.
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'increment_id'          => $this->increment_id,
            'status'                => $this->status,
            'status_label'          => $this->status_label,
            'customer_name'         => $this->customer_full_name,
            'customer_email'        => $this->customer_email,
            'grand_total'           => $this->grand_total,
            'base_currency_code'    => $this->base_currency_code,
            'total_item_count'      => $this->total_item_count,
            'inventory_source_id'   => $this->inventory_source_id,
            'created_at'            => $this->created_at?->toIso8601String(),
            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}

<?php

namespace Webkul\Admin\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name ?? $this->translations->sortByDesc(fn ($t) => $t->locale === config('app.fallback_locale'))->first()?->name ?? '',
            'slug' => $this->slug,
            'url' => $this->url,
            'status' => $this->status,
            'position' => $this->position,
            'children' => self::collection($this->children),
        ];
    }
}

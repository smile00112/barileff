<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryTreeResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'name' => html_entity_decode($this->name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'slug' => $this->slug,
            'url' => $this->url,
            'status' => $this->status,
            'children' => self::collection($this->children),
        ];
    }
}

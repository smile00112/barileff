<?php

namespace Webkul\Shop\Http\Resources;

use Illuminate\Database\Eloquent\Model;
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
            'name' => html_entity_decode($this->name, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'slug' => $this->slug,
            'url' => $this->url,
            'status' => $this->status,
            'logo' => $this->when($this->logo_path, [
                'small_image_url' => url('cache/small/'.$this->logo_path),
                'medium_image_url' => url('cache/medium/'.$this->logo_path),
                'large_image_url' => url('cache/large/'.$this->logo_path),
                'original_image_url' => url('cache/original/'.$this->logo_path),
            ]),
            'additional' => $this->normalizedAdditional(),
            'children' => self::collection($this->children),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizedAdditional(): array
    {
        $value = $this->resource instanceof Model
            ? $this->resource->getRawOriginal('additional')
            : $this->additional;

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return json_decode($value, true) ?: [];
        }

        return [];
    }
}

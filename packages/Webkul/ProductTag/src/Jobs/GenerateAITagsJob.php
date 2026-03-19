<?php

namespace Webkul\ProductTag\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Product\Contracts\Product;
use Webkul\ProductTag\Repositories\TagRepository;
use Webkul\ProductTag\Services\GigaChatTagService;

class GenerateAITagsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $productId) {}

    public function handle(GigaChatTagService $service, TagRepository $tagRepository): void
    {
        /** @var Product $product */
        $product = app(Product::class)::find($this->productId);

        if (! $product) {
            return;
        }

        $names = $service->generateTags($product);

        if (empty($names)) {
            return;
        }

        $tagIds = $tagRepository->syncByNames($names);

        $product->tags()->syncWithoutDetaching($tagIds);
    }
}

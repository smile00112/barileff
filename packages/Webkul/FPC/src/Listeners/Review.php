<?php

namespace Webkul\FPC\Listeners;

use Spatie\ResponseCache\Facades\ResponseCache;
use Webkul\FPC\Concerns\ClearsApiCache;
use Webkul\Product\Repositories\ProductReviewRepository;

class Review
{
    use ClearsApiCache;

    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(protected ProductReviewRepository $productReviewRepository) {}

    /**
     * After review is updated
     *
     * @param  \Webkul\Product\Contracts\Review  $review
     * @return void
     */
    public function afterUpdate($review)
    {
        ResponseCache::forget('/'.$review->product->url_key);

        $this->clearApiCacheAndWarm();
    }

    /**
     * Before review is deleted
     *
     * @param  \Webkul\Product\Contracts\Review  $review
     * @return void
     */
    public function beforeDelete($reviewId)
    {
        $review = $this->productReviewRepository->find($reviewId);

        ResponseCache::forget('/'.$review->product->url_key);

        $this->clearApiCacheAndWarm();
    }
}

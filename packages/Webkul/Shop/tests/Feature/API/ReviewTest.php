<?php

use function Pest\Laravel\getJson;

it('returns not found for non-numeric product id in reviews index route', function () {
    getJson(route('shop.api.products.reviews.index', ['id' => 'architecto']))
        ->assertNotFound();
});

it('returns not found for non-numeric review id in reviews translate route', function () {
    getJson(route('shop.api.products.reviews.translate', [
        'id' => 1,
        'review_id' => 'architecto',
    ]))->assertNotFound();
});

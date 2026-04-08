<?php

use Webkul\Installer\Database\Seeders\ProductTableSeeder;

it('keeps non localized product attributes for the configured default locale', function () {
    $seeder = new ProductTableSeeder;
    $localeSpecificAttributes = ['name', 'url_key', 'short_description', 'description', 'meta_title', 'meta_keywords', 'meta_description'];

    expect($seeder->shouldSkipAttributeValue('status', 'ru', 'ru', $localeSpecificAttributes))->toBeFalse()
        ->and($seeder->shouldSkipAttributeValue('visible_individually', 'ru', 'ru', $localeSpecificAttributes))->toBeFalse()
        ->and($seeder->shouldSkipAttributeValue('price', 'ru', 'ru', $localeSpecificAttributes))->toBeFalse()
        ->and($seeder->shouldSkipAttributeValue('status', 'en', 'ru', $localeSpecificAttributes))->toBeTrue()
        ->and($seeder->shouldSkipAttributeValue('name', 'en', 'ru', $localeSpecificAttributes))->toBeFalse();
});

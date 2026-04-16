<?php

it('installer countries.json only contains Armenia Belarus and Russia', function () {
    $path = base_path('packages/Webkul/Installer/src/Data/countries.json');

    $countries = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    expect($countries)->toHaveCount(3);

    $codes = collect($countries)->pluck('code')->sort()->values()->all();

    expect($codes)->toBe(['AM', 'BY', 'RU']);
});

it('installer states.json is a valid array (may be empty)', function () {
    $path = base_path('packages/Webkul/Installer/src/Data/states.json');

    $states = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

    expect($states)->toBeArray();
});

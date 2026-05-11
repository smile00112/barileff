<?php

it('keeps mobile product card title min-height and add-to-cart spacing in the template', function () {
    $path = base_path('packages/Webkul/Shop/src/Resources/views/components/products/card.blade.php');

    expect(file_exists($path))->toBeTrue();

    $contents = file_get_contents($path);

    expect($contents)->toContain('max-md:min-h-[35px]')
        ->and($contents)->toContain('max-md:mt-2.5');
});

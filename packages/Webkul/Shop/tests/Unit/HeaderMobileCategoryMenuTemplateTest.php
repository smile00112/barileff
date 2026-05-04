<?php

it('includes decoration category handling in mobile header menu template', function () {
    $path = base_path('packages/Webkul/Shop/src/Resources/views/components/layouts/header/mobile/index.blade.php');

    expect(file_exists($path))->toBeTrue();

    $contents = file_get_contents($path);

    expect($contents)->toContain('isDecorationCategory')
        ->and($contents)->toContain('decoration');
});

<?php

it('does not include horizontal sidebar category strip helpers in desktop header blade', function () {
    $path = base_path('packages/Webkul/Shop/src/Resources/views/components/layouts/header/desktop/bottom.blade.php');

    expect(file_exists($path))->toBeTrue();

    $contents = file_get_contents($path);

    expect($contents)->not->toContain('categories.slice(0, 4)')
        ->and($contents)->not->toContain('pairCategoryChildren');
});

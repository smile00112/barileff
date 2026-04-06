<?php

$projectRoot = dirname(__DIR__, 2);

test('installer database seeder runs inventory before core', function () use ($projectRoot): void {
    $content = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Database/Seeders/DatabaseSeeder.php');

    $inventoryPos = strpos($content, 'InventorySeeder::class');
    $corePos = strpos($content, 'CoreSeeder::class');

    expect($inventoryPos)->not->toBeFalse();
    expect($corePos)->not->toBeFalse();
    expect($inventoryPos)->toBeLessThan($corePos);
});

<?php

$projectRoot = dirname(__DIR__, 2);

/**
 * Full migrate:fresh --seed integration is environment-specific: Bagisto core seeders assume
 * MySQL-style FK/session behavior; PostgreSQL and SQLite fail during core seed in common setups.
 * These tests lock in wiring so custom attributes run after Bagisto seeders via the app seeder and installers.
 */
test('custom product attributes seeder is registered in database seeder', function () use ($projectRoot): void {
    $content = file_get_contents($projectRoot.'/database/seeders/DatabaseSeeder.php');

    expect($content)->toContain('CustomProductAttributesSeeder');
    expect($content)->toContain('$this->call(CustomProductAttributesSeeder::class)');
});

test('bagisto install command invokes custom product attributes seeder', function () use ($projectRoot): void {
    $content = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Console/Commands/Installer.php');

    expect($content)->toContain('CustomProductAttributesSeeder');
    expect($content)->toContain('app(CustomProductAttributesSeeder::class)->run()');
});

test('installer database manager invokes custom product attributes seeder', function () use ($projectRoot): void {
    $content = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Helpers/DatabaseManager.php');

    expect($content)->toContain('CustomProductAttributesSeeder');
    expect($content)->toContain('app(CustomProductAttributesSeeder::class)->run()');
});

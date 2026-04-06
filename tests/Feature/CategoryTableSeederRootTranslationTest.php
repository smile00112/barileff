<?php

$projectRoot = dirname(__DIR__, 2);

test('category table seeder root translation includes url_path', function () use ($projectRoot): void {
    $content = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Database/Seeders/Category/CategoryTableSeeder.php');

    expect($content)->toContain("'slug' => 'root'");
    expect($content)->toContain("'url_path' => ''");
});

<?php

$projectRoot = dirname(__DIR__, 2);

test('attribute installer seeders run in family, attributes, groups, options order', function () use ($projectRoot): void {
    $content = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Database/Seeders/Attribute/DatabaseSeeder.php');

    $expectedOrder = [
        'AttributeFamilyTableSeeder',
        'AttributeTableSeeder',
        'AttributeGroupTableSeeder',
        'AttributeOptionTableSeeder',
    ];

    $lastPosition = -1;

    foreach ($expectedOrder as $class) {
        $position = strpos($content, $class);

        expect($position)->not->toBeFalse("Expected {$class} in Attribute DatabaseSeeder");
        expect($position)->toBeGreaterThan($lastPosition);

        $lastPosition = $position;
    }
});

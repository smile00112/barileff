<?php

$projectRoot = dirname(__DIR__, 2);

test('custom product attributes seeder uses next group id and PostgreSQL sequence sync', function () use ($projectRoot): void {
    $content = file_get_contents($projectRoot.'/database/seeders/CustomProductAttributesSeeder.php');

    expect($content)->toContain('SyncsPostgreSqlIdentitySequence');
    expect($content)->toContain('->max(\'id\') + 1');
    expect($content)->toContain('\'id\' => $nextGroupId');
});

test('bagisto attribute seeders sync PostgreSQL sequence after explicit ids', function () use ($projectRoot): void {
    $group = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Database/Seeders/Attribute/AttributeGroupTableSeeder.php');
    $attributes = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Database/Seeders/Attribute/AttributeTableSeeder.php');
    $options = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Database/Seeders/Attribute/AttributeOptionTableSeeder.php');

    expect($group)->toContain('syncPostgreSqlAttributeGroupsIdSequence');
    expect($attributes)->toContain('syncPostgreSqlAttributesIdSequence');
    expect($options)->toContain('syncPostgreSqlAttributeOptionsIdSequence');
});

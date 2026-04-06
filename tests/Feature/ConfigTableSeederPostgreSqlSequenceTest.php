<?php

$projectRoot = dirname(__DIR__, 2);

test('config table seeder syncs PostgreSQL sequence after explicit ids', function () use ($projectRoot): void {
    $content = file_get_contents($projectRoot.'/packages/Webkul/Installer/src/Database/Seeders/Core/ConfigTableSeeder.php');

    expect($content)->toContain('syncPostgreSqlCoreConfigIdSequence');
    expect($content)->toContain('pg_get_serial_sequence');
    expect($content)->toContain('setval');
});

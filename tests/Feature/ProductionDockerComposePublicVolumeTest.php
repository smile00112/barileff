<?php

use Symfony\Component\Yaml\Yaml;

$projectRoot = dirname(__DIR__, 2);

test('production compose keeps built public assets from the image available to app and nginx', function () use ($projectRoot): void {
    $compose = Yaml::parseFile($projectRoot.'/docker-compose.prod.yml');

    expect($compose['volumes'])->toHaveKey('public_assets');

    $appVolumes = $compose['services']['app']['volumes'];
    $nginxVolumes = $compose['services']['nginx']['volumes'];

    expect($appVolumes)
        ->toContain('public_assets:/var/www/html/public')
        ->not->toContain('./public:/var/www/html/public');

    expect($nginxVolumes)
        ->toContain('public_assets:/var/www/html/public:ro')
        ->not->toContain('./public:/var/www/html/public:ro');
});

test('production image syncs built public assets into the shared public volume on startup', function () use ($projectRoot): void {
    $dockerfile = file_get_contents($projectRoot.'/Dockerfile');
    $entrypoint = file_get_contents($projectRoot.'/docker/php/entrypoint.sh');

    expect($dockerfile)->toContain('RUN cp -a public public-image');
    expect($entrypoint)->toContain('sync_public_assets');
    expect($entrypoint)->toContain('cp -a /var/www/html/public-image/. /var/www/html/public/');
});

test('production startup clears cached html after public asset hashes change', function () use ($projectRoot): void {
    $entrypoint = file_get_contents($projectRoot.'/docker/php/entrypoint.sh');

    $syncPosition = strpos($entrypoint, 'sync_public_assets');
    $clearPosition = strpos($entrypoint, 'run_artisan_optional responsecache:clear');

    expect($clearPosition)
        ->not->toBeFalse()
        ->and($clearPosition)->toBeGreaterThan($syncPosition);
});

test('production update reloads octane workers after clearing cached html', function () use ($projectRoot): void {
    $updateScript = file_get_contents($projectRoot.'/update.sh');

    $clearPosition = strpos($updateScript, 'php artisan responsecache:clear');
    $reloadPosition = strpos($updateScript, 'php artisan octane:reload');

    expect($reloadPosition)
        ->not->toBeFalse()
        ->and($reloadPosition)->toBeGreaterThan($clearPosition);
});

<?php

use Webkul\DataTransfer\Helpers\RemoteProductImageUrl;

it('accepts https URLs with cyrillic path segments rejected by filter_var', function () {
    $raw = 'https://sinicaxo.store/wp-content/uploads/2024/03/Пивной_напиток_Эсса_вкус_апельсина_и_вишни.jpg';

    expect(filter_var($raw, FILTER_VALIDATE_URL))->toBeFalse();

    $normalized = RemoteProductImageUrl::normalizeHttpUrlForRequest($raw);

    expect($normalized)->toBeString()
        ->and($normalized)->toStartWith('https://sinicaxo.store/wp-content/uploads/2024/03/')
        ->and($normalized)->toEndWith('.jpg')
        ->and($normalized)->toContain('%D0%');
});

it('returns null for non absolute or invalid schemes', function () {
    expect(RemoteProductImageUrl::normalizeHttpUrlForRequest(''))->toBeNull()
        ->and(RemoteProductImageUrl::normalizeHttpUrlForRequest('   '))->toBeNull()
        ->and(RemoteProductImageUrl::normalizeHttpUrlForRequest('/relative/path.jpg'))->toBeNull()
        ->and(RemoteProductImageUrl::normalizeHttpUrlForRequest('javascript:alert(1)'))->toBeNull()
        ->and(RemoteProductImageUrl::normalizeHttpUrlForRequest('ftp://example.com/a.jpg'))->toBeNull();
});

it('preserves query and fragment without re-encoding them', function () {
    $url = 'https://example.com/файл.jpg?x=1&y=тест#frag';

    $normalized = RemoteProductImageUrl::normalizeHttpUrlForRequest($url);

    expect($normalized)->toContain('?x=1&y=тест')
        ->and($normalized)->toEndWith('#frag');
});

it('normalizes already percent-encoded path segments', function () {
    $encoded = 'https://example.com/wp-content/%D0%9F%D0%B8%D0%B2%D0%BD%D0%BE%D0%B9.jpg';

    $normalized = RemoteProductImageUrl::normalizeHttpUrlForRequest($encoded);

    expect($normalized)->toBe($encoded);
});

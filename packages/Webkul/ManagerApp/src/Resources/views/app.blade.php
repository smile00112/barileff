<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta name="theme-color" content="#2563eb" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="apple-mobile-web-app-title" content="Manager App" />

    <title>Manager App</title>

    <link rel="manifest" href="/manager/manifest.webmanifest" />
    <link rel="apple-touch-icon" href="/manager/icons/icon-192.png" />

    {{-- Vite-built assets from public/manager --}}
    @php
        $manifest = file_exists(public_path('manager/.vite/manifest.json'))
            ? json_decode(file_get_contents(public_path('manager/.vite/manifest.json')), true)
            : [];
        $entry = $manifest['resources/js/main.js'] ?? null;
    @endphp

    @if ($entry)
        <link rel="modulepreload" href="/manager/{{ $entry['file'] }}" />
        @foreach ($entry['css'] ?? [] as $css)
            <link rel="stylesheet" href="/manager/{{ $css }}" />
        @endforeach
    @endif
</head>
<body>
    <div id="app"></div>

    @if ($entry)
        <script type="module" src="/manager/{{ $entry['file'] }}"></script>
    @else
        {{-- Dev fallback: assets served by the package's Vite dev server --}}
        <script type="module" src="http://localhost:5174/@vite/client"></script>
        <script type="module" src="http://localhost:5174/resources/js/main.js"></script>
    @endif
</body>
</html>

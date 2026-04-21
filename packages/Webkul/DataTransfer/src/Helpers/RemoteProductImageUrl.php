<?php

namespace Webkul\DataTransfer\Helpers;

/**
 * Normalizes absolute HTTP(S) URLs for downloading remote product images during catalog import.
 *
 * PHP's {@see filter_var()} with {@see FILTER_VALIDATE_URL} rejects many valid URLs that contain
 * non-ASCII characters in the path (for example Cyrillic filenames on WordPress uploads).
 */
final class RemoteProductImageUrl
{
    /**
     * Return an HTTP(S) URL safe for clients to request, or null if the value is not a valid absolute URL.
     *
     * Path segments are percent-encoded per RFC 3986 (already-encoded segments are normalized via decode/encode).
     */
    public static function normalizeHttpUrlForRequest(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['path']) && $parts['path'] !== '') {
            $segments = explode('/', $parts['path']);

            $parts['path'] = implode('/', array_map(
                static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
                $segments
            ));
        }

        $normalized = $scheme.'://';

        if (isset($parts['user'])) {
            $normalized .= $parts['user'];

            if (isset($parts['pass'])) {
                $normalized .= ':'.$parts['pass'];
            }

            $normalized .= '@';
        }

        $normalized .= $parts['host'];

        if (isset($parts['port'])) {
            $normalized .= ':'.$parts['port'];
        }

        $normalized .= $parts['path'] ?? '';

        if (isset($parts['query'])) {
            $normalized .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $normalized .= '#'.$parts['fragment'];
        }

        return $normalized;
    }
}

<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dev\CategoryImageCsvImportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Webkul\Category\Models\Category;

class CategoryImageCsvImportController extends Controller
{
    private const MAX_REMOTE_IMAGE_BYTES = 12582912;

    public function __invoke(CategoryImageCsvImportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $target = ($validated['target'] ?? null) === 'banner' ? 'banner' : 'logo';
        $column = $target === 'banner' ? 'banner_path' : 'logo_path';

        $file = $request->file('csv');
        $realPath = $file->getRealPath();

        if ($realPath === false) {
            return response()->json(['message' => 'Could not read uploaded file.'], 422);
        }

        $handle = fopen($realPath, 'rb');

        if ($handle === false) {
            return response()->json(['message' => 'Could not open uploaded file.'], 422);
        }

        $firstBytes = fread($handle, 3) ?: '';
        if ($firstBytes !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $stats = [
            'lines_read' => 0,
            'skipped_header' => false,
            'skipped_empty_name' => 0,
            'skipped_no_url' => 0,
            'not_found' => 0,
            'updated' => 0,
            'download_or_image_errors' => 0,
            'errors' => [],
        ];

        $lineNumber = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $stats['lines_read']++;

            if ($this->looksLikeHeaderRow($row)) {
                $stats['skipped_header'] = true;

                continue;
            }

            $name = trim((string) ($row[0] ?? ''));
            $rawUrl = trim((string) ($row[1] ?? ''));

            if ($name === '') {
                $stats['skipped_empty_name']++;

                continue;
            }

            if ($rawUrl === '') {
                $stats['skipped_no_url']++;

                continue;
            }

            $url = html_entity_decode($rawUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $normalizedUrl = $this->normalizeHttpUrl($url);

            if ($normalizedUrl === null) {
                $stats['errors'][] = [
                    'line' => $lineNumber,
                    'name' => $name,
                    'message' => 'Invalid image URL.',
                ];
                $stats['download_or_image_errors']++;

                continue;
            }

            $category = $this->findCategoryByExactName($name);

            if (! $category) {
                $stats['not_found']++;

                continue;
            }

            try {
                $this->assignRemoteImageToCategory($category, $normalizedUrl, $column);
                $stats['updated']++;
            } catch (\Throwable $e) {
                Log::warning('Dev category CSV image import: row failed.', [
                    'line' => $lineNumber,
                    'category_id' => $category->id,
                    'name' => $name,
                    'url' => $url,
                    'normalized_url' => $normalizedUrl,
                    'message' => $e->getMessage(),
                ]);
                $stats['errors'][] = [
                    'line' => $lineNumber,
                    'name' => $name,
                    'message' => $e->getMessage(),
                ];
                $stats['download_or_image_errors']++;
            }
        }

        fclose($handle);

        return response()->json([
            'target' => $target,
            'summary' => $stats,
        ]);
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function looksLikeHeaderRow(array $row): bool
    {
        $c1 = mb_strtolower(trim((string) ($row[0] ?? '')));
        $c2 = trim((string) ($row[1] ?? ''));

        if ($c2 === '') {
            return false;
        }

        $c2Lower = mb_strtolower($c2);

        if (preg_match('/\.(jpe?g|png|gif|webp|svg|bmp|ico)(\?|#|$)/ui', $c2)) {
            return false;
        }

        if (str_contains($c1, 'http://') || str_contains($c1, 'https://')) {
            return false;
        }

        return str_contains($c1, 'названи')
            && (str_contains($c2Lower, 'url') || str_contains($c2Lower, 'изображен'));
    }

    /**
     * Build an ASCII-only HTTP(S) URI (RFC 3986) so filter_var / Guzzle accept IRIs with Unicode in path.
     */
    private function normalizeHttpUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) $parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = (string) $parts['host'];

        if (function_exists('idn_to_ascii')) {
            $asciiHost = @idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

            if ($asciiHost !== false) {
                $host = $asciiHost;
            }
        }

        $userInfo = '';

        if (isset($parts['user'])) {
            $userInfo = rawurlencode((string) $parts['user']);

            if (isset($parts['pass'])) {
                $userInfo .= ':'.rawurlencode((string) $parts['pass']);
            }

            $userInfo .= '@';
        }

        $hostWithPort = $host;

        if (! empty($parts['port'])) {
            $hostWithPort .= ':'.$parts['port'];
        }

        $path = $parts['path'] ?? '';

        if ($path !== '') {
            $segments = explode('/', (string) $path);
            $segments = array_map(function (string $segment): string {
                if ($segment === '') {
                    return '';
                }

                return rawurlencode(urldecode($segment));
            }, $segments);
            $path = implode('/', $segments);
        }

        $built = $scheme.'://'.$userInfo.$hostWithPort.$path;

        if (isset($parts['query'])) {
            $built .= '?'.$parts['query'];
        }

        if (isset($parts['fragment'])) {
            $built .= '#'.rawurlencode(urldecode((string) $parts['fragment']));
        }

        if (! filter_var($built, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $built;
    }

    private function findCategoryByExactName(string $name): ?Category
    {
        return Category::query()
            ->whereHas('translations', function ($query) use ($name) {
                $query->where('name', $name);
            })
            ->first();
    }

    private function assignRemoteImageToCategory(Category $category, string $url, string $column): void
    {
        $response = Http::timeout(30)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status());
        }

        $content = $response->body();

        if (strlen($content) > self::MAX_REMOTE_IMAGE_BYTES) {
            throw new \RuntimeException('Image body exceeds size limit.');
        }

        $image = (new ImageManager)->make($content)->encode('webp');

        $relativePath = 'category/'.$category->id.'/'.Str::random(40).'.webp';

        if ($category->{$column}) {
            Storage::delete($category->{$column});
        }

        Storage::put($relativePath, $image);

        $category->{$column} = $relativePath;
        $category->save();
    }
}

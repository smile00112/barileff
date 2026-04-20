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

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
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
                $this->assignRemoteImageToCategory($category, $url, $column);
                $stats['updated']++;
            } catch (\Throwable $e) {
                Log::warning('Dev category CSV image import: row failed.', [
                    'line' => $lineNumber,
                    'category_id' => $category->id,
                    'name' => $name,
                    'url' => $url,
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

        if (filter_var($c2, FILTER_VALIDATE_URL)) {
            return false;
        }

        return str_contains($c1, 'названи');
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

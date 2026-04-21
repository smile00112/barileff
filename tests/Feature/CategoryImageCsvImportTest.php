<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Webkul\Faker\Helpers\Category as CategoryFaker;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

it('returns validation error when csv file is missing', function () {
    $this->postJson(route('dev.category-images-from-csv'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['csv']);
});

it('counts not_found and skipped_no_url from csv', function () {
    Storage::fake('public');

    $csv = <<<'CSV'
"Название категории","URL изображения"
"Missing Category","https://example.com/a.png"
"No Url Category",
CSV;

    $file = UploadedFile::fake()->createWithContent('categories.csv', $csv);

    Http::fake([
        'example.com/*' => Http::response(
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='),
            200,
            ['Content-Type' => 'image/png']
        ),
    ]);

    $this->postJson(route('dev.category-images-from-csv'), ['csv' => $file])
        ->assertOk()
        ->assertJsonPath('summary.not_found', 1)
        ->assertJsonPath('summary.skipped_no_url', 1)
        ->assertJsonPath('summary.updated', 0);
});

it('imports remote image into category logo by exact name match', function () {
    Storage::fake('public');

    $category = (new CategoryFaker)->factory()->create();

    $uniqueName = 'CSV Import Category '.$category->id;

    foreach ($category->translations as $translation) {
        $translation->name = $uniqueName;
        $translation->save();
    }

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Http::fake([
        'images.test/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
    ]);

    $csv = '"Название категории","URL изображения"'."\n";
    $csv .= '"'.$uniqueName.'","https://images.test/logo.png"'."\n";

    $file = UploadedFile::fake()->createWithContent('categories.csv', $csv);

    $this->postJson(route('dev.category-images-from-csv'), ['csv' => $file])
        ->assertOk()
        ->assertJsonPath('summary.updated', 1)
        ->assertJsonPath('summary.not_found', 0);

    $category->refresh();

    expect($category->logo_path)->not->toBeNull()
        ->and(str_starts_with((string) $category->logo_path, 'category/'.$category->id.'/'))->toBeTrue();

    Storage::disk('public')->assertExists((string) $category->logo_path);
});

it('imports into banner when target is banner', function () {
    Storage::fake('public');

    $category = (new CategoryFaker)->factory()->create();

    $uniqueName = 'CSV Banner Category '.$category->id;

    foreach ($category->translations as $translation) {
        $translation->name = $uniqueName;
        $translation->save();
    }

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Http::fake([
        'images.test/*' => Http::response($png, 200, ['Content-Type' => 'image/png']),
    ]);

    $csv = '"Название категории","URL изображения"'."\n";
    $csv .= '"'.$uniqueName.'","https://images.test/banner.png"'."\n";

    $file = UploadedFile::fake()->createWithContent('categories.csv', $csv);

    $this->postJson(route('dev.category-images-from-csv'), [
        'csv' => $file,
        'target' => 'banner',
    ])
        ->assertOk()
        ->assertJsonPath('target', 'banner')
        ->assertJsonPath('summary.updated', 1);

    $category->refresh();

    expect($category->banner_path)->not->toBeNull();

    Storage::disk('public')->assertExists((string) $category->banner_path);
});

it('imports image url with cyrillic characters in the path', function () {
    Storage::fake('public');

    $category = (new CategoryFaker)->factory()->create();

    $uniqueName = 'Cyrillic Path Category '.$category->id;

    foreach ($category->translations as $translation) {
        $translation->name = $uniqueName;
        $translation->save();
    }

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Http::fake(function (Request $request) use ($png) {
        expect($request->url())->toContain('.jpg');
        expect($request->url())->toMatch('/%[0-9A-F]{2}/');

        return Http::response($png, 200, ['Content-Type' => 'image/png']);
    });

    $cyrillicFile = 'Пивной_напиток_Эсса_вкус_апельсина_и_вишни.jpg';

    $csv = '"Название категории","URL изображения"'."\n";
    $csv .= '"'.$uniqueName.'","https://images.test/wp-content/uploads/2024/03/'.$cyrillicFile.'"'."\n";

    $file = UploadedFile::fake()->createWithContent('categories.csv', $csv);

    $this->postJson(route('dev.category-images-from-csv'), ['csv' => $file])
        ->assertOk()
        ->assertJsonPath('summary.updated', 1)
        ->assertJsonPath('summary.download_or_image_errors', 0);

    $category->refresh();

    expect($category->logo_path)->not->toBeNull();
});

it('treats html entity ampersands in urls correctly', function () {
    Storage::fake('public');

    $category = (new CategoryFaker)->factory()->create();

    $uniqueName = 'Amp Category '.$category->id;

    foreach ($category->translations as $translation) {
        $translation->name = $uniqueName;
        $translation->save();
    }

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Http::fake(function (Request $request) use ($png) {
        expect($request->url())->toContain('nocache=1');

        return Http::response($png, 200, ['Content-Type' => 'image/png']);
    });

    $csv = '"Название категории","URL изображения"'."\n";
    $csv .= '"'.$uniqueName.'","https://images.test/x.png?src=https://x&amp;nocache=1"'."\n";

    $file = UploadedFile::fake()->createWithContent('categories.csv', $csv);

    $this->postJson(route('dev.category-images-from-csv'), ['csv' => $file])
        ->assertOk()
        ->assertJsonPath('summary.updated', 1);
});

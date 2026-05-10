<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Error as DataTransferError;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as ProductImporter;
use Webkul\DataTransfer\Models\Import;
use Webkul\DataTransfer\Models\ImportBatch as DataTransferImportBatch;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Models\Product;

beforeEach(function () {
    Http::preventStrayRequests();
});

afterEach(function () {
    Http::fake();
});

/**
 * @return array<string, mixed>
 */
function buildProductImportRowFromModel(Product $product, array $overrides = []): array
{
    $product->loadMissing('attribute_values.attribute');

    $familyCode = AttributeFamily::query()->find($product->attribute_family_id)?->code ?? 'default';

    $row = [
        'sku' => $product->sku,
        'type' => $product->type,
        'attribute_family_code' => $familyCode,
        'locale' => 'en',
        'channel' => core()->getDefaultChannelCode(),
        'categories' => '',
        'inventories' => '',
        'customer_group_prices' => '',
    ];

    foreach ($product->attribute_values as $attributeValue) {
        $attribute = $attributeValue->attribute;

        if (! $attribute || in_array($attribute->code, ['images', 'image_url', 'sku'], true)) {
            continue;
        }

        $columnName = $attribute->column_name;

        $row[$attribute->code] = $attributeValue->{$columnName};
    }

    return array_merge($row, $overrides);
}

function runProductImportBatch(array $batchRow): void
{
    $import = Import::create([
        'state' => ImportHelper::STATE_VALIDATED,
        'process_in_queue' => false,
        'type' => 'products',
        'action' => ImportHelper::ACTION_APPEND,
        'validation_strategy' => ImportHelper::VALIDATION_STRATEGY_SKIP_ERRORS,
        'allowed_errors' => 100,
        'field_separator' => ',',
        'file_path' => 'catalog-imports/placeholder-import.csv',
    ]);

    $importBatch = DataTransferImportBatch::create([
        'import_id' => $import->id,
        'state' => 'pending',
        'data' => [$batchRow],
    ]);

    $importBatch->setRelation('import', $import);

    $importer = app(ProductImporter::class)
        ->setImport($import)
        ->setErrorHelper(app(DataTransferError::class));

    $importer->importBatch($importBatch);
}

it('imports remote image_url for an existing simple product when it has no gallery images', function () {
    $pngBody = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    Http::fake([
        'https://example.com/*' => Http::response($pngBody, 200, ['Content-Type' => 'image/png']),
    ]);

    /** @var Product $product */
    $product = (new ProductFaker([]))->getSimpleProductFactory()->create();

    DB::table('product_images')->where('product_id', '=', $product->id)->delete();

    expect(DB::table('product_images')->where('product_id', '=', $product->id)->count())->toBe(0);

    $row = buildProductImportRowFromModel($product, [
        'image_url' => 'https://example.com/pixel.png',
    ]);

    runProductImportBatch($row);

    $after = DB::table('product_images')->where('product_id', '=', $product->id)->count();

    expect($after)->toBeGreaterThanOrEqual(1);
});

it('does not add images from image_url when the product already has gallery images', function () {
    $catalogImageHttpAttempts = 0;

    Http::fake(function (Request $request) use (&$catalogImageHttpAttempts) {
        if (str_contains($request->url(), 'example.org/catalog-skip-me')) {
            $catalogImageHttpAttempts++;
        }

        return Http::response('', 200, ['Content-Type' => 'text/plain']);
    });

    /** @var Product $product */
    $product = (new ProductFaker([]))->getSimpleProductFactory()->create();

    DB::table('product_images')->where('product_id', '=', $product->id)->delete();

    DB::table('product_images')->insert([
        'type' => 'images',
        'path' => 'product/'.$product->id.'/existing-placeholder.webp',
        'product_id' => $product->id,
        'position' => 1,
    ]);

    expect(DB::table('product_images')->where('product_id', '=', $product->id)->count())->toBe(1);

    $row = buildProductImportRowFromModel($product, [
        'image_url' => 'https://example.org/catalog-skip-me/photo.png',
    ]);

    runProductImportBatch($row);

    expect(DB::table('product_images')->where('product_id', '=', $product->id)->count())->toBe(1);

    expect($catalogImageHttpAttempts)->toBe(0);
});

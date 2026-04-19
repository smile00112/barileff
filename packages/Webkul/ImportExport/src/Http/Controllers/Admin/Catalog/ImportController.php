<?php

namespace Webkul\ImportExport\Http\Controllers\Admin\Catalog;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Repositories\ImportRepository;
use Webkul\ImportExport\Events\CatalogImportCompleted;
use Webkul\ImportExport\Http\Requests\Catalog\ImportUploadRequest;
use Webkul\ImportExport\Models\CatalogImportSession;
use Webkul\Inventory\Models\InventorySource;
use Webkul\Supplier\Models\Supplier;
use Webkul\User\Models\Admin;

class ImportController extends Controller
{
    /**
     * Map of delimiter slugs to actual characters.
     *
     * @var array<string, string>
     */
    protected array $delimiterMap = [
        'comma' => ',',
        'semicolon' => ';',
        'tab' => "\t",
        'pipe' => '|',
    ];

    public function __construct(
        protected ImportHelper $importHelper,
        protected ImportRepository $importRepository,
        protected AttributeRepository $attributeRepository,
        protected CategoryRepository $categoryRepository
    ) {}

    /**
     * Display a listing of import sessions.
     */
    public function index(): View
    {
        $sessions = CatalogImportSession::where('created_by', auth()->guard('admin')->id())
            ->latest()
            ->paginate(20);

        return view('import_export::admin.catalog.imports.index', compact('sessions'));
    }

    /**
     * Show the upload/create form.
     */
    public function create(): View
    {
        $locales = core()->getAllLocales();
        $parentCategoryOptions = $this->buildParentCategoryOptions();

        return view('import_export::admin.catalog.imports.upload', compact('locales', 'parentCategoryOptions'));
    }

    /**
     * Handle file upload and create import session.
     */
    public function store(ImportUploadRequest $request)
    {
        $delimiter = $this->delimiterMap[$request->delimiter] ?? ',';
        $file = $request->file('file');
        $origName = $file->getClientOriginalName();
        $safeName = uniqid('cat_import_').'_'.hash('sha256', $origName).'.csv';
        $storagePath = $file->storeAs('catalog-imports', $safeName, 'private');

        $fullPath = Storage::disk('private')->path($storagePath);
        $handle = fopen($fullPath, 'r');
        $headers = $handle ? (fgetcsv($handle, 4096, $delimiter) ?: []) : [];

        if ($handle) {
            fclose($handle);
        }

        $session = CatalogImportSession::create([
            'state' => CatalogImportSession::STATE_PENDING,
            'file_name' => $origName,
            'file_path' => $storagePath,
            'delimiter' => $delimiter,
            'locale' => $request->locale,
            'create_categories' => (bool) $request->input('create_categories', false),
            'parent_category_id' => (int) $request->input('parent_category_id', 1),
            'allow_insert' => (bool) $request->input('allow_insert', true),
            'allow_update' => (bool) $request->input('allow_update', true),
            'headers' => array_values($headers),
            'created_by' => auth()->guard('admin')->id(),
        ]);

        return redirect()->route('admin.catalog.imports.show', $session->id)
            ->with('success', trans('admin::app.catalog.imports.upload.success'));
    }

    /**
     * Show the mapping / progress page for an import session.
     */
    public function show(int $id): View
    {
        $session = CatalogImportSession::findOrFail($id);
        $bagistoFields = $this->getSinicaFields();
        $inventorySources = InventorySource::where('status', 1)->orderBy('name')->get(['id', 'name', 'code']);
        $previewRows = $this->readPreviewRows($session);

        return view('import_export::admin.catalog.imports.show', compact('session', 'bagistoFields', 'inventorySources', 'previewRows'));
    }

    /**
     * Start the import process (validates mapping, reformats CSV, queues jobs).
     *
     * Accepts `column_mapping` in request body to save mapping before starting.
     */
    public function start(Request $request, int $id): JsonResponse
    {
        $session = CatalogImportSession::findOrFail($id);

        if ($request->has('column_mapping')) {
            $request->validate([
                'column_mapping' => ['required', 'array'],
                'inventory_source_id' => ['nullable', 'integer', 'exists:inventory_sources,id'],
            ]);

            $session->update([
                'column_mapping' => $request->column_mapping,
                'inventory_source_id' => $request->input('inventory_source_id'),
                'state' => CatalogImportSession::STATE_READY,
            ]);

            $session->refresh();
        }

        if ($session->state !== CatalogImportSession::STATE_READY) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.imports.errors.map-first'),
            ], 422);
        }

        if ($session->create_categories) {
            $this->createMissingCategories($session);
        }

        $remappedPath = $this->createRemappedCsv($session);

        if (! $remappedPath) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.imports.errors.csv-reformat-failed'),
            ], 500);
        }

        $dtImport = $this->importRepository->create([
            'type' => 'products',
            'action' => 'append',
            'process_in_queue' => true,
            'validation_strategy' => 'skip-errors',
            'allowed_errors' => 100,
            'field_separator' => ',',
            'file_path' => $remappedPath,
            'summary' => [
                'category_chain_anchor_id' => (int) ($session->parent_category_id ?? 1),
            ],
        ]);

        $isValid = $this->importHelper->setImport($dtImport)->validate();

        if (! $isValid) {
            Storage::disk('private')->delete($remappedPath);
            $this->importRepository->delete($dtImport->id);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.imports.errors.validation-failed'),
                'errors' => $this->importHelper->getFormattedErrors(),
            ], 422);
        }

        $this->importHelper->started();
        $this->importHelper->start();

        $session->update([
            'import_ref_id' => $dtImport->id,
            'state' => CatalogImportSession::STATE_PROCESSING,
            'started_at' => now(),
        ]);

        activity('catalog_import')
            ->causedBy(auth()->guard('admin')->user())
            ->performedOn($session)
            ->withProperties([
                'file' => $session->file_name,
                'import_ref_id' => $dtImport->id,
            ])
            ->log('started');

        return new JsonResponse([
            'success' => true,
            'state' => CatalogImportSession::STATE_PROCESSING,
        ]);
    }

    /**
     * Return JSON progress status for the given session.
     */
    public function status(int $id): JsonResponse
    {
        $session = CatalogImportSession::findOrFail($id);

        if ($session->state !== CatalogImportSession::STATE_PROCESSING || ! $session->import_ref_id) {
            return new JsonResponse([
                'state' => $session->state,
                'stats' => [
                    'progress' => $session->state === CatalogImportSession::STATE_COMPLETED ? 100 : 0,
                    'batches' => ['total' => 0, 'completed' => 0, 'remaining' => 0],
                    'summary' => ['created' => 0, 'updated' => 0, 'deleted' => 0],
                ],
            ]);
        }

        $dtImport = $this->importRepository->find($session->import_ref_id);

        if (! $dtImport) {
            return new JsonResponse(['state' => $session->state, 'stats' => ['progress' => 0]]);
        }

        $this->importHelper->setImport($dtImport);

        $statsState = match ($dtImport->state) {
            ImportHelper::STATE_INDEXED,
            ImportHelper::STATE_COMPLETED => ImportHelper::STATE_INDEXED,
            ImportHelper::STATE_LINKED,
            ImportHelper::STATE_INDEXING,
            ImportHelper::STATE_LINKING => ImportHelper::STATE_LINKED,
            default => ImportHelper::STATE_PROCESSED,
        };

        $stats = $this->importHelper->stats($statsState);

        if ($dtImport->state === ImportHelper::STATE_COMPLETED) {
            $stats['progress'] = 100;
            $stats['summary'] = array_merge([
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
            ], $dtImport->summary ?? $stats['summary'] ?? []);

            $session->update([
                'state' => CatalogImportSession::STATE_COMPLETED,
                'completed_at' => now(),
            ]);

            $admin = Admin::find($session->created_by);

            activity('catalog_import')
                ->causedBy($admin)
                ->performedOn($session->fresh())
                ->withProperties($dtImport->summary ?? [])
                ->log('completed');

            Event::dispatch(new CatalogImportCompleted($session->fresh()));
        }

        return new JsonResponse([
            'state' => $session->fresh()->state,
            'stats' => $stats,
            'import_state' => $dtImport->state,
        ]);
    }

    /**
     * Build grouped Sinica fields available for column mapping.
     *
     * @return array<int, array{label: string, children: array<int, array{code: string, name: string}>}>
     */
    protected function getSinicaFields(): array
    {
        $fieldLabels = [
            'sku' => trans('admin::app.catalog.imports.fields.sku'),
            'type' => trans('admin::app.catalog.imports.fields.type'),
            'attribute_family_code' => trans('admin::app.catalog.imports.fields.attribute-family'),
            'locale' => trans('admin::app.catalog.imports.fields.locale'),
            'qty' => trans('admin::app.catalog.imports.fields.qty'),
            'categories' => trans('admin::app.catalog.imports.fields.categories'),
            'images' => trans('admin::app.catalog.imports.fields.images'),
            'image_url' => trans('admin::app.catalog.imports.fields.image-url'),
            'inventories' => trans('admin::app.catalog.imports.fields.inventories'),
            'parent_sku' => trans('admin::app.catalog.imports.fields.parent-sku'),
            'related_skus' => trans('admin::app.catalog.imports.fields.related-skus'),
            'cross_sell_skus' => trans('admin::app.catalog.imports.fields.cross-sell-skus'),
            'up_sell_skus' => trans('admin::app.catalog.imports.fields.up-sell-skus'),
            'supplier' => trans('admin::app.catalog.imports.fields.supplier'),
        ];

        $attributeFields = $this->attributeRepository
            ->all(['code', 'admin_name'])
            ->sortBy('admin_name')
            ->map(fn ($attribute) => [
                'code' => $attribute->code,
                'name' => $attribute->admin_name ?: $attribute->code,
            ])
            ->values()
            ->all();

        $attributeFieldsByCode = collect($attributeFields)->keyBy('code');

        $groupCodes = [
            'product-data' => ['sku', 'type', 'attribute_family_code', 'locale', 'supplier'],
            'prices-and-inventory' => ['qty', 'price', 'cost', 'special_price', 'special_price_from', 'special_price_to', 'inventories'],
            'content-and-media' => ['name', 'short_description', 'description', 'url_key', 'images', 'image_url', 'weight'],
            'categories-and-relations' => ['categories', 'parent_sku', 'related_skus', 'cross_sell_skus', 'up_sell_skus'],
        ];

        $groupedFields = [
            [
                'label' => trans('admin::app.catalog.imports.mapping.group-select'),
                'children' => [[
                    'code' => '__skip__',
                    'name' => '— '.trans('admin::app.catalog.imports.mapping.skip').' —',
                ]],
            ],
        ];

        $usedCodes = ['__skip__' => true];

        foreach ($groupCodes as $groupKey => $codes) {
            $children = [];

            foreach ($codes as $code) {
                if (isset($usedCodes[$code])) {
                    continue;
                }

                $attribute = $attributeFieldsByCode->get($code);

                if (! $attribute && ! isset($fieldLabels[$code])) {
                    continue;
                }

                $children[] = [
                    'code' => $code,
                    'name' => $fieldLabels[$code] ?? $attribute['name'],
                ];

                $usedCodes[$code] = true;
            }

            if (! empty($children)) {
                $groupedFields[] = [
                    'label' => trans('admin::app.catalog.imports.mapping.group-'.$groupKey),
                    'children' => $children,
                ];
            }
        }

        $remainingAttributes = array_values(array_filter(
            $attributeFields,
            fn (array $attribute): bool => ! isset($usedCodes[$attribute['code']])
        ));

        if (! empty($remainingAttributes)) {
            $groupedFields[] = [
                'label' => trans('admin::app.catalog.imports.mapping.group-attributes'),
                'children' => $remainingAttributes,
            ];
        }

        return $groupedFields;
    }

    /**
     * Scan the import CSV for supplier names, create missing ones, and return the
     * resolved name→id map plus per-supplier log events.
     *
     * @return array{map: array<string, int>, events: array<int, array{action: string, id: int, name: string}>}
     */
    protected function resolveImportSuppliers(CatalogImportSession $session): array
    {
        $mapping = $session->column_mapping ?? [];

        if (! in_array('supplier', $mapping, true)) {
            return ['map' => [], 'events' => []];
        }

        $originalPath = Storage::disk('private')->path($session->file_path);
        $delimiter = $session->delimiter;

        $handle = fopen($originalPath, 'r');

        if (! $handle) {
            return ['map' => [], 'events' => []];
        }

        $originalHeaders = fgetcsv($handle, 4096, $delimiter) ?: [];
        $supplierColIndex = null;

        foreach ($originalHeaders as $idx => $header) {
            if (($mapping[$header] ?? null) === 'supplier') {
                $supplierColIndex = $idx;
                break;
            }
        }

        if ($supplierColIndex === null) {
            fclose($handle);

            return ['map' => [], 'events' => []];
        }

        $uniqueNames = [];

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
            $name = trim($row[$supplierColIndex] ?? '');

            if ($name !== '') {
                $uniqueNames[$name] = true;
            }
        }

        fclose($handle);

        if (empty($uniqueNames)) {
            return ['map' => [], 'events' => []];
        }

        $existingMap = Supplier::all(['id', 'name'])
            ->mapWithKeys(fn (Supplier $s) => [mb_strtolower($s->name) => $s->id])
            ->all();

        $supplierNameToId = $existingMap;
        $events = [];

        foreach (array_keys($uniqueNames) as $name) {
            $lower = mb_strtolower($name);

            if (isset($existingMap[$lower])) {
                $events[] = ['action' => 'found', 'id' => $existingMap[$lower], 'name' => $name];
            } else {
                $new = Supplier::create(['name' => $name, 'status' => true]);
                $supplierNameToId[$lower] = $new->id;
                $events[] = ['action' => 'created', 'id' => $new->id, 'name' => $name];
            }
        }

        return ['map' => $supplierNameToId, 'events' => $events];
    }

    /**
     * Reformat the uploaded CSV using the column mapping and save as a new file.
     */
    protected function createRemappedCsv(CatalogImportSession $session, array $supplierNameToId = []): ?string
    {
        $mapping = $session->column_mapping ?? [];
        $originalPath = Storage::disk('private')->path($session->file_path);
        $delimiter = $session->delimiter;
        $locale = $session->locale;

        $handle = fopen($originalPath, 'r');

        if (! $handle) {
            return null;
        }

        $originalHeaders = fgetcsv($handle, 4096, $delimiter) ?: [];

        /** @var array<string, int> $columnMap field_code => original_column_index */
        $columnMap = [];

        foreach ($originalHeaders as $idx => $header) {
            $field = $mapping[$header] ?? '__skip__';

            if ($field && $field !== '__skip__') {
                $columnMap[$field] = $idx;
            }
        }

        if (empty($columnMap) || ! isset($columnMap['sku'])) {
            fclose($handle);

            return null;
        }

        // If the user selected an inventory source and mapped either:
        //   a) a plain `qty` column → rename it to `inventories` and tag with source code, or
        //   b) an `inventories` column directly (plain number) → tag with source code per-row.
        // Both cases transform the raw value to the `{source_code}={qty}` format that
        // the DataTransfer Importer expects.
        $inventorySourceCode = null;

        if ($session->inventory_source_id) {
            $inventorySource = InventorySource::find($session->inventory_source_id);

            if ($inventorySource) {
                if (isset($columnMap['qty']) && ! isset($columnMap['inventories'])) {
                    // qty column mapped → rename to inventories and apply source code prefix
                    $inventorySourceCode = $inventorySource->code;
                    $qtyColumnIndex = $columnMap['qty'];
                    unset($columnMap['qty']);
                    $columnMap = array_merge(['inventories' => $qtyColumnIndex], $columnMap);
                } elseif (isset($columnMap['inventories'])) {
                    // inventories column mapped directly with a warehouse selected →
                    // treat raw values as plain quantities and apply source code prefix per-row
                    $inventorySourceCode = $inventorySource->code;
                }
            }
        }

        // If the user mapped a `supplier` column, rename it to `supplier_id` so the
        // DataTransfer importer recognises it as a base-table column.  Supplier names
        // are resolved (and missing suppliers auto-created) before the main row loop.
        $supplierColumnIndex = null;

        if (isset($columnMap['supplier'])) {
            $supplierColumnIndex = $columnMap['supplier'];
            unset($columnMap['supplier']);
            $columnMap = array_merge(['supplier_id' => $supplierColumnIndex], $columnMap);
        }

        $addLocaleColumn = ! isset($columnMap['locale']);
        $addTypeColumn = ! isset($columnMap['type']);
        $addFamilyColumn = ! isset($columnMap['attribute_family_code']);

        // When new_products_active=false and status is not mapped, set status per-row
        // (0 for new products, 1 for existing) instead of using a static column default.
        $handleStatusPerRow = ! isset($columnMap['status']) && ! ($session->new_products_active ?? true);

        // When new_products_in_stock=false, zero out inventory for new products per-row.
        $handleInventoryPerRow = ! ($session->new_products_in_stock ?? true) && isset($columnMap['inventories']);

        // Required by the DataTransfer product importer — static defaults for boolean/numeric fields.
        $staticRequiredDefaults = [];

        foreach (['status' => '1', 'visible_individually' => '1', 'guest_checkout' => '1', 'weight' => '0'] as $field => $default) {
            if (isset($columnMap[$field])) {
                continue;
            }

            if ($field === 'status' && $handleStatusPerRow) {
                continue; // handled per-row below
            }

            $staticRequiredDefaults[$field] = $default;
        }

        // url_key: auto-generated per row from the mapped SKU value.
        $autoUrlKey = ! isset($columnMap['url_key']);

        // short_description / description: copied from mapped `name` column when not mapped.
        $autoShortDescription = ! isset($columnMap['short_description']);
        $autoDescription = ! isset($columnMap['description']);

        $skuColumnIndex = $columnMap['sku'] ?? null;
        $nameColumnIndex = $columnMap['name'] ?? null;

        $newHeaders = array_keys($columnMap);

        if ($addLocaleColumn) {
            $newHeaders[] = 'locale';
        }

        if ($addTypeColumn) {
            $newHeaders[] = 'type';
        }

        if ($addFamilyColumn) {
            $newHeaders[] = 'attribute_family_code';
        }

        foreach (array_keys($staticRequiredDefaults) as $field) {
            $newHeaders[] = $field;
        }

        if ($autoUrlKey) {
            $newHeaders[] = 'url_key';
        }

        if ($autoShortDescription) {
            $newHeaders[] = 'short_description';
        }

        if ($autoDescription) {
            $newHeaders[] = 'description';
        }

        if ($handleStatusPerRow) {
            $newHeaders[] = 'status';
        }

        $remappedName = 'catalog-imports/remapped_'.basename($session->file_path);
        $remappedFullPath = Storage::disk('private')->path($remappedName);
        $dir = dirname($remappedFullPath);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writeHandle = fopen($remappedFullPath, 'w');

        if (! $writeHandle) {
            fclose($handle);

            return null;
        }

        fputcsv($writeHandle, $newHeaders);

        // Pre-load existing SKUs for insert/update filtering and per-row flag handling.
        $existingSkus = null;

        if (! $session->allow_insert || ! $session->allow_update || $handleStatusPerRow || $handleInventoryPerRow) {
            $existingSkus = DB::table('products')->pluck('sku', 'sku')->all();
        }

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
            $skuValue = $skuColumnIndex !== null ? trim($row[$skuColumnIndex] ?? '') : '';

            if ($existingSkus !== null && $skuValue !== '') {
                $skuExists = isset($existingSkus[$skuValue]);

                if (! $session->allow_insert && ! $skuExists) {
                    continue;
                }

                if (! $session->allow_update && $skuExists) {
                    continue;
                }
            }

            $newRow = [];

            foreach (array_keys($columnMap) as $field) {
                $rawValue = $row[$columnMap[$field]] ?? '';

                // Wrap the qty value in `source_code=qty` format for the inventories column.
                if ($field === 'inventories' && $inventorySourceCode !== null) {
                    $rawValue = $rawValue !== '' ? $inventorySourceCode.'='.$rawValue : '';
                }

                // Zero out inventory for new products when new_products_in_stock=false.
                if ($field === 'inventories' && $handleInventoryPerRow) {
                    $skuIsNew = $existingSkus !== null && ! isset($existingSkus[$skuValue]);

                    if ($skuIsNew) {
                        $rawValue = '';
                    }
                }

                // Resolve supplier name → supplier_id (empty string when name not found/blank).
                if ($field === 'supplier_id' && $supplierColumnIndex !== null) {
                    $supplierName = trim($rawValue);
                    $rawValue = $supplierName !== ''
                        ? (string) ($supplierNameToId[mb_strtolower($supplierName)] ?? '')
                        : '';
                }

                $newRow[] = $rawValue;
            }

            if ($addLocaleColumn) {
                $newRow[] = $locale;
            }

            if ($addTypeColumn) {
                $newRow[] = 'simple';
            }

            if ($addFamilyColumn) {
                $newRow[] = 'default';
            }

            foreach ($staticRequiredDefaults as $default) {
                $newRow[] = $default;
            }

            if ($autoUrlKey) {
                $rawSku = $skuColumnIndex !== null ? ($row[$skuColumnIndex] ?? '') : '';
                $newRow[] = $this->toUrlKey($rawSku ?: uniqid('p'));
            }

            if ($autoShortDescription) {
                $newRow[] = $nameColumnIndex !== null ? ($row[$nameColumnIndex] ?? '-') : '-';
            }

            if ($autoDescription) {
                $newRow[] = $nameColumnIndex !== null ? ($row[$nameColumnIndex] ?? '-') : '-';
            }

            // Per-row status: 0 for new products when new_products_active=false, 1 for existing.
            if ($handleStatusPerRow) {
                $newRow[] = ($existingSkus !== null && isset($existingSkus[$skuValue])) ? '1' : '0';
            }

            fputcsv($writeHandle, $newRow);
        }

        fclose($handle);
        fclose($writeHandle);

        return $remappedName;
    }

    /**
     * Create missing categories referenced in the import file.
     *
     * Returns an array of ['id' => int, 'name' => string] for each category that
     * was newly created (pre-existing ones are not included).
     *
     * @return array<int, array{id: int, name: string}>
     */
    protected function createMissingCategories(CatalogImportSession $session): array
    {
        $mapping = $session->column_mapping ?? [];
        $originalPath = Storage::disk('private')->path($session->file_path);
        $delimiter = $session->delimiter;
        $anchorId = (int) ($session->parent_category_id ?? 1);
        $locale = $session->locale;

        // Find which original CSV header is mapped to `categories`.
        $categoriesHeader = null;

        foreach ($mapping as $header => $field) {
            if ($field === 'categories') {
                $categoriesHeader = $header;

                break;
            }
        }

        if ($categoriesHeader === null) {
            return [];
        }

        $handle = fopen($originalPath, 'r');

        if (! $handle) {
            return [];
        }

        $originalHeaders = fgetcsv($handle, 4096, $delimiter) ?: [];
        $categoriesColIndex = array_search($categoriesHeader, $originalHeaders, true);

        if ($categoriesColIndex === false) {
            fclose($handle);

            return [];
        }

        // Snapshot of existing category IDs before we create anything.
        $preExistingIds = DB::table('categories')->pluck('id', 'id')->all();
        $allReturnedIds = [];
        $seenChains = [];

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
            $cell = trim($row[$categoriesColIndex] ?? '');

            if ($cell === '') {
                continue;
            }

            $segments = array_values(array_filter(array_map('trim', explode(',', $cell))));

            if ($segments === []) {
                continue;
            }

            $chainKey = implode("\0", $segments);

            if (isset($seenChains[$chainKey])) {
                continue;
            }

            $seenChains[$chainKey] = true;

            $returnedIds = $this->categoryRepository->ensureCategoryChainUnderParent($anchorId, $segments, $locale);

            foreach ($returnedIds as $catId) {
                $allReturnedIds[$catId] = true;
            }
        }

        fclose($handle);

        // New categories = returned IDs that were not in the pre-existing snapshot.
        $newCategoryIds = array_keys(array_diff_key($allReturnedIds, $preExistingIds));

        if (empty($newCategoryIds)) {
            return [];
        }

        // Batch-fetch their translated names (preferred locale first, any locale as fallback).
        $categoryNames = DB::table('category_translations')
            ->whereIn('category_id', $newCategoryIds)
            ->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END', [$locale])
            ->get(['category_id', 'name'])
            ->unique('category_id')
            ->pluck('name', 'category_id')
            ->all();

        return array_map(
            fn (int $id): array => [
                'id' => $id,
                'name' => $categoryNames[$id] ?? "Category #{$id}",
            ],
            $newCategoryIds
        );
    }

    /**
     * Build a flat option list of categories for the parent select.
     *
     * Returns the root category node plus first-level children so the admin
     * can choose where new categories will be created.
     *
     * @return array<int, array{id: int, label: string}>
     */
    protected function buildParentCategoryOptions(): array
    {
        $localeCode = app()->getLocale();

        $rows = DB::table('categories as c')
            ->leftJoin('category_translations as ct', function ($join) use ($localeCode) {
                $join->on('ct.category_id', '=', 'c.id')
                    ->where('ct.locale', $localeCode);
            })
            ->select('c.id', 'c.parent_id', DB::raw('COALESCE(ct.name, CAST(c.id AS CHAR)) as name'))
            ->where('c.status', 1)
            ->orderBy('c.parent_id')
            ->orderBy('c.position')
            ->orderBy('c.id')
            ->get();

        $options = [];
        $rootIds = [];

        // Root node (parent_id IS NULL)
        foreach ($rows as $row) {
            if ($row->parent_id === null) {
                $options[] = ['id' => (int) $row->id, 'label' => $row->name];
                $rootIds[] = (int) $row->id;
            }
        }

        // First-level children
        foreach ($rows as $row) {
            if ($row->parent_id !== null && in_array((int) $row->parent_id, $rootIds, true)) {
                $options[] = ['id' => (int) $row->id, 'label' => '— '.$row->name];
            }
        }

        return $options;
    }

    /**
     * Convert a raw string to a url_key-compatible slug.
     *
     * Preserves Unicode letters/numbers (as required by the Slug validation rule)
     * and joins word groups with a single hyphen.
     */
    protected function toUrlKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = (string) preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $value);

        return trim($value, '-') ?: 'product';
    }

    /**
     * Read the first few data rows from the session CSV for preview.
     *
     * Returns an array keyed by header name, where each value is an array
     * of up to PREVIEW_ROW_COUNT non-empty sample values from that column.
     *
     * @return array<string, list<string>>
     */
    protected function readPreviewRows(CatalogImportSession $session, int $maxRows = 5): array
    {
        $headers = $session->headers ?? [];

        if (empty($headers)) {
            return [];
        }

        $originalPath = Storage::disk('private')->path($session->file_path);
        $handle = @fopen($originalPath, 'r');

        if (! $handle) {
            return [];
        }

        fgetcsv($handle, 4096, $session->delimiter); // skip header row

        $preview = array_fill_keys($headers, []);
        $rowsRead = 0;

        while ($rowsRead < $maxRows && ($row = fgetcsv($handle, 4096, $session->delimiter)) !== false) {
            foreach ($headers as $idx => $header) {
                $value = trim($row[$idx] ?? '');

                if ($value !== '') {
                    $preview[$header][] = $value;
                }
            }

            $rowsRead++;
        }

        fclose($handle);

        return $preview;
    }

    /**
     * Remove a catalog import session owned by the current admin.
     */
    public function destroy(int $id): JsonResponse
    {
        if (! bouncer()->hasPermission('catalog.imports')) {
            abort(403);
        }

        $session = CatalogImportSession::query()
            ->where('created_by', auth()->guard('admin')->id())
            ->whereKey($id)
            ->firstOrFail();

        if ($session->state === CatalogImportSession::STATE_PROCESSING) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.imports.index.delete-processing-not-allowed'),
            ], 422);
        }

        try {
            $this->deleteCatalogImportSession($session);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.imports.index.delete-failed'),
            ], 500);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.imports.index.delete-success'),
        ]);
    }

    /**
     * Mass-delete catalog import sessions owned by the current admin.
     */
    public function massDestroy(MassDestroyRequest $request): JsonResponse
    {
        if (! bouncer()->hasPermission('catalog.imports')) {
            abort(403);
        }

        foreach ($request->input('indices', []) as $sessionId) {
            $session = CatalogImportSession::query()
                ->where('created_by', auth()->guard('admin')->id())
                ->whereKey($sessionId)
                ->first();

            if (! $session) {
                continue;
            }

            if ($session->state === CatalogImportSession::STATE_PROCESSING) {
                continue;
            }

            try {
                $this->deleteCatalogImportSession($session);
            } catch (\Exception $e) {
                report($e);

                return new JsonResponse([
                    'message' => trans('admin::app.catalog.imports.index.delete-failed'),
                ], 500);
            }
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.imports.index.mass-delete-success'),
        ]);
    }

    /**
     * Delete storage files, linked data-transfer import, and the session row.
     */
    protected function deleteCatalogImportSession(CatalogImportSession $session): void
    {
        if ($session->import_ref_id) {
            $import = $this->importRepository->find($session->import_ref_id);

            if ($import) {
                Storage::disk('private')->delete($import->file_path);
                Storage::disk('private')->delete($import->error_file_path ?? '');

                $this->importRepository->delete($import->id);
            }
        }

        Storage::disk('private')->delete($session->file_path);

        $remappedPath = 'catalog-imports/remapped_'.basename($session->file_path);

        if (Storage::disk('private')->exists($remappedPath)) {
            Storage::disk('private')->delete($remappedPath);
        }

        $session->delete();
    }
}

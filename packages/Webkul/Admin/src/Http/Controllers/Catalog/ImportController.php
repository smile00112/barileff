<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Events\CatalogImportCompleted;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\Catalog\ImportUploadRequest;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Models\CatalogImportSession;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Repositories\ImportRepository;
use Webkul\Inventory\Models\InventorySource;
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
        protected AttributeRepository $attributeRepository
    ) {}

    /**
     * Display a listing of import sessions.
     */
    public function index(): View
    {
        $sessions = CatalogImportSession::where('created_by', auth()->guard('admin')->id())
            ->latest()
            ->paginate(20);

        return view('admin::catalog.imports.index', compact('sessions'));
    }

    /**
     * Show the upload/create form.
     */
    public function create(): View
    {
        $locales = core()->getAllLocales();

        return view('admin::catalog.imports.upload', compact('locales'));
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

        return view('admin::catalog.imports.show', compact('session', 'bagistoFields', 'inventorySources'));
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

        $stats = $this->importHelper->stats(ImportHelper::STATE_PROCESSED);

        if ($dtImport->state === ImportHelper::STATE_COMPLETED) {
            $stats['progress'] = 100;

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
            'product-data' => ['sku', 'type', 'attribute_family_code', 'locale'],
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
     * Reformat the uploaded CSV using the column mapping and save as a new file.
     */
    protected function createRemappedCsv(CatalogImportSession $session): ?string
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

        // If the user mapped a plain `qty` column and selected an inventory source,
        // rewrite that column as `inventories` with the format `{source_code}={qty}`
        // that the DataTransfer Importer expects.  Skip this when there is already
        // an explicit `inventories` column in the mapping.
        $inventorySourceCode = null;

        if (isset($columnMap['qty']) && ! isset($columnMap['inventories']) && $session->inventory_source_id) {
            $inventorySource = InventorySource::find($session->inventory_source_id);

            if ($inventorySource) {
                $inventorySourceCode = $inventorySource->code;
                $qtyColumnIndex = $columnMap['qty'];
                unset($columnMap['qty']);
                $columnMap = array_merge(['inventories' => $qtyColumnIndex], $columnMap);
            }
        }

        $addLocaleColumn = ! isset($columnMap['locale']);
        $addTypeColumn = ! isset($columnMap['type']);
        $addFamilyColumn = ! isset($columnMap['attribute_family_code']);

        // Required by the DataTransfer product importer — static defaults for boolean/numeric fields.
        $staticRequiredDefaults = [];

        foreach (['status' => '1', 'visible_individually' => '1', 'guest_checkout' => '1', 'weight' => '0'] as $field => $default) {
            if (! isset($columnMap[$field])) {
                $staticRequiredDefaults[$field] = $default;
            }
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

        while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
            $newRow = [];

            foreach (array_keys($columnMap) as $field) {
                $rawValue = $row[$columnMap[$field]] ?? '';

                // Wrap the qty value in `source_code=qty` format for the inventories column.
                if ($field === 'inventories' && $inventorySourceCode !== null) {
                    $rawValue = $rawValue !== '' ? $inventorySourceCode.'='.$rawValue : '';
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

            fputcsv($writeHandle, $newRow);
        }

        fclose($handle);
        fclose($writeHandle);

        return $remappedName;
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

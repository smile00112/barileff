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
use Webkul\Admin\Models\CatalogImportSession;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\DataTransfer\Helpers\Import as ImportHelper;
use Webkul\DataTransfer\Repositories\ImportRepository;
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
        $bagistoFields = $this->getBagistoFields();

        return view('admin::catalog.imports.show', compact('session', 'bagistoFields'));
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
            $request->validate(['column_mapping' => ['required', 'array']]);

            $session->update([
                'column_mapping' => $request->column_mapping,
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
     * Build the ordered list of Bagisto fields available for column mapping.
     *
     * @return array<string, string>
     */
    protected function getBagistoFields(): array
    {
        $coreFields = [
            '__skip__' => '— '.trans('admin::app.catalog.imports.mapping.skip').' —',
            'sku' => trans('admin::app.catalog.imports.fields.sku'),
            'type' => trans('admin::app.catalog.imports.fields.type'),
            'attribute_family_code' => trans('admin::app.catalog.imports.fields.attribute-family'),
            'locale' => trans('admin::app.catalog.imports.fields.locale'),
            'qty' => trans('admin::app.catalog.imports.fields.qty'),
        ];

        $attributeFields = $this->attributeRepository
            ->all(['code', 'admin_name'])
            ->sortBy('admin_name')
            ->mapWithKeys(fn ($a) => [$a->code => ($a->admin_name ?: $a->code)])
            ->all();

        return array_merge($coreFields, $attributeFields);
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

        $addLocaleColumn = ! isset($columnMap['locale']);
        $newHeaders = array_keys($columnMap);

        if ($addLocaleColumn) {
            $newHeaders[] = 'locale';
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
                $newRow[] = $row[$columnMap[$field]] ?? '';
            }

            if ($addLocaleColumn) {
                $newRow[] = $locale;
            }

            fputcsv($writeHandle, $newRow);
        }

        fclose($handle);
        fclose($writeHandle);

        return $remappedName;
    }
}

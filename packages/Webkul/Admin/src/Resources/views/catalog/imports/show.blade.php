<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.imports.show.title', ['id' => $session->id])
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.catalog.imports.show.title', ['id' => $session->id])
        </p>

        <a
            href="{{ route('admin.catalog.imports.index') }}"
            class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
        >
            @lang('admin::app.catalog.imports.show.back-btn')
        </a>
    </div>

    <!-- Vue Import Wizard -->
    <v-catalog-import />

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-catalog-import-template"
        >
            <div class="mt-3.5 flex flex-col gap-5">

                <!-- Step stepper -->
                <div class="box-shadow flex items-center gap-3 rounded-sm bg-white p-4 dark:bg-gray-900">
                    <div
                        class="flex items-center gap-2 text-sm font-medium"
                        :class="['pending','ready'].includes(state) ? 'text-indigo-600' : 'text-gray-400 dark:text-gray-500'"
                    >
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                            :class="['pending','ready'].includes(state) ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700'"
                        >1</span>
                        @lang('admin::app.catalog.imports.show.step-mapping')
                    </div>

                    <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>

                    <div
                        class="flex items-center gap-2 text-sm font-medium"
                        :class="state === 'processing' ? 'text-indigo-600' : 'text-gray-400 dark:text-gray-500'"
                    >
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                            :class="state === 'processing' ? 'bg-indigo-600 text-white' : 'bg-gray-200 dark:bg-gray-700'"
                        >2</span>
                        @lang('admin::app.catalog.imports.show.step-import')
                    </div>

                    <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>

                    <div
                        class="flex items-center gap-2 text-sm font-medium"
                        :class="['completed','failed'].includes(state) ? (state === 'completed' ? 'text-green-600' : 'text-red-600') : 'text-gray-400 dark:text-gray-500'"
                    >
                        <span
                            class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold"
                            :class="state === 'completed' ? 'bg-green-600 text-white' : state === 'failed' ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-gray-700'"
                        >3</span>
                        @lang('admin::app.catalog.imports.show.step-result')
                    </div>
                </div>

                <!-- Step 1: Column Mapping -->
                <div
                    v-if="['pending','ready'].includes(state)"
                    class="box-shadow rounded-sm bg-white p-6 dark:bg-gray-900"
                >
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.imports.show.mapping-title')
                    </h3>

                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        @lang('admin::app.catalog.imports.show.mapping-hint')
                    </p>

                    <!-- Validation notice – SKU required -->
                    <div
                        v-if="!hasMappedSku"
                        class="mb-4 flex items-center gap-2 rounded-sm border border-orange-200 bg-orange-50 p-3 text-sm text-orange-700 dark:border-gray-700 dark:bg-gray-800 dark:text-orange-400"
                    >
                        <i class="icon-information text-xl"></i>
                        @lang('admin::app.catalog.imports.show.sku-required')
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-auto text-sm text-gray-600 dark:text-gray-300">
                            <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium">@lang('admin::app.catalog.imports.show.csv-column')</th>
                                    <th class="px-4 py-2 text-left font-medium">@lang('admin::app.catalog.imports.show.bagisto-field')</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr
                                    v-for="header in headers"
                                    :key="header"
                                >
                                    <td class="px-4 py-2 font-mono text-xs">@{{ header }}</td>

                                    <td class="px-4 py-2">
                                        <select
                                            v-model="mapping[header]"
                                            class="block w-full rounded-sm border border-gray-300 px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 focus:border-indigo-500 focus:outline-none"
                                        >
                                            <optgroup
                                                v-for="group in bagistoFields"
                                                :key="group.label"
                                                :label="group.label"
                                            >
                                                <option
                                                    v-for="field in group.children"
                                                    :key="field.code"
                                                    :value="field.code"
                                                >@{{ field.name }}</option>
                                            </optgroup>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <button
                            class="primary-button"
                            :disabled="!hasMappedSku || isLoading"
                            @click="startImport"
                        >
                            <svg
                                v-if="isLoading"
                                class="-ml-1 mr-2 h-4 w-4 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            @lang('admin::app.catalog.imports.show.start-import')
                        </button>

                        <span v-if="error" class="text-sm text-red-500">@{{ error }}</span>
                    </div>
                </div>

                <!-- Step 2: Processing -->
                <div
                    v-if="state === 'processing'"
                    class="box-shadow rounded-sm bg-white p-6 dark:bg-gray-900"
                >
                    <h3 class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.catalog.imports.show.processing-title')
                    </h3>

                    <div class="mb-2 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                        <span>@lang('admin::app.catalog.imports.show.progress-label')</span>
                        <span>@{{ stats.progress }}%</span>
                    </div>

                    <div class="mb-4 h-4 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-4 rounded-full bg-indigo-600 transition-all duration-500"
                            :style="{ width: stats.progress + '%' }"
                        ></div>
                    </div>

                    <div class="flex flex-wrap gap-6 text-sm text-gray-600 dark:text-gray-300">
                        <span>
                            @lang('admin::app.catalog.imports.show.batches-label'):
                            <strong>@{{ stats.batches?.completed ?? 0 }} / @{{ stats.batches?.total ?? 0 }}</strong>
                        </span>
                    </div>

                    <p class="mt-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg class="h-4 w-4 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        @lang('admin::app.catalog.imports.show.polling-hint')
                    </p>
                </div>

                <!-- Step 3a: Completed -->
                <div
                    v-if="state === 'completed'"
                    class="box-shadow rounded-sm border border-green-200 bg-green-50 p-6 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <i class="icon-done h-fit rounded-full bg-green-200 text-2xl text-green-600"></i>
                        <h3 class="text-base font-semibold text-green-800 dark:text-green-400">
                            @lang('admin::app.catalog.imports.show.completed-title')
                        </h3>
                    </div>

                    <div class="flex flex-wrap gap-6 text-sm text-gray-700 dark:text-gray-300">
                        <span>
                            @lang('admin::app.catalog.imports.show.summary-created'):
                            <strong class="text-green-700 dark:text-green-400">@{{ stats.summary?.created ?? 0 }}</strong>
                        </span>
                        <span>
                            @lang('admin::app.catalog.imports.show.summary-updated'):
                            <strong class="text-blue-700 dark:text-blue-400">@{{ stats.summary?.updated ?? 0 }}</strong>
                        </span>
                        <span>
                            @lang('admin::app.catalog.imports.show.summary-deleted'):
                            <strong class="text-red-700 dark:text-red-400">@{{ stats.summary?.deleted ?? 0 }}</strong>
                        </span>
                    </div>

                    <div class="mt-4">
                        <a
                            href="{{ route('admin.catalog.imports.index') }}"
                            class="secondary-button"
                        >
                            @lang('admin::app.catalog.imports.show.back-to-list')
                        </a>
                    </div>
                </div>

                <!-- Step 3b: Failed -->
                <div
                    v-if="state === 'failed'"
                    class="box-shadow rounded-sm border border-red-200 bg-red-50 p-6 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="flex items-center gap-2">
                        <i class="icon-cross h-fit rounded-full bg-red-200 text-2xl text-red-600"></i>
                        <h3 class="text-base font-semibold text-red-800 dark:text-red-400">
                            @lang('admin::app.catalog.imports.show.failed-title')
                        </h3>
                    </div>

                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">@{{ error }}</p>

                    <div class="mt-4">
                        <a
                            href="{{ route('admin.catalog.imports.index') }}"
                            class="secondary-button"
                        >
                            @lang('admin::app.catalog.imports.show.back-to-list')
                        </a>
                    </div>
                </div>

            </div>
        </script>

        <script type="module">
            app.component('v-catalog-import', {
                template: '#v-catalog-import-template',

                data() {
                    return {
                        state: '{{ $session->state }}',
                        headers: @json($session->headers ?? []),
                        mapping: @json($session->column_mapping ?? []),
                        bagistoFields: @json($bagistoFields),
                        isLoading: false,
                        error: null,
                        stats: {
                            progress: 0,
                            batches: { total: 0, completed: 0, remaining: 0 },
                            summary: { created: 0, updated: 0, deleted: 0 },
                        },
                    };
                },

                computed: {
                    hasMappedSku() {
                        return Object.values(this.mapping).includes('sku');
                    },
                },

                watch: {
                    mapping: {
                        deep: true,

                        handler() {
                            if (! ['pending', 'ready'].includes(this.state) || ! this.headers.length) {
                                return;
                            }

                            this.persistMappingToStorage();
                        },
                    },
                },

                mounted() {
                    if (this.state === 'processing') {
                        this.pollStatus();
                    }

                    if (! ['pending', 'ready'].includes(this.state) || ! this.headers.length) {
                        return;
                    }

                    this.initializeMapping();
                },

                methods: {
                    initializeMapping() {
                        this.headers.forEach((header) => {
                            if (typeof this.mapping[header] === 'undefined') {
                                this.mapping[header] = '__skip__';
                            }
                        });

                        const hasServerMapping = this.headers.some((header) => {
                            const mappedCode = this.mapping[header];

                            return mappedCode && mappedCode !== '__skip__';
                        });

                        if (! hasServerMapping) {
                            this.restoreMappingFromStorage();
                            this.autoMapHeaders();
                        }

                        this.persistMappingToStorage();
                    },

                    getMappingStorageKey() {
                        const signature = this.headers
                            .map((header) => this.normalizeHeaderName(header))
                            .join('|');

                        return `catalog-import-mapping:v1:${signature}`;
                    },

                    normalizeHeaderName(header) {
                        return String(header ?? '')
                            .trim()
                            .toLowerCase()
                            .replace(/[\s-]+/g, '_')
                            .replace(/[^\w]/g, '_')
                            .replace(/_+/g, '_')
                            .replace(/^_+|_+$/g, '');
                    },

                    getAvailableFieldCodes() {
                        const codes = new Set(['__skip__']);

                        this.bagistoFields.forEach((group) => {
                            (group.children ?? []).forEach((field) => {
                                if (field.code) {
                                    codes.add(field.code);
                                }
                            });
                        });

                        return codes;
                    },

                    getAutoMappingAliases() {
                        return {
                            sku: 'sku',
                            product_sku: 'sku',
                            article: 'sku',

                            type: 'type',
                            product_type: 'type',

                            attribute_family_code: 'attribute_family_code',
                            attribute_family: 'attribute_family_code',

                            locale: 'locale',
                            language: 'locale',

                            qty: 'qty',
                            quantity: 'qty',
                            stock: 'qty',

                            price: 'price',
                            cost: 'cost',

                            special_price: 'special_price',
                            special_price_from: 'special_price_from',
                            special_price_to: 'special_price_to',

                            name: 'name',
                            title: 'name',
                            product_name: 'name',

                            description: 'description',
                            short_description: 'short_description',

                            url_key: 'url_key',
                            slug: 'url_key',

                            weight: 'weight',

                            images: 'images',
                            image: 'images',
                            image_name: 'images',

                            image_url: 'image_url',
                            image_link: 'image_url',
                            image_urls: 'image_url',

                            categories: 'categories',
                            category: 'categories',

                            inventories: 'inventories',
                            inventory: 'inventories',

                            parent_sku: 'parent_sku',
                            related_skus: 'related_skus',
                            cross_sell_skus: 'cross_sell_skus',
                            up_sell_skus: 'up_sell_skus',
                        };
                    },

                    restoreMappingFromStorage() {
                        try {
                            const raw = window.localStorage.getItem(this.getMappingStorageKey());

                            if (! raw) {
                                return false;
                            }

                            const stored = JSON.parse(raw);

                            if (! stored || typeof stored !== 'object') {
                                return false;
                            }

                            const availableCodes = this.getAvailableFieldCodes();
                            let restored = false;

                            this.headers.forEach((header) => {
                                const mappedCode = stored[header];

                                if (typeof mappedCode === 'string' && availableCodes.has(mappedCode)) {
                                    this.mapping[header] = mappedCode;
                                    restored = true;
                                }
                            });

                            return restored;
                        } catch (_) {
                            return false;
                        }
                    },

                    persistMappingToStorage() {
                        try {
                            const payload = {};

                            this.headers.forEach((header) => {
                                const mappedCode = this.mapping[header];
                                payload[header] = typeof mappedCode === 'string' ? mappedCode : '__skip__';
                            });

                            window.localStorage.setItem(this.getMappingStorageKey(), JSON.stringify(payload));
                        } catch (_) {
                            // Ignore storage quota or privacy mode errors.
                        }
                    },

                    autoMapHeaders() {
                        const availableCodes = this.getAvailableFieldCodes();
                        const aliases = this.getAutoMappingAliases();

                        this.headers.forEach((header) => {
                            const current = this.mapping[header] ?? '__skip__';

                            if (current && current !== '__skip__') {
                                return;
                            }

                            const normalized = this.normalizeHeaderName(header);

                            if (availableCodes.has(normalized)) {
                                this.mapping[header] = normalized;

                                return;
                            }

                            const alias = aliases[normalized];

                            if (alias && availableCodes.has(alias)) {
                                this.mapping[header] = alias;

                                return;
                            }

                            this.mapping[header] = '__skip__';
                        });
                    },

                    startImport() {
                        this.isLoading = true;
                        this.error = null;

                        this.$axios.post(
                            '{{ route('admin.catalog.imports.start', $session->id) }}',
                            { column_mapping: this.mapping }
                        )
                            .then(response => {
                                this.state = response.data.state;

                                if (this.state === 'processing') {
                                    this.pollStatus();
                                }
                            })
                            .catch(err => {
                                this.error = err.response?.data?.message ?? '@lang('admin::app.catalog.imports.errors.generic')';

                                const validationErrors = err.response?.data?.errors;

                                if (Array.isArray(validationErrors) && validationErrors.length) {
                                    this.error += ' ' + validationErrors.join('; ');
                                }
                            })
                            .finally(() => {
                                this.isLoading = false;
                            });
                    },

                    pollStatus() {
                        this.$axios.get('{{ route('admin.catalog.imports.status', $session->id) }}')
                            .then(response => {
                                this.state = response.data.state;

                                if (response.data.stats) {
                                    this.stats = response.data.stats;
                                }

                                if (this.state === 'processing') {
                                    setTimeout(() => this.pollStatus(), 2000);
                                }
                            })
                            .catch(() => {
                                setTimeout(() => this.pollStatus(), 5000);
                            });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>

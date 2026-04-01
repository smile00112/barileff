{{-- General Info --}}
<div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
        @lang('markup::app.admin.groups.form.general')
    </p>

    <x-admin::form.control-group>
        <x-admin::form.control-group.label class="required">
            @lang('markup::app.admin.groups.form.name')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="text"
            name="name"
            rules="required"
            :value="old('name', $group->name ?? '')"
            :label="trans('markup::app.admin.groups.form.name')"
            :placeholder="trans('markup::app.admin.groups.form.name')"
        />
        <x-admin::form.control-group.error control-name="name" />
    </x-admin::form.control-group>

    <x-admin::form.control-group>
        <x-admin::form.control-group.label>
            @lang('markup::app.admin.groups.form.description')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="textarea"
            name="description"
            :value="old('description', $group->description ?? '')"
            :placeholder="trans('markup::app.admin.groups.form.description')"
        />
        <x-admin::form.control-group.error control-name="description" />
    </x-admin::form.control-group>

    <div class="flex gap-4">
        <x-admin::form.control-group class="flex-1">
            <x-admin::form.control-group.label class="required">
                @lang('markup::app.admin.groups.form.type')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                name="type"
                rules="required"
                :value="old('type', $group->type ?? 'markup')"
                :label="trans('markup::app.admin.groups.form.type')"
            >
                <option value="markup">@lang('markup::app.admin.groups.form.type-markup')</option>
                <option value="discount">@lang('markup::app.admin.groups.form.type-discount')</option>
            </x-admin::form.control-group.control>
            <x-admin::form.control-group.error control-name="type" />
        </x-admin::form.control-group>

        <x-admin::form.control-group class="flex-1">
            <x-admin::form.control-group.label class="required">
                @lang('markup::app.admin.groups.form.schedule-type')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                name="schedule_type"
                rules="required"
                :value="old('schedule_type', $group->schedule_type ?? 'daily')"
                :label="trans('markup::app.admin.groups.form.schedule-type')"
            >
                <option value="daily">@lang('markup::app.admin.groups.form.daily')</option>
                <option value="weekly">@lang('markup::app.admin.groups.form.weekly')</option>
            </x-admin::form.control-group.control>
            <x-admin::form.control-group.error control-name="schedule_type" />
        </x-admin::form.control-group>
    </div>

    <div class="flex gap-4">
        <x-admin::form.control-group class="flex-1">
            <x-admin::form.control-group.label class="required">
                @lang('markup::app.admin.groups.form.status')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="select"
                name="is_active"
                rules="required"
                :value="old('is_active', isset($group) ? ($group->is_active ? '1' : '0') : '1')"
                :label="trans('markup::app.admin.groups.form.status')"
            >
                <option value="1">@lang('markup::app.admin.groups.form.active')</option>
                <option value="0">@lang('markup::app.admin.groups.form.inactive')</option>
            </x-admin::form.control-group.control>
            <x-admin::form.control-group.error control-name="is_active" />
        </x-admin::form.control-group>

        <x-admin::form.control-group class="flex-1">
            <x-admin::form.control-group.label>
                @lang('markup::app.admin.groups.form.sort-order')
            </x-admin::form.control-group.label>

            <x-admin::form.control-group.control
                type="text"
                name="sort_order"
                :value="old('sort_order', $group->sort_order ?? 0)"
                :placeholder="trans('markup::app.admin.groups.form.sort-order')"
            />
            <x-admin::form.control-group.error control-name="sort_order" />
        </x-admin::form.control-group>
    </div>

    @php
        $applyToAllOld = old('apply_to_all_sources', isset($group) ? ($group->apply_to_all_sources ? '1' : '0') : '1');
        $initialInventorySourceIds = old('inventory_sources', isset($group) ? $group->inventorySources->pluck('id')->values()->all() : []);
        $markupInventorySourceLabels = [
            'loading'         => trans('markup::app.admin.groups.form.inventory-sources-loading'),
            'loadError'       => trans('markup::app.admin.groups.form.inventory-sources-load-error'),
            'empty'           => trans('markup::app.admin.groups.form.inventory-sources-empty'),
            'modalTitle'      => trans('markup::app.admin.groups.form.inventory-sources-modal-title'),
            'selectBtn'       => trans('markup::app.admin.groups.form.inventory-sources-select-btn'),
            'selectedSummary' => trans('markup::app.admin.groups.form.inventory-sources-selected-summary'),
            'code'            => trans('markup::app.admin.groups.form.inventory-sources-code'),
            'apply'           => trans('markup::app.admin.groups.form.inventory-sources-modal-apply'),
            'cancel'          => trans('markup::app.admin.groups.form.inventory-sources-modal-cancel'),
        ];
    @endphp

    <x-admin::form.control-group>
        <v-markup-inventory-sources
            sources-url="{{ route('admin.markup.groups.inventory-sources') }}"
            initial-apply-to-all="{{ $applyToAllOld }}"
            :initial-selected-ids='@json($initialInventorySourceIds)'
            :labels='@json($markupInventorySourceLabels)'
        ></v-markup-inventory-sources>

        <x-admin::form.control-group.error control-name="apply_to_all_sources" />
        <x-admin::form.control-group.error control-name="inventory_sources" />
    </x-admin::form.control-group>
</div>

@php
    $schedulesJson = isset($group) && $group->schedules
        ? $group->schedules->toArray()
        : [];

    $conditionsJson = [];

    if (isset($group) && $group->conditions) {
        foreach ($group->conditions as $c) {
            $conditionsJson[] = [
                'cost_from'        => $c->cost_from,
                'cost_to'          => $c->cost_to,
                'adjustment_type'  => $c->adjustment_type,
                'adjustment_value' => $c->adjustment_value,
                'sort_order'       => $c->sort_order,
                'categories'       => $c->categories->pluck('id')->toArray(),
                'products'         => $c->products->pluck('id')->toArray(),
            ];
        }
    }

    $logsExist = isset($group) && $group->logs && $group->logs->count() > 0;
@endphp

{{-- Schedules --}}
<div class="box-shadow mt-3 rounded bg-white p-4 dark:bg-gray-900">
    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
        @lang('markup::app.admin.groups.form.schedules')
    </p>

    <v-markup-schedules
        :initial-schedules='@json($schedulesJson)'
    ></v-markup-schedules>
</div>

{{-- Conditions --}}
<div class="box-shadow mt-3 rounded bg-white p-4 dark:bg-gray-900">
    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
        @lang('markup::app.admin.groups.form.conditions')
    </p>

    <v-markup-conditions
        :initial-conditions='@json($conditionsJson)'
    ></v-markup-conditions>
</div>

{{-- Logs (edit only) --}}
@if($logsExist)
    <div class="box-shadow mt-3 rounded bg-white p-4 dark:bg-gray-900">
        <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
            @lang('markup::app.admin.groups.form.logs')
        </p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b dark:border-gray-700">
                        <th class="px-2 py-1 text-left">@lang('markup::app.admin.groups.form.log-action')</th>
                        <th class="px-2 py-1 text-left">@lang('markup::app.admin.groups.form.log-products')</th>
                        <th class="px-2 py-1 text-left">@lang('markup::app.admin.groups.form.log-message')</th>
                        <th class="px-2 py-1 text-left">@lang('markup::app.admin.groups.form.log-date')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($group->logs as $log)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-2 py-1">{{ $log->action }}</td>
                            <td class="px-2 py-1">{{ $log->products_affected }}</td>
                            <td class="px-2 py-1">{{ $log->message }}</td>
                            <td class="px-2 py-1">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@pushOnce('scripts')
    <script type="text/x-template" id="v-markup-inventory-sources-template">
        <div>
            <label class="mb-1.5 flex items-center gap-1 text-sm font-medium text-gray-800 required dark:text-white">
                @lang('markup::app.admin.groups.form.apply-to-all-sources')
            </label>

            <select
                name="apply_to_all_sources"
                v-model="applyToAll"
                class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border bg-white px-3 py-1.5 text-sm leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
            >
                <option value="1">@lang('markup::app.admin.groups.form.yes')</option>
                <option value="0">@lang('markup::app.admin.groups.form.no')</option>
            </select>

            <div v-show="applyToAll === '0'" class="mt-3">
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @{{ selectedSummaryText }}
                </p>

                <template v-if="applyToAll === '0'">
                    <input
                        v-for="id in selectedIds"
                        :key="id"
                        type="hidden"
                        name="inventory_sources[]"
                        :value="id"
                    />
                </template>

                <button
                    type="button"
                    class="secondary-button mt-2"
                    @click="openModal"
                >
                    @{{ labels.selectBtn }}
                </button>
            </div>

            <v-modal ref="sourcesModal">
                <template #header="{ toggle }">
                    <div class="flex w-full items-center justify-between gap-2.5 pr-2">
                        <p class="text-lg font-bold text-gray-800 dark:text-white">
                            @{{ labels.modalTitle }}
                        </p>

                        <span
                            class="icon-cancel-1 cursor-pointer text-3xl hover:rounded-md hover:bg-gray-100 dark:hover:bg-gray-950"
                            @click="toggle"
                        >
                        </span>
                    </div>
                </template>

                <template #content>
                    <div v-if="loadState === 'loading'" class="py-6 text-center text-sm text-gray-600 dark:text-gray-300">
                        @{{ labels.loading }}
                    </div>

                    <div v-else-if="loadState === 'error'" class="py-6 text-center text-sm text-red-600">
                        @{{ labels.loadError }}
                    </div>

                    <div v-else-if="!allSources.length" class="py-6 text-center text-sm text-gray-500">
                        @{{ labels.empty }}
                    </div>

                    <ul v-else class="max-h-72 space-y-2 overflow-y-auto py-1">
                        <li
                            v-for="src in allSources"
                            :key="src.id"
                            class="flex items-start gap-2 rounded border border-gray-200 p-2 dark:border-gray-700"
                        >
                            <input
                                type="checkbox"
                                class="peer mt-0.5 cursor-pointer"
                                :id="'inv-src-' + src.id"
                                :checked="pendingIds.includes(Number(src.id))"
                                @change="togglePending(src.id)"
                            />

                            <label
                                class="cursor-pointer text-sm text-gray-800 dark:text-gray-200"
                                :for="'inv-src-' + src.id"
                            >
                                <span class="font-medium">@{{ src.name }}</span>
                                <span class="ml-1 text-xs text-gray-500">
                                    (@{{ labels.code }}: @{{ src.code }})
                                </span>
                            </label>
                        </li>
                    </ul>
                </template>

                <template #footer>
                    <div class="flex w-full justify-end gap-2.5">
                        <button
                            type="button"
                            class="secondary-button"
                            @click="cancelModal"
                        >
                            @{{ labels.cancel }}
                        </button>

                        <button
                            type="button"
                            class="primary-button"
                            @click="confirmModal"
                        >
                            @{{ labels.apply }}
                        </button>
                    </div>
                </template>
            </v-modal>
        </div>
    </script>

    <script type="text/x-template" id="v-markup-schedules-template">
        <div>
            <div
                v-for="(schedule, index) in schedules"
                :key="index"
                class="mb-3 flex items-end gap-3 rounded border p-3 dark:border-gray-700"
            >
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        @lang('markup::app.admin.groups.form.day-of-week')
                    </label>
                    <select
                        :name="'schedules[' + index + '][day_of_week]'"
                        class="inline-flex w-full cursor-pointer appearance-none items-center justify-between gap-x-1 rounded-md border bg-white px-3 py-1.5 text-sm leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                    >
                        <option value="">@lang('markup::app.admin.groups.form.every-day')</option>
                        <option v-for="(label, day) in days" :key="day" :value="day" :selected="schedule.day_of_week == day">
                            @{{ label }}
                        </option>
                    </select>
                </div>

                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        @lang('markup::app.admin.groups.form.time-from')
                    </label>
                    <input
                        type="time"
                        :name="'schedules[' + index + '][time_from]'"
                        v-model="schedule.time_from"
                        class="w-full rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        required
                    />
                </div>

                <div class="flex-1">
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                        @lang('markup::app.admin.groups.form.time-to')
                    </label>
                    <input
                        type="time"
                        :name="'schedules[' + index + '][time_to]'"
                        v-model="schedule.time_to"
                        class="w-full rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        required
                    />
                </div>

                <button
                    type="button"
                    class="rounded-md bg-red-100 p-1.5 text-red-600 hover:bg-red-200"
                    @click="removeSchedule(index)"
                >
                    <i class="icon-delete text-xl"></i>
                </button>
            </div>

            <button
                type="button"
                class="secondary-button mt-2"
                @click="addSchedule"
            >
                @lang('markup::app.admin.groups.form.add-schedule')
            </button>
        </div>
    </script>

    <script type="text/x-template" id="v-markup-conditions-template">
        <div>
            <div
                v-for="(condition, idx) in conditions"
                :key="idx"
                class="mb-3 rounded border p-3 dark:border-gray-700"
            >
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        @lang('markup::app.admin.groups.form.condition') #@{{ idx + 1 }}
                    </span>
                    <button
                        type="button"
                        class="rounded-md bg-red-100 p-1.5 text-red-600 hover:bg-red-200"
                        @click="removeCondition(idx)"
                    >
                        <i class="icon-delete text-xl"></i>
                    </button>
                </div>

                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            @lang('markup::app.admin.groups.form.cost-from')
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            :name="'conditions[' + idx + '][cost_from]'"
                            v-model="condition.cost_from"
                            class="w-full rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        />
                    </div>

                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            @lang('markup::app.admin.groups.form.cost-to')
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            :name="'conditions[' + idx + '][cost_to]'"
                            v-model="condition.cost_to"
                            class="w-full rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        />
                    </div>

                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            @lang('markup::app.admin.groups.form.adjustment-type')
                        </label>
                        <select
                            :name="'conditions[' + idx + '][adjustment_type]'"
                            v-model="condition.adjustment_type"
                            class="w-full cursor-pointer rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            required
                        >
                            <option value="percent">@lang('markup::app.admin.groups.form.percent')</option>
                            <option value="fixed">@lang('markup::app.admin.groups.form.fixed')</option>
                        </select>
                    </div>

                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            @lang('markup::app.admin.groups.form.adjustment-value')
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            :name="'conditions[' + idx + '][adjustment_value]'"
                            v-model="condition.adjustment_value"
                            class="w-full rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            required
                        />
                    </div>

                    <div class="w-20">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            @lang('markup::app.admin.groups.form.sort-order')
                        </label>
                        <input
                            type="number"
                            :name="'conditions[' + idx + '][sort_order]'"
                            v-model="condition.sort_order"
                            class="w-full rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                        />
                    </div>
                </div>

                <div class="mt-3 flex gap-3">
                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            @lang('markup::app.admin.groups.form.categories')
                        </label>
                        <textarea
                            :name="'conditions[' + idx + '][categories]'"
                            v-model="condition.categoriesStr"
                            class="w-full rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            :placeholder="'@lang('markup::app.admin.groups.form.categories-placeholder')'"
                            rows="1"
                        ></textarea>
                    </div>

                    <div class="flex-1">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">
                            @lang('markup::app.admin.groups.form.products')
                        </label>
                        <textarea
                            :name="'conditions[' + idx + '][products]'"
                            v-model="condition.productsStr"
                            class="w-full rounded-md border px-3 py-1.5 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                            :placeholder="'@lang('markup::app.admin.groups.form.products-placeholder')'"
                            rows="1"
                        ></textarea>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="secondary-button mt-2"
                @click="addCondition"
            >
                @lang('markup::app.admin.groups.form.add-condition')
            </button>
        </div>
    </script>

    <script type="module">
        app.component('v-markup-inventory-sources', {
            template: '#v-markup-inventory-sources-template',

            props: {
                sourcesUrl: {
                    type: String,
                    required: true,
                },
                initialApplyToAll: {
                    type: String,
                    default: '1',
                },
                initialSelectedIds: {
                    type: Array,
                    default: () => [],
                },
                labels: {
                    type: Object,
                    default: () => ({}),
                },
            },

            data() {
                return {
                    applyToAll: this.initialApplyToAll === '0' ? '0' : '1',
                    selectedIds: (this.initialSelectedIds || []).map((id) => Number(id)),
                    pendingIds: [],
                    allSources: [],
                    loadState: 'idle',
                };
            },

            computed: {
                selectedSummaryText() {
                    const template = this.labels.selectedSummary || ':count';

                    return String(template).replace(':count', String(this.selectedIds.length));
                },
            },

            watch: {
                applyToAll(value) {
                    if (value === '1') {
                        this.selectedIds = [];
                        this.pendingIds = [];
                    }
                },
            },

            mounted() {
                this.loadSources();
            },

            methods: {
                async loadSources() {
                    this.loadState = 'loading';

                    try {
                        const response = await fetch(this.sourcesUrl, {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        });

                        if (! response.ok) {
                            throw new Error('Bad response');
                        }

                        const payload = await response.json();

                        this.allSources = payload.data || [];
                        this.loadState = 'loaded';
                    } catch (e) {
                        this.loadState = 'error';
                    }
                },

                openModal() {
                    this.pendingIds = [...this.selectedIds];
                    this.$refs.sourcesModal.open();
                },

                cancelModal() {
                    this.$refs.sourcesModal.close();
                },

                confirmModal() {
                    this.selectedIds = [...this.pendingIds];
                    this.$refs.sourcesModal.close();
                },

                togglePending(sourceId) {
                    const id = Number(sourceId);
                    const index = this.pendingIds.indexOf(id);

                    if (index > -1) {
                        this.pendingIds.splice(index, 1);
                    } else {
                        this.pendingIds.push(id);
                    }
                },
            },
        });

        app.component('v-markup-schedules', {
            template: '#v-markup-schedules-template',

            props: {
                initialSchedules: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    schedules: this.initialSchedules.length
                        ? this.initialSchedules.map(s => ({
                            day_of_week: s.day_of_week ?? '',
                            time_from: s.time_from ? s.time_from.substring(0, 5) : '',
                            time_to: s.time_to ? s.time_to.substring(0, 5) : '',
                        }))
                        : [{ day_of_week: '', time_from: '', time_to: '' }],
                    days: {
                        0: '@lang("markup::app.admin.groups.form.sunday")',
                        1: '@lang("markup::app.admin.groups.form.monday")',
                        2: '@lang("markup::app.admin.groups.form.tuesday")',
                        3: '@lang("markup::app.admin.groups.form.wednesday")',
                        4: '@lang("markup::app.admin.groups.form.thursday")',
                        5: '@lang("markup::app.admin.groups.form.friday")',
                        6: '@lang("markup::app.admin.groups.form.saturday")',
                    },
                };
            },

            methods: {
                addSchedule() {
                    this.schedules.push({ day_of_week: '', time_from: '', time_to: '' });
                },

                removeSchedule(index) {
                    if (this.schedules.length > 1) {
                        this.schedules.splice(index, 1);
                    }
                },
            },
        });

        app.component('v-markup-conditions', {
            template: '#v-markup-conditions-template',

            props: {
                initialConditions: {
                    type: Array,
                    default: () => [],
                },
            },

            data() {
                return {
                    conditions: this.initialConditions.length
                        ? this.initialConditions.map(c => ({
                            cost_from: c.cost_from ?? '',
                            cost_to: c.cost_to ?? '',
                            adjustment_type: c.adjustment_type ?? 'percent',
                            adjustment_value: c.adjustment_value ?? '',
                            sort_order: c.sort_order ?? 0,
                            categoriesStr: (c.categories || []).join(', '),
                            productsStr: (c.products || []).join(', '),
                        }))
                        : [this.emptyCondition()],
                };
            },

            methods: {
                emptyCondition() {
                    return {
                        cost_from: '',
                        cost_to: '',
                        adjustment_type: 'percent',
                        adjustment_value: '',
                        sort_order: 0,
                        categoriesStr: '',
                        productsStr: '',
                    };
                },

                addCondition() {
                    this.conditions.push(this.emptyCondition());
                },

                removeCondition(index) {
                    if (this.conditions.length > 1) {
                        this.conditions.splice(index, 1);
                    }
                },
            },
        });
    </script>
@endPushOnce

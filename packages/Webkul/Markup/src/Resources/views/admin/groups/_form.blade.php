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

    <x-admin::form.control-group>
        <x-admin::form.control-group.label class="required">
            @lang('markup::app.admin.groups.form.apply-to-all-sources')
        </x-admin::form.control-group.label>

        <x-admin::form.control-group.control
            type="select"
            name="apply_to_all_sources"
            rules="required"
            :value="old('apply_to_all_sources', isset($group) ? ($group->apply_to_all_sources ? '1' : '0') : '1')"
            :label="trans('markup::app.admin.groups.form.apply-to-all-sources')"
        >
            <option value="1">@lang('markup::app.admin.groups.form.yes')</option>
            <option value="0">@lang('markup::app.admin.groups.form.no')</option>
        </x-admin::form.control-group.control>
        <x-admin::form.control-group.error control-name="apply_to_all_sources" />
    </x-admin::form.control-group>
</div>

{{-- Schedules --}}
<div class="box-shadow mt-3 rounded bg-white p-4 dark:bg-gray-900">
    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
        @lang('markup::app.admin.groups.form.schedules')
    </p>

    <v-markup-schedules
        :initial-schedules='@json($group->schedules ?? [])'
    ></v-markup-schedules>
</div>

{{-- Conditions --}}
<div class="box-shadow mt-3 rounded bg-white p-4 dark:bg-gray-900">
    <p class="mb-4 text-base font-semibold text-gray-800 dark:text-white">
        @lang('markup::app.admin.groups.form.conditions')
    </p>

    <v-markup-conditions
        :initial-conditions='@json(
            isset($group)
                ? $group->conditions->map(fn($c) => [
                    "cost_from" => $c->cost_from,
                    "cost_to" => $c->cost_to,
                    "adjustment_type" => $c->adjustment_type,
                    "adjustment_value" => $c->adjustment_value,
                    "sort_order" => $c->sort_order,
                    "categories" => $c->categories->pluck("id")->toArray(),
                    "products" => $c->products->pluck("id")->toArray(),
                ])
                : []
        )'
    ></v-markup-conditions>
</div>

{{-- Logs (edit only) --}}
@if(isset($group) && $group->logs->count())
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

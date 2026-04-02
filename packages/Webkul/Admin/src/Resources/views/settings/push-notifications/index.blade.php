<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.push-notifications.index.title')
    </x-slot>

    {!! view_render_event('bagisto.admin.settings.push_notifications.index.before') !!}

    <v-push-notification-settings
        :event-map="{{ json_encode($eventMap) }}"
        :initial-settings="{{ json_encode($settings) }}"
        :vapid="{{ json_encode($vapid) }}"
    >
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('admin::app.settings.push-notifications.index.title')
            </p>
        </div>

        <x-admin::shimmer.datagrid />
    </v-push-notification-settings>

    {!! view_render_event('bagisto.admin.settings.push_notifications.index.after') !!}

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-push-notification-settings-template"
        >
            <div>
                <!-- Page header -->
                <div class="flex items-center justify-between gap-4 mb-5 max-sm:flex-wrap">
                    <p class="text-xl font-bold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.push-notifications.index.title')
                    </p>
                </div>

                <!-- No VAPID warning -->
                <div
                    v-if="!vapidData"
                    class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                >
                    @lang('admin::app.settings.push-notifications.index.no-vapid-warning')
                </div>

                <!-- VAPID Keys Card -->
                <div class="mb-6 rounded-lg border bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                        @lang('admin::app.settings.push-notifications.index.vapid-section')
                    </p>
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                        @lang('admin::app.settings.push-notifications.index.vapid-description')
                    </p>

                    <div class="mb-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                @lang('admin::app.settings.push-notifications.index.public-key')
                            </label>
                            <input
                                type="text"
                                :value="vapidData?.public_key ?? '—'"
                                readonly
                                class="w-full rounded-md border bg-gray-50 px-3 py-2 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                @lang('admin::app.settings.push-notifications.index.subject')
                            </label>
                            <div class="flex gap-2">
                                <input
                                    type="text"
                                    v-model="vapidSubject"
                                    class="w-full rounded-md border px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                />
                                <button
                                    type="button"
                                    class="secondary-button shrink-0"
                                    @click="saveSubject"
                                >
                                    @lang('admin::app.settings.push-notifications.index.save-subject-btn')
                                </button>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="primary-button"
                        @click="generateVapid"
                        :disabled="generatingVapid"
                    >
                        <span v-if="generatingVapid">@lang('admin::app.common.saving')…</span>
                        <span v-else>@lang('admin::app.settings.push-notifications.index.generate-btn')</span>
                    </button>
                </div>

                <!-- Tabs -->
                <div class="mb-4 flex gap-1 border-b dark:border-gray-700">
                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'admin'
                            ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        @click="activeTab = 'admin'"
                    >
                        @lang('admin::app.settings.push-notifications.index.admin-events-tab')
                    </button>

                    <button
                        type="button"
                        class="px-4 py-2 text-sm font-medium transition-colors"
                        :class="activeTab === 'shop'
                            ? 'border-b-2 border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                            : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                        @click="activeTab = 'shop'"
                    >
                        @lang('admin::app.settings.push-notifications.index.shop-events-tab')
                    </button>
                </div>

                <!-- Events Table -->
                <div class="rounded-lg border bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.settings.push-notifications.index.event-col')
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.settings.push-notifications.index.title-col')
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 min-w-[280px]">
                                        @lang('admin::app.settings.push-notifications.index.body-col')
                                    </th>
                                    <th
                                        v-if="activeTab === 'shop'"
                                        class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300"
                                    >
                                        @lang('admin::app.settings.push-notifications.index.target-col')
                                    </th>
                                    <th class="px-4 py-3 text-center font-semibold text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.settings.push-notifications.index.active-col')
                                    </th>
                                    <th class="px-4 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">
                                        @lang('admin::app.settings.push-notifications.index.save-btn')
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                <template
                                    v-for="(meta, eventKey) in filteredEvents"
                                    :key="eventKey"
                                >
                                    <tr class="border-b last:border-0 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-950">
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-gray-800 dark:text-white">@{{ meta.label }}</p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500">@{{ eventKey }}</p>
                                        </td>

                                        <td class="px-4 py-3">
                                            <input
                                                type="text"
                                                v-model="getRow(eventKey).title"
                                                class="w-full rounded-md border px-3 py-1.5 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                                placeholder="Notification title"
                                            />
                                        </td>

                                        <td class="px-4 py-3">
                                            <textarea
                                                v-model="getRow(eventKey).body"
                                                rows="2"
                                                class="w-full rounded-md border px-3 py-1.5 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                                placeholder="Message body. Use {order_id}, {customer_name}, {order_status}"
                                            ></textarea>
                                        </td>

                                        <td
                                            v-if="activeTab === 'shop'"
                                            class="px-4 py-3"
                                        >
                                            <select
                                                v-model="getRow(eventKey).target"
                                                class="rounded-md border px-2 py-1.5 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            >
                                                <option value="admin">Admin only</option>
                                                <option value="customer">Customer only</option>
                                                <option value="both">Both</option>
                                            </select>
                                        </td>

                                        <td class="px-4 py-3 text-center">
                                            <label class="relative inline-flex cursor-pointer items-center">
                                                <input
                                                    type="checkbox"
                                                    v-model="getRow(eventKey).is_active"
                                                    class="peer sr-only"
                                                />
                                                <div class="peer h-5 w-9 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-blue-600 peer-checked:after:translate-x-full dark:bg-gray-700"></div>
                                            </label>
                                        </td>

                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="secondary-button"
                                                @click="saveSetting(eventKey)"
                                                :disabled="savingRows[eventKey]"
                                            >
                                                <span v-if="savingRows[eventKey]">…</span>
                                                <span v-else>@lang('admin::app.settings.push-notifications.index.save-btn')</span>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Placeholders hint -->
                <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                    @lang('admin::app.settings.push-notifications.index.placeholders-hint'):
                    <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">{order_id}</code>
                    <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">{order_status}</code>
                    <code class="rounded bg-gray-100 px-1 dark:bg-gray-800">{customer_name}</code>
                </p>
            </div>
        </script>

        <script type="module">
            app.component('v-push-notification-settings', {
                template: '#v-push-notification-settings-template',

                props: {
                    eventMap:        { type: Object, default: () => ({}) },
                    initialSettings: { type: Object, default: () => ({}) },
                    vapid:           { type: Object, default: null },
                },

                data() {
                    return {
                        activeTab:      'admin',
                        vapidData:      this.vapid,
                        vapidSubject:   this.vapid?.subject ?? '',
                        generatingVapid: false,
                        savingRows:     {},
                        rows:           {},
                    };
                },

                computed: {
                    filteredEvents() {
                        return Object.fromEntries(
                            Object.entries(this.eventMap).filter(([, meta]) => {
                                if (this.activeTab === 'admin') {
                                    return meta.section === 'admin' || meta.section === 'both';
                                }
                                return meta.section === 'shop' || meta.section === 'both';
                            })
                        );
                    },
                },

                methods: {
                    getRow(eventKey) {
                        if (! this.rows[eventKey]) {
                            const target = this.activeTab === 'admin' ? 'admin' : 'both';
                            const key    = eventKey + '|' + target;
                            const saved  = this.initialSettings[key];

                            this.rows[eventKey] = {
                                title:     saved?.title     ?? '',
                                body:      saved?.body      ?? '',
                                target:    saved?.target    ?? target,
                                is_active: saved?.is_active ?? true,
                            };
                        }

                        return this.rows[eventKey];
                    },

                    saveSetting(eventKey) {
                        const row = this.getRow(eventKey);

                        this.savingRows = { ...this.savingRows, [eventKey]: true };

                        const target = this.activeTab === 'admin' ? 'admin' : row.target;

                        this.$axios.post('{{ route('admin.settings.push_notifications.update') }}', {
                            event:     eventKey,
                            target:    target,
                            title:     row.title,
                            body:      row.body,
                            is_active: row.is_active,
                        })
                        .then(response => {
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch(error => {
                            const msg = error.response?.data?.message ?? 'Error saving setting.';
                            this.$emitter.emit('add-flash', { type: 'error', message: msg });
                        })
                        .finally(() => {
                            this.savingRows = { ...this.savingRows, [eventKey]: false };
                        });
                    },

                    generateVapid() {
                        this.generatingVapid = true;

                        this.$axios.post('{{ route('admin.settings.push_notifications.vapid.generate') }}', {
                            subject: this.vapidSubject || 'mailto:admin@' + window.location.hostname,
                        })
                        .then(response => {
                            this.vapidData = { ...this.vapidData, public_key: response.data.public_key };
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch(() => {
                            this.$emitter.emit('add-flash', { type: 'error', message: 'Failed to generate VAPID keys.' });
                        })
                        .finally(() => {
                            this.generatingVapid = false;
                        });
                    },

                    saveSubject() {
                        this.$axios.put('{{ route('admin.settings.push_notifications.vapid.update') }}', {
                            subject: this.vapidSubject,
                        })
                        .then(response => {
                            this.$emitter.emit('add-flash', { type: 'success', message: response.data.message });
                        })
                        .catch(error => {
                            const msg = error.response?.data?.message ?? 'Failed to save subject.';
                            this.$emitter.emit('add-flash', { type: 'error', message: msg });
                        });
                    },
                },
            });
        </script>
    @endpushOnce
</x-admin::layouts>

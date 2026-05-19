<x-admin::layouts>
    @include('admin::settings.order-statuses._icons')

    <!-- Page Title -->
    <x-slot:title>
        @lang('admin::app.sales.orders.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.sales.orders.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <x-admin::datagrid.export src="{{ route('admin.sales.orders.index') }}" />

            {!! view_render_event('bagisto.admin.sales.orders.create.before') !!}

            @if (bouncer()->hasPermission('sales.orders.create'))
                <button
                    class="primary-button"
                    @click="$refs.selectCustomerComponent.openDrawer()"
                >
                    @lang('admin::app.sales.orders.index.create-btn')
                </button>
            @endif

            {!! view_render_event('bagisto.admin.sales.orders.create.after') !!}
        </div>
    </div>

    <v-customer-search ref="selectCustomerComponent"></v-customer-search>

    <v-order-status-panel></v-order-status-panel>

    <x-admin::datagrid :src="route('admin.sales.orders.index')" :isMultiRow="true">
        <template #header="{
            isLoading,
            available,
            applied,
            selectAll,
            sort,
            performAction
        }">
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.table.head :isMultiRow="true" />
            </template>

            <template v-else>
                <!-- Grid Header Columns -->
                <div class="row grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 items-center border-b px-2 sm:px-4 py-2.5 dark:border-gray-800">
                    <div
                        class="flex select-none items-center gap-2.5"
                        v-for="(columnGroup, index) in [['increment_id', 'created_at', 'status'], ['base_grand_total', 'method', 'channel_id'], ['full_name', 'customer_email', 'location'], ['items']]"
                    >
                        <p class="text-gray-600 dark:text-gray-300 text-sm sm:text-base">
                            <span class="[&>*]:after:content-['_/_']">
                                <template v-for="column in columnGroup">
                                    <span
                                        class="after:content-['/'] last:after:content-['']"
                                        :class="{
                                            'font-medium text-gray-800 dark:text-white': applied.sort.column == column,
                                            'cursor-pointer hover:text-gray-800 dark:hover:text-white': available.columns.find(columnTemp => columnTemp.index === column)?.sortable,
                                        }"
                                        @click="
                                            available.columns.find(columnTemp => columnTemp.index === column)?.sortable ? sort(available.columns.find(columnTemp => columnTemp.index === column)) : {}
                                        "
                                    >
                                        @{{ available.columns.find(columnTemp => columnTemp.index === column)?.label }}
                                    </span>
                                </template>
                            </span>

                            <i
                                class="align-text-bottom text-base text-gray-800 dark:text-white ltr:ml-1.5 rtl:mr-1.5"
                                :class="[applied.sort.order === 'asc' ? 'icon-down-stat': 'icon-up-stat']"
                                v-if="columnGroup.includes(applied.sort.column)"
                            >
                            </i>
                        </p>
                    </div>
                </div>
            </template>
        </template>

        <template #body="{
            isLoading,
            available,
            applied,
            selectAll,
            sort,
            performAction
        }">
            <template v-if="isLoading">
                <x-admin::shimmer.datagrid.table.body :isMultiRow="true" />
            </template>

            <template v-else>
                <!-- Order Rows -->
                <div
                    class="row grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-y-4 border-b px-2 sm:px-4 py-2.5 transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950"
                    v-for="record in available.records"
                >
                    <!-- Order Id, Created, Status Section -->
                    <div class="flex flex-col gap-1.5">
                        <p class="text-sm sm:text-base font-semibold text-gray-800 dark:text-white">
                            @{{ "@lang('admin::app.sales.orders.index.datagrid.id')".replace(':id', record.increment_id) }}
                        </p>

                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                            @{{ record.created_at }}
                        </p>

                        <v-order-status-select
                            :record="record"
                            update-url="{{ route('admin.sales.orders.update_status', ':id') }}"
                        ></v-order-status-select>
                    </div>

                    <!-- Total Amount, Pay Via, Channel -->
                    <div class="flex flex-col gap-1.5">
                        <p class="text-sm sm:text-base font-semibold text-gray-800 dark:text-white">
                            @{{ $admin.formatPrice(record.base_grand_total) }}
                        </p>

                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                            @lang('admin::app.sales.orders.index.datagrid.pay-by', ['method' => ''])@{{ record.method }}
                        </p>

                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                            @{{ record.channel_name }}
                        </p>
                    </div>

                    <!-- Customer, Email, Location Section -->
                    <div class="flex flex-col gap-1.5">
                        <p class="text-sm sm:text-base text-gray-800 dark:text-white">
                            @{{ record.full_name }}
                        </p>

                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                            @{{ record.customer_email }}
                        </p>

                        <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                            @{{ record.location }}
                        </p>
                    </div>

                    <!-- Images Section -->
                    <div class="flex items-center justify-between gap-x-2">
                        <div
                            class="flex flex-col gap-1.5 text-xs sm:text-sm"
                            v-html="record.items"
                        >
                        </div>

                        <a :href=`{{ route('admin.sales.orders.view', '') }}/${record.id}`>
                            <span class="icon-sort-right rtl:icon-sort-left cursor-pointer p-1.5 text-xl sm:text-2xl hover:rounded-md hover:bg-gray-200 dark:hover:bg-gray-800 ltr:ml-1 rtl:mr-1"></span>
                        </a>
                    </div>
                </div>
            </template>
        </template>
    </x-admin::datagrid>

    @include('admin::customers.customers.index.create')

    @pushOnce('scripts')
        @php
            $orderStatusesJs = $orderStatuses->map(fn ($s) => [
                'code'  => $s->code,
                'name'  => $s->name,
                'color' => $s->color ?? '#6b7280',
                'icon'  => $s->icon,
            ]);
        @endphp
        <script>
            const orderStatuses = @json($orderStatusesJs);
        </script>

        <script
            type="text/x-template"
            id="v-order-status-panel-template"
        >
            <div
                v-if="statuses.length"
                class="mb-4 flex gap-3 overflow-x-auto pb-2"
            >
                <div
                    v-for="status in statuses"
                    :key="status.code"
                    class="flex w-[140px] flex-shrink-0 cursor-pointer flex-col items-center gap-1.5 rounded-lg border-2 bg-white px-4 py-3 shadow-sm transition-all duration-200 hover:scale-105 hover:shadow-md dark:bg-gray-900"
                    :style="{ borderColor: status.color }"
                    @click="filter(status.code)"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-full"
                        :style="{ backgroundColor: status.color + '22' }"
                    >
                        <svg
                            v-if="status.icon && getIcon(status.icon)"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            :stroke="status.color"
                            stroke-width="1.5"
                            v-html="getIcon(status.icon)"
                        ></svg>
                        <span
                            v-else
                            class="h-3 w-3 rounded-full"
                            :style="{ backgroundColor: status.color }"
                        ></span>
                    </div>

                    <span class="text-center text-xs font-semibold text-gray-700 dark:text-gray-300">
                        @{{ status.name }}
                    </span>

                    <span
                        class="text-lg font-bold"
                        :style="{ color: status.color }"
                    >
                        @{{ status.count }}
                    </span>
                </div>
            </div>
        </script>

        <script
            type="text/x-template"
            id="v-order-status-select-template"
        >
            <div class="relative" v-if="currentStatus">
                <button
                    type="button"
                    class="flex cursor-pointer items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium transition-opacity hover:opacity-80"
                    :style="{ backgroundColor: currentStatus.color + '22', color: currentStatus.color }"
                    @click.stop="open = !open"
                >
                    <svg
                        v-if="currentStatus.icon && getIcon(currentStatus.icon)"
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-3.5 w-3.5"
                        fill="none"
                        viewBox="0 0 24 24"
                        :stroke="currentStatus.color"
                        stroke-width="1.5"
                        v-html="getIcon(currentStatus.icon)"
                    ></svg>
                    @{{ currentStatus.name }}
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    v-if="open"
                    class="absolute left-0 top-full z-50 mt-1 min-w-[160px] rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
                    v-click-outside="() => open = false"
                >
                    <button
                        v-for="status in statuses"
                        :key="status.code"
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs transition-opacity hover:opacity-80"
                        :class="{ 'font-semibold': status.code === currentStatus.code }"
                        :style="{ backgroundColor: status.color + '22', color: status.color }"
                        @click="change(status)"
                    >
                        <span
                            class="inline-block h-2.5 w-2.5 flex-shrink-0 rounded-full"
                            :style="{ backgroundColor: status.color }"
                        ></span>
                        @{{ status.name }}
                    </button>
                </div>
            </div>

            <span v-else v-html="record.status"></span>
        </script>

        <script type="module">
            app.component('v-order-status-panel', {
                template: '#v-order-status-panel-template',

                data() {
                    return {
                        statuses: [],
                    };
                },

                mounted() {
                    this.load();
                },

                methods: {
                    load() {
                        this.$axios.get("{{ route('admin.sales.orders.status_counts') }}")
                            .then(response => { this.statuses = response.data; })
                            .catch(() => {});
                    },

                    getIcon(key) {
                        const icons = getOrderStatusIcons();
                        const found = icons.find(i => i.key === key);

                        return found ? found.svg : null;
                    },

                    filter(code) {
                        const url = new URL(window.location.href);

                        url.searchParams.delete('filters[status][0]');
                        url.searchParams.set('filters[status][0]', code);

                        window.location.href = url.toString();
                    },
                },
            });

            app.component('v-order-status-select', {
                template: '#v-order-status-select-template',

                props: {
                    record: { type: Object, required: true },
                    updateUrl: { type: String, required: true },
                },

                data() {
                    return {
                        open: false,
                        statuses: orderStatuses,
                        currentCode: this.record.status_code ?? null,
                        saving: false,
                    };
                },

                computed: {
                    currentStatus() {
                        return this.statuses.find(s => s.code === this.currentCode) ?? null;
                    },
                },

                methods: {
                    getIcon(key) {
                        const icons = getOrderStatusIcons();
                        const found = icons.find(i => i.key === key);

                        return found ? found.svg : null;
                    },

                    change(status) {
                        if (status.code === this.currentCode || this.saving) {
                            this.open = false;

                            return;
                        }

                        this.saving = true;
                        this.open = false;

                        const url = this.updateUrl.replace(':id', this.record.id);

                        this.$axios.post(url, { status: status.code })
                            .then(() => {
                                this.currentCode = status.code;

                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: "@lang('admin::app.sales.orders.index.status-change-success')",
                                });
                            })
                            .catch(error => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: error?.response?.data?.message ?? "@lang('admin::app.sales.orders.index.status-change-error')",
                                });
                            })
                            .finally(() => { this.saving = false; });
                    },
                },
            });
        </script>

        <script
            type="text/x-template"
            id="v-customer-search-template"
        >
            <div class="">
                <!-- Search Drawer -->
                <x-admin::drawer
                    ref="searchCustomerDrawer"
                    @close="searchTerm = ''; searchedCustomers = [];"
                >
                    <!-- Drawer Header -->
                    <x-slot:header>
                        <div class="grid gap-3">
                            <p class="py-2 text-xl font-medium dark:text-white">
                                @lang('admin::app.sales.orders.index.search-customer.title')
                            </p>

                            <div class="relative w-full">
                                <input
                                    type="text"
                                    class="block w-full rounded-lg border bg-white py-1.5 leading-6 text-gray-600 transition-all hover:border-gray-400 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 ltr:pl-3 ltr:pr-10 rtl:pl-10 rtl:pr-3"
                                    placeholder="@lang('admin::app.sales.orders.index.search-customer.search-by')"
                                    v-model.lazy="searchTerm"
                                    v-debounce="500"
                                />

                                <template v-if="isSearching">
                                    <img
                                        class="absolute top-2.5 h-5 w-5 animate-spin ltr:right-3 rtl:left-3"
                                        src="{{ bagisto_asset('images/spinner.svg') }}"
                                    />
                                </template>

                                <template v-else>
                                    <span class="icon-search pointer-events-none absolute top-1.5 flex items-center text-2xl ltr:right-3 rtl:left-3"></span>
                                </template>
                            </div>
                        </div>
                    </x-slot>

                    <!-- Drawer Content -->
                    <x-slot:content class="!p-0">
                        <div
                            class="grid max-h-[400px] overflow-y-auto"
                            v-if="searchedCustomers.length"
                        >
                            <div
                                class="grid cursor-pointer place-content-start gap-1.5 border-b border-slate-300 p-4 last:border-b-0 hover:bg-gray-100 dark:border-gray-800 dark:hover:bg-gray-950"
                                v-for="customer in searchedCustomers"
                                @click="createCart(customer)"
                            >
                                <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
                                    @{{ customer.first_name + ' ' + customer.last_name }}
                                </p>

                                <p class="text-gray-500">
                                    @{{ customer.email }}
                                </p>
                            </div>
                        </div>

                        <!-- For Empty Variations -->
                        <div
                            class="grid justify-center justify-items-center gap-3.5 px-2.5 py-10"
                            v-else
                        >
                            <!-- Placeholder Image -->
                            <img
                                src="{{ bagisto_asset('images/empty-placeholders/customers.svg') }}"
                                class="h-20 w-20 dark:mix-blend-exclusion dark:invert"
                            />

                            <!-- Add Variants Information -->
                            <div class="flex flex-col items-center gap-1.5">
                                <p class="text-base font-semibold text-gray-400">
                                    @lang('admin::app.sales.orders.index.search-customer.empty-title')
                                </p>

                                <p class="text-gray-400">
                                    @lang('admin::app.sales.orders.index.search-customer.empty-info')
                                </p>

                                <button
                                    class="secondary-button"
                                    @click="$refs.searchCustomerDrawer.close(); $refs.createCustomerComponent.openModal()"
                                >
                                    @lang('admin::app.sales.orders.index.search-customer.create-btn')
                                </button>
                            </div>
                        </div>
                    </x-slot>
                </x-admin::drawer>

                <v-create-customer-form
                    ref="createCustomerComponent"
                    @customer-created="createCart"
                ></v-create-customer-form>
            </div>
        </script>

        <script type="module">
            app.component('v-customer-search', {
                template: '#v-customer-search-template',

                data() {
                    return {
                        searchTerm: '',

                        searchedCustomers: [],

                        isSearching: false,
                    }
                },

                watch: {
                    searchTerm: function(newVal, oldVal) {
                        this.search();
                    }
                },

                methods: {
                    openDrawer() {
                        this.$refs.searchCustomerDrawer.open();
                    },

                    search() {
                        if (this.searchTerm.length <= 1) {
                            this.searchedCustomers = [];

                            return;
                        }

                        this.isSearching = true;

                        let self = this;

                        this.$axios.get("{{ route('admin.customers.customers.search') }}", {
                                params: {
                                    query: this.searchTerm,
                                }
                            })
                            .then(function(response) {
                                self.isSearching = false;

                                self.searchedCustomers = response.data.data;
                            })
                            .catch(function (error) {
                            });
                    },

                    createCart(customer) {
                        this.$axios.post("{{ route('admin.sales.cart.store') }}", {customer_id: customer.id})
                            .then(function(response) {
                                window.location.href = response.data.redirect_url;
                            })
                            .catch(function (error) {
                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    },
                }
            });
        </script>
    @endPushOnce
</x-admin::layouts>

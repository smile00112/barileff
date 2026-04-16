<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.catalog.categories.index.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.catalog.categories.index.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            {!! view_render_event('bagisto.admin.catalog.categories.index.create-button.before') !!}

            @if (bouncer()->hasPermission('catalog.categories.create'))
                <a href="{{ route('admin.catalog.categories.create') }}">
                    <div class="primary-button">
                        @lang('admin::app.catalog.categories.index.add-btn')
                    </div>
                </a>
            @endif

            {!! view_render_event('bagisto.admin.catalog.categories.index.create-button.after') !!}
        </div>
    </div>

    {!! view_render_event('bagisto.admin.catalog.categories.list.before') !!}

    <v-category-tree :items='@json($categories)' />

    {!! view_render_event('bagisto.admin.catalog.categories.list.after') !!}

    @pushOnce('scripts')
        <script type="text/x-template" id="v-category-tree-template">
            <div class="box-shadow mt-3 rounded-xl bg-white dark:bg-gray-900">
                <!-- Table header -->
                <div class="flex items-center gap-2.5 border-b px-4 py-3 text-xs font-semibold uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <div class="flex-1">@lang('admin::app.catalog.categories.index.datagrid.name')</div>
                    <div class="w-20 text-center">@lang('admin::app.catalog.categories.index.datagrid.position')</div>
                    <div class="w-28 text-center">@lang('admin::app.catalog.categories.index.datagrid.status')</div>
                    <div class="w-20"></div>
                </div>

                <!-- Tree rows -->
                <template
                    v-for="item in flatItems"
                    :key="item.node.id"
                >
                    <div
                        v-show="item.visible"
                        class="flex items-center gap-2.5 border-b px-4 py-3 text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-950"
                    >
                        <!-- Name with indent and toggle icon -->
                        <div
                            class="flex flex-1 items-center gap-1.5 overflow-hidden"
                            :style="{ paddingLeft: ((item.level - 1) * 24) + 'px' }"
                        >
                            <i
                                :class="[
                                    item.node.children && item.node.children.length
                                        ? (isExpanded(item.node.id) ? 'icon-sort-down' : 'icon-sort-right')
                                        : 'invisible',
                                    'shrink-0 cursor-pointer rounded-md text-xl transition-all hover:bg-gray-100 dark:hover:bg-gray-950'
                                ]"
                                @click.stop="toggle(item.node.id)"
                            ></i>

                            <img
                                v-if="item.node.logo_url"
                                :src="item.node.logo_url"
                                :alt="item.node.name"
                                class="h-8 w-8 shrink-0 rounded object-cover"
                            />

                            <i
                                v-else
                                :class="[
                                    item.node.children && item.node.children.length ? 'icon-folder' : 'icon-attribute',
                                    'shrink-0 text-2xl'
                                ]"
                            ></i>

                            <a
                                :href="editUrl(item.node.id)"
                                class="truncate hover:text-indigo-600 dark:hover:text-indigo-400"
                                v-text="item.node.name"
                            ></a>
                        </div>

                        <!-- Position -->
                        <div class="w-20 text-center text-sm text-gray-500 dark:text-gray-400">
                            @{{ item.node.position }}
                        </div>

                        <!-- Status badge -->
                        <div class="w-28 text-center">
                            <span
                                :class="item.node.status ? 'label-active' : 'label-canceled'"
                                v-text="item.node.status
                                    ? '@lang('admin::app.catalog.categories.index.datagrid.active')'
                                    : '@lang('admin::app.catalog.categories.index.datagrid.inactive')'"
                            ></span>
                        </div>

                        <!-- Actions -->
                        <div class="flex w-20 items-center justify-end gap-0.5">
                            @if (bouncer()->hasPermission('catalog.categories.edit'))
                                <a :href="editUrl(item.node.id)">
                                    <span class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800"></span>
                                </a>
                            @endif

                            @if (bouncer()->hasPermission('catalog.categories.delete'))
                                <span
                                    class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800"
                                    @click.stop="confirmDelete(item.node)"
                                ></span>
                            @endif
                        </div>
                    </div>
                </template>

                <!-- Empty state -->
                <div
                    v-if="flatItems.length === 0"
                    class="py-12 text-center text-gray-400 dark:text-gray-600"
                >
                    @lang('admin::app.catalog.categories.index.title')
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-category-tree', {
                template: '#v-category-tree-template',

                props: {
                    items: {
                        type: [Array, String],
                        default: () => ([]),
                    },
                },

                data() {
                    return {
                        expanded: {},

                        formattedItems: typeof this.items === 'string'
                            ? JSON.parse(this.items)
                            : this.items,

                        editUrlTemplate: "{{ route('admin.catalog.categories.edit', 'CATEGORY_ID') }}",

                        deleteUrlTemplate: "{{ route('admin.catalog.categories.delete', 'CATEGORY_ID') }}",
                    };
                },

                computed: {
                    flatItems() {
                        const result = [];

                        const traverse = (nodes, level, parentVisible) => {
                            for (const node of nodes) {
                                result.push({ node, level, visible: parentVisible });

                                const expanded = this.expanded[node.id] !== false;

                                traverse(node.children || [], level + 1, parentVisible && expanded);
                            }
                        };

                        traverse(this.formattedItems, 1, true);

                        return result;
                    },
                },

                methods: {
                    toggle(nodeId) {
                        const current = this.expanded[nodeId];

                        this.expanded[nodeId] = current === undefined ? false : ! current;
                    },

                    isExpanded(nodeId) {
                        return this.expanded[nodeId] !== false;
                    },

                    editUrl(id) {
                        return this.editUrlTemplate.replace('CATEGORY_ID', id);
                    },

                    confirmDelete(node) {
                        this.$emitter.emit('open-confirm-modal', {
                            agree: () => {
                                this.$axios.delete(this.deleteUrlTemplate.replace('CATEGORY_ID', node.id))
                                    .then(response => {
                                        this.removeNode(this.formattedItems, node.id);

                                        this.$emitter.emit('add-flash', {
                                            type: 'success',
                                            message: response.data.message,
                                        });
                                    })
                                    .catch(error => {
                                        this.$emitter.emit('add-flash', {
                                            type: 'error',
                                            message: error?.response?.data?.message ?? "@lang('admin::app.catalog.categories.delete-failed')",
                                        });
                                    });
                            },
                        });
                    },

                    removeNode(nodes, id) {
                        const idx = nodes.findIndex(n => n.id === id);

                        if (idx !== -1) {
                            nodes.splice(idx, 1);

                            return true;
                        }

                        for (const node of nodes) {
                            if (this.removeNode(node.children || [], id)) {
                                return true;
                            }
                        }

                        return false;
                    },
                },
            });
        </script>
    @endPushOnce

</x-admin::layouts>

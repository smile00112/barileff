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

    <v-category-tree
        :items='@json($categories)'
        :product-counts='@json($productCounts)'
        products-url-template="{{ route('admin.catalog.products.index') }}?filters[category_name][0]=CATEGORY_ID"
        reorder-url="{{ route('admin.catalog.categories.reorder') }}"
    />

    {!! view_render_event('bagisto.admin.catalog.categories.list.after') !!}

    @pushOnce('scripts')
        {{-- v-category-level: renders one sibling group inside a <draggable> and recurses --}}
        <script type="text/x-template" id="v-category-level-template">
            <draggable
                :list="nodes"
                item-key="id"
                handle=".icon-drag"
                :group="'cat-' + parentId"
                ghost-class="draggable-ghost"
                :animation="150"
                @end="tree.onReorder(nodes)"
            >
                <template #item="{ element: node }">
                    <div>
                        <div class="flex items-center gap-2.5 border-b px-4 py-3 text-gray-600 transition-all hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-950">

                            {{-- Drag handle --}}
                            @if (bouncer()->hasPermission('catalog.categories.edit'))
                                <i class="icon-drag shrink-0 cursor-grab text-xl text-gray-400 transition-all dark:text-gray-500"></i>
                            @else
                                <span class="w-5 shrink-0"></span>
                            @endif

                            {{-- Name with indent, expand toggle, icon --}}
                            <div
                                class="flex flex-1 items-center gap-1.5 overflow-hidden"
                                :style="{ paddingLeft: ((level - 1) * 24) + 'px' }"
                            >
                                <i
                                    :class="[
                                        node.children && node.children.length
                                            ? (tree.isExpanded(node.id) ? 'icon-sort-down' : 'icon-sort-right')
                                            : 'invisible',
                                        'shrink-0 cursor-pointer rounded-md text-xl transition-all hover:bg-gray-100 dark:hover:bg-gray-950'
                                    ]"
                                    @click.stop="tree.toggle(node.id)"
                                ></i>

                                <img
                                    v-if="node.logo_url"
                                    :src="node.logo_url"
                                    :alt="node.name"
                                    class="h-8 w-8 shrink-0 rounded object-cover"
                                />

                                <i
                                    v-else
                                    :class="[
                                        node.children && node.children.length ? 'icon-folder' : 'icon-attribute',
                                        'shrink-0 text-2xl'
                                    ]"
                                ></i>

                                <a
                                    :href="tree.editUrl(node.id)"
                                    class="truncate hover:text-indigo-600 dark:hover:text-indigo-400"
                                    v-text="node.name"
                                ></a>
                            </div>

                            {{-- Products count --}}
                            <div class="w-24 text-center">
                                <a
                                    :href="tree.productsUrl(node.id)"
                                    class="text-sm text-indigo-600 hover:underline dark:text-indigo-400"
                                    v-text="tree.productsCount(node.id)"
                                ></a>
                            </div>

                            {{-- Position --}}
                            <div class="w-20 text-center text-sm text-gray-500 dark:text-gray-400">
                                @{{ node.position }}
                            </div>

                            {{-- Status badge --}}
                            <div class="w-28 text-center">
                                <span
                                    :class="node.status ? 'label-active' : 'label-canceled'"
                                    v-text="node.status
                                        ? '@lang('admin::app.catalog.categories.index.datagrid.active')'
                                        : '@lang('admin::app.catalog.categories.index.datagrid.inactive')'"
                                ></span>
                            </div>

                            {{-- Actions --}}
                            <div class="flex w-20 items-center justify-end gap-0.5">
                                @if (bouncer()->hasPermission('catalog.categories.edit'))
                                    <a :href="tree.editUrl(node.id)">
                                        <span class="icon-edit cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800"></span>
                                    </a>
                                @endif

                                @if (bouncer()->hasPermission('catalog.categories.delete'))
                                    <span
                                        class="icon-delete cursor-pointer rounded-md p-1.5 text-2xl transition-all hover:bg-gray-200 dark:hover:bg-gray-800"
                                        @click.stop="tree.confirmDelete(node)"
                                    ></span>
                                @endif
                            </div>
                        </div>

                        {{-- Recursive children --}}
                        <v-category-level
                            v-if="tree.isExpanded(node.id) && node.children && node.children.length"
                            :nodes="node.children"
                            :parent-id="node.id"
                            :level="level + 1"
                        />
                    </div>
                </template>
            </draggable>
        </script>

        {{-- v-category-tree: root, column headers, provides state to children --}}
        <script type="text/x-template" id="v-category-tree-template">
            <div class="box-shadow mt-3 rounded-xl bg-white dark:bg-gray-900">
                {{-- Column headers --}}
                <div class="flex items-center gap-2.5 border-b px-4 py-3 text-xs font-semibold uppercase text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <div class="w-5 shrink-0"></div>
                    <div class="flex-1">@lang('admin::app.catalog.categories.index.datagrid.name')</div>
                    <div class="w-24 text-center">@lang('admin::app.catalog.categories.index.datagrid.no-of-products')</div>
                    <div class="w-20 text-center">@lang('admin::app.catalog.categories.index.datagrid.position')</div>
                    <div class="w-28 text-center">@lang('admin::app.catalog.categories.index.datagrid.status')</div>
                    <div class="w-20"></div>
                </div>

                <v-category-level
                    v-if="formattedItems.length"
                    :nodes="formattedItems"
                    :parent-id="0"
                    :level="1"
                />

                <div
                    v-if="formattedItems.length === 0"
                    class="py-12 text-center text-gray-400 dark:text-gray-600"
                >
                    @lang('admin::app.catalog.categories.index.title')
                </div>
            </div>
        </script>

        <script type="module">
            app.component('v-category-level', {
                template: '#v-category-level-template',

                inject: ['tree'],

                props: {
                    nodes: {
                        type: Array,
                        required: true,
                    },

                    parentId: {
                        type: [Number, String],
                        default: 0,
                    },

                    level: {
                        type: Number,
                        default: 1,
                    },
                },
            });

            app.component('v-category-tree', {
                template: '#v-category-tree-template',

                props: {
                    items: {
                        type: [Array, String],
                        default: () => ([]),
                    },

                    productCounts: {
                        type: Object,
                        default: () => ({}),
                    },

                    productsUrlTemplate: {
                        type: String,
                        default: '',
                    },

                    reorderUrl: {
                        type: String,
                        default: '',
                    },
                },

                provide() {
                    return {
                        tree: this,
                    };
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

                    productsUrl(id) {
                        return this.productsUrlTemplate.replace('CATEGORY_ID', id);
                    },

                    productsCount(id) {
                        return this.productCounts[id] ?? 0;
                    },

                    onReorder(nodes) {
                        const positions = nodes.map((node, index) => ({
                            id: node.id,
                            position: index + 1,
                        }));

                        this.$axios.post(this.reorderUrl, { positions })
                            .then(response => {
                                this.$emitter.emit('add-flash', {
                                    type: 'success',
                                    message: response.data.message,
                                });
                            })
                            .catch(() => {
                                this.$emitter.emit('add-flash', {
                                    type: 'error',
                                    message: "@lang('admin::app.catalog.categories.reorder-failed')",
                                });
                            });
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

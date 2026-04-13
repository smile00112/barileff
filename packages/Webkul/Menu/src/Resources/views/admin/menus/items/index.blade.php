<x-admin::layouts>
    <x-slot:title>
        @lang('menu::app.admin.items.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                @lang('menu::app.admin.items.title'): {{ $menu->name }}
            </p>
            <p class="text-sm text-gray-500">{{ $menu->location }}</p>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="{{ route('admin.menu.menus.edit', $menu->id) }}" class="secondary-button">
                @lang('menu::app.admin.common.back')
            </a>
        </div>
    </div>

    <div class="mt-4 box-shadow rounded bg-white p-4 dark:bg-gray-900">
        <v-menu-items-manager
            :menu-id="{{ $menu->id }}"
            initial-tree='@json($items)'
            cms-pages='@json($cmsPages)'
            flat-items='@json($flatItems)'
            store-url="{{ route('admin.menu.items.store', $menu->id) }}"
            sort-url="{{ route('admin.menu.items.sort', $menu->id) }}"
            update-url-template="{{ route('admin.menu.items.update', [$menu->id, 0]) }}"
            delete-url-template="{{ route('admin.menu.items.delete', [$menu->id, 0]) }}"
        ></v-menu-items-manager>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-menu-item-node-template">
            <div class="rounded border border-gray-200 p-3 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <span class="icon-drag cursor-grab text-lg text-gray-400"></span>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">@{{ node.title }}</span>
                    <span class="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800">@{{ node.type }}</span>
                    <span class="ml-auto text-xs text-gray-500">#@{{ node.id }}</span>
                    <button class="secondary-button !py-1 !px-2" @click="$emit('edit', node)">Edit</button>
                    <button class="secondary-button !py-1 !px-2" @click="$emit('delete', node)">Delete</button>
                </div>

                <draggable
                    class="mt-3 space-y-2"
                    :list="node.children"
                    item-key="id"
                    group="menu-tree"
                    handle=".icon-drag"
                >
                    <template #item="{ element }">
                        <v-menu-item-node :node="element" @edit="$emit('edit', $event)" @delete="$emit('delete', $event)" />
                    </template>
                </draggable>
            </div>
        </script>

        <script type="text/x-template" id="v-menu-items-manager-template">
            <div>
                <div class="mb-4 grid grid-cols-1 gap-4 rounded border border-gray-200 p-4 dark:border-gray-700 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Title</label>
                        <input v-model="form.title" type="text" class="w-full rounded border px-3 py-2 dark:bg-gray-900" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Parent</label>
                        <select v-model="form.parent_id" class="w-full rounded border px-3 py-2 dark:bg-gray-900">
                            <option :value="null">Root</option>
                            <option v-for="item in flatItems" :key="item.id" :value="item.id">@{{ item.title }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Type</label>
                        <select v-model="form.type" class="w-full rounded border px-3 py-2 dark:bg-gray-900">
                            <option value="custom_url">Custom URL</option>
                            <option value="cms_page">CMS Page</option>
                        </select>
                    </div>

                    <div v-if="form.type === 'custom_url'">
                        <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">URL</label>
                        <input v-model="form.url" type="text" class="w-full rounded border px-3 py-2 dark:bg-gray-900" />
                    </div>

                    <div v-else>
                        <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">CMS Page</label>
                        <select v-model="form.cms_page_id" class="w-full rounded border px-3 py-2 dark:bg-gray-900">
                            <option :value="null">Select page</option>
                            <option v-for="page in cmsPages" :key="page.id" :value="page.id">@{{ page.title }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600 dark:text-gray-300">Target</label>
                        <select v-model="form.target" class="w-full rounded border px-3 py-2 dark:bg-gray-900">
                            <option value="_self">_self</option>
                            <option value="_blank">_blank</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-6">
                        <input v-model="form.is_active" id="menu-item-is-active" type="checkbox" />
                        <label for="menu-item-is-active" class="text-sm">Active</label>
                    </div>
                </div>

                <div class="mb-4 flex gap-2">
                    <button class="primary-button" @click="saveItem">@{{ form.id ? 'Update Item' : 'Add Item' }}</button>
                    <button v-if="form.id" class="secondary-button" @click="resetForm">Cancel</button>
                    <button class="secondary-button" @click="saveSort">Save Sort</button>
                </div>

                <draggable class="space-y-3" :list="tree" item-key="id" group="menu-tree" handle=".icon-drag">
                    <template #item="{ element }">
                        <v-menu-item-node :node="element" @edit="startEdit" @delete="removeItem" />
                    </template>
                </draggable>
            </div>
        </script>

        <script type="module">
            app.component('v-menu-item-node', {
                template: '#v-menu-item-node-template',
                props: ['node'],
            });

            app.component('v-menu-items-manager', {
                template: '#v-menu-items-manager-template',
                props: ['menuId', 'initialTree', 'cmsPages', 'flatItems', 'storeUrl', 'sortUrl', 'updateUrlTemplate', 'deleteUrlTemplate'],
                data() {
                    return {
                        tree: JSON.parse(this.initialTree || '[]'),
                        form: {
                            id: null,
                            title: '',
                            parent_id: null,
                            type: 'custom_url',
                            cms_page_id: null,
                            url: '',
                            target: '_self',
                            is_active: true,
                        },
                    };
                },
                methods: {
                    resetForm() {
                        this.form = { id: null, title: '', parent_id: null, type: 'custom_url', cms_page_id: null, url: '', target: '_self', is_active: true };
                    },
                    startEdit(node) {
                        this.form = {
                            id: node.id,
                            title: node.title,
                            parent_id: node.parent_id,
                            type: node.type,
                            cms_page_id: node.cms_page_id,
                            url: node.url,
                            target: node.target || '_self',
                            is_active: !! node.is_active,
                        };
                    },
                    saveItem() {
                        const payload = {
                            title: this.form.title,
                            parent_id: this.form.parent_id,
                            type: this.form.type,
                            cms_page_id: this.form.type === 'cms_page' ? this.form.cms_page_id : null,
                            url: this.form.type === 'custom_url' ? this.form.url : null,
                            target: this.form.target,
                            is_active: this.form.is_active ? 1 : 0,
                        };

                        if (this.form.id) {
                            const url = this.updateUrlTemplate.replace(/0$/, this.form.id);
                            this.$axios.put(url, payload).then(() => window.location.reload());
                            return;
                        }

                        this.$axios.post(this.storeUrl, payload).then(() => window.location.reload());
                    },
                    removeItem(node) {
                        if (! confirm('Delete item?')) {
                            return;
                        }

                        const url = this.deleteUrlTemplate.replace(/0$/, node.id);
                        this.$axios.delete(url).then(() => window.location.reload());
                    },
                    saveSort() {
                        const toPayload = (nodes) => nodes.map((node, index) => ({
                            id: node.id,
                            sort_order: index,
                            children: toPayload(node.children || []),
                        }));

                        this.$axios.post(this.sortUrl, { tree: toPayload(this.tree) }).then(() => {
                            this.$emitter.emit('add-flash', { type: 'success', message: 'Sort saved' });
                        });
                    },
                },
            });
        </script>
    @endPushOnce
</x-admin::layouts>

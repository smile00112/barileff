@props([
    'filters' => [],
    'desktopColumns' => 4,
    'mobileColumns' => 2,
    'showName' => true,
])

@php
    $inventorySourceId = getCurrentInventorySourceId() ?? 0;
@endphp

<v-categories-nested-grid>
    <x-shop::shimmer.categories.grid
        :count="8"
        :desktop-columns="(int) ($desktopColumns ?? 4)"
        :mobile-columns="(int) ($mobileColumns ?? 2)"
    />
</v-categories-nested-grid>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-categories-nested-grid-template"
    >
        <div v-if="isLoading">
            <x-shop::shimmer.categories.grid
                :count="8"
                :desktop-columns="(int) ($desktopColumns ?? 4)"
                :mobile-columns="(int) ($mobileColumns ?? 2)"
            />
        </div>

        <template v-for="section in sections" :key="section.parent.id">
            <section
                v-if="section.children.length"
                class="container mt-14 max-lg:px-8 max-md:mt-10 max-sm:mt-8"
            >
                <h2 class="mb-6 text-2xl font-semibold tracking-tight text-zinc-900 max-md:text-xl max-sm:text-lg">
                    @{{ section.parent.name }}
                </h2>

                <div
                    class="grid gap-6 max-md:gap-4"
                    :style="gridStyle"
                    :aria-label="section.parent.name"
                >
                    <a
                        v-for="category in section.children"
                        :key="category.id"
                        :href="category.slug"
                        class="group overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                        :aria-label="category.name"
                    >
                        <div class="aspect-[4/3] overflow-hidden bg-zinc-100">
                            <x-shop::media.images.lazy
                                ::src="category.logo?.large_image_url || fallback"
                                ::srcset="`
                                    ${(category.logo?.large_image_url || fallback)} 900w
                                `"
                                sizes="(max-width: 768px) 50vw, 25vw"
                                width="600"
                                height="600"
                                class="w-full object-cover transition duration-300 group-hover:scale-105"
                                ::alt="category.name"
                            />
                        </div>

                        <div
                            class="px-3 py-3"
                            v-if="shouldShowName"
                        >
                            <p
                                class="line-clamp-2 text-center text-base font-medium text-zinc-900 max-md:text-sm"
                                v-text="category.name"
                            >
                            </p>
                        </div>
                    </a>
                </div>
            </section>
        </template>
    </script>

    <script type="module">
        app.component('v-categories-nested-grid', {
            template: '#v-categories-nested-grid-template',

            data() {
                return {
                    isLoading: true,

                    sections: [],

                    windowWidth: window.innerWidth,

                    fallback: "{{ bagisto_asset('images/small-product-placeholder.webp') }}",

                    filterParams: @json($filters ?? []),

                    inventorySourceId: {{ (int) $inventorySourceId }},

                    desktopColumns: {{ (int) ($desktopColumns ?? 4) }},

                    mobileColumns: {{ (int) ($mobileColumns ?? 2) }},

                    showName: {{ (int) ((bool) ($showName ?? true)) }},
                };
            },

            computed: {
                shouldShowName() {
                    return ['1', 1, true, 'true'].includes(this.showName);
                },

                columns() {
                    let desktopColumns = Number.parseInt(this.desktopColumns, 10);
                    let mobileColumns = Number.parseInt(this.mobileColumns, 10);

                    desktopColumns = Number.isNaN(desktopColumns) ? 4 : desktopColumns;
                    mobileColumns = Number.isNaN(mobileColumns) ? 2 : mobileColumns;

                    if (this.windowWidth < 768) {
                        return Math.min(Math.max(mobileColumns, 1), 4);
                    }

                    return Math.min(Math.max(desktopColumns, 1), 6);
                },

                gridStyle() {
                    return {
                        gridTemplateColumns: `repeat(${this.columns}, minmax(0, 1fr))`,
                    };
                },
            },

            mounted() {
                this.loadSections();

                window.addEventListener('resize', this.handleResize);
            },

            beforeUnmount() {
                window.removeEventListener('resize', this.handleResize);
            },

            methods: {
                treeQueryParams() {
                    const params = {
                    };

                    if (this.inventorySourceId) {
                        params.inventory_source_id = this.inventorySourceId;
                    }

                    return params;
                },

                findCategory(categories, categoryId) {
                    for (const category of categories) {
                        if (Number(category.id) === categoryId) {
                            return category;
                        }

                        const match = this.findCategory(category.children ?? [], categoryId);

                        if (match) {
                            return match;
                        }
                    }

                    return null;
                },

                topLevelCategories(categories) {
                    const parentId = Number.parseInt(this.filterParams.parent_id ?? 0, 10);
                    const anchorId = Number.isNaN(parentId) ? 0 : parentId;

                    let parents = categories;

                    if (anchorId > 0) {
                        const anchor = this.findCategory(categories, anchorId);

                        parents = anchor?.children ?? categories.filter(category => Number(category.parent_id ?? 0) === anchorId);
                    } else if (categories.length === 1 && Number(categories[0].parent_id ?? 0) === 0) {
                        parents = categories[0].children ?? [];
                    }

                    if (this.filterParams.name) {
                        const name = String(this.filterParams.name).toLocaleLowerCase();

                        parents = parents.filter(parent => String(parent.name ?? '').toLocaleLowerCase().includes(name));
                    }

                    if (this.filterParams.sort === 'desc') {
                        parents = [...parents].reverse();
                    }

                    const limit = Number.parseInt(this.filterParams.limit ?? 0, 10);

                    if (! Number.isNaN(limit) && limit > 0) {
                        return parents.slice(0, limit);
                    }

                    return parents;
                },

                async loadSections() {
                    this.isLoading = true;
                    this.sections = [];

                    try {
                        const treeRes = await this.$axios.get(
                            '{{ route('shop.api.categories.tree') }}',
                            { params: this.treeQueryParams() },
                        );

                        const parents = this.topLevelCategories(treeRes.data?.data ?? []);

                        for (const parent of parents) {
                            const children = parent.children ?? [];
                            if (children.length > 0) {
                                this.sections.push({ parent, children });

                                if (this.isLoading) {
                                    this.isLoading = false;
                                }
                            }
                        }
                    } catch (error) {
                        console.error(error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                handleResize() {
                    this.windowWidth = window.innerWidth;
                },
            },
        });
    </script>
@endPushOnce

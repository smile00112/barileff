@props([
    'filters' => [],
    'desktopColumns' => 4,
    'mobileColumns' => 2,
    'showName' => true,
])

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
                        <div class="aspect-square overflow-hidden bg-zinc-100">
                            <x-shop::media.images.lazy
                                ::src="category.logo?.medium_image_url || fallback"
                                ::srcset="`
                                    ${(category.logo?.small_image_url || fallback)} 300w,
                                    ${(category.logo?.medium_image_url || fallback)} 600w,
                                    ${(category.logo?.large_image_url || fallback)} 900w
                                `"
                                sizes="(max-width: 768px) 50vw, 25vw"
                                width="600"
                                height="600"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
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
                childQueryParams(parentId) {
                    const params = {
                        ...this.filterParams,
                        parent_id: parentId,
                    };

                    return params;
                },

                async loadSections() {
                    this.isLoading = true;

                    try {
                        const level1Res = await this.$axios.get(
                            '{{ route('shop.api.categories.index') }}',
                            { params: this.filterParams },
                        );

                        const parents = level1Res.data?.data ?? [];

                        const sectionResults = await Promise.all(
                            parents.map(async (parent) => {
                                const res = await this.$axios.get(
                                    '{{ route('shop.api.categories.index') }}',
                                    { params: this.childQueryParams(parent.id) },
                                );

                                return {
                                    parent,
                                    children: res.data?.data ?? [],
                                };
                            }),
                        );

                        this.sections = sectionResults.filter((s) => s.children.length > 0);
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

<v-categories-grid
    src="{{ $src }}"
    title="{{ $title }}"
    navigation-link="{{ $navigationLink ?? '' }}"
    desktop-columns="{{ (int) ($desktopColumns ?? 4) }}"
    mobile-columns="{{ (int) ($mobileColumns ?? 2) }}"
    show-name="{{ (int) ((bool) ($showName ?? true)) }}"
>
    <x-shop::shimmer.categories.grid
        :count="8"
        :desktop-columns="(int) ($desktopColumns ?? 4)"
        :mobile-columns="(int) ($mobileColumns ?? 2)"
    />
</v-categories-grid>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-categories-grid-template"
    >
        <div
            class="container mt-14 max-lg:px-8 max-md:mt-7 max-sm:mt-5"
            v-if="! isLoading && categories?.length"
        >
            <div class="grid gap-6 max-md:gap-4" 111111 :style="gridStyle">
                <a
                    v-for="category in categories"
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
        </div>

        <template v-if="isLoading">
            <x-shop::shimmer.categories.grid
                :count="8"
                :desktop-columns="(int) ($desktopColumns ?? 4)"
                :mobile-columns="(int) ($mobileColumns ?? 2)"
            />
        </template>
    </script>

    <script type="module">
        app.component('v-categories-grid', {
            template: '#v-categories-grid-template',

            props: [
                'src',
                'title',
                'navigationLink',
                'desktopColumns',
                'mobileColumns',
                'showName',
            ],

            data() {
                return {
                    isLoading: true,

                    categories: [],

                    windowWidth: window.innerWidth,

                    fallback: "{{ bagisto_asset('images/small-product-placeholder.webp') }}",
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
                this.getCategories();

                window.addEventListener('resize', this.handleResize);
            },

            beforeUnmount() {
                window.removeEventListener('resize', this.handleResize);
            },

            methods: {
                getCategories() {
                    this.$axios.get(this.src)
                        .then(response => {
                            this.isLoading = false;

                            this.categories = response.data.data;
                        }).catch(error => {
                            console.log(error);
                        });
                },

                handleResize() {
                    this.windowWidth = window.innerWidth;
                },
            },
        });
    </script>
@endPushOnce

@php
    $deliveryZonesActive = (bool) core()->getConfigData('sales.carriers.delivery_zones.active');
    $welcomeModalActive = (bool) core()->getConfigData('sales.carriers.delivery_zones.welcome_modal_active');
    $detectCityByIp = (bool) core()->getConfigData('sales.carriers.delivery_zones.detect_city_by_ip');
    $welcomeModalText = (string) (core()->getConfigData('sales.carriers.delivery_zones.welcome_modal_text') ?? '');
@endphp

@if ($deliveryZonesActive && $welcomeModalActive)
    <div id="v-delivery-welcome-modal-app">
        <v-delivery-welcome-modal
            :detect-by-ip="{{ $detectCityByIp ? 'true' : 'false' }}"
            welcome-text="{{ e($welcomeModalText) }}"
            api-cities-url="{{ route('shop.api.delivery_zones.index') }}"
            api-detect-city-url="{{ route('shop.api.delivery_zones.detect_city') }}"
        ></v-delivery-welcome-modal>
    </div>

    @pushOnce('scripts')
        <script type="text/x-template" id="v-delivery-welcome-modal-template">
            <div>
                <transition name="modal-fade">
                    <div
                        v-if="isOpen"
                        class="fixed inset-0 z-[9999] flex items-end sm:items-center justify-center"
                        role="dialog"
                        aria-modal="true"
                    >
                        <!-- Backdrop -->
                        <div
                            class="absolute inset-0 bg-black/50"
                            @click="dismiss"
                        ></div>

                        <!-- Modal panel -->
                        <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full sm:max-w-md mx-0 sm:mx-4 max-h-[90vh] overflow-y-auto">
                            <!-- Header -->
                            <div class="flex items-start justify-between p-5 pb-3">
                                <h2 class="text-lg font-semibold text-zinc-900 leading-snug flex-1 pr-4">
                                    @{{ welcomeText || '@lang('shop::app.delivery-zones.welcome-modal.title')' }}
                                </h2>

                                <button
                                    type="button"
                                    class="shrink-0 text-zinc-400 hover:text-zinc-600 transition-colors"
                                    @click="dismiss"
                                    aria-label="@lang('shop::app.delivery-zones.welcome-modal.close')"
                                >
                                    <span class="icon-cancel text-2xl"></span>
                                </button>
                            </div>

                            <!-- City list -->
                            <div class="px-5 pb-2" v-if="cities.length > 0">
                                <p class="text-sm text-zinc-500 mb-3">
                                    @lang('shop::app.delivery-zones.welcome-modal.select-city')
                                </p>

                                <div class="flex flex-col gap-1 max-h-56 overflow-y-auto pr-1">
                                    <button
                                        v-for="city in cities"
                                        :key="city.id"
                                        type="button"
                                        class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-colors"
                                        :class="selectedCityId === city.id
                                            ? 'bg-navyBlue text-white font-medium'
                                            : 'bg-zinc-50 hover:bg-zinc-100 text-zinc-800'"
                                        @click="selectedCityId = city.id"
                                    >
                                        @{{ city.name }}
                                        <span
                                            v-if="detectedCityId === city.id && selectedCityId !== city.id"
                                            class="ml-1 text-xs opacity-60"
                                        >
                                            @lang('shop::app.delivery-zones.welcome-modal.detected')
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="p-5 pt-3 flex flex-col gap-2">
                                <button
                                    v-if="cities.length > 0"
                                    type="button"
                                    class="w-full rounded-xl bg-navyBlue py-3 text-sm font-semibold text-white hover:opacity-90 transition-opacity disabled:opacity-50"
                                    :disabled="!selectedCityId"
                                    @click="openDeliverySelector"
                                >
                                    @lang('shop::app.delivery-zones.welcome-modal.specify-address')
                                </button>

                                <button
                                    type="button"
                                    class="w-full rounded-xl border border-zinc-200 py-2.5 text-sm text-zinc-500 hover:bg-zinc-50 transition-colors"
                                    @click="dismiss"
                                >
                                    @lang('shop::app.delivery-zones.welcome-modal.close')
                                </button>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </script>

        <script type="module">
            app.component('v-delivery-welcome-modal', {
                template: '#v-delivery-welcome-modal-template',

                props: {
                    detectByIp: {
                        type: Boolean,
                        default: false,
                    },

                    welcomeText: {
                        type: String,
                        default: '',
                    },

                    apiCitiesUrl: {
                        type: String,
                        required: true,
                    },

                    apiDetectCityUrl: {
                        type: String,
                        required: true,
                    },
                },

                data() {
                    return {
                        isOpen: false,
                        cities: [],
                        selectedCityId: null,
                        detectedCityId: null,
                        storageKey: 'delivery-selector-active-address',
                    };
                },

                mounted() {
                    try {
                        const saved = localStorage.getItem(this.storageKey);

                        if (saved) {
                            const parsed = JSON.parse(saved);

                            if (parsed && parsed.delivery_zone_id && parsed.inventory_source_id) {
                                return;
                            }
                        }
                    } catch (_) {}

                    this.loadCities().then(() => {
                        if (this.cities.length === 0) {
                            return;
                        }

                        if (this.detectByIp) {
                            this.detectCityByIp();
                        } else {
                            this.selectedCityId = this.cities[0]?.id ?? null;
                        }

                        setTimeout(() => {
                            this.isOpen = true;
                        }, 800);
                    });
                },

                methods: {
                    async loadCities() {
                        try {
                            const response = await fetch(this.apiCitiesUrl);

                            if (!response.ok) {
                                return;
                            }

                            const json = await response.json();
                            this.cities = json?.data?.data ?? [];
                        } catch (_) {}
                    },

                    detectCityByIp() {
                        fetch(this.apiDetectCityUrl)
                            .then((r) => (r.ok ? r.json() : null))
                            .then((json) => {
                                const matchedId = json?.data?.matched_city_id ?? null;

                                if (matchedId) {
                                    this.detectedCityId = matchedId;
                                    this.selectedCityId = matchedId;
                                } else {
                                    this.selectedCityId = this.cities[0]?.id ?? null;
                                }
                            })
                            .catch(() => {
                                this.selectedCityId = this.cities[0]?.id ?? null;
                            });
                    },

                    openDeliverySelector() {
                        this.isOpen = false;

                        window.dispatchEvent(
                            new CustomEvent('delivery-zone:open', {
                                detail: { cityId: this.selectedCityId },
                            })
                        );
                    },

                    dismiss() {
                        this.isOpen = false;
                    },
                },
            });
        </script>
    @endPushOnce
@endif

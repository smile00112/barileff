{!! view_render_event('bagisto.shop.checkout.onepage.address.customer.before') !!}

<!-- Customer Address Vue Component -->
<v-checkout-address-customer
    :cart="cart"
    @processing="stepForward"
    @processed="stepProcessed"
>
    <!-- Billing Address Shimmer -->
    <x-shop::shimmer.checkout.onepage.address />
</v-checkout-address-customer>

{!! view_render_event('bagisto.shop.checkout.onepage.address.customer.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-customer-template"
    >
        <template v-if="isLoading">
            <x-shop::shimmer.checkout.onepage.address />
        </template>

        <template v-else>
            <!-- Delivery Zone Summary -->
            <template v-if="cart.delivery_zone">
                <div class="mb-6 rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                    <p class="text-sm font-semibold text-zinc-500">
                        @lang('shop::app.checkout.onepage.address.delivery-address')
                    </p>

                    <p class="mt-1 text-base font-medium text-zinc-900">@{{ deliveryStreetAddress }}</p>

                    <p class="text-sm text-zinc-500">
                        @{{ cart.delivery_zone.name }}@{{ cart.delivery_zone.city_name ? ', ' + cart.delivery_zone.city_name : '' }}
                    </p>
                </div>
            </template>

            <template v-else>
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 text-amber-800">
                    <p class="font-semibold">@lang('shop::app.checkout.onepage.address.no-delivery-zone')</p>

                    <a
                        href="{{ route('shop.home.index') }}"
                        class="mt-1 block text-sm underline"
                    >
                        @lang('shop::app.checkout.onepage.address.select-delivery-zone')
                    </a>
                </div>
            </template>

            <!-- Personal Info Form -->
            <template v-if="cart.delivery_zone">
                <x-shop::form
                    v-slot="{ meta, errors, handleSubmit }"
                    as="div"
                >
                    <form @submit="handleSubmit($event, submitPersonalInfo)">
                        <!-- Billing Address Header -->
                        <div class="mb-4 flex items-center justify-between">
                            <h2 class="text-xl font-medium max-md:text-base max-sm:font-normal">
                                @lang('shop::app.checkout.onepage.address.billing-address')
                            </h2>
                        </div>

                        <v-checkout-address-form
                            control-name="billing"
                            :address="prefillAddress"
                        ></v-checkout-address-form>

                        <!-- Proceed Button -->
                        <div class="mt-4 flex justify-end max-md:my-4">
                            <x-shop::button
                                class="primary-button rounded-2xl px-11 py-3 max-md:rounded-lg max-sm:w-full max-sm:max-w-full max-sm:py-1.5"
                                :title="trans('shop::app.checkout.onepage.address.proceed')"
                                ::loading="isStoring"
                                ::disabled="isStoring"
                            />
                        </div>
                    </form>
                </x-shop::form>
            </template>
        </template>
    </script>

    <script type="module">
        app.component('v-checkout-address-customer', {
            template: '#v-checkout-address-customer-template',

            props: ['cart'],

            emits: ['processing', 'processed'],

            data() {
                return {
                    isLoading: false,

                    isStoring: false,

                    deliverySelection: null,
                }
            },

            computed: {
                prefillAddress() {
                    const b = this.cart.billing_address;

                    return {
                        id: b?.id ?? 0,
                        company_name: b?.company_name ?? '',
                        first_name: b?.first_name ?? '',
                        last_name: b?.last_name ?? '',
                        email: b?.email ?? '',
                        phone: b?.phone ?? '',
                        address: this.deliverySelection?.label ? [this.deliverySelection.label] : (b?.address ?? []),
                        city: this.cart.delivery_zone?.city_name || this.deliverySelection?.city || b?.city || '',
                        country: this.cart.delivery_zone?.city_country || this.deliverySelection?.country || b?.country || '',
                        state: this.cart.delivery_zone?.city_state || this.cart.delivery_zone?.inventory_source_state || this.deliverySelection?.state || b?.state || '',
                        postcode: this.deliverySelection?.postcode || b?.postcode || '',
                    };
                },

                deliveryStreetAddress() {
                    if (this.deliverySelection?.label) {
                        return this.deliverySelection.label;
                    }

                    const addr = this.cart.shipping_address?.address;

                    return Array.isArray(addr) ? addr[0] : (addr ?? '');
                },
            },

            mounted() {
                this.loadDeliverySelection();
            },

            methods: {
                loadDeliverySelection() {
                    try {
                        const raw = localStorage.getItem('delivery-selector-active-address');

                        if (! raw) {
                            return;
                        }

                        const parsed = JSON.parse(raw);

                        if (parsed.expires_at && parsed.expires_at > Date.now()) {
                            this.deliverySelection = parsed;
                        }
                    } catch (e) {}
                },

                submitPersonalInfo(params, { setErrors }) {
                    const zone = this.cart.delivery_zone;
                    const sel = this.deliverySelection;

                    const locationPatch = {
                        address: [sel?.label || this.deliveryStreetAddress || ''],
                        city: zone?.city_name || sel?.city || '',
                        country: zone?.city_country || sel?.country || 'RU',
                        state: zone?.city_state || zone?.inventory_source_state || sel?.state || '',
                        postcode: sel?.postcode || '',
                        additional: {
                            label: sel?.label ?? '',
                            latitude: sel?.latitude ?? null,
                            longitude: sel?.longitude ?? null,
                            zone_id: zone?.id ?? sel?.zone_id ?? null,
                            zone_name: zone?.name ?? sel?.zone_name ?? '',
                            is_private_house: sel?.is_private_house || false,
                            apartment: sel?.apartment || '',
                            entrance: sel?.entrance || '',
                            floor: sel?.floor || '',
                            intercom: sel?.intercom || '',
                        },
                    };

                    const payload = {
                        billing: {
                            ...params.billing,
                            ...locationPatch,
                            use_for_shipping: true,
                        },
                        delivery_zone_id: zone?.id ?? sel?.zone_id ?? undefined,
                        delivery_point_lat: sel?.latitude ?? undefined,
                        delivery_point_lng: sel?.longitude ?? undefined,
                    };

                    payload.billing.email = window.ShopInputMask?.normalizeEmailValue(payload.billing.email) ?? payload.billing.email;
                    payload.billing.phone = window.ShopInputMask?.normalizeRuPhoneValue(payload.billing.phone) ?? payload.billing.phone;

                    this.isStoring = true;

                    this.moveToNextStep();

                    this.$axios.post('{{ route('shop.checkout.onepage.addresses.store') }}', payload)
                        .then((response) => {
                            this.isStoring = false;

                            if (response.data.data.redirect_url) {
                                window.location.href = response.data.data.redirect_url;
                            } else {
                                if (this.cart.have_stockable_items) {
                                    this.$emit('processed', response.data.data.shippingMethods);
                                } else {
                                    this.$emit('processed', response.data.data.payment_methods);
                                }
                            }
                        })
                        .catch(error => {
                            this.isStoring = false;

                            this.$emit('processing', 'address');

                            if (error.response?.status == 422) {
                                setErrors(error.response.data.errors);
                            }
                        });
                },

                moveToNextStep() {
                    if (this.cart.have_stockable_items) {
                        this.$emit('processing', 'shipping');
                    } else {
                        this.$emit('processing', 'payment');
                    }
                },
            },
        });
    </script>
@endPushOnce

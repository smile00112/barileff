{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.before') !!}

<!-- Guest Address Vue Component -->
<v-checkout-address-guest
    :cart="cart"
    @processing="stepForward"
    @processed="stepProcessed"
></v-checkout-address-guest>

{!! view_render_event('bagisto.shop.checkout.onepage.address.guest.after') !!}

@include('shop::checkout.onepage.address.form')

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-guest-template"
    >
        <!-- Delivery Zone Summary -->
        <template v-if="deliverySelection">
            <div class="mb-6 rounded-xl border border-zinc-200 bg-zinc-50 p-5">
                <p class="text-sm font-semibold text-zinc-500">
                    @lang('shop::app.checkout.onepage.address.delivery-address')
                </p>

                <p class="mt-1 text-base font-medium text-zinc-900">@{{ deliverySelection.label }}</p>

                <p class="text-sm text-zinc-500">@{{ deliverySelection.zone_name }}@{{ deliverySelection.city ? ', ' + deliverySelection.city : '' }}</p>
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

        <!-- Address Form -->
        <template v-if="deliverySelection">
            <x-shop::form
                v-slot="{ meta, errors, handleSubmit }"
                as="div"
            >
                <form @submit="handleSubmit($event, addAddress)">
                    <!-- Guest Billing Address -->
                    <div class="mb-4">
                        {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.before') !!}

                        <!-- Billing Address Header -->
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-medium max-md:text-lg max-sm:text-base">
                                @lang('shop::app.checkout.onepage.address.billing-address')
                            </h2>
                        </div>

                        <!-- Billing Address Form (personal info only; location injected from delivery selection) -->
                        <v-checkout-address-form
                            control-name="billing"
                            :address="prefillAddress"
                        ></v-checkout-address-form>

                        <!-- use_for_shipping is always true; hidden input ensures the value is submitted -->
                        <input type="hidden" name="billing.use_for_shipping" value="1" />

                        {!! view_render_event('bagisto.shop.checkout.onepage.address.guest.billing.after') !!}
                    </div>

                    <!-- Proceed Button -->
                    <div class="mt-4 flex justify-end">
                        <x-shop::button
                            class="primary-button rounded-2xl px-11 py-3 max-md:w-full max-md:max-w-full max-md:rounded-lg"
                            :title="trans('shop::app.checkout.onepage.address.proceed')"
                            ::loading="isStoring"
                            ::disabled="isStoring"
                        />
                    </div>
                </form>
            </x-shop::form>
        </template>
    </script>

    <script type="module">
        app.component('v-checkout-address-guest', {
            template: '#v-checkout-address-guest-template',

            props: ['cart'],

            emits: ['processing', 'processed'],

            data() {
                return {
                    deliverySelection: null,

                    isStoring: false,
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
                        vat_id: b?.vat_id ?? '',
                        address: this.deliverySelection?.label ? [this.deliverySelection.label] : (b?.address ?? []),
                        city: this.deliverySelection?.city || b?.city || '',
                        country: this.deliverySelection?.country || b?.country || '',
                        state: this.deliverySelection?.state || b?.state || '',
                        postcode: this.deliverySelection?.postcode || b?.postcode || '',
                    };
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
                    } catch (e) {
                        console.error('[checkout/guest] loadDeliverySelection error:', e);
                    }
                },

                addAddress(params, { setErrors }) {
                    if (! this.deliverySelection) {
                        return;
                    }

                    this.isStoring = true;

                    const sel = this.deliverySelection;

                    const locationPatch = {
                        address: [sel.label || ''],
                        city: sel.city || '',
                        country: sel.country || 'RU',
                        state: sel.state || this.cart.delivery_zone?.city_state || this.cart.delivery_zone?.inventory_source_state || '',
                        postcode: sel.postcode || '',
                        additional: {
                            label: sel.label || '',
                            latitude: sel.latitude ?? null,
                            longitude: sel.longitude ?? null,
                            zone_id: sel.zone_id ?? null,
                            zone_name: sel.zone_name || '',
                            is_private_house: sel.is_private_house || false,
                            apartment: sel.apartment || '',
                            entrance: sel.entrance || '',
                            floor: sel.floor || '',
                            intercom: sel.intercom || '',
                        },
                    };

                    params['billing'] = {
                        ...params['billing'],
                        ...locationPatch,
                        use_for_shipping: true,
                    };

                    params.billing.email = window.ShopInputMask?.normalizeEmailValue(params.billing.email) ?? params.billing.email;
                    params.billing.phone = window.ShopInputMask?.normalizeRuPhoneValue(params.billing.phone) ?? params.billing.phone;

                    if (params['shipping']) {
                        params['shipping'] = { ...params['shipping'], ...locationPatch };
                    }

                    // Pass delivery zone coords as top-level params so Task 1 backend guard can use them
                    params['delivery_zone_id'] = sel.zone_id ?? undefined;
                    params['delivery_point_lat'] = sel.latitude ?? undefined;
                    params['delivery_point_lng'] = sel.longitude ?? undefined;

                    this.moveToNextStep();

                    this.$axios.post('{{ route('shop.checkout.onepage.addresses.store') }}', params)
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

<!-- SEO Meta Content -->
@push('meta')
    <meta name="description" content="@lang('shop::app.checkout.onepage.index.checkout')"/>

    <meta name="keywords" content="@lang('shop::app.checkout.onepage.index.checkout')"/>
@endPush

<x-shop::layouts
    :has-feature="false"
    :has-footer="false"
>
    <!-- Page Title -->
    <x-slot:title>
        @lang('shop::app.checkout.onepage.index.checkout')
    </x-slot>

    <!-- Page Content -->
    <div class="container px-[60px] max-lg:px-8 max-sm:px-4">

        {!! view_render_event('bagisto.shop.checkout.onepage.breadcrumbs.before') !!}

        <!-- Breadcrumbs -->
        @if ((core()->getConfigData('general.general.breadcrumbs.shop')))
            <x-shop::breadcrumbs name="checkout" />
        @endif

        {!! view_render_event('bagisto.shop.checkout.onepage.breadcrumbs.after') !!}

        <!-- Checkout Vue Component -->
        <v-checkout>
            <!-- Shimmer Effect -->
            <x-shop::shimmer.checkout.onepage />
        </v-checkout>
    </div>

    @pushOnce('scripts')
        <script
            type="text/x-template"
            id="v-checkout-template"
        >
            <template v-if="! cart">
                <!-- Shimmer Effect -->
                <x-shop::shimmer.checkout.onepage />
            </template>

            <template v-else>
                <div class="grid grid-cols-[1fr_auto] gap-8 max-lg:grid-cols-[1fr] max-md:gap-5">
                    <!-- Included Checkout Summary Blade File For Mobile view -->
                    <div class="hidden max-md:block">
                        @include('shop::checkout.onepage.summary')
                    </div>

                    <div
                        class="overflow-y-auto max-md:grid max-md:gap-4"
                        id="steps-container"
                    >
                        <!-- Step Progress Indicator -->
                        <div class="mb-8 flex items-start max-md:mb-5">
                            <template v-for="(step, index) in steps" :key="step.key">
                                <button
                                    type="button"
                                    class="flex flex-col items-center focus:outline-none"
                                    :class="isStepCompleted(step.key) ? 'cursor-pointer' : 'cursor-default pointer-events-none'"
                                    @click="goToStep(step.key)"
                                >
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-full border-2 text-sm font-semibold transition-all duration-200"
                                        :class="{
                                            'border-navyBlue bg-navyBlue text-white': isStepCompleted(step.key),
                                            'border-navyBlue bg-white text-navyBlue': isStepActive(step.key),
                                            'border-zinc-200 bg-zinc-100 text-zinc-400': isStepFuture(step.key),
                                        }"
                                    >
                                        <svg v-if="isStepCompleted(step.key)" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>

                                        <span v-else>@{{ index + 1 }}</span>
                                    </div>

                                    <span
                                        class="mt-1.5 whitespace-nowrap text-xs font-medium max-sm:hidden"
                                        :class="{
                                            'text-navyBlue': isStepCompleted(step.key) || isStepActive(step.key),
                                            'text-zinc-400': isStepFuture(step.key),
                                        }"
                                    >@{{ step.label }}</span>
                                </button>

                                <!-- Connector Line -->
                                <div
                                    v-if="index < steps.length - 1"
                                    class="mt-5 h-0.5 flex-1 transition-all duration-200 max-sm:mx-2"
                                    :class="isStepCompleted(step.key) ? 'bg-navyBlue' : 'bg-zinc-200'"
                                ></div>
                            </template>
                        </div>

                        <!-- Back Navigation -->
                        <div class="mb-5" v-if="currentStepIndex > 0">
                            <button
                                type="button"
                                class="flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition-colors hover:text-navyBlue"
                                @click="stepBack"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                                </svg>

                                <span>@{{ steps[currentStepIndex - 1]?.label }}</span>
                            </button>
                        </div>

                        <!-- Included Addresses Blade File -->
                        <template v-if="currentStep === 'address'">
                            @include('shop::checkout.onepage.address')
                        </template>

                        <!-- Included Shipping Methods Blade File -->
                        <template v-if="cart.have_stockable_items && currentStep === 'shipping'">
                            @include('shop::checkout.onepage.shipping')
                        </template>

                        <!-- Included Payment Methods Blade File -->
                        <template v-if="['payment', 'review'].includes(currentStep)">
                            @include('shop::checkout.onepage.payment')
                        </template>

                        <!-- Place Order -->
                        <div
                            class="mt-6 flex justify-end max-md:mt-4"
                            v-if="canPlaceOrder"
                        >
                            <template v-if="cart.payment_method == 'paypal_smart_button'">
                                {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.before') !!}

                                <!-- Paypal Smart Button Vue Component -->
                                <v-paypal-smart-button></v-paypal-smart-button>

                                {!! view_render_event('bagisto.shop.checkout.onepage.summary.paypal_smart_button.after') !!}
                            </template>

                            <template v-else>
                                <x-shop::button
                                    type="button"
                                    class="primary-button w-max rounded-2xl bg-navyBlue px-11 py-3 max-md:w-full max-md:max-w-full max-md:rounded-lg max-sm:py-1.5"
                                    :title="trans('shop::app.checkout.onepage.summary.place-order')"
                                    ::disabled="isPlacingOrder"
                                    ::loading="isPlacingOrder"
                                    @click="placeOrder"
                                />
                            </template>
                        </div>
                    </div>

                    <!-- Included Checkout Summary Blade File For Desktop view -->
                    <div class="sticky top-8 block h-max w-[442px] max-w-full max-lg:w-auto max-lg:max-w-[442px] ltr:pl-8 max-lg:ltr:pl-0 rtl:pr-8 max-lg:rtl:pr-0">
                        <div class="block max-md:hidden">
                            @include('shop::checkout.onepage.summary')
                        </div>
                    </div>
                </div>
            </template>
        </script>

        <script type="module">
            app.component('v-checkout', {
                template: '#v-checkout-template',

                data() {
                    return {
                        cart: null,

                        displayTax: {
                            prices: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_prices') }}",

                            subtotal: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_subtotal') }}",

                            shipping: "{{ core()->getConfigData('sales.taxes.shopping_cart.display_shipping_amount') }}",
                        },

                        isPlacingOrder: false,

                        currentStep: 'address',

                        shippingMethods: null,

                        paymentMethods: null,

                        canPlaceOrder: false,
                    }
                },

                mounted() {
                    this.getCart();
                },

                computed: {
                    steps() {
                        return [
                            { key: 'address', label: "@lang('shop::app.checkout.onepage.address.title')" },
                            { key: 'payment', label: "@lang('shop::app.checkout.onepage.payment.payment-method')" },
                        ];
                    },

                    currentStepIndex() {
                        const key = this.currentStep === 'review' ? 'payment' : this.currentStep;

                        return this.steps.findIndex(s => s.key === key);
                    },
                },

                methods: {
                    getCart() {
                        this.$axios.get("{{ route('shop.checkout.onepage.summary') }}")
                            .then(response => {
                                this.cart = response.data.data;

                                this.scrollToCurrentStep();
                            })
                            .catch(error => {});
                    },

                    stepForward(step) {
                        this.currentStep = step;

                        if (step == 'review') {
                            this.canPlaceOrder = true;

                            return;
                        }

                        this.canPlaceOrder = false;

                        if (this.currentStep == 'shipping') {
                            this.shippingMethods = null;
                        } else if (this.currentStep == 'payment') {
                            this.paymentMethods = null;
                        }
                    },

                    stepProcessed(data) {
                        if (this.currentStep == 'shipping') {
                            this.shippingMethods = data;
                        } else if (this.currentStep == 'payment') {
                            this.paymentMethods = data;
                        }

                        this.getCart();
                    },

                    scrollToCurrentStep() {
                        let container = document.getElementById('steps-container');

                        if (! container) {
                            return;
                        }

                        container.scrollIntoView({
                            behavior: 'smooth',
                            block: 'end'
                        });
                    },

                    isStepCompleted(stepKey) {
                        const stepIndex = this.steps.findIndex(s => s.key === stepKey);

                        return stepIndex < this.currentStepIndex;
                    },

                    isStepActive(stepKey) {
                        const key = this.currentStep === 'review' ? 'payment' : this.currentStep;

                        return stepKey === key;
                    },

                    isStepFuture(stepKey) {
                        return ! this.isStepCompleted(stepKey) && ! this.isStepActive(stepKey);
                    },

                    goToStep(stepKey) {
                        const targetIndex = this.steps.findIndex(s => s.key === stepKey);
                        const shippingIndex = this.steps.findIndex(s => s.key === 'shipping');
                        const paymentIndex = this.steps.findIndex(s => s.key === 'payment');

                        if (targetIndex <= shippingIndex) {
                            this.shippingMethods = null;
                        }

                        if (targetIndex <= paymentIndex) {
                            this.paymentMethods = null;
                        }

                        this.canPlaceOrder = false;
                        this.currentStep = stepKey;
                    },

                    stepBack() {
                        const prevIndex = this.currentStepIndex - 1;

                        if (prevIndex >= 0) {
                            this.goToStep(this.steps[prevIndex].key);
                        }
                    },

                    placeOrder() {
                        this.isPlacingOrder = true;

                        this.$axios.post('{{ route('shop.checkout.onepage.orders.store') }}')
                            .then(response => {
                                if (response.data.data.redirect) {
                                    window.location.href = response.data.data.redirect_url;
                                } else {
                                    window.location.href = '{{ route('shop.checkout.onepage.success') }}';
                                }

                                this.isPlacingOrder = false;
                            })
                            .catch(error => {
                                this.isPlacingOrder = false

                                this.$emitter.emit('add-flash', { type: 'error', message: error.response.data.message });
                            });
                    }
                },
            });
        </script>
    @endPushOnce
</x-shop::layouts>

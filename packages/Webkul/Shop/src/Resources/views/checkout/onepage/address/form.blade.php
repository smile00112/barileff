@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-checkout-address-form-template"
    >
        <div class="mt-2 max-md:mt-3">
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.id'"
                    ::value="address.id"
                />
            </x-shop::form.control-group>

            <!-- Location fields — populated programmatically from delivery zone / localStorage -->
            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="hidden"
                    ::name="controlName + '.address.[0]'"
                    ::value="address.address ? address.address[0] : ''"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="hidden"
                    ::name="controlName + '.city'"
                    ::value="address.city || ''"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="hidden"
                    ::name="controlName + '.country'"
                    ::value="address.country || ''"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="hidden"
                    ::name="controlName + '.state'"
                    ::value="address.state || ''"
                />
            </x-shop::form.control-group>

            <x-shop::form.control-group class="hidden">
                <x-shop::form.control-group.control
                    type="hidden"
                    ::name="controlName + '.postcode'"
                    ::value="address.postcode || ''"
                />
            </x-shop::form.control-group>

            <!-- Full Name (ФИО) -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.full-name')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.full_name'"
                    ::value="address.full_name"
                    rules="required"
                    :label="trans('shop::app.checkout.onepage.address.full-name')"
                    :placeholder="trans('shop::app.checkout.onepage.address.full-name')"
                />

                <x-shop::form.control-group.error ::name="controlName + '.full_name'" />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.full_name.after') !!}

            <!-- Email -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.email')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="email"
                    ::name="controlName + '.email'"
                    ::value="address.email"
                    rules="required|email"
                    data-mask-email="true"
                    :label="trans('shop::app.checkout.onepage.address.email')"
                    placeholder="email@example.com"
                />

                <x-shop::form.control-group.error ::name="controlName + '.email'" />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.email.after') !!}

            <!-- Phone Number -->
            <x-shop::form.control-group>
                <x-shop::form.control-group.label class="required !mt-0">
                    @lang('shop::app.checkout.onepage.address.telephone')
                </x-shop::form.control-group.label>

                <x-shop::form.control-group.control
                    type="text"
                    ::name="controlName + '.phone'"
                    ::value="address.phone"
                    rules="required|phone"
                    data-mask-phone-ru="true"
                    :label="trans('shop::app.checkout.onepage.address.telephone')"
                    placeholder="+7 (___) ___-__-__"
                />

                <x-shop::form.control-group.error ::name="controlName + '.phone'" />
            </x-shop::form.control-group>

            {!! view_render_event('bagisto.shop.checkout.onepage.address.form.phone.after') !!}
        </div>
    </script>

    <script type="module">
        app.component('v-checkout-address-form', {
            template: '#v-checkout-address-form-template',

            props: {
                controlName: {
                    type: String,
                    required: true,
                },

                address: {
                    type: Object,

                    default: () => ({
                        id: 0,
                        company_name: '',
                        full_name: '',
                        email: '',
                        address: [],
                        city: '',
                        country: '',
                        state: '',
                        postcode: '',
                        phone: '',
                    }),
                },
            },
        });
    </script>
@endPushOnce

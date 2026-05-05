@php
    $yandexMapsApiKey = (string) config('services.yandex_maps.api_key', '');
    $yandexMapsScriptUrl = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU';

    if ($yandexMapsApiKey !== '') {
        $yandexMapsScriptUrl .= '&apikey=' . urlencode($yandexMapsApiKey);
    }

    $hasDeliveryMethod = (bool) core()->getConfigData('sales.carriers.flatrate.active')
        || (bool) core()->getConfigData('sales.carriers.delivery_zones.active')
        || (bool) core()->getConfigData('sales.carriers.free.active');

    $currentCustomer = auth()->guard('customer')->user();

    $customerPayload = $currentCustomer
        ? [
            'id' => $currentCustomer->id,
            'first_name' => $currentCustomer->first_name,
            'last_name' => $currentCustomer->last_name,
            'email' => $currentCustomer->email,
            'phone' => $currentCustomer->phone,
        ]
        : null;
@endphp

{!! view_render_event('bagisto.shop.components.layouts.header.delivery_method_selector.before') !!}

<v-delivery-method-selector>
    <div class="flex cursor-pointer items-center gap-2.5 whitespace-nowrap rounded-full border border-zinc-200 bg-white px-5 py-3 text-sm transition-colors hover:border-navyBlue/40">
        <span class="icon-location text-xl text-navyBlue"></span>

        <span class="flex flex-col leading-tight">
            <span class="text-sm font-semibold text-zinc-900">@lang('shop::app.components.layouts.header.delivery-method-selector.title')</span>
            <span class="text-xs text-zinc-500">@lang('shop::app.components.layouts.header.delivery-method-selector.subtitle')</span>
        </span>

        <span class="icon-arrow-right text-lg text-zinc-400 rtl:icon-arrow-left"></span>
    </div>
</v-delivery-method-selector>

{!! view_render_event('bagisto.shop.components.layouts.header.delivery_method_selector.after') !!}

@pushOnce('scripts')
    <style>
        .delivery-left-panel { padding: 30px; }
        .delivery-modal-shell { max-width: 1080px; height: 830px; max-height: 96vh; }

        @media (max-width: 768px) {
            .delivery-modal-shell {
                height: 100vh;
                max-height: none;
            }
        }
    </style>

    <script type="text/x-template" id="v-delivery-method-selector-template">
        <div class="flex items-center">
            <!-- Default state: no address selected yet -->
            <div
                v-if="!confirmedAddress"
                class="flex cursor-pointer items-center gap-2.5 whitespace-nowrap rounded-full border border-zinc-200 bg-white px-5 py-3 text-sm transition-colors hover:border-navyBlue/40"
                @click="openModal"
            >
                <span class="icon-location text-xl text-navyBlue"></span>

                <span class="flex flex-col leading-tight">
                    <span class="text-sm font-semibold text-zinc-900">@lang('shop::app.components.layouts.header.delivery-method-selector.title')</span>
                    <span class="max-w-[320px] truncate text-xs text-zinc-500">@lang('shop::app.components.layouts.header.delivery-method-selector.subtitle')</span>
                </span>

                <span class="icon-arrow-right text-lg text-zinc-400 rtl:icon-arrow-left"></span>
            </div>

            <!-- Confirmed state: address selected -->
            <div
                v-else
                class="flex w-[300px] cursor-pointer items-center justify-between gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-2.5 transition-colors hover:border-navyBlue/40"
                @click="openModal"
            >
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 25 24" fill="none">
                        <path d="M6.6665 20C5.74984 20 4.97067 19.6667 4.329 19C3.68734 18.3333 3.3665 17.5238 3.3665 16.5714H2.2665C1.95484 16.5714 1.69359 16.4619 1.48275 16.2429C1.27192 16.0238 1.1665 15.7524 1.1665 15.4286V13.1429C1.1665 11.8857 1.59734 10.8095 2.459 9.91429C3.32067 9.01905 4.3565 8.57143 5.5665 8.57143H7.7665C8.3715 8.57143 8.88942 8.79524 9.32025 9.24286C9.75109 9.69048 9.9665 10.2286 9.9665 10.8571V14.2857H13.8165L17.6665 9.31429V6.28571H15.4665C15.1548 6.28571 14.8936 6.17619 14.6828 5.95714C14.4719 5.7381 14.3665 5.46667 14.3665 5.14286C14.3665 4.81905 14.4719 4.54762 14.6828 4.32857C14.8936 4.10952 15.1548 4 15.4665 4H17.6665C18.2715 4 18.7894 4.22381 19.2203 4.67143C19.6511 5.11905 19.8665 5.65714 19.8665 6.28571V9.31429C19.8665 9.58095 19.8253 9.83333 19.7428 10.0714C19.6603 10.3095 19.5457 10.5333 19.399 10.7429L15.5765 15.7143C15.3748 15.981 15.1182 16.1905 14.8065 16.3429C14.4948 16.4952 14.174 16.5714 13.844 16.5714H9.9665C9.9665 17.5238 9.64567 18.3333 9.004 19C8.36234 19.6667 7.58317 20 6.6665 20ZM6.6665 17.7143C6.97817 17.7143 7.23942 17.6048 7.45025 17.3857C7.66109 17.1667 7.7665 16.8952 7.7665 16.5714H5.5665C5.5665 16.8952 5.67192 17.1667 5.88275 17.3857C6.09359 17.6048 6.35484 17.7143 6.6665 17.7143ZM8.8665 7.42857H5.5665C5.25484 7.42857 4.99359 7.31905 4.78275 7.1C4.57192 6.88095 4.4665 6.60952 4.4665 6.28571C4.4665 5.9619 4.57192 5.69048 4.78275 5.47143C4.99359 5.25238 5.25484 5.14286 5.5665 5.14286H8.8665C9.17817 5.14286 9.43942 5.25238 9.65025 5.47143C9.86109 5.69048 9.9665 5.9619 9.9665 6.28571C9.9665 6.60952 9.86109 6.88095 9.65025 7.1C9.43942 7.31905 9.17817 7.42857 8.8665 7.42857ZM19.8665 20C18.9498 20 18.1707 19.6667 17.529 19C16.8873 18.3333 16.5665 17.5238 16.5665 16.5714C16.5665 15.619 16.8873 14.8095 17.529 14.1429C18.1707 13.4762 18.9498 13.1429 19.8665 13.1429C20.7832 13.1429 21.5623 13.4762 22.204 14.1429C22.8457 14.8095 23.1665 15.619 23.1665 16.5714C23.1665 17.5238 22.8457 18.3333 22.204 19C21.5623 19.6667 20.7832 20 19.8665 20ZM19.8665 17.7143C20.1782 17.7143 20.4394 17.6048 20.6503 17.3857C20.8611 17.1667 20.9665 16.8952 20.9665 16.5714C20.9665 16.2476 20.8611 15.9762 20.6503 15.7571C20.4394 15.5381 20.1782 15.4286 19.8665 15.4286C19.5548 15.4286 19.2936 15.5381 19.0828 15.7571C18.8719 15.9762 18.7665 16.2476 18.7665 16.5714C18.7665 16.8952 18.8719 17.1667 19.0828 17.3857C19.2936 17.6048 19.5548 17.7143 19.8665 17.7143ZM3.3665 14.2857H7.7665V10.8571H5.5665C4.9615 10.8571 4.44359 11.081 4.01275 11.5286C3.58192 11.9762 3.3665 12.5143 3.3665 13.1429V14.2857Z" fill="currentColor"/>
                    </svg>
                </span>

                <span class="flex min-w-0 flex-col leading-snug">
                    <span class="text-[11px] font-medium text-zinc-400">@lang('shop::app.components.layouts.header.delivery-method-selector.delivery')</span>
                    <span class="max-w-[380px] truncate text-sm font-semibold text-zinc-900">@{{ confirmedAddress }}</span>
                </span>

                <svg class="ml-1 h-4 w-4 flex-shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                    <path d="M8.6998 17.2998C8.51647 17.1165 8.4248 16.8831 8.4248 16.5998C8.4248 16.3165 8.51647 16.0831 8.6998 15.8998L12.5998 11.9998L8.6998 8.0998C8.51647 7.91647 8.4248 7.68314 8.4248 7.3998C8.4248 7.11647 8.51647 6.88314 8.6998 6.6998C8.88314 6.51647 9.11647 6.4248 9.3998 6.4248C9.68314 6.4248 9.91647 6.51647 10.0998 6.6998L14.6998 11.2998C14.7998 11.3998 14.8706 11.5081 14.9123 11.6248C14.954 11.7415 14.9748 11.8665 14.9748 11.9998C14.9748 12.1331 14.954 12.2581 14.9123 12.3748C14.8706 12.4915 14.7998 12.5998 14.6998 12.6998L10.0998 17.2998C9.91647 17.4831 9.68314 17.5748 9.3998 17.5748C9.11647 17.5748 8.88314 17.4831 8.6998 17.2998Z" fill="currentColor"/>
                </svg>
            </div>

            <transition
                enter-active-class="duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="isOpen"
                    class="fixed inset-0 z-[999]"
                    style="background-color: #0000004d;"
                    @click="closeModal"
                ></div>
            </transition>

            <transition
                enter-active-class="duration-300 ease-out"
                enter-from-class="translate-y-6 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-6 opacity-0"
            >
                <div
                    v-show="isOpen"
                    class="fixed inset-0 z-[1000] flex items-center justify-center p-4 max-md:items-stretch max-md:p-0"
                >
                    <div
                        class="delivery-modal-shell relative flex w-full overflow-hidden rounded-2xl bg-white shadow-2xl max-md:h-screen max-md:flex-col-reverse max-md:rounded-none"
                        @click.stop
                    >
                        <!-- Left panel: close + tabs + content -->
                        <div class="delivery-left-panel flex w-[345px] flex-shrink-0 flex-col overflow-hidden border-r border-zinc-100 max-md:w-full max-md:border-r-0 max-md:border-t">

                            <!-- Header: close button + tab switcher -->
                            <div class="flex-shrink-0 pb-5">
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="appearance-none flex flex-shrink-0 items-center justify-center overflow-hidden border border-zinc-200 bg-white p-0 shadow-sm transition-colors hover:bg-zinc-100"
                                        style="display: inline-flex; width: 42px !important; height: 42px !important; min-width: 42px !important; min-height: 42px !important; max-width: 42px !important; max-height: 42px !important; border-radius: 9999px !important; clip-path: circle(50% at 50% 50%); -webkit-appearance: none; appearance: none;"
                                        @click="closeModal"
                                    >
                                        <span class="icon-cancel text-xl text-zinc-600"></span>
                                    </button>

                                    <div class="min-w-0 flex-1 rounded-[24px] bg-zinc-50 p-1.5">
                                        <div class="grid gap-1.5" :class="showPickupTab ? 'grid-cols-2' : 'grid-cols-1'">
                                            <button
                                                v-if="hasDeliveryMethod"
                                                type="button"
                                                class="flex min-h-[44px] items-center justify-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold transition-all"
                                                :class="activeTab === 'delivery' ? 'bg-navyBlue text-white shadow-sm' : 'bg-white text-zinc-600 hover:bg-zinc-200'"
                                                @click="switchTab('delivery')"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                                                @lang('shop::app.components.layouts.header.delivery-method-selector.delivery')
                                            </button>

                                            {{-- Pickup tab: temporarily hidden, do not remove --}}
                                            <button
                                                v-show="showPickupTab"
                                                type="button"
                                                class="flex min-h-[44px] items-center justify-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold transition-all"
                                                :class="activeTab === 'pickup' ? 'bg-navyBlue text-white shadow-sm' : 'bg-white text-zinc-600 hover:bg-zinc-200'"
                                                @click="switchTab('pickup')"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                @lang('shop::app.components.layouts.header.delivery-method-selector.pickup')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Delivery tab content -->
                            <div v-show="activeTab === 'delivery'" class="flex flex-1 flex-col overflow-hidden">
                                <div class="flex-1 overflow-y-auto" @click="cityDropdownOpen = false">

                                    <div v-if="cities.length > 1" class="relative mt-1" @click.stop>
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-900 outline-none transition-colors hover:border-zinc-300 focus:border-navyBlue"
                                            :class="cityDropdownOpen ? 'border-navyBlue' : ''"
                                            @click="cityDropdownOpen = !cityDropdownOpen"
                                        >
                                            <span class="font-medium">@{{ cities.find(c => c.id == selectedCityId)?.name ?? '...' }}</span>
                                            <svg class="h-4 w-4 flex-shrink-0 text-zinc-400 transition-transform" :class="cityDropdownOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                        </button>

                                        <div
                                            v-show="cityDropdownOpen"
                                            class="absolute z-2 left-0 right-0 top-[calc(100%+6px)] z-30 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl"
                                        >
                                            <div class="py-1.5">
                                                <button
                                                    v-for="city in cities"
                                                    :key="city.id"
                                                    type="button"
                                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-sm transition-colors"
                                                    :class="selectedCityId == city.id ? 'bg-navyBlue/6 font-semibold text-navyBlue' : 'text-zinc-800 hover:bg-zinc-50'"
                                                    @click="changeCity(city.id); cityDropdownOpen = false"
                                                >
                                                    <svg v-if="selectedCityId == city.id" class="h-3.5 w-3.5 flex-shrink-0 text-navyBlue" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                    <span v-else class="w-3.5 flex-shrink-0"></span>
                                                    @{{ city.name }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="relative mt-5">
                                        <input
                                            v-model="deliveryQuery"
                                            type="text"
                                            class="w-full rounded-2xl border border-zinc-200 px-4 py-3.5 text-sm text-zinc-900 outline-none transition-colors placeholder:text-zinc-400 focus:border-navyBlue"
                                            :placeholder="deliveryPlaceholder"
                                            autocomplete="off"
                                            @input="handleDeliveryInput"
                                            @focus="handleDeliveryFocus"
                                            @blur="handleDeliveryBlur"
                                            @keydown.down.prevent="moveSuggestion(1)"
                                            @keydown.up.prevent="moveSuggestion(-1)"
                                            @keydown.enter.prevent="applyHighlightedSuggestion"
                                            @keydown.esc.prevent="hideSuggestions"
                                        >

                                        <div v-if="isSearchingSuggestions" class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2">
                                            <div class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-200 border-t-navyBlue"></div>
                                        </div>

                                        <div
                                            v-if="showSuggestions"
                                            class="absolute z-2 left-0 right-0 top-[calc(100%+8px)] z-20 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-lg"
                                        >
                                            <div class="max-h-[320px] overflow-y-auto py-2">
                                                <button
                                                    v-for="(suggestion, index) in visibleSuggestions"
                                                    :key="suggestion.key"
                                                    type="button"
                                                    class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors"
                                                    :class="highlightedSuggestionIndex === index ? 'bg-navyBlue/5' : 'hover:bg-zinc-50'"
                                                    @mousedown.prevent="selectSuggestion(suggestion)"
                                                >
                                                    <span class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-navyBlue/10">
                                                        <svg class="h-3.5 w-3.5 text-navyBlue" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    </span>

                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-sm font-medium text-zinc-900">@{{ suggestion.primary }}</span>
                                                        <span v-if="suggestion.secondary" class="mt-0.5 block text-xs text-zinc-500">@{{ suggestion.secondary }}</span>
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-5 flex items-center gap-3">
                                        <input
                                            v-model="isPrivateHouse"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-zinc-300 text-navyBlue focus:ring-navyBlue"
                                        >

                                        <span class="text-sm font-medium text-zinc-800">
                                            @lang('shop::app.components.layouts.header.delivery-method-selector.private-house')
                                        </span>
                                    </div>

                                    <div class="mt-5">
                                        <div class="grid grid-cols-2 gap-4 max-sm:grid-cols-1">
                                            <input v-model="apartment" type="text" class="rounded-2xl border border-zinc-200 px-4 py-3 text-sm outline-none transition-colors focus:border-navyBlue" :placeholder="translations.apartment">
                                            <input v-model="entrance" type="text" class="rounded-2xl border border-zinc-200 px-4 py-3 text-sm outline-none transition-colors focus:border-navyBlue" :placeholder="translations.entrance">
                                            <input v-model="floor" type="text" class="rounded-2xl border border-zinc-200 px-4 py-3 text-sm outline-none transition-colors focus:border-navyBlue" :placeholder="translations.floor">
                                            <input v-model="intercom" type="text" class="rounded-2xl border border-zinc-200 px-4 py-3 text-sm outline-none transition-colors focus:border-navyBlue" :placeholder="translations.intercom">
                                        </div>
                                    </div>

                                    <div v-if="isCustomer" class="mt-5">
                                        <div class="flex items-center justify-between gap-3">
                                            <h4 class="text-sm font-semibold text-zinc-900">
                                                @lang('shop::app.components.layouts.header.delivery-method-selector.saved-addresses')
                                            </h4>

                                            <div v-if="isLoadingAddresses" class="h-4 w-4 animate-spin rounded-full border-2 border-zinc-200 border-t-navyBlue"></div>
                                        </div>

                                        <div v-if="savedAddresses.length" class="mt-3 space-y-2">
                                            <div
                                                v-for="address in savedAddresses"
                                                :key="address.id"
                                                class="rounded-2xl border p-4 transition-all"
                                                :class="activeCustomerAddressId === address.id ? 'border-navyBlue bg-navyBlue/5' : 'border-zinc-200 bg-white'"
                                            >
                                                <button type="button" class="w-full text-left" @click="selectSavedAddress(address)">
                                                    <p class="text-sm font-semibold text-zinc-900">@{{ address.label }}</p>
                                                    <p v-if="address.zoneName" class="mt-1 text-xs text-zinc-500">@{{ address.zoneName }}</p>
                                                </button>

                                                <div class="mt-3 flex items-center gap-2">
                                                    <button type="button" class="rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700 transition-colors hover:bg-zinc-200" @click="startEditAddress(address)">
                                                        @lang('shop::app.components.layouts.header.delivery-method-selector.edit')
                                                    </button>

                                                    <button type="button" class="rounded-full bg-red-50 px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-100" @click="deleteSavedAddress(address)">
                                                        @lang('shop::app.components.layouts.header.delivery-method-selector.delete')
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-else-if="!isLoadingAddresses" class="mt-3 rounded-2xl border border-dashed border-zinc-200 px-4 py-3 text-sm text-zinc-500">
                                            @lang('shop::app.components.layouts.header.delivery-method-selector.no-saved-addresses')
                                        </div>
                                    </div>

                                    <div v-if="selectedZone && deliverySummary && !deliverySummary.outsideZone" class="mt-5">
                                        <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4">
                                            <div class="flex items-center gap-2 text-sm font-semibold text-zinc-900">
                                                <span>@lang('shop::app.components.layouts.header.delivery-method-selector.delivery')</span>
                                                <span class="inline-block h-[4px] w-[4px] rounded-full bg-zinc-500/40"></span>
                                                <span>@{{ deliverySummary.timeLabel }}</span>
                                            </div>

                                            <div class="mt-2 flex items-center gap-2 text-sm text-zinc-600">
                                                <span>@{{ deliverySummary.priceLabel }}</span>
                                                <template v-if="deliverySummary.freeFromLabel">
                                                    <span class="inline-block h-[4px] w-[4px] rounded-full bg-zinc-500/40"></span>
                                                    <span>@{{ deliverySummary.freeFromLabel }}</span>
                                                </template>
                                            </div>

                                        </div>
                                    </div>

                                    <div v-if="addressOutsideZone" class="mt-5">
                                        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-4">
                                            <div class="flex items-center gap-2 text-sm font-semibold text-red-700">
                                                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                                <span>Ваш адрес вне зоны доставки</span>
                                            </div>
                                            <div class="mt-1 text-sm text-red-600">Невозможно определить стоимость доставки · Выберите корректный адрес</div>
                                        </div>
                                    </div>

                                    <div v-if="message" class="mt-5">
                                        <div
                                            class="rounded-2xl border px-4 py-3 text-sm"
                                            :class="messageIsError ? 'border-red-200 bg-red-50 text-red-700' : 'border-green-200 bg-green-50 text-green-700'"
                                        >
                                            @{{ message }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-shrink-0 border-t border-zinc-100 mx-[-30px] px-[30px] pt-5 pb-[30px]">
                                    <button
                                        type="button"
                                        class="w-full rounded-2xl py-3 text-center text-sm font-bold transition-all"
                                        :class="canConfirmDelivery ? 'bg-navyBlue text-white hover:bg-navyBlue/90 shadow-sm' : 'cursor-not-allowed bg-zinc-100 text-zinc-400'"
                                        :disabled="!canConfirmDelivery || isSubmittingDelivery"
                                        @click="confirmSelection"
                                    >
                                        <span v-if="isSubmittingDelivery">@lang('shop::app.components.layouts.header.delivery-method-selector.loading')</span>
                                        <span v-else>@lang('shop::app.components.layouts.header.delivery-method-selector.confirm-address')</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Pickup tab content -->
                            <div v-show="activeTab === 'pickup'" class="flex flex-1 flex-col overflow-y-auto">
                                <div class="px-5 pt-2">
                                    <h3 class="text-lg font-bold text-zinc-900">
                                        @lang('shop::app.components.layouts.header.delivery-method-selector.pickup-title')
                                    </h3>

                                    <p class="mt-1 text-[13px] text-zinc-500">
                                        @lang('shop::app.components.layouts.header.delivery-method-selector.pickup-hint')
                                    </p>
                                </div>

                                <div v-if="isLoadingPickup" class="flex items-center justify-center py-10">
                                    <div class="h-6 w-6 animate-spin rounded-full border-2 border-zinc-200 border-t-navyBlue"></div>
                                </div>

                                <div v-if="!isLoadingPickup && pickupPoints.length" class="flex flex-col gap-2 px-5 pt-4 pb-5">
                                    <div
                                        v-for="point in pickupPoints"
                                        :key="point.id"
                                        class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition-all"
                                        :class="selectedPickupPoint && selectedPickupPoint.id === point.id ? 'border-navyBlue bg-navyBlue/5' : 'border-zinc-200 hover:border-zinc-300'"
                                        @click="selectPickupPoint(point)"
                                    >
                                        <div class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-navyBlue/10">
                                            <svg class="h-3.5 w-3.5 text-navyBlue" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-zinc-900">@{{ point.name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-zinc-500">@{{ point.street }}<template v-if="point.city">, @{{ point.city }}</template></p>
                                            <p v-if="point.contact_number" class="mt-0.5 text-xs text-zinc-400">@{{ point.contact_number }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="!isLoadingPickup && !pickupPoints.length" class="flex flex-col items-center justify-center py-12 text-zinc-400">
                                    <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <p class="mt-2 text-sm">@lang('shop::app.components.layouts.header.delivery-method-selector.no-pickup-points')</p>
                                </div>
                            </div>
                        </div>

                        <!-- Right panel: map (always visible) -->
                        <div class="delivery-selector-map relative flex flex-1 flex-col p-1.5 max-md:h-[320px]">
                            <div class="relative flex-1 overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100">
                                <div ref="mapContainer" class="absolute inset-0"></div>

                                <div class="pointer-events-none absolute inset-0 z-[2]">
                                    <div
                                        v-if="!mapReady"
                                        class="absolute inset-0 flex items-center justify-center bg-white/35 backdrop-blur-[1px]"
                                    >
                                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-white/92 shadow-[0_10px_30px_rgba(15,23,42,0.16)]">
                                            <div style="width: 38px; height: 38px; border: 4px solid rgba(15, 23, 42, 0.16); border-top-color: #1e3a8a; border-right-color: #1e3a8a; border-radius: 9999px; animation: spin 0.8s linear infinite;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="mapLoadError" class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                @{{ mapLoadError }}
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </script>

    <script type="module">
        const _ymapsRaw = { map: null, collection: null, zones: {}, marker: null };

        // Shared promise so the script is injected only once across multiple openModal() calls
        let _ymapsScriptPromise = null;

        app.component('v-delivery-method-selector', {
            template: '#v-delivery-method-selector-template',

            data() {
                return {
                    isOpen: false,
                    activeTab: 'delivery',
                    hasDeliveryMethod: @json($hasDeliveryMethod),
                    showPickupTab: false,
                    isCustomer: @json($currentCustomer !== null),
                    customerProfile: @json($customerPayload),
                    isLoading: false,
                    isLoadingPickup: false,
                    isLoadingAddresses: false,
                    isSearchingSuggestions: false,
                    isSubmittingDelivery: false,
                    cities: [],
                    allZones: [],
                    pickupPoints: [],
                    savedAddresses: [],
                    cartSummary: null,
                    selectedZone: null,
                    resolvedZone: null,
                    selectedPickupPoint: null,
                    confirmedAddress: '',
                    deliveryQuery: '',
                    suggestions: [],
                    highlightedSuggestionIndex: -1,
                    isPrivateHouse: false,
                    apartment: '',
                    entrance: '',
                    floor: '',
                    intercom: '',
                    activeCustomerAddressId: null,
                    editingCustomerAddressId: null,
                    message: '',
                    messageIsError: false,
                    mapReady: false,
                    mapPreloaded: false,
                    mapLoadError: '',
                    activeReverseGeocodeRequestId: 0,
                    suggestionTimer: null,
                    currentAddressCoords: null,
                    addressOutsideZone: false,
                    cityDropdownOpen: false,
                    selectedCityId: null,
                    ymapsScriptUrl: @json($yandexMapsScriptUrl),
                    zonesApiUrl: @json(route('shop.api.delivery_zones.index')),
                    selectApiUrl: @json(route('shop.api.delivery_zones.select')),
                    pickupApiUrl: @json(route('shop.api.delivery_zones.pickup_points')),
                    cartSummaryApiUrl: @json(route('shop.checkout.onepage.summary')),
                    customerAddressesApiUrl: @json(route('shop.api.customers.account.addresses.index')),
                    customerAddressUpdateUrl: @json(route('shop.api.customers.account.addresses.update', ['id' => 0])),
                    customerAddressDeleteUrl: @json(route('shop.api.customers.account.addresses.delete', ['id' => 0])),
                    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || @json(csrf_token()),
                    guestAddressStorageKey: 'delivery-selector-active-address',
                    guestCityStorageKey: 'delivery-selector-city-id',
                    guestAddressTtlMs: 7 * 24 * 60 * 60 * 1000,
                    translations: {
                        apartment: @json(__('shop::app.components.layouts.header.delivery-method-selector.apartment')),
                        entrance: @json(__('shop::app.components.layouts.header.delivery-method-selector.entrance')),
                        floor: @json(__('shop::app.components.layouts.header.delivery-method-selector.floor')),
                        intercom: @json(__('shop::app.components.layouts.header.delivery-method-selector.intercom')),
                        deleteConfirm: @json(__('shop::app.components.layouts.header.delivery-method-selector.delete-confirm')),
                    },
                };
            },

            computed: {
                activeCity() {
                    if (this.selectedCityId) {
                        return this.cities.find((city) => city.id === this.selectedCityId) || this.cities[0] || null;
                    }

                    if (this.selectedZone) {
                        return this.cities.find((city) => city.id === this.selectedZone.city_id) || this.cities[0] || null;
                    }

                    return this.cities[0] || null;
                },

                activeCityZones() {
                    if (!this.activeCity) {
                        return this.allZones;
                    }

                    return this.allZones.filter((zone) => zone.city_id === this.activeCity.id);
                },

                deliveryPlaceholder() {
                    return 'Адрес';
                },

                showSuggestions() {
                    return this.suggestions.length > 0 && this.activeTab === 'delivery';
                },

                visibleSuggestions() {
                    return this.suggestions.slice(0, 10);
                },

                deliverySummary() {
                    if (!this.selectedZone) {
                        if (this.addressOutsideZone) {
                            return {
                                outsideZone: true,
                                timeLabel: '',
                                priceLabel: '',
                                freeFromLabel: '',
                                belowMinimum: false,
                                belowMinimumLabel: '',
                            };
                        }

                        return null;
                    }

                    const rates = Array.isArray(this.selectedZone.rates) ? [...this.selectedZone.rates] : [];
                    const subtotal = Number(this.cartSummary?.sub_total ?? 0);

                    rates.sort((left, right) => Number(right.min_order_total) - Number(left.min_order_total));

                    const matchedRate = rates.find((rate) => subtotal >= Number(rate.min_order_total)) || null;
                    const fallbackRate = rates
                        .sort((left, right) => Number(left.min_order_total) - Number(right.min_order_total))[0] || null;
                    const displayRate = matchedRate || fallbackRate;
                    const freeRate = rates
                        .filter((rate) => Number(rate.price) === 0)
                        .sort((left, right) => Number(left.min_order_total) - Number(right.min_order_total))[0] || null;

                    const minOrderTotal = rates.length
                        ? Math.min(...rates.map((rate) => Number(rate.min_order_total)))
                        : null;
                    const belowMinimum = minOrderTotal !== null && subtotal < minOrderTotal;
                    const amountMissing = belowMinimum ? minOrderTotal - subtotal : 0;

                    return {
                        timeLabel: this.selectedZone.delivery_time_minutes
                            ? `${this.selectedZone.delivery_time_minutes} ${@json(__('shop::app.components.layouts.header.delivery-method-selector.minutes'))}`
                            : @json(__('shop::app.components.layouts.header.delivery-method-selector.delivery-time-unknown')),
                        priceLabel: displayRate
                            ? (Number(displayRate.price) === 0
                                    ? @json(__('shop::app.components.layouts.header.delivery-method-selector.free'))
                                    : this.formatPrice(Number(displayRate.price)))
                            : @json(__('shop::app.components.layouts.header.delivery-method-selector.price-unavailable')),
                        freeFromLabel: freeRate && !belowMinimum
                            ? `${@json(__('shop::app.components.layouts.header.delivery-method-selector.free-from'))} ${this.formatPrice(Number(freeRate.min_order_total))}`
                            : '',
                        belowMinimum,
                        belowMinimumLabel: belowMinimum
                            ? `${@json(__('shop::app.components.layouts.header.delivery-method-selector.add-more'))} ${this.formatPrice(amountMissing)}`
                            : '',
                    };
                },

                canConfirmDelivery() {
                    if (this.activeTab !== 'delivery') {
                        return false;
                    }

                    if (!this.deliveryQuery.trim() || !this.selectedZone || this.addressOutsideZone) {
                        return false;
                    }

                    return true;
                },
            },

            mounted() {
                this._citiesPromise = this.loadCities().then(() => {
                    if (this.cities.length) {
                        const persistedCityId = this.readPersistedCityId();

                        if (persistedCityId) {
                            this.selectedCityId = persistedCityId;
                        } else {
                            this.selectedCityId = this.cities[0].id;
                        }
                    }

                    this.restorePersistedAddress();
                });

                if (this.isCustomer) {
                    this._addressesPromise = this.loadCustomerAddresses();
                }

                window.addEventListener('delivery-zone:open', (e) => {
                    if (e.detail?.cityId) {
                        this.selectedCityId = e.detail.cityId;
                        this.persistCitySelection(this.selectedCityId);
                    }

                    this.openModal();
                });
            },

            methods: {

                loadYmapsScript() {
                    if (_ymapsScriptPromise) {
                        return _ymapsScriptPromise;
                    }

                    if (typeof ymaps !== 'undefined') {
                        _ymapsScriptPromise = Promise.resolve();
                        return _ymapsScriptPromise;
                    }

                    _ymapsScriptPromise = new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = this.ymapsScriptUrl;
                        script.async = true;
                        script.onload = () => resolve();
                        script.onerror = () => reject(new Error('Yandex Maps script failed to load'));
                        document.head.appendChild(script);
                    });

                    return _ymapsScriptPromise;
                },

                _preloadMap() {
                    if (this.mapPreloaded || typeof ymaps === 'undefined') {
                        return;
                    }

                    const container = document.createElement('div');
                    container.style.cssText = 'position:absolute;visibility:hidden;pointer-events:none;width:1px;height:1px;overflow:hidden;';
                    document.body.appendChild(container);

                    ymaps.ready(async () => {
                        try {
                            const center = this.activeCity?.center_lat != null && this.activeCity?.center_lng != null
                                ? [this.activeCity.center_lat, this.activeCity.center_lng]
                                : [55.7558, 37.6173];

                            const preloadMap = new ymaps.Map(container, { center, zoom: 11, controls: [] });

                            await new Promise((resolve) => {
                                const t = setTimeout(resolve, 2000);
                                preloadMap.events.once('boundschange', () => { clearTimeout(t); resolve(); });
                            });

                            preloadMap.destroy();
                            document.body.removeChild(container);
                            this.mapPreloaded = true;
                            console.log('[DS] map preloaded');
                        } catch (e) {
                            console.warn('[DS] preload error:', e?.message);

                            try { document.body.removeChild(container); } catch (_) {}
                        }
                    });
                },

                async openModal() {
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                    this.message = '';
                    this.mapLoadError = '';

                    if (!this.cities.length) {
                        await (this._citiesPromise || this.loadCities());
                    }

                    await this.loadCartSummary();

                    if (this.isCustomer && !this.savedAddresses.length) {
                        await (this._addressesPromise || this.loadCustomerAddresses());
                    }

                    this.restorePersistedAddress();

                    if (this.activeTab === 'pickup' && !this.pickupPoints.length) {
                        this.loadPickupPoints();
                    }

                    // Load Yandex Maps API on first modal open, then preload tiles + init map
                    try {
                        await this.loadYmapsScript();
                        this._preloadMap();
                    } catch (e) {
                        console.warn('[DS] Yandex Maps script load error:', e?.message);
                    }

                    this.$nextTick(() => {
                        setTimeout(() => this.initMap(), this.mapPreloaded ? 50 : 1500);
                    });
                },

                closeModal() {
                    this.isOpen = false;
                    this.hideSuggestions();
                    document.body.style.overflow = '';

                    if (_ymapsRaw.map) {
                        _ymapsRaw.map.destroy();
                        _ymapsRaw.map = null;
                        _ymapsRaw.collection = null;
                        _ymapsRaw.zones = {};
                        _ymapsRaw.marker = null;
                        this.mapReady = false;
                        this.mapLoadError = '';
                    }
                },

                async switchTab(tab) {
                    this.activeTab = tab;
                    this.message = '';
                    this.hideSuggestions();

                    await this.$nextTick();

                    if (tab === 'pickup' && !this.pickupPoints.length) {
                        this.loadPickupPoints();
                    }

                    if (tab === 'delivery') {
                        this.reinitializeMap();
                    }
                },

                async loadCities() {
                    this.isLoading = true;

                    try {
                        const response = await fetch(this.zonesApiUrl, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json();

                        let raw = payload.data;

                        if (raw && !Array.isArray(raw) && Array.isArray(raw.data)) {
                            raw = raw.data;
                        }

                        this.cities = Array.isArray(raw) ? raw : [];
                        this.allZones = this.cities.flatMap((city) => city.zones || []);
                        console.log('[DS] cities loaded:', this.cities.length, '| zones:', this.allZones.length);
                    } catch (error) {
                        console.error('[DS] loadCities error:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async loadCartSummary() {
                    try {
                        const response = await fetch(this.cartSummaryApiUrl, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json();
                        this.cartSummary = payload.data || null;
                    } catch (error) {
                        console.error('[DS] loadCartSummary error:', error);
                    }
                },

                async loadCustomerAddresses() {
                    this.isLoadingAddresses = true;

                    try {
                        const response = await fetch(this.customerAddressesApiUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const payload = await response.json();
                        this.savedAddresses = Array.isArray(payload.data)
                            ? payload.data.map((address) => this.normalizeCustomerAddress(address))
                            : [];
                    } catch (error) {
                        console.error('[DS] loadCustomerAddresses error:', error);
                    } finally {
                        this.isLoadingAddresses = false;
                    }
                },

                async loadPickupPoints() {
                    this.isLoadingPickup = true;

                    try {
                        const response = await fetch(this.pickupApiUrl, {
                            headers: {
                                'Accept': 'application/json',
                            },
                        });
                        const payload = await response.json();

                        let raw = payload.data;

                        if (raw && !Array.isArray(raw) && Array.isArray(raw.data)) {
                            raw = raw.data;
                        }

                        this.pickupPoints = Array.isArray(raw) ? raw : [];
                    } catch (error) {
                        console.error('[DS] loadPickupPoints error:', error);
                    } finally {
                        this.isLoadingPickup = false;
                    }
                },

                initMap() {
                    const element = this.$refs.mapContainer;

                    if (!element || _ymapsRaw.map || this.activeTab !== 'delivery') {
                        return;
                    }

                    if (typeof ymaps === 'undefined') {
                        console.warn('[DS] ymaps undefined — map cannot init');
                        this.mapLoadError = @json(__('shop::app.components.layouts.header.delivery-method-selector.error'));
                        return;
                    }

                    ymaps.ready(async () => {
                        try {
                            this.mapLoadError = '';

                            const center = this.activeCity?.center_lat != null && this.activeCity?.center_lng != null
                                ? [this.activeCity.center_lat, this.activeCity.center_lng]
                                : [55.7558, 37.6173];

                            console.log('[DS] initMap city=', this.activeCity?.name, 'center=', center, 'zones=', this.allZones.length);

                            _ymapsRaw.map = new ymaps.Map(element, {
                                center,
                                zoom: 11,
                                controls: ['zoomControl', 'geolocationControl'],
                            });

                            _ymapsRaw.map.events.add('click', (event) => {
                                if (this.activeTab !== 'delivery') {
                                    return;
                                }

                                const coords = event.get('coords');

                                this.syncAddressFromCoords(coords);
                            });

                            try {
                                await _ymapsRaw.map.zoomRange.get(center);
                            } catch (zoomRangeError) {
                                console.warn('[DS] zoomRange error:', zoomRangeError);
                            }

                            await new Promise((resolve) => {
                                const fallback = setTimeout(resolve, 1500);

                                _ymapsRaw.map.events.once('boundschange', () => {
                                    clearTimeout(fallback);
                                    resolve();
                                });
                            });

                            this.addZonePolygons();

                            if (this.currentAddressCoords) {
                                this.addOrMoveMarker(this.currentAddressCoords);
                                _ymapsRaw.map.setCenter(this.toPlainCoords(this.currentAddressCoords), 16, {
                                    checkZoomRange: true,
                                    duration: 0,
                                });
                            } else if (this.activeCity) {
                                this._flyToCity(this.activeCity);
                            }

                            this.mapReady = true;
                            this.scheduleMapViewportRefresh();
                        } catch (error) {
                            this.mapLoadError = @json(__('shop::app.components.layouts.header.delivery-method-selector.error'));
                            console.error('[DS] initMap error:', error);
                        }
                    });
                },

                reinitializeMap() {
                    if (_ymapsRaw.map) {
                        _ymapsRaw.map.destroy();
                        _ymapsRaw.map = null;
                        _ymapsRaw.collection = null;
                        _ymapsRaw.zones = {};
                        _ymapsRaw.marker = null;
                        this.mapReady = false;
                        this.mapLoadError = '';
                    }

                    this.$nextTick(() => {
                        setTimeout(() => this.initMap(), 50);
                    });
                },

                refreshMapViewport() {
                    if (! _ymapsRaw.map) {
                        return;
                    }

                    try {
                        _ymapsRaw.map.container.fitToViewport();
                    } catch (error) {
                        console.warn('[DS] refreshMapViewport error:', error);
                    }
                },

                scheduleMapViewportRefresh() {
                    [0, 120, 300, 700].forEach((delay) => {
                        setTimeout(() => this.refreshMapViewport(), delay);
                    });
                },

                addZonePolygons() {
                    if (!_ymapsRaw.map) {
                        return;
                    }

                    console.log('[DS] addZonePolygons count=', this.allZones.length);

                    const collection = new ymaps.GeoObjectCollection({});

                    this.allZones.forEach((zone) => {
                        const coords = this.parseCoordinatesValue(zone.polygon_json);

                        if (coords.length < 3) {
                            return;
                        }

                        const isSelected = Number(this.selectedZone?.id || 0) === Number(zone.id);
                        const fillOpacity = isSelected ? Number(zone.polygon_fill_opacity ?? 0.28) : 0.15;
                        const strokeOpacity = Number(zone.polygon_stroke_opacity ?? 0.9);
                        const fillColor = this.hexToRgba(zone.polygon_color || '#2563eb', fillOpacity);
                        const strokeColor = this.hexToRgba(zone.polygon_color || '#2563eb', strokeOpacity);

                        try {
                            const polygon = new ymaps.Polygon(
                                [coords],
                                {},
                                {
                                    fillColor: fillColor,
                                    fillOpacity: 1,
                                    strokeColor: strokeColor,
                                    strokeOpacity: 1,
                                    strokeWidth: isSelected ? 3 : 2,
                                    zIndex: isSelected ? 2 : 1,
                                    interactivityModel: 'default#transparent',
                                }
                            );

                            _ymapsRaw.zones[zone.id] = polygon;
                            collection.add(polygon);
                        } catch (polygonError) {
                            delete _ymapsRaw.zones[zone.id];
                            console.error('[DS] polygon error zone=', zone.id, polygonError);
                        }
                    });

                    _ymapsRaw.collection = collection;
                    _ymapsRaw.map.geoObjects.add(collection);

                    console.log('[DS] zones rendered:', Object.keys(_ymapsRaw.zones).length);
                },

                addOrMoveMarker(coords) {
                    const plainCoords = this.toPlainCoords(coords);

                    if (!plainCoords || !_ymapsRaw.map) {
                        return;
                    }

                    if (_ymapsRaw.marker) {
                        _ymapsRaw.marker.geometry.setCoordinates(plainCoords);
                    } else {
                        _ymapsRaw.marker = new ymaps.Placemark(
                            plainCoords,
                            {},
                            {
                                iconLayout: 'default#image',
                                iconImageHref: '/images/marker-delivery.svg',
                                iconImageSize: [26, 34],
                                iconImageOffset: [-13, -34],
                            }
                        );
                        _ymapsRaw.map.geoObjects.add(_ymapsRaw.marker);
                    }
                },

                updateZoneHighlight() {
                    Object.entries(_ymapsRaw.zones).forEach(([zoneId, polygon]) => {
                        const zone = this.allZones.find((z) => Number(z.id) === Number(zoneId));

                        if (!zone) {
                            return;
                        }

                        const isSelected = Number(this.selectedZone?.id || 0) === Number(zoneId);
                        const fillOpacity = isSelected ? Number(zone.polygon_fill_opacity ?? 0.28) : 0.12;
                        const strokeOpacity = Number(zone.polygon_stroke_opacity ?? 0.9);
                        const fillColor = this.hexToRgba(zone.polygon_color || '#2563eb', fillOpacity);
                        const strokeColor = this.hexToRgba(zone.polygon_color || '#2563eb', strokeOpacity);

                        polygon.options.set({
                            fillColor: fillColor,
                            fillOpacity: 1,
                            strokeColor: strokeColor,
                            strokeOpacity: 1,
                            strokeWidth: isSelected ? 3 : 2,
                            zIndex: isSelected ? 2 : 1,
                        });
                    });
                },

                toPlainCoords(coords) {
                    if (!Array.isArray(coords) || coords.length < 2) {
                        return null;
                    }

                    const latitude = Number(coords[0]);
                    const longitude = Number(coords[1]);

                    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                        return null;
                    }

                    return [latitude, longitude];
                },

                colorToYmaps(color) {
                    if (typeof color !== 'string') {
                        return '2563eb';
                    }

                    const normalized = color.trim().replace(/^#/, '');

                    if (normalized.length === 3) {
                        return normalized.split('').map((c) => c + c).join('');
                    }

                    return normalized.length === 6 ? normalized : '2563eb';
                },

                hexToRgba(color, opacity = 1) {
                    if (typeof color !== 'string') {
                        return `rgba(37, 99, 235, ${opacity})`;
                    }

                    const normalized = color.trim();

                    if (!normalized.startsWith('#')) {
                        return normalized;
                    }

                    const hex = normalized.slice(1);
                    const safeHex = hex.length === 3
                        ? hex.split('').map((char) => char + char).join('')
                        : hex;

                    if (safeHex.length !== 6) {
                        return `rgba(37, 99, 235, ${opacity})`;
                    }

                    const red = parseInt(safeHex.slice(0, 2), 16);
                    const green = parseInt(safeHex.slice(2, 4), 16);
                    const blue = parseInt(safeHex.slice(4, 6), 16);

                    return `rgba(${red}, ${green}, ${blue}, ${opacity})`;
                },

                normalizeCoordinatePair(point) {
                    if (!Array.isArray(point) || point.length < 2) {
                        return null;
                    }

                    const latitude = Number(point[0]);
                    const longitude = Number(point[1]);

                    if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
                        return null;
                    }

                    return [Number(latitude.toFixed(7)), Number(longitude.toFixed(7))];
                },

                stripClosingPoint(points) {
                    if (!Array.isArray(points) || points.length < 2) {
                        return Array.isArray(points) ? points : [];
                    }

                    const firstPoint = points[0];
                    const lastPoint = points[points.length - 1];

                    if (firstPoint[0] === lastPoint[0] && firstPoint[1] === lastPoint[1]) {
                        return points.slice(0, -1);
                    }

                    return points;
                },

                parseCoordinatesValue(value) {
                    if (!Array.isArray(value)) {
                        return [];
                    }

                    if (!value.length) {
                        return [];
                    }

                    let rawCoordinates = value;

                    if (Array.isArray(value[0]) && value[0].length && Array.isArray(value[0][0])) {
                        rawCoordinates = value[0];
                    }

                    return this.stripClosingPoint(
                        rawCoordinates
                            .map((point) => this.normalizeCoordinatePair(point))
                            .filter((point) => point !== null)
                    );
                },

                placePin(coords) {
                    const plainCoords = this.toPlainCoords(coords);

                    if (!plainCoords) {
                        return;
                    }

                    this.currentAddressCoords = plainCoords;

                    if (this.mapReady) {
                        this.addOrMoveMarker(plainCoords);
                    }
                },

                clearPin() {
                    this.currentAddressCoords = null;

                    if (_ymapsRaw.marker && _ymapsRaw.map) {
                        _ymapsRaw.map.geoObjects.remove(_ymapsRaw.marker);
                        _ymapsRaw.marker = null;
                    }
                },

                async reverseGeocode(coords) {
                    if (!Array.isArray(coords) || coords.length < 2) {
                        return null;
                    }

                    if (typeof ymaps === 'undefined') {
                        console.warn('[DS] reverseGeocode: ymaps not loaded');
                        return null;
                    }

                    console.log('[DS] reverseGeocode coords:', coords);

                    try {
                        const result = await ymaps.geocode([coords[0], coords[1]], {
                            results: 1,
                            kind: 'house',
                        });

                        console.log('[DS] reverseGeocode geoObjects count:', result.geoObjects.getLength());

                        if (!result.geoObjects.getLength()) {
                            console.warn('[DS] reverseGeocode: no results');
                            return null;
                        }

                        const address = result.geoObjects.get(0).getAddressLine();
                        console.log('[DS] reverseGeocode raw address:', address);

                        if (!address) {
                            return null;
                        }

                        const parts = address.split(', ');
                        const stripped = parts.length >= 3 ? parts.slice(1).join(', ') : address;
                        console.log('[DS] reverseGeocode result:', stripped);

                        return stripped;
                    } catch (e) {
                        console.error('[DS] reverseGeocode error:', e?.message || e);

                        return null;
                    }
                },

                shortenAddress(fullAddress) {
                    if (!fullAddress || typeof fullAddress !== 'string') {
                        return fullAddress;
                    }

                    const parts = fullAddress.split(', ');

                    if (parts.length >= 3) {
                        return parts.slice(1).join(', ');
                    }

                    return fullAddress;
                },

                async syncAddressFromCoords(coords) {
                    if (!Array.isArray(coords) || coords.length < 2) {
                        return;
                    }

                    const plainCoords = this.toPlainCoords(coords);

                    if (!plainCoords) {
                        return;
                    }

                    const requestId = ++this.activeReverseGeocodeRequestId;

                    this.hideSuggestions();
                    console.log('[DS] map click → coords:', plainCoords);

                    try {
                        this.placePin(plainCoords);
                    } catch (error) {
                        console.error('[DS] placePin error:', error?.message || error);
                    }

                    const addressLine = await this.reverseGeocode(plainCoords);

                    if (requestId !== this.activeReverseGeocodeRequestId) {
                        return;
                    }

                    if (addressLine) {
                        this.deliveryQuery = addressLine;
                    } else {
                        this.deliveryQuery = `${Number(plainCoords[0]).toFixed(5)}, ${Number(plainCoords[1]).toFixed(5)}`;
                    }

                    if (_ymapsRaw.map) {
                        _ymapsRaw.map.setCenter(plainCoords, 16, {
                            checkZoomRange: true,
                            duration: 0,
                        });
                    }

                    this.findZoneByCoords(plainCoords);
                },

                findZoneByCoords(coords) {
                    if (!Array.isArray(coords) || coords.length < 2) {
                        this.selectedZone = null;
                        this.resolvedZone = null;
                        this.updateZoneHighlight();

                            return;
                    }

                    const [lat, lng] = coords;
                    let foundZone = null;

                    for (const zone of this.allZones) {
                        const polygon = this.parseCoordinatesValue(zone.polygon_json);

                        if (polygon.length < 3) {
                            continue;
                        }

                        if (this.pointInPolygon(lat, lng, polygon)) {
                            foundZone = zone;
                            break;
                        }
                    }

                    this.selectedZone = foundZone;
                    this.resolvedZone = foundZone;
                    this.addressOutsideZone = !foundZone && !!this.currentAddressCoords;

                    console.log('[DS] zone found:', foundZone ? `${foundZone.name} (id=${foundZone.id})` : 'none');

                    if (foundZone) {
                        this.message = '';
                        this.messageIsError = false;
                        this.updateZoneHighlight();
                        return;
                    }

                    this.message = '';
                    this.messageIsError = false;
                    this.updateZoneHighlight();
                },

                _getCityBounds(cityId) {
                    const numericCityId = Number(cityId);
                    const cityZones = this.allZones.filter((zone) => zone.city_id === numericCityId);
                    const points = [];

                    for (const zone of cityZones) {
                        const coords = this.parseCoordinatesValue(zone.polygon_json);

                        for (const point of coords) {
                            points.push(point);
                        }
                    }

                    if (points.length < 3) {
                        return null;
                    }

                    const minLat = points.reduce((min, p) => Math.min(min, p[0]), Infinity);
                    const maxLat = points.reduce((max, p) => Math.max(max, p[0]), -Infinity);
                    const minLng = points.reduce((min, p) => Math.min(min, p[1]), Infinity);
                    const maxLng = points.reduce((max, p) => Math.max(max, p[1]), -Infinity);

                    return [
                        [minLat, minLng],
                        [maxLat, maxLng],
                    ];
                },

                _flyToCity(city) {
                    if (!city) {
                        return;
                    }

                    if (!_ymapsRaw.map) {
                        return;
                    }

                    const bounds = this._getCityBounds(city.id);

                    if (bounds) {
                        _ymapsRaw.map.setBounds(bounds, {
                            checkZoomRange: true,
                            duration: 300,
                            margin: [40, 40, 40, 40],
                        });
                        return;
                    }

                    if (city.center_lat && city.center_lng) {
                        _ymapsRaw.map.setCenter([city.center_lat, city.center_lng], 11, {
                            checkZoomRange: true,
                            duration: 300,
                        });
                        return;
                    }

                    _ymapsRaw.map.setCenter([40.1872, 44.5152], 11); // Yerevan, AM — default fallback
                },

                changeCity(cityId) {
                    this.selectedCityId = Number(cityId);
                    const city = this.cities.find((c) => c.id === this.selectedCityId);

                    this.persistCitySelection(this.selectedCityId);

                    this._flyToCity(city);

                    this.selectedZone = null;
                    this.resolvedZone = null;
                    this.addressOutsideZone = false;
                    this.deliveryQuery = '';
                    this.clearPin();
                    this.hideSuggestions();
                    this.updateZoneHighlight();
                },

                pointInPolygon(lat, lng, polygon) {
                    if (!Array.isArray(polygon) || polygon.length < 3) {
                        return false;
                    }

                    let inside = false;

                    for (let index = 0, previous = polygon.length - 1; index < polygon.length; previous = index++) {
                        const currentPoint = this.normalizeCoordinatePair(polygon[index]);
                        const previousPoint = this.normalizeCoordinatePair(polygon[previous]);

                        if (!currentPoint || !previousPoint) {
                            continue;
                        }

                        const xi = currentPoint[0];
                        const yi = currentPoint[1];
                        const xj = previousPoint[0];
                        const yj = previousPoint[1];

                        const intersects = ((yi > lng) !== (yj > lng))
                            && (lat < ((xj - xi) * (lng - yi)) / ((yj - yi) || 0.0000001) + xi);

                        if (intersects) {
                            inside = !inside;
                        }
                    }

                    return inside;
                },

                handleDeliveryInput() {
                    this.message = '';
                    this.messageIsError = false;
                    this.activeCustomerAddressId = null;
                    this.editingCustomerAddressId = null;
                    this.selectedZone = null;
                    this.resolvedZone = null;
                    this.addressOutsideZone = false;
                    this.clearPin();
                    this.updateZoneHighlight();

                    if (this.suggestionTimer) {
                        clearTimeout(this.suggestionTimer);
                    }

                    this.hideSuggestions();

                    if (this.deliveryQuery.trim().length < 2) {
                        return;
                    }

                    this.suggestionTimer = setTimeout(() => {
                        this.fetchAddressSuggestions();
                    }, 1500);
                },

                handleDeliveryFocus() {
                    if (this.deliveryQuery.trim().length >= 2 && !this.suggestions.length) {
                        this.fetchAddressSuggestions();
                    }
                },

                async handleDeliveryBlur() {
                    if (this.activeTab !== 'delivery' || !this.deliveryQuery.trim() || this.showSuggestions) {
                        return;
                    }

                    if (!this.currentAddressCoords) {
                        await this.syncAddressFromInput();
                    }
                },

                async fetchAddressSuggestions() {
                    if (!this.activeCity || typeof ymaps === 'undefined') {
                        console.warn('[DS] suggest skip: activeCity=', this.activeCity?.name, 'ymaps=', typeof ymaps);
                        return;
                    }

                    this.isSearchingSuggestions = true;

                    try {
                        const cityName = this.activeCity.name || '';
                        const userInput = this.deliveryQuery.trim();
                        const query = cityName ? `${cityName}, ${userInput}` : userInput;
                        const cityLat = Number(this.activeCity.center_lat);
                        const cityLng = Number(this.activeCity.center_lng);

                        const baseOptions = { results: 10 };

                        if (Number.isFinite(cityLat) && Number.isFinite(cityLng)) {
                            baseOptions.boundedBy = [
                                [cityLat - 0.5, cityLng - 0.5],
                                [cityLat + 0.5, cityLng + 0.5],
                            ];
                            baseOptions.strictBounds = true;
                        }

                        console.log('[DS] geocode suggest query:', query);

                        // Два запроса параллельно: обычный (улица+дома) и строго по домам
                        const [resultGeneral, resultHouses] = await Promise.all([
                            ymaps.geocode(query, { ...baseOptions, results: 5 }),
                            ymaps.geocode(query, { ...baseOptions, results: 10, kind: 'house' }),
                        ]);

                        const seenLabels = new Set();
                        const suggestions = [];

                        const addFromResult = (geocodeResult) => {
                            const n = geocodeResult.geoObjects.getLength();

                            for (let i = 0; i < n; i++) {
                                const obj = geocodeResult.geoObjects.get(i);
                                const address = obj.getAddressLine();
                                const coords = obj.geometry.getCoordinates();

                                if (!address || !coords) {
                                    continue;
                                }

                                const parts = address.split(', ');
                                const label = parts.length >= 3 ? parts.slice(1).join(', ') : address;

                                if (seenLabels.has(label)) {
                                    continue;
                                }

                                seenLabels.add(label);

                                const labelParts = label.split(', ');

                                suggestions.push({
                                    key: `${label}-${suggestions.length}`,
                                    label,
                                    fullValue: address,
                                    primary: labelParts.slice(0, 2).join(', '),
                                    secondary: labelParts.length > 2 ? labelParts.slice(2).join(', ') : '',
                                    coords,
                                });
                            }
                        };

                        // Сначала дома (более специфичные), потом общие
                        addFromResult(resultHouses);
                        addFromResult(resultGeneral);

                        console.log('[DS] geocode suggest mapped:', suggestions.length);

                        this.suggestions = suggestions.slice(0, 10);
                        this.highlightedSuggestionIndex = suggestions.length ? 0 : -1;
                    } catch (e) {
                        console.error('[DS] fetchAddressSuggestions error:', e?.message || e);
                    } finally {
                        this.isSearchingSuggestions = false;
                    }
                },

                moveSuggestion(step) {
                    if (!this.visibleSuggestions.length) {
                        return;
                    }

                    const maxIndex = this.visibleSuggestions.length - 1;

                    if (this.highlightedSuggestionIndex === -1) {
                        this.highlightedSuggestionIndex = 0;
                        return;
                    }

                    this.highlightedSuggestionIndex = Math.max(0, Math.min(maxIndex, this.highlightedSuggestionIndex + step));
                },

                applyHighlightedSuggestion() {
                    if (this.highlightedSuggestionIndex < 0 || !this.visibleSuggestions[this.highlightedSuggestionIndex]) {
                        return;
                    }

                    this.selectSuggestion(this.visibleSuggestions[this.highlightedSuggestionIndex]);
                },

                hideSuggestions() {
                    this.suggestions = [];
                    this.highlightedSuggestionIndex = -1;
                },

                async selectSuggestion(suggestion) {
                    this.deliveryQuery = suggestion.label;
                    this.hideSuggestions();

                    const coords = suggestion.coords;

                    if (!coords) {
                        return;
                    }

                    this.currentAddressCoords = this.toPlainCoords(coords);

                    if (_ymapsRaw.map) {
                        this.placePin(coords);
                        _ymapsRaw.map.setCenter(this.toPlainCoords(coords), 16, {
                            checkZoomRange: true,
                            duration: 0,
                        });
                    }

                    this.findZoneByCoords(coords);
                },

                async resolveAddressByQuery() {
                    if (!this.deliveryQuery.trim() || !this.activeCity || typeof ymaps === 'undefined') {
                        return null;
                    }

                    try {
                        const cityName = this.activeCity.name || '';
                        const query = cityName ? `${cityName}, ${this.deliveryQuery.trim()}` : this.deliveryQuery.trim();
                        const result = await ymaps.geocode(query, { results: 1 });

                        if (!result.geoObjects.getLength()) {
                            return null;
                        }

                        const geoObject = result.geoObjects.get(0);
                        const address = geoObject.getAddressLine();
                        const coords = geoObject.geometry.getCoordinates();

                        if (!coords) {
                            return null;
                        }

                        const parts = (address || '').split(', ');
                        const label = parts.length >= 3 ? parts.slice(1).join(', ') : (address || '');

                        return { label, coords };
                    } catch (error) {
                        console.error('[DeliverySelector] resolveAddressByQuery error:', error);
                        return null;
                    }
                },

                async syncAddressFromInput() {
                    const resolved = await this.resolveAddressByQuery();

                    if (!resolved?.coords) {
                        this.selectedZone = null;
                        this.resolvedZone = null;
                        this.message = @json(__('shop::app.components.layouts.header.delivery-method-selector.address-required'));
                        this.messageIsError = true;
                        this.clearPin();
                        this.updateZoneHighlight();
                        return null;
                    }

                    this.deliveryQuery = resolved.label;
                    this.currentAddressCoords = this.toPlainCoords(resolved.coords);

                    if (_ymapsRaw.map) {
                        this.placePin(resolved.coords);
                        _ymapsRaw.map.setCenter(this.toPlainCoords(resolved.coords), 16, {
                            checkZoomRange: true,
                            duration: 0,
                        });
                    }

                    this.findZoneByCoords(resolved.coords);

                    return resolved;
                },

                validateDeliveryForm() {
                    if (!this.deliveryQuery.trim()) {
                        return @json(__('shop::app.components.layouts.header.delivery-method-selector.address-required'));
                    }

                    if (!this.isPrivateHouse && !/\d/.test(this.deliveryQuery)) {
                        return @json(__('shop::app.components.layouts.header.delivery-method-selector.address-number-required'));
                    }

                    if (!this.isPrivateHouse && (!this.apartment.trim() || !this.entrance.trim() || !this.floor.trim())) {
                        return @json(__('shop::app.components.layouts.header.delivery-method-selector.apartment-fields-required'));
                    }

                    if (!this.selectedZone) {
                        return @json(__('shop::app.components.layouts.header.delivery-method-selector.outside-zones'));
                    }

                    return null;
                },

                async confirmSelection() {
                    if (this.activeTab === 'pickup') {
                        this.confirmPickupSelection();
                        return;
                    }

                    this.message = '';
                    this.messageIsError = false;

                    if (!this.currentAddressCoords) {
                        const resolved = await this.syncAddressFromInput();

                        if (resolved?.coords) {
                            this.deliveryQuery = resolved.label;
                        }
                    }

                    const validationError = this.validateDeliveryForm();

                    if (validationError) {
                        this.message = validationError;
                        this.messageIsError = true;
                        return;
                    }

                    this.isSubmittingDelivery = true;

                    try {
                        const formData = new FormData();
                        formData.append('delivery_zone_id', this.selectedZone.id);
                        formData.append('_token', this.csrfToken);

                        if (this.currentAddressCoords) {
                            formData.append('delivery_point_lat', this.currentAddressCoords[0]);
                            formData.append('delivery_point_lng', this.currentAddressCoords[1]);
                        }

                        formData.append('city', this.selectedZone.city_name || this.activeCity?.name || '');

                        const response = await fetch(this.selectApiUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: formData,
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            throw payload;
                        }

                        const inner = payload.data?.data || payload.data || payload;

                        if (inner.cart) {
                            this.cartSummary = inner.cart;
                        }

                        this.confirmedAddress = this.getDisplayAddress();
                        this.persistGuestAddress();

                        if (this.isCustomer) {
                            await this.persistCustomerAddress();
                        }

                        this.message = '';
                        this.messageIsError = false;

                        setTimeout(() => this.closeModal(), 200);
                    } catch (error) {
                        this.message = error?.data?.message || error?.message || @json(__('shop::app.components.layouts.header.delivery-method-selector.error'));
                        this.messageIsError = true;
                    } finally {
                        this.isSubmittingDelivery = false;
                    }
                },

                confirmPickupSelection() {
                    if (!this.selectedPickupPoint) {
                        return;
                    }

                    this.confirmedAddress = `${this.selectedPickupPoint.street}${this.selectedPickupPoint.city ? `, ${this.selectedPickupPoint.city}` : ''}`;
                    this.closeModal();
                },

                selectPickupPoint(point) {
                    this.selectedPickupPoint = point;
                    this.confirmedAddress = `${point.street}${point.city ? `, ${point.city}` : ''}`;
                },

                normalizeCustomerAddress(address) {
                    const additional = address.additional || {};
                    const addressLine = Array.isArray(address.address) ? (address.address[0] || '') : '';
                    const label = additional.label || [address.city, addressLine].filter(Boolean).join(', ');

                    return {
                        ...address,
                        label,
                        zoneId: additional.zone_id || null,
                        zoneName: additional.zone_name || '',
                        latitude: additional.latitude || null,
                        longitude: additional.longitude || null,
                        isPrivateHouse: Boolean(additional.is_private_house),
                        apartment: additional.apartment || '',
                        entrance: additional.entrance || '',
                        floor: additional.floor || '',
                        intercom: additional.intercom || '',
                    };
                },

                selectSavedAddress(address) {
                    this.activeCustomerAddressId = address.id;
                    this.editingCustomerAddressId = null;
                    this.applyStoredAddress({
                        label: address.label,
                        latitude: address.latitude,
                        longitude: address.longitude,
                        zone_id: address.zoneId,
                        zone_name: address.zoneName,
                        is_private_house: address.isPrivateHouse,
                        apartment: address.apartment,
                        entrance: address.entrance,
                        floor: address.floor,
                        intercom: address.intercom,
                    });
                    this.clearGuestAddress();
                },

                startEditAddress(address) {
                    this.selectSavedAddress(address);
                    this.editingCustomerAddressId = address.id;
                },

                async deleteSavedAddress(address) {
                    if (!window.confirm(this.translations.deleteConfirm)) {
                        return;
                    }

                    try {
                        await fetch(this.customerAddressDeleteUrl.replace(/0$/, String(address.id)), {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                        });

                        this.savedAddresses = this.savedAddresses.filter((item) => item.id !== address.id);

                        if (this.activeCustomerAddressId === address.id) {
                            this.activeCustomerAddressId = null;
                            this.editingCustomerAddressId = null;
                        }
                    } catch (error) {
                        console.error('[DeliverySelector] deleteSavedAddress error:', error);
                    }
                },

                applyStoredAddress(payload) {
                    this.deliveryQuery = payload.label || '';
                    this.isPrivateHouse = Boolean(payload.is_private_house);
                    this.apartment = payload.apartment || '';
                    this.entrance = payload.entrance || '';
                    this.floor = payload.floor || '';
                    this.intercom = payload.intercom || '';

                    if (payload.zone_id) {
                        this.selectedZone = this.allZones.find((zone) => zone.id === payload.zone_id) || null;
                        this.resolvedZone = this.selectedZone;
                    }

                    if (payload.latitude && payload.longitude) {
                        const coords = [Number(payload.latitude), Number(payload.longitude)];
                        this.currentAddressCoords = coords;

                        if (_ymapsRaw.map) {
                            this.placePin(coords);
                            _ymapsRaw.map.setCenter(coords, 16, {
                                checkZoomRange: true,
                                duration: 0,
                            });
                        }
                    }

                    this.updateZoneHighlight();
                },

                restorePersistedAddress() {
                    const payload = this.readGuestAddress();

                    if (this.selectedCityId) {
                        this.persistCitySelection(this.selectedCityId);
                    }

                    if (!payload) {
                        return;
                    }

                    if (this.isCustomer && this.activeCustomerAddressId) {
                        return;
                    }

                    const resolvedCityId = this.resolveCityIdFromPayload(payload);

                    if (resolvedCityId) {
                        this.selectedCityId = resolvedCityId;
                        this.persistCitySelection(this.selectedCityId);
                    }

                    this.applyStoredAddress(payload);
                    this.confirmedAddress = payload.label || this.confirmedAddress;
                },

                resolveCityIdFromPayload(payload) {
                    const payloadCityId = Number(payload?.city_id || 0);

                    if (payloadCityId && this.cities.some((city) => city.id === payloadCityId)) {
                        return payloadCityId;
                    }

                    const payloadZoneId = Number(payload?.zone_id || 0);

                    if (!payloadZoneId) {
                        return null;
                    }

                    const zone = this.allZones.find((candidate) => candidate.id === payloadZoneId);

                    if (!zone || !zone.city_id) {
                        return null;
                    }

                    return this.cities.some((city) => city.id === zone.city_id)
                        ? zone.city_id
                        : null;
                },

                readPersistedCityId() {
                    try {
                        const rawCityId = localStorage.getItem(this.guestCityStorageKey);
                        const cityId = Number(rawCityId || 0);

                        if (!cityId) {
                            return null;
                        }

                        return this.cities.some((city) => city.id === cityId)
                            ? cityId
                            : null;
                    } catch (error) {
                        return null;
                    }
                },

                persistCitySelection(cityId) {
                    const normalizedCityId = Number(cityId || 0);

                    if (!normalizedCityId) {
                        return;
                    }

                    try {
                        localStorage.setItem(this.guestCityStorageKey, String(normalizedCityId));
                    } catch (error) {
                        console.error('[DeliverySelector] persistCitySelection error:', error);
                    }
                },

                readGuestAddress() {
                    try {
                        const raw = localStorage.getItem(this.guestAddressStorageKey);

                        if (!raw) {
                            return null;
                        }

                        const parsed = JSON.parse(raw);

                        if (!parsed.expires_at || parsed.expires_at < Date.now()) {
                            this.clearGuestAddress();
                            return null;
                        }

                        return parsed;
                    } catch (error) {
                        this.clearGuestAddress();
                        return null;
                    }
                },

                persistGuestAddress() {
                    try {
                        localStorage.setItem(this.guestAddressStorageKey, JSON.stringify({
                            label: this.getDisplayAddress(),
                            latitude: this.currentAddressCoords?.[0] ?? null,
                            longitude: this.currentAddressCoords?.[1] ?? null,
                            zone_id: this.selectedZone?.id ?? null,
                            zone_name: this.selectedZone?.name ?? '',
                            city_id: this.selectedCityId ?? this.selectedZone?.city_id ?? null,
                            city: this.selectedZone?.city_name || this.activeCity?.name || '',
                            country: this.selectedZone?.country || this.activeCity?.country || '',
                            state: this.selectedZone?.state || this.activeCity?.state || '',
                            is_private_house: this.isPrivateHouse,
                            apartment: this.apartment,
                            entrance: this.entrance,
                            floor: this.floor,
                            intercom: this.intercom,
                            expires_at: Date.now() + this.guestAddressTtlMs,
                        }));
                    } catch (error) {
                        console.error('[DeliverySelector] persistGuestAddress error:', error);
                    }
                },

                clearGuestAddress() {
                    localStorage.removeItem(this.guestAddressStorageKey);
                },

                async persistCustomerAddress() {
                    if (!this.isCustomer) {
                        return;
                    }

                    const fallbackAddress = this.savedAddresses[0] || null;
                    const requestBody = {
                        first_name: this.customerProfile?.first_name || fallbackAddress?.first_name || 'Customer',
                        last_name: this.customerProfile?.last_name || fallbackAddress?.last_name || 'Customer',
                        email: this.customerProfile?.email || fallbackAddress?.email || 'customer@example.com',
                        phone: this.customerProfile?.phone || fallbackAddress?.phone || '9999999999',
                        country: this.selectedZone?.country || this.activeCity?.country || fallbackAddress?.country || 'RU',
                        state: this.selectedZone?.state || this.activeCity?.state || fallbackAddress?.state || 'MOW',
                        postcode: fallbackAddress?.postcode || '000000',
                        address: [this.deliveryQuery.trim()],
                        city: this.selectedZone?.city_name || this.activeCity?.name || fallbackAddress?.city || '',
                        default_address: false,
                        additional: {
                            label: this.getDisplayAddress(),
                            latitude: this.currentAddressCoords?.[0] ?? null,
                            longitude: this.currentAddressCoords?.[1] ?? null,
                            zone_id: this.selectedZone?.id ?? null,
                            zone_name: this.selectedZone?.name ?? '',
                            is_private_house: this.isPrivateHouse,
                            apartment: this.apartment,
                            entrance: this.entrance,
                            floor: this.floor,
                            intercom: this.intercom,
                        },
                    };

                    const isEditing = Boolean(this.editingCustomerAddressId);
                    const url = isEditing
                        ? this.customerAddressUpdateUrl.replace(/0$/, String(this.editingCustomerAddressId))
                        : this.customerAddressesApiUrl;

                    const response = await fetch(url, {
                        method: isEditing ? 'PUT' : 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                        body: JSON.stringify(requestBody),
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        throw payload;
                    }

                    const normalized = this.normalizeCustomerAddress(payload.data);

                    if (isEditing) {
                        this.savedAddresses = this.savedAddresses.map((address) => address.id === normalized.id ? normalized : address);
                    } else {
                        this.savedAddresses.unshift(normalized);
                    }

                    this.activeCustomerAddressId = normalized.id;
                    this.editingCustomerAddressId = normalized.id;
                    this.clearGuestAddress();
                },

                getDisplayAddress() {
                    const parts = [this.deliveryQuery.trim()];

                    if (!this.isPrivateHouse && this.apartment.trim()) {
                        parts.push(`${this.translations.apartment}: ${this.apartment.trim()}`);
                    }

                    return parts.filter(Boolean).join(', ');
                },

                formatPrice(price) {
                    return new Intl.NumberFormat(@json(app()->getLocale()), {
                        style: 'currency',
                        currency: @json(core()->getCurrentCurrencyCode()),
                    }).format(price);
                },
            },
        });
    </script>
@endPushOnce

@once
    @include('delivery-zones::shop.components.welcome-modal')
@endonce

@props([
    'hasHeader'  => true,
    'hasFeature' => true,
    'hasFooter'  => true,
])

<!DOCTYPE html>

<html
    lang="{{ app()->getLocale() }}"
    dir="{{ core()->getCurrentLocale()->direction }}"
>
    <head>

        {!! view_render_event('bagisto.shop.layout.head.before') !!}

        <title>{{ $title ?? '' }}</title>

        <meta charset="UTF-8">

        <meta
            http-equiv="X-UA-Compatible"
            content="IE=edge"
        >
        <meta
            http-equiv="content-language"
            content="{{ app()->getLocale() }}"
        >

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="base-url"
            content="{{ url()->to('/') }}"
        >
        <meta
            name="currency"
            content="{{ core()->getCurrentCurrency()->toJson() }}"
        >
        <meta 
            name="generator" 
            content="Bagisto"
        >

        @stack('meta')

        <link
            rel="icon"
            sizes="16x16"
            href="{{ core()->getCurrentChannel()->favicon_url ?? bagisto_asset('images/favicon.ico') }}"
        />

        @bagistoVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'])

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
            crossorigin
        />

        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        />

        <link
            rel="preload" as="style"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap"
        />

        <link
            rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=DM+Serif+Display&display=swap"
        />

        @stack('styles')

        <style>
            {!! core()->getConfigData('general.content.custom_scripts.custom_css') !!}
        </style>

        @if(core()->getConfigData('general.content.speculation_rules.enabled'))
            <script type="speculationrules">
                @json(core()->getSpeculationRules(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            </script>
        @endif

        {!! view_render_event('bagisto.shop.layout.head.after') !!}

    </head>

    <body>
        {!! view_render_event('bagisto.shop.layout.body.before') !!}

        <a
            href="#main"
            class="skip-to-main-content-link"
        >
            Skip to main content
        </a>

        <!-- Built With Bagisto -->
        <div id="app">
            <!-- Flash Message Blade Component -->
            <x-shop::flash-group />

            <!-- Confirm Modal Blade Component -->
            <x-shop::modal.confirm />

            <!-- Page Header Blade Component -->
            @if ($hasHeader)
                <x-shop::layouts.header />
            @endif

            @if(
                core()->getConfigData('general.gdpr.settings.enabled')
                && core()->getConfigData('general.gdpr.cookie.enabled')
            )
                <x-shop::layouts.cookie />
            @endif

            {!! view_render_event('bagisto.shop.layout.content.before') !!}

            <!-- Page Content Blade Component -->
            <main id="main" class="bg-white">
                {{ $slot }}
            </main>

            {!! view_render_event('bagisto.shop.layout.content.after') !!}


            <!-- Page Services Blade Component -->
            @if ($hasFeature)
                <x-shop::layouts.services />
            @endif

            <!-- Page Footer Blade Component -->
            @if ($hasFooter)
                <x-shop::layouts.footer />
            @endif
        </div>

        {!! view_render_event('bagisto.shop.layout.body.after') !!}

        @stack('scripts')

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.before') !!}
        <script>
            /**
             * Load event, the purpose of using the event is to mount the application
             * after all of our `Vue` components which is present in blade file have
             * been registered in the app. No matter what `app.mount()` should be
             * called in the last.
             */
            window.addEventListener("load", function (event) {
                app.mount("#app");
            });
        </script>

        {!! view_render_event('bagisto.shop.layout.vue-app-mount.after') !!}

        <script type="text/javascript">
            {!! core()->getConfigData('general.content.custom_scripts.custom_javascript') !!}
        </script>

        @auth('customer')
        <script type="module">
            (function () {
                if (! ('serviceWorker' in navigator) || ! ('PushManager' in window)) {
                    return;
                }

                const vapidKeyUrl    = '{{ route('shop.push.vapid_public_key') }}';
                const subscribeUrl   = '{{ route('shop.customers.push.subscribe') }}';
                const pollUrl        = '{{ route('shop.customers.push.notifications') }}';
                const markAllReadUrl = '{{ route('shop.customers.push.mark_all_read') }}';

                function urlBase64ToUint8Array(base64String) {
                    const padding = '='.repeat((4 - base64String.length % 4) % 4);
                    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                    const raw     = window.atob(base64);
                    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
                }

                async function registerShopPush() {
                    try {
                        const keyResp = await fetch(vapidKeyUrl);
                        const keyData = await keyResp.json();

                        if (! keyData.public_key) {
                            return;
                        }

                        const reg          = await navigator.serviceWorker.register('/sw.js');
                        const subscription = await reg.pushManager.getSubscription();

                        if (subscription) {
                            return;
                        }

                        const permission = await Notification.requestPermission();

                        if (permission !== 'granted') {
                            return;
                        }

                        const newSub = await reg.pushManager.subscribe({
                            userVisibleOnly:      true,
                            applicationServerKey: urlBase64ToUint8Array(keyData.public_key),
                        });

                        const subJson = newSub.toJSON();

                        await axios.post(subscribeUrl, {
                            endpoint:   subJson.endpoint,
                            public_key: subJson.keys.p256dh,
                            auth_token: subJson.keys.auth,
                        });
                    } catch (e) {
                        console.error('[ShopPush] Registration error:', e);
                    }
                }

                let lastSeenId = 0;

                function showInPageToast(notification) {
                    const container = document.getElementById('v-push-toast-container');

                    if (! container) {
                        return;
                    }

                    const toast = document.createElement('div');
                    toast.className = 'pointer-events-auto flex max-w-sm rounded-lg border bg-white p-4 shadow-lg dark:border-gray-700 dark:bg-gray-900';
                    toast.innerHTML = `
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">${notification.title}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">${notification.body}</p>
                        </div>
                        <button class="ml-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" onclick="this.parentElement.remove()">✕</button>
                    `;

                    if (notification.url) {
                        toast.style.cursor = 'pointer';
                        toast.addEventListener('click', (e) => {
                            if (e.target.tagName !== 'BUTTON') {
                                window.location.href = notification.url;
                            }
                        });
                    }

                    container.appendChild(toast);

                    setTimeout(() => toast.remove(), 8000);
                }

                async function pollNotifications() {
                    try {
                        const resp = await axios.get(pollUrl);
                        const notifications = resp.data?.data ?? [];

                        const newOnes = notifications.filter(n => n.id > lastSeenId);

                        if (newOnes.length > 0) {
                            lastSeenId = Math.max(...newOnes.map(n => n.id));

                            newOnes.forEach(n => showInPageToast(n));

                            await axios.post(markAllReadUrl);
                        }
                    } catch (e) {
                        // Polling failure is non-critical
                    }
                }

                navigator.serviceWorker.ready.then(registerShopPush);

                setInterval(pollNotifications, 30000);
                setTimeout(pollNotifications, 3000);
            })();
        </script>

        <div
            id="v-push-toast-container"
            class="fixed bottom-4 right-4 z-[10002] flex flex-col gap-2 pointer-events-none"
        ></div>
        @endauth
    </body>
</html>

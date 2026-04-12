@props([
    'count'           => 8,
    'desktopColumns'  => 4,
    'mobileColumns'   => 2,
])

<div class="container mt-14 max-lg:px-8 max-md:mt-7 max-sm:mt-5">
    <div
        class="grid gap-6 max-md:gap-4"
        style="grid-template-columns: repeat({{ (int) $desktopColumns }}, minmax(0, 1fr));"
    >
        @for ($i = 0; $i < $count; $i++)
            <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white">
                <div class="shimmer aspect-square w-full"></div>

                <div class="px-3 py-3">
                    <p class="shimmer mx-auto h-5 w-3/4 rounded-2xl"></p>
                </div>
            </div>
        @endfor
    </div>
</div>

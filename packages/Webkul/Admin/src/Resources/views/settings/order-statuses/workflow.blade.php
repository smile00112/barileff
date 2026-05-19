<x-admin::layouts>
    <x-slot:title>
        @lang('admin::app.settings.order-statuses.workflow.title')
    </x-slot>

    <div class="flex items-center gap-4 max-sm:flex-wrap">
        <a
            href="{{ route('admin.settings.order_statuses.index') }}"
            class="icon-arrow-left text-2xl"
        ></a>

        <p class="text-xl font-bold text-gray-800 dark:text-white">
            @lang('admin::app.settings.order-statuses.workflow.title')
        </p>
    </div>

    <form
        method="POST"
        action="{{ route('admin.settings.order_statuses.workflow.update') }}"
    >
        @csrf
        @method('PUT')

        <div class="box-shadow rounded bg-white p-4 dark:bg-gray-900">
            <x-admin::form.control-group class="max-w-sm">
                <x-admin::form.control-group.label class="required">
                    @lang('admin::app.settings.order-statuses.workflow.new-order-status')
                </x-admin::form.control-group.label>

                <select
                    name="new_order_status"
                    class="w-full rounded border px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    @foreach ($allStatuses as $status)
                        <option
                            value="{{ $status->code }}"
                            @selected($status->code === $newOrderStatus)
                        >
                            {{ $status->name }} ({{ $status->code }})
                        </option>
                    @endforeach
                </select>

                <x-admin::form.control-group.error control-name="new_order_status" />
            </x-admin::form.control-group>
        </div>

        <div class="mt-4 flex justify-end">
            <button
                type="submit"
                class="primary-button"
            >
                @lang('admin::app.settings.order-statuses.workflow.save-btn')
            </button>
        </div>
    </form>
</x-admin::layouts>

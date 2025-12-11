<div
    class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3 lg:grid-cols-4"
    x-data="{
        index: {{ $exception->defaultFrame() }},
        includeVendorFrames: false,
    }"
>
    <x-laravel-exceptions-renderer::trace :$exception />
    <x-laravel-exceptions-renderer::editor :$exception />
</div>


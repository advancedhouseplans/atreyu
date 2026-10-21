<x-layouts::app.sidebar :title="$title ?? null">
    <div class="mx-auto w-full max-w-[1600px]">
        {{ $slot }}
    </div>
</x-layouts::app.sidebar>

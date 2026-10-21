<x-layouts::app.sidebar :title="$title ?? null">
    <main class="mx-auto w-full max-w-[1600px]">
        {{ $slot }}
    </main>
</x-layouts::app.sidebar>

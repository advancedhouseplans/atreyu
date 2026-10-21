@props(['title' => null, 'heading' => null, 'description' => null])

<x-layouts::auth.simple :title="$title" :heading="$heading" :description="$description">
    {{ $slot }}
</x-layouts::auth.simple>

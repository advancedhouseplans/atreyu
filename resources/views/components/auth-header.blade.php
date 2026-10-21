@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="text-2xl font-bold tracking-tight">{{ $title }}</h1>
    <p class="mt-2 text-sm leading-6 text-base-content/60">{{ $description }}</p>
</div>

@props(['href', 'title' => null])
<div class="flex mx-auto max-w-screen-xl mb-8 space-x-2 items-center">
    <a href="{{ $href }}" class="focus:outline-none rounded-lg px-3 py-2.5 text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="shrink-0 w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
        </svg>
    </a>
    <h2 class="text-2xl font-extrabold leading-none tracking-tight text-gray-900 md:text-3xl dark:text-white">
        {{ $title ?? $slot }}
    </h2>
</div>
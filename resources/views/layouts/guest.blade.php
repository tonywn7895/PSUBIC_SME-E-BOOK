<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="overflow-x-hidden">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'SME E-Books') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        @stack('styles')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-900 font-sans antialiased overflow-x-hidden">
        {{-- Public Header --}}
        <header class="sticky top-0 z-40 w-full border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2" wire:navigate>
                    <x-app-logo-icon class="size-10 sm:size-12" />
                    <span class="font-bold text-xl text-zinc-900 dark:text-white hidden sm:block">{{ config('app.name') }}</span>
                </a>

                <nav class="flex items-center gap-2 sm:gap-4">
                    <flux:button :href="route('explore')" variant="ghost" wire:navigate>{{ __('Explore') }}</flux:button>
                    <flux:button :href="route('insights')" variant="ghost" wire:navigate>{{ __('Insights') }}</flux:button>
                    
                    @auth
                        <flux:button :href="route('dashboard')" variant="ghost" wire:navigate>{{ __('Dashboard') }}</flux:button>
                    @endauth

                    <flux:button 
                        x-data
                        @click="$flux.appearance = $flux.appearance === 'dark' ? 'light' : 'dark'" 
                        variant="ghost" 
                        size="sm" 
                        class="cursor-pointer"
                        aria-label="Toggle theme"
                    >
                        <flux:icon name="sun" variant="outline" class="size-5 hidden dark:block" />
                        <flux:icon name="moon" variant="outline" class="size-5 dark:hidden" />
                    </flux:button>
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        @fluxScripts
        @stack('scripts')
    </body>
</html>

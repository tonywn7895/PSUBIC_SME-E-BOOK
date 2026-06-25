<?php

use App\Models\Ebook;
use function Livewire\Volt\{with, on};

on([
    'ebook-deleted' => fn () => null,
]);

with(fn () => [
    'recentEbooks' => Ebook::query()->where('user_id', auth()->id())->with('user')->latest()->take(5)->get(),
]);

?>

<flux:card class="space-y-4">
    <div class="flex items-center justify-between">
        <flux:heading size="lg">{{ __('Recent Uploads') }}</flux:heading>
        <flux:link :href="route('ebooks.index')" wire:navigate class="text-sm font-medium">{{ __('View all') }}</flux:link>
    </div>

    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
        @forelse ($recentEbooks as $ebook)
            <div class="py-3 flex items-center justify-between group">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                        <flux:icon name="book-open" variant="outline" class="size-5 text-zinc-400" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $ebook->title }}</span>
                        <span class="text-xs text-zinc-500">
                            {{ __('Uploaded by') }} {{ $ebook->user?->name ?? __('Unknown admin') }} · {{ $ebook->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('ebooks.edit', $ebook)" wire:navigate />
                    <flux:button variant="ghost" size="sm" icon="eye" :href="route('ebooks.view', $ebook->slug)" target="_blank" />
                </div>
            </div>
        @empty
            <div class="py-12 text-center text-zinc-500 text-sm italic">
                <div class="flex flex-col items-center gap-2">
                    <flux:icon name="cloud-arrow-up" class="size-10 text-zinc-200 dark:text-zinc-800" />
                    {{ __('No e-books uploaded yet.') }}
                </div>
            </div>
        @endforelse
    </div>
</flux:card>

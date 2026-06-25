<?php

use App\Models\Ebook;
use App\Models\Spreadsheet;
use App\Models\Chart;
use function Livewire\Volt\{with, on};

on([
    'ebook-deleted' => fn () => null,
    'spreadsheet-deleted' => fn () => null,
]);

with(fn () => [
    'ebookStats' => [
        'total' => Ebook::query()->where('user_id', auth()->id())->count(),
        'published' => Ebook::query()->where('user_id', auth()->id())->where('status', 'published')->count(),
        'drafts' => Ebook::query()->where('user_id', auth()->id())->where('status', 'draft')->count(),
    ],
    'spreadsheetStats' => [
        'total' => Spreadsheet::query()->where('user_id', auth()->id())->count(),
        'published' => Spreadsheet::query()->where('user_id', auth()->id())->where('status', 'published')->count(),
        'drafts' => Spreadsheet::query()->where('user_id', auth()->id())->where('status', 'draft')->count(),
    ],
    'chartStats' => [
        'total' => auth()->user()->is_admin ? Chart::count() : 0,
    ]
]);

?>

<div class="space-y-4">
    <flux:heading size="lg">{{ __('My Content') }}</flux:heading>
    
    <div class="grid auto-rows-min gap-4 md:grid-cols-4 {{ auth()->user()->is_admin ? 'xl:grid-cols-5' : '' }}">
        {{-- E-books Summary --}}
        <flux:card class="flex flex-col gap-1">
            <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wider">{{ __('Total E-books') }}</flux:heading>
            <div class="flex items-end justify-between">
                <span class="text-3xl font-bold">{{ $ebookStats['total'] }}</span>
                <flux:icon name="book-open" class="size-8 text-zinc-200 dark:text-zinc-800" />
            </div>
        </flux:card>

        <flux:card class="flex flex-col gap-1">
            <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wider">{{ __('Published E-books') }}</flux:heading>
            <div class="flex items-end justify-between">
                <span class="text-3xl font-bold text-green-600 dark:text-green-500">{{ $ebookStats['published'] }}</span>
                <flux:icon name="check-circle" class="size-8 text-zinc-200 dark:text-zinc-800" />
            </div>
        </flux:card>

        {{-- Tables Summary --}}
        <flux:card class="flex flex-col gap-1">
            <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wider">{{ __('Data Tables') }}</flux:heading>
            <div class="flex items-end justify-between">
                <span class="text-3xl font-bold text-indigo-600 dark:text-indigo-500">{{ $spreadsheetStats['total'] }}</span>
                <flux:icon name="table-cells" class="size-8 text-zinc-200 dark:text-zinc-800" />
            </div>
        </flux:card>

        @if (auth()->user()->is_admin)
            {{-- Charts Summary --}}
            <flux:card class="flex flex-col gap-1">
                <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wider">{{ __('Total Charts') }}</flux:heading>
                <div class="flex items-end justify-between">
                    <span class="text-3xl font-bold text-blue-600 dark:text-blue-500">{{ $chartStats['total'] }}</span>
                    <flux:icon name="chart-bar" class="size-8 text-zinc-200 dark:text-zinc-800" />
                </div>
            </flux:card>
        @endif

        <flux:card class="flex flex-col gap-1">
            <flux:heading size="sm" class="text-zinc-500 uppercase tracking-wider">{{ __('Drafts') }}</flux:heading>
            <div class="flex items-end justify-between">
                <span class="text-3xl font-bold text-amber-600 dark:text-amber-500">{{ $ebookStats['drafts'] + $spreadsheetStats['drafts'] }}</span>
                <flux:icon name="pencil-square" class="size-8 text-zinc-200 dark:text-zinc-800" />
            </div>
        </flux:card>
    </div>
</div>

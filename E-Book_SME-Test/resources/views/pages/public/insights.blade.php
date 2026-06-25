<?php

use App\Models\Chart;
use App\Support\Thailand;
use function Livewire\Volt\{computed, layout, title, state};

layout('layouts.guest');
title('Platform Insights');

state([
    'search' => '',
    'category' => '',
    'province' => '',
    'fiscalYear' => '',
    'perPage' => 9,
])->url();

$charts = computed(function () {
    return Chart::query()
        ->public()
        ->where('title', 'like', '%' . $this->search . '%')
        ->whereHas('spreadsheet', function ($query) {
            if ($this->category) {
                $query->category($this->category);
            }
            if ($this->province) {
                $query->province($this->province);
            }
            if ($this->fiscalYear) {
                $query->fiscalYear($this->fiscalYear);
            }
        })
        ->with('spreadsheet')
        ->latest()
        ->take($this->perPage)
        ->get();
});

?>

<div class="bg-white dark:bg-zinc-900 min-h-screen">
    {{-- Header Section with subtle gradients --}}
    <div class="relative overflow-hidden bg-zinc-50 dark:bg-zinc-950 border-b border-zinc-100 dark:border-zinc-800 py-16">
        {{-- Background blur decorations --}}
        <div class="absolute top-0 right-1/4 size-72 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute bottom-0 left-1/4 size-72 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3 text-indigo-600 dark:text-indigo-400 mb-2">
                <flux:icon name="chart-bar" class="size-8" />
                <span class="text-sm font-semibold uppercase tracking-wider">{{ __('Data Analytics') }}</span>
            </div>
            <h1 class="text-4xl font-extrabold text-zinc-900 dark:text-white sm:text-5xl tracking-tight">{{ __('Platform Insights') }}</h1>
            <p class="mt-3 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl">{{ __('แสดงผลข้อมูลสถิติและวิเคราะห์ในรูปแบบกราฟสาธารณะ คัดกรองและเจาะลึกสถิติสำคัญได้ทันที.') }}</p>

            {{-- Interactive Filters Block --}}
            <div class="mt-10 flex flex-col lg:flex-row gap-4 items-center justify-between">
                <div class="w-full max-w-xl">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        placeholder="{{ __('Search charts by title...') }}"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 w-full lg:w-auto sm:min-w-[450px]">
                    <flux:select wire:model.live="category" placeholder="{{ __('All Categories') }}">
                        <flux:select.option value="">{{ __('All Categories') }}</flux:select.option>
                        <flux:select.option value="sme">{{ __('SME') }}</flux:select.option>
                        <flux:select.option value="otop">{{ __('OTOP') }}</flux:select.option>
                        <flux:select.option value="startup">{{ __('Startup') }}</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="province" placeholder="{{ __('All Provinces') }}">
                        <flux:select.option value="">{{ __('All Provinces') }}</flux:select.option>
                        @foreach (Thailand::provinces() as $prov)
                            <flux:select.option :value="$prov">{{ $prov }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="fiscalYear" placeholder="{{ __('All Fiscal Years') }}">
                        <flux:select.option value="">{{ __('All Fiscal Years') }}</flux:select.option>
                        @foreach (Thailand::fiscalYears() as $year)
                            <flux:select.option :value="$year">ปี {{ $year }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>

            {{-- Active Badges --}}
            @if ($category || $province || $fiscalYear)
                <div class="mt-4 flex flex-wrap gap-2 items-center">
                    <span class="text-xs text-zinc-500 font-semibold uppercase tracking-wider mr-2">{{ __('Active Filters:') }}</span>

                    @if ($category)
                        <flux:badge size="sm" color="indigo" class="flex items-center gap-1">
                            <span>{{ __('Category') }}: {{ strtoupper($category) }}</span>
                            <button wire:click="$set('category', '')" class="hover:text-red-500 font-bold ml-1 outline-none text-zinc-400 cursor-pointer">
                                <flux:icon name="x-mark" variant="micro" class="size-3" />
                            </button>
                        </flux:badge>
                    @endif

                    @if ($province)
                        <flux:badge size="sm" color="indigo" class="flex items-center gap-1">
                            <span>{{ __('Province') }}: {{ $province }}</span>
                            <button wire:click="$set('province', '')" class="hover:text-red-500 font-bold ml-1 outline-none text-zinc-400 cursor-pointer">
                                <flux:icon name="x-mark" variant="micro" class="size-3" />
                            </button>
                        </flux:badge>
                    @endif

                    @if ($fiscalYear)
                        <flux:badge size="sm" color="indigo" class="flex items-center gap-1">
                            <span>{{ __('Fiscal Year') }}: {{ $fiscalYear }}</span>
                            <button wire:click="$set('fiscalYear', '')" class="hover:text-red-500 font-bold ml-1 outline-none text-zinc-400 cursor-pointer">
                                <flux:icon name="x-mark" variant="micro" class="size-3" />
                            </button>
                        </flux:badge>
                    @endif

                    <button wire:click="$set('category', ''); $set('province', ''); $set('fiscalYear', '');" class="text-xs text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-semibold ml-2 underline outline-none cursor-pointer">
                        {{ __('Clear All') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Content Grid --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse ($this->charts as $chart)
                <x-charts.display :chart="$chart" />
            @empty
                <div class="col-span-full py-20 text-center bg-zinc-50 dark:bg-zinc-950 rounded-2xl border border-dashed border-zinc-200 dark:border-zinc-800">
                    <flux:icon name="chart-bar" variant="outline" class="mx-auto size-16 text-zinc-300 dark:text-zinc-700 mb-4" />
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-1">{{ __('No matching insights found') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mx-auto">{{ __('Try adjusting your filters or search query to discover other public charts.') }}</p>
                    @if ($search || $category || $province || $fiscalYear)
                        <flux:button wire:click="$set('search', ''); $set('category', ''); $set('province', ''); $set('fiscalYear', '');" size="sm" variant="filled" class="mt-6">
                            {{ __('Reset Filters') }}
                        </flux:button>
                    @endif
                </div>
            @endforelse
        </div>

        @if ($this->charts->count() >= $this->perPage)
            <div class="mt-12 flex justify-center border-t border-zinc-100 dark:border-zinc-800 pt-12">
                <flux:button wire:click="$set('perPage', {{ $perPage + 9 }})" variant="filled" icon="chevron-down">
                    {{ __('Load More') }}
                </flux:button>
            </div>
        @endif
    </div>
</div>

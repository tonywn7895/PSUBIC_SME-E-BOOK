<?php

use App\Models\Ebook;
use App\Models\Spreadsheet;
use App\Support\Thailand;
use function Livewire\Volt\{computed, layout, title, state};
use Illuminate\Support\Facades\Cache;

layout('layouts.guest');
title('Explore Library');

state([
    'search' => '',
    'type' => 'all',
    'perPage' => 12,
    'category' => '',
    'province' => '',
    'fiscalYear' => '',
]);

$items = computed(function () {
    return collect()
        ->concat(
            Ebook::query()
                ->published()
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->search($this->search)
                ->category($this->category)
                ->province($this->province)
                ->fiscalYear($this->fiscalYear)
                ->select(['id', 'user_id', 'title', 'slug', 'description', 'cover_path', 'created_at'])
                ->with('user:id,name')
                ->get()
                ->map(function($ebook) {
                    return (object) [
                        'id' => $ebook->id,
                        'title' => $ebook->title,
                        'slug' => $ebook->slug ?? '',
                        'description' => $ebook->description,
                        'cover_path' => $ebook->cover_path,
                        'created_at' => $ebook->created_at,
                        'user' => (object) ['name' => $ebook->user->name ?? 'Unknown'],
                        'display_type' => 'ebook'
                    ];
                })
        )
        ->concat(
            Spreadsheet::query()
                ->where('status', 'published')
                ->whereNotNull('slug')
                ->where('slug', '!=', '')
                ->where('title', 'like', '%' . $this->search . '%')
                ->category($this->category)
                ->province($this->province)
                ->fiscalYear($this->fiscalYear)
                ->select(['id','user_id', 'title', 'slug', 'created_at'])
                ->with('user:id,name')
                ->get()
                ->map(function($table) {
                    return (object) [
                        'id' => $table->id,
                        'title' => $table->title,
                        'slug' => $table->slug ?? '',
                        'created_at' => $table->created_at,
                        'user' => (object) ['name' => $table->user->name ?? 'Unknown'],
                        'display_type' => 'table'
                    ];
                })
        )
        ->filter(function($item) {
            if (! is_object($item)) return false;
            if ($this->type === 'all') return true;
            return isset($item->display_type) && $item->display_type === $this->type;
        })
        ->sortByDesc('created_at')
        ->values()
        ->take($this->perPage);
});

?>

<div class="bg-white dark:bg-zinc-900 min-h-screen">
    {{-- Header Section --}}
    <div class="bg-zinc-50 dark:bg-zinc-950 border-b border-zinc-100 dark:border-zinc-800 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Knowledge Library') }}</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ __('เลือกดู e-books และ data tables ที่ได้รับการแบ่งปันโดยชุมชนของเรา.') }}</p>

            <div class="mt-8 flex flex-col lg:flex-row gap-4 items-center justify-between">
                <div class="w-full max-w-xl">
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        icon="magnifying-glass"
                        placeholder="{{ __('Search library...') }}"
                    />
                </div>

                <div class="flex flex-col sm:flex-row gap-4 items-center w-full lg:w-auto">
                    <div class="flex p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg shrink-0">
                        <button wire:click="$set('type', 'all')" 
                            class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $type === 'all' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
                            {{ __('All') }}
                        </button>
                        <button wire:click="$set('type', 'ebook')" 
                            class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $type === 'ebook' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
                            {{ __('E-books') }}
                        </button>
                        <button wire:click="$set('type', 'table')" 
                            class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $type === 'table' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' }}">
                            {{ __('Data Tables') }}
                        </button>
                    </div>

                    <div class="grid grid-cols-3 gap-2 w-full sm:w-auto sm:min-w-[450px]">
                        <flux:select wire:model.live="category">
                            <flux:select.option value="">{{ __('All Categories') }}</flux:select.option>
                            <flux:select.option value="sme">{{ __('SME') }}</flux:select.option>
                            <flux:select.option value="otop">{{ __('OTOP') }}</flux:select.option>
                            <flux:select.option value="startup">{{ __('Startup') }}</flux:select.option>
                        </flux:select>

                        <flux:select wire:model.live="province">
                            <flux:select.option value="">{{ __('All Provinces') }}</flux:select.option>
                            @foreach (Thailand::provinces() as $prov)
                                <flux:select.option :value="$prov">{{ $prov }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="fiscalYear">
                            <flux:select.option value="">{{ __('All Fiscal Years') }}</flux:select.option>
                            @foreach (Thailand::fiscalYears() as $year)
                                <flux:select.option :value="$year">{{ $year }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Content Grid --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if ($category || $province || $fiscalYear)
            <div class="flex flex-wrap items-center gap-2 mb-6">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mr-2">{{ __('Active Filters:') }}</span>
                
                @if ($category)
                    <flux:badge size="sm" color="zinc" class="flex items-center gap-1">
                        <span>{{ __('Category') }}: {{ strtoupper($category) }}</span>
                        <button wire:click="$set('category', '')" class="hover:text-red-500 font-bold ml-1 outline-none text-zinc-400 cursor-pointer">
                            <flux:icon name="x-mark" variant="micro" class="size-3" />
                        </button>
                    </flux:badge>
                @endif

                @if ($province)
                    <flux:badge size="sm" color="zinc" class="flex items-center gap-1">
                        <span>{{ __('Province') }}: {{ $province }}</span>
                        <button wire:click="$set('province', '')" class="hover:text-red-500 font-bold ml-1 outline-none text-zinc-400 cursor-pointer">
                            <flux:icon name="x-mark" variant="micro" class="size-3" />
                        </button>
                    </flux:badge>
                @endif

                @if ($fiscalYear)
                    <flux:badge size="sm" color="zinc" class="flex items-center gap-1">
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

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @forelse ($this->items as $item)
                @if ($item->display_type === 'ebook')
                    {{-- E-book Card --}}
                    <div class="group relative flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="aspect-[3/4] relative overflow-hidden bg-zinc-100 dark:bg-zinc-900">
                            @if ($item->cover_path)
                                <img src="{{ Storage::url($item->cover_path) }}" alt="{{ $item->title }}" class="absolute inset-0 h-full w-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-zinc-400">
                                    <flux:icon name="book-open" variant="outline" class="size-16" />
                                </div>
                            @endif

                            <div class="absolute top-2 right-2">
                                <flux:badge size="sm" color="blue" inset="top">{{ __('E-book') }}</flux:badge>
                            </div>

                            <div class="absolute inset-0 bg-black/40 opacity-0 sm:group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <flux:button :href="route('ebooks.view', $item->slug)" variant="primary" icon="eye" wire:navigate>
                                    {{ __('Read Now') }}
                                </flux:button>
                            </div>
                        </div>

                        <div class="p-4 flex-1 flex flex-col">
                            <h3 class="font-semibold text-zinc-900 dark:text-white truncate">{{ $item->title }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 flex-1">{{ $item->description }}</p>
                            
                            <div class="mt-4 sm:hidden">
                                <flux:button :href="route('ebooks.view', $item->slug)" variant="filled" size="sm" class="w-full" wire:navigate>
                                    {{ __('Read Now') }}
                                </flux:button>
                            </div>

                            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between text-xs text-zinc-400">
                                <span class="flex items-center gap-1">
                                    <flux:icon name="user" variant="micro" />
                                    {{ $item->user->name }}
                                </span>
                                <span>{{ $item->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @elseif ($item->display_type === 'table')
                    {{-- Table Card --}}
                    <div class="group relative flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-shadow">
                        <div class="aspect-[3/4] relative overflow-hidden bg-zinc-50 dark:bg-zinc-950 flex flex-col">
                            {{-- Table Preview Mimic --}}
                            <div class="p-4 space-y-2 opacity-30 group-hover:opacity-50 transition-opacity">
                                <div class="h-4 w-full bg-zinc-300 dark:bg-zinc-700 rounded"></div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="h-8 bg-zinc-200 dark:bg-zinc-800 rounded"></div>
                                    <div class="h-8 bg-zinc-200 dark:bg-zinc-800 rounded"></div>
                                    <div class="h-8 bg-zinc-200 dark:bg-zinc-800 rounded"></div>
                                </div>
                                @for($i=0; $i<4; $i++)
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="h-4 bg-zinc-100 dark:bg-zinc-900 rounded"></div>
                                    <div class="h-4 bg-zinc-100 dark:bg-zinc-900 rounded"></div>
                                    <div class="h-4 bg-zinc-100 dark:bg-zinc-900 rounded"></div>
                                </div>
                                @endfor
                            </div>
                            
                            <div class="absolute inset-0 flex items-center justify-center">
                                <flux:icon name="table-cells" variant="outline" class="size-16 text-zinc-300 group-hover:text-indigo-500 transition-colors" />
                            </div>

                            <div class="absolute top-2 right-2">
                                <flux:badge size="sm" color="indigo" inset="top">{{ __('Data Table') }}</flux:badge>
                            </div>

                            <div class="absolute inset-0 bg-black/40 opacity-0 sm:group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <flux:button :href="route('spreadsheets.view', $item->slug)" variant="primary" icon="table-cells" wire:navigate>
                                    {{ __('View Table') }}
                                </flux:button>
                            </div>
                        </div>

                        <div class="p-4 flex-1 flex flex-col">
                            <h3 class="font-semibold text-zinc-900 dark:text-white truncate">{{ $item->title }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 flex-1">{{ __('Interactive data table with sortable columns and search.') }}</p>
                            
                            <div class="mt-4 sm:hidden">
                                <flux:button :href="route('spreadsheets.view', $item->slug)" variant="filled" size="sm" class="w-full" wire:navigate>
                                    {{ __('View Table') }}
                                </flux:button>
                            </div>

                            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between text-xs text-zinc-400">
                                <span class="flex items-center gap-1">
                                    <flux:icon name="user" variant="micro" />
                                    {{ $item->user->name }}
                                </span>
                                <span>{{ $item->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            @empty
                <div class="col-span-full py-20 text-center">
                    <flux:icon name="magnifying-glass" variant="outline" class="mx-auto size-12 text-zinc-300" />
                    <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Nothing found') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('Try adjusting your search or filters.') }}</p>
                </div>
            @endforelse
        </div>
        <div class="mt-12 flex flex-col items-center justify-center pb-12 border-t border-zinc-100 dark:border-zinc-800 pt-12">
            @if ($this->items->count() >= $this->perPage)
            <flux:button wire:click="$set('perPage', {{$perPage + 12}})" variant="filled" icon="chevron-down" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ __('Load More') }}</span>
                <span wire:loading wire:target="perPage">{{ __('Loading...') }}</span>
            </flux:button>
            @else
            <div class="flex flex-col items-center">
                <flux:icon name="check-circle" variant="outline" class="size-12 text-green-500" />
                <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No more items to load.') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

<?php

use App\Models\Ebook;
use App\Models\Spreadsheet;
use App\Models\Chart;
use App\Models\CarouselImage;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{layout, title, with};

layout('layouts.guest');
title('Welcome to SME E-books');

with(fn () => [
    'latestEbooks' => Ebook::query()
        ->published()
        ->whereNotNull('slug')
        ->where('slug', '!=', '')
        ->with('user') // Eager load the user relationship to avoid N+1 queries
        ->latest()
        ->take(3)
        ->get(),
    'latestSpreadsheets' => Spreadsheet::query()
        ->where('status', 'published')
        ->whereNotNull('slug')
        ->where('slug', '!=', '')
        ->with('user') // Eager load the user relationship to avoid N+1 queries
        ->latest()
        ->take(3)
        ->get(),
    'latestCharts' => Chart::query()
        ->public()
        ->with('spreadsheet')
        ->latest()
        ->take(3)
        ->get(),
    'carouselImages' => CarouselImage::where('is_active', true)
        ->orderBy('order')
        ->pluck('image_path')
        ->map(fn($path) => Storage::url($path))
        ->toArray(),
    'stats' => [
        'ebooks' => Ebook::query()->published()->count(),
        'spreadsheets' => Spreadsheet::query()->where('status', 'published')->count(),
    ],
]);

?>

<div class="bg-white dark:bg-zinc-900 min-h-screen">
    {{-- Hero Section with Carousel Background --}}
    <div
        x-data="{
            active: 0,
            images: {{ count($carouselImages) > 0 ? json_encode($carouselImages) : json_encode([
                'https://images.unsplash.com/photo-1512820790803-83ca734da794?q=80&w=2000',
                'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?q=80&w=2000',
                'https://images.unsplash.com/photo-1507842217343-583bb7270b66?q=80&w=2000'
            ]) }},
            init() {
                setInterval(() => { this.active = (this.active + 1) % this.images.length }, 5000)
            }
        }"
        class="relative py-24 sm:py-32 border-b border-zinc-100 dark:border-zinc-800 overflow-hidden"
    >
        {{-- Carousel Background Images --}}
        <div class="absolute inset-0 z-0">
            <template x-for="(image, index) in images" :key="index">
                <div
                    x-show="active === index"
                    x-transition:enter="transition ease-out duration-1000"
                    x-transition:enter-start="opacity-0 scale-105"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-1000"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0"
                >
                    <img :src="image" class="h-full w-full object-cover" />
                    {{-- Dark Overlay --}}
                    <div class="absolute inset-0 bg-zinc-900/60 backdrop-blur-[2px]"></div>
                </div>
            </template>
        </div>

        {{-- Ambient Glowing Blurs --}}
        <div class="absolute top-1/4 left-1/4 size-96 bg-indigo-500/20 rounded-full blur-3xl animate-pulse z-0 pointer-events-none"></div>
        <div class="absolute bottom-1/4 right-1/4 size-96 bg-purple-500/20 rounded-full blur-3xl animate-pulse z-0 pointer-events-none" style="animation-delay: 2s;"></div>

        {{-- Hero Content --}}
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="backdrop-blur-lg bg-zinc-950/50 border border-white/10 rounded-3xl p-6 sm:p-12 shadow-2xl space-y-6">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-indigo-400 bg-indigo-400/10 rounded-full border border-indigo-400/20 uppercase tracking-widest">
                    <flux:icon name="sparkles" variant="micro" />
                    SME Portal
                </span>
                <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-6xl">
                    {{ __('SME E-Book & Data Platform') }}
                </h1>
                <p class="text-lg leading-8 text-zinc-300 max-w-2xl mx-auto">
                    {{ __('เสริมสร้างศักยภาพให้กับธุรกิจขนาดเล็ก, Start-Up, และ OTOP ผ่านการแบ่งปันหนังสือและตารางข้อมูลความรู้.') }}
                </p>
                <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                    <flux:button :href="route('explore')" variant="primary" icon-trailing="chevron-right" class="cursor-pointer shadow-lg hover:shadow-indigo-500/20 transition-all">
                        {{ __('สำรวจคลังความรู้') }}
                    </flux:button>
                    <flux:button href="#about" variant="ghost" class="!text-white hover:!text-white border border-white/10 hover:bg-white/10 cursor-pointer transition-all">
                        {{ __('เรียนรู้เพิ่มเติม') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Bar --}}
    <div class="relative z-20 -mt-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-2xl p-6 shadow-xl text-center">
            <div class="space-y-1">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('หนังสือเผยแพร่') }}</dt>
                <dd class="text-2xl sm:text-4xl font-bold text-zinc-900 dark:text-white">{{ $stats['ebooks'] }}</dd>
            </div>
            <div class="space-y-1 py-4 sm:py-0 border-y sm:border-y-0 sm:border-x border-zinc-100 dark:border-zinc-700">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('ตารางข้อมูลสาธารณะ') }}</dt>
                <dd class="text-2xl sm:text-4xl font-bold text-indigo-600 dark:text-indigo-400">{{ $stats['spreadsheets'] }}</dd>
            </div>
            <div class="space-y-1">
                <dt class="text-xs font-semibold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('หมวดหมู่หลัก') }}</dt>
                <dd class="text-2xl sm:text-4xl font-bold text-zinc-900 dark:text-white">3</dd>
            </div>
        </div>
    </div>

    {{-- About Us Section --}}
    <div id="about" class="py-24 bg-white dark:bg-zinc-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-2 lg:gap-16 items-center">
                <div>
                    <h2 class="text-base font-semibold tracking-wide uppercase text-indigo-600 dark:text-indigo-400">
                        {{ __('About Us') }}
                    </h2>
                    <p class="mt-2 text-3xl font-extrabold text-zinc-900 dark:text-white sm:text-4xl">
                        {{ __('เสริมสร้างชุมชน SME ที่แข็งแกร่ง') }}
                    </p>
                    <p class="mt-4 text-lg text-zinc-500 dark:text-zinc-400">
                        {{ __('แพลตฟอร์มของเราออกแบบมาเพื่อช่วยให้ธุรกิจขนาดเล็กและชุมชนท้องถิ่นแบ่งปันความเชี่ยวชาญของตน ไม่ว่าจะเป็นหนังสือคู่มือแนะนำธุรกิจในรูปแบบ E-book หรือชุดตารางข้อมูลตัวเลขดิบ (Data Tables) ที่เป็นประโยชน์ เรามอบเครื่องมือที่จำเป็นในการช่วยให้ข้อมูลของคุณเข้าถึงกลุ่มเป้าหมายได้อย่างมีประสิทธิภาพ.') }}
                    </p>
                </div>
                <div class="mt-12 lg:mt-0 relative lg:translate-x-8">
                    <div class="aspect-video rounded-2xl bg-zinc-100 dark:bg-zinc-800 overflow-hidden shadow-xl">
                        <img src="https://www.psusp.net/uploadfiles/service/d78d8916ad786257b433b0d8430f1464.jpg" class="w-full h-full object-cover" alt="Team collaborating">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Interactive Features Showcase --}}
    <div class="py-20 bg-zinc-50/50 dark:bg-zinc-950/20 border-t border-zinc-100 dark:border-zinc-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-base font-semibold tracking-wide uppercase text-indigo-600 dark:text-indigo-400">{{ __('Features') }}</h2>
                <p class="mt-2 text-3xl font-extrabold text-zinc-900 dark:text-white sm:text-4xl">{{ __('เครื่องมือแบ่งปันข้อมูลประสิทธิภาพสูง') }}</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Feature 1 --}}
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-4 hover:scale-[1.02] hover:shadow-lg transition-all duration-300">
                     <div class="size-12 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                         <flux:icon name="book-open" class="size-6" />
                     </div>
                     <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('3D Flipbook Reader') }}</h3>
                     <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('อ่านหนังสือเสมือนจริงด้วย Flipbook แบบพลิกหน้าได้ และ Scroll Mode สำหรับการอ่านบนมือถือ พร้อมระบบสารบัญภาพย่อ (Thumbnails) ค้นหาหน้าที่ต้องการได้รวดเร็ว.') }}</p>
                </div>
                
                {{-- Feature 2 --}}
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-4 hover:scale-[1.02] hover:shadow-lg transition-all duration-300">
                     <div class="size-12 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                         <flux:icon name="table-cells" class="size-6" />
                     </div>
                     <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Interactive Data Tables') }}</h3>
                     <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('แสดงผลและแก้ไขตารางข้อมูลผ่านเว็บบราวเซอร์ด้วย Univer Sheets รองรับการดูข้อมูลแบบหลายแท็บ ปรับแต่งเซลล์ คัดกรองตัวเลข และดาวน์โหลดในรูปแบบไฟล์ CSV.') }}</p>
                </div>
                
                {{-- Feature 3 --}}
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-6 space-y-4 hover:scale-[1.02] hover:shadow-lg transition-all duration-300">
                     <div class="size-12 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                         <flux:icon name="magnifying-glass" class="size-6" />
                     </div>
                     <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ __('Dynamic Search & Filter') }}</h3>
                     <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ระบบค้นหาและฟิลเตอร์อัจฉริยะ คัดกรองหนังสือและตารางข้อมูลตามหมวดหมู่ (SME/OTOP/Startup) จังหวัด หรือปีงบประมาณได้อย่างแม่นยำ.') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Latest Arrivals Section with Alpine Tabs --}}
    <div class="bg-zinc-50 dark:bg-zinc-950 py-24" x-data="{ activeTab: 'ebooks' }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
                <div>
                    <h2 class="text-3xl font-extrabold text-zinc-900 dark:text-white">{{ __('Latest Knowledge Assets') }}</h2>
                    <p class="mt-2 text-zinc-500 dark:text-zinc-400">{{ __('ลองดูเอกสารเผยแพร่และตารางสถิติล่าสุดที่แบ่งปันโดยชุมชนของเรา.') }}</p>
                </div>
                
                <div class="flex flex-wrap items-center gap-4">
                    {{-- Tab Switcher --}}
                    <div class="flex p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg shrink-0 border border-zinc-200/50 dark:border-zinc-700/50">
                        <button @click="activeTab = 'ebooks'" 
                            :class="activeTab === 'ebooks' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400'"
                            class="px-4 py-2 text-sm font-medium rounded-md transition-colors cursor-pointer"
                        >
                            {{ __('E-books') }}
                        </button>
                        <button @click="activeTab = 'tables'" 
                            :class="activeTab === 'tables' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400'"
                            class="px-4 py-2 text-sm font-medium rounded-md transition-colors cursor-pointer"
                        >
                            {{ __('Data Tables') }}
                        </button>
                        <button @click="activeTab = 'charts'" 
                            :class="activeTab === 'charts' ? 'bg-white dark:bg-zinc-700 shadow-sm text-zinc-900 dark:text-white font-semibold' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400'"
                            class="px-4 py-2 text-sm font-medium rounded-md transition-colors cursor-pointer"
                        >
                            {{ __('Insights') }}
                        </button>
                    </div>
                    
                    <flux:button x-show="activeTab !== 'charts'" :href="route('explore')" variant="ghost" icon-trailing="arrow-right">
                        {{ __('View Full Library') }}
                    </flux:button>
                    <flux:button x-show="activeTab === 'charts'" :href="route('insights')" variant="ghost" icon-trailing="arrow-right" wire:navigate x-cloak>
                        {{ __('View All Insights') }}
                    </flux:button>
                </div>
            </div>

            {{-- E-books Grid --}}
            <div x-show="activeTab === 'ebooks'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                @forelse($latestEbooks as $ebook)
                    <div class="group relative flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-all duration-300">
                        <div class="aspect-[3/4] relative overflow-hidden bg-zinc-100 dark:bg-zinc-950">
                            @if ($ebook->cover_path)
                                <img src="{{ Storage::url($ebook->cover_path) }}" alt="{{ $ebook->title }}" class="absolute inset-0 h-full w-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-zinc-400">
                                    <flux:icon name="book-open" variant="outline" class="size-12" />
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <flux:button :href="$ebook->slug ? route('ebooks.view', $ebook->slug) : '#'" variant="primary" icon="eye">
                                    {{ __('Read Now') }}
                                </flux:button>
                            </div>
                        </div>
                        <div class="p-5 flex-1 flex flex-col">
                            <h3 class="font-bold text-zinc-900 dark:text-white truncate">{{ $ebook->title }}</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 flex-1">{{ $ebook->description }}</p>
                            
                            @if ($ebook->category || $ebook->province || $ebook->fiscal_year)
                                <div class="flex flex-wrap gap-1 mt-3">
                                    @if ($ebook->category)
                                        <flux:badge size="sm" color="zinc">{{ strtoupper($ebook->category) }}</flux:badge>
                                    @endif
                                    @if ($ebook->province)
                                        <flux:badge size="sm" color="zinc">{{ $ebook->province }}</flux:badge>
                                    @endif
                                    @if ($ebook->fiscal_year)
                                        <flux:badge size="sm" color="zinc">ปี {{ $ebook->fiscal_year }}</flux:badge>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between text-xs text-zinc-400">
                                <span class="flex items-center gap-1">
                                    <flux:icon name="user" variant="micro" />
                                    {{ $ebook->user->name }}
                                 </span>
                                <span>{{ $ebook->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center">
                        <flux:icon name="book-open" variant="outline" class="mx-auto size-12 text-zinc-300" />
                        <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No E-books available') }}</h3>
                    </div>
                @endforelse
            </div>

            {{-- Spreadsheets Grid --}}
            <div x-show="activeTab === 'tables'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="grid grid-cols-1 sm:grid-cols-3 gap-8" x-cloak>
                @forelse($latestSpreadsheets as $table)
                    <div class="group relative flex flex-col bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-all duration-300">
                        <div class="aspect-[3/4] relative overflow-hidden bg-zinc-50 dark:bg-zinc-950 flex flex-col">
                            {{-- Mock Table Grid Preview --}}
                            <div class="p-4 space-y-2 opacity-30 group-hover:opacity-50 transition-opacity">
                                <div class="h-4 w-full bg-zinc-300 dark:bg-zinc-700 rounded"></div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="h-6 bg-zinc-200 dark:bg-zinc-800 rounded"></div>
                                    <div class="h-6 bg-zinc-200 dark:bg-zinc-800 rounded"></div>
                                    <div class="h-6 bg-zinc-200 dark:bg-zinc-800 rounded"></div>
                                </div>
                                @for($i=0; $i<5; $i++)
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="h-3.5 bg-zinc-100 dark:bg-zinc-900 rounded"></div>
                                        <div class="h-3.5 bg-zinc-100 dark:bg-zinc-900 rounded"></div>
                                        <div class="h-3.5 bg-zinc-100 dark:bg-zinc-900 rounded"></div>
                                    </div>
                                @endfor
                            </div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <flux:icon name="table-cells" variant="outline" class="size-16 text-zinc-300 group-hover:text-indigo-500 transition-colors" />
                            </div>
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <flux:button :href="$table->slug ? route('spreadsheets.view', $table->slug) : '#'" variant="primary" icon="table-cells">
                                    {{ __('View Table') }}
                                </flux:button>
                            </div>
                        </div>
                        
                        <div class="p-5 flex-1 flex flex-col">
                            <h3 class="font-bold text-zinc-900 dark:text-white truncate">{{ $table->title }}</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 flex-1">{{ __('Interactive data table containing statistical resources.') }}</p>
                            
                            @if ($table->category || $table->province || $table->fiscal_year)
                                <div class="flex flex-wrap gap-1 mt-3">
                                    @if ($table->category)
                                        <flux:badge size="sm" color="indigo">{{ strtoupper($table->category) }}</flux:badge>
                                    @endif
                                    @if ($table->province)
                                        <flux:badge size="sm" color="indigo">{{ $table->province }}</flux:badge>
                                    @endif
                                    @if ($table->fiscal_year)
                                        <flux:badge size="sm" color="indigo">ปี {{ $table->fiscal_year }}</flux:badge>
                                    @endif
                                </div>
                            @endif
                            
                            <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between text-xs text-zinc-400">
                                <span class="flex items-center gap-1">
                                    <flux:icon name="user" variant="micro" />
                                    {{ $table->user->name }}
                                 </span>
                                <span>{{ $table->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center">
                        <flux:icon name="table-cells" variant="outline" class="size-12 text-zinc-300" />
                        <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No Data Tables available') }}</h3>
                    </div>
                @endforelse
            </div>

            {{-- Insights/Charts Grid --}}
            <div x-show="activeTab === 'charts'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="grid grid-cols-1 md:grid-cols-3 gap-8" x-cloak>
                @forelse($latestCharts as $chart)
                    <x-charts.display :chart="$chart" />
                @empty
                    <div class="col-span-full py-20 text-center">
                        <flux:icon name="chart-bar" variant="outline" class="mx-auto size-12 text-zinc-300" />
                        <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No public charts available') }}</h3>
                        <p class="mt-1 text-sm text-zinc-500">{{ __('Check back later for public insights.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Sleek CTA Section --}}
    <div class="relative overflow-hidden bg-zinc-900 py-24 sm:py-32">
        {{-- Background decorative grid --}}
        <div class="absolute inset-0 z-0 opacity-15" style="background-image: radial-gradient(circle, #4f46e5 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 size-[600px] bg-gradient-to-tr from-indigo-500 to-purple-500 rounded-full blur-[140px] opacity-25 z-0 pointer-events-none"></div>
        
        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-6">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl">{{ __('ค้นหาข้อมูลและต่อยอดธุรกิจของคุณ') }}</h2>
            <p class="text-lg text-zinc-300 max-w-xl mx-auto">{{ __('สำรวจสถิติเชิงลึก คู่มือการค้า และตารางวิเคราะห์ความรู้ฟรี โดยไม่ต้องมีขั้นตอนลงทะเบียน.') }}</p>
            
            <div class="flex flex-wrap items-center justify-center gap-4 pt-4">
                <flux:button :href="route('explore')" variant="primary" icon="magnifying-glass" class="cursor-pointer shadow-lg hover:shadow-indigo-500/20 transition-all">
                    {{ __('ค้นหาในห้องสมุด') }}
                </flux:button>
                
                <flux:button :href="route('insights')" variant="ghost" class="!text-white hover:!text-white border border-zinc-700 hover:bg-zinc-800 cursor-pointer" wire:navigate>
                    {{ __('ดูข้อมูลสถิติ (Insights)') }}
                </flux:button>
                
                @guest
                    <flux:button :href="route('login')" variant="ghost" class="!text-white hover:!text-white border border-zinc-700 hover:bg-zinc-800 cursor-pointer">
                        {{ __('ผู้ดูแลระบบ (Admin)') }}
                    </flux:button>
                @else
                    <flux:button :href="route('dashboard')" variant="ghost" class="!text-white hover:!text-white border border-zinc-700 hover:bg-zinc-800 cursor-pointer">
                        {{ __('แดชบอร์ดจัดการ') }}
                    </flux:button>
                @endguest
            </div>
        </div>
    </div>
</div>

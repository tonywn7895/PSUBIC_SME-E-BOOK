<?php

use App\Models\Ebook;
use function Livewire\Volt\{layout, title, mount, state};

layout('layouts.guest');
title('Reading E-book');

state(['ebook' => null]);

mount(function (Ebook $ebook) {
    if ($ebook->status !== 'published') {
        abort(404);
    }
    $this->ebook = $ebook;
});

?>

<div 
    id="reader-container"
    x-data="{ 
        isLoading: true,
        progress: 0,
        totalPages: 0,
        currentPage: 1,
        hasError: false,
        errorMessage: '',
        viewMode: 'flip', // 'flip' or 'scroll'
        showThumbnails: false,
        
        sync(data) {
            if (data.progress !== undefined) this.progress = data.progress;
            if (data.totalPages !== undefined) this.totalPages = data.totalPages;
            if (data.currentPage !== undefined) this.currentPage = data.currentPage;
            if (data.isLoading !== undefined) this.isLoading = data.isLoading;
            if (data.hasError !== undefined) this.hasError = data.hasError;
            if (data.errorMessage !== undefined) this.errorMessage = data.errorMessage;
            if (data.viewMode !== undefined) this.viewMode = data.viewMode;
        },

        setMode(mode) {
            if (this.viewMode === mode) return;
            this.viewMode = mode;
            window.dispatchEvent(new CustomEvent('reader:mode-changed', { detail: { mode: this.viewMode } }));
        },

        toggleThumbnails() {
            this.showThumbnails = !this.showThumbnails;
            // Trigger a resize event to adjust flipbook size when thumbnails appear/hide
            setTimeout(() => window.dispatchEvent(new Event('resize')), 300);
        },

        goToPage(num) {
            window.dispatchEvent(new CustomEvent('reader:goto-page', { detail: { page: num } }));
        }
    }"
    x-init="
        $watch('showThumbnails', (val) => {
            if (val) {
                // Wait a small moment for Alpine DOM layout synchronization
                setTimeout(() => {
                    if (this.showThumbnails) {
                        window.dispatchEvent(new CustomEvent('reader:thumbnails-opened'));
                    }
                }, 150);
            } else {
                window.dispatchEvent(new CustomEvent('reader:thumbnails-closed'));
            }
        });

        $watch('currentPage', (val) => {
            document.querySelectorAll('#thumbnail-strip > div').forEach(el => {
                el.classList.remove('ring-2', 'ring-blue-500', 'border-blue-500', 'dark:border-blue-500');
                el.classList.add('border-zinc-300', 'dark:border-zinc-700');
            });
            const activeThumb = document.getElementById('thumb-' + val);
            if (activeThumb) {
                activeThumb.classList.remove('border-zinc-300', 'dark:border-zinc-700');
                activeThumb.classList.add('ring-2', 'ring-blue-500', 'border-blue-500', 'dark:border-blue-500');
                if (this.showThumbnails) {
                    activeThumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            }
        });
    "
    class="bg-zinc-100 dark:bg-zinc-950 h-screen max-h-screen w-full flex flex-col overflow-hidden"
>
    {{-- Top Bar --}}
    <div class="sticky top-0 left-0 right-0 h-14 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md border-b border-zinc-200 dark:border-zinc-800 z-50 flex items-center justify-between px-4">
        <div class="flex items-center gap-3">
            <flux:button :href="route('explore')" icon="arrow-left" variant="ghost" size="sm" wire:navigate />
            <h1 class="text-sm font-bold truncate max-w-[120px] sm:max-w-md">{{ $ebook->title }}</h1>
        </div>

        <div class="flex items-center gap-2">
            {{-- Mode Toggle (visible on mobile, responsive padding/text) --}}
            <div class="flex items-center bg-zinc-100 dark:bg-zinc-800 p-0.5 sm:p-1 rounded-lg mr-1 sm:mr-2">
                <button 
                    @click="setMode('flip')" 
                    :class="viewMode === 'flip' ? 'bg-white dark:bg-zinc-700 shadow-sm text-blue-600 dark:text-blue-400 font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                    class="px-2 sm:px-3 py-1 text-[9px] sm:text-[10px] font-medium uppercase tracking-wider rounded-md transition-all"
                >
                    Flip
                </button>
                <button 
                    @click="setMode('scroll')" 
                    :class="viewMode === 'scroll' ? 'bg-white dark:bg-zinc-700 shadow-sm text-blue-600 dark:text-blue-400 font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                    class="px-2 sm:px-3 py-1 text-[9px] sm:text-[10px] font-medium uppercase tracking-wider rounded-md transition-all"
                >
                    Scroll
                </button>
            </div>

            {{-- Thumbnails Toggle --}}
            <flux:button 
                @click="toggleThumbnails()" 
                icon="squares-2x2" 
                variant="ghost" 
                size="sm" 
                x-bind:class="showThumbnails ? 'text-blue-600' : ''"
            />

            {{-- Interactive Page Navigation --}}
            <div class="flex items-center gap-1.5 text-xs font-mono">
                <input 
                    type="text" 
                    inputmode="numeric"
                    pattern="[0-9]*"
                    :value="currentPage"
                    @keydown.enter="
                        let val = parseInt($el.value);
                        if (!isNaN(val) && val >= 1 && val <= totalPages) {
                            goToPage(val);
                        } else {
                            $el.value = currentPage;
                        }
                        $el.blur();
                    "
                    @blur="
                        let val = parseInt($el.value);
                        if (!isNaN(val) && val >= 1 && val <= totalPages) {
                            goToPage(val);
                        } else {
                            $el.value = currentPage;
                        }
                    "
                    class="w-10 text-center bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 focus:border-blue-500 dark:focus:border-blue-500 focus:ring-1 focus:ring-blue-500 rounded py-0.5 font-bold text-zinc-800 dark:text-zinc-200 transition-all outline-none"
                />
                <span class="text-zinc-400 dark:text-zinc-600">/</span>
                <span class="text-zinc-500 dark:text-zinc-400 min-w-[20px] text-left" x-text="totalPages">0</span>
            </div>
            @if ($ebook->spreadsheet_id && $ebook->spreadsheet && $ebook->spreadsheet->status === 'published')
                <flux:button :href="route('spreadsheets.view', $ebook->spreadsheet->slug)" target="_blank" icon="table-cells" variant="ghost" size="sm" class="hidden sm:inline-flex">
                    {{ __('View Data') }}
                </flux:button>
            @endif
            <flux:button :href="\Illuminate\Support\Facades\Storage::url($ebook->pdf_path)" target="_blank" icon="arrow-down-tray" variant="ghost" size="sm" />
        </div>
    </div>

    {{-- Main Container --}}
    <div class="flex-1 relative overflow-hidden flex flex-col">
        <div class="flex-1 relative overflow-hidden" id="pdf-view-container">
            {{-- Flipbook Mount --}}
            <div 
                x-show="viewMode === 'flip'" 
                id="pdf-flip-mount" 
                class="absolute inset-0 flex items-center justify-center p-2 sm:p-8 overflow-hidden bg-zinc-200 dark:bg-zinc-900"
            >
                <div id="flipbook" class="shadow-2xl opacity-0 transition-opacity duration-500">
                    {{-- Pages are injected here --}}
                </div>
            </div>

            {{-- Scroll Mount --}}
            <div 
                x-show="viewMode === 'scroll'" 
                id="pdf-scroll-mount" 
                class="absolute inset-0 overflow-y-auto w-full p-4 sm:p-8 space-y-6 flex flex-col items-center bg-zinc-200 dark:bg-zinc-900"
            >
                <div id="pdf-canvas-list" class="w-full flex flex-col items-center space-y-6">
                    {{-- Pages are injected here --}}
                </div>
                <div x-show="totalPages > 0 && !isLoading" class="py-12 text-zinc-500 text-xs italic">
                    {{ __('End of Document') }}
                </div>
            </div>
        </div>

        {{-- Thumbnail Strip --}}
        <div 
            x-show="showThumbnails" 
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-y-full"
            x-transition:enter-end="translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-y-0"
            x-transition:leave-end="translate-y-full"
            class="h-32 bg-white/90 dark:bg-zinc-900/90 backdrop-blur border-t border-zinc-200 dark:border-zinc-800 z-50 flex flex-col"
        >
            <div class="flex items-center justify-between px-4 py-1 border-b border-zinc-100 dark:border-zinc-800">
                <span class="text-[10px] font-bold uppercase tracking-widest text-zinc-400">{{ __('Quick Glimpse') }}</span>
                <button @click="toggleThumbnails()" class="text-zinc-400 hover:text-zinc-600"><flux:icon name="x-mark" variant="micro" /></button>
            </div>
            <div id="thumbnail-strip" class="flex-1 overflow-x-auto overflow-y-hidden flex items-center gap-4 px-4 py-2 custom-scrollbar border-t border-zinc-50 dark:border-zinc-800/50">
                {{-- Thumbnails injected here --}}
            </div>
        </div>

        {{-- Loader Layer --}}
        <div x-show="isLoading" class="fixed inset-0 bg-zinc-950 z-[60] flex flex-col items-center justify-center p-8 text-center">
            <flux:icon name="book-open" class="size-12 text-blue-500 animate-pulse mb-4" />
            <h2 class="text-white font-bold mb-2">{{ __('Loading Reader...') }}</h2>
            <div class="w-full max-w-xs bg-white/10 h-1.5 rounded-full overflow-hidden">
                <div class="bg-blue-600 h-full transition-all duration-300" :style="'width: ' + progress + '%'"></div>
            </div>
            <p class="text-[10px] text-zinc-500 mt-2 tracking-widest" x-text="progress + '%'"></p>
        </div>

        {{-- Error Layer --}}
        <div x-show="hasError" x-cloak class="fixed inset-0 bg-zinc-950 z-[70] flex flex-col items-center justify-center p-8 text-center">
            <flux:icon name="exclamation-circle" class="size-12 text-red-500 mx-auto mb-4" />
            <h2 class="text-xl font-bold text-white mb-2">{{ __('Unable to start reader') }}</h2>
            <p class="text-zinc-500 mb-6" x-text="errorMessage"></p>
            <flux:button variant="primary" onclick="location.reload()">{{ __('Try Again') }}</flux:button>
        </div>
    </div>

    @push('scripts')
        <script>
            (function() {
                if (window.__ebookReaderCleanup) {
                    window.__ebookReaderCleanup();
                }

                var engine = {
                    doc: null,
                    pages: new Map(),
                    flip: null,
                    pdfRatio: 1.41,
                    rendered: new Set(),
                    thumbnailsRendered: new Set(),
                    pdfUrl: "{{ \Illuminate\Support\Facades\Storage::disk('public')->url($ebook->pdf_path) }}",
                    workerUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js',
                    observer: null,
                    activeWindowSize: 12,
                    queue: [],
                    isProcessing: false,
                    resizeTimeout: null,
                    flipInitTimer: null,
                    modeVersion: 0,
                    booted: false,
                    listenersBound: false,
                    listeners: [],
                    thumbObserver: null
                };

                function createFlipbookElement() {
                    const flipMount = document.getElementById('pdf-flip-mount');
                    if (!flipMount) return null;

                    const oldFlipbook = document.getElementById('flipbook');
                    if (oldFlipbook) oldFlipbook.remove();

                    const flipbook = document.createElement('div');
                    flipbook.id = 'flipbook';
                    flipbook.className = 'shadow-2xl opacity-0 transition-opacity duration-500';
                    flipMount.appendChild(flipbook);

                    return flipbook;
                }

                function addTrackedListener(target, event, handler, options) {
                    target.addEventListener(event, handler, options);
                    engine.listeners.push({ target, event, handler, options });
                }

                function waitForGlobal(check, timeout = 10000) {
                    const startedAt = Date.now();

                    return new Promise((resolve, reject) => {
                        const tick = () => {
                            if (check()) {
                                resolve();
                                return;
                            }

                            if (Date.now() - startedAt > timeout) {
                                reject(new Error("Reader libraries timeout."));
                                return;
                            }

                            setTimeout(tick, 50);
                        };

                        tick();
                    });
                }

                function loadScriptOnce(id, src, check) {
                    if (check()) return Promise.resolve();

                    let script = document.getElementById(id);
                    if (!script) {
                        script = document.createElement('script');
                        script.id = id;
                        script.src = src;
                        script.async = true;
                        document.head.appendChild(script);
                    }

                    return waitForGlobal(check);
                }

                async function ensureReaderLibraries() {
                    await loadScriptOnce(
                        'pdfjs-reader-lib',
                        'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
                        () => typeof window.pdfjsLib !== 'undefined'
                    );

                    await loadScriptOnce(
                        'page-flip-reader-lib',
                        'https://cdn.jsdelivr.net/npm/page-flip@2.0.7/dist/js/page-flip.browser.min.js',
                        () => typeof window.St !== 'undefined'
                    );
                }

                async function addToQueue(num, prefix, priority = false, type = 'page') {
                    const key = prefix + num;
                    if (type === 'page' && engine.rendered.has(key)) return;
                    if (type === 'thumb' && engine.thumbnailsRendered.has(num)) return;

                    const task = { num, prefix, key, type, version: engine.modeVersion };

                    if (type === 'page') {
                        if (priority) {
                            engine.queue = engine.queue.filter(t => t.key !== key);
                            engine.queue.unshift(task);
                        } else {
                            const lastPageIndex = engine.queue.findLastIndex(t => t.type === 'page');
                            if (lastPageIndex === -1) engine.queue.unshift(task);
                            else engine.queue.splice(lastPageIndex + 1, 0, task);
                        }
                    } else {
                        if (!engine.queue.some(t => t.key === key)) {
                            engine.queue.push(task);
                        }
                    }
                    processQueue();
                }

                async function processQueue() {
                    if (engine.isProcessing || engine.queue.length === 0) return;
                    engine.isProcessing = true;

                    const task = engine.queue.shift();
                    if (task.type === 'page' && task.version === engine.modeVersion) {
                        await draw(task.num, task.prefix, task.version);
                    } else if (task.type === 'thumb') {
                        await drawThumbnail(task.num);
                    }

                    engine.isProcessing = false;
                    setTimeout(processQueue, 50);
                }

                async function getCachedPage(num) {
                    if (engine.pages.has(num)) return engine.pages.get(num);
                    if (!engine.doc) return null;
                    const page = await engine.doc.getPage(num);
                    engine.pages.set(num, page);
                    return page;
                }

                function refreshFlipbookPages(version) {
                    if (version !== engine.modeVersion || !engine.flip || typeof engine.flip.updateFromHtml !== 'function') return;

                    try {
                        engine.flip.updateFromHtml(document.querySelectorAll('#flipbook [id^="p-flip-"]'));
                    } catch (e) {
                        console.warn("PageFlip refresh skipped:", e);
                    }
                }

                function cleanupReader() {
                    teardownMode();

                    if (engine.thumbObserver) {
                        engine.thumbObserver.disconnect();
                        engine.thumbObserver = null;
                    }

                    engine.listeners.forEach(({ target, event, handler, options }) => {
                        target.removeEventListener(event, handler, options);
                    });
                    engine.listeners = [];
                    engine.listenersBound = false;
                    engine.booted = false;
                }

                window.__ebookReaderCleanup = cleanupReader;

                async function boot() {
                    if (engine.booted) return;
                    
                    // --- Robust Alpine Initialization ---
                    let alpine = null;
                    let el = null;
                    let attempts = 0;
                    while (attempts < 30) {
                        el = document.getElementById('reader-container');
                        if (el && window.Alpine) {
                            try {
                                // Check for internal Alpine v3/v4 data stack
                                if (el._x_dataStack || el.__x) {
                                    alpine = Alpine.$data(el);
                                    if (alpine && typeof alpine.sync === 'function') break;
                                }
                            } catch (e) {
                                // Not ready
                            }
                        }
                        await new Promise(r => setTimeout(r, 100));
                        attempts++;
                    }

                    if (!alpine || typeof alpine.sync !== 'function') {
                        console.error("Reader: Failed to find Alpine 'sync' function after 3 seconds.");
                        return;
                    }

                    engine.booted = true;
                    console.log("Reader: Alpine data connected successfully.");

                    try {
                        await ensureReaderLibraries();

                        pdfjsLib.GlobalWorkerOptions.workerSrc = engine.workerUrl;
                        const loadingTask = pdfjsLib.getDocument(engine.pdfUrl);
                        loadingTask.onProgress = (p) => {
                            if (p.total > 0) alpine.sync({ progress: Math.round((p.loaded / p.total) * 100) });
                        };
                        engine.doc = await loadingTask.promise;

                        const firstPage = await engine.doc.getPage(1);
                        const vp = firstPage.getViewport({ scale: 1 });
                        engine.pdfRatio = vp.height / vp.width;

                        alpine.sync({ totalPages: engine.doc.numPages, isLoading: false });

                        initUI(alpine);
                        initThumbnails(alpine);
                        if (alpine.showThumbnails) {
                            setTimeout(() => startThumbnailObservation(alpine), 100);
                        }

                        if (!engine.listenersBound) {
                            engine.listenersBound = true;
                            addTrackedListener(window, 'reader:mode-changed', () => initUI(alpine));
                            addTrackedListener(window, 'reader:goto-page', (e) => {
                                if (alpine.viewMode === 'flip' && engine.flip) engine.flip.turnToPage(e.detail.page - 1);
                                else document.getElementById('p-scroll-' + e.detail.page)?.scrollIntoView({ behavior: 'smooth' });
                            });
                            addTrackedListener(window, 'reader:thumbnails-opened', () => startThumbnailObservation(alpine));
                            addTrackedListener(window, 'reader:thumbnails-closed', () => stopThumbnailObservation());
                            addTrackedListener(window, 'resize', () => {
                                clearTimeout(engine.resizeTimeout);
                                engine.resizeTimeout = setTimeout(() => {
                                    if (alpine.viewMode === 'flip') initUI(alpine);
                                    if (alpine.showThumbnails) startThumbnailObservation(alpine);
                                }, 300);
                            });
                        }

                    } catch (err) {
                        console.error("PDF Boot Error:", err);
                        alpine.sync({ hasError: true, errorMessage: err.message, isLoading: false });
                        engine.booted = false;
                    }
                }

                function teardownMode() {
                    engine.modeVersion++;
                    engine.rendered.clear();
                    engine.queue = [];

                    clearTimeout(engine.flipInitTimer);
                    engine.flipInitTimer = null;

                    if (engine.observer) {
                        engine.observer.disconnect();
                        engine.observer = null;
                    }

                    if (engine.flip) {
                        try {
                            engine.flip.destroy();
                        } catch (e) {
                            console.warn("PageFlip destroy skipped:", e);
                        }
                        engine.flip = null;
                    }

                    createFlipbookElement();

                    const scrollList = document.getElementById('pdf-canvas-list');
                    if (scrollList) scrollList.innerHTML = '';
                }

                function initUI(alpine) {
                    Alpine.nextTick(() => {
                        teardownMode();
                        if (alpine.viewMode === 'scroll') initScrollMode(alpine);
                        else initFlipMode(alpine);
                    });
                }

                function initThumbnails(alpine) {
                    const strip = document.getElementById('thumbnail-strip');
                    strip.innerHTML = '';
                    for (let i = 1; i <= engine.doc.numPages; i++) {
                        const thumb = document.createElement('div');
                        thumb.className = 'flex-shrink-0 h-20 aspect-[1/1.41] bg-white dark:bg-zinc-800 rounded border border-zinc-300 dark:border-zinc-700 cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-300 hover:scale-105 hover:shadow-md relative group overflow-hidden';
                        thumb.id = 'thumb-' + i;
                        thumb.onclick = () => alpine.goToPage(i);
                        thumb.innerHTML = `<span class="absolute bottom-1 right-1 bg-zinc-900/75 dark:bg-zinc-950/75 backdrop-blur-sm text-white text-[9px] font-mono px-1.5 py-0.5 rounded shadow-sm">${i}</span>`;
                        strip.appendChild(thumb);
                    }

                    // Highlight the initial active page
                    const activeThumb = document.getElementById('thumb-' + alpine.currentPage);
                    if (activeThumb) {
                        activeThumb.classList.remove('border-zinc-300', 'dark:border-zinc-700');
                        activeThumb.classList.add('ring-2', 'ring-blue-500', 'border-blue-500', 'dark:border-blue-500');
                    }
                }

                function startThumbnailObservation(alpine) {
                    const strip = document.getElementById('thumbnail-strip');
                    if (!strip) return;

                    if (engine.thumbObserver) {
                        engine.thumbObserver.disconnect();
                    }

                    engine.thumbObserver = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            const num = parseInt(entry.target.id.split('-')[1]);
                            if (entry.isIntersecting) {
                                addToQueue(num, 'thumb-', false, 'thumb');
                            } else {
                                // Scrolled out of view: clean up canvas to free memory
                                const container = document.getElementById('thumb-' + num);
                                if (container) {
                                    const canvas = container.querySelector('canvas');
                                    if (canvas) {
                                        canvas.remove();
                                        engine.thumbnailsRendered.delete(num);
                                    }
                                }
                            }
                        });
                    }, { 
                        threshold: 0.01,
                        rootMargin: '300px' // Preload comfortable margin of off-screen thumbnails
                    });

                    strip.querySelectorAll('[id^="thumb-"]').forEach(t => engine.thumbObserver.observe(t));
                }

                function stopThumbnailObservation() {
                    if (engine.thumbObserver) {
                        engine.thumbObserver.disconnect();
                        engine.thumbObserver = null;
                    }
                    engine.queue = engine.queue.filter(task => task.type !== 'thumb');
                }

                function initScrollMode(alpine) {
                    const version = engine.modeVersion;
                    const mount = document.getElementById('pdf-canvas-list');
                    if (!mount) return;
                    mount.innerHTML = '';
                    for (let i = 1; i <= engine.doc.numPages; i++) {
                        const div = document.createElement('div');
                        div.id = 'p-scroll-' + i;
                        div.className = 'w-full max-w-3xl bg-white shadow-xl rounded flex items-center justify-center overflow-hidden relative';
                        div.style.aspectRatio = (1 / engine.pdfRatio);
                        div.dataset.page = i;
                        div.innerHTML = '<div class="absolute inset-0 flex items-center justify-center"><div class="size-8 border-2 border-zinc-200 dark:border-zinc-800 border-t-blue-500 rounded-full animate-spin"></div></div>';
                        mount.appendChild(div);
                    }
                    engine.observer = new IntersectionObserver((entries) => {
                        if (version !== engine.modeVersion) return;
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const num = parseInt(entry.target.dataset.page);
                                alpine.sync({ currentPage: num });
                                queueRender(num, 'p-scroll-');
                                cleanMemory(num, 'p-scroll-');
                            }
                        });
                    }, { threshold: 0.1, rootMargin: '300px' });
                    mount.querySelectorAll('[id^="p-scroll-"]').forEach(p => engine.observer.observe(p));
                }

                function initFlipMode(alpine) {
                    const version = engine.modeVersion;
                    const mount = createFlipbookElement();
                    const viewContainer = document.getElementById('pdf-view-container');
                    if (!mount || !viewContainer) return;
                    mount.style.opacity = '0';
                    const isMobile = window.innerWidth < 768;
                    for (let i = 1; i <= engine.doc.numPages; i++) {
                        const div = document.createElement('div');
                        div.id = 'p-flip-' + i;
                        div.className = 'bg-white overflow-hidden border-l border-zinc-200 shadow-inner relative';
                        div.dataset.page = i;
                        div.innerHTML = '<div class="absolute inset-0 flex items-center justify-center"><div class="size-8 border-2 border-zinc-200 dark:border-zinc-800 border-t-blue-500 rounded-full animate-spin"></div></div>';
                        mount.appendChild(div);
                    }
                    engine.flipInitTimer = setTimeout(() => {
                        if (version !== engine.modeVersion || alpine.viewMode !== 'flip') return;
                        const container = document.getElementById('flipbook');
                        if (!container) return;

                        const rect = viewContainer.getBoundingClientRect();
                        const availableWidth = rect.width - (isMobile ? 20 : 60);
                        const availableHeight = rect.height - (isMobile ? 20 : 60);

                        let width, height;
                        if (isMobile) {
                            height = Math.min(availableHeight, availableWidth * engine.pdfRatio);
                            width = height / engine.pdfRatio;
                        } else {
                            height = Math.min(availableHeight, (availableWidth / 2) * engine.pdfRatio);
                            width = (height / engine.pdfRatio) * 2;
                        }

                        const pageWidth = Math.max(300, Math.floor(width / (isMobile ? 1 : 2)));
                        const pageHeight = Math.max(400, Math.floor(height));
                        const currentPage = Math.min(Math.max(alpine.currentPage, 1), engine.doc.numPages);
                        const visiblePages = new Set([currentPage]);
                        if (!isMobile && currentPage < engine.doc.numPages) visiblePages.add(currentPage + 1);
                        if (currentPage > 1) visiblePages.add(currentPage - 1);

                        mount.querySelectorAll('[id^="p-flip-"]').forEach(page => {
                            page.style.width = pageWidth + 'px';
                            page.style.height = pageHeight + 'px';
                        });

                        Promise.all([...visiblePages].map(pageNum => draw(pageNum, 'p-flip-', version))).then(() => {
                            if (version !== engine.modeVersion || alpine.viewMode !== 'flip') return;

                        engine.flip = new St.PageFlip(container, {
                            width: pageWidth,
                            height: pageHeight,
                            size: "fixed",
                            minWidth: 300, maxWidth: 1200, minHeight: 400, maxHeight: 1600,
                            maxShadowOpacity: 0.5, showCover: true, mobileScrollSupport: false, usePortrait: isMobile,
                        });
                        engine.flip.loadFromHTML(container.querySelectorAll('[id^="p-flip-"]'));
                        container.style.opacity = '1';
                        if (currentPage > 1) {
                            engine.flip.turnToPage(currentPage - 1);
                        }
                        engine.flip.on('flip', (e) => {
                            if (version !== engine.modeVersion) return;
                            const pageNum = e.data + 1;
                            alpine.sync({ currentPage: pageNum });
                            queueRender(pageNum, 'p-flip-');
                            cleanMemory(pageNum, 'p-flip-');
                        });
                        queueRender(currentPage, 'p-flip-', true);
                        });
                    }, 200);
                }

                function queueRender(num, prefix, priority = false) {
                    addToQueue(num, prefix, priority, 'page');
                    if (num > 1) addToQueue(num - 1, prefix, priority, 'page');
                    if (num < engine.doc.numPages) addToQueue(num + 1, prefix, priority, 'page');
                    for (let i = 1; i <= 2; i++) {
                        if (num + i + 1 <= engine.doc.numPages) addToQueue(num + i + 1, prefix, false, 'page');
                    }
                }

                function cleanMemory(currentNum, prefix) {
                    const min = currentNum - engine.activeWindowSize;
                    const max = currentNum + engine.activeWindowSize;
                    engine.rendered.forEach(key => {
                        if (!key.startsWith(prefix)) return;
                        const num = parseInt(key.replace(prefix, ''));
                        if (num < min || num > max) {
                            const container = document.getElementById(key);
                            if (container) {
                                container.innerHTML = '<div class="absolute inset-0 flex items-center justify-center"><div class="size-8 border-2 border-zinc-200 dark:border-zinc-800 border-t-blue-500 rounded-full animate-spin"></div></div>';
                                engine.rendered.delete(key);
                            }
                        }
                    });
                }

                async function draw(num, prefix, version) {
                    const key = prefix + num;
                    if (version !== engine.modeVersion) return;
                    if (engine.rendered.has(key)) return;
                    const container = document.getElementById(key);
                    if (!container) return;
                    engine.rendered.add(key);
                    try {
                        const page = await getCachedPage(num);
                        if (version !== engine.modeVersion) return;
                        if (!page) throw new Error("Page not found");
                        const rect = container.getBoundingClientRect();
                        const basicViewport = page.getViewport({ scale: 1 });

                        let scale = 1.5;
                        if (rect.width > 0 && rect.height > 0) {
                            const scaleX = rect.width / basicViewport.width;
                            const scaleY = rect.height / basicViewport.height;
                            scale = Math.min(scaleX, scaleY) * window.devicePixelRatio;
                        }
                        scale = Math.min(scale, 2.0);

                        const viewport = page.getViewport({ scale: scale });
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d', { alpha: false });
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        canvas.className = 'absolute inset-0 w-full h-full object-contain opacity-0 transition-opacity duration-500';
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, canvas.width, canvas.height);
                        await page.render({ canvasContext: ctx, viewport: viewport }).promise;
                        if (version !== engine.modeVersion || !document.body.contains(container)) return;
                        if (prefix === 'p-flip-') canvas.classList.remove('opacity-0');
                        container.innerHTML = '';
                        container.appendChild(canvas);
                        if (prefix === 'p-flip-') refreshFlipbookPages(version);
                        setTimeout(() => {
                            if (version === engine.modeVersion) canvas.classList.remove('opacity-0');
                        }, 50);
                    } catch (e) {
                        console.error("Page draw fail:", e);
                        engine.rendered.delete(key);
                    }
                }

                async function drawThumbnail(num) {
                    if (engine.thumbnailsRendered.has(num)) return;
                    const container = document.getElementById('thumb-' + num);
                    if (!container) return;
                    engine.thumbnailsRendered.add(num);
                    try {
                        const page = await getCachedPage(num);
                        const viewport = page.getViewport({ scale: 0.3 });
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d', { alpha: false });
                        canvas.height = viewport.height; canvas.width = viewport.width;
                        canvas.className = 'w-full h-full object-cover opacity-0 transition-opacity duration-300';
                        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height);
                        await page.render({ canvasContext: ctx, viewport: viewport }).promise;
                        container.prepend(canvas);
                        setTimeout(() => canvas.classList.remove('opacity-0'), 50);
                    } catch (e) {
                        console.error("Thumb draw fail:", e);
                        engine.thumbnailsRendered.delete(num);
                    }
                }

                addTrackedListener(document, 'livewire:navigated', boot);
                addTrackedListener(document, 'livewire:navigating', cleanupReader);

                if (document.readyState === 'loading') {
                    addTrackedListener(document, 'DOMContentLoaded', boot, { once: true });
                } else {
                    boot();
                }
            })();
        </script>
    @endpush

    <style>
        [x-cloak] { display: none !important; }
        body { overflow: hidden; height: 100vh; } 
        .custom-scrollbar::-webkit-scrollbar { height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #3b82f6; border-radius: 10px; }
    </style>
</div>

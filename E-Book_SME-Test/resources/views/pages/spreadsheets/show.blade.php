<?php

use App\Models\Spreadsheet;
use function Livewire\Volt\{layout, title, mount, state};

layout('layouts.guest');
title('Viewing Table');

state(['spreadsheet' => null, 'snapshot' => []]);

mount(function (Spreadsheet $spreadsheet) {
    if ($spreadsheet->status !== 'published') {
        abort(404);
    }
    $this->spreadsheet = $spreadsheet;

    // On-demand conversion: if no Univer snapshot yet, build one from legacy data
    if (empty($spreadsheet->snapshot)) {
        $converted = $spreadsheet->toUniverSnapshot();
        $spreadsheet->update(['snapshot' => $converted]);
        $this->snapshot = $converted;
    } else {
        $this->snapshot = $spreadsheet->snapshot;
    }
});

?>

<div class="bg-zinc-50 dark:bg-zinc-950 min-h-screen py-12" x-data="{ hasError: false, errorMessage: '' }">
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@univerjs/preset-sheets-core/lib/index.css" />
        <style>
            #spreadsheet-viewer {
                height: 70vh;
                min-height: 400px;
            }
            @media (max-width: 767px) {
                #spreadsheet-viewer { height: 60vh; }
            }
            [x-cloak] { display: none !important; }
        </style>
    @endpush

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        {{-- Back Button --}}
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('explore') }}" icon="arrow-left" variant="ghost" size="sm" wire:navigate>
                {{ __('ย้อนกลับ') }}
            </flux:button>
        </div>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div class="text-start">
                <flux:heading size="xl" class="mb-2">{{ $spreadsheet->title }}</flux:heading>
                <div class="flex items-center gap-2">
                    <flux:icon name="user" variant="micro" class="text-zinc-400" />
                    <flux:subheading>{{ __('Shared by') }} {{ $spreadsheet->user->name }}</flux:subheading>
                </div>
            </div>

            <flux:button icon="arrow-down-tray" variant="ghost" onclick="window.exportViewerCsv()">
                {{ __('Download CSV') }}
            </flux:button>
        </div>

        <div x-show="hasError" x-cloak class="p-8 text-center bg-white dark:bg-zinc-900 rounded-xl border border-red-200">
            <flux:icon name="exclamation-triangle" class="size-12 text-red-500 mx-auto mb-4" />
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2">{{ __('Unable to Load Table') }}</h3>
            <p class="text-zinc-500 dark:text-zinc-400" x-text="errorMessage"></p>
            <flux:button class="mt-6" variant="primary" onclick="location.reload()">{{ __('Try Again') }}</flux:button>
        </div>

        <flux:card x-show="!hasError" class="p-0 overflow-hidden shadow-2xl border-zinc-200 dark:border-zinc-700">
            <div id="spreadsheet-viewer"></div>
        </flux:card>

        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 py-4 border-t border-zinc-100 dark:border-zinc-800">
            <div class="flex items-center gap-2 text-xs text-zinc-500">
                <flux:icon name="shield-check" variant="micro" />
                <p>{{ __('Secure, read-only view of the data.') }}</p>
            </div>
            <p class="text-xs text-zinc-400">{{ __('Powered by SME Platform') }}</p>
        </div>
    </div>

    @push('scripts')
        <script>
        (function () {
            'use strict';

            let _viewerAPI = null;
            let _univerInstance = null;

            const requiredScripts = [
                { src: 'https://cdn.jsdelivr.net/npm/react@18.3.1/umd/react.production.min.js', check: () => typeof React !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/react-dom@18.3.1/umd/react-dom.production.min.js', check: () => typeof ReactDOM !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/rxjs@7/dist/bundles/rxjs.umd.min.js', check: () => typeof rxjs !== 'undefined' || typeof Rx !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/@univerjs/presets/lib/umd/index.js', check: () => typeof UniverPresets !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/@univerjs/preset-sheets-core/lib/umd/index.js', check: () => typeof UniverPresetSheetsCore !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/@univerjs/preset-sheets-core/lib/umd/locales/en-US.js', check: () => typeof UniverPresetSheetsCoreEnUS !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js', check: () => typeof Papa !== 'undefined' }
            ];

            function loadScripts(scripts) {
                return scripts.reduce((promise, spec) => {
                    return promise.then(() => {
                        return new Promise((resolve, reject) => {
                            if (spec.check()) {
                                resolve();
                                return;
                            }
                            let script = document.querySelector(`script[src="${spec.src}"]`);
                            if (script) {
                                if (script.getAttribute('data-loaded') === 'true') {
                                    resolve();
                                } else {
                                    script.addEventListener('load', () => resolve());
                                    script.addEventListener('error', (e) => reject(e));
                                }
                                return;
                            }
                            script = document.createElement('script');
                            script.src = spec.src;
                            script.crossOrigin = 'anonymous';
                            script.setAttribute('data-loaded', 'false');
                            script.onload = () => {
                                script.setAttribute('data-loaded', 'true');
                                resolve();
                            };
                            script.onerror = (e) => {
                                reject(e);
                            };
                            document.head.appendChild(script);
                        });
                    });
                }, Promise.resolve());
            }

            window.exportViewerCsv = function () {
                if (!_viewerAPI) return;
                const wb = _viewerAPI.getActiveWorkbook();
                const ws = wb ? wb.getActiveSheet() : null;
                if (!ws) return;

                const rowCount = ws.getMaxRows ? ws.getMaxRows() : 100;
                const colCount = ws.getMaxColumns ? ws.getMaxColumns() : 26;
                const rows = [];

                for (let r = 0; r < rowCount; r++) {
                    const row = [];
                    let hasData = false;
                    for (let c = 0; c < colCount; c++) {
                        const cell = ws.getRange(r, c, 1, 1);
                        const val  = cell ? (cell.getValue() ?? '') : '';
                        if (val !== '') hasData = true;
                        row.push(val);
                    }
                    if (hasData) rows.push(row);
                }

                const csv  = Papa.unparse(rows);
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url  = URL.createObjectURL(blob);
                const a    = document.createElement('a');
                a.href     = url;
                a.download = 'spreadsheet.csv';
                a.click();
                URL.revokeObjectURL(url);
            };

            function destroyViewer() {
                if (_univerInstance) {
                    try {
                        _univerInstance.dispose();
                    } catch (_) { /* ignore */ }
                    _univerInstance = null;
                    _viewerAPI = null;
                } else if (_viewerAPI) {
                    try {
                        const wb = _viewerAPI.getActiveWorkbook();
                        if (wb) _viewerAPI.disposeUnit(wb.getId());
                    } catch (_) { /* ignore */ }
                    _viewerAPI = null;
                }
                const el = document.getElementById('spreadsheet-viewer');
                if (el) el.innerHTML = '';
            }

            function initViewer() {
                const el = document.getElementById('spreadsheet-viewer');
                if (!el || el.dataset.initialized === 'true') return;
                el.dataset.initialized = 'true';

                loadScripts(requiredScripts).then(() => {
                    destroyViewer();

                    const snapshot = @json($snapshot);

                    // Sanitize snapshot styles and dictionaries to objects (instead of arrays) only if they are empty arrays, preventing serialization issues
                    if (!snapshot.styles || (Array.isArray(snapshot.styles) && snapshot.styles.length === 0)) {
                        snapshot.styles = {};
                    }
                    if (snapshot.sheets) {
                        for (const sheetId in snapshot.sheets) {
                            const sheet = snapshot.sheets[sheetId];
                            if (sheet) {
                                if (!sheet.cellData || (Array.isArray(sheet.cellData) && sheet.cellData.length === 0)) {
                                    sheet.cellData = {};
                                }
                                if (!sheet.columnData || (Array.isArray(sheet.columnData) && sheet.columnData.length === 0)) {
                                    sheet.columnData = {};
                                }
                                if (!sheet.rowData || (Array.isArray(sheet.rowData) && sheet.rowData.length === 0)) {
                                    sheet.rowData = {};
                                }
                            }
                        }
                    }

                    try {
                        const result = UniverPresets.createUniver({
                            locale: 'en-US',
                            locales: { 'en-US': UniverPresetSheetsCoreEnUS },
                            presets: [
                                UniverPresetSheetsCore.UniverSheetsCorePreset({
                                    container: 'spreadsheet-viewer',
                                    // Hide editing UI for a clean read-only view
                                    toolbar:    false,
                                    formulaBar: false,
                                    footer:     true,
                                }),
                            ],
                        });

                        _univerInstance = result;
                        _viewerAPI = result.univerAPI;

                        // Load the snapshot
                        const wb = _viewerAPI.createUniverSheet(snapshot);

                        // Set workbook to read-only mode immediately to prevent editing interactions
                        if (wb) {
                            try {
                                if (wb.getWorkbookPermission) {
                                    wb.getWorkbookPermission().setReadOnly();
                                }
                            } catch (e) {
                                console.warn('Failed to set read-only mode on workbook:', e);
                            }
                        }

                    } catch (err) {
                        console.error('Viewer error:', err);
                        const alpineEl = document.querySelector('[x-data]');
                        if (alpineEl && alpineEl.__x) {
                            alpineEl.__x.$data.hasError     = true;
                            alpineEl.__x.$data.errorMessage = err.message || 'Failed to load spreadsheet.';
                        }
                    }
                }).catch((err) => {
                    console.error('Failed to load spreadsheet dependencies:', err);
                    const alpineEl = document.querySelector('[x-data]');
                    if (alpineEl && alpineEl.__x) {
                        alpineEl.__x.$data.hasError     = true;
                        alpineEl.__x.$data.errorMessage = 'Failed to load spreadsheet dependency scripts.';
                    }
                });
            }

            document.addEventListener('livewire:navigated', initViewer);
            document.addEventListener('livewire:navigating', destroyViewer);

            if (document.readyState === 'complete') {
                initViewer();
            } else {
                window.addEventListener('load', initViewer, { once: true });
            }
        })();
        </script>
    @endpush
</div>

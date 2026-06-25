<?php

use App\Models\Spreadsheet;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\state;
use function Livewire\Volt\title;
use function Livewire\Volt\rules;
use App\Support\Thailand;

layout('layouts.app');
title(__('Edit Table'));

state([
    'spreadsheet' => null,
    'title'       => '',
    'status'      => 'published',
    'lastSaved'   => null,
    'isSaving'    => false,
    'snapshot'    => [],
    'category'    => '',
    'province'    => '',
    'fiscal_year' => null,
]);

mount(function (Spreadsheet $spreadsheet) {
    Gate::authorize('update', $spreadsheet);

    $this->spreadsheet = $spreadsheet;
    $this->title       = $spreadsheet->title;
    $this->status      = $spreadsheet->status;
    $this->category    = $spreadsheet->category ?? '';
    $this->province    = $spreadsheet->province ?? '';
    $this->fiscal_year = $spreadsheet->fiscal_year;
    $this->lastSaved   = now()->format('H:i:s');

    // On-demand migration: if no Univer snapshot yet, convert legacy data
    if (empty($spreadsheet->snapshot)) {
        $converted = $spreadsheet->toUniverSnapshot();
        $spreadsheet->update(['snapshot' => $converted]);
        $this->snapshot = $converted;
    } else {
        $this->snapshot = $spreadsheet->snapshot;
    }
});

rules([
    'title' => ['required', 'string', 'max:255'],
    'status' => ['required', 'in:draft,published'],
    'category' => ['nullable', 'string', 'in:sme,otop,startup'],
    'province' => ['nullable', 'string'],
    'fiscal_year' => ['nullable', 'integer'],
]);

/**
 * Save the full Univer IWorkbookData snapshot sent from the browser.
 *
 * @param  array<string, mixed>  $newSnapshot
 */
$saveSnapshot = function (array $newSnapshot): void {
    $this->isSaving = true;
    $this->snapshot = $newSnapshot;

    $this->spreadsheet->update(['snapshot' => $newSnapshot]);

    $this->lastSaved = now()->format('H:i:s');
    $this->isSaving  = false;
};

/**
 * Manual save — same as auto-save but also shows a toast.
 *
 * @param  array<string, mixed>  $newSnapshot
 */
$manualSave = function (array $newSnapshot): void {
    $this->saveSnapshot($newSnapshot);
    Flux::toast(variant: 'success', text: __('Data saved successfully.'));
};

$updateSettings = function (): void {
    $this->validate();

    $this->spreadsheet->update([
        'title'  => $this->title,
        'status' => $this->status,
        'category' => $this->category ?: null,
        'province' => $this->province ?: null,
        'fiscal_year' => $this->fiscal_year ?: null,
    ]);
    Flux::toast(variant: 'success', text: __('Settings updated.'));
};

?>

<div class="space-y-6">
    @push('styles')
        {{-- Univer Sheets CSS --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@univerjs/preset-sheets-core/lib/index.css" />
        <style>
            /* ── Container sizing ────────────────────────────────────────────── */
            #spreadsheet-editor {
                height: 75vh;
                min-height: 500px;
            }
            /* ── Keep Univer inside our rounded card ─────────────────────────── */
            .univer-editor-wrap {
                overflow: hidden;
                border-radius: 0.5rem;
            }
        </style>
    @endpush

    {{-- ── Top bar ── --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <flux:heading size="xl">{{ $title }}</flux:heading>
                <flux:badge size="sm" inset="top" :color="$status === 'published' ? 'green' : 'zinc'">
                    {{ ucfirst($status) }}
                </flux:badge>
            </div>
            <flux:subheading>
                <div class="flex items-center gap-2">
                    <flux:icon name="clock" variant="micro" class="text-zinc-400" />
                    {{ __('Last saved at') }} {{ $lastSaved }}
                    <span wire:loading wire:target="saveSnapshot" class="flex items-center gap-1 ml-2 text-indigo-500 font-medium animate-pulse text-xs">
                        <flux:icon name="arrow-path" class="size-3 animate-spin" />
                        {{ __('Saving...') }}
                    </span>
                </div>
            </flux:subheading>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button icon="check" variant="primary" onclick="window.manualSaveSpreadsheet()" wire:loading.attr="disabled">
                {{ __('Save Data') }}
            </flux:button>

            <flux:button.group>
                <flux:button icon="arrow-up-on-square" variant="ghost" onclick="document.getElementById('spreadsheet-import').click()">
                    {{ __('Import') }}
                </flux:button>
                <flux:button icon="arrow-down-tray" variant="ghost" onclick="window.exportSpreadsheet()">
                    {{ __('Export CSV') }}
                </flux:button>
            </flux:button.group>

            <input type="file" id="spreadsheet-import" accept=".csv,.xlsx" class="hidden" onchange="window.importFile(this)">

            <flux:separator vertical class="mx-2 hidden lg:block" />

            <flux:button :href="route('spreadsheets.view', ['spreadsheet' => $spreadsheet->slug])" target="_blank" icon="eye" variant="ghost">
                {{ __('View Public') }}
            </flux:button>

            <flux:modal.trigger name="table-settings">
                <flux:button icon="cog-6-tooth" variant="ghost">{{ __('Settings') }}</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    {{-- ── Univer editor ── --}}
    <flux:card class="p-0 overflow-hidden border-zinc-200 dark:border-zinc-700 shadow-lg univer-editor-wrap">
        <div wire:ignore id="spreadsheet-editor"></div>
    </flux:card>

    <flux:callout variant="info" icon="information-circle">
        <div class="text-sm">
            <strong>{{ __('Tips:') }}</strong>
            {{ __('Use the built-in toolbar to format cells (bold, italic, color, merge). Type') }}
            <code class="bg-blue-50 dark:bg-blue-900/30 px-1 rounded">=SUM(A1:B1)</code>
            {{ __('for formulas. Data auto-saves every 2 seconds.') }}
        </div>
    </flux:callout>

    {{-- Settings Modal --}}
    <flux:modal name="table-settings" class="max-w-md">
        <form wire:submit="updateSettings" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Table Settings') }}</flux:heading>
                <flux:subheading>{{ __('Update basic information about this table.') }}</flux:subheading>
            </div>

            <flux:input wire:model="title" :label="__('Title')" required />

            <flux:radio.group wire:model="status" :label="__('Visibility')" variant="cards" class="flex-col">
                <flux:radio value="published" :label="__('Public')" :description="__('Anyone with the link can view')" />
                <flux:radio value="draft"     :label="__('Private')" :description="__('Only you can view and edit')" />
            </flux:radio.group>

            <flux:select wire:model="category" :label="__('Category (Optional)')" placeholder="{{ __('Select a category') }}">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                <flux:select.option value="sme">{{ __('SME') }}</flux:select.option>
                <flux:select.option value="otop">{{ __('OTOP') }}</flux:select.option>
                <flux:select.option value="startup">{{ __('Startup') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model="province" :label="__('Province (Optional)')" placeholder="{{ __('Select a province') }}">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach (Thailand::provinces() as $prov)
                    <flux:select.option :value="$prov">{{ $prov }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="fiscal_year" :label="__('Fiscal Year (Optional)')" placeholder="{{ __('Select a fiscal year') }}">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach (Thailand::fiscalYears() as $year)
                    <flux:select.option :value="$year">{{ $year }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ __('Save Changes') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    @push('scripts')
        <script>
        (function () {
            'use strict';

            /* ─────────────────────────────────────────────────────────────────────
             * State shared between init and event handlers
             * ─────────────────────────────────────────────────────────────────── */
            let _univerAPI        = null;   // FUniver facade
            let _univerInstance   = null;
            let _debounceId       = null;
            let _lastSnapshotJson = null;   // Local JSON cache to prevent duplicate saves

            const requiredScripts = [
                { src: 'https://cdn.jsdelivr.net/npm/react@18.3.1/umd/react.production.min.js', check: () => typeof React !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/react-dom@18.3.1/umd/react-dom.production.min.js', check: () => typeof ReactDOM !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/rxjs@7/dist/bundles/rxjs.umd.min.js', check: () => typeof rxjs !== 'undefined' || typeof Rx !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/@univerjs/presets/lib/umd/index.js', check: () => typeof UniverPresets !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/@univerjs/preset-sheets-core/lib/umd/index.js', check: () => typeof UniverPresetSheetsCore !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/@univerjs/preset-sheets-core/lib/umd/locales/en-US.js', check: () => typeof UniverPresetSheetsCoreEnUS !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js', check: () => typeof Papa !== 'undefined' },
                { src: 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js', check: () => typeof XLSX !== 'undefined' }
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

            /* ─────────────────────────────────────────────────────────────────────
             * Debounced auto-save — sends full snapshot to the Livewire component
             * ─────────────────────────────────────────────────────────────────── */
            function autoSave() {
                clearTimeout(_debounceId);
                _debounceId = setTimeout(function () {
                    if (!_univerAPI) return;
                    const wb       = _univerAPI.getActiveWorkbook();
                    if (!wb) return;
                    const snapshot = wb.save();
                    
                    // Compare current snapshot JSON with cache to avoid redundant saves
                    const snapshotJson = JSON.stringify(snapshot);
                    if (snapshotJson === _lastSnapshotJson) {
                         return;
                    }
                    _lastSnapshotJson = snapshotJson;
                    @this.saveSnapshot(snapshot);
                }, 2000);
            }

            /* ─────────────────────────────────────────────────────────────────────
             * Manual save — called by the "Save Data" button in the toolbar
             * ─────────────────────────────────────────────────────────────────── */
            window.manualSaveSpreadsheet = function () {
                if (!_univerAPI) return;
                const wb = _univerAPI.getActiveWorkbook();
                if (!wb) return;
                const snapshot = wb.save();
                @this.manualSave(snapshot);
            };

            /* ─────────────────────────────────────────────────────────────────────
             * Export active sheet as CSV
             * ─────────────────────────────────────────────────────────────────── */
            window.exportSpreadsheet = function () {
                if (!_univerAPI) return;
                const wb    = _univerAPI.getActiveWorkbook();
                const ws    = wb ? wb.getActiveSheet() : null;
                if (!ws) return;

                // Collect cell values into a 2D array
                const rowCount = ws.getMaxRows ? ws.getMaxRows() : 100;
                const colCount = ws.getMaxColumns ? ws.getMaxColumns() : 26;
                const rows     = [];

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

            /* ─────────────────────────────────────────────────────────────────────
             * Import CSV / XLSX into the active workbook
             * ─────────────────────────────────────────────────────────────────── */
            window.importFile = function (input) {
                const file = input.files[0];
                if (!file || !_univerAPI) return;
                const ext = file.name.split('.').pop().toLowerCase();

                function loadRows(rows) {
                    const wb = _univerAPI.getActiveWorkbook();
                    const ws = wb ? wb.getActiveSheet() : null;
                    if (!ws || !rows.length) return;
                    rows.forEach(function (row, r) {
                        (row || []).forEach(function (val, c) {
                            ws.getRange(r, c, 1, 1).setValue(String(val ?? ''));
                        });
                    });
                    autoSave();
                }

                if (ext === 'xlsx') {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const data   = new Uint8Array(e.target.result);
                        const wb     = XLSX.read(data, { type: 'array' });
                        const ws     = wb.Sheets[wb.SheetNames[0]];
                        const json   = XLSX.utils.sheet_to_json(ws, { header: 1 });
                        loadRows(json);
                    };
                    reader.readAsArrayBuffer(file);
                } else if (ext === 'csv') {
                    Papa.parse(file, {
                        complete: function (r) { loadRows(r.data); },
                        error:    function (e) { console.error('CSV error', e); },
                    });
                } else {
                    alert('Unsupported format. Please use .csv or .xlsx');
                }
                input.value = '';
            };

            /* ─────────────────────────────────────────────────────────────────────
             * Initialise Univer Sheets
             * ─────────────────────────────────────────────────────────────────── */
            function destroyUniver() {
                if (_univerInstance) {
                    try {
                        _univerInstance.dispose();
                    } catch (_) { /* ignore */ }
                    _univerInstance = null;
                    _univerAPI = null;
                } else if (_univerAPI) {
                    try {
                        const wb = _univerAPI.getActiveWorkbook();
                        if (wb) _univerAPI.disposeUnit(wb.getId());
                    } catch (_) { /* ignore */ }
                    _univerAPI = null;
                }
                const el = document.getElementById('spreadsheet-editor');
                if (el) el.innerHTML = '';
            }

            function initSpreadsheetEditor() {
                const el = document.getElementById('spreadsheet-editor');
                if (!el || el.dataset.initialized === 'true') return;
                el.dataset.initialized = 'true';

                loadScripts(requiredScripts).then(() => {
                    // Clean up any previous instance
                    destroyUniver();

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

                    // Core preset — includes formula engine (SUM, IF, VLOOKUP, etc.) built-in
                    const result = UniverPresets.createUniver({
                        locale: 'en-US',
                        locales: {
                            'en-US': UniverPresetSheetsCoreEnUS,
                        },
                        presets: [
                            UniverPresetSheetsCore.UniverSheetsCorePreset({
                                container: 'spreadsheet-editor',
                            }),
                        ],
                    });

                    _univerInstance = result;
                    _univerAPI = result.univerAPI;

                    // Load the workbook snapshot
                    _univerAPI.createUniverSheet(snapshot);
                    _lastSnapshotJson = JSON.stringify(snapshot);

                    // ── Auto-save on any cell value change ──────────────────────────
                    try {
                        _univerAPI.addEvent(
                            _univerAPI.Event.SheetValueChanged,
                            function () { autoSave(); }
                        );
                    } catch (e) { console.warn('SheetValueChanged event not available:', e); }

                    // ── Auto-save on style / alignment / border / formatting mutations ─────
                    try {
                        _univerAPI.onCommandExecuted(function (command) {
                            if (command && (command.type === 1 || command.type === 'MUTATION')) {
                                autoSave();
                            }
                        });
                    } catch (e) { console.warn('onCommandExecuted listener not available:', e); }

                    // ── Auto-save on style / skeleton changes ────────────────────────
                    try {
                        _univerAPI.addEvent(
                            _univerAPI.Event.SheetSkeletonChanged,
                            function () { autoSave(); }
                        );
                    } catch (e) { /* Event may not exist in this Univer version — safe to ignore */ }
                }).catch((err) => {
                    console.error('Failed to load spreadsheet dependencies:', err);
                });
            }

            // ── Livewire lifecycle hooks ──────────────────────────────────────────
            document.addEventListener('livewire:navigated', initSpreadsheetEditor);
            document.addEventListener('livewire:navigating', destroyUniver);

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                initSpreadsheetEditor();
            } else {
                window.addEventListener('load', initSpreadsheetEditor, { once: true });
            }
        })();
        </script>
    @endpush
</div>
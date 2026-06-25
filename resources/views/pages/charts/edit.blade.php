<?php

use App\Models\Chart;
use App\Models\Spreadsheet;
use Illuminate\Support\Facades\Gate;
use function Livewire\Volt\{state, rules, layout, title, with, computed, mount};
use Flux\Flux;

layout('layouts.app');
title(__('Edit Chart'));

state([
    'chart' => null,
    'title' => '',
    'spreadsheet_id' => '',
    'chart_type' => 'bar',
    'x_column' => '',
    'y_column' => '',
    'is_public' => false,
    'headers' => [],
    'previewData' => null,
]);

mount(function (Chart $chart) {
    Gate::authorize('update', $chart);

    $this->chart = $chart;
    $this->title = $chart->title;
    $this->spreadsheet_id = $chart->spreadsheet_id;
    $this->chart_type = $chart->chart_type;
    $this->x_column = $chart->x_column;
    $this->y_column = $chart->y_column;
    $this->is_public = (bool)$chart->is_public;

    $spreadsheet = $chart->spreadsheet;
    if ($spreadsheet) {
        $grid = $spreadsheet->getGridData();
        if (!empty($grid) && is_array($grid[0])) {
            $this->headers = [];
            foreach ($grid[0] as $index => $colName) {
                $name = trim((string)$colName);
                if ($name === '') {
                    $name = "Column " . ($index + 1);
                }
                $this->headers[$index] = $name;
            }
        }
    }

    $this->previewData = $this->calculatePreviewData();
});

with(fn () => [
    'spreadsheets' => Spreadsheet::query()->latest()->get(),
]);

$updatedSpreadsheetId = function ($value) {
    if (empty($value)) {
        $this->headers = [];
        $this->x_column = '';
        $this->y_column = '';
        $this->previewData = null;
        return;
    }

    $spreadsheet = Spreadsheet::find($value);
    if ($spreadsheet) {
        $grid = $spreadsheet->getGridData();
        if (!empty($grid) && is_array($grid[0])) {
            $this->headers = [];
            foreach ($grid[0] as $index => $colName) {
                $name = trim((string)$colName);
                if ($name === '') {
                    $name = "Column " . ($index + 1);
                }
                $this->headers[$index] = $name;
            }
        } else {
            $this->headers = [];
        }
    } else {
        $this->headers = [];
    }
    $this->x_column = '';
    $this->y_column = '';
    $this->previewData = null;
};

$updated = function ($property) {
    if (in_array($property, ['spreadsheet_id', 'x_column', 'y_column', 'chart_type'])) {
        $this->previewData = $this->calculatePreviewData();
    }
};

rules([
    'title' => ['required', 'string', 'max:255'],
    'spreadsheet_id' => ['required', 'exists:spreadsheets,id'],
    'chart_type' => ['required', 'in:bar,line,pie'],
    'x_column' => ['required', 'string'],
    'y_column' => ['required', 'string'],
    'is_public' => ['boolean'],
]);

$save = function () {
    $this->validate();

    $spreadsheet = Spreadsheet::find($this->spreadsheet_id);
    if ($spreadsheet) {
        $grid = $spreadsheet->getGridData();
        $yIdx = (int)$this->y_column;
        $hasNumeric = false;
        for ($i = 1; $i < count($grid); $i++) {
            $val = $grid[$i][$yIdx] ?? '';
            $cleanVal = trim(str_replace(',', '', (string)$val));
            if ($cleanVal !== '') {
                if (!is_numeric($cleanVal)) {
                    $this->addError('y_column', __('The selected Y column must contain numeric values only. Found non-numeric value: ":value"', ['value' => $val]));
                    return;
                }
                $hasNumeric = true;
            }
        }
        if (!$hasNumeric) {
            $this->addError('y_column', __('The selected Y column must contain at least some numeric values.'));
            return;
        }
    }

    $this->chart->update([
        'spreadsheet_id' => $this->spreadsheet_id,
        'title' => $this->title,
        'chart_type' => $this->chart_type,
        'x_column' => $this->x_column,
        'y_column' => $this->y_column,
        'is_public' => (bool)$this->is_public,
    ]);

    Flux::toast(variant: 'success', text: __('Chart updated successfully.'));
    return $this->redirectRoute('charts.index', navigate: true);
};

$calculatePreviewData = function () {
    if (empty($this->spreadsheet_id) || $this->x_column === '' || $this->y_column === '') {
        return null;
    }

    $spreadsheet = Spreadsheet::find($this->spreadsheet_id);
    if (!$spreadsheet) {
        return null;
    }

    $grid = $spreadsheet->getGridData();
    if (count($grid) <= 1) {
        return null;
    }

    $yIdx = (int)$this->y_column;

    $hasNonNumeric = false;
    $nonNumericValue = null;
    for ($i = 1; $i < count($grid); $i++) {
        $val = $grid[$i][$yIdx] ?? '';
        $cleanVal = trim(str_replace(',', '', (string)$val));
        if ($cleanVal !== '' && !is_numeric($cleanVal)) {
            $hasNonNumeric = true;
            $nonNumericValue = $val;
            break;
        }
    }

    if ($hasNonNumeric) {
        return [
            'error' => "Column contains non-numeric value: '$nonNumericValue'. Y-axis values must be numeric."
        ];
    }

    $tempChart = new Chart([
        'spreadsheet_id' => $this->spreadsheet_id,
        'x_column' => $this->x_column,
        'y_column' => $this->y_column,
    ]);

    try {
        return $tempChart->getChartData();
    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
};

?>

<div class="max-w-4xl mx-auto space-y-6">
    <flux:heading size="xl">{{ __('Edit Chart') }}</flux:heading>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Settings Form --}}
        <flux:card class="space-y-6">
            <form wire:submit="save" class="space-y-6">
                <flux:input wire:model="title" :label="__('Chart Title')" placeholder="{{ __('Enter chart title') }}" required />

                <flux:select wire:model.live="spreadsheet_id" :label="__('Spreadsheet')" placeholder="{{ __('Select spreadsheet') }}" required>
                    @foreach ($spreadsheets as $s)
                        <flux:select.option :value="$s->id">{{ $s->title }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if (!empty($headers))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:select wire:model.live="x_column" :label="__('X-Axis Column (Labels)')" placeholder="{{ __('Select column') }}" required>
                            @foreach ($headers as $idx => $name)
                                <flux:select.option :value="$idx">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model.live="y_column" :label="__('Y-Axis Column (Values)')" placeholder="{{ __('Select column') }}" required>
                            @foreach ($headers as $idx => $name)
                                <flux:select.option :value="$idx">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <flux:error name="y_column" />
                @endif

                <flux:radio.group wire:model="chart_type" :label="__('Chart Type')" variant="cards" class="flex-col gap-2">
                    <flux:radio value="bar" :label="__('Bar Chart')" :description="__('Show comparisons among discrete categories')" />
                    <flux:radio value="line" :label="__('Line Chart')" :description="__('Display trends over intervals')" />
                    <flux:radio value="pie" :label="__('Pie Chart')" :description="__('Illustrate numerical proportions')" />
                </flux:radio.group>

                <flux:checkbox wire:model="is_public" :label="__('Publish publicly')" :description="__('Visible to everyone on the Insights page')" />

                <div class="flex items-center gap-3 justify-end pt-4">
                    <flux:button :href="route('charts.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>

        {{-- Interactive Preview Card --}}
        <flux:card class="flex flex-col space-y-4">
            <flux:heading size="lg">{{ __('Live Preview') }}</flux:heading>

            @if ($this->previewData && isset($this->previewData['error']))
                <flux:callout variant="danger" title="{{ __('Data Validation Error') }}">
                    {{ $this->previewData['error'] }}
                </flux:callout>
            @endif

            <div wire:ignore x-data="{
                chartInstance: null,
                ensureChartJs(cb) {
                    if (typeof Chart !== 'undefined') {
                        cb();
                        return;
                    }
                    const src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js';
                    let script = document.querySelector(`script[src='${src}']`);
                    if (script) {
                        if (script.getAttribute('data-loaded') === 'true') {
                            cb();
                        } else {
                            script.addEventListener('load', cb);
                        }
                        return;
                    }
                    script = document.createElement('script');
                    script.src = src;
                    script.crossOrigin = 'anonymous';
                    script.setAttribute('data-loaded', 'false');
                    script.onload = () => {
                        script.setAttribute('data-loaded', 'true');
                        cb();
                    };
                    document.head.appendChild(script);
                },
                updateChart(data) {
                    if (!data || data.error) {
                        if (this.chartInstance) {
                            this.chartInstance.destroy();
                            this.chartInstance = null;
                        }
                        return;
                    }

                    this.ensureChartJs(() => {
                        const ctx = this.$refs.canvas.getContext('2d');
                        if (this.chartInstance) {
                            this.chartInstance.data.labels = data.labels;
                            this.chartInstance.data.datasets[0].data = data.values;
                            this.chartInstance.config.type = $wire.chart_type;
                            
                            if ($wire.chart_type === 'pie') {
                                this.chartInstance.data.datasets[0].backgroundColor = this.getColors(data.values.length);
                            } else {
                                this.chartInstance.data.datasets[0].backgroundColor = 'rgba(79, 70, 229, 0.6)';
                                this.chartInstance.data.datasets[0].borderColor = 'rgb(79, 70, 229)';
                            }
                            this.chartInstance.update();
                        } else {
                            this.chartInstance = new Chart(ctx, {
                                type: $wire.chart_type,
                                data: {
                                    labels: data.labels,
                                    datasets: [{
                                        label: $wire.title || 'Preview',
                                        data: data.values,
                                        backgroundColor: $wire.chart_type === 'pie' ? this.getColors(data.values.length) : 'rgba(79, 70, 229, 0.6)',
                                        borderColor: 'rgb(79, 70, 229)',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false
                                }
                            });
                        }
                    });
                },
                getColors(count) {
                    const palette = [
                        'rgba(79, 70, 229, 0.7)',
                        'rgba(236, 72, 153, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(139, 92, 246, 0.7)',
                        'rgba(107, 114, 128, 0.7)'
                    ];
                    let colors = [];
                    for (let i = 0; i < count; i++) {
                        colors.push(palette[i % palette.length]);
                    }
                    return colors;
                }
            }"
            x-init="
                $watch('$wire.chart_type', () => { if (chartInstance) { chartInstance.destroy(); chartInstance = null; } updateChart($wire.previewData); });
                $watch('$wire.title', (val) => { if (chartInstance) { chartInstance.data.datasets[0].label = val; chartInstance.update(); } });
                $watch('$wire.previewData', (data) => { updateChart(data); });
                updateChart($wire.previewData);
            "
            class="relative flex-1 w-full h-80 bg-zinc-50 dark:bg-zinc-950 rounded-xl p-4 flex items-center justify-center border border-zinc-200 dark:border-zinc-800 min-h-[300px]">
                <canvas x-ref="canvas" class="w-full h-full" x-show="$wire.previewData && !$wire.previewData.error"></canvas>
                <div x-show="!$wire.previewData || $wire.previewData.error" class="text-sm text-zinc-400 dark:text-zinc-600">
                    {{ __('Select a spreadsheet and columns to preview chart') }}
                </div>
            </div>
        </flux:card>
    </div>
</div>

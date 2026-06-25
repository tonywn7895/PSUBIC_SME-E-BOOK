@props(['chart'])

@php
    $chartData = $chart->getChartData();
    $labelsJson = json_encode($chartData['labels'] ?? []);
    $valuesJson = json_encode($chartData['values'] ?? []);
    
    $summaryItems = [];
    foreach (($chartData['labels'] ?? []) as $index => $label) {
        $val = $chartData['values'][$index] ?? 0;
        $summaryItems[] = "$label ($val)";
    }
    $summary = $chart->title . ': ' . implode(', ', $summaryItems);
@endphp

<div 
    x-data="{
        appearance: 'light',
        chartInstance: null,
        init() {
            this.appearance = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
            
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        const newAppearance = isDark ? 'dark' : 'light';
                        if (newAppearance !== this.appearance) {
                            this.appearance = newAppearance;
                            this.updateChartTheme();
                        }
                    }
                });
            });
            observer.observe(document.documentElement, { attributes: true });

            this.initChart();

            this.$cleanup(() => {
                observer.disconnect();
                if (this.chartInstance) {
                    this.chartInstance.destroy();
                }
            });
        },
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
        initChart() {
            const canvas = this.$refs.canvas;
            if (!canvas) return;

            this.ensureChartJs(() => {
                if (this.chartInstance) {
                    this.chartInstance.destroy();
                    this.chartInstance = null;
                }

                const ctx = canvas.getContext('2d');
                const type = this.$el.dataset.type;
                const title = this.$el.dataset.title;
                const labels = JSON.parse(this.$el.dataset.labels);
                const values = JSON.parse(this.$el.dataset.values);

                const palette = [
                    'rgba(79, 70, 229, 0.7)',
                    'rgba(236, 72, 153, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(107, 114, 128, 0.7)'
                ];
                
                let backgroundColors = 'rgba(79, 70, 229, 0.6)';
                let borderColors = 'rgb(79, 70, 229)';

                if (type === 'pie') {
                    backgroundColors = labels.map((_, i) => palette[i % palette.length]);
                    borderColors = '#fff';
                }

                this.chartInstance = new Chart(ctx, {
                    type: type,
                    data: {
                        labels: labels,
                        datasets: [{
                            label: title,
                            data: values,
                            backgroundColor: backgroundColors,
                            borderColor: borderColors,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: type === 'pie',
                                position: 'bottom',
                                labels: {
                                    color: this.appearance === 'dark' ? '#fff' : '#000'
                                }
                            }
                        },
                        scales: type === 'pie' ? {} : {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    color: this.appearance === 'dark' ? '#a1a1aa' : '#71717a'
                                },
                                grid: {
                                    color: this.appearance === 'dark' ? '#27272a' : '#f4f4f5'
                                }
                            },
                            x: {
                                ticks: {
                                    color: this.appearance === 'dark' ? '#a1a1aa' : '#71717a'
                                },
                                grid: {
                                    color: this.appearance === 'dark' ? '#27272a' : '#f4f4f5'
                                }
                            }
                        }
                    }
                });
            });
        },
        updateChartTheme() {
            if (!this.chartInstance) return;
            
            const isDark = this.appearance === 'dark';
            const textColor = isDark ? '#fff' : '#000';
            const tickColor = isDark ? '#a1a1aa' : '#71717a';
            const gridColor = isDark ? '#27272a' : '#f4f4f5';
            
            if (this.chartInstance.config.type !== 'pie') {
                this.chartInstance.options.scales.y.ticks.color = tickColor;
                this.chartInstance.options.scales.y.grid.color = gridColor;
                this.chartInstance.options.scales.x.ticks.color = tickColor;
                this.chartInstance.options.scales.x.grid.color = gridColor;
            }
            if (this.chartInstance.options.plugins.legend) {
                this.chartInstance.options.plugins.legend.labels.color = textColor;
            }
            this.chartInstance.update();
        }
    }"
    data-labels="{{ $labelsJson }}"
    data-values="{{ $valuesJson }}"
    data-type="{{ $chart->chart_type }}"
    data-title="{{ $chart->title }}"
    class="bg-white dark:bg-zinc-800 p-6 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm flex flex-col h-[28rem]"
>
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-zinc-900 dark:text-white truncate">{{ $chart->title }}</h3>
        <flux:badge size="sm" color="indigo">{{ ucfirst($chart->chart_type) }}</flux:badge>
    </div>

    <div class="relative flex-1 w-full h-0">
        <canvas x-ref="canvas" role="img" aria-label="{{ $summary }}" class="w-full h-full"></canvas>
    </div>

    @if ($chart->spreadsheet && $chart->spreadsheet->status === 'published')
        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700 flex justify-between items-center text-xs">
            <span class="text-zinc-500 dark:text-zinc-400 truncate max-w-[200px]" title="{{ $chart->spreadsheet->title }}">
                {{ __('Source:') }} {{ $chart->spreadsheet->title }}
            </span>
            <flux:button :href="route('spreadsheets.view', $chart->spreadsheet->slug)" variant="ghost" size="sm" icon="eye" wire:navigate class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700">
                {{ __('View Data Table') }}
            </flux:button>
        </div>
    @endif
</div>

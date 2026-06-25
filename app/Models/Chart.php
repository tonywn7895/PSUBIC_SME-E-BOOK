<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'spreadsheet_id',
        'title',
        'chart_type',
        'x_column',
        'y_column',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Get the spreadsheet linked to this chart.
     */
    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(Spreadsheet::class);
    }

    /**
     * Scope for public charts.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Get data structured for Chart.js.
     * Format: ['labels' => [...], 'values' => [...]]
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function getChartData(): array
    {
        $spreadsheet = $this->spreadsheet;
        if (!$spreadsheet) {
            return ['labels' => [], 'values' => []];
        }

        $grid = $spreadsheet->getGridData();
        if (count($grid) <= 1) {
            return ['labels' => [], 'values' => []];
        }

        $headerRow = $grid[0];

        $xIdx = is_numeric($this->x_column) ? (int)$this->x_column : array_search($this->x_column, $headerRow);
        $yIdx = is_numeric($this->y_column) ? (int)$this->y_column : array_search($this->y_column, $headerRow);

        if ($xIdx === false || $yIdx === false) {
            return ['labels' => [], 'values' => []];
        }

        $labels = [];
        $values = [];

        for ($i = 1; $i < count($grid); $i++) {
            $row = $grid[$i];

            $labelVal = $row[$xIdx] ?? '';
            $rawNumVal = $row[$yIdx] ?? '';

            $labelStr = trim((string) $labelVal);
            $cleanNumStr = trim(str_replace(',', '', (string) $rawNumVal));

            if ($labelStr === '' && $cleanNumStr === '') {
                continue;
            }

            $labels[] = $labelStr;
            $values[] = is_numeric($cleanNumStr) ? (float) $cleanNumStr : 0.0;
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}

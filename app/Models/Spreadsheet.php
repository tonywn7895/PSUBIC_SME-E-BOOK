<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Spreadsheet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'data',
        'options',
        'styles',
        'status',
        'snapshot',
        'category',
        'province',
        'fiscal_year',
    ];

    protected $casts = [
        'data' => 'array',
        'options' => 'array',
        'styles' => 'array',
        'snapshot' => 'array',
        'fiscal_year' => 'integer',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Spreadsheet $spreadsheet) {
            if (empty($spreadsheet->slug)) {
                $spreadsheet->slug = static::generateUniqueSlug($spreadsheet->title);
            }

            if (empty($spreadsheet->data)) {
                $spreadsheet->data = [
                    ['', '', '', ''],
                    ['', '', '', ''],
                    ['', '', '', ''],
                    ['', '', '', ''],
                ];
            }

            if (empty($spreadsheet->options)) {
                $spreadsheet->options = [
                    'search' => true,
                    'pagination' => 50,
                    'columnSorting' => true,
                    'columnDrag' => true,
                    'columnResize' => true,
                    'minDimensions' => [10, 15],
                ];
            }

            if (empty($spreadsheet->styles)) {
                $spreadsheet->styles = [];
            }
        });
    }

    /**
     * Generate a unique slug for the spreadsheet.
     */
    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);

        if (empty($slug)) {
            $slug = 'table-'.Str::lower(Str::random(6));
        }

        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    /**
     * Get the user that owns the spreadsheet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        return $query->when($category, function (Builder $query, string $category) {
            $query->where('category', $category);
        });
    }

    public function scopeProvince(Builder $query, ?string $province): Builder
    {
        return $query->when($province, function (Builder $query, string $province) {
            $query->where('province', $province);
        });
    }

    public function scopeFiscalYear(Builder $query, $fiscalYear): Builder
    {
        return $query->when($fiscalYear, function (Builder $query, $fiscalYear) {
            $query->where('fiscal_year', $fiscalYear);
        });
    }

    /**
     * Convert legacy jspreadsheet data (2D array + styles + options) into a
     * Univer IWorkbookData snapshot on first open. Called only when snapshot is null.
     *
     * @return array<string, mixed>
     */
    public function toUniverSnapshot(): array
    {
        $rawData = $this->data ?? [];
        $styles  = $this->styles ?? [];
        $options = $this->options ?? [];

        // Normalise to a 2D array of strings
        if (! is_array($rawData) || count($rawData) === 0) {
            $rawData = array_fill(0, 20, array_fill(0, 10, ''));
        }

        $rowCount = max(100, count($rawData) + 10);
        $colCount = max(26, count($rawData[0] ?? []) + 5);

        // Build sparse cellData from the 2D array
        $cellData = [];
        foreach ($rawData as $rowIdx => $row) {
            if (! is_array($row)) {
                continue;
            }
            foreach ($row as $colIdx => $cell) {
                $val = (string) ($cell ?? '');
                if ($val !== '') {
                    $cellData[$rowIdx][$colIdx] = ['v' => $val];
                }
            }
        }

        // Convert legacy inline-CSS style map to Univer cell style objects
        // Legacy: { "A1": "font-weight: bold; color: #ff0000;" }
        // Univer:  cellData[row][col].s = { bl: 1, cl: { rgb: "#ff0000" } }
        foreach ($styles as $cellRef => $cssString) {
            $col = ord(strtoupper($cellRef[0])) - ord('A');
            $row = (int) substr($cellRef, 1) - 1;
            if ($row < 0 || $col < 0) {
                continue;
            }

            $styleObj = [];
            if (str_contains($cssString, 'font-weight: bold')) {
                $styleObj['bl'] = 1;
            }
            if (str_contains($cssString, 'font-style: italic')) {
                $styleObj['it'] = 1;
            }
            if (str_contains($cssString, 'text-decoration: underline')) {
                $styleObj['ul'] = ['s' => 1];
            }
            if (preg_match('/color:\s*(#[0-9a-fA-F]{3,6}|rgb[^;]+)/', $cssString, $m)) {
                $styleObj['cl'] = ['rgb' => $m[1]];
            }
            if (preg_match('/background-color:\s*(#[0-9a-fA-F]{3,6}|rgb[^;]+)/', $cssString, $m)) {
                $styleObj['bg'] = ['rgb' => $m[1]];
            }
            if (preg_match('/font-size:\s*([\d.]+)px/', $cssString, $m)) {
                $styleObj['fs'] = (int) $m[1];
            }
            if (preg_match('/text-align:\s*(\w+)/', $cssString, $m)) {
                $alignMap = ['left' => 1, 'center' => 2, 'right' => 3];
                $styleObj['ht'] = $alignMap[$m[1]] ?? 1;
            }

            if (! empty($styleObj)) {
                $cellData[$row][$col]['s'] = $styleObj;
            }
        }

        // Convert legacy mergeCells { "A1": [colspan, rowspan] } → Univer mergeData array
        $mergeData = [];
        foreach ($options['mergeCells'] ?? [] as $cellRef => $span) {
            $col = ord(strtoupper($cellRef[0])) - ord('A');
            $row = (int) substr($cellRef, 1) - 1;
            if ($row < 0 || $col < 0 || ! is_array($span) || count($span) < 2) {
                continue;
            }
            $mergeData[] = [
                'startRow'    => $row,
                'startColumn' => $col,
                'endRow'      => $row + $span[1] - 1,
                'endColumn'   => $col + $span[0] - 1,
            ];
        }

        // Convert column widths { [0]: 150, ... } → Univer columnData
        $columnData = [];
        foreach ($options['colWidths'] ?? [] as $idx => $width) {
            if ($width) {
                $columnData[$idx] = ['w' => (int) $width];
            }
        }

        // Convert row heights { "0": { "height": 30 }, ... } → Univer rowData
        $rowData = [];
        foreach ($options['rows'] ?? [] as $idx => $rowMeta) {
            if (isset($rowMeta['height'])) {
                $rowData[$idx] = ['h' => (int) $rowMeta['height']];
            }
        }

        $sheetId   = 'sheet-1';
        $workbookId = 'workbook-'.$this->id;

        return [
            'id'         => $workbookId,
            'name'       => $this->title,
            'sheetOrder' => [$sheetId],
            'sheets'     => [
                $sheetId => [
                    'id'          => $sheetId,
                    'name'        => 'Sheet 1',
                    'rowCount'    => $rowCount,
                    'columnCount' => $colCount,
                    'cellData'    => $cellData,
                    'mergeData'   => $mergeData,
                    'columnData'  => $columnData,
                    'rowData'     => $rowData,
                ],
            ],
            'styles' => [],
        ];
    }

    /**
     * Get the 2D array representation of the spreadsheet data,
     * merging legacy data and Univer snapshot formats.
     *
     * @return array<int, array<int, mixed>>
     */
    public function getGridData(): array
    {
        if (!empty($this->snapshot)) {
            $sheets = $this->snapshot['sheets'] ?? [];
            $sheet = reset($sheets);
            if ($sheet) {
                $cellData = $sheet['cellData'] ?? [];
                
                // Determine max row and column with actual data
                $maxRow = 0;
                $maxCol = 0;
                foreach ($cellData as $r => $cols) {
                    if (!is_array($cols)) {
                        continue;
                    }
                    $maxRow = max($maxRow, (int) $r);
                    foreach ($cols as $c => $cell) {
                        $maxCol = max($maxCol, (int) $c);
                    }
                }

                $grid = [];
                for ($r = 0; $r <= $maxRow; $r++) {
                    $row = [];
                    for ($c = 0; $c <= $maxCol; $c++) {
                        $row[] = $cellData[$r][$c]['v'] ?? '';
                    }
                    $grid[] = $row;
                }
                return $grid;
            }
        }

        return $this->data ?? [];
    }
}

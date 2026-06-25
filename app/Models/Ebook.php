<?php

namespace App\Models;

use Database\Factories\EbookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

#[Fillable(['user_id', 'spreadsheet_id', 'title', 'slug', 'description', 'pdf_path', 'cover_path', 'status', 'category', 'province', 'fiscal_year'])]
class Ebook extends Model
{
    /** @use HasFactory<EbookFactory> */
    use HasFactory, SoftDeletes;

    protected $casts = [
        'fiscal_year' => 'integer',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Ebook $ebook) {
            if (empty($ebook->slug)) {
                $ebook->slug = static::generateUniqueSlug($ebook->title);
            }
        });
    }

    /**
     * Generate a unique slug for the ebook.
     */
    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);

        if (empty($slug)) {
            $slug = 'ebook-'.Str::lower(Str::random(6));
        }

        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    /**
     * Get the user (company) that owns the ebook.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the linked spreadsheet for the ebook.
     */
    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(Spreadsheet::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('title', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        });
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

    protected static function booted(): void
    {
        static::saved(Fn () => static::clearExploreCache());
        static::deleted(Fn () => static::clearExploreCache());
    }

    protected static function clearExploreCache(): void
    {
        Cache::forget('explore_items');
    }
}

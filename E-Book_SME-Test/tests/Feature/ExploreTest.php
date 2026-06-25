<?php

namespace Tests\Feature;

use App\Models\Ebook;
use App\Models\Spreadsheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExploreTest extends TestCase
{
    use RefreshDatabase;

    public function test_explore_page_returns_a_successful_response(): void
    {
        $response = $this->get(route('explore'));

        $response->assertOk();
    }

    public function test_explore_search_scope_only_returns_matching_published_ebooks(): void
    {
        $user = User::factory()->create();

        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Public Export Guide',
            'description' => 'A guide for SMEs',
            'status' => 'published',
        ]);

        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Internal Notes',
            'description' => 'Private export strategy',
            'status' => 'draft',
        ]);

        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Accounting Basics',
            'description' => 'Simple bookkeeping',
            'status' => 'published',
        ]);

        $ebooks = Ebook::query()
            ->published()
            ->search('export')
            ->pluck('title');

        $this->assertTrue($ebooks->contains('Public Export Guide'));
        $this->assertFalse($ebooks->contains('Internal Notes'));
        $this->assertFalse($ebooks->contains('Accounting Basics'));
    }

    public function test_explore_page_excludes_items_without_slugs(): void
    {
        $user = User::factory()->create();

        // Ebook with slug should be visible
        $ebookWithSlug = Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Ebook With Slug',
            'slug' => 'ebook-with-slug',
            'status' => 'published',
        ]);

        // Ebook with empty slug should be filtered out
        $ebookWithEmptySlug = Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Ebook Without Slug',
            'status' => 'published',
        ]);
        \Illuminate\Support\Facades\DB::table('ebooks')
            ->where('id', $ebookWithEmptySlug->id)
            ->update(['slug' => '']);

        $response = $this->get(route('explore'));

        $response->assertOk();
        $response->assertSee('Ebook With Slug');
        $response->assertDontSee('Ebook Without Slug');
    }

    public function test_explore_filter_by_category(): void
    {
        $user = User::factory()->create();

        // SME items
        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'SME Ebook',
            'status' => 'published',
            'category' => 'sme',
        ]);
        Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'title' => 'SME Spreadsheet',
            'status' => 'published',
            'category' => 'sme',
        ]);

        // OTOP items
        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'OTOP Ebook',
            'status' => 'published',
            'category' => 'otop',
        ]);
        Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'title' => 'OTOP Spreadsheet',
            'status' => 'published',
            'category' => 'otop',
        ]);

        \Livewire\Volt\Volt::test('pages.public.explore')
            ->set('category', 'sme')
            ->assertSee('SME Ebook')
            ->assertSee('SME Spreadsheet')
            ->assertDontSee('OTOP Ebook')
            ->assertDontSee('OTOP Spreadsheet');

        \Livewire\Volt\Volt::test('pages.public.explore')
            ->set('category', 'otop')
            ->assertSee('OTOP Ebook')
            ->assertSee('OTOP Spreadsheet')
            ->assertDontSee('SME Ebook')
            ->assertDontSee('SME Spreadsheet');
    }

    public function test_explore_filter_by_province(): void
    {
        $user = User::factory()->create();

        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Bangkok Ebook',
            'status' => 'published',
            'province' => 'กรุงเทพมหานคร',
        ]);
        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Chiang Mai Ebook',
            'status' => 'published',
            'province' => 'เชียงใหม่',
        ]);

        \Livewire\Volt\Volt::test('pages.public.explore')
            ->set('province', 'กรุงเทพมหานคร')
            ->assertSee('Bangkok Ebook')
            ->assertDontSee('Chiang Mai Ebook');
    }

    public function test_explore_filter_by_fiscal_year(): void
    {
        $user = User::factory()->create();

        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Year 2569 Ebook',
            'status' => 'published',
            'fiscal_year' => 2569,
        ]);
        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Year 2568 Ebook',
            'status' => 'published',
            'fiscal_year' => 2568,
        ]);

        \Livewire\Volt\Volt::test('pages.public.explore')
            ->set('fiscalYear', 2569)
            ->assertSee('Year 2569 Ebook')
            ->assertDontSee('Year 2568 Ebook');
    }

    public function test_explore_combined_filters(): void
    {
        $user = User::factory()->create();

        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Match Ebook',
            'status' => 'published',
            'category' => 'startup',
            'province' => 'ภูเก็ต',
            'fiscal_year' => 2567,
        ]);

        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Wrong Category Ebook',
            'status' => 'published',
            'category' => 'sme',
            'province' => 'ภูเก็ต',
            'fiscal_year' => 2567,
        ]);

        Ebook::factory()->create([
            'user_id' => $user->id,
            'title' => 'Wrong Province Ebook',
            'status' => 'published',
            'category' => 'startup',
            'province' => 'กรุงเทพมหานคร',
            'fiscal_year' => 2567,
        ]);

        \Livewire\Volt\Volt::test('pages.public.explore')
            ->set('category', 'startup')
            ->set('province', 'ภูเก็ต')
            ->set('fiscalYear', 2567)
            ->assertSee('Match Ebook')
            ->assertDontSee('Wrong Category Ebook')
            ->assertDontSee('Wrong Province Ebook');
    }

    public function test_explore_page_does_not_contain_public_charts(): void
    {
        $user = User::factory()->admin()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'title' => 'Explore Spreadsheet',
            'status' => 'published',
            'category' => 'sme',
        ]);

        \App\Models\Chart::create([
            'spreadsheet_id' => $spreadsheet->id,
            'title' => 'Explore Public Chart',
            'chart_type' => 'bar',
            'x_column' => '0',
            'y_column' => '1',
            'is_public' => true,
        ]);

        \Livewire\Volt\Volt::test('pages.public.explore')
            ->assertDontSee('Explore Public Chart');
    }

    public function test_explore_page_displays_active_filter_badges_when_filters_are_set(): void
    {
        \Livewire\Volt\Volt::test('pages.public.explore')
            ->set('category', 'sme')
            ->assertSee('Active Filters:')
            ->assertSee('Category: SME')
            ->assertSee('Clear All')
            ->set('category', '')
            ->assertDontSee('Active Filters:');
    }
}


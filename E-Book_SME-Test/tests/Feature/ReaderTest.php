<?php

namespace Tests\Feature;

use App\Models\Ebook;
use App\Models\Spreadsheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_page_returns_a_successful_response_for_published_ebook(): void
    {
        $user = User::factory()->create();
        $ebook = Ebook::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'pdf_path' => 'ebooks/pdfs/test.pdf',
        ]);

        $response = $this->get(route('ebooks.view', $ebook->slug));

        $response->assertOk();
        $response->assertSee($ebook->title);
    }

    public function test_reader_page_returns_404_for_draft_ebook(): void
    {
        $user = User::factory()->create();
        $ebook = Ebook::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        $response = $this->get(route('ebooks.view', $ebook->slug));

        $response->assertStatus(404);
    }

    public function test_reader_page_displays_spreadsheet_link_if_linked_and_published(): void
    {
        $user = User::factory()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
        ]);
        $ebook = Ebook::factory()->create([
            'user_id' => $user->id,
            'spreadsheet_id' => $spreadsheet->id,
            'status' => 'published',
            'pdf_path' => 'ebooks/pdfs/test.pdf',
        ]);

        $response = $this->get(route('ebooks.view', $ebook->slug));

        $response->assertOk();
        $response->assertSee(route('spreadsheets.view', $spreadsheet->slug));
    }

    public function test_reader_page_does_not_display_spreadsheet_link_if_linked_but_draft(): void
    {
        $user = User::factory()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
        $ebook = Ebook::factory()->create([
            'user_id' => $user->id,
            'spreadsheet_id' => $spreadsheet->id,
            'status' => 'published',
            'pdf_path' => 'ebooks/pdfs/test.pdf',
        ]);

        $response = $this->get(route('ebooks.view', $ebook->slug));

        $response->assertOk();
        $response->assertDontSee(route('spreadsheets.view', $spreadsheet->slug));
    }
}

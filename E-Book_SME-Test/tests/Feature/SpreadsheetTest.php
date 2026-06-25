<?php

namespace Tests\Feature;

use App\Models\Spreadsheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpreadsheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_spreadsheet_index(): void
    {
        $response = $this->get(route('spreadsheets.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_users_can_create_a_spreadsheet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('spreadsheets.create'));

        $response->assertOk();
    }

    public function test_users_can_edit_their_own_spreadsheet(): void
    {
        $user = User::factory()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'title' => 'My Test Table',
        ]);

        $response = $this->actingAs($user)
            ->get(route('spreadsheets.edit', $spreadsheet));

        $response->assertOk();
        $response->assertSee('My Test Table');
    }

    public function test_public_can_view_published_spreadsheet(): void
    {
        $user = User::factory()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'title'   => 'Public Data',
            'status'  => 'published',
        ]);

        $response = $this->get(route('spreadsheets.view', $spreadsheet->slug));

        $response->assertOk();
        $response->assertSee('Public Data');
    }

    public function test_public_cannot_view_draft_spreadsheet(): void
    {
        $user = User::factory()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'title' => 'Private Data',
            'status' => 'draft',
        ]);

        $response = $this->get(route('spreadsheets.view', $spreadsheet->slug));

        $response->assertStatus(404);
    }

    public function test_users_can_save_spreadsheet_snapshot(): void
    {
        $user        = User::factory()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $user->id,
            'title'   => 'Snapshot Table',
        ]);

        $this->actingAs($user);

        $snapshot = [
            'id'         => 'workbook-test',
            'name'       => 'Snapshot Table',
            'sheetOrder' => ['sheet-1'],
            'sheets'     => [
                'sheet-1' => [
                    'id'          => 'sheet-1',
                    'name'        => 'Sheet 1',
                    'rowCount'    => 100,
                    'columnCount' => 26,
                    'cellData'    => [
                        '0' => ['0' => ['v' => 'Hello'], '1' => ['v' => 'World']],
                        '1' => ['0' => ['v' => 'Foo'],   '1' => ['v' => 'Bar']],
                    ],
                    'mergeData'   => [
                        ['startRow' => 0, 'startColumn' => 0, 'endRow' => 0, 'endColumn' => 1],
                    ],
                ],
            ],
            'styles' => [],
        ];

        \Livewire\Volt\Volt::test('pages.spreadsheets.edit', ['spreadsheet' => $spreadsheet])
            ->call('saveSnapshot', $snapshot)
            ->assertHasNoErrors();

        $spreadsheet->refresh();

        $this->assertNotNull($spreadsheet->snapshot);
        $this->assertArrayHasKey('sheets', $spreadsheet->snapshot);
        $this->assertEquals('workbook-test', $spreadsheet->snapshot['id']);
    }

    public function test_toUniverSnapshot_converts_legacy_data(): void
    {
        $spreadsheet = Spreadsheet::factory()->create([
            'data'    => [['Name', 'Age'], ['Alice', '30']],
            'styles'  => ['A1' => 'font-weight: bold; color: #ff0000;'],
            'options' => [
                'mergeCells' => ['A1' => [2, 1]],
                'colWidths'  => [150, 100],
            ],
            'snapshot' => null,
        ]);

        $result = $spreadsheet->toUniverSnapshot();

        $this->assertArrayHasKey('sheets', $result);
        $sheet = $result['sheets']['sheet-1'];

        // Cell data should be populated
        $this->assertEquals('Name', $sheet['cellData'][0][0]['v']);
        $this->assertEquals('Age',  $sheet['cellData'][0][1]['v']);

        // Style should be converted: bold + red color
        $this->assertEquals(1, $sheet['cellData'][0][0]['s']['bl']);
        $this->assertEquals('#ff0000', $sheet['cellData'][0][0]['s']['cl']['rgb']);

        // Merge should be converted
        $this->assertCount(1, $sheet['mergeData']);
        $this->assertEquals(0, $sheet['mergeData'][0]['startRow']);
        $this->assertEquals(1, $sheet['mergeData'][0]['endColumn']);

        // Column widths should be converted
        $this->assertEquals(150, $sheet['columnData'][0]['w']);
        $this->assertEquals(100, $sheet['columnData'][1]['w']);
    }
}

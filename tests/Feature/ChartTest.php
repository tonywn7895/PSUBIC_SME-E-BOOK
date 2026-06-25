<?php

namespace Tests\Feature;

use App\Models\Chart;
use App\Models\Spreadsheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_chart_successfully(): void
    {
        $admin = User::factory()->admin()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Test Spreadsheet',
            'data' => [
                ['Month', 'Revenue'],
                ['Jan', 100],
                ['Feb', 150],
            ],
        ]);

        $this->actingAs($admin);

        \Livewire\Volt\Volt::test('pages.charts.create')
            ->set('title', 'Revenue Chart')
            ->set('spreadsheet_id', $spreadsheet->id)
            ->set('chart_type', 'bar')
            ->set('x_column', '0')
            ->set('y_column', '1')
            ->set('is_public', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('charts.index'));

        $this->assertDatabaseHas('charts', [
            'title' => 'Revenue Chart',
            'chart_type' => 'bar',
            'x_column' => '0',
            'y_column' => '1',
            'is_public' => true,
        ]);

        $chart = Chart::first();
        $chartData = $chart->getChartData();

        $this->assertEquals(['Jan', 'Feb'], $chartData['labels']);
        $this->assertEquals([100.0, 150.0], $chartData['values']);
    }

    public function test_non_admin_cannot_manage_charts(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $admin = User::factory()->admin()->create();
        $spreadsheet = Spreadsheet::factory()->create(['user_id' => $admin->id]);
        
        $chart = Chart::create([
            'spreadsheet_id' => $spreadsheet->id,
            'title' => 'Test Chart',
            'chart_type' => 'bar',
            'x_column' => '0',
            'y_column' => '1',
            'is_public' => true,
        ]);

        // Non-admin visiting admin pages gets 403
        $this->actingAs($user)
            ->get(route('charts.index'))
            ->assertStatus(403);

        $this->actingAs($user)
            ->get(route('charts.create'))
            ->assertStatus(403);

        $this->actingAs($user)
            ->get(route('charts.edit', $chart))
            ->assertStatus(403);
    }

    public function test_guests_can_see_public_charts_but_not_private_charts(): void
    {
        $admin = User::factory()->admin()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $admin->id,
            'data' => [
                ['Year', 'Count'],
                ['2025', 10],
            ]
        ]);

        $publicChart = Chart::create([
            'spreadsheet_id' => $spreadsheet->id,
            'title' => 'Public Chart',
            'chart_type' => 'bar',
            'x_column' => '0',
            'y_column' => '1',
            'is_public' => true,
        ]);

        $privateChart = Chart::create([
            'spreadsheet_id' => $spreadsheet->id,
            'title' => 'Private Chart',
            'chart_type' => 'bar',
            'x_column' => '0',
            'y_column' => '1',
            'is_public' => false,
        ]);

        $response = $this->get(route('insights'));
        $response->assertOk();
        $response->assertSee('Public Chart');
        $response->assertDontSee('Private Chart');
    }

    public function test_admin_can_edit_chart_successfully(): void
    {
        $admin = User::factory()->admin()->create();
        $spreadsheet = Spreadsheet::factory()->create([
            'user_id' => $admin->id,
            'title' => 'Test Spreadsheet',
            'data' => [
                ['Month', 'Revenue'],
                ['Jan', 100],
                ['Feb', 150],
            ],
        ]);

        $chart = Chart::create([
            'spreadsheet_id' => $spreadsheet->id,
            'title' => 'Original Title',
            'chart_type' => 'bar',
            'x_column' => '0',
            'y_column' => '1',
            'is_public' => false,
        ]);

        $this->actingAs($admin);

        \Livewire\Volt\Volt::test('pages.charts.edit', ['chart' => $chart])
            ->set('title', 'Updated Revenue Chart')
            ->set('chart_type', 'line')
            ->set('is_public', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('charts.index'));

        $this->assertDatabaseHas('charts', [
            'id' => $chart->id,
            'title' => 'Updated Revenue Chart',
            'chart_type' => 'line',
            'is_public' => true,
        ]);
    }
}

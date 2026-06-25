<?php

namespace Database\Factories;

use App\Models\Spreadsheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Spreadsheet>
 */
class SpreadsheetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'status' => 'published',
            'data' => [
                ['Header 1', 'Header 2'],
                ['Data 1', 'Data 2'],
            ],
            'options' => [
                'search' => true,
                'pagination' => 50,
                'columnSorting' => true,
                'columnDrag' => true,
                'columnResize' => true,
                'minDimensions' => [10, 15],
            ],
            'styles' => [],
        ];
    }
}

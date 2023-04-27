<?php

namespace Database\Factories;

use App\Models\Assets;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assets>
 */
class AssetsFactory extends Factory
{
    protected $model = Assets::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'id' => fake()->numberBetween(3, 10000),
            'asset_name' => fake()->text(40),
            'asset_url' => fake()->url().'/asset.js',
            'flow_id' => fake()->text(20),
            'position' => 1,
            'asset_type' => 'js',
            'is_asynchronous' => 0,
            'parent_id' => 0,
        ];
    }
}

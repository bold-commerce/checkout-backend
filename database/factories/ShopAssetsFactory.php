<?php

namespace Database\Factories;

use App\Models\ShopAssets;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopAssets>
 */
class ShopAssetsFactory extends Factory
{
    protected $model = ShopAssets::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'shop_id' => fake()->randomNumber(6),
            'asset_id' => fake()->randomNumber(6),
        ];
    }
}

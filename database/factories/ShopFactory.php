<?php

namespace Database\Factories;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shop>
 */
class ShopFactory extends Factory
{
    protected $model = Shop::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'id' => fake()->randomNumber(6),
            'platform_domain' => fake()->url(),
            'custom_domain' => fake()->url(),
            'platform_type' => fake()->text(20),
            'platform_identifier' => fake()->text(10),
            'shop_name' => fake()->text(20),
            'support_email' => fake()->email(),
            'created_at' => fake()->time('Y-m-d H:i:s'),
            'updated_at' => fake()->time('Y-m-d H:i:s'),
            'deleted_at' => null,
            'redacted_at' => null,
        ];
    }
}

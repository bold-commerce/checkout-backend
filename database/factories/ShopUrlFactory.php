<?php

namespace Database\Factories;

use App\Models\ShopUrl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopUrl>
 */
class ShopUrlFactory extends Factory
{
    protected $model = ShopUrl::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'shop_id' => fake()->randomNumber(6),
            'back_to_cart_url' => fake()->url(),
            'back_to_store_url' => fake()->url(),
            'login_url' => fake()->url(),
            'logo_url' => fake()->url(),
            'favicon_url' => fake()->url(),
        ];
    }
}

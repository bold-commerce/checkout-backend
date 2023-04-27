<?php

namespace Database\Factories;

use App\Models\ShopApiToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends Factory<ShopApiToken>
 */
class ShopApiTokenFactory extends Factory
{
    protected $model = ShopApiToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'shop_id' => fake()->randomNumber(6),
            'api_token' => Crypt::encryptString(fake()->text(60)),
        ];
    }
}

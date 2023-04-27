<?php

namespace Database\Factories;

use App\Constants\Assets as AssetsConstants;
use App\Models\Assets;
use App\Models\FrontendShop;
use App\Models\Shop;
use App\Models\ShopApiToken;
use App\Models\ShopAssets;
use App\Models\ShopUrl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Collection\Collection;

/**
 * @extends Factory<FrontendShop>
 */
class FrontendShopFactory extends Factory
{
    protected $model = FrontendShop::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $shopFactory = Shop::factory()->create();
        $shopApiTokenFactory = ShopApiToken::factory()->create(['shop_id' => $shopFactory->id]);
        $shopUrlFactory = ShopUrl::factory()->create(['shop_id' => $shopFactory->id]);

        $shopAssets = collect([
            'template' => collect(ShopAssets::factory()->create(['shop_id' => $shopFactory->getID()])),
            'children' => collect([
                'header' => collect([]),
                'body' => collect([]),
                'footer' => collect([]),
            ]),
        ]);

        return [
            'shop' => $shopFactory,
            'shopApiToken' => $shopApiTokenFactory,
            'shopAssets' => $shopAssets,
            'shopUrl' => $shopUrlFactory,
            'returnToCheckoutAfterLoginUrl' => fake()->url(),
        ];
    }
}

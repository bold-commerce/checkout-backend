<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'shop_id' => fake()->randomNumber(6),
            'event_date_time' => fake()->date('Y-m-d H:i:s.u'),
            'event_name' => fake()->text(10),
            'public_order_id' => fake()->md5(),
            'context' => json_encode([
                'some_key' => fake()->text(32),
            ]),
            'created_at' => fake()->dateTime(),
            'updated_at' => fake()->dateTime(),
        ];
    }
}

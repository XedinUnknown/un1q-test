<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of Event
 *
 * @extends Factory<TModel>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $duration = 60 * 60; // 1 hour
        return [
            'start' => now(),
            'end' => now()->addSeconds($duration),
            'frequency' => null,
            'interval' => null,
            'until' => null,
            'description' => '',
        ];
    }
}

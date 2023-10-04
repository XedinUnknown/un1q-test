<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of EventOccurrence
 *
 * @extends Factory<TModel>
 */
class EventOccurrenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = EventOccurrence::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        ];
    }
}

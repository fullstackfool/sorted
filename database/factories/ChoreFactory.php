<?php

namespace Database\Factories;

use App\Models\Chore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chore>
 */
class ChoreFactory extends Factory
{
    /**
     * Define the model's default state: a chore due every day from today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'points' => 10,
            'schedule' => Chore::ON,
            'rule' => 'FREQ=DAILY',
            'starts_on' => today(),
            'next_due_on' => today(),
        ];
    }

    /**
     * A one-off chore.
     */
    public function once(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule' => Chore::ONCE,
            'rule' => null,
            'starts_on' => null,
        ]);
    }

    /**
     * A chore due again a week after it was last done.
     */
    public function after(): static
    {
        return $this->state(fn (array $attributes) => [
            'schedule' => Chore::AFTER,
            'every' => 1,
            'unit' => 'week',
            'rule' => null,
            'starts_on' => null,
        ]);
    }
}

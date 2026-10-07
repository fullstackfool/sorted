<?php

namespace Database\Factories;

use App\Models\Chore;
use App\Models\Completion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Completion>
 */
class CompletionFactory extends Factory
{
    /**
     * Define the model's default state: a chore done today.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'chore_id' => Chore::factory(),
            'user_id' => User::factory(),
            'status' => Completion::DONE,
            'due_on' => today(),
            'points' => 10,
            'completed_at' => now(),
        ];
    }
}

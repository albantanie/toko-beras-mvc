<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PdfReport>
 */
class PdfReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'type' => $this->faker->randomElement(['sales', 'stock', 'financial']),
            'description' => $this->faker->paragraph(),
            'date_from' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'date_to' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'generated_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
            'file_path' => null,
        ];
    }

    /**
     * Indicate that the report is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
        ]);
    }

    /**
     * Indicate that the report is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'approval_notes' => $this->faker->sentence(),
        ]);
    }

    /**
     * Indicate that the report is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::factory(),
            'approved_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'approval_notes' => $this->faker->sentence(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\InquiryCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inquiry>
 */
class InquiryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-3 months', 'now');
        $seq = fake()->unique()->numberBetween(1, 9999);

        return [
            'inquiry_number' => 'INQ-'.$date->format('Ymd').'-'.str_pad($seq, 4, '0', STR_PAD_LEFT),
            'staff_id' => fake()->optional(0.7)->randomElement(User::pluck('id')->toArray() ?: [null]),
            'order_number' => fake()->optional(0.6)->numerify('ORD-########'),
            'category' => fake()->randomElement(InquiryCategory::cases()),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'status' => fake()->randomElement(['pending', 'in_progress', 'resolved', 'closed']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'internal_notes' => fake()->optional(0.3)->realText(100),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}

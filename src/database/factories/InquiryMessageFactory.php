<?php

namespace Database\Factories;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InquiryMessage>
 */
class InquiryMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $messageType = fake()->randomElement(['initial_inquiry', 'customer_reply', 'staff_reply']);
        $staffId = $messageType === 'staff_reply'
            ? User::inRandomOrder()->value('id')
            : null;

        return [
            'inquiry_id' => Inquiry::factory(),
            'staff_id' => $staffId,
            'message_type' => $messageType,
            'subject' => fake()->realText(50),
            'body' => fake()->realText(300),
        ];
    }
}

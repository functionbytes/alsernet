<?php

namespace Modules\Reviews\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Reviews\Models\ReviewReplyTemplate;

class ReviewReplyTemplateFactory extends Factory
{
    protected $model = ReviewReplyTemplate::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'body' => 'Thank you {reviewer_name} for your review at {location_name}!',
            'category' => 'general',
            'is_active' => true,
            'usage_count' => fake()->numberBetween(0, 50),
            'created_by' => User::factory(),
        ];
    }

    public function positive(): static
    {
        return $this->state([
            'name' => 'Positive Response - '.fake()->word(),
            'category' => 'positive',
            'body' => fake()->randomElement([
                'Thank you {reviewer_name} for your amazing {rating} star review! We\'re thrilled you enjoyed {specific_mention}. We look forward to serving you again!',
                'We\'re so grateful for your {rating} star feedback, {reviewer_name}! Your support means the world to us. See you soon!',
                'Thank you for the wonderful {rating} star review! We\'re delighted that {specific_mention} exceeded your expectations. Come back soon, {reviewer_name}!',
            ]),
        ]);
    }

    public function negative(): static
    {
        return $this->state([
            'name' => 'Negative Response - '.fake()->word(),
            'category' => 'negative',
            'body' => fake()->randomElement([
                'We sincerely apologize for your experience, {reviewer_name}. This is not the standard we hold ourselves to. Please contact us at {contact_email} so we can make this right.',
                'Thank you for bringing this to our attention, {reviewer_name}. We take all feedback seriously and are actively working to improve {specific_issue}. We\'d love the chance to discuss this further.',
                'We\'re truly sorry you had this experience, {reviewer_name}. Please reach out to us directly at {contact_phone} so we can resolve {specific_issue} for you.',
            ]),
        ]);
    }

    public function neutral(): static
    {
        return $this->state([
            'name' => 'Neutral Response - '.fake()->word(),
            'category' => 'neutral',
            'body' => fake()->randomElement([
                'Thank you for your {rating} star review, {reviewer_name}. We appreciate your feedback and are always looking for ways to improve.',
                'We appreciate you taking the time to share your thoughts, {reviewer_name}. Your feedback helps us serve you better.',
                'Thank you {reviewer_name} for your honest {rating} star review. We value your input and hope to see you again soon.',
            ]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
            'usage_count' => 0,
        ]);
    }

    public function popular(): static
    {
        return $this->state([
            'usage_count' => fake()->numberBetween(100, 500),
        ]);
    }
}

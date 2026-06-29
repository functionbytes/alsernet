<?php

namespace Modules\Ads\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ads\Enums\AdsStatus;
use Modules\Ads\Enums\AdsType;
use Modules\Ads\Models\Ads;

class AdsFactory extends Factory
{
    protected $model = Ads::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'key' => $this->faker->unique()->slug(2),
            'status' => AdsStatus::PUBLISHED->value,
            'open_in_new_tab' => true,
            'expired_at' => $this->faker->optional()->dateTimeBetween('+1 week', '+1 year'),
            'location' => $this->faker->optional()->word(),
            'url' => $this->faker->optional()->url(),
            'clicked' => 0,
            'order' => $this->faker->numberBetween(0, 100),
            'ads_type' => AdsType::IMAGE->value,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Ads $ad) {
            $ad->translations()->create([
                'locale' => 'es',
                'name' => $ad->name,
            ]);
        });
    }

    public function googleAdsense(): self
    {
        return $this->state(fn () => [
            'ads_type' => AdsType::GOOGLE_ADSENSE->value,
            'google_adsense_slot_id' => $this->faker->uuid(),
        ]);
    }

    public function expired(): self
    {
        return $this->state(fn () => [
            'expired_at' => now()->subWeek(),
        ]);
    }
}

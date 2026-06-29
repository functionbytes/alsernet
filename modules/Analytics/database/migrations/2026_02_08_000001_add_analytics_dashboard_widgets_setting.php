<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration ensures analytics settings exist in the settings table
        // Settings are managed through the setting() helper which uses the settings table

        // We don't need to create a new table as settings are stored in the existing settings table
        // This migration is just for documentation and can seed default values if needed

        if (Schema::hasTable('settings')) {
            $defaultSettings = [
                [
                    'key' => 'google_analytics_enable',
                    'value' => json_encode(false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'google_analytics_property_id',
                    'value' => json_encode(''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'google_analytics_credentials',
                    'value' => json_encode(''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'analytics_cache_lifetime',
                    'value' => json_encode(60),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'key' => 'analytics_dashboard_widgets',
                    'value' => json_encode(['general', 'top_pages', 'top_browsers', 'top_referrers']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            foreach ($defaultSettings as $setting) {
                // Only insert if the setting doesn't already exist
                if (! DB::table('settings')->where('key', $setting['key'])->exists()) {
                    DB::table('settings')->insert($setting);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove analytics settings from settings table
        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('key', [
                'google_analytics_enable',
                'google_analytics_property_id',
                'google_analytics_credentials',
                'analytics_cache_lifetime',
                'analytics_dashboard_widgets',
            ])->delete();
        }
    }
};

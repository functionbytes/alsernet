<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds A/B variants per campaign with weighted traffic split.
 * Adds variant_id to impressions so analytics can break down per variant.
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('helpdesk');

        if (! $schema->hasTable('helpdesk_campaign_variants')) {
            $schema->create('helpdesk_campaign_variants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('helpdesk_campaigns')->cascadeOnDelete();
                $table->string('label', 64)->comment('A, B, Control, etc.');
                $table->unsignedTinyInteger('weight')->default(50)->comment('0-100 percentage');
                $table->longText('content')->nullable();
                $table->longText('appearance')->nullable();
                $table->unsignedBigInteger('impressions_count')->default(0);
                $table->unsignedBigInteger('clicks_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['campaign_id', 'deleted_at']);
            });
        }

        if ($schema->hasTable('helpdesk_campaign_impressions')
            && ! $schema->hasColumn('helpdesk_campaign_impressions', 'variant_id')) {
            $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) {
                $table->unsignedBigInteger('variant_id')->nullable()->after('campaign_id');
                $table->index('variant_id');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('helpdesk');

        if ($schema->hasColumn('helpdesk_campaign_impressions', 'variant_id')) {
            $schema->table('helpdesk_campaign_impressions', function (Blueprint $table) {
                $table->dropColumn('variant_id');
            });
        }

        $schema->dropIfExists('helpdesk_campaign_variants');
    }
};

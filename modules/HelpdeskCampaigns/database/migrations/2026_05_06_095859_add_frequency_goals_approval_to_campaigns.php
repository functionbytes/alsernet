<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds enterprise features to helpdesk_campaigns:
 *  - Frequency capping: max_impressions_per_user, cooldown_minutes
 *  - Goal-based auto-end: goal_type, goal_value
 *  - Approval workflow: approval_required, approved_at, approved_by_user_id
 *
 * The status enum gains 'pending_approval' implicitly (status is varchar).
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('helpdesk');

        $schema->table('helpdesk_campaigns', function (Blueprint $table) use ($schema) {
            // Frequency capping
            if (! $schema->hasColumn('helpdesk_campaigns', 'max_impressions_per_user')) {
                $table->unsignedInteger('max_impressions_per_user')->nullable()->after('clicks_count');
            }
            if (! $schema->hasColumn('helpdesk_campaigns', 'cooldown_minutes')) {
                $table->unsignedInteger('cooldown_minutes')->nullable()->after('max_impressions_per_user');
            }

            // Goal-based auto-end
            if (! $schema->hasColumn('helpdesk_campaigns', 'goal_type')) {
                $table->string('goal_type', 32)->nullable()->after('cooldown_minutes')
                    ->comment('impressions|clicks|conversions|null');
            }
            if (! $schema->hasColumn('helpdesk_campaigns', 'goal_value')) {
                $table->unsignedBigInteger('goal_value')->nullable()->after('goal_type');
            }

            // Approval workflow
            if (! $schema->hasColumn('helpdesk_campaigns', 'approval_required')) {
                $table->boolean('approval_required')->default(false)->after('goal_value');
            }
            if (! $schema->hasColumn('helpdesk_campaigns', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_required');
            }
            if (! $schema->hasColumn('helpdesk_campaigns', 'approved_by_user_id')) {
                $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('approved_at');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection('helpdesk');

        $schema->table('helpdesk_campaigns', function (Blueprint $table) use ($schema) {
            foreach ([
                'approved_by_user_id', 'approved_at', 'approval_required',
                'goal_value', 'goal_type',
                'cooldown_minutes', 'max_impressions_per_user',
            ] as $col) {
                if ($schema->hasColumn('helpdesk_campaigns', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global performance indexes migration.
 *
 * Adds missing indexes identified during the global performance audit.
 * Each index targets a confirmed query pattern — no speculative additions.
 */
return new class extends Migration
{
    public function up(): void
    {
        // helpdesk_customers: created_at is used in "new_this_month" aggregation
        // and email_verified_at is used in the verified() scope filter.
        if (Schema::hasTable('helpdesk_customers')) {
            Schema::table('helpdesk_customers', function (Blueprint $table) {
                if (! $this->hasIndex('helpdesk_customers', 'helpdesk_customers_created_at_index')) {
                    $table->index('created_at', 'helpdesk_customers_created_at_index');
                }
                if (! $this->hasIndex('helpdesk_customers', 'helpdesk_customers_email_verified_at_index')) {
                    $table->index('email_verified_at', 'helpdesk_customers_email_verified_at_index');
                }
            });
        }

        // activity_log: (causer_id, event) composite index supports the common
        // audit page filter "filter by user + filter by event type".
        // The nullableMorphs() creates individual causer_id/causer_type indexes,
        // but a composite (causer_id, event) index speeds up the combined filter.
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                if (! $this->hasIndex('activity_log', 'activity_log_causer_id_event_index')) {
                    $table->index(['causer_id', 'event'], 'activity_log_causer_id_event_index');
                }
            });
        }

        // form_submission_values: value column is searched with LIKE in the
        // FormSubmissionController search filter.
        // An index on (submission_id) is already present; add (field_key) since
        // bulkAnonymize queries it with WHERE field_key LIKE ?.
        if (Schema::hasTable('form_submission_values')) {
            Schema::table('form_submission_values', function (Blueprint $table) {
                if (! $this->hasIndex('form_submission_values', 'fsv_field_key_index')) {
                    $table->index('field_key', 'fsv_field_key_index');
                }
                if (! $this->hasIndex('form_submission_values', 'fsv_field_type_index')) {
                    $table->index('field_type', 'fsv_field_type_index');
                }
            });
        }

        // seo_404_logs: last_seen_at already has an index from the create migration.
        // Add (has_redirect, last_seen_at) composite for the withoutRedirect + date
        // combination used in the admin dashboard stats.
        if (Schema::hasTable('seo_404_logs')) {
            Schema::table('seo_404_logs', function (Blueprint $table) {
                if (! $this->hasIndex('seo_404_logs', 'seo_404_logs_has_redirect_last_seen_index')) {
                    $table->index(['has_redirect', 'last_seen_at'], 'seo_404_logs_has_redirect_last_seen_index');
                }
            });
        }

        // users: (available, verified) composite supports the helpdesk agents dropdown
        // query: WHERE available = 1 AND verified = 1 ORDER BY name.
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! $this->hasIndex('users', 'users_available_verified_index')) {
                    $table->index(['available', 'verified'], 'users_available_verified_index');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('helpdesk_customers')) {
            Schema::table('helpdesk_customers', function (Blueprint $table) {
                $table->dropIndexIfExists('helpdesk_customers_created_at_index');
                $table->dropIndexIfExists('helpdesk_customers_email_verified_at_index');
            });
        }

        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                $table->dropIndexIfExists('activity_log_causer_id_event_index');
            });
        }

        if (Schema::hasTable('form_submission_values')) {
            Schema::table('form_submission_values', function (Blueprint $table) {
                $table->dropIndexIfExists('fsv_field_key_index');
                $table->dropIndexIfExists('fsv_field_type_index');
            });
        }

        if (Schema::hasTable('seo_404_logs')) {
            Schema::table('seo_404_logs', function (Blueprint $table) {
                $table->dropIndexIfExists('seo_404_logs_has_redirect_last_seen_index');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndexIfExists('users_available_verified_index');
            });
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->pluck('name')
            ->contains($name);
    }
};

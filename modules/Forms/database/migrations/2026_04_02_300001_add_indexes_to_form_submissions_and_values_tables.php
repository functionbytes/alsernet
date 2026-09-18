<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // form_submissions.assigned_to: queried in list, inbox, and count queries
        // (where('assigned_to', ...) and where('assigned_to', auth()->id()))
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->index('assigned_to');
        });

        // form_submission_values.field_key: used in FormSubmission::getFieldValue() and
        // email/PII detection loops — always scoped to a submission
        // form_submission_values.field_type: used in getEmailValue() and bulkAnonymize()
        Schema::table('form_submission_values', function (Blueprint $table) {
            $table->index('field_key');
            $table->index('field_type');

            // Composite: the most common access pattern (submission + field_key lookup)
            $table->index(['submission_id', 'field_key'], 'form_submission_values_sub_key_index');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
        });

        Schema::table('form_submission_values', function (Blueprint $table) {
            $table->dropIndex(['field_key']);
            $table->dropIndex(['field_type']);
            $table->dropIndex('form_submission_values_sub_key_index');
        });
    }
};

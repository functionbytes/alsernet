<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (! $this->indexExists('form_submissions', 'form_submissions_form_is_spam_index')) {
                $table->index(['form_id', 'is_spam'], 'form_submissions_form_is_spam_index');
            }

            if (! $this->indexExists('form_submissions', 'form_submissions_form_assigned_index')) {
                $table->index(['form_id', 'assigned_to'], 'form_submissions_form_assigned_index');
            }

            if (! $this->indexExists('form_submissions', 'form_submissions_form_created_index')) {
                $table->index(['form_id', 'created_at'], 'form_submissions_form_created_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if ($this->indexExists('form_submissions', 'form_submissions_form_is_spam_index')) {
                $table->dropIndex('form_submissions_form_is_spam_index');
            }

            if ($this->indexExists('form_submissions', 'form_submissions_form_assigned_index')) {
                $table->dropIndex('form_submissions_form_assigned_index');
            }

            if ($this->indexExists('form_submissions', 'form_submissions_form_created_index')) {
                $table->dropIndex('form_submissions_form_created_index');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $indexName) {
                return true;
            }
        }

        return false;
    }
};

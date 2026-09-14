<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE supplier_ai_contents MODIFY COLUMN status ENUM(
            'pending_generation',
            'generating',
            'pending_validation',
            'in_review',
            'needs_revision',
            'validated',
            'published',
            'published_hidden',
            'rejected',
            'error_insufficient_info',
            'error_source_unavailable',
            'error_generation_failed'
        ) NOT NULL DEFAULT 'pending_generation'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE supplier_ai_contents MODIFY COLUMN status ENUM(
            'pending_generation',
            'generating',
            'pending_validation',
            'in_review',
            'needs_revision',
            'validated',
            'published',
            'rejected',
            'error_insufficient_info',
            'error_source_unavailable',
            'error_generation_failed'
        ) NOT NULL DEFAULT 'pending_generation'");
    }
};

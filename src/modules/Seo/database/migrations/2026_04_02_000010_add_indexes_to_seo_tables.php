<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            if (! Schema::hasIndex('seo_metas', 'seo_metas_seo_score_index')) {
                $table->index('seo_score');
            }
            if (! Schema::hasIndex('seo_metas', 'seo_metas_seo_grade_index')) {
                $table->index('seo_grade');
            }
            if (! Schema::hasIndex('seo_metas', 'seo_metas_seo_audited_at_index')) {
                $table->index('seo_audited_at');
            }
        });

        Schema::table('seo_audit_logs', function (Blueprint $table) {
            if (! Schema::hasIndex('seo_audit_logs', 'seo_audit_logs_audited_at_index')) {
                $table->index('audited_at');
            }
            if (! Schema::hasIndex('seo_audit_logs', 'seo_audit_logs_seo_meta_id_index')) {
                $table->index('seo_meta_id');
            }
        });

        Schema::table('seo_404_logs', function (Blueprint $table) {
            if (! Schema::hasIndex('seo_404_logs', 'seo_404_logs_hit_count_index')) {
                $table->index('hit_count');
            }
            if (! Schema::hasIndex('seo_404_logs', 'seo_404_logs_has_redirect_index')) {
                $table->index('has_redirect');
            }
            if (
                Schema::hasColumn('seo_404_logs', 'resolved_at') &&
                ! Schema::hasIndex('seo_404_logs', 'seo_404_logs_resolved_at_index')
            ) {
                $table->index('resolved_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('seo_metas', function (Blueprint $table) {
            if (Schema::hasIndex('seo_metas', 'seo_metas_seo_score_index')) {
                $table->dropIndex('seo_metas_seo_score_index');
            }
            if (Schema::hasIndex('seo_metas', 'seo_metas_seo_grade_index')) {
                $table->dropIndex('seo_metas_seo_grade_index');
            }
            if (Schema::hasIndex('seo_metas', 'seo_metas_seo_audited_at_index')) {
                $table->dropIndex('seo_metas_seo_audited_at_index');
            }
        });

        Schema::table('seo_audit_logs', function (Blueprint $table) {
            if (Schema::hasIndex('seo_audit_logs', 'seo_audit_logs_audited_at_index')) {
                $table->dropIndex('seo_audit_logs_audited_at_index');
            }
            if (Schema::hasIndex('seo_audit_logs', 'seo_audit_logs_seo_meta_id_index')) {
                $table->dropIndex('seo_audit_logs_seo_meta_id_index');
            }
        });

        Schema::table('seo_404_logs', function (Blueprint $table) {
            if (Schema::hasIndex('seo_404_logs', 'seo_404_logs_hit_count_index')) {
                $table->dropIndex('seo_404_logs_hit_count_index');
            }
            if (Schema::hasIndex('seo_404_logs', 'seo_404_logs_has_redirect_index')) {
                $table->dropIndex('seo_404_logs_has_redirect_index');
            }
            if (Schema::hasIndex('seo_404_logs', 'seo_404_logs_resolved_at_index')) {
                $table->dropIndex('seo_404_logs_resolved_at_index');
            }
        });
    }
};

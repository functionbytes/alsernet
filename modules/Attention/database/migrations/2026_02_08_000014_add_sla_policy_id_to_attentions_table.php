<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attentions', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->after('assigned_user_id')
                ->constrained('attention_sla_policies')->nullOnDelete();
            $table->index('sla_policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attentions', function (Blueprint $table) {
            $table->dropForeign(['sla_policy_id']);
            $table->dropColumn('sla_policy_id');
        });
    }
};

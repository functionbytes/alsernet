<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->index('assigned_to_user_id');
            $table->index('replied_at');
            $table->index('social_sla_policy_id');
            $table->index('social_conversation_id');
            $table->index('created_at');
        });

        Schema::table('helpdesk_social_accounts', function (Blueprint $table) {
            $table->index('crisis_mode_active');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->dropIndex(['assigned_to_user_id']);
            $table->dropIndex(['replied_at']);
            $table->dropIndex(['social_sla_policy_id']);
            $table->dropIndex(['social_conversation_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('helpdesk_social_accounts', function (Blueprint $table) {
            $table->dropIndex(['crisis_mode_active']);
        });
    }
};

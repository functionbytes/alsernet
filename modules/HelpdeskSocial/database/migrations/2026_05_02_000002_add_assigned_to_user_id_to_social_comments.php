<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete()->after('replied_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->dropForeign(['assigned_to_user_id']);
            $table->dropColumn('assigned_to_user_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->string('workflow_status', 16)->default('draft')->after('visibility');
            $table->foreignId('approved_by_user_id')->nullable()->after('workflow_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->index('workflow_status');
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropIndex(['workflow_status']);
            $table->dropConstrainedForeignId('approved_by_user_id');
            $table->dropColumn(['workflow_status', 'approved_at']);
        });
    }
};

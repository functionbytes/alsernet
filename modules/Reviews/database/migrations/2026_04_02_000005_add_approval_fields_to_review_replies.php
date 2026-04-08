<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_replies', function (Blueprint $table) {
            // Add pending_approval to the status enum (keeps scheduled from migration 4)
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'scheduled', 'published', 'failed'])
                ->default('draft')
                ->comment('Reply workflow status')
                ->change();

            $table->timestamp('approval_requested_at')->nullable()->after('approved_at')
                ->comment('When approval was requested');

            $table->foreignId('approval_requested_by')->nullable()->after('approval_requested_at')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User who requested approval');

            $table->text('approval_note')->nullable()->after('approval_requested_by')
                ->comment('Reason or notes for the approval request');

            $table->text('rejection_reason')->nullable()->after('approval_note')
                ->comment('Reason given by supervisor for rejecting the reply');

            $table->index('approval_requested_at');
            $table->index('approval_requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('review_replies', function (Blueprint $table) {
            $table->dropIndex(['approval_requested_at']);
            $table->dropIndex(['approval_requested_by']);
            $table->dropForeign(['approval_requested_by']);
            $table->dropColumn(['approval_requested_at', 'approval_requested_by', 'approval_note', 'rejection_reason']);

            $table->enum('status', ['draft', 'approved', 'published', 'failed'])
                ->default('draft')
                ->comment('Reply workflow status')
                ->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->unsignedBigInteger('sent_count')->default(0)->after('status');
            $table->unsignedBigInteger('open_count')->default(0)->after('sent_count');
            $table->unsignedBigInteger('click_count')->default(0)->after('open_count');
            $table->unsignedBigInteger('bounce_count')->default(0)->after('click_count');
            $table->unsignedBigInteger('unsubscribe_count')->default(0)->after('bounce_count');
            $table->unsignedBigInteger('feedback_count')->default(0)->after('unsubscribe_count');
            $table->unsignedBigInteger('failed_count')->default(0)->after('feedback_count');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropColumn([
                'sent_count',
                'open_count',
                'click_count',
                'bounce_count',
                'unsubscribe_count',
                'feedback_count',
                'failed_count',
            ]);
        });
    }
};

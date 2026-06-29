<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('review_replies', function (Blueprint $table) {
            $table->enum('status', ['draft', 'approved', 'scheduled', 'published', 'failed'])
                ->default('draft')
                ->change();

            $table->timestamp('scheduled_at')->nullable()->after('published_at')->comment('When reply is scheduled to be published');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('review_replies', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn('scheduled_at');

            $table->enum('status', ['draft', 'approved', 'published', 'failed'])
                ->default('draft')
                ->change();
        });
    }
};

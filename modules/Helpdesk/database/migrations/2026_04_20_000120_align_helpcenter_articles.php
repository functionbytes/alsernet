<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_helpcenter_articles', function (Blueprint $table) {
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'excerpt')) {
                $table->text('excerpt')->nullable()->after('content');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('active');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'author_id')) {
                $table->unsignedBigInteger('author_id')->nullable()->after('is_published');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('author_id');
            }
            if (! Schema::connection('helpdesk')->hasColumn('helpdesk_helpcenter_articles', 'meta_description')) {
                $table->string('meta_description', 500)->nullable()->after('published_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_helpcenter_articles', function (Blueprint $table) {
            $table->dropColumn(['excerpt', 'is_published', 'author_id', 'published_at', 'meta_description']);
        });
    }
};

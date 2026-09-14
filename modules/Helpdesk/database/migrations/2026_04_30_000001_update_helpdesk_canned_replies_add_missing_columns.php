<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_canned_replies', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('shortcut', 50)->nullable()->unique()->after('title');
            $table->text('body')->nullable()->after('shortcut');
            $table->text('html_body')->nullable()->after('body');
            $table->string('category', 100)->nullable()->after('html_body');
            $table->json('tags')->nullable()->after('category');
            $table->boolean('is_global')->default(false)->after('tags');

            $table->index('user_id');
            $table->index('category');
            $table->index('is_global');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_canned_replies', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['category']);
            $table->dropIndex(['is_global']);
            $table->dropColumn(['user_id', 'shortcut', 'body', 'html_body', 'category', 'tags', 'is_global']);
        });
    }
};

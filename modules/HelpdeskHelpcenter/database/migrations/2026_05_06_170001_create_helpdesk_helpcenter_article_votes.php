<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_helpcenter_article_votes')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_helpcenter_article_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('article_id');
            $table->string('cookie_id', 64)->nullable()->index();
            $table->string('ip_hash', 64)->nullable()->index();
            $table->tinyInteger('vote');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['article_id', 'cookie_id']);
            $table->index(['article_id', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_helpcenter_article_votes');
    }
};

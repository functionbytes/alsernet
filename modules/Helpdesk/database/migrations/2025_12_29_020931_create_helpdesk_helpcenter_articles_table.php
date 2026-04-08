<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_helpcenter_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->integer('order')->default(0);
            $table->integer('views_count')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('helpdesk_helpcenter_categories')->onDelete('cascade');

            $table->index('category_id');
            $table->index('order');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_helpcenter_articles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adapt faq_categories: old columns were id, uid, title, slug, available, timestamps
        if (Schema::hasTable('faq_categories') && Schema::hasColumn('faq_categories', 'title')) {
            Schema::table('faq_categories', function (Blueprint $table) {
                $table->dropColumn(['uid', 'slug']);
                $table->renameColumn('title', 'name');
                $table->text('description')->nullable()->after('name');
                $table->integer('order')->default(0)->after('description');
                $table->renameColumn('available', 'status');
            });

            // Change status from tinyint to varchar
            Schema::table('faq_categories', function (Blueprint $table) {
                $table->string('status', 60)->default('published')->change();
            });
        }

        // Adapt faqs: old columns were id, uid, title, slug, description, available, category_id, timestamps
        if (Schema::hasTable('faqs') && Schema::hasColumn('faqs', 'title')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->dropColumn(['uid', 'slug']);
                $table->renameColumn('title', 'question');
                $table->renameColumn('description', 'answer');
                $table->integer('order')->default(0)->after('category_id');
                $table->renameColumn('available', 'status');
            });

            // Change status from tinyint to varchar
            Schema::table('faqs', function (Blueprint $table) {
                $table->string('status', 60)->default('published')->change();
            });
        }
    }

    public function down(): void
    {
        // Revert faq_categories
        if (Schema::hasTable('faq_categories') && Schema::hasColumn('faq_categories', 'name')) {
            Schema::table('faq_categories', function (Blueprint $table) {
                $table->renameColumn('name', 'title');
                $table->renameColumn('status', 'available');
                $table->dropColumn(['description', 'order']);
                $table->char('uid', 36)->unique()->after('id');
                $table->string('slug')->nullable()->after('title');
            });

            Schema::table('faq_categories', function (Blueprint $table) {
                $table->boolean('available')->default(1)->change();
            });
        }

        // Revert faqs
        if (Schema::hasTable('faqs') && Schema::hasColumn('faqs', 'question')) {
            Schema::table('faqs', function (Blueprint $table) {
                $table->renameColumn('question', 'title');
                $table->renameColumn('answer', 'description');
                $table->renameColumn('status', 'available');
                $table->dropColumn('order');
                $table->char('uid', 36)->unique()->after('id');
                $table->string('slug')->after('description');
            });

            Schema::table('faqs', function (Blueprint $table) {
                $table->boolean('available')->default(1)->change();
            });
        }
    }
};

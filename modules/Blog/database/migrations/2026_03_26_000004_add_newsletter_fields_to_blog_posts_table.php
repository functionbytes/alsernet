<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->boolean('send_newsletter')->default(false)->after('status');
            $table->timestamp('newsletter_sent_at')->nullable()->after('send_newsletter');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(['send_newsletter', 'newsletter_sent_at']);
        });
    }
};

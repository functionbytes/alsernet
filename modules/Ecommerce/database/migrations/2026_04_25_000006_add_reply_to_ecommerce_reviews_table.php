<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_reviews', function (Blueprint $table): void {
            $table->text('reply')->nullable()->after('images');
            $table->timestamp('replied_at')->nullable()->after('reply');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_reviews', function (Blueprint $table): void {
            $table->dropColumn(['reply', 'replied_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_thresholds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // tickets_overdue, reviews_negative, forms_unread, attention_pending
            $table->json('conditions'); // {"hours": 24, "min_count": 1}
            $table->json('channels');  // ["mail", "database"]
            $table->json('recipients')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('cooldown_minutes')->default(60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_thresholds');
    }
};

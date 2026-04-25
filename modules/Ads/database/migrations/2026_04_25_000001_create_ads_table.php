<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 120)->unique();
            $table->string('status', 60)->default('published');
            $table->boolean('open_in_new_tab')->default(false);
            $table->date('expired_at')->nullable();
            $table->string('location', 120)->nullable();
            $table->string('image')->nullable();
            $table->string('tablet_image')->nullable();
            $table->string('mobile_image')->nullable();
            $table->string('url')->nullable();
            $table->unsignedBigInteger('clicked')->default(0);
            $table->integer('order')->default(0);
            $table->string('ads_type', 60)->default('image');
            $table->string('google_adsense_slot_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('helpdesk_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 36)->unique();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->unsignedTinyInteger('level')->unique(); // 1=Low, 2=Normal, 3=High, 4=Urgent, 5=Critical
            $table->string('color', 7);
            $table->unsignedSmallInteger('response_time_hours')->nullable();
            $table->unsignedSmallInteger('resolution_time_hours')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_priorities');
    }
};

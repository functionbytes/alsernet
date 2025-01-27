<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('livechat_flows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('liveshatflow')->nullable();
            $table->tinyInteger('active')->nullable()->default(0);
            $table->text('active_draft')->nullable();
            $table->string('responsename')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livechat_flows');
    }
};

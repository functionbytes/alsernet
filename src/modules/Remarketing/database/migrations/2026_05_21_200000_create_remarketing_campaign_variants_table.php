<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remarketing_campaign_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('remarketing_campaigns')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('subject', 255)->nullable();
            $table->string('preheader', 255)->nullable();
            $table->unsignedTinyInteger('weight')->default(50);
            $table->foreignId('template_id')->nullable()->constrained('remarketing_templates')->nullOnDelete();
            $table->unsignedBigInteger('sent')->default(0);
            $table->unsignedBigInteger('opened')->default(0);
            $table->unsignedBigInteger('clicked')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->boolean('is_winner')->default(false);
            $table->timestamps();

            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remarketing_campaign_variants');
    }
};

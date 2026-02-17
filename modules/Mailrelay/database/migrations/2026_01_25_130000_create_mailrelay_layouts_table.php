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
        Schema::create('mailrelay_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->enum('type', ['partial', 'layout', 'component'])->default('layout')->index();
            $table->text('content')->nullable()->comment('HTML content of the layout');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->boolean('is_default')->default(false)->comment('Default layout for templates');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mailrelay_layout_langs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailrelay_layout_id')->constrained('mailrelay_layouts')->cascadeOnDelete();
            $table->unsignedBigInteger('lang_id');
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['mailrelay_layout_id', 'lang_id'], 'mailrelay_layout_lang_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailrelay_layout_langs');
        Schema::dropIfExists('mailrelay_layouts');
    }
};

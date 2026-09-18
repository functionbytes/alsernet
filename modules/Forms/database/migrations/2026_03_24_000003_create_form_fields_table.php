<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('form_id');
            $table->string('key', 100);
            $table->string('label', 255);
            $table->enum('type', [
                'text', 'email', 'tel', 'number', 'url', 'textarea',
                'select', 'checkbox', 'radio', 'file',
                'date', 'time', 'datetime', 'hidden',
                'rating', 'calculation', 'signature', 'nps', 'likert', 'slider',
                'image_choice', 'rich_text', 'address',
                'section_header', 'html_block', 'divider', 'spacer',
                'consent', 'newsletter_consent',
            ]);
            $table->string('placeholder', 255)->nullable();
            $table->text('default_value')->nullable();
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('conditions')->nullable();
            $table->json('logic_jumps')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->enum('width', ['full', 'half', 'third', 'quarter'])->default('full');
            $table->integer('sort_order')->default(0);
            $table->unsignedTinyInteger('step_number')->default(1);
            $table->string('help_text', 500)->nullable();
            $table->boolean('show_char_counter')->default(false);
            $table->enum('label_position', ['top', 'floating', 'hidden'])->default('top');
            $table->string('formula', 500)->nullable();
            $table->decimal('min_value', 10, 2)->nullable();
            $table->decimal('max_value', 10, 2)->nullable();
            $table->decimal('step_value', 10, 2)->nullable();
            $table->unsignedInteger('mailrelay_group_id')->nullable();
            $table->string('auto_populate_param', 100)->nullable();
            $table->text('html_content')->nullable();
            $table->json('likert_rows')->nullable();
            $table->text('consent_text')->nullable();
            $table->timestamps();

            $table->unique(['form_id', 'key']);
            $table->index('form_id');
            $table->index('sort_order');
            $table->index('step_number');

            $table->foreign('form_id')
                ->references('id')
                ->on('forms')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};

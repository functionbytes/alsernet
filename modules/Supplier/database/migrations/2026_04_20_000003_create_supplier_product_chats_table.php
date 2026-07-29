<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_product_chats')) {
            Schema::create('supplier_product_chats', function (Blueprint $table) {
                $table->id();
                $table->ulid('uid')->unique();
                $table->foreignId('product_id')->constrained('supplier_products')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title', 255)->nullable();
                $table->string('model', 60)->default('gpt-4o-mini');
                $table->boolean('web_search_enabled')->default(false);
                $table->string('prompt_uid', 26)->nullable();
                $table->decimal('total_cost', 10, 6)->default(0);
                $table->unsignedInteger('total_tokens')->default(0);
                $table->unsignedSmallInteger('messages_count')->default(0);
                $table->foreignId('saved_content_id')->nullable()->constrained('supplier_ai_contents')->nullOnDelete();
                $table->timestamps();

                $table->index(['product_id', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('supplier_product_chat_messages')) {
            Schema::create('supplier_product_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('chat_id')->constrained('supplier_product_chats')->cascadeOnDelete();
                $table->enum('role', ['system', 'user', 'assistant']);
                $table->longText('content');
                $table->json('sources')->nullable();
                $table->boolean('web_search_used')->default(false);
                $table->unsignedInteger('input_tokens')->default(0);
                $table->unsignedInteger('output_tokens')->default(0);
                $table->decimal('cost', 10, 6)->default(0);
                $table->string('model', 60)->nullable();
                $table->unsignedInteger('latency_ms')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['chat_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_product_chat_messages');
        Schema::dropIfExists('supplier_product_chats');
    }
};

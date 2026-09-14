<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $s = Schema::connection($this->connection);

        if ($s->hasTable('helpdesk_macros')) {
            return;
        }

        $s->create('helpdesk_macros', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->text('description')->nullable();
            $t->json('actions');
            $t->boolean('is_shared')->default(true)->index();
            $t->foreignId('user_id')->nullable();
            $t->integer('usage_count')->default(0);
            $t->timestamp('last_used_at')->nullable();
            $t->boolean('is_active')->default(true)->index();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_macros');
    }
};

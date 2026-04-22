<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('helpdesk_group_user') && ! $schema->hasColumn('helpdesk_group_user', 'updated_at')) {
            $schema->table('helpdesk_group_user', fn (Blueprint $t) => $t->timestamp('updated_at')->nullable());
        }

        // Create helpdesk_tags if missing (conversation/ticket tags master table)
        if (! $schema->hasTable('helpdesk_tags')) {
            $schema->create('helpdesk_tags', function (Blueprint $t) {
                $t->id();
                $t->string('name');
                $t->string('slug')->unique();
                $t->string('color', 32)->nullable();
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true)->index();
                $t->integer('order')->default(0)->index();
                $t->timestamps();
                $t->softDeletes();
            });
        }
    }

    public function down(): void {}
};

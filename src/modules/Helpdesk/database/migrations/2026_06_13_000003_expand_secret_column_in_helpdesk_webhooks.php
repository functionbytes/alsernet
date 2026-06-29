<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->table('helpdesk_webhooks', function (Blueprint $table) {
            $table->text('secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_webhooks', function (Blueprint $table) {
            $table->string('secret', 64)->nullable()->change();
        });
    }
};

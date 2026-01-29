<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('acelle')->create('mailing_layouts', function (Blueprint $table) {
            $table->id();
            $table->char('uid', 36);
            $table->char('alias', 191);
            $table->char('group_name', 191);
            $table->longText('content');
            $table->char('type', 191);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->char('subject', 191);
        });
    }

    public function down(): void
    {
        Schema::connection('acelle')->dropIfExists('mailing_layouts');
    }
};

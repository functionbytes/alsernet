<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection('helpdesk')->hasColumn('helpdesk_attributes', 'model_type')) {
            return;
        }

        Schema::connection('helpdesk')->table('helpdesk_attributes', function (Blueprint $table) {
            $table->string('model_type', 32)->default('contact')->after('type')->index();
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->table('helpdesk_attributes', function (Blueprint $table) {
            $table->dropColumn('model_type');
        });
    }
};

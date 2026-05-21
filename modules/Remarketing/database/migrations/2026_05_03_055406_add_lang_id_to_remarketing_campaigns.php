<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remarketing_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('remarketing_campaigns', 'mailer_template_id')) {
                $table->unsignedBigInteger('lang_id')
                    ->nullable()
                    ->after('mailer_template_id')
                    ->comment('Idioma de envío vinculado a mailer_template_langs');
            } else {
                $table->unsignedBigInteger('lang_id')
                    ->nullable()
                    ->comment('Idioma de envío vinculado a mailer_template_langs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('remarketing_campaigns', function (Blueprint $table) {
            $table->dropColumn('lang_id');
        });
    }
};

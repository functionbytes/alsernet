<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forms — formularios de suscripción. Portado de acellemail (App\Model\Form).
 * Cada form envuelve un Template (editable con el builder, kind=form) y está
 * ligado a una lista (campaign_maillists) donde aterrizan los suscriptores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_forms', function (Blueprint $table): void {
            $table->id();
            $table->char('uid', 36)->unique();
            $table->string('name');
            $table->foreignId('mail_list_id')->nullable()
                ->constrained('campaign_maillists')->nullOnDelete();
            $table->foreignId('template_id')->nullable()
                ->constrained('campaign_templates')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_forms');
    }
};

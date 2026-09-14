<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una fila por día: la "campaña del día" que agrupa los correos de cumpleaños.
 *
 * El UNIQUE sobre campaign_date es la garantía de idempotencia del comando
 * helpdeskbirthday:prepare — relanzarlo no crea una segunda campaña ni provoca
 * un doble envío.
 *
 * El cupón se congela aquí (código + validez + importe) en el momento de
 * preparar la campaña, en vez de leerse de la config al enviar: así lo que se
 * envió queda auditable aunque luego cambien los ajustes.
 */
return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        if (Schema::connection($this->connection)->hasTable('helpdesk_birthday_campaigns')) {
            return;
        }

        Schema::connection($this->connection)->create('helpdesk_birthday_campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->date('campaign_date')->unique();
            $table->string('status', 20)->default('draft');

            // Cupón congelado en el momento de preparar la campaña.
            $table->string('coupon_code')->nullable();
            $table->date('coupon_valid_from')->nullable();
            $table->date('coupon_valid_to')->nullable();
            $table->decimal('coupon_amount', 10, 2)->nullable();
            $table->decimal('coupon_min_purchase', 10, 2)->nullable();
            $table->string('coupon_source', 10)->default('manual'); // manual|erp
            $table->json('coupon_meta')->nullable();

            $table->string('template_key')->nullable();

            // Ventana y ritmo calculado para este día concreto.
            $table->time('window_start')->nullable();
            $table->time('window_end')->nullable();
            $table->unsignedInteger('throttle_per_hour')->nullable();
            $table->unsignedInteger('interval_seconds')->nullable();

            $table->unsignedInteger('recipients_total')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'campaign_date']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_birthday_campaigns');
    }
};

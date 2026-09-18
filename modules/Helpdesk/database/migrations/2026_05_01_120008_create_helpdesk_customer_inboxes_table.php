<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection('helpdesk')->create('helpdesk_customer_inboxes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_id')->index('hd_customer_inboxes_customer_id_index');
            $table->unsignedBigInteger('inbox_id')->index('hd_customer_inboxes_inbox_id_index');
            $table->string('source_id')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'inbox_id'], 'hd_customer_inboxes_customer_inbox_unique');

            $table->foreign('customer_id')
                ->references('id')
                ->on('helpdesk_customers')
                ->onDelete('cascade');

            $table->foreign('inbox_id')
                ->references('id')
                ->on('helpdesk_inboxes')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('helpdesk')->dropIfExists('helpdesk_customer_inboxes');
    }
};

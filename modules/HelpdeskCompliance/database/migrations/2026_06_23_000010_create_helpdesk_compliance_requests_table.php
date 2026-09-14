<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_compliance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('type'); // export | delete_soft | delete_hard
            $table->string('status')->default('completed'); // pending | processing | completed | failed
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->json('modules_affected')->nullable();
            $table->json('result_summary')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_compliance_requests');
    }
};

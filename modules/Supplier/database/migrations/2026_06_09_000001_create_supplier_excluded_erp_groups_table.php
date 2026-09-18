<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_excluded_erp_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('erp_id')->unique()->comment('idgrupo_cl / idsubfamilia_cl / idfamilia_cl del ERP Oracle');
            $table->string('label')->nullable()->comment('Descripción legible, opcional');
            $table->string('reason')->nullable()->comment('Motivo de la exclusión');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_excluded_erp_groups');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaFilesTable extends Migration
{
    public function up()
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('file_url');
            $table->unsignedBigInteger('folder_id')->nullable();
            $table->timestamps();

            // Relación con la tabla de carpetas de medios
            $table->foreign('folder_id')->references('id')->on('media_folders')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('media_files');
    }
}

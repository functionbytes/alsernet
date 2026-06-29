<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailTemplatesTable extends Migration
{
    public function up()
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre de la plantilla
            $table->text('html_content'); // Contenido HTML de la plantilla
            $table->text('text_content'); // Contenido de texto (alternativo al HTML)
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_templates');
    }
}

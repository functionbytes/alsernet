<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support MODIFY COLUMN — only needed for MariaDB/MySQL
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE form_fields MODIFY COLUMN type ENUM(
            'text','email','tel','number','url','textarea',
            'select','checkbox','radio',
            'file',
            'date','time','datetime',
            'hidden','rating','calculation','signature',
            'nps','likert','slider','image_choice','rich_text',
            'address',
            'section_header','html_block','divider','spacer',
            'consent','newsletter_consent',
            'color_picker'
        ) NOT NULL DEFAULT 'text'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE form_fields MODIFY COLUMN type ENUM(
            'text','email','tel','number','url','textarea',
            'select','checkbox','radio',
            'file',
            'date','time','datetime',
            'hidden','rating','calculation','signature',
            'nps','likert','slider','image_choice','rich_text',
            'address',
            'section_header','html_block','divider','spacer',
            'consent','newsletter_consent'
        ) NOT NULL DEFAULT 'text'");
    }
};

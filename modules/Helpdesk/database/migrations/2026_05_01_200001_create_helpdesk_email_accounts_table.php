<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_email_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();

            // IMAP (inbound)
            $table->string('imap_host')->nullable();
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->string('imap_username')->nullable();
            $table->text('imap_password')->nullable();
            $table->string('imap_folder')->default('INBOX');
            $table->string('imap_encryption')->default('ssl');

            // SMTP (outbound)
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->default(587);
            $table->string('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();
            $table->string('smtp_from_name')->nullable();
            $table->string('smtp_encryption')->default('tls');

            $table->timestamp('last_sync_at')->nullable();
            $table->boolean('is_active')->default(true);

            // Optional link to an inbox
            $table->unsignedBigInteger('inbox_id')->nullable()->index();

            $table->timestamps();

            $table->index('is_active');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('helpdesk_email_accounts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'helpdesk';

    public function up(): void
    {
        Schema::connection($this->connection)->create('helpdesk_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable(); // hostname for auto-detect
            $table->string('primary_color', 7)->default('#90bb13');
            $table->string('logo_url')->nullable();
            $table->string('email_from_name')->nullable();
            $table->string('email_from_address')->nullable();
            $table->string('widget_token')->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('domain');
            $table->index('is_active');
        });

        Schema::connection($this->connection)->table('helpdesk_inboxes', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('id');
            $table->index('brand_id');
        });

        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('brand_id')->nullable()->after('inbox_id');
            $table->index('brand_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('helpdesk_conversations', function (Blueprint $table) {
            $table->dropIndex(['brand_id']);
            $table->dropColumn('brand_id');
        });

        Schema::connection($this->connection)->table('helpdesk_inboxes', function (Blueprint $table) {
            $table->dropIndex(['brand_id']);
            $table->dropColumn('brand_id');
        });

        Schema::connection($this->connection)->dropIfExists('helpdesk_brands');
    }
};

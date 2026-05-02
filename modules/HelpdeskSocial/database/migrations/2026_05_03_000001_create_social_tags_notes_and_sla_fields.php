<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tags for social comments
        Schema::create('helpdesk_social_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#b10100'); // hex color
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('helpdesk_social_comment_tag', function (Blueprint $table) {
            $table->foreignId('social_comment_id')->constrained('helpdesk_social_comments')->cascadeOnDelete();
            $table->foreignId('social_tag_id')->constrained('helpdesk_social_tags')->cascadeOnDelete();
            $table->foreignId('tagged_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['social_comment_id', 'social_tag_id']);
        });

        // Internal notes on comments
        Schema::create('helpdesk_social_comment_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_comment_id')->constrained('helpdesk_social_comments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('type', 16)->default('internal'); // internal, public
            $table->timestamps();

            $table->index('social_comment_id');
        });

        // SLA policies
        Schema::create('helpdesk_social_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('response_time_minutes')->default(120); // first response
            $table->unsignedInteger('resolution_time_minutes')->nullable(); // total resolution
            $table->string('platform', 32)->nullable(); // null = all platforms
            $table->string('priority', 16)->nullable(); // null = all priorities
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable(); // extra JSON conditions
            $table->timestamps();
        });

        // SLA fields on comments
        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->timestamp('first_response_at')->nullable()->after('replied_at');
            $table->timestamp('sla_response_deadline')->nullable()->after('first_response_at');
            $table->timestamp('sla_resolution_deadline')->nullable()->after('sla_response_deadline');
            $table->boolean('sla_response_breached')->default(false)->after('sla_resolution_deadline');
            $table->boolean('sla_resolution_breached')->default(false)->after('sla_response_breached');
            $table->foreignId('social_sla_policy_id')->nullable()->after('social_account_id')->constrained('helpdesk_social_sla_policies')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('helpdesk_social_comments', function (Blueprint $table) {
            $table->dropForeign(['social_sla_policy_id']);
            $table->dropColumn([
                'first_response_at',
                'sla_response_deadline',
                'sla_resolution_deadline',
                'sla_response_breached',
                'sla_resolution_breached',
                'social_sla_policy_id',
            ]);
        });

        Schema::dropIfExists('helpdesk_social_sla_policies');
        Schema::dropIfExists('helpdesk_social_comment_notes');
        Schema::dropIfExists('helpdesk_social_comment_tag');
        Schema::dropIfExists('helpdesk_social_tags');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name', 150);
            $table->string('slug', 150)->unique();
            $table->text('description')->nullable();
            $table->text('success_message')->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->string('notify_admin_email', 500)->nullable();

            // Email notifications
            $table->boolean('send_confirmation')->default(false);
            $table->string('email_field_key', 100)->nullable();
            $table->unsignedBigInteger('admin_template_id')->nullable();
            $table->unsignedBigInteger('confirmation_template_id')->nullable();

            // Visibility & access
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_multiple')->default(true);
            $table->boolean('honeypot_enabled')->default(true);
            $table->boolean('captcha_enabled')->default(false);
            $table->boolean('is_password_protected')->default(false);
            $table->string('password', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_submissions')->nullable();
            $table->boolean('prevent_duplicate_email')->default(false);
            $table->enum('access_control', ['public', 'authenticated', 'roles'])->default('public');
            $table->json('allowed_roles')->nullable();

            // Data management
            $table->unsignedInteger('retention_days')->nullable();
            $table->boolean('limit_per_user')->default(false);

            // Webhooks
            $table->string('webhook_url', 500)->nullable();
            $table->string('webhook_secret', 255)->nullable();

            // Multi-step
            $table->boolean('is_multi_step')->default(false);
            $table->json('steps_config')->nullable();

            // Appearance
            $table->string('theme', 50)->default('default');
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();
            $table->json('style_config')->nullable();

            // Button
            $table->string('button_text', 100)->default('Enviar');
            $table->enum('button_position', ['left', 'center', 'right', 'full'])->default('left');
            $table->enum('button_size', ['sm', 'md', 'lg'])->default('md');
            $table->string('button_variant', 50)->default('primary');
            $table->string('button_icon', 50)->nullable();

            // UX
            $table->enum('success_animation', ['none', 'fade', 'checkmark', 'confetti'])->default('fade');
            $table->enum('progress_bar_style', ['bar', 'dots', 'steps', 'percentage'])->default('bar');
            $table->boolean('floating_label')->default(false);

            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
            $table->index('category_id');

            $table->foreign('category_id')
                ->references('id')
                ->on('form_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};

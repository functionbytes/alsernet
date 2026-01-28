<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailListsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('mail_lists', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('uid');
            $table->integer('customer_id')->unsigned();
            $table->integer('contact_id')->unsigned();
            $table->string('name');
            $table->string('default_subject');
            $table->renameColumn('description', 'remind_message');
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->text('description')->nullable();
            $table->integer('custom_order')->default(0);
            $table->text('email_subscribe')->nullable();
            $table->text('email_unsubscribe')->nullable();
            $table->text('email_daily')->nullable();
            $table->boolean('send_welcome_email')->default(false);
            $table->boolean('unsubscribe_notification')->default(false);
            $table->string('status');

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::drop('mail_lists');
    }
}

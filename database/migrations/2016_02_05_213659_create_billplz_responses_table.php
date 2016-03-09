<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBillplzResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billplz_responses', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->string('billplz_id');
            $table->string('collection_id');
            $table->integer('paid');
            $table->string('state');
            $table->string('amount');
            $table->integer('paid_amount');
            $table->timestamp('due_at');
            $table->string('email');
            $table->string('mobile');
            $table->string('name');

            $table->integer('metadata_order_id')->unsigned();
            $table->foreign('metadata_order_id')->references('id')->on('orders');

            $table->integer('metadata_user_id')->unsigned();
            $table->foreign('metadata_user_id')->references('id')->on('users');

            $table->integer('metadata_site_id')->unsigned();
            $table->foreign('metadata_site_id')->references('id')->on('sites');

            $table->string('url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('billplz_responses');
    }

}

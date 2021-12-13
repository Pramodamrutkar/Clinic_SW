<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmartListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smart_lists', function (Blueprint $table) {
            $table->id('smart_list_code');
            $table->foreignId('dgcode')->references('dg_code')->on('smart_list_data_groups');
            $table->foreignId('lang_code')->references('l_code')->on('languages');
            $table->foreignId('datacode')->references('data_code')->on('smart_list_data');
            $table->tinyInteger('status')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('smart_lists');
    }
}

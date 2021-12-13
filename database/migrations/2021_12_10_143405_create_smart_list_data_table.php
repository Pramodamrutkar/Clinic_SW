<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmartListDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smart_list_data', function (Blueprint $table) {
            $table->id('data_code');
            $table->foreignId('lang_code')->references('l_code')->on('languages');
            $table->string('data_sdesc')->nullable(false);
            $table->string('data_ldesc')->nullable(false);
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
        Schema::dropIfExists('smart_list_data');
    }
}

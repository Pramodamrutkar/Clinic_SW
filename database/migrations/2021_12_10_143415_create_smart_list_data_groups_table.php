<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmartListDataGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('smart_list_data_groups', function (Blueprint $table) {
            $table->id('dg_code');
            $table->foreignId('lang_code')->references('l_code')->on('languages');
            $table->string('dg_desc')->nullable(false);
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
        Schema::dropIfExists('smart_list_data_groups');
    }
}

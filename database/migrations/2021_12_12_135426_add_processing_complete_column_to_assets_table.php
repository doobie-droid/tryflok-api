<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProcessingCompleteColumnToAssetsTable extends Migration
{
    public function up()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->boolean('processing_complete')->default(0);
        });
    }

    public function down()
    {
        Schema::table('assets', function (Blueprint $table) {
            //
        });
    }
}

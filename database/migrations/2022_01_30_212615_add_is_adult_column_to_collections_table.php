<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAdultColumnToCollectionsTable extends Migration
{
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('is_adult')->default(0);
        });
    }

    public function down()
    {
        Schema::table('collections', function (Blueprint $table) {
            //
        });
    }
}

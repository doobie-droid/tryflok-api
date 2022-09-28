<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAdultColumnToContentsTable extends Migration
{
    public function up()
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->boolean('is_adult')->default(0);
        });
    }

    public function down()
    {
        Schema::table('contents', function (Blueprint $table) {
            //
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropViewsColumnAddScheduleDateAndLiveStatusColumnsToContentsTable extends Migration
{
    public function up()
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dateTime('scheduled_date')->nullable();
            $table->string('live_status')->nullable();
            $table->dropColumn('views');
        });
    }

    public function down()
    {
        Schema::table('contents', function (Blueprint $table) {
            //
        });
    }
}

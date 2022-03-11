<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLiveEndedAtToContentsTable extends Migration
{
    public function up()
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dateTime('live_ended_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('contents', function (Blueprint $table) {
            //
        });
    }
}

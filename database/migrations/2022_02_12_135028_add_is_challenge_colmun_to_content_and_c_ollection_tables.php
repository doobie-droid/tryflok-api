<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsChallengeColmunToContentAndCOllectionTables extends Migration
{
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->boolean('is_challenge')->default(0);
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->boolean('is_challenge')->default(0);
        });
    }

    public function down()
    {
        Schema::table('content_and_c_ollection_tables', function (Blueprint $table) {
            //
        });
    }
}

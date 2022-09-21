<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrendingPointsToCollectionAndContentTables extends Migration
{
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->integer('trending_points')->default(0);
        });

        Schema::table('contents', function (Blueprint $table) {
            $table->integer('trending_points')->default(0);
        });
    }

    public function down()
    {
        Schema::table('collection_and_content_tables', function (Blueprint $table) {
            //
        });
    }
}

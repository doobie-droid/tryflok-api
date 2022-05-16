<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArchivedAtColumnToCollectionsTable extends Migration
{
    public function up()
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dateTime('archived_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('collections', function (Blueprint $table) {
            //
        });
    }
}

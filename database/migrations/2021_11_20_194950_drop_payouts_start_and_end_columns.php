<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPayoutsStartAndEndColumns extends Migration
{
    public function up()
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn('start');
            $table->dropColumn('end');
        });
    }

    public function down()
    {
        //
    }
}

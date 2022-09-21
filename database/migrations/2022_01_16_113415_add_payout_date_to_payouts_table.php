<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayoutDateToPayoutsTable extends Migration
{
    public function up()
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dateTime('payout_date')->nullable();
        });
    }

    public function down()
    {
        Schema::table('payouts', function (Blueprint $table) {
            //
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastPaymentRequestColumnToPayoutsTable extends Migration
{
    public function up()
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dateTime('last_payment_request')->nullable();
        });
    }

    public function down()
    {
        Schema::table('payouts', function (Blueprint $table) {
            //
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotificationColumnsToPayoutsTable extends Migration
{
    public function up()
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->integer('cashout_attempts')->default(0);
            $table->boolean('cancelled_by_admin')->default(0);
            $table->dateTime('failed_notification_sent')->nullable();
        });
    }

    public function down()
    {
        Schema::table('payouts', function (Blueprint $table) {
            //
        });
    }
}

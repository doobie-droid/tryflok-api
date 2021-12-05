<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayoutsTable extends Migration
{
    public function up()
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id'); // user to be paid to
            $table->dateTime('start'); // start of interval summation
            $table->dateTime('end'); // end of interval summation
            $table->decimal('amount', 6, 2); // amount to be paid
            $table->string('currency')->default('USD');
            $table->boolean('claimed')->default(0); // has a successful payout been made?
            $table->string('handler')->nullable();//manual,flutterwave,stripe,paypal
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payouts');
    }
}

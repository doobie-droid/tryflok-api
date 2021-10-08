<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payer_id');//person that paid
            $table->foreignUuid('payee_id')->nullable();//person that owns entity being paid for
            $table->decimal('amount', 6,2)->default(0);
            $table->unsignedDecimal('payment_processor_fee', $precision = 5, $scale = 2);
            $table->string('currency')->default('USD');
            $table->mediumText('description')->nullable();
            $table->string('provider');
            $table->string('provider_id');
            $table->morphs('paymentable'); // entity that is being paid for
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('saleable');
            $table->foreignUuid('user_id');
            $table->unsignedDecimal('amount', 6, 2);
            $table->string('currency')->default('USD');
            $table->unsignedDecimal('payment_processor_fee', 6, 2);
            $table->unsignedDecimal('platform_share', 6, 2); // (amount - fee) * 30%
            $table->unsignedDecimal('benefactor_share', 6, 2); // ((amount - fee) * 70%) * (share_on_benefactor_table / 100)
            $table->unsignedDecimal('referral_bonus', 5, 2); // platform_share * 2.5%
            $table->boolean('added_to_payout')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sales');
    }
}

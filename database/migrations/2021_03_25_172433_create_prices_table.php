<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('priceable');
            $table->decimal('amount', 6,2)->default(0);
            $table->string('currency')->default('USD');
            $table->string('interval')->default('one-off'); // one-off, monthly , yearly
            $table->integer('interval_amount')->default(1);
            $table->foreignUuid('continent_id')->nullable();
            $table->foreignUuid('country_id')->nullable();
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
        Schema::dropIfExists('prices');
    }
}

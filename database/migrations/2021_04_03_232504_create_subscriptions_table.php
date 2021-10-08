<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('userable_id'); // person that paid for subscription
            $table->foreignUuid('price_id'); // check if the price is still a subscription model
            $table->uuidMorphs('subscriptionable'); // entity being subscribed to
            $table->string('status')->default('active'); // active, disabled
            $table->boolean('auto_renew')->default(1);
            $table->dateTime('start');
            $table->dateTime('end');
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
        Schema::dropIfExists('subscriptions');
    }
}

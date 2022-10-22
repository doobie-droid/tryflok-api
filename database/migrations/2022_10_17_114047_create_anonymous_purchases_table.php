<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnonymousPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('anonymous_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->string('access_token')->unique();
            $table->string('anonymous_purchaseable_type');
            $table->uuid('anonymous_purchaseable_id');
            $table->string('status')->default('available'); // wishlist, available, subscription-ended, content-deleted
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
        Schema::dropIfExists('anonymous_purchases');
    }
}

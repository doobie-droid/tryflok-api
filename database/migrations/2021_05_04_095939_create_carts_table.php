<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('refunded')->default(0);
            $table->string('refund_status')->nullable();
            $table->boolean('checked_out')->default(0);
            $table->uuidMorphs('cartable');
            $table->string('status')->default('in-cart'); // in-cart, completed, cancelled
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
}

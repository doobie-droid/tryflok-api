<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTipsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_tips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tipper_user_id')->nullable();
            $table->string('tipper_email');
            $table->foreignUuid('tippee_user_id');
            $table->unsignedInteger('amount_in_flk');
            $table->string('tip_frequency');
            $table->string('status')->default('active');
            $table->foreignUuid('originating_content_id')->nullable();
            $table->string('originating_currency')->default('NGN');
            $table->string('originating_client_source')->default('ios');
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
        Schema::dropIfExists('user_tips');
    }
}

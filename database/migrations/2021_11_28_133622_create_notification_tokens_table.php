<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTokensTable extends Migration
{
    public function up()
    {
        Schema::create('notification_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id');//person that owns entities
            $table->string('provider')->default('firebase');
            $table->string('token');
            $table->string('status')->default('active');//inactive
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_tokens');
    }
}

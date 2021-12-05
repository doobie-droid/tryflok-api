<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->mediumText('bio')->nullable();
            $table->date('dob')->nullable();
            $table->string('referral_id')->unique();
            $table->string('email_token', 500)->nullable();
            $table->boolean('email_verified')->default(0);
            $table->string('password');
            $table->string('password_token', 500)->nullable();
            $table->foreignUuid('referrer_id')->nullable();
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}

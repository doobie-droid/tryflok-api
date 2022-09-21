<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentChallengeContributorsTable extends Migration
{
    public function up()
    {
        Schema::create('content_challenge_contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_id');
            $table->foreignUuid('user_id');
            $table->unsignedDecimal('amount', 9, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_challenge_contributors');
    }
}

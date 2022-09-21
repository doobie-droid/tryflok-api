<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentChallengeVotesTable extends Migration
{
    public function up()
    {
        Schema::create('content_challenge_votes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_id');
            $table->foreignUuid('voter_id');
            $table->foreignUuid('contestant_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_challenge_votes');
    }
}

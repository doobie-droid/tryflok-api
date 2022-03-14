<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAgoraIdColumnToContentChallengeContestantsTable extends Migration
{
    public function up()
    {
        Schema::table('content_challenge_contestants', function (Blueprint $table) {
            $table->string('agora_uid')->nullable();
        });
    }

    public function down()
    {
        Schema::table('content_challenge_contestants', function (Blueprint $table) {
            //
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropAgoraUidFromContentChallengeContestantsTable extends Migration
{
    public function up()
    {
        Schema::table('content_challenge_contestants', function (Blueprint $table) {
            $table->dropColumn('agora_uid');
        });
    }

    public function down()
    {
        Schema::table('content_challenge_contestants', function (Blueprint $table) {
            //
        });
    }
}

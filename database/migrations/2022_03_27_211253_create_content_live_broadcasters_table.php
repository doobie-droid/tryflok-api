<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentLiveBroadcastersTable extends Migration
{
    public function up()
    {
        Schema::create('content_live_broadcasters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_id');
            $table->foreignUuid('user_id');
            $table->string('agora_uid')->nullable();
            $table->string('video_stream_status')->default('inactive');
            $table->string('audio_stream_status')->default('inactive');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_live_broadcasters');
    }
}

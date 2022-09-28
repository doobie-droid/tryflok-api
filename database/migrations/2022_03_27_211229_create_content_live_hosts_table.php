<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentLiveHostsTable extends Migration
{
    public function up()
    {
        Schema::create('content_live_hosts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_id');
            $table->foreignUuid('user_id');
            $table->string('designation');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('content_live_hosts');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentPollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_polls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('question');
            $table->dateTime('closes_at');
            $table->timestamps();
            $table->foreignUuid('user_id'); //creator of the poll  
            $table->foreignUuid('content_id'); // id of the content
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_polls');
    }
}

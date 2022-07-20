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
            $table->datetime('closes_at');
            $table->tinyInteger('is_closed');
            $table->timestamps();
            $table->foreignUuid('user_id'); //creator of the poll  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('polls');
    }
}

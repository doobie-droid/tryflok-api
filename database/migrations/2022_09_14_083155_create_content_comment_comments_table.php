<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentCommentCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('content_comment_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id');//person writing comment
            $table->mediumText('comment')->nullable();
            $table->foreignUuid('content_comment_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('content_comment_comments');
    }
}

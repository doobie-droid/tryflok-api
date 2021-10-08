<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->mediumText('description')->nullable();
            $table->foreignUuid('user_id');//uploader of book
            $table->string('type'); // book, audio, video, news-letter
            $table->boolean('is_available')->default(0);
            $table->boolean('approved_by_admin')->default(0);
            $table->boolean('show_only_in_collections')->default(0);
            $table->boolean('show_only_in_digiverses')->default(1);
            $table->unsignedBigInteger('views')->default(0);
            $table->softDeletes();
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
        Schema::dropIfExists('contents');
    }
}
